<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $templates = \App\Models\WhatsappTemplate::with('user')->latest()->get();
        return view('admin.whatsapp-templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        if (!can('whatsapp-templates.global')) {
            abort(403, 'Only authorized users with global access can create templates.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,image,video,pdf',
            'message_text' => 'nullable|string',
            'media_files' => 'nullable|array',
            'media_files.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,3gp,pdf|max:10240', // 10MB max per file
        ]);

        $data = $request->only(['name', 'template_code', 'type', 'message_text']);
        $data['user_id'] = auth()->id();

        $uploadedMedia = [];
        $newFilesByOriginalName = [];
        
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('whatsapp_media', 'public');
                    // Store temporarily keyed by original name to allow sorting later
                    $newFilesByOriginalName[$file->getClientOriginalName()] = [
                        'path' => $path,
                        'type' => $data['type'],
                        'name' => $file->getClientOriginalName()
                    ];
                }
            }
        }

        // Apply structural order from UI SortableJS
        $structuralOrder = $request->structural_order ? json_decode($request->structural_order, true) : null;
        
        if (is_array($structuralOrder)) {
            foreach ($structuralOrder as $item) {
                if ($item['source'] === 'new' && isset($newFilesByOriginalName[$item['identifier']])) {
                    $uploadedMedia[] = $newFilesByOriginalName[$item['identifier']];
                }
            }
        } else {
            // Fallback if no structural order provided
            $uploadedMedia = array_values($newFilesByOriginalName);
        }

        $data['media_files'] = empty($uploadedMedia) ? null : $uploadedMedia;

        \App\Models\WhatsappTemplate::create($data);

        return redirect()->back()->with('success', 'Template created successfully.');
    }

    public function update(Request $request, $id)
    {
        $template = \App\Models\WhatsappTemplate::findOrFail($id);

        if (!can('whatsapp-templates.global') && $template->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,image,video,pdf',
            'message_text' => 'nullable|string',
            'media_files' => 'nullable|array',
            'media_files.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,3gp,pdf|max:10240',
            'existing_media' => 'nullable|string', // JSON string from frontend defining kept existing files
        ]);

        $data = $request->only(['name', 'template_code', 'type', 'message_text']);

        // Existing files kept by user (these are complete media details)
        $existingMedia = $request->existing_media ? json_decode($request->existing_media, true) : [];
        if (!is_array($existingMedia)) $existingMedia = [];

        // Identify files that were removed and delete from disk
        $oldFiles = $template->media_files ?? [];
        $keptPaths = array_column($existingMedia, 'path');
        foreach ($oldFiles as $oldFile) {
            if (!in_array($oldFile['path'], $keptPaths)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldFile['path']);
            }
        }

        $newFilesByOriginalName = [];
        // Process new uploaded files (if any format allows mixing existing + new)
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('whatsapp_media', 'public');
                    $newFilesByOriginalName[$file->getClientOriginalName()] = [
                        'path' => $path,
                        'type' => $data['type'],
                        'name' => $file->getClientOriginalName()
                    ];
                }
            }
        }
        
        $structuralOrder = $request->structural_order ? json_decode($request->structural_order, true) : null;
        $uploadedMedia = [];

        if (is_array($structuralOrder)) {
            foreach ($structuralOrder as $item) {
                if ($item['source'] === 'existing') {
                    // Find it in existingMedia
                    $found = collect($existingMedia)->firstWhere('path', $item['identifier']);
                    if ($found) $uploadedMedia[] = $found;
                } elseif ($item['source'] === 'new' && isset($newFilesByOriginalName[$item['identifier']])) {
                    $uploadedMedia[] = $newFilesByOriginalName[$item['identifier']];
                }
            }
        } else {
            // Fallback
            $uploadedMedia = array_merge($existingMedia, array_values($newFilesByOriginalName));
        }
        
        $data['media_files'] = empty($uploadedMedia) ? null : $uploadedMedia;

        $template->update($data);

        return redirect()->back()->with('success', 'Template updated successfully.');
    }

    public function destroy($id)
    {
        $template = \App\Models\WhatsappTemplate::findOrFail($id);
        
        if (!can('whatsapp-templates.global') && $template->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!empty($template->media_files)) {
            foreach ($template->media_files as $media) {
                if (isset($media['path'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($media['path']);
                }
            }
        }
        $template->delete();

        return redirect()->back()->with('success', 'Template deleted successfully.');
    }
}

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
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,3gp,pdf|max:10240', // 10MB max
        ]);

        $data = $request->only(['name', 'template_code', 'type', 'message_text']);
        $data['user_id'] = auth()->id();

        if ($request->hasFile('media_file')) {
            $path = $request->file('media_file')->store('whatsapp_media', 'public');
            $data['media_path'] = $path;
        }

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
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,3gp,pdf|max:10240',
        ]);

        $data = $request->only(['name', 'template_code', 'type', 'message_text']);

        if ($request->hasFile('media_file')) {
            if ($template->media_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($template->media_path);
            }
            $path = $request->file('media_file')->store('whatsapp_media', 'public');
            $data['media_path'] = $path;
        }

        $template->update($data);

        return redirect()->back()->with('success', 'Template updated successfully.');
    }

    public function destroy($id)
    {
        $template = \App\Models\WhatsappTemplate::findOrFail($id);
        
        if (!can('whatsapp-templates.global') && $template->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($template->media_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($template->media_path);
        }
        $template->delete();

        return redirect()->back()->with('success', 'Template deleted successfully.');
    }
}

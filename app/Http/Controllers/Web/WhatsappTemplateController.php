<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $templates = \App\Models\WhatsappTemplate::latest()->get();
        return view('admin.whatsapp-templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,image,video,pdf',
            'message_text' => 'nullable|string',
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,mp4,pdf|max:10240', // 10MB max
        ]);

        $data = $request->only(['name', 'type', 'message_text']);

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

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,image,video,pdf',
            'message_text' => 'nullable|string',
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,mp4,pdf|max:10240',
        ]);

        $data = $request->only(['name', 'type', 'message_text']);

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
        if ($template->media_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($template->media_path);
        }
        $template->delete();

        return redirect()->back()->with('success', 'Template deleted successfully.');
    }
}

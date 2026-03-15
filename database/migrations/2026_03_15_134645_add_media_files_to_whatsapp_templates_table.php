<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->json('media_files')->nullable()->after('message_text');
        });

        // Migrate existing single media_path to media_files json array
        $templates = \Illuminate\Support\Facades\DB::table('whatsapp_templates')->whereNotNull('media_path')->get();
        foreach ($templates as $template) {
            $mediaFiles = [
                [
                    'path' => $template->media_path,
                    'type' => $template->type,
                    'name' => basename($template->media_path)
                ]
            ];
            \Illuminate\Support\Facades\DB::table('whatsapp_templates')
                ->where('id', $template->id)
                ->update(['media_files' => json_encode($mediaFiles)]);
        }

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('media_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('message_text');
            $table->dropColumn('media_files');
        });
    }
};

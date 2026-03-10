<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->string('template_code', 10)->nullable()->unique()->after('name');
        });

        // Auto-generate codes for existing templates
        $templates = \App\Models\WhatsappTemplate::all();
        foreach ($templates as $template) {
            $template->update([
                'template_code' => strtoupper(Str::random(8)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('template_code');
        });
    }
};

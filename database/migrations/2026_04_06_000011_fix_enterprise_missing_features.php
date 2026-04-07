<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add missing token_analytics and chat_history to Enterprise package
        $enterprise = DB::table('subscription_packages')->where('slug', 'enterprise')->first();
        if ($enterprise) {
            $modules = json_decode($enterprise->module_permissions, true) ?? [];
            $features = json_decode($enterprise->features, true) ?? [];

            // Add missing Enterprise features
            $missingModules = ['token_analytics', 'chat_history'];
            foreach ($missingModules as $mod) {
                if (!isset($modules[$mod])) {
                    $modules[$mod] = true;
                }
                if (!in_array($mod, $features)) {
                    $features[] = $mod;
                }
            }

            DB::table('subscription_packages')
                ->where('id', $enterprise->id)
                ->update([
                    'module_permissions' => json_encode($modules),
                    'features' => json_encode($features),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Revert
        $enterprise = DB::table('subscription_packages')->where('slug', 'enterprise')->first();
        if ($enterprise) {
            $modules = json_decode($enterprise->module_permissions, true) ?? [];
            $features = json_decode($enterprise->features, true) ?? [];

            unset($modules['token_analytics'], $modules['chat_history']);
            $features = array_values(array_diff($features, ['token_analytics', 'chat_history']));

            DB::table('subscription_packages')
                ->where('id', $enterprise->id)
                ->update([
                    'module_permissions' => json_encode($modules),
                    'features' => json_encode($features),
                ]);
        }
    }
};

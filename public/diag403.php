<?php
// Test the fixed can() helper
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre>";
echo "=== TESTING FIXED can() HELPER ===\n\n";

$users = \App\Models\User::with('role')->get();

foreach ($users as $user) {
    echo "User #{$user->id}: {$user->name} (role: " . ($user->role->name ?? 'NONE') . ")\n";

    // Simulate auth
    auth()->login($user);

    $testPerms = ['leads.read', 'roles.read', 'quotes.read', 'products.read', 'leads.global'];
    foreach ($testPerms as $p) {
        echo "  can('$p'): " . (can($p) ? '✅ YES' : '❌ NO') . "\n";
    }
    echo "  isAdmin(): " . (isAdmin() ? '✅ YES' : '❌ NO') . "\n";
    echo "\n";

    auth()->logout();
}

echo "=== TEST COMPLETE ===\n";
echo "</pre>";

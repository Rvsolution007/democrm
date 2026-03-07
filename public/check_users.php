<?php
// ==============================================
// RV CRM Setup Script - Run this ONCE
// Access via: http://localhost/rvallsolutionscrm-main/backend/public/check_users.php
// ==============================================

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

echo "<html><head><title>RV CRM Setup</title><style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; background: #1a1a2e; color: #eee; padding: 20px; }
.success { background: #0f5132; color: #badbcc; padding: 15px; border-radius: 8px; margin: 10px 0; }
.error { background: #842029; color: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0; }
.info { background: #055160; color: #cff4fc; padding: 15px; border-radius: 8px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #444; text-align: left; }
th { background: #16213e; }
a { color: #4cc9f0; font-size: 18px; }
h1 { color: #4cc9f0; }
h2 { color: #f72585; }
</style></head><body>";

echo "<h1>🔧 RV CRM Setup</h1>";

// Step 1: Show all users
echo "<h2>1. Users in Database</h2>";
$users = DB::table('users')->get();
if ($users->count() === 0) {
    echo "<div class='error'>❌ No users found in the database!</div>";
} else {
    echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr>";
    foreach ($users as $u) {
        $status = $u->status ?? ($u->is_active ?? 'N/A');
        echo "<tr><td>{$u->id}</td><td>{$u->name}</td><td>{$u->email}</td><td>{$u->phone}</td><td>{$status}</td></tr>";
    }
    echo "</table>";
}

// Step 2: Reset password for rvsolution696@gmail.com
echo "<h2>2. Password Reset</h2>";
$email = 'rvsolution696@gmail.com';
$newPassword = '9773256235';

$user = DB::table('users')->where('email', $email)->first();
if ($user) {
    $hashed = Hash::make($newPassword);
    DB::table('users')->where('email', $email)->update([
        'password' => $hashed,
        'status' => 'active',
    ]);
    // Verify
    $updated = DB::table('users')->where('email', $email)->first();
    $verified = password_verify($newPassword, $updated->password);
    if ($verified) {
        echo "<div class='success'>✅ Password reset successful for <b>{$email}</b></div>";
        echo "<div class='success'>✅ Password verified: <b>{$newPassword}</b></div>";
        echo "<div class='success'>✅ Account status set to: <b>active</b></div>";
    } else {
        echo "<div class='error'>❌ Password verification failed!</div>";
    }
} else {
    echo "<div class='error'>❌ User <b>{$email}</b> NOT FOUND!</div>";
    echo "<div class='info'>ℹ️ Available emails: " . $users->pluck('email')->implode(', ') . "</div>";
}

// Step 3: Clear Laravel caches
echo "<h2>3. Cache Cleared</h2>";
try {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    echo "<div class='success'>✅ All Laravel caches cleared (config, cache, view, route)</div>";
} catch (\Exception $e) {
    echo "<div class='error'>⚠️ Cache clear error: " . $e->getMessage() . "</div>";
}

// Step 4: Instructions
echo "<h2>4. Next Steps</h2>";
echo "<div class='info'>";
echo "<p><b>You need to do 2 things:</b></p>";
echo "<p><b>Step A:</b> Add this line to your Windows hosts file (<code>C:\\Windows\\System32\\drivers\\etc\\hosts</code>):</p>";
echo "<pre style='background:#16213e;padding:10px;border-radius:5px'>127.0.0.1    rvcrm.local</pre>";
echo "<p><b>Step B:</b> Restart Apache from XAMPP Control Panel (Stop → Start)</p>";
echo "<p><b>Then open:</b> <a href='http://rvcrm.local/login'>http://rvcrm.local/login</a></p>";
echo "</div>";

echo "<h2>Login Credentials</h2>";
echo "<div class='success'>";
echo "<p>📧 Email: <b>{$email}</b></p>";
echo "<p>🔑 Password: <b>{$newPassword}</b></p>";
echo "</div>";

echo "<div class='info' style='margin-top:30px'>⚠️ <b>Delete this file after setup!</b> (check_users.php)</div>";
echo "</body></html>";

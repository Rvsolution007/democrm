<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\LeadsController;
use App\Http\Controllers\Web\ClientsController;
use App\Http\Controllers\Web\QuotesController;
use App\Http\Controllers\Web\PaymentsController;
use App\Http\Controllers\Web\FollowupsController;

use App\Http\Controllers\Web\ProductsController;
use App\Http\Controllers\Web\CategoriesController;
use App\Http\Controllers\Web\UsersController;
use App\Http\Controllers\Web\RolesController;


use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\AiAnalyticsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth Routes (Admin / Staff)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Super Admin Login (Hidden URL from .env SA_LOGIN_SLUG)
Route::prefix(env('SA_LOGIN_SLUG', 'sa-portal'))->group(function () {
    Route::get('/login', [AuthController::class, 'showSaLogin'])->name('sa.login');
    Route::post('/login', [AuthController::class, 'saLogin'])->name('sa.login.post');
});

// Subscription Expired Page (accessible when logged in but expired)
Route::get('/subscription/expired', [AuthController::class, 'subscriptionExpired'])
    ->name('subscription.expired')
    ->middleware('auth');

// Super Admin Panel Routes (protected by auth + superadmin middleware)
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'superadmin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

    // Businesses
    Route::get('/businesses', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'index'])->name('businesses.index');
    Route::get('/businesses/create', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'create'])->name('businesses.create');
    Route::post('/businesses', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'store'])->name('businesses.store');
    Route::get('/businesses/{company}', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'show'])->name('businesses.show');
    Route::post('/businesses/{company}/toggle-status', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'toggleStatus'])->name('businesses.toggle-status');
    Route::post('/businesses/{company}/assign-subscription', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'assignSubscription'])->name('businesses.assign-subscription');
    Route::post('/businesses/{company}/add-credits', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'addCredits'])->name('businesses.add-credits');
    Route::post('/businesses/{company}/update-users', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'updateMaxUsers'])->name('businesses.update-users');
    Route::post('/businesses/{company}/reset-credentials', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'resetAdminCredentials'])->name('businesses.reset-credentials');
    Route::post('/businesses/{company}/create-admin', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'createAdmin'])->name('businesses.create-admin');
    Route::post('/businesses/{company}/dismiss-upgrade', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'dismissUpgrade'])->name('businesses.dismiss-upgrade');
    Route::delete('/businesses/{company}', [App\Http\Controllers\SuperAdmin\BusinessController::class, 'destroy'])->name('businesses.destroy');

    // Bot Traces (per-business, AJAX)
    Route::get('/businesses/{company}/bot-traces', [App\Http\Controllers\SuperAdmin\BusinessTraceController::class, 'sessions'])->name('businesses.bot-traces');
    Route::get('/businesses/{company}/bot-traces/{sessionId}', [App\Http\Controllers\SuperAdmin\BusinessTraceController::class, 'traces'])->name('businesses.bot-traces.show');

    // Packages
    Route::get('/packages', [App\Http\Controllers\SuperAdmin\PackageController::class, 'index'])->name('packages.index');
    Route::get('/packages/create', [App\Http\Controllers\SuperAdmin\PackageController::class, 'create'])->name('packages.create');
    Route::post('/packages', [App\Http\Controllers\SuperAdmin\PackageController::class, 'store'])->name('packages.store');
    Route::get('/packages/{package}/edit', [App\Http\Controllers\SuperAdmin\PackageController::class, 'edit'])->name('packages.edit');
    Route::put('/packages/{package}', [App\Http\Controllers\SuperAdmin\PackageController::class, 'update'])->name('packages.update');
    Route::post('/packages/{package}/toggle', [App\Http\Controllers\SuperAdmin\PackageController::class, 'toggleActive'])->name('packages.toggle-active');

    // Subscriptions
    Route::get('/subscriptions', [App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'index'])->name('subscriptions.index');

    // Credit Packs
    Route::get('/credit-packs', [App\Http\Controllers\SuperAdmin\CreditPackController::class, 'index'])->name('credit-packs.index');
    Route::post('/credit-packs', [App\Http\Controllers\SuperAdmin\CreditPackController::class, 'store'])->name('credit-packs.store');
    Route::put('/credit-packs/{pack}', [App\Http\Controllers\SuperAdmin\CreditPackController::class, 'update'])->name('credit-packs.update');
    Route::post('/credit-packs/{pack}/toggle', [App\Http\Controllers\SuperAdmin\CreditPackController::class, 'toggle'])->name('credit-packs.toggle');

    // Wallets
    Route::get('/wallets', [App\Http\Controllers\SuperAdmin\WalletController::class, 'index'])->name('wallets.index');
    Route::post('/wallets/{wallet}/add-credits', [App\Http\Controllers\SuperAdmin\WalletController::class, 'addCredits'])->name('wallets.add-credits');

    // Global Settings
    Route::get('/settings', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'index'])->name('settings');
    Route::post('/settings/credits', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'saveCredits'])->name('settings.save-credits');
    Route::post('/settings/platform', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'savePlatform'])->name('settings.save-platform');
    Route::post('/settings/payment', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'savePayment'])->name('settings.save-payment');
    Route::post('/settings/ai-config', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'saveAiConfig'])->name('settings.save-ai-config');
    Route::post('/settings/ai-prompts', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'saveAiPrompts'])->name('settings.save-ai-prompts');
    Route::post('/settings/evolution', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'saveEvolutionConfig'])->name('settings.save-evolution');

    // Per-business match config (from business detail)
    Route::post('/businesses/{companyId}/match-confidence', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'saveMatchConfidence'])->name('businesses.match-confidence');
    Route::post('/businesses/{companyId}/match-test', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'testMatch'])->name('businesses.match-test');
    Route::post('/businesses/{companyId}/clear-cache', [App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'clearMatchCache'])->name('businesses.clear-cache');

    // Setup Tour Management
    Route::get('/setup-tour', [App\Http\Controllers\SuperAdmin\SetupTourController::class, 'index'])->name('setup-tour.index');
    Route::post('/setup-tour', [App\Http\Controllers\SuperAdmin\SetupTourController::class, 'save'])->name('setup-tour.save');
});

// Temporary Route to seed users — inline logic, no class dependency
Route::get('/seed-users', function () {
    try {
        // Disable FK checks for clean slate
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('users')->truncate();
        \Illuminate\Support\Facades\DB::table('roles')->truncate();
        \Illuminate\Support\Facades\DB::table('companies')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Seed Company
        \Illuminate\Support\Facades\DB::table('companies')->insert([
            'id' => 1,
            'name' => 'Demo Company Pvt Ltd',
            'gstin' => '27AABCU9603R1ZM',
            'pan' => 'AABCU9603R',
            'phone' => '9876543210',
            'email' => 'admin@democompany.com',
            'address' => json_encode([
                'line1' => '123 Business Park',
                'line2' => 'Tech Hub',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'country' => 'India',
            ]),
            'default_gst_percent' => 18,
            'quote_prefix' => 'Q',
            'quote_fy_format' => 'YY-YY',
            'terms_and_conditions' => "1. Prices are valid for 30 days.\n2. Payment: 50% advance, 50% on delivery.\n3. GST extra.\n4. Delivery: 7-10 working days.",
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed Roles
        \Illuminate\Support\Facades\DB::table('roles')->insert([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Full access to all features',
            'permissions' => json_encode(["all"]),
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('roles')->insert([
            'id' => 2,
            'company_id' => 1,
            'name' => 'Sales',
            'slug' => 'sales',
            'description' => 'Access to leads, clients, quotes',
            'permissions' => json_encode(["leads.read","leads.write","clients.read","clients.write","quotes.read","quotes.write","products.read","categories.read","activities.read","activities.write","tasks.read","tasks.write"]),
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Seed Users
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'id' => 1,
            'company_id' => 1,
            'role_id' => 1,
            'name' => 'Admin User',
            'email' => 'rvsolution696@gmail.com',
            'phone' => '9876543210',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('Rvsolution@1415'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'id' => 2,
            'company_id' => 1,
            'role_id' => 2,
            'name' => 'Sales User',
            'email' => 'sales@rvcrm.local',
            'phone' => '9876543211',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return 'SUCCESS! Seeded: companies(1 row), roles(2 rows), users(2 rows) in database "democrm". Go to /login now.';
    } catch (\Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
});

// Temporary Route to safely reset/create the super admin
Route::get('/reset-admin', function () {
    $user = \Illuminate\Support\Facades\DB::table('users')->where('email', 'rvsolution696@gmail.com')->first();
    if ($user) {
        \Illuminate\Support\Facades\DB::table('users')
            ->where('email', 'rvsolution696@gmail.com')
            ->update([
                'password' => \Illuminate\Support\Facades\Hash::make('Rvsolution@1415'),
                'user_type' => 'super_admin',
            ]);
        return 'Super Admin password reset and user_type set to super_admin. Go to /sa-portal/login';
    }

    // User doesn't exist — create it
    // First ensure company exists
    $company = \Illuminate\Support\Facades\DB::table('companies')->first();
    $companyId = $company ? $company->id : null;
    if (!$companyId) {
        \Illuminate\Support\Facades\DB::table('companies')->insert([
            'id' => 1,
            'name' => 'Platform Admin',
            'email' => 'admin@platform.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $companyId = 1;
    }

    \Illuminate\Support\Facades\DB::table('users')->insert([
        'name' => 'Admin User',
        'email' => 'rvsolution696@gmail.com',
        'password' => \Illuminate\Support\Facades\Hash::make('Rvsolution@1415'),
        'user_type' => 'super_admin',
        'company_id' => $companyId,
        'status' => 'active',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    return 'Super Admin user CREATED with email rvsolution696@gmail.com. Go to /sa-portal/login';
});

// Temporary Route to seed ALL demo data (leads, clients, quotes, etc.)
Route::get('/seed-demo', function () {
    try {
        return require base_path('seed_demo_data.php');
    } catch (\Exception $e) {
        return 'ERROR: ' . $e->getMessage() . ' at line ' . $e->getLine();
    }
});

// ─── Landing Website ────────────────────────────────────────────────
Route::get('/', [App\Http\Controllers\Web\LandingController::class, 'index'])->name('landing');
Route::get('/features', [App\Http\Controllers\Web\LandingController::class, 'features'])->name('landing.features');
Route::get('/about', [App\Http\Controllers\Web\LandingController::class, 'about'])->name('landing.about');
Route::get('/packages', [App\Http\Controllers\Web\LandingController::class, 'packages'])->name('landing.packages');
Route::get('/faq', [App\Http\Controllers\Web\LandingController::class, 'faq'])->name('landing.faq');
Route::get('/reviews', [App\Http\Controllers\Web\LandingController::class, 'reviews'])->name('landing.reviews');
Route::get('/contact', [App\Http\Controllers\Web\LandingController::class, 'contact'])->name('landing.contact');

// Registration (choose package → register → enter admin)
Route::get('/register/{package}', [App\Http\Controllers\Web\LandingController::class, 'showRegister'])->name('register');
Route::post('/register', [App\Http\Controllers\Web\LandingController::class, 'register'])->name('register.store');
Route::post('/register/check-email', [App\Http\Controllers\Web\LandingController::class, 'checkEmail'])->name('register.check-email');

// Password Reset (email-based 6-digit code)
Route::get('/forgot-password', [App\Http\Controllers\Web\LandingController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/forgot-password', [App\Http\Controllers\Web\LandingController::class, 'sendResetCode'])->name('password.send-code');
Route::get('/reset-password', [App\Http\Controllers\Web\LandingController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [App\Http\Controllers\Web\LandingController::class, 'resetPassword'])->name('password.reset.store');

// Contact Form
Route::post('/contact', [App\Http\Controllers\Web\LandingController::class, 'submitContact'])->name('contact.submit');

// WhatsApp Webhook (public — no auth, Evolution API calls this)
Route::post('/webhook/whatsapp/incoming/{instanceName}', [App\Http\Controllers\Web\WhatsappWebhookController::class, 'handleIncoming']);
Route::get('/webhook/whatsapp/incoming/{instanceName}', [App\Http\Controllers\Web\WhatsappWebhookController::class, 'testWebhook']);

// Official WhatsApp Cloud API Webhook (public — Meta calls this)
Route::post('/webhook/whatsapp-official', [App\Http\Controllers\Web\WhatsappWebhookController::class, 'handleOfficialWebhook']);
Route::get('/webhook/whatsapp-official', [App\Http\Controllers\Web\WhatsappWebhookController::class, 'verifyOfficialWebhook']);

// Admin Panel Routes (protected by auth + subscription check)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'subscription'])->group(function () {

    // Dashboard
    Route::get('/', function () {
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Current month stats
        $newLeads = \App\Models\Lead::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)->count();

        $wonLeads = \App\Models\Lead::where('stage', 'won')
            ->whereMonth('updated_at', $currentMonth)
            ->whereYear('updated_at', $currentYear)->count();

        $totalOrderValue = \App\Models\Quote::whereNotNull('client_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('grand_total') / 100;

        $totalPaymentReceived = \App\Models\QuotePayment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount') / 100;

        // Monthly order values for current year (Jan-Dec)
        $monthlyOrders = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyOrders[] = \App\Models\Quote::whereNotNull('client_id')
                ->whereMonth('created_at', $m)
                ->whereYear('created_at', $currentYear)
                ->sum('grand_total') / 100;
        }

        // Lead source data (all time counts + values)
        $leadSources = \App\Models\Lead::selectRaw('source, count(*) as count')
            ->whereNotNull('source')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        return view('admin.dashboard', compact(
            'newLeads',
            'wonLeads',
            'totalOrderValue',
            'totalPaymentReceived',
            'monthlyOrders',
            'leadSources',
            'currentYear'
        ));
    })->name('dashboard');

    // Sales Dashboard
    Route::get('/sales-dashboard', function () {
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Current month stats
        $newLeads = \App\Models\Lead::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)->count();

        $wonLeads = \App\Models\Lead::where('stage', 'won')
            ->whereMonth('updated_at', $currentMonth)
            ->whereYear('updated_at', $currentYear)->count();

        $totalOrderValue = \App\Models\Quote::whereNotNull('client_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('grand_total') / 100;

        $totalPaymentReceived = \App\Models\QuotePayment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount') / 100;

        // Monthly order values for current year (Jan-Dec)
        $monthlyOrders = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyOrders[] = \App\Models\Quote::whereNotNull('client_id')
                ->whereMonth('created_at', $m)
                ->whereYear('created_at', $currentYear)
                ->sum('grand_total') / 100;
        }

        // Lead source data (all time counts + values)
        $leadSources = \App\Models\Lead::selectRaw('source, count(*) as count')
            ->whereNotNull('source')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        return view('admin.sales-dashboard', compact(
            'newLeads',
            'wonLeads',
            'totalOrderValue',
            'totalPaymentReceived',
            'monthlyOrders',
            'leadSources',
            'currentYear'
        ));
    })->name('sales-dashboard');


    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Web\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [App\Http\Controllers\Web\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Web\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Web\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    // ═══ WhatsApp Features — Package 2+ (feature gated) ═══
    Route::middleware(['feature:whatsapp_connect'])->group(function () {
        // WhatsApp Templates
        Route::resource('whatsapp-templates', App\Http\Controllers\Web\WhatsappTemplateController::class)->except(['create', 'show', 'edit']);

        // WhatsApp Extension
        Route::get('/whatsapp-extension', [App\Http\Controllers\Web\WhatsappConnectController::class, 'extension'])->name('whatsapp.extension');

        // WhatsApp Campaigns
        Route::get('whatsapp-campaigns', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'index'])->name('whatsapp-campaigns.index');
        Route::get('whatsapp-campaigns/create', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'create'])->name('whatsapp-campaigns.create');
        Route::post('whatsapp-campaigns', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'store'])->name('whatsapp-campaigns.store');
        Route::post('whatsapp-campaigns/preview', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'preview'])->name('whatsapp-campaigns.preview');
        Route::get('whatsapp-campaigns/{campaign}', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'show'])->name('whatsapp-campaigns.show');
        Route::delete('whatsapp-campaigns/{campaign}', [App\Http\Controllers\Web\WhatsappCampaignController::class, 'destroy'])->name('whatsapp-campaigns.destroy');

        // WhatsApp Auto-Reply
        Route::get('whatsapp-auto-reply', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'index'])->name('whatsapp-auto-reply.index');
        Route::get('whatsapp-auto-reply/create', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'create'])->name('whatsapp-auto-reply.create');
        Route::post('whatsapp-auto-reply', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'store'])->name('whatsapp-auto-reply.store');
        Route::get('whatsapp-auto-reply/{id}/edit', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'edit'])->name('whatsapp-auto-reply.edit');
        Route::put('whatsapp-auto-reply/{id}', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'update'])->name('whatsapp-auto-reply.update');
        Route::delete('whatsapp-auto-reply/{id}', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'destroy'])->name('whatsapp-auto-reply.destroy');
        Route::post('whatsapp-auto-reply/{id}/toggle', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'toggle'])->name('whatsapp-auto-reply.toggle');
        Route::post('whatsapp-auto-reply/{id}/duplicate', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'duplicate'])->name('whatsapp-auto-reply.duplicate');
        Route::post('whatsapp-auto-reply/pause-all', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'pauseAll'])->name('whatsapp-auto-reply.pause-all');
        Route::get('whatsapp-auto-reply-analytics', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'analytics'])->name('whatsapp-auto-reply.analytics');
        Route::get('whatsapp-auto-reply-blacklist', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'blacklist'])->name('whatsapp-auto-reply.blacklist');
        Route::post('whatsapp-auto-reply-blacklist', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'addToBlacklist'])->name('whatsapp-auto-reply.blacklist.store');
        Route::delete('whatsapp-auto-reply-blacklist/{id}', [App\Http\Controllers\Web\WhatsappAutoReplyController::class, 'removeFromBlacklist'])->name('whatsapp-auto-reply.blacklist.destroy');

        // Meta WhatsApp Templates (Official API — only works when official API + WABA ID configured)
        Route::get('meta-templates', [App\Http\Controllers\Web\MetaTemplateController::class, 'index'])->name('meta-templates.index');
        Route::get('meta-templates/create', [App\Http\Controllers\Web\MetaTemplateController::class, 'create'])->name('meta-templates.create');
        Route::post('meta-templates', [App\Http\Controllers\Web\MetaTemplateController::class, 'store'])->name('meta-templates.store');
        Route::get('meta-templates/{id}', [App\Http\Controllers\Web\MetaTemplateController::class, 'show'])->name('meta-templates.show');
        Route::delete('meta-templates/{id}', [App\Http\Controllers\Web\MetaTemplateController::class, 'destroy'])->name('meta-templates.destroy');
        Route::post('meta-templates/sync', [App\Http\Controllers\Web\MetaTemplateController::class, 'syncAll'])->name('meta-templates.sync');
        Route::post('meta-templates/{id}/sync', [App\Http\Controllers\Web\MetaTemplateController::class, 'syncOne'])->name('meta-templates.sync-one');
        Route::post('meta-templates/{id}/retry', [App\Http\Controllers\Web\MetaTemplateController::class, 'retry'])->name('meta-templates.retry');
        Route::get('meta-templates-approved-json', [App\Http\Controllers\Web\MetaTemplateController::class, 'approvedTemplatesJson'])->name('meta-templates.approved-json');
    }); // end WhatsApp feature gate

    // Leads
    Route::get('/leads/whatsapp-lookup', [LeadsController::class, 'whatsappLookup'])->name('leads.whatsapp-lookup');
    Route::get('/leads', [LeadsController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadsController::class, 'store'])->name('leads.store');
    Route::get('/leads/{id}/edit', [LeadsController::class, 'edit'])->name('leads.edit');
    Route::get('/leads/{id}', [LeadsController::class, 'show'])->name('leads.show');
    Route::put('/leads/{id}', [LeadsController::class, 'update'])->name('leads.update');
    Route::patch('/leads/{id}/stage', [LeadsController::class, 'updateStage'])->name('leads.updateStage');
    Route::delete('/leads/{id}', [LeadsController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{id}/convert-to-quote', [LeadsController::class, 'convertToQuote'])->name('leads.convert-to-quote');
    Route::post('/leads/{id}/convert-to-client', [LeadsController::class, 'convertToClient'])->name('leads.convert-to-client');
    Route::post('/leads/{id}/followups', [LeadsController::class, 'storeFollowup'])->name('leads.followups.store');
    Route::patch('/leads/{id}/followups/{followupId}', [LeadsController::class, 'updateFollowup'])->name('leads.followups.update');
    Route::delete('/leads/{id}/followups/{followupId}', [LeadsController::class, 'destroyFollowup'])->name('leads.followups.destroy');

    // Clients
    Route::get('/clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [ClientsController::class, 'show'])->name('clients.show');
    Route::post('/clients', [ClientsController::class, 'store'])->name('clients.store');
    Route::put('/clients/{id}', [ClientsController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{id}', [ClientsController::class, 'destroy'])->name('clients.destroy');

    // Quotes
    Route::get('/quotes', [QuotesController::class, 'index'])->name('quotes.index');
    Route::post('/quotes', [QuotesController::class, 'store'])->name('quotes.store');
    Route::get('/quotes/{id}/edit', [QuotesController::class, 'edit'])->name('quotes.edit');
    Route::get('/quotes/{id}', [QuotesController::class, 'show'])->name('quotes.show');
    Route::get('/quotes/{id}/download', [QuotesController::class, 'download'])->name('quotes.download');
    Route::put('/quotes/{id}', [QuotesController::class, 'update'])->name('quotes.update');
    Route::delete('/quotes/{id}', [QuotesController::class, 'destroy'])->name('quotes.destroy');
    Route::post('/quotes/{id}/convert', [QuotesController::class, 'convert'])->name('quotes.convert');

    // Invoices
    Route::get('/invoices', [\App\Http\Controllers\Web\InvoicesController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [\App\Http\Controllers\Web\InvoicesController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}/edit', [\App\Http\Controllers\Web\InvoicesController::class, 'edit'])->name('invoices.edit');
    Route::get('/invoices/{id}', [\App\Http\Controllers\Web\InvoicesController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{id}/download', [\App\Http\Controllers\Web\InvoicesController::class, 'download'])->name('invoices.download');
    Route::put('/invoices/{id}', [\App\Http\Controllers\Web\InvoicesController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{id}', [\App\Http\Controllers\Web\InvoicesController::class, 'destroy'])->name('invoices.destroy');

    // Payments
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentsController::class, 'store'])->name('payments.store');
    Route::put('/payments/{id}', [PaymentsController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{id}', [PaymentsController::class, 'destroy'])->name('payments.destroy');

    // Follow-ups
    Route::get('/followups', [FollowupsController::class, 'index'])->name('followups.index');


    // ═══ AI Setup Wizard — Catalogue Onboarding ═══
    Route::get('/setup-wizard', [App\Http\Controllers\Web\SetupWizardController::class, 'index'])->name('setup-wizard.index');
    Route::post('/setup-wizard/analyze', [App\Http\Controllers\Web\SetupWizardController::class, 'analyze'])->name('setup-wizard.analyze');
    Route::get('/setup-wizard/download-columns-excel', [App\Http\Controllers\Web\SetupWizardController::class, 'downloadColumnsExcel'])->name('setup-wizard.download-columns');
    Route::post('/setup-wizard/import-columns', [App\Http\Controllers\Web\SetupWizardController::class, 'importColumns'])->name('setup-wizard.import-columns');
    Route::post('/setup-wizard/extract-products', [App\Http\Controllers\Web\SetupWizardController::class, 'extractProducts'])->name('setup-wizard.extract-products');
    Route::post('/setup-wizard/import-products', [App\Http\Controllers\Web\SetupWizardController::class, 'importProducts'])->name('setup-wizard.import-products');
    Route::get('/setup-wizard/download-products-excel', [App\Http\Controllers\Web\SetupWizardController::class, 'downloadProductsExcel'])->name('setup-wizard.download-products');
    Route::post('/setup-wizard/complete', [App\Http\Controllers\Web\SetupWizardController::class, 'complete'])->name('setup-wizard.complete');
    Route::post('/setup-wizard/reset', [App\Http\Controllers\Web\SetupWizardController::class, 'reset'])->name('setup-wizard.reset');

    // Products — Excel Import/Export (must be before resource routes)
    Route::get('/products/demo-excel', [ProductsController::class, 'downloadDemoExcel'])->name('products.demo-excel');
    Route::post('/products/import-excel/validate', [ProductsController::class, 'validateImportExcel'])->name('products.import-validate');
    Route::post('/products/import-excel/process', [ProductsController::class, 'processImportExcel'])->name('products.import-process');

    // Products
    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductsController::class, 'store'])->name('products.store');
    Route::put('/products/{id}', [ProductsController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductsController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/bulk-delete', [ProductsController::class, 'bulkDestroy'])->name('products.bulk-destroy');

    // Categories
    Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoriesController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])->name('categories.destroy');

    // Vendors
    Route::get('/vendors', [App\Http\Controllers\Web\VendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors', [App\Http\Controllers\Web\VendorController::class, 'store'])->name('vendors.store');
    Route::put('/vendors/{id}', [App\Http\Controllers\Web\VendorController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{id}', [App\Http\Controllers\Web\VendorController::class, 'destroy'])->name('vendors.destroy');
    // Vendor Custom Fields (AJAX)
    Route::get('/vendors/{id}/custom-fields', [App\Http\Controllers\Web\VendorController::class, 'getCustomFields'])->name('vendors.custom-fields.index');
    Route::post('/vendors/custom-fields', [App\Http\Controllers\Web\VendorController::class, 'storeCustomField'])->name('vendors.custom-fields.store');
    Route::delete('/vendors/custom-fields/{id}', [App\Http\Controllers\Web\VendorController::class, 'deleteCustomField'])->name('vendors.custom-fields.destroy');

    // Purchases
    Route::get('/purchases', [App\Http\Controllers\Web\PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/search', [App\Http\Controllers\Web\PurchaseController::class, 'search'])->name('purchases.search');
    Route::get('/purchases/vendor-fields/{vendorId}', [App\Http\Controllers\Web\PurchaseController::class, 'getVendorCustomFields'])->name('purchases.vendor-fields');
    Route::post('/purchases', [App\Http\Controllers\Web\PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{id}', [App\Http\Controllers\Web\PurchaseController::class, 'show'])->name('purchases.show');
    Route::put('/purchases/{id}', [App\Http\Controllers\Web\PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('/purchases/{id}', [App\Http\Controllers\Web\PurchaseController::class, 'destroy'])->name('purchases.destroy');
    Route::post('/purchases/{id}/payment', [App\Http\Controllers\Web\PurchaseController::class, 'addPayment'])->name('purchases.payment');

    // Purchase Payments
    Route::get('/purchase-payments', [App\Http\Controllers\Web\PurchasePaymentController::class, 'index'])->name('purchase-payments.index');
    Route::put('/purchase-payments/{id}', [App\Http\Controllers\Web\PurchasePaymentController::class, 'update'])->name('purchase-payments.update');
    Route::delete('/purchase-payments/{id}', [App\Http\Controllers\Web\PurchasePaymentController::class, 'destroy'])->name('purchase-payments.destroy');

    // Users
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');

    // Roles
    Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    Route::put('/roles/{id}', [RolesController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');

    // Activities
    Route::get('/activities', function (\Illuminate\Http\Request $request) {
        if (!can('activities.read'))
            abort(403, 'Unauthorized action.');

        // ─── Build Activity query ───
        $activityQuery = \App\Models\Activity::with('createdBy')
            ->select('id', 'company_id', 'created_by_user_id', 'entity_type', 'entity_id', 'type', 'subject', 'summary', 'created_at')
            ->selectRaw("'activity' as source");

        // ─── Build TaskActivity query ───
        $taskActivityQuery = \App\Models\TaskActivity::with(['user', 'task'])
            ->select('id', 'task_id', 'user_id', 'type', 'message', 'old_value', 'new_value', 'created_at');

        // ─── Apply filters to Activity ───
        if ($request->filled('search')) {
            $s = $request->search;
            $activityQuery->where(function ($q) use ($s) {
                $q->where('subject', 'like', "%{$s}%")
                    ->orWhere('summary', 'like', "%{$s}%");
            });
        }
        if ($request->filled('user_id')) {
            $activityQuery->where('created_by_user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $activityQuery->where('type', $request->type);
        }
        if ($request->filled('entity_type') && $request->entity_type !== 'task') {
            $activityQuery->where('entity_type', $request->entity_type);
        }
        if ($request->filled('date_from')) {
            $activityQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $activityQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // ─── Apply filters to TaskActivity ───
        if ($request->filled('search')) {
            $s = $request->search;
            $taskActivityQuery->where('message', 'like', "%{$s}%");
        }
        if ($request->filled('user_id')) {
            $taskActivityQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $taskActivityQuery->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $taskActivityQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $taskActivityQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // If entity_type is set and NOT task, skip task activities
        $skipTasks = $request->filled('entity_type') && $request->entity_type !== 'task';
        // If entity_type is 'task', skip regular activities
        $skipActivities = $request->filled('entity_type') && $request->entity_type === 'task';

        // ─── Fetch & merge ───
        $allActivities = collect();

        if (!$skipActivities) {
            $regularActivities = $activityQuery->latest()->take(200)->get()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'source' => 'activity',
                    'type' => $a->type,
                    'subject' => $a->subject,
                    'summary' => $a->summary ?? $a->subject,
                    'entity_type' => $a->entity_type,
                    'entity_id' => $a->entity_id,
                    'user_name' => $a->createdBy->name ?? 'System',
                    'user_initials' => strtoupper(substr($a->createdBy->name ?? 'S', 0, 2)),
                    'user_id' => $a->created_by_user_id,
                    'created_at' => $a->created_at->toISOString(),
                    'created_at_human' => $a->created_at->diffForHumans(),
                    'created_at_formatted' => $a->created_at->format('d M Y, h:i A'),
                ];
            });
            $allActivities = $allActivities->merge($regularActivities);
        }

        if (!$skipTasks) {
            $taskActivities = $taskActivityQuery->latest()->take(200)->get()->map(function ($ta) {
                $taskName = $ta->task->title ?? 'Task';
                $taskId = $ta->task_id;
                $projectId = $ta->task->project_id ?? null;
                return [
                    'id' => $ta->id,
                    'source' => 'task_activity',
                    'type' => $ta->type,
                    'subject' => $ta->getTypeLabel(),
                    'summary' => $ta->message ?? ($ta->getTypeLabel() . ' on ' . $taskName),
                    'entity_type' => 'task',
                    'entity_id' => $taskId,
                    'entity_name' => $taskName,
                    'project_id' => $projectId,
                    'old_value' => $ta->old_value,
                    'new_value' => $ta->new_value,
                    'user_name' => $ta->user->name ?? 'System',
                    'user_initials' => strtoupper(substr($ta->user->name ?? 'S', 0, 2)),
                    'user_id' => $ta->user_id,
                    'created_at' => $ta->created_at->toISOString(),
                    'created_at_human' => $ta->created_at->diffForHumans(),
                    'created_at_formatted' => $ta->created_at->format('d M Y, h:i A'),
                ];
            });
            $allActivities = $allActivities->merge($taskActivities);
        }

        // Sort merged by created_at desc
        $allActivities = $allActivities->sortByDesc('created_at')->values();

        // ─── Stats ───
        $totalActivities = \App\Models\Activity::count() + \App\Models\TaskActivity::count();
        $todayActivities = \App\Models\Activity::whereDate('created_at', today())->count()
            + \App\Models\TaskActivity::whereDate('created_at', today())->count();

        // Most active user
        $topUserActivity = \App\Models\Activity::selectRaw('created_by_user_id, count(*) as cnt')
            ->groupBy('created_by_user_id')
            ->orderByDesc('cnt')
            ->first();
        $topUserTaskAct = \App\Models\TaskActivity::selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->first();

        $mostActiveUser = 'N/A';
        $mostActiveCount = 0;
        if ($topUserActivity && $topUserActivity->cnt >= ($topUserTaskAct->cnt ?? 0)) {
            $u = \App\Models\User::find($topUserActivity->created_by_user_id);
            $mostActiveUser = $u->name ?? 'N/A';
            $mostActiveCount = $topUserActivity->cnt;
        } elseif ($topUserTaskAct) {
            $u = \App\Models\User::find($topUserTaskAct->user_id);
            $mostActiveUser = $u->name ?? 'N/A';
            $mostActiveCount = $topUserTaskAct->cnt;
        }

        // Top entity
        $topEntity = \App\Models\Activity::selectRaw('entity_type, count(*) as cnt')
            ->groupBy('entity_type')
            ->orderByDesc('cnt')
            ->first();
        $topEntityLabel = $topEntity ? ucfirst($topEntity->entity_type) . ' (' . $topEntity->cnt . ')' : 'N/A';

        // ─── Users for filter dropdown ───
        $users = \App\Models\User::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        // ─── AJAX response ───
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'activities' => $allActivities,
                'total' => $allActivities->count(),
                'stats' => [
                    'total' => $totalActivities,
                    'today' => $todayActivities,
                    'most_active_user' => $mostActiveUser,
                    'most_active_count' => $mostActiveCount,
                    'top_entity' => $topEntityLabel,
                ],
            ]);
        }

        return view('admin.activities.index', compact(
            'allActivities',
            'users',
            'totalActivities',
            'todayActivities',
            'mostActiveUser',
            'mostActiveCount',
            'topEntityLabel'
        ));
    })->name('activities.index');

    // Backups
    Route::get('/backups', [\App\Http\Controllers\Web\BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups/run', [\App\Http\Controllers\Web\BackupController::class, 'runBackup'])->name('backups.run');
    Route::get('/backups/download/{fileName}', [\App\Http\Controllers\Web\BackupController::class, 'download'])->name('backups.download');
    Route::delete('/backups/{fileName}', [\App\Http\Controllers\Web\BackupController::class, 'destroy'])->name('backups.destroy');
    Route::post('/backups/import', [\App\Http\Controllers\Web\BackupController::class, 'import'])->name('backups.import');
    Route::post('/backups/restore', [\App\Http\Controllers\Web\BackupController::class, 'restore'])->name('backups.restore');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Web\ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/leads', [\App\Http\Controllers\Web\ReportsController::class, 'exportLeads'])->name('reports.export.leads');
    Route::get('/reports/export/quotes', [\App\Http\Controllers\Web\ReportsController::class, 'exportQuotes'])->name('reports.export.quotes');
    Route::get('/reports/export/payments', [\App\Http\Controllers\Web\ReportsController::class, 'exportPayments'])->name('reports.export.payments');
    Route::get('/reports/export/projects', [\App\Http\Controllers\Web\ReportsController::class, 'exportProjects'])->name('reports.export.projects');
    Route::get('/reports/export/tasks', [\App\Http\Controllers\Web\ReportsController::class, 'exportTasks'])->name('reports.export.tasks');
    Route::get('/reports/export/team', [\App\Http\Controllers\Web\ReportsController::class, 'exportTeam'])->name('reports.export.team');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
    Route::post('/settings/column-visibility', [SettingsController::class, 'saveColumnVisibility'])->name('settings.column-visibility.save');
    Route::get('/settings/column-visibility/{module}', [SettingsController::class, 'getColumnVisibility'])->name('settings.column-visibility.get');
    Route::post('/settings/taxes', [SettingsController::class, 'saveTaxes'])->name('settings.taxes.save');
    Route::post('/settings/lead-stages', [SettingsController::class, 'saveLeadStages'])->name('settings.lead-stages.save');
    Route::post('/settings/lead-stages/check', [SettingsController::class, 'checkStageLeads'])->name('settings.lead-stages.check');
    Route::post('/settings/lead-stages/transfer', [SettingsController::class, 'transferStageLeads'])->name('settings.lead-stages.transfer');
    Route::post('/settings/lead-sources', [SettingsController::class, 'saveLeadSources'])->name('settings.lead-sources.save');
    Route::post('/settings/task-statuses', [SettingsController::class, 'saveTaskStatuses'])->name('settings.task-statuses.save');
    Route::post('/settings/payment-types', [SettingsController::class, 'savePaymentTypes'])->name('settings.payment-types.save');
    Route::post('/settings/whatsapp-api', [SettingsController::class, 'saveWhatsappApi'])->name('settings.whatsapp-api.save');

    // AI Bot Settings
    Route::post('/settings/ai-config', [SettingsController::class, 'saveAiConfig'])->name('settings.ai-config.save');
    Route::post('/settings/ai-session', [SettingsController::class, 'saveAiSessionSettings'])->name('settings.ai-session.save');
    Route::post('/settings/ai-prompt', [SettingsController::class, 'saveAiPrompt'])->name('settings.ai-prompt.save');
    Route::post('/settings/ai-architecture-rules', [SettingsController::class, 'saveAiArchitectureRules'])->name('settings.ai-architecture-rules.save');
    Route::post('/settings/ai-language', [SettingsController::class, 'saveAiLanguage'])->name('settings.ai-language.save');
    Route::post('/settings/ai-business-prompt', [SettingsController::class, 'saveAiBusinessPrompt'])->name('settings.ai-business-prompt.save');
    Route::post('/settings/ai-toggle', [SettingsController::class, 'toggleAiBot'])->name('settings.ai-toggle');
    Route::post('/settings/followup', [SettingsController::class, 'saveFollowupSettings'])->name('settings.followup.save');
    Route::post('/settings/ai-tier3-prompt', [SettingsController::class, 'saveAiTier3Prompt'])->name('settings.ai-tier3-prompt.save');
    Route::post('/settings/ai-greeting-words', [SettingsController::class, 'saveAiGreetingWords'])->name('settings.ai-greeting-words.save');
    Route::post('/settings/ai-match-confidence', [SettingsController::class, 'saveAiMatchConfidence'])->name('settings.ai-match-confidence.save');
    Route::post('/settings/ai-clear-pgm-cache', [SettingsController::class, 'clearPgmCache'])->name('settings.ai-clear-pgm-cache');
    Route::post('/settings/ai-test-pgm', [SettingsController::class, 'testProductGroupMatch'])->name('settings.ai-test-pgm');

    // Bot Mode & List Bot Settings
    Route::post('/settings/bot-mode', [SettingsController::class, 'saveBotMode'])->name('settings.bot-mode.save');
    Route::post('/settings/list-bot', [SettingsController::class, 'saveListBotSettings'])->name('settings.list-bot.save');
    Route::post('/settings/interactive-list-mode', [SettingsController::class, 'saveInteractiveListMode'])->name('settings.interactive-list-mode.save');

    // Dual WhatsApp API Configuration
    Route::post('/settings/official-api-config', [SettingsController::class, 'saveOfficialApiConfig'])->name('settings.official-api.save');
    Route::post('/settings/official-api-toggle', [SettingsController::class, 'toggleOfficialApi'])->name('settings.official-api.toggle');
    Route::post('/settings/evolution-api-toggle', [SettingsController::class, 'toggleEvolutionApi'])->name('settings.evolution-api.toggle');
    Route::post('/settings/evolution-api-sub-toggle', [SettingsController::class, 'toggleEvolutionSubFeature'])->name('settings.evolution-api.sub-toggle');

    // Billing & Subscription (Admin self-service)
    Route::get('/billing', [\App\Http\Controllers\Web\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/request-upgrade', [\App\Http\Controllers\Web\BillingController::class, 'requestUpgrade'])->name('billing.request-upgrade');
    Route::post('/billing/request-credits', [\App\Http\Controllers\Web\BillingController::class, 'requestCredits'])->name('billing.request-credits');

    // ═══ AI Token Analytics — Enterprise only (feature gated) ═══
    Route::middleware(['feature:token_analytics'])->group(function () {
        Route::get('/ai-analytics', [AiAnalyticsController::class, 'index'])->name('ai-analytics.index');
        Route::get('/ai-analytics/chats', [AiAnalyticsController::class, 'chats'])->name('ai-analytics.chats');
        Route::get('/ai-analytics/chats/{id}', [AiAnalyticsController::class, 'chatDetail'])->name('ai-analytics.chat-detail');
        Route::get('/ai-analytics/tester', [AiAnalyticsController::class, 'tester'])->name('ai-analytics.tester');
        Route::post('/ai-analytics/test-questions', [AiAnalyticsController::class, 'saveTestQuestions'])->name('ai-analytics.test-questions.save');
        Route::get('/ai-analytics/test-questions', [AiAnalyticsController::class, 'getTestQuestions'])->name('ai-analytics.test-questions.get');
        Route::post('/ai-analytics/conversation-test-run', [AiAnalyticsController::class, 'runConversationTest'])->name('ai-analytics.conversation-test.run');
        Route::post('/ai-analytics/diagnostic-test-run', [AiAnalyticsController::class, 'runDiagnosticTest'])->name('ai-analytics.diagnostic-test.run');
        Route::post('/ai-analytics/test-step-init', [AiAnalyticsController::class, 'testStepInit'])->name('ai-analytics.test-step.init');
        Route::post('/ai-analytics/test-step-send', [AiAnalyticsController::class, 'testStepSend'])->name('ai-analytics.test-step.send');
        Route::post('/ai-analytics/test-step-cleanup', [AiAnalyticsController::class, 'testStepCleanup'])->name('ai-analytics.test-step.cleanup');

        // AI Node Traces
        Route::get('/ai-analytics/traces', [\App\Http\Controllers\Web\AiTraceController::class, 'index'])->name('ai-analytics.traces.index');
        Route::get('/ai-analytics/traces/{sessionId}', [\App\Http\Controllers\Web\AiTraceController::class, 'show'])->name('ai-analytics.traces.show');
    }); // end AI analytics feature gate

    // System Logs
    Route::get('/settings/system-logs', [SettingsController::class, 'systemLogsIndex'])->name('system-logs.index');
    Route::delete('/settings/system-logs/clear', [SettingsController::class, 'systemLogsClear'])->name('system-logs.clear');

    // ═══ Catalogue Custom Columns — All packages ═══
    Route::middleware(['feature:catalogue_columns'])->group(function () {
        Route::get('/catalogue-columns', [App\Http\Controllers\Web\CatalogueColumnController::class, 'index'])->name('catalogue-columns.index');
        Route::post('/catalogue-columns', [App\Http\Controllers\Web\CatalogueColumnController::class, 'store'])->name('catalogue-columns.store');
        Route::put('/catalogue-columns/{id}', [App\Http\Controllers\Web\CatalogueColumnController::class, 'update'])->name('catalogue-columns.update');
        Route::delete('/catalogue-columns/{id}', [App\Http\Controllers\Web\CatalogueColumnController::class, 'destroy'])->name('catalogue-columns.destroy');
        Route::post('/catalogue-columns/reorder', [App\Http\Controllers\Web\CatalogueColumnController::class, 'reorder'])->name('catalogue-columns.reorder');
        Route::post('/catalogue-columns/{id}/toggle-active', [App\Http\Controllers\Web\CatalogueColumnController::class, 'toggleActive'])->name('catalogue-columns.toggle-active');
        Route::post('/catalogue-columns/bulk-delete', [App\Http\Controllers\Web\CatalogueColumnController::class, 'bulkDestroy'])->name('catalogue-columns.bulk-destroy');
    }); // end catalogue feature gate

    // ═══ Chatflow Builder — Enterprise only (feature gated) ═══
    Route::middleware(['feature:chatflow'])->group(function () {
        Route::get('/chatflow', [App\Http\Controllers\Web\ChatflowController::class, 'index'])->name('chatflow.index');
        Route::post('/chatflow', [App\Http\Controllers\Web\ChatflowController::class, 'store'])->name('chatflow.store');
        Route::put('/chatflow/{id}', [App\Http\Controllers\Web\ChatflowController::class, 'update'])->name('chatflow.update');
        Route::delete('/chatflow/{id}', [App\Http\Controllers\Web\ChatflowController::class, 'destroy'])->name('chatflow.destroy');
        Route::post('/chatflow/reorder', [App\Http\Controllers\Web\ChatflowController::class, 'reorder'])->name('chatflow.reorder');
    }); // end chatflow feature gate

    // WhatsApp Connect — Package 2+ (feature gated)
    Route::middleware(['feature:whatsapp_connect'])->group(function () {
        Route::get('/whatsapp-connect', [App\Http\Controllers\Web\WhatsappConnectController::class, 'index'])->name('whatsapp-connect.index');
        Route::get('/whatsapp-connect/qr', [App\Http\Controllers\Web\WhatsappConnectController::class, 'getQrCode'])->name('whatsapp-connect.qr');
        Route::get('/whatsapp-connect/status', [App\Http\Controllers\Web\WhatsappConnectController::class, 'getStatus'])->name('whatsapp-connect.status');
        Route::post('/whatsapp-connect/disconnect', [App\Http\Controllers\Web\WhatsappConnectController::class, 'disconnect'])->name('whatsapp-connect.disconnect');
        Route::post('/whatsapp-connect/force-reconnect', [App\Http\Controllers\Web\WhatsappConnectController::class, 'forceReconnect'])->name('whatsapp-connect.force-reconnect');
        Route::get('/whatsapp-connect/debug', [App\Http\Controllers\Web\WhatsappConnectController::class, 'debugApi'])->name('whatsapp-connect.debug');
    }); // end WhatsApp connect feature gate

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

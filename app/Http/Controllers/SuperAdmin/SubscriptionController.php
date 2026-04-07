<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['company', 'package', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $stats = [
            'active' => Subscription::whereIn('status', ['active', 'trial'])->where('expires_at', '>=', now())->count(),
            'trial' => Subscription::where('status', 'trial')->where('expires_at', '>=', now())->count(),
            'expired' => Subscription::where('expires_at', '<', now())->count(),
            'expiring_soon' => Subscription::expiringSoon()->count(),
        ];

        return view('superadmin.subscriptions.index', compact('subscriptions', 'stats'));
    }
}

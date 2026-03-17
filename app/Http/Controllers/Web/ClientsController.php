<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        if (!can('clients.read')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Client::query();

        // Global permission filter
        if (!can('clients.global')) {
            $query->where(function ($q) {
                $q->where('created_by_user_id', auth()->id())
                    ->orWhereHas('assignedUsers', function($q2) {
                        $q2->where('user_id', auth()->id());
                    });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate(20)->withQueryString();
        $users = (can('clients.global') || auth()->user()->isAdmin())
            ? User::where('status', 'active')->withModulePermission('clients')->orderBy('name')->get()
            : collect();

        return view('admin.clients.index', compact('clients', 'users'));
    }

    public function show($id)
    {
        if (!can('clients.read')) {
            abort(403, 'Unauthorized action.');
        }

        $client = Client::with(['quotes.items', 'lead.followups.user', 'assignedUsers'])->findOrFail($id);

        // Non-global users can only view their own clients
        if (!can('clients.global') && $client->created_by_user_id != auth()->id() && !$client->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only view your own clients.');
        }

        return view('admin.clients.show', compact('client'));
    }

    public function store(Request $request)
    {
        if (!can('clients.write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'contact_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email',
            'phone' => 'required|string|max:15|unique:clients,phone',
            'gstin' => 'nullable|string|max:15',
            'business_category' => 'nullable|string|max:255',
            'type' => 'required|in:business,individual',
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by_user_id'] = auth()->id();

        $client = Client::create($validated);

        $assignedUsers = [];
        if (!can('clients.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $client->assignedUsers()->sync($assignedUsers);

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client created successfully');
    }

    public function update(Request $request, $id)
    {
        if (!can('clients.write')) {
            abort(403, 'Unauthorized action.');
        }

        $client = Client::findOrFail($id);

        // Non-global users can only update their own clients
        if (!can('clients.global') && $client->created_by_user_id != auth()->id() && !$client->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only edit your own clients.');
        }

        $validated = $request->validate([
            'contact_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email,' . $id,
            'phone' => 'required|string|max:15|unique:clients,phone,' . $id,
            'gstin' => 'nullable|string|max:15',
            'business_category' => 'nullable|string|max:255',
            'type' => 'required|in:business,individual',
            'assigned_to_users' => 'nullable|array',
            'assigned_to_users.*' => 'exists:users,id',
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
        ]);

        $client->update($validated);

        $assignedUsers = [];
        if (!can('clients.global') && !auth()->user()->isAdmin()) {
            $assignedUsers = [auth()->id()];
        } else {
            $assignedUsers = $request->input('assigned_to_users', []);
        }
        $client->assignedUsers()->sync($assignedUsers);

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client updated successfully');
    }

    public function destroy($id)
    {
        if (!can('clients.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $client = Client::findOrFail($id);

        // Non-global users can only delete their own clients
        if (!can('clients.global') && $client->created_by_user_id != auth()->id() && !$client->assignedUsers->contains('id', auth()->id())) {
            abort(403, 'You can only delete your own clients.');
        }

        $client->delete();

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client deleted successfully');
    }
}

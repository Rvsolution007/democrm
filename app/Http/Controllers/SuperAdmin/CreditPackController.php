<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditPack;
use Illuminate\Http\Request;

class CreditPackController extends Controller
{
    public function index()
    {
        $packs = AiCreditPack::orderBy('sort_order')->get();
        return view('superadmin.credit-packs.index', compact('packs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_active'] = true;
        $data['is_popular'] = $request->boolean('is_popular');
        $data['sort_order'] = $data['sort_order'] ?? AiCreditPack::max('sort_order') + 1;

        AiCreditPack::create($data);

        return back()->with('success', "Credit pack '{$data['name']}' created successfully.");
    }

    public function update(Request $request, AiCreditPack $pack)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_popular'] = $request->boolean('is_popular');
        $pack->update($data);

        return back()->with('success', "Credit pack '{$pack->name}' updated.");
    }

    public function toggle(AiCreditPack $pack)
    {
        $pack->update(['is_active' => !$pack->is_active]);
        $label = $pack->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Credit pack '{$pack->name}' {$label}.");
    }
}

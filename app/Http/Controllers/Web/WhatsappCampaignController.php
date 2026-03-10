<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Lead;
use App\Models\Product;
use App\Models\WhatsappCampaign;
use App\Models\WhatsappCampaignRecipient;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\DB;

class WhatsappCampaignController extends Controller
{
    public function index()
    {
        $campaigns = WhatsappCampaign::with('template', 'product')
            ->latest()
            ->paginate(15);

        return view('admin.whatsapp-campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $templates = WhatsappTemplate::latest()->get();
        // Pluck unique stages from leads to use in the dropdown
        $stages = Lead::select('stage')->distinct()->pluck('stage');
        $products = Product::where('status', 'active')->get();

        return view('admin.whatsapp-campaigns.create', compact('templates', 'stages', 'products'));
    }

    public function preview(Request $request)
    {
        $query = Lead::query();

        if ($request->stage) {
            $query->where('stage', $request->stage);
        }

        if ($request->product_id) {
            // Find leads that have this product attached
            $query->whereHas('products', function ($q) use ($request) {
                $q->where('products.id', $request->product_id);
            });
        }

        // Must have phone number
        $query->whereNotNull('phone')->where('phone', '!=', '');

        $count = $query->count();
        $leads = $query->select('id', 'name', 'phone')->limit(10)->get();

        // Calculate ETA: 20 seconds per message
        $etaSeconds = $count * 20;

        if ($etaSeconds < 60) {
            $etaReadable = $etaSeconds . ' Seconds';
        } elseif ($etaSeconds < 3600) {
            $etaReadable = round($etaSeconds / 60) . ' Minutes';
        } else {
            $hours = floor($etaSeconds / 3600);
            $minutes = round(($etaSeconds % 3600) / 60);
            $etaReadable = "{$hours} Hours, {$minutes} Minutes";
        }

        return response()->json([
            'count' => $count,
            'leads' => $leads,
            'eta_seconds' => $etaSeconds,
            'eta_readable' => $etaReadable
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:whatsapp_templates,id',
            'target_stage' => 'nullable|string',
            'target_product_id' => 'nullable|exists:products,id'
        ]);

        // Get Leads
        $query = Lead::query()->whereNotNull('phone')->where('phone', '!=', '');

        if ($request->target_stage) {
            $query->where('stage', $request->target_stage);
        }

        if ($request->target_product_id) {
            $query->whereHas('products', function ($q) use ($request) {
                $q->where('products.id', $request->target_product_id);
            });
        }

        $leads = $query->get();

        if ($leads->isEmpty()) {
            return redirect()->back()->with('error', 'No leads found matching the selected criteria.');
        }

        DB::beginTransaction();
        try {
            $campaign = WhatsappCampaign::create([
                'company_id' => auth()->user()->company_id ?? 1,
                'user_id' => auth()->id(),
                'template_id' => $request->template_id,
                'target_stage' => $request->target_stage,
                'target_product_id' => $request->target_product_id,
                'total_recipients' => $leads->count(),
                'status' => 'pending'
            ]);

            $recipients = [];
            foreach ($leads as $lead) {
                $recipients[] = [
                    'company_id' => auth()->user()->company_id ?? 1,
                    'campaign_id' => $campaign->id,
                    'lead_id' => $lead->id,
                    'phone_number' => $lead->phone,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert in chunks to avoid memory issues if large
            foreach (array_chunk($recipients, 500) as $chunk) {
                WhatsappCampaignRecipient::insert($chunk);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Campaign created successfully! The background job will start sending messages shortly based on the security interval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create campaign: ' . $e->getMessage());
        }
    }

    public function show(WhatsappCampaign $campaign)
    {
        $campaign->load(['template', 'product', 'user']);
        $recipients = WhatsappCampaignRecipient::where('campaign_id', $campaign->id)
            ->with('lead')
            ->orderByRaw("FIELD(status, 'failed', 'pending', 'sent')")
            ->get();

        return view('admin.whatsapp-campaigns.show', compact('campaign', 'recipients'));
    }

    public function destroy(WhatsappCampaign $campaign)
    {
        // Delete recipients manually if no cascade on db level
        $campaign->recipients()->delete();
        $campaign->delete();

        return redirect()->back()->with('success', 'Campaign deleted successfully.');
    }
}

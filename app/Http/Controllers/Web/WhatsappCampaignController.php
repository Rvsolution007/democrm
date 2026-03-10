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

        // Deduplication: exclude phone numbers already sent with same template_code
        $alreadySentPhones = [];
        $alreadySentCount = 0;

        if ($request->template_id) {
            $template = WhatsappTemplate::find($request->template_id);
            if ($template && $template->template_code) {
                // Find all template IDs that share the same template_code
                $sameCodeTemplateIds = WhatsappTemplate::where('template_code', $template->template_code)
                    ->pluck('id');

                // Find all campaign IDs that used any of these templates
                $campaignIds = WhatsappCampaign::whereIn('template_id', $sameCodeTemplateIds)->pluck('id');

                // Get all phone numbers successfully sent in those campaigns
                $alreadySentPhones = WhatsappCampaignRecipient::whereIn('campaign_id', $campaignIds)
                    ->where('status', 'sent')
                    ->pluck('phone_number')
                    ->map(function ($phone) {
                        // Normalize phone: strip non-digits, remove leading 91 if 12 digits
                        $clean = preg_replace('/\D/', '', $phone);
                        if (strlen($clean) == 12 && str_starts_with($clean, '91')) {
                            $clean = substr($clean, 2);
                        }
                        return $clean;
                    })
                    ->unique()
                    ->toArray();
            }
        }

        // Get all matching leads
        $allLeads = $query->get();

        // Filter out already-sent leads
        $filteredLeads = $allLeads->filter(function ($lead) use ($alreadySentPhones) {
            if (empty($alreadySentPhones))
                return true;
            $cleanPhone = preg_replace('/\D/', '', $lead->phone);
            if (strlen($cleanPhone) == 12 && str_starts_with($cleanPhone, '91')) {
                $cleanPhone = substr($cleanPhone, 2);
            }
            return !in_array($cleanPhone, $alreadySentPhones);
        });

        $totalBeforeDedup = $allLeads->count();
        $alreadySentCount = $totalBeforeDedup - $filteredLeads->count();
        $count = $filteredLeads->count();
        $leads = $filteredLeads->take(10)->map(function ($lead) {
            return ['id' => $lead->id, 'name' => $lead->name, 'phone' => $lead->phone];
        })->values();

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
            'already_sent_count' => $alreadySentCount,
            'total_before_dedup' => $totalBeforeDedup,
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

        $template = WhatsappTemplate::findOrFail($request->template_id);

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

        $allLeads = $query->get();

        // Deduplication: exclude phone numbers already sent with same template_code
        $alreadySentPhones = [];
        if ($template->template_code) {
            $sameCodeTemplateIds = WhatsappTemplate::where('template_code', $template->template_code)
                ->pluck('id');
            $campaignIds = WhatsappCampaign::whereIn('template_id', $sameCodeTemplateIds)->pluck('id');
            $alreadySentPhones = WhatsappCampaignRecipient::whereIn('campaign_id', $campaignIds)
                ->where('status', 'sent')
                ->pluck('phone_number')
                ->map(function ($phone) {
                    $clean = preg_replace('/\D/', '', $phone);
                    if (strlen($clean) == 12 && str_starts_with($clean, '91')) {
                        $clean = substr($clean, 2);
                    }
                    return $clean;
                })
                ->unique()
                ->toArray();
        }

        $leads = $allLeads->filter(function ($lead) use ($alreadySentPhones) {
            if (empty($alreadySentPhones))
                return true;
            $cleanPhone = preg_replace('/\D/', '', $lead->phone);
            if (strlen($cleanPhone) == 12 && str_starts_with($cleanPhone, '91')) {
                $cleanPhone = substr($cleanPhone, 2);
            }
            return !in_array($cleanPhone, $alreadySentPhones);
        });

        if ($leads->isEmpty()) {
            return redirect()->back()->with('error', 'No new leads found. All matching leads have already received this template code.');
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

            $skippedCount = $allLeads->count() - $leads->count();
            $msg = "Campaign created! {$leads->count()} recipients will receive messages.";
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} already sent with same template code were skipped)";
            }

            return redirect()->back()->with('success', $msg);
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

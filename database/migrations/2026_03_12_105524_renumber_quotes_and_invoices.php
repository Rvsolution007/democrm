<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renumber all existing quotes and invoices with the new pattern.
     * Quotes (lead quotes, not accepted): Q-25-26-000001
     * Invoices (client quotes / accepted): I-25-26-000001
     */
    public function up(): void
    {
        $now = now();

        // Determine financial year (April to March)
        if ($now->month >= 4) {
            $fyStart = $now->year;
            $fyEnd = $now->year + 1;
        } else {
            $fyStart = $now->year - 1;
            $fyEnd = $now->year;
        }

        $fy = substr($fyStart, -2) . '-' . substr($fyEnd, -2);

        // Renumber all non-accepted quotes (Quotes tab) with Q-YY-YY-NNNNNN
        $quotes = DB::table('quotes')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('client_id')
                  ->orWhere('status', '!=', 'accepted');
            })
            ->orderBy('id')
            ->get();

        $qSeq = 1;
        foreach ($quotes as $quote) {
            $newNo = sprintf('Q-%s-%06d', $fy, $qSeq);
            DB::table('quotes')->where('id', $quote->id)->update(['quote_no' => $newNo]);
            $qSeq++;
        }

        // Renumber all accepted client quotes (Invoices tab) with I-YY-YY-NNNNNN
        $invoices = DB::table('quotes')
            ->whereNull('deleted_at')
            ->whereNotNull('client_id')
            ->where('status', 'accepted')
            ->orderBy('id')
            ->get();

        $iSeq = 1;
        foreach ($invoices as $invoice) {
            $newNo = sprintf('I-%s-%06d', $fy, $iSeq);
            DB::table('quotes')->where('id', $invoice->id)->update(['quote_no' => $newNo]);
            $iSeq++;
        }
    }

    /**
     * Reverse the migrations (not reversible for numbering).
     */
    public function down(): void
    {
        // Cannot reverse renumbering
    }
};

<?php
// seed_demo_data.php — Standalone demo data seeder
// This file is included from the /seed-demo route in web.php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Disable FK checks
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Truncate all relevant tables
$tables = [
    'micro_tasks','task_user','task_activities','tasks',
    'project_user','projects',
    'purchase_custom_field_values','purchase_payments','purchases',
    'vendor_custom_fields','vendors',
    'quote_payments','quote_items','quote_user','quotes',
    'lead_followups','lead_product','lead_user','leads',
    'activities','clients','products','categories',
];
foreach ($tables as $t) {
    try { DB::table($t)->truncate(); } catch (\Exception $e) { /* skip */ }
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

$now = now();

// ── 1. Categories (10) ──
$categories = [
    ['company_id'=>1,'name'=>'Web Development','description'=>'Website & app development services','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Digital Marketing','description'=>'SEO, SMM, PPC services','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Graphic Design','description'=>'Logo, banner, brochure design','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Mobile Apps','description'=>'Android & iOS app development','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Cloud Hosting','description'=>'Server & cloud hosting services','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'IT Support','description'=>'Annual maintenance & support','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'E-Commerce','description'=>'Online store setup','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Video Production','description'=>'Corporate videos & ads','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Content Writing','description'=>'Blog, article & copy writing','status'=>'active','created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'name'=>'Software License','description'=>'Third-party software licenses','status'=>'active','created_at'=>$now,'updated_at'=>$now],
];
DB::table('categories')->insert($categories);

// ── 2. Products (10) ── (prices in paise)
$products = [
    ['company_id'=>1,'category_id'=>1,'created_by_user_id'=>1,'sku'=>'WEB-001','name'=>'Basic Website','description'=>'5-page responsive website','unit'=>'project','mrp'=>2500000,'sale_price'=>2000000,'gst_percent'=>18,'hsn_code'=>'998314','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>true,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>1,'created_by_user_id'=>1,'sku'=>'WEB-002','name'=>'E-Commerce Website','description'=>'Full featured online store','unit'=>'project','mrp'=>7500000,'sale_price'=>6500000,'gst_percent'=>18,'hsn_code'=>'998314','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>true,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>2,'created_by_user_id'=>1,'sku'=>'DM-001','name'=>'SEO Package (3 Months)','description'=>'On-page + Off-page SEO','unit'=>'package','mrp'=>3000000,'sale_price'=>2500000,'gst_percent'=>18,'hsn_code'=>'998366','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>false,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>2,'created_by_user_id'=>1,'sku'=>'DM-002','name'=>'Social Media Management','description'=>'Monthly SMM for 3 platforms','unit'=>'month','mrp'=>1500000,'sale_price'=>1200000,'gst_percent'=>18,'hsn_code'=>'998366','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>false,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>3,'created_by_user_id'=>1,'sku'=>'GD-001','name'=>'Logo Design','description'=>'Premium logo with 3 concepts','unit'=>'piece','mrp'=>800000,'sale_price'=>500000,'gst_percent'=>18,'hsn_code'=>'998396','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>true,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>4,'created_by_user_id'=>1,'sku'=>'MA-001','name'=>'Android App','description'=>'Custom Android application','unit'=>'project','mrp'=>15000000,'sale_price'=>12000000,'gst_percent'=>18,'hsn_code'=>'998314','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>true,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>5,'created_by_user_id'=>1,'sku'=>'CH-001','name'=>'VPS Hosting (Annual)','description'=>'4GB RAM, 80GB SSD','unit'=>'year','mrp'=>2400000,'sale_price'=>1800000,'gst_percent'=>18,'hsn_code'=>'998315','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>true,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>6,'created_by_user_id'=>1,'sku'=>'IT-001','name'=>'IT AMC (Annual)','description'=>'Annual maintenance contract','unit'=>'year','mrp'=>6000000,'sale_price'=>5000000,'gst_percent'=>18,'hsn_code'=>'998316','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>false,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>8,'created_by_user_id'=>1,'sku'=>'VP-001','name'=>'Corporate Video (60s)','description'=>'60 second corporate promo video','unit'=>'piece','mrp'=>5000000,'sale_price'=>4000000,'gst_percent'=>18,'hsn_code'=>'998397','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>true,'created_at'=>$now,'updated_at'=>$now],
    ['company_id'=>1,'category_id'=>9,'created_by_user_id'=>1,'sku'=>'CW-001','name'=>'Blog Pack (10 Articles)','description'=>'10 SEO-optimized blog articles','unit'=>'package','mrp'=>1000000,'sale_price'=>750000,'gst_percent'=>18,'hsn_code'=>'998366','stock_qty'=>999,'min_stock_qty'=>1,'status'=>'active','is_purchase_enabled'=>false,'created_at'=>$now,'updated_at'=>$now],
];
DB::table('products')->insert($products);

// ── 3. Leads (10) ──
$leadNames = ['Rahul Sharma','Priya Patel','Amit Gupta','Neha Singh','Vikram Joshi','Sneha Reddy','Arjun Kumar','Kavita Mehta','Rohit Verma','Pooja Thakur'];
$stages = ['new','contacted','qualified','proposal','negotiation','won','won','new','contacted','proposal'];
$sources = ['walk-in','reference','indiamart','facebook','website','whatsapp','call','reference','indiamart','website'];
$cities = ['Mumbai','Delhi','Bangalore','Pune','Chennai','Hyderabad','Ahmedabad','Jaipur','Kolkata','Lucknow'];

for ($i = 0; $i < 10; $i++) {
    DB::table('leads')->insert([
        'company_id' => 1,
        'created_by_user_id' => 1,
        'name' => $leadNames[$i],
        'phone' => '98' . str_pad($i + 1, 8, rand(10000000, 99999999), STR_PAD_LEFT),
        'email' => strtolower(str_replace(' ', '.', $leadNames[$i])) . '@example.com',
        'city' => $cities[$i],
        'state' => 'Maharashtra',
        'stage' => $stages[$i],
        'source' => $sources[$i],
        'expected_value' => rand(50000, 500000) * 100,
        'notes' => 'Demo lead - interested in our services',
        'created_at' => $now->copy()->subDays(rand(1, 30)),
        'updated_at' => $now,
    ]);
}

// ── 4. Clients (10 — first 5 linked to won/qualified leads) ──
$bizNames = ['TechNova Solutions','GreenLeaf Agro','SparkLine Media','BluePeak Construction','AquaFresh Industries','Zenith Textiles','CloudNine Retail','StarBright Education','Metro Auto Parts','PrimeHealth Clinic'];
$bizCategories = ['IT / Software','Agriculture','E-commerce','Construction Chemical','Manufacturing','Textile','Retail','Education','Automotive','Healthcare'];

for ($i = 0; $i < 10; $i++) {
    DB::table('clients')->insert([
        'company_id' => 1,
        'lead_id' => $i < 5 ? ($i + 1) : null,
        'created_by_user_id' => 1,
        'type' => 'business',
        'business_name' => $bizNames[$i],
        'business_category' => $bizCategories[$i],
        'contact_name' => $i < 5 ? $leadNames[$i] : $leadNames[$i + 5 > 9 ? $i : $i],
        'phone' => '97' . str_pad($i + 1, 8, rand(10000000, 99999999), STR_PAD_LEFT),
        'email' => 'info@' . strtolower(str_replace(' ', '', $bizNames[$i])) . '.com',
        'gstin' => '27' . strtoupper(substr(md5($bizNames[$i]), 0, 5)) . rand(1000, 9999) . 'A1Z' . rand(1, 9),
        'billing_address' => json_encode(['line1' => ($i+1)*100 . ' Business Park', 'city' => $cities[$i], 'state' => 'Maharashtra', 'pincode' => '4000' . str_pad($i+1, 2, '0', STR_PAD_LEFT)]),
        'credit_limit' => rand(100000, 1000000) * 100,
        'outstanding_amount' => rand(0, 50000) * 100,
        'payment_terms_days' => [15, 30, 45, 60][$i % 4],
        'status' => 'active',
        'created_at' => $now->copy()->subDays(rand(1, 25)),
        'updated_at' => $now,
    ]);
}

// ── 5. Quotes (10 — linked to clients, some with leads) ──
$quoteStatuses = ['draft','sent','accepted','accepted','sent','draft','accepted','sent','rejected','draft'];
for ($i = 0; $i < 10; $i++) {
    $subtotal = rand(100000, 1500000);
    $discount = rand(0, intval($subtotal * 0.1));
    $gst = intval(($subtotal - $discount) * 0.18);
    $grand = $subtotal - $discount + $gst;
    DB::table('quotes')->insert([
        'company_id' => 1,
        'client_id' => $i + 1,
        'lead_id' => $i < 5 ? ($i + 1) : null,
        'created_by_user_id' => 1,
        'quote_no' => sprintf('Q-25-26-%06d', $i + 1),
        'date' => $now->copy()->subDays(rand(1, 20))->toDateString(),
        'valid_till' => $now->copy()->addDays(rand(10, 30))->toDateString(),
        'subtotal' => $subtotal,
        'discount' => $discount,
        'gst_total' => $gst,
        'grand_total' => $grand,
        'status' => $quoteStatuses[$i],
        'notes' => 'Demo quote #' . ($i + 1),
        'created_at' => $now->copy()->subDays(rand(1, 20)),
        'updated_at' => $now,
    ]);
}

// ── 6. Quote Items (2–3 per quote) ──
for ($q = 1; $q <= 10; $q++) {
    $itemCount = rand(2, 3);
    for ($j = 0; $j < $itemCount; $j++) {
        $pid = rand(1, 10);
        $rate = rand(50000, 500000);
        $qty = rand(1, 5);
        $disc = rand(0, 5000);
        $net = ($rate * $qty) - $disc;
        $gstAmt = intval($net * 0.18);
        $lineTotal = $net + $gstAmt;
        DB::table('quote_items')->insert([
            'quote_id' => $q,
            'product_id' => $pid,
            'product_name' => $products[$pid - 1]['name'],
            'description' => $products[$pid - 1]['description'],
            'hsn_code' => $products[$pid - 1]['hsn_code'],
            'qty' => $qty,
            'rate' => $rate,
            'unit' => $products[$pid - 1]['unit'],
            'unit_price' => $rate,
            'discount' => $disc,
            'purchase_amount' => intval($rate * 0.6),
            'gst_percent' => 18,
            'gst_amount' => $gstAmt,
            'line_total' => $lineTotal,
            'sort_order' => $j + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// ── 7. Invoices (5 — converted from accepted quotes) ──
$invoiceQuotes = [3, 4, 7]; // accepted quote IDs
for ($i = 0; $i < count($invoiceQuotes); $i++) {
    $qId = $invoiceQuotes[$i];
    $quote = DB::table('quotes')->where('id', $qId)->first();
    DB::table('quotes')->insert([
        'company_id' => 1,
        'client_id' => $quote->client_id,
        'lead_id' => $quote->lead_id,
        'created_by_user_id' => 1,
        'quote_no' => sprintf('I-25-26-%06d', $i + 1),
        'date' => $now->copy()->subDays(rand(1, 10))->toDateString(),
        'valid_till' => $now->copy()->addDays(30)->toDateString(),
        'subtotal' => $quote->subtotal,
        'discount' => $quote->discount,
        'gst_total' => $quote->gst_total,
        'grand_total' => $quote->grand_total,
        'status' => 'accepted',
        'notes' => 'Invoice converted from Quote ' . $quote->quote_no,
        'created_at' => $now->copy()->subDays(rand(1, 5)),
        'updated_at' => $now,
    ]);
}

// ── 8. Quote Payments (10 payments across quotes/invoices) ──
for ($i = 0; $i < 10; $i++) {
    $qId = rand(1, 13); // 10 quotes + 3 invoices
    $quote = DB::table('quotes')->where('id', $qId)->first();
    if (!$quote) continue;
    DB::table('quote_payments')->insert([
        'company_id' => 1,
        'quote_id' => $qId,
        'amount' => intval($quote->grand_total * (rand(20, 60) / 100)),
        'payment_date' => $now->copy()->subDays(rand(0, 10))->toDateString(),
        'payment_type' => ['bank_transfer','upi','cash','cheque','card'][$i % 5],
        'reference_no' => 'REF-' . strtoupper(substr(md5(rand()), 0, 8)),
        'notes' => 'Demo payment #' . ($i + 1),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// ── 9. Vendors (10) ──
$vendorNames = ['PrintMax Solutions','HostKing Cloud','AdSpark Agency','CodeForge Labs','PixelPerfect Studio','DataStream Analytics','SecureNet Systems','LogiTech Hardware','ContentBridge Writers','CamPro Studios'];
for ($i = 0; $i < 10; $i++) {
    DB::table('vendors')->insert([
        'company_id' => 1,
        'name' => $vendorNames[$i],
        'phone' => '96' . str_pad($i + 1, 8, rand(10000000, 99999999), STR_PAD_LEFT),
        'email' => 'vendor@' . strtolower(str_replace(' ', '', $vendorNames[$i])) . '.com',
        'address' => ($i+1) * 50 . ' Vendor Lane, ' . $cities[$i],
        'status' => 'active',
        'has_purchase_section' => $i < 7,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// ── 10. Purchases (10 — linked to vendors, clients, products) ──
$purchaseStatuses = ['pending','partial','paid','pending','paid','partial','pending','paid','partial','pending'];
for ($i = 0; $i < 10; $i++) {
    $totalAmt = rand(50000, 500000);
    $paidAmt = $purchaseStatuses[$i] === 'paid' ? $totalAmt : ($purchaseStatuses[$i] === 'partial' ? intval($totalAmt * 0.5) : 0);
    DB::table('purchases')->insert([
        'company_id' => 1,
        'vendor_id' => ($i % 10) + 1,
        'client_id' => ($i % 10) + 1,
        'product_id' => ($i % 10) + 1,
        'purchase_no' => sprintf('PUR-2026-27-%06d', $i + 1),
        'date' => $now->copy()->subDays(rand(1, 20))->toDateString(),
        'total_amount' => $totalAmt,
        'paid_amount' => $paidAmt,
        'status' => $purchaseStatuses[$i],
        'notes' => 'Demo purchase from ' . $vendorNames[$i],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// ── 11. Purchase Payments (10) ──
for ($i = 0; $i < 10; $i++) {
    $purchase = DB::table('purchases')->where('id', $i + 1)->first();
    if (!$purchase || $purchase->paid_amount <= 0) continue;
    DB::table('purchase_payments')->insert([
        'company_id' => 1,
        'purchase_id' => $i + 1,
        'amount' => $purchase->paid_amount,
        'payment_date' => $now->copy()->subDays(rand(0, 10))->toDateString(),
        'payment_type' => ['bank_transfer','upi','cash','cheque','card'][$i % 5],
        'reference_no' => 'PREF-' . strtoupper(substr(md5(rand()), 0, 8)),
        'notes' => 'Payment for ' . $purchase->purchase_no,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// ── 12. Projects (10 — linked to clients and invoices) ──
$projectNames = ['TechNova Website Redesign','GreenLeaf E-Commerce Store','SparkLine Brand Identity','BluePeak CRM System','AquaFresh Inventory App','Zenith Marketing Campaign','CloudNine Mobile App','StarBright LMS Portal','Metro Parts Catalog','PrimeHealth Booking System'];
$projectStatuses = ['in_progress','in_progress','completed','pending','in_progress','on_hold','pending','in_progress','completed','pending'];
for ($i = 0; $i < 10; $i++) {
    DB::table('projects')->insert([
        'company_id' => 1,
        'client_id' => $i + 1,
        'quote_id' => $i < 3 ? (11 + $i) : null, // link first 3 to invoices
        'created_by_user_id' => 1,
        'name' => $projectNames[$i],
        'description' => 'Demo project for ' . $bizNames[$i],
        'status' => $projectStatuses[$i],
        'start_date' => $now->copy()->subDays(rand(10, 30))->toDateString(),
        'due_date' => $now->copy()->addDays(rand(15, 60))->toDateString(),
        'budget' => rand(200000, 2000000),
        'created_at' => $now->copy()->subDays(rand(5, 25)),
        'updated_at' => $now,
    ]);
}

// ── 13. Tasks (10 — linked to projects and clients) ──
$taskTitles = ['Setup Development Environment','Design Homepage Mockup','Implement Login System','Database Schema Design','API Integration','Payment Gateway Setup','Testing & QA','Content Upload','Deploy to Production','Client Training Session'];
$taskStatuses = ['done','doing','doing','todo','doing','todo','done','doing','done','todo'];
$taskPriorities = ['high','medium','high','low','medium','high','medium','low','high','medium'];
for ($i = 0; $i < 10; $i++) {
    DB::table('tasks')->insert([
        'company_id' => 1,
        'project_id' => ($i % 10) + 1,
        'created_by_user_id' => 1,
        'entity_type' => 'client',
        'entity_id' => ($i % 10) + 1,
        'title' => $taskTitles[$i],
        'description' => 'Demo task: ' . $taskTitles[$i],
        'contact_number' => '95' . str_pad($i + 1, 8, rand(10000000, 99999999), STR_PAD_LEFT),
        'due_at' => $now->copy()->addDays(rand(-5, 15)),
        'priority' => $taskPriorities[$i],
        'status' => $taskStatuses[$i],
        'completed_at' => $taskStatuses[$i] === 'done' ? $now : null,
        'sort_order' => $i + 1,
        'created_at' => $now->copy()->subDays(rand(1, 15)),
        'updated_at' => $now,
    ]);
}

// Assign tasks to users
for ($i = 1; $i <= 10; $i++) {
    try {
        DB::table('task_user')->insert(['task_id' => $i, 'user_id' => ($i % 2) + 1]);
    } catch (\Exception $e) {}
}

// ── 14. Micro Tasks (2-3 per task) ──
for ($t = 1; $t <= 10; $t++) {
    $count = rand(2, 3);
    for ($j = 0; $j < $count; $j++) {
        $mStatuses = ['todo','doing','done'];
        DB::table('micro_tasks')->insert([
            'task_id' => $t,
            'title' => 'Subtask ' . ($j + 1) . ' for ' . $taskTitles[$t - 1],
            'status' => $mStatuses[$j % 3],
            'sort_order' => $j + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// ── 15. Lead Followups (10) ──
for ($i = 1; $i <= 10; $i++) {
    $lead = DB::table('leads')->where('id', $i)->first();
    if (!$lead || in_array($lead->stage, ['won', 'lost'])) continue;
    DB::table('lead_followups')->insert([
        'lead_id' => $i,
        'user_id' => ($i % 2) + 1,
        'type' => ['call','email','meeting','whatsapp','visit'][$i % 5],
        'notes' => 'Follow up with ' . $lead->name . ' regarding project requirements',
        'follow_up_at' => $now->copy()->addDays(rand(1, 7)),
        'status' => ['pending','completed','pending','completed','pending'][$i % 5],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// ── 16. Activities (10) ──
$actTypes = ['note','call','email','meeting','status_change','note','call','email','meeting','status_change'];
for ($i = 0; $i < 10; $i++) {
    DB::table('activities')->insert([
        'company_id' => 1,
        'created_by_user_id' => ($i % 2) + 1,
        'entity_type' => $i < 5 ? 'lead' : 'client',
        'entity_id' => ($i % 5) + 1,
        'type' => $actTypes[$i],
        'subject' => 'Demo activity: ' . ucfirst($actTypes[$i]),
        'summary' => 'This is a demo ' . $actTypes[$i] . ' activity for testing purposes.',
        'created_at' => $now->copy()->subDays(rand(0, 10)),
        'updated_at' => $now,
    ]);
}

return 'SUCCESS! Demo data seeded in database "democrm": categories(10), products(10), leads(10), clients(10), quotes(10), invoices(3), quote_payments(10), vendors(10), purchases(10), purchase_payments(~7), projects(10), tasks(10), micro_tasks(~25), lead_followups(~6), activities(10)';

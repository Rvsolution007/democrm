<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemCustomColumnsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            $systemColumns = [
                [
                    'name' => 'Product Name',
                    'slug' => 'name',
                    'type' => 'text',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => [],
                    'is_required' => true,
                    'sort_order' => 10,
                    'show_on_list' => true,
                    'connected_modules' => ['Quotes', 'Invoices', 'AI Bot', 'Chatflow'],
                ],
                [
                    'name' => 'Description',
                    'slug' => 'description',
                    'type' => 'textarea',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => [],
                    'is_required' => false,
                    'sort_order' => 20,
                    'show_on_list' => false,
                    'connected_modules' => ['AI Bot', 'Quotes'],
                ],
                [
                    'name' => 'Sale Price',
                    'slug' => 'sale_price',
                    'type' => 'number',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => [],
                    'is_required' => true,
                    'sort_order' => 30,
                    'show_on_list' => true,
                    'connected_modules' => ['Quotes', 'Invoices', 'AI Bot'],
                ],
                [
                    'name' => 'MRP',
                    'slug' => 'mrp',
                    'type' => 'number',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => [],
                    'is_required' => false,
                    'sort_order' => 35,
                    'show_on_list' => false,
                    'connected_modules' => ['Quotes', 'Invoices'],
                ],
                [
                    'name' => 'HSN Code',
                    'slug' => 'hsn_code',
                    'type' => 'text',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => [],
                    'is_required' => false,
                    'sort_order' => 40,
                    'show_on_list' => false,
                    'connected_modules' => ['Invoices', 'Quotes'],
                ],
                [
                    'name' => 'GST %',
                    'slug' => 'gst_percent',
                    'type' => 'number',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => [],
                    'is_required' => false,
                    'sort_order' => 50,
                    'show_on_list' => false,
                    'connected_modules' => ['Invoices', 'Quotes'],
                ],
                [
                    'name' => 'Unit',
                    'slug' => 'unit',
                    'type' => 'select',
                    'is_system' => true,
                    'is_active' => true,
                    'is_combo' => false,
                    'options' => ['Nos', 'Kg', 'Ltr', 'Mtr', 'Pcs', 'Box', 'Roll', 'Set'],
                    'is_required' => false,
                    'sort_order' => 60,
                    'show_on_list' => false,
                    'connected_modules' => ['Invoices', 'Quotes'],
                ],
            ];

            foreach ($systemColumns as $colData) {
                // Use firstOrCreate to avoid duplicating system columns if seeder is run twice
                \App\Models\CatalogueCustomColumn::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'slug' => $colData['slug'],
                    ],
                    $colData
                );
            }
        }
    }
}

<?php

namespace App\Services;

use App\Models\CatalogueCustomColumn;
use App\Models\CatalogueCustomValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCombo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductExcelService
{
    protected int $companyId;
    protected int $userId;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
    }

    protected function getColumns()
    {
        return CatalogueCustomColumn::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    protected function getCategories()
    {
        return Category::where('company_id', $this->companyId)
            ->orderBy('name')
            ->get();
    }

    // ═══════════════════════════════════════════════════════════
    // DEMO EXCEL GENERATION
    // ═══════════════════════════════════════════════════════════

    public function generateDemoExcel(): Spreadsheet
    {
        $columns = $this->getColumns();
        $categories = $this->getCategories();

        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Products ──
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Products');

        $colIndex = 1;
        $headerMap = [];

        foreach ($columns as $col) {
            $letter = Coordinate::stringFromColumnIndex($colIndex);
            $headerName = $col->name;
            if ($col->is_combo) $headerName .= ' (combo)';

            $sheet1->setCellValue($letter . '1', $headerName);
            $sheet1->setCellValue($letter . '2', $this->getSampleValue($col, $categories));
            $sheet1->getColumnDimension($letter)->setAutoSize(true);

            $headerMap[$colIndex] = $col;
            $colIndex++;
        }

        // Style header row
        $lastLetter = Coordinate::stringFromColumnIndex(max($colIndex - 1, 1));
        $sheet1->getStyle("A1:{$lastLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ]);
        $sheet1->getRowDimension(1)->setRowHeight(28);

        // Style sample row
        $sheet1->getStyle("A2:{$lastLetter}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F3FF']],
        ]);

        // ── Sheet 2: Lookups ──
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Lookups');

        $lookupCol = 1;
        $lookupMapping = []; // key => ['letter' => X, 'count' => Y]

        // Categories lookup
        $hasCategoryCol = $columns->where('is_category', true)->first();
        if ($hasCategoryCol && $categories->count() > 0) {
            $catLetter = Coordinate::stringFromColumnIndex($lookupCol);
            $sheet2->setCellValue($catLetter . '1', 'Categories');
            $sheet2->getStyle($catLetter . '1')->getFont()->setBold(true);
            $r = 2;
            foreach ($categories as $cat) {
                $sheet2->setCellValue($catLetter . $r, $cat->name);
                $r++;
            }
            $lookupMapping['category'] = ['letter' => $catLetter, 'count' => $categories->count()];
            $sheet2->getColumnDimension($catLetter)->setAutoSize(true);
            $lookupCol++;
        }

        // Select/combo options lookup
        foreach ($columns as $col) {
            if (!$col->options || count($col->options) === 0) continue;
            if ($col->is_category) continue;

            $optsLetter = Coordinate::stringFromColumnIndex($lookupCol);
            $sheet2->setCellValue($optsLetter . '1', $col->name);
            $sheet2->getStyle($optsLetter . '1')->getFont()->setBold(true);
            $r = 2;
            foreach ($col->options as $opt) {
                $sheet2->setCellValue($optsLetter . $r, $opt);
                $r++;
            }
            $lookupMapping[$col->id] = ['letter' => $optsLetter, 'count' => count($col->options)];
            $sheet2->getColumnDimension($optsLetter)->setAutoSize(true);
            $lookupCol++;
        }

        // Style Sheet2 header
        if ($lookupCol > 1) {
            $lastLookup = Coordinate::stringFromColumnIndex($lookupCol - 1);
            $sheet2->getStyle("A1:{$lastLookup}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
            ]);
        }

        // ── Data Validations on Sheet1 ──
        $colIndex = 1;
        $maxValidationRows = 200;

        foreach ($columns as $col) {
            $dLetter = Coordinate::stringFromColumnIndex($colIndex);

            if ($col->is_category && isset($lookupMapping['category'])) {
                $ref = $lookupMapping['category'];
                $formula = "Lookups!\${$ref['letter']}\$2:\${$ref['letter']}\$" . ($ref['count'] + 1);
                $this->applyListValidation($sheet1, $dLetter, 2, $maxValidationRows, $formula, 'Category', !$col->is_required, 'warning');
            } elseif ($col->type === 'select' && !$col->is_combo && isset($lookupMapping[$col->id])) {
                $ref = $lookupMapping[$col->id];
                $formula = "Lookups!\${$ref['letter']}\$2:\${$ref['letter']}\$" . ($ref['count'] + 1);
                $this->applyListValidation($sheet1, $dLetter, 2, $maxValidationRows, $formula, $col->name, !$col->is_required, 'stop');
            } elseif ($col->type === 'boolean') {
                $this->applyListValidation($sheet1, $dLetter, 2, $maxValidationRows, '"Yes,No"', $col->name, !$col->is_required, 'stop');
            }

            $colIndex++;
        }

        $sheet1->freezePane('A2');
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function applyListValidation($sheet, string $letter, int $startRow, int $endRow, string $formula, string $title, bool $allowBlank, string $style): void
    {
        $errorStyle = $style === 'warning' ? DataValidation::STYLE_WARNING : DataValidation::STYLE_STOP;

        // Create validation on first cell, then clone for range
        $baseValidation = new DataValidation();
        $baseValidation->setType(DataValidation::TYPE_LIST);
        $baseValidation->setErrorStyle($errorStyle);
        $baseValidation->setAllowBlank($allowBlank);
        $baseValidation->setShowDropDown(true);
        $baseValidation->setFormula1($formula);
        $baseValidation->setErrorTitle("Invalid {$title}");
        $baseValidation->setError("Please select a valid {$title}.");
        $baseValidation->setShowErrorMessage(true);

        for ($r = $startRow; $r <= $startRow + $endRow; $r++) {
            $sheet->getCell($letter . $r)->setDataValidation(clone $baseValidation);
        }
    }

    private function getSampleValue($col, $categories): string
    {
        if ($col->is_category) return $categories->first()->name ?? 'Category Name';

        if ($col->is_combo) {
            $opts = $col->options ?? [];
            return count($opts) > 1 ? $opts[0] . ' | ' . $opts[1] : ($opts[0] ?? 'Option1');
        }

        return match (true) {
            $col->slug === 'sku' => 'SKU-001',
            $col->slug === 'name' => 'Sample Product',
            $col->slug === 'description' => 'Sample description',
            $col->slug === 'hsn_code' => '8302',
            in_array($col->slug, ['sale_price', 'mrp']) => '1500',
            $col->slug === 'gst_percent' => '18',
            $col->type === 'number' => '100',
            $col->type === 'select' => $col->options[0] ?? 'Option',
            $col->type === 'boolean' => 'Yes',
            $col->type === 'textarea' => 'Sample description text',
            default => 'Sample ' . $col->name,
        };
    }

    // ═══════════════════════════════════════════════════════════
    // EXCEL VALIDATION (Step 1)
    // ═══════════════════════════════════════════════════════════

    public function validateExcel(string $filePath): array
    {
        $columns = $this->getColumns();
        $categories = $this->getCategories();

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // Map headers to catalogue columns
        $headers = [];
        for ($c = 1; $c <= $highestColIdx; $c++) {
            $headers[$c] = trim($sheet->getCell(Coordinate::stringFromColumnIndex($c) . '1')->getValue() ?? '');
        }

        $columnMapping = [];
        $unmappedHeaders = [];
        foreach ($headers as $idx => $name) {
            if (empty($name)) continue;
            $clean = preg_replace('/\s*\(combo\)\s*$/i', '', $name);
            $match = $columns->first(fn($col) => mb_strtolower(trim($col->name)) === mb_strtolower(trim($clean)));
            if ($match) $columnMapping[$idx] = $match;
            else $unmappedHeaders[] = $name;
        }

        $validRows = 0;
        $categoryErrors = [];
        $hardErrors = [];
        $missingCategories = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->readRow($sheet, $row, $highestColIdx);
            if ($rowData === null) continue; // empty row

            $catErrs = [];
            $otherErrs = [];

            foreach ($columnMapping as $colIdx => $col) {
                $value = trim($rowData[$colIdx] ?? '');
                $errors = $this->validateCell($col, $value, $categories);

                foreach ($errors as $err) {
                    if ($err['type'] === 'category') {
                        $catErrs[] = $err['message'];
                        $missingCategories[$err['category']] = $err['category'];
                    } else {
                        $otherErrs[] = $err['message'];
                    }
                }
            }

            if (count($catErrs) === 0 && count($otherErrs) === 0) {
                $validRows++;
            } elseif (count($otherErrs) === 0 && count($catErrs) > 0) {
                $categoryErrors[] = ['row' => $row, 'errors' => $catErrs];
            } else {
                $hardErrors[] = ['row' => $row, 'errors' => array_merge($catErrs, $otherErrs)];
            }
        }

        return [
            'valid_count' => $validRows,
            'category_error_count' => count($categoryErrors),
            'hard_error_count' => count($hardErrors),
            'category_errors' => $categoryErrors,
            'hard_errors' => $hardErrors,
            'missing_categories' => array_values($missingCategories),
            'unmapped_headers' => $unmappedHeaders,
        ];
    }

    private function readRow($sheet, int $row, int $maxCol): ?array
    {
        $data = [];
        $isEmpty = true;
        for ($c = 1; $c <= $maxCol; $c++) {
            $val = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $row)->getValue();
            if ($val !== null && $val !== '') $isEmpty = false;
            $data[$c] = $val;
        }
        return $isEmpty ? null : $data;
    }

    private function validateCell($col, string $value, $categories): array
    {
        $errors = [];

        if ($col->is_required && $value === '') {
            $errors[] = ['type' => 'other', 'message' => "\"{$col->name}\" is required"];
            return $errors;
        }
        if ($value === '') return [];

        // Category
        if ($col->is_category) {
            $match = $categories->first(fn($cat) => mb_strtolower(trim($cat->name)) === mb_strtolower(trim($value)));
            if (!$match) {
                $errors[] = ['type' => 'category', 'message' => "Category \"{$value}\" not found", 'category' => $value];
            }
            return $errors;
        }

        // Select (non-combo)
        if ($col->type === 'select' && !$col->is_combo) {
            $options = $col->options ?? [];
            if (count($options) > 0 && !$this->matchOption($value, $options)) {
                $errors[] = ['type' => 'other', 'message' => "\"{$col->name}\" value \"{$value}\" invalid. Valid: " . implode(', ', $options)];
            }
        }

        // Combo
        if ($col->is_combo) {
            $comboValues = array_filter(array_map('trim', explode('|', $value)));
            $options = $col->options ?? [];
            if (count($options) > 0) {
                foreach ($comboValues as $cv) {
                    if (!$this->matchOption($cv, $options)) {
                        $errors[] = ['type' => 'other', 'message' => "Combo \"{$col->name}\" value \"{$cv}\" invalid. Valid: " . implode(', ', $options)];
                    }
                }
            }
        }

        // Number
        if ($col->type === 'number' && !is_numeric($value)) {
            $errors[] = ['type' => 'other', 'message' => "\"{$col->name}\" expects a number, got \"{$value}\""];
        }

        // Boolean
        if ($col->type === 'boolean') {
            if (!in_array(mb_strtolower($value), ['yes', 'no', '1', '0', 'true', 'false'])) {
                $errors[] = ['type' => 'other', 'message' => "\"{$col->name}\" expects Yes/No, got \"{$value}\""];
            }
        }

        return $errors;
    }

    private function matchOption(string $value, array $options): bool
    {
        foreach ($options as $opt) {
            if (mb_strtolower(trim($opt)) === mb_strtolower(trim($value))) return true;
        }
        return false;
    }

    private function findOption(string $value, array $options): ?string
    {
        foreach ($options as $opt) {
            if (mb_strtolower(trim($opt)) === mb_strtolower(trim($value))) return $opt;
        }
        return null;
    }

    // ═══════════════════════════════════════════════════════════
    // EXCEL PROCESSING (Step 2)
    // ═══════════════════════════════════════════════════════════

    public function processImport(string $filePath, string $categoryAction = 'skip'): array
    {
        $columns = $this->getColumns();
        $categories = $this->getCategories();

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // Map headers
        $headers = [];
        for ($c = 1; $c <= $highestColIdx; $c++) {
            $headers[$c] = trim($sheet->getCell(Coordinate::stringFromColumnIndex($c) . '1')->getValue() ?? '');
        }
        $columnMapping = [];
        foreach ($headers as $idx => $name) {
            if (empty($name)) continue;
            $clean = preg_replace('/\s*\(combo\)\s*$/i', '', $name);
            $match = $columns->first(fn($col) => mb_strtolower(trim($col->name)) === mb_strtolower(trim($clean)));
            if ($match) $columnMapping[$idx] = $match;
        }

        $uniqueCol = $columns->where('is_unique', true)->first();
        $categoriesMap = [];
        foreach ($categories as $cat) {
            $categoriesMap[mb_strtolower(trim($cat->name))] = $cat;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $createdCategories = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->readRow($sheet, $row, $highestColIdx);
            if ($rowData === null) continue;

            $rowErrors = [];
            $systemData = [];
            $customData = [];
            $comboData = [];
            $categoryId = null;

            foreach ($columnMapping as $colIdx => $col) {
                $value = trim($rowData[$colIdx] ?? '');

                // Required check
                if ($col->is_required && $value === '') {
                    $rowErrors[] = "\"{$col->name}\" is required";
                    continue;
                }
                if ($value === '') continue;

                // ── Category ──
                if ($col->is_category) {
                    $catKey = mb_strtolower(trim($value));
                    if (isset($categoriesMap[$catKey])) {
                        $categoryId = $categoriesMap[$catKey]->id;
                    } elseif ($categoryAction === 'create') {
                        $newCat = Category::create([
                            'company_id' => $this->companyId,
                            'created_by_user_id' => $this->userId,
                            'name' => trim($value),
                            'status' => 'active',
                        ]);
                        $categoriesMap[$catKey] = $newCat;
                        $categoryId = $newCat->id;
                        $createdCategories[] = trim($value);
                    } else {
                        $rowErrors[] = "Category \"{$value}\" not found";
                    }
                    continue;
                }

                // ── Combo ──
                if ($col->is_combo) {
                    $comboValues = array_filter(array_map('trim', explode('|', $value)));
                    $validCombo = [];
                    $options = $col->options ?? [];
                    foreach ($comboValues as $cv) {
                        if (count($options) > 0) {
                            $matched = $this->findOption($cv, $options);
                            if ($matched) { $validCombo[] = $matched; }
                            else { $rowErrors[] = "Combo \"{$col->name}\" value \"{$cv}\" invalid"; }
                        } else {
                            $validCombo[] = $cv;
                        }
                    }
                    if (count($validCombo) > 0) $comboData[$col->id] = $validCombo;
                    continue;
                }

                // ── Select ──
                if ($col->type === 'select' && !$col->is_category) {
                    $options = $col->options ?? [];
                    if (count($options) > 0) {
                        $matched = $this->findOption($value, $options);
                        if (!$matched) {
                            $rowErrors[] = "\"{$col->name}\" value \"{$value}\" invalid";
                            continue;
                        }
                        $value = $matched;
                    }
                }

                // ── Boolean ──
                if ($col->type === 'boolean') {
                    $lower = mb_strtolower($value);
                    if (in_array($lower, ['yes', '1', 'true'])) $value = '1';
                    elseif (in_array($lower, ['no', '0', 'false'])) $value = '0';
                    else { $rowErrors[] = "\"{$col->name}\" expects Yes/No"; continue; }
                }

                // ── Number ──
                if ($col->type === 'number') {
                    if (!is_numeric($value)) { $rowErrors[] = "\"{$col->name}\" expects a number"; continue; }
                    $value = (float)$value;
                }

                // Store
                if ($col->is_system) {
                    $systemData[$col->slug] = $value;
                } else {
                    $customData[$col->id] = $value;
                }
            }

            if (count($rowErrors) > 0) {
                $errors[] = ['row' => $row, 'errors' => $rowErrors];
                $skipped++;
                continue;
            }

            // Convert prices to paise
            if (isset($systemData['sale_price'])) $systemData['sale_price'] = round($systemData['sale_price'] * 100);
            if (isset($systemData['mrp'])) $systemData['mrp'] = round($systemData['mrp'] * 100);

            // Inject defaults
            $systemData['company_id'] = $this->companyId;
            $systemData['created_by_user_id'] = $this->userId;
            if ($categoryId) $systemData['category_id'] = $categoryId;

            $defaults = ['name' => 'Unnamed Product', 'sku' => 'AUTO-' . strtoupper(uniqid()), 'description' => '', 'sale_price' => 0, 'mrp' => 0, 'gst_percent' => 0, 'hsn_code' => '', 'unit' => ''];
            foreach ($defaults as $k => $v) {
                if (!isset($systemData[$k])) $systemData[$k] = $v;
            }

            try {
                // ── Upsert on unique column ──
                $product = null;
                if ($uniqueCol) {
                    $uniqueValue = $uniqueCol->is_system
                        ? ($systemData[$uniqueCol->slug] ?? null)
                        : ($customData[$uniqueCol->id] ?? null);

                    if ($uniqueValue) {
                        if ($uniqueCol->is_system) {
                            $product = Product::where('company_id', $this->companyId)->where($uniqueCol->slug, $uniqueValue)->first();
                        } else {
                            $ev = CatalogueCustomValue::where('column_id', $uniqueCol->id)->where('value', $uniqueValue)
                                ->whereHas('product', fn($q) => $q->where('company_id', $this->companyId))->first();
                            if ($ev) $product = $ev->product;
                        }
                    }
                }

                if ($product) {
                    $product->update($systemData);
                    $updated++;
                } else {
                    $product = Product::create($systemData);
                    $created++;
                }

                // Custom data
                foreach ($customData as $colId => $val) {
                    CatalogueCustomValue::updateOrCreate(
                        ['product_id' => $product->id, 'column_id' => $colId],
                        ['value' => is_array($val) ? json_encode($val) : $val]
                    );
                }

                // Combo data
                foreach ($comboData as $colId => $vals) {
                    CatalogueCustomValue::updateOrCreate(
                        ['product_id' => $product->id, 'column_id' => $colId],
                        ['value' => json_encode(array_values($vals))]
                    );
                    ProductCombo::updateOrCreate(
                        ['product_id' => $product->id, 'column_id' => $colId],
                        ['selected_values' => array_values($vals)]
                    );
                }
            } catch (\Exception $e) {
                $errors[] = ['row' => $row, 'errors' => ['System error: ' . $e->getMessage()]];
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'created_categories' => array_unique($createdCategories),
            'total' => $created + $updated + $skipped,
        ];
    }
}

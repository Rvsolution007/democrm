<?php

namespace App\Services;

use App\Models\CatalogueCustomColumn;
use App\Models\Category;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;

class CatalogueColumnImportService
{
    protected int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Import catalogue columns from Excel file
     *
     * @param string $filePath  Path to the Excel file
     * @return array  {created: int, skipped: int, errors: [], categories_created: []}
     */
    public function importFromExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // Read headers
        $headers = [];
        for ($c = 1; $c <= $highestColIdx; $c++) {
            $headers[$c] = trim($sheet->getCell(Coordinate::stringFromColumnIndex($c) . '1')->getValue() ?? '');
        }

        // Map headers to expected fields
        $fieldMap = $this->mapHeaders($headers);

        if (!isset($fieldMap['name'])) {
            throw new \RuntimeException('Excel must have a "Name" column in the header row.');
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $categoriesCreated = [];

        // Get existing column slugs to prevent duplicates
        $existingSlugs = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->pluck('slug')
            ->toArray();

        // Get max sort_order
        $maxSort = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->max('sort_order') ?? 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $isEmpty = true;

            for ($c = 1; $c <= $highestColIdx; $c++) {
                $val = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $row)->getValue();
                if ($val !== null && $val !== '') $isEmpty = false;
                $rowData[$c] = trim($val ?? '');
            }

            if ($isEmpty) continue;

            // Extract fields
            $name = $rowData[$fieldMap['name']] ?? '';
            if (empty($name)) {
                $errors[] = ['row' => $row, 'error' => 'Name is empty'];
                $skipped++;
                continue;
            }

            $slug = Str::slug($name, '_');

            // Skip duplicate slugs
            if (in_array($slug, $existingSlugs)) {
                $skipped++;
                continue;
            }

            $type = $this->normalizeType($rowData[$fieldMap['type'] ?? 0] ?? 'text');
            $optionsStr = $rowData[$fieldMap['options'] ?? 0] ?? '';
            $options = !empty($optionsStr)
                ? array_values(array_filter(array_map('trim', explode(',', $optionsStr))))
                : null;

            $isRequired = $this->parseBoolean($rowData[$fieldMap['is_required'] ?? 0] ?? 'No');
            $isUnique = $this->parseBoolean($rowData[$fieldMap['is_unique'] ?? 0] ?? 'No');
            $isCategory = $this->parseBoolean($rowData[$fieldMap['is_category'] ?? 0] ?? 'No');
            $isTitle = $this->parseBoolean($rowData[$fieldMap['is_title'] ?? 0] ?? 'No');
            $isCombo = $this->parseBoolean($rowData[$fieldMap['is_combo'] ?? 0] ?? 'No');
            $showInAI = $this->parseBoolean($rowData[$fieldMap['show_in_ai'] ?? 0] ?? 'Yes');
            $sortOrder = intval($rowData[$fieldMap['sort_order'] ?? 0] ?? 0);
            if ($sortOrder <= 0) $sortOrder = ++$maxSort;

            // If category column: auto-create categories from options
            if ($isCategory && !empty($options)) {
                foreach ($options as $catName) {
                    $catSlug = Str::slug($catName);
                    $exists = Category::where('company_id', $this->companyId)
                        ->where('slug', $catSlug)
                        ->exists();

                    if (!$exists) {
                        Category::create([
                            'company_id' => $this->companyId,
                            'created_by_user_id' => auth()->id(),
                            'name' => $catName,
                            'slug' => $catSlug,
                            'status' => 'active',
                        ]);
                        $categoriesCreated[] = $catName;
                    }
                }
            }

            try {
                CatalogueCustomColumn::create([
                    'company_id' => $this->companyId,
                    'name' => $name,
                    'slug' => $slug,
                    'type' => $type,
                    'options' => (in_array($type, ['select', 'multiselect']) && $options) ? $options : null,
                    'is_required' => $isRequired,
                    'is_unique' => $isUnique,
                    'is_category' => $isCategory,
                    'is_title' => $isTitle,
                    'is_combo' => $isCombo,
                    'is_system' => false,
                    'is_active' => true,
                    'show_on_list' => true,
                    'show_in_ai' => $showInAI,
                    'sort_order' => $sortOrder,
                ]);

                $existingSlugs[] = $slug;
                $created++;

            } catch (\Exception $e) {
                $errors[] = ['row' => $row, 'error' => $e->getMessage()];
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'categories_created' => array_unique($categoriesCreated),
        ];
    }

    /**
     * Import columns directly from array (AI-analyzed, no Excel)
     */
    public function importFromArray(array $columns): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];
        $categoriesCreated = [];

        $existingSlugs = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->pluck('slug')
            ->toArray();

        $maxSort = CatalogueCustomColumn::where('company_id', $this->companyId)
            ->max('sort_order') ?? 0;

        foreach ($columns as $col) {
            $name = $col['name'] ?? '';
            if (empty($name)) continue;

            $slug = Str::slug($name, '_');
            if (in_array($slug, $existingSlugs)) {
                $skipped++;
                continue;
            }

            $type = $col['type'] ?? 'text';
            $options = $col['options'] ?? null;
            $isCategory = $col['is_category'] ?? false;

            // Auto-create categories
            if ($isCategory && !empty($options)) {
                foreach ($options as $catName) {
                    $catSlug = Str::slug($catName);
                    $exists = Category::where('company_id', $this->companyId)
                        ->where('slug', $catSlug)->exists();
                    if (!$exists) {
                        Category::create([
                            'company_id' => $this->companyId,
                            'created_by_user_id' => auth()->id(),
                            'name' => $catName,
                            'slug' => $catSlug,
                            'status' => 'active',
                        ]);
                        $categoriesCreated[] = $catName;
                    }
                }
            }

            try {
                CatalogueCustomColumn::create([
                    'company_id' => $this->companyId,
                    'name' => $name,
                    'slug' => $slug,
                    'type' => $type,
                    'options' => (in_array($type, ['select', 'multiselect']) || !empty($col['is_combo']))
                        ? ($options ?: null) : null,
                    'is_required' => $col['is_required'] ?? false,
                    'is_unique' => $col['is_unique'] ?? false,
                    'is_category' => $isCategory,
                    'is_title' => $col['is_title'] ?? false,
                    'is_combo' => $col['is_combo'] ?? false,
                    'is_system' => false,
                    'is_active' => true,
                    'show_on_list' => true,
                    'show_in_ai' => $col['show_in_ai'] ?? true,
                    'sort_order' => $col['sort_order'] ?? (++$maxSort),
                ]);

                $existingSlugs[] = $slug;
                $created++;
            } catch (\Exception $e) {
                $errors[] = ['column' => $name, 'error' => $e->getMessage()];
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'categories_created' => array_unique($categoriesCreated),
        ];
    }

    /**
     * Map Excel headers to expected field names
     */
    private function mapHeaders(array $headers): array
    {
        $map = [];
        $aliases = [
            'name' => ['name', 'field name', 'column name', 'label', 'field label'],
            'type' => ['type', 'input type', 'field type', 'data type'],
            'options' => ['options', 'dropdown options', 'options (comma-separated)', 'values'],
            'is_required' => ['is required', 'required', 'is_required', 'mandatory'],
            'is_unique' => ['is unique', 'unique', 'is_unique', 'unique identifier'],
            'is_category' => ['is category', 'category', 'is_category', 'category linked'],
            'is_title' => ['is title', 'title', 'is_title', 'display title'],
            'is_combo' => ['is combo', 'combo', 'is_combo', 'variation', 'variation matrix'],
            'show_in_ai' => ['show in ai', 'ai', 'show_in_ai', 'ai bot access'],
            'sort_order' => ['sort order', 'order', 'sort_order', 'position'],
        ];

        foreach ($headers as $colIdx => $header) {
            $normalized = mb_strtolower(trim($header));
            foreach ($aliases as $field => $alts) {
                foreach ($alts as $alt) {
                    if ($normalized === $alt) {
                        $map[$field] = $colIdx;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Normalize type string to valid column type
     */
    private function normalizeType(string $type): string
    {
        $type = mb_strtolower(trim($type));
        $valid = ['text', 'textarea', 'number', 'select', 'multiselect', 'boolean'];

        if (in_array($type, $valid)) return $type;

        // Aliases
        $aliases = [
            'string' => 'text', 'short text' => 'text', 'varchar' => 'text',
            'long text' => 'textarea', 'description' => 'textarea', 'memo' => 'textarea',
            'int' => 'number', 'integer' => 'number', 'decimal' => 'number', 'float' => 'number', 'numeric' => 'number',
            'dropdown' => 'select', 'list' => 'select', 'enum' => 'select',
            'multi-select' => 'multiselect', 'multi select' => 'multiselect', 'tags' => 'multiselect',
            'yes/no' => 'boolean', 'bool' => 'boolean', 'switch' => 'boolean', 'toggle' => 'boolean',
        ];

        return $aliases[$type] ?? 'text';
    }

    /**
     * Parse Yes/No/True/False to boolean
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        $str = mb_strtolower(trim($value));
        return in_array($str, ['yes', '1', 'true']);
    }
}

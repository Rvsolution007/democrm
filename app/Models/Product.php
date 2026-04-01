<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToCompany;

class Product extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $appends = ['display_name'];

    public static $uniqueColumnCache = [];
    public static $titleColumnCache = [];

    /**
     * Get the display name for this product.
     * Priority: is_title column → is_unique column → native name
     */
    public function getDisplayNameAttribute()
    {
        if (!$this->company_id) return $this->name;

        // Check for is_title column first
        if (!array_key_exists($this->company_id, self::$titleColumnCache)) {
            self::$titleColumnCache[$this->company_id] = \App\Models\CatalogueCustomColumn::where('company_id', $this->company_id)
                ->where('is_title', true)->first();
        }

        $titleCol = self::$titleColumnCache[$this->company_id];
        if ($titleCol) {
            $val = $this->getColumnValue($titleCol);
            if ($val) return $val;
        }

        // Fallback to is_unique column
        if (!array_key_exists($this->company_id, self::$uniqueColumnCache)) {
            self::$uniqueColumnCache[$this->company_id] = \App\Models\CatalogueCustomColumn::where('company_id', $this->company_id)
                ->where('is_unique', true)->first();
        }

        $uc = self::$uniqueColumnCache[$this->company_id];
        if ($uc) {
            $val = $this->getColumnValue($uc);
            if ($val) return $val;
        }
        
        return $this->name;
    }

    /**
     * Get the name of the title column (for dynamic table headers).
     * Returns e.g. "Model" if Model is marked as is_title.
     * Falls back to is_unique column name, then "Product Name".
     */
    public function getTitleColumnNameAttribute(): string
    {
        if (!$this->company_id) return 'Product Name';

        if (!array_key_exists($this->company_id, self::$titleColumnCache)) {
            self::$titleColumnCache[$this->company_id] = \App\Models\CatalogueCustomColumn::where('company_id', $this->company_id)
                ->where('is_title', true)->first();
        }

        $titleCol = self::$titleColumnCache[$this->company_id];
        if ($titleCol) return $titleCol->name;

        if (!array_key_exists($this->company_id, self::$uniqueColumnCache)) {
            self::$uniqueColumnCache[$this->company_id] = \App\Models\CatalogueCustomColumn::where('company_id', $this->company_id)
                ->where('is_unique', true)->first();
        }

        $uc = self::$uniqueColumnCache[$this->company_id];
        if ($uc) return $uc->name;

        return 'Product Name';
    }

    /**
     * Build a dynamic description from all custom column values
     * excluding the is_title column, ordered by sort_order.
     * Optionally overlay session answers from chatbot.
     *
     * @param array|null $sessionAnswers  Key-value pairs from chatbot session (slug => answer)
     * @return string
     */
    public function getDynamicDescription(array $sessionAnswers = null): string
    {
        if (!$this->company_id) return $this->description ?? '';

        // Get all active columns for this company, sorted by sort_order
        $columns = \App\Models\CatalogueCustomColumn::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        // Identify the title column to exclude it
        $titleColId = null;
        foreach ($columns as $col) {
            if ($col->is_title) {
                $titleColId = $col->id;
                break;
            }
        }
        // If no title column, exclude unique column
        if (!$titleColId) {
            foreach ($columns as $col) {
                if ($col->is_unique) {
                    $titleColId = $col->id;
                    break;
                }
            }
        }

        $lines = [];
        foreach ($columns as $col) {
            if ($col->id === $titleColId || $col->is_category) continue; // skip title & category columns

            // Try session answers first (chatbot overlay)
            if ($sessionAnswers && isset($sessionAnswers[$col->slug])) {
                $lines[] = "{$col->name}: {$sessionAnswers[$col->slug]}";
                continue;
            }

            // Try actual saved values
            $val = $this->getColumnValue($col);
            if ($val) {
                $lines[] = "{$col->name}: {$val}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Helper to get a column's value for this product — handles both system and custom columns.
     */
    private function getColumnValue(\App\Models\CatalogueCustomColumn $col): ?string
    {
        if ($col->is_system) {
            $rawVal = $this->{$col->slug} ?? null;
            // Special case: is_category column should show category name, not ID
            if ($col->is_category && $this->category_id) {
                return $this->category?->name ?? $rawVal;
            }
            return $rawVal ?: null;
        }

        // Custom column value lookup
        if ($this->relationLoaded('customValues')) {
            $val = $this->customValues->where('column_id', $col->id)->first();
        } else {
            $val = \App\Models\CatalogueCustomValue::where('product_id', $this->id)->where('column_id', $col->id)->first();
        }

        if ($val) {
            $decoded = json_decode($val->value, true);
            return is_array($decoded) ? implode(', ', $decoded) : $val->value;
        }

        return null;
    }


    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'category_id',
        'sku',
        'name',
        'description',
        'unit',
        'mrp',
        'sale_price',
        'gst_percent',
        'hsn_code',
        'stock_qty',
        'min_stock_qty',
        'image',
        'cover_media_url',
        'group_media_url',
        'specifications',
        'status',
        'is_purchase_enabled',
    ];

    protected $casts = [
        'mrp' => 'integer',
        'sale_price' => 'integer',
        'gst_percent' => 'integer',
        'stock_qty' => 'integer',
        'min_stock_qty' => 'integer',
        'specifications' => 'array',
        'is_purchase_enabled' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customValues(): HasMany
    {
        return $this->hasMany(CatalogueCustomValue::class);
    }

    public function combos(): HasMany
    {
        return $this->hasMany(ProductCombo::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function activeVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->where('status', 'active');
    }

    // Price helpers (convert paise to rupees)
    public function getMrpInRupeesAttribute(): float
    {
        return $this->mrp / 100;
    }

    public function getSalePriceInRupeesAttribute(): float
    {
        return $this->sale_price / 100;
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_stock_qty;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

<?php

namespace DoubleThreeDigital\SimpleCommerce\Models;

use DoubleThreeDigital\SimpleCommerce\Models\Traits\HasAttributes;
use DoubleThreeDigital\SimpleCommerce\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasAttributes, HasUuid;

    protected $fillable = [
        'uuid', 'title', 'slug', 'product_category_id', 'is_enabled', 'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected $appends = [
        'url', 'variant_count',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function getUrlAttribute()
    {
        return route('products.show', ['product' => $this->attributes['slug']]);
    }

    public function getVariantCountAttribute()
    {
        return sprintf('%s %s', $count = $this->variants->count(), Str::plural('variant', $count));
    }

    public function createUrl()
    {
        return cp_route('products.create');
    }

    public function editUrl()
    {
        return cp_route('products.edit', ['product' => $this->uuid]);
    }

    public function updateUrl()
    {
        return cp_route('products.update', ['product' => $this->uuid]);
    }

    public function deleteUrl()
    {
        return cp_route('products.destroy', ['product' => $this->uuid]);
    }
}

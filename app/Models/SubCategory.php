<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCategory extends Model
{
        protected $fillable = [
        'category_id',
        'name',
        'image',
        'is_active',
    ];
    public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}
}

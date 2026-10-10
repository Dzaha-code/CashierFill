<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            // $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('sub_categories')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('in_stock')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};

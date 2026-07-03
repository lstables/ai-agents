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
        Schema::table('inventory_items', function (Blueprint $table) {
            // Optional preferred/primary supplier. Nullable and nullOnDelete
            // since this is a soft preference, not a hard dependency like
            // Purchase's required, restrictOnDelete supplier_id.
            $table->foreignId('supplier_id')->nullable()->after('unit')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\ContractTemplate;
use Database\Seeders\ContractTemplateSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_contract_templates')) {
            if (ContractTemplate::count() === 0) {
                $seeder = new ContractTemplateSeeder();
                $seeder->run();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete templates on down to prevent data loss
    }
};

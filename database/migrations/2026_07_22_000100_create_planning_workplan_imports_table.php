<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planning_workplan_imports')) {
            Schema::create('planning_workplan_imports', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->unsignedBigInteger('planning_year_id')->nullable()->index();
                $table->string('original_filename');
                $table->string('status', 40)->default('Pending')->index();
                $table->unsignedInteger('rows_read')->default(0);
                $table->unsignedInteger('workplans_created')->default(0);
                $table->unsignedInteger('targets_imported')->default(0);
                $table->json('errors')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_workplan_imports');
    }
};

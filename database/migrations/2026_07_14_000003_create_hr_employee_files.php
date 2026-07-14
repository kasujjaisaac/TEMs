<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hr_employees')) {
            Schema::create('hr_employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('employee_code', 50);
                $table->string('full_name', 180);
                $table->string('gender', 40)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('department', 120)->nullable();
                $table->string('job_title', 120)->nullable();
                $table->string('employment_type', 60)->default('Full time');
                $table->string('phone', 80)->nullable();
                $table->string('email', 180)->nullable();
                $table->string('national_id', 120)->nullable();
                $table->string('bank_wallet', 180)->nullable();
                $table->text('address')->nullable();
                $table->string('next_of_kin', 180)->nullable();
                $table->string('kin_phone', 80)->nullable();
                $table->decimal('basic_pay', 15, 2)->default(0);
                $table->string('status', 50)->default('Active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'employee_code'], 'uq_hr_employee_code');
            });
        }

        if (! Schema::hasTable('hr_employee_documents')) {
            Schema::create('hr_employee_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('employee_id');
                $table->string('document_type', 120);
                $table->string('title', 180);
                $table->string('document_ref', 160)->nullable();
                $table->string('issued_by', 180)->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable()->index();
                $table->string('file_path')->nullable();
                $table->string('original_name')->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('status', 50)->default('Filed');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'employee_id'], 'idx_hr_doc_employee');
            });
        }

        if (! Schema::hasTable('hr_employee_contracts')) {
            Schema::create('hr_employee_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('employee_id');
                $table->string('contract_type', 80)->default('Full time');
                $table->string('department', 120)->nullable();
                $table->string('job_title', 120)->nullable();
                $table->string('supervisor', 180)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable()->index();
                $table->date('probation_end')->nullable();
                $table->string('status', 50)->default('Active');
                $table->text('role_summary')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'employee_id'], 'idx_hr_contract_employee');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_contracts');
        Schema::dropIfExists('hr_employee_documents');
        Schema::dropIfExists('hr_employees');
    }
};

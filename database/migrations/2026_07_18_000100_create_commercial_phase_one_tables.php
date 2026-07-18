<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('type', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'type', 'year'], 'commercial_sequences_unique');
        });

        Schema::create('commercial_pipeline_stages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 120);
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->unsignedTinyInteger('default_probability')->default(0);
            $table->json('required_fields')->nullable();
            $table->json('required_documents')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->text('exit_criteria')->nullable();
            $table->unsignedSmallInteger('maximum_days')->nullable();
            $table->string('color', 32)->default('#ffffff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'commercial_stages_name_unique');
            $table->index(['tenant_id', 'display_order'], 'commercial_stages_order_idx');
        });

        Schema::create('commercial_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('legacy_customer_id')->nullable()->index();
            $table->string('reference', 40);
            $table->string('legal_name', 180);
            $table->string('trading_name', 180)->nullable();
            $table->string('organization_type', 80)->nullable();
            $table->string('customer_category', 80)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('sector', 120)->nullable();
            $table->string('tin', 80)->nullable();
            $table->string('registration_number', 120)->nullable();
            $table->string('primary_email', 180)->nullable();
            $table->string('primary_telephone', 80)->nullable();
            $table->string('alternative_telephone', 80)->nullable();
            $table->string('website', 180)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->text('physical_address')->nullable();
            $table->text('postal_address')->nullable();
            $table->unsignedInteger('number_of_branches')->nullable();
            $table->unsignedInteger('number_of_employees')->nullable();
            $table->string('customer_status', 80)->default('Prospect');
            $table->unsignedBigInteger('account_manager_id')->nullable()->index();
            $table->string('acquisition_source', 120)->nullable();
            $table->string('relationship_status', 80)->nullable();
            $table->unsignedTinyInteger('relationship_score')->nullable();
            $table->string('credit_status', 80)->nullable();
            $table->string('payment_terms', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference'], 'commercial_org_reference_unique');
            $table->index(['tenant_id', 'customer_status'], 'commercial_org_status_idx');
        });

        Schema::create('commercial_stakeholders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('organization_id')->constrained('commercial_organizations')->cascadeOnDelete();
            $table->string('full_name', 180);
            $table->string('position_title', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('telephone', 80)->nullable();
            $table->string('alternative_telephone', 80)->nullable();
            $table->string('decision_role', 80)->nullable();
            $table->string('influence_level', 60)->nullable();
            $table->string('interest_level', 60)->nullable();
            $table->string('relationship_status', 80)->nullable();
            $table->string('preferred_contact_method', 80)->nullable();
            $table->string('communication_preference', 120)->nullable();
            $table->string('decision_authority', 80)->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_billing_contact')->default(false);
            $table->boolean('is_technical_contact')->default(false);
            $table->boolean('is_contract_signatory')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_id'], 'commercial_stakeholders_org_idx');
        });

        Schema::create('commercial_leads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('stakeholder_id')->nullable()->index();
            $table->unsignedBigInteger('opportunity_id')->nullable()->index();
            $table->string('reference', 40);
            $table->string('organization_name', 180);
            $table->string('contact_person', 180)->nullable();
            $table->string('telephone', 80)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('location', 180)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('sector', 120)->nullable();
            $table->string('customer_type', 80)->nullable();
            $table->string('lead_source', 80)->nullable();
            $table->string('source_campaign', 120)->nullable();
            $table->string('interested_product', 180)->nullable();
            $table->string('interested_service', 180)->nullable();
            $table->decimal('estimated_budget', 15, 2)->nullable();
            $table->date('expected_decision_date')->nullable();
            $table->text('description')->nullable();
            $table->text('pain_points')->nullable();
            $table->text('requirements_summary')->nullable();
            $table->unsignedBigInteger('assigned_employee_id')->nullable()->index();
            $table->string('assigned_department', 120)->nullable();
            $table->string('temperature', 40)->default('Warm');
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->string('status', 60)->default('New');
            $table->string('qualification_status', 80)->nullable();
            $table->string('next_action', 180)->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->date('last_contacted_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference'], 'commercial_lead_reference_unique');
            $table->index(['tenant_id', 'status'], 'commercial_leads_status_idx');
        });

        Schema::create('commercial_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('organization_id')->constrained('commercial_organizations')->cascadeOnDelete();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('primary_stakeholder_id')->nullable()->index();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable()->index();
            $table->string('reference', 40);
            $table->string('title', 180);
            $table->unsignedBigInteger('assigned_employee_id')->nullable()->index();
            $table->string('product_or_service', 180)->nullable();
            $table->string('opportunity_type', 80)->default('New Business');
            $table->string('opportunity_source', 80)->nullable();
            $table->string('current_stage', 120)->default('Qualified');
            $table->unsignedTinyInteger('probability')->default(10);
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->string('currency', 8)->default('UGX');
            $table->date('expected_close_date')->nullable();
            $table->date('expected_start_date')->nullable();
            $table->unsignedInteger('contract_duration_months')->nullable();
            $table->string('revenue_type', 80)->nullable();
            $table->string('billing_frequency', 80)->nullable();
            $table->text('customer_need')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('proposed_solution')->nullable();
            $table->text('commercial_strategy')->nullable();
            $table->text('competitors')->nullable();
            $table->string('competitive_position', 120)->nullable();
            $table->text('decision_criteria')->nullable();
            $table->text('decision_process')->nullable();
            $table->text('identified_risks')->nullable();
            $table->string('risk_level', 60)->nullable();
            $table->string('next_action', 180)->nullable();
            $table->date('next_action_date')->nullable();
            $table->date('last_activity_date')->nullable();
            $table->string('lost_reason', 180)->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference'], 'commercial_opp_reference_unique');
            $table->index(['tenant_id', 'current_stage'], 'commercial_opp_stage_idx');
        });

        Schema::create('commercial_opportunity_stage_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('opportunity_id')->constrained('commercial_opportunities')->cascadeOnDelete();
            $table->string('previous_stage', 120)->nullable();
            $table->string('new_stage', 120);
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'opportunity_id'], 'commercial_stage_history_opp_idx');
        });

        Schema::create('commercial_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('activity_type', 80);
            $table->string('related_type', 120);
            $table->unsignedBigInteger('related_id');
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_employee_id')->nullable()->index();
            $table->date('activity_date')->nullable();
            $table->time('activity_time')->nullable();
            $table->text('description');
            $table->text('outcome')->nullable();
            $table->string('next_action', 180)->nullable();
            $table->date('next_action_date')->nullable();
            $table->string('completion_status', 60)->default('Open');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['related_type', 'related_id'], 'commercial_activities_related_idx');
        });

        Schema::create('commercial_meetings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('stakeholder_id')->nullable()->index();
            $table->unsignedBigInteger('opportunity_id')->nullable()->index();
            $table->string('title', 180);
            $table->string('meeting_type', 80)->default('Discovery');
            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 180)->nullable();
            $table->string('meeting_link', 255)->nullable();
            $table->text('internal_attendees')->nullable();
            $table->text('external_attendees')->nullable();
            $table->text('agenda')->nullable();
            $table->text('discussion_notes')->nullable();
            $table->text('customer_requirements')->nullable();
            $table->text('decisions_made')->nullable();
            $table->text('action_items')->nullable();
            $table->date('next_meeting_date')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('commercial_site_visits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('opportunity_id')->nullable()->index();
            $table->string('reference', 40);
            $table->string('site_location', 180);
            $table->date('visit_date');
            $table->string('visit_purpose', 180)->nullable();
            $table->text('internal_team')->nullable();
            $table->text('customer_representatives')->nullable();
            $table->text('current_environment')->nullable();
            $table->text('existing_systems')->nullable();
            $table->text('technical_infrastructure')->nullable();
            $table->string('internet_availability', 120)->nullable();
            $table->unsignedInteger('number_of_users')->nullable();
            $table->unsignedInteger('number_of_branches')->nullable();
            $table->text('business_processes_observed')->nullable();
            $table->text('customer_challenges')->nullable();
            $table->text('functional_requirements')->nullable();
            $table->text('technical_requirements')->nullable();
            $table->text('implementation_considerations')->nullable();
            $table->text('risks')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('follow_up_actions')->nullable();
            $table->string('report_status', 80)->default('Draft');
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference'], 'commercial_site_visit_reference_unique');
        });

        Schema::create('commercial_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('documentable_type', 120);
            $table->unsignedBigInteger('documentable_id');
            $table->string('title', 180);
            $table->string('document_type', 120)->nullable();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id'], 'commercial_documents_related_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_documents');
        Schema::dropIfExists('commercial_site_visits');
        Schema::dropIfExists('commercial_meetings');
        Schema::dropIfExists('commercial_activities');
        Schema::dropIfExists('commercial_opportunity_stage_history');
        Schema::dropIfExists('commercial_opportunities');
        Schema::dropIfExists('commercial_leads');
        Schema::dropIfExists('commercial_stakeholders');
        Schema::dropIfExists('commercial_organizations');
        Schema::dropIfExists('commercial_pipeline_stages');
        Schema::dropIfExists('commercial_number_sequences');
    }
};

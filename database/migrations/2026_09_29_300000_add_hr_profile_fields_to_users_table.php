<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Personal
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('national_id')->nullable()->after('gender');
            $table->string('nationality')->nullable()->after('national_id');
            $table->string('marital_status')->nullable()->after('nationality');

            // Contact
            $table->string('personal_email')->nullable()->after('marital_status');
            $table->string('address_line1')->nullable()->after('personal_email');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');

            // Emergency contact
            $table->string('emergency_contact_name')->nullable()->after('country');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_relationship');

            // Employment terms
            $table->string('employment_type')->nullable()->after('emergency_contact_phone');
            $table->date('contract_start_date')->nullable()->after('employment_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->date('probation_end_date')->nullable()->after('contract_end_date');
            $table->string('work_location')->nullable()->after('probation_end_date');
            $table->string('job_grade')->nullable()->after('work_location');
            $table->string('pay_frequency')->nullable()->after('job_grade');

            // Bank & tax
            $table->string('bank_name')->nullable()->after('pay_frequency');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_branch')->nullable()->after('bank_account_number');
            $table->string('tax_number')->nullable()->after('bank_branch');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth', 'gender', 'national_id', 'nationality', 'marital_status',
                'personal_email', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
                'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
                'employment_type', 'contract_start_date', 'contract_end_date', 'probation_end_date',
                'work_location', 'job_grade', 'pay_frequency',
                'bank_name', 'bank_account_number', 'bank_branch', 'tax_number',
            ]);
        });
    }
};

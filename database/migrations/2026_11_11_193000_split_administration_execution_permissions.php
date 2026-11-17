<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->foreignId('completion_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completion_verified_at')->nullable();
            $table->text('completion_verification_remarks')->nullable();
        });
        Schema::create('administration_workflow_grant_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 20);
            $table->string('target_key', 100);
            $table->json('added_permissions');
            $table->timestamp('created_at');
        });

        foreach ([
            'assign' => ['Assign Administrative Work', 'Assign approved administrative requests to the designated executor.'],
            'complete' => ['Complete Assigned Administrative Work', 'Record completion of administrative work assigned to the current user.'],
            'verify-completion' => ['Verify Administrative Completion', 'Verify completed work before closure. Ordinary users cannot verify their own execution.'],
            'close' => ['Close Administrative Request', 'Close a request only after recorded completion verification.'],
        ] as $code => [$label, $description]) {
            Permission::updateOrCreate(['name' => 'administration.'.$code], ['label' => $label, 'module' => 'administration', 'description' => $description]);
        }
        Permission::where('name', 'administration.execute')->update([
            'label' => 'Legacy Administrative Execute (inactive)',
            'description' => 'Retained for grant history. New workflow uses Assign, Complete Assigned Work, Verify Completion and Close permissions.',
        ]);

        app(\App\Services\Administration\AdministrationWorkflowGrantMapper::class)->apply();
    }

    public function down(): void
    {
        // Grant records remain for deliberate review, not automatic revocation.
        DB::table('administrative_requests')->where('status', 'execution_verified')->update(['status' => 'executed']);
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completion_verified_by');
            $table->dropColumn(['completion_verified_at', 'completion_verification_remarks']);
        });
        Schema::dropIfExists('administration_workflow_grant_mappings');
    }
};

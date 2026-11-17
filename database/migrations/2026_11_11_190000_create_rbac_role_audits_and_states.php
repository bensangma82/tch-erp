<?php

use App\Support\Rbac\RoleCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbac_role_states', function (Blueprint $table) {
            $table->string('role', 100)->primary();
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
        });
        Schema::create('rbac_role_audits', function (Blueprint $table) {
            $table->id();
            $table->string('role', 100)->index();
            // Deliberately retain the actor ID if an account is later deleted.
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->text('reason');
            $table->json('before_permission_ids');
            $table->json('after_permission_ids');
            $table->json('added_permissions');
            $table->json('removed_permissions');
            $table->timestamp('created_at');
        });
        // On an established ERP, preserve every listed role, including empty roles.
        // On a fresh database, leave states empty so the baseline can initialize.
        if (DB::table('role_permission')->exists()) {
            $roles = array_unique([...array_keys(RoleCatalogue::configurableRoles()), ...DB::table('role_permission')->distinct()->pluck('role')->all()]);
            foreach ($roles as $role) {
                DB::table('rbac_role_states')->insert(['role' => $role, 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_role_audits');
        Schema::dropIfExists('rbac_role_states');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mhis_variance_review_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('mhis_package_variance_approvals')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 30);
            $table->jsonb('snapshot');
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
        });
    }
    public function down(): void
    {
        if (DB::table('mhis_variance_review_events')->exists()) {
            throw new RuntimeException('Review audit history exists; retain this table.');
        }
        Schema::dropIfExists('mhis_variance_review_events');
    }
};

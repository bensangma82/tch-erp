<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('investigation_ms_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->boolean('active')->default(false);
            $table->foreignId('designated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('designated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_ms_approvers');
    }
};

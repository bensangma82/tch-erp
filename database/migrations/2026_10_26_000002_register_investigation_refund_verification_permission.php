<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        if (!DB::table('permissions')->where('name','billing.investigation-refund.verify')->exists()) {
            DB::table('permissions')->insert(['name'=>'billing.investigation-refund.verify','label'=>'Verify investigation refund settlement',
            'module'=>'billing','description'=>'Independently verify settled refund evidence and post refund accounting','created_at'=>now(),'updated_at'=>now()]);
        }
        // No automatic permission grants.
    }
    public function down(): void { /* Retain permission and audit references */ }
};

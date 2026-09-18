<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PharmacyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            [
                'name' => 'pharmacy.po.create',
                'label' => 'Create Purchase Orders',
                'module' => 'pharmacy',
                'description' => 'Create pharmacy purchase orders.',
            ],

            [
                'name' => 'pharmacy.po.approve',
                'label' => 'Approve Purchase Orders',
                'module' => 'pharmacy',
                'description' => 'Approve pharmacy purchase orders.',
            ],

            [
                'name' => 'pharmacy.grn.create',
                'label' => 'Create GRN',
                'module' => 'pharmacy',
                'description' => 'Receive stock through Goods Receipt Notes.',
            ],

            [
                'name' => 'pharmacy.purchase-return.create',
                'label' => 'Create Purchase Returns',
                'module' => 'pharmacy',
                'description' => 'Return stock to suppliers.',
            ],

            [
                'name' => 'pharmacy.supplier-payment.record',
                'label' => 'Record Supplier Payments',
                'module' => 'pharmacy',
                'description' => 'Record payments against supplier payables.',
            ],

            [
                'name' => 'pharmacy.stock-adjustment.create',
                'label' => 'Create Stock Adjustments',
                'module' => 'pharmacy',
                'description' => 'Create manual stock corrections.',
            ],

            [
                'name' => 'pharmacy.disposal.create',
                'label' => 'Create Disposal',
                'module' => 'pharmacy',
                'description' => 'Dispose or write off pharmacy stock.',
            ],

            [
                'name' => 'pharmacy.stock-audit.create',
                'label' => 'Create Stock Audits',
                'module' => 'pharmacy',
                'description' => 'Start and count physical stock audits.',
            ],

            [
                'name' => 'pharmacy.stock-audit.approve',
                'label' => 'Approve Stock Audits',
                'module' => 'pharmacy',
                'description' => 'Approve reviewed pharmacy stock audits.',
            ],

            [
                'name' => 'pharmacy.stock-audit.post',
                'label' => 'Post Stock Audits',
                'module' => 'pharmacy',
                'description' => 'Post approved stock audit variances into live stock.',
            ],

        ];


        foreach ($permissions as $data) {

            Permission::updateOrCreate(
                [
                    'name' => $data['name'],
                ],
                $data
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Temporary compatibility assignment
        |--------------------------------------------------------------------------
        |
        | Give ALL current pharmacy users ALL pharmacy permissions so nothing
        | breaks while we introduce permission enforcement gradually.
        |
        */

        $permissionIds =
            Permission::query()
                ->where(
                    'module',
                    'pharmacy'
                )
                ->pluck('id')
                ->all();


        User::query()
            ->where(
                'role',
                'pharmacy'
            )
            ->each(
                function (User $user) use ($permissionIds) {

                    $user->permissions()
                        ->syncWithoutDetaching(
                            $permissionIds
                        );
                }
            );
    }
}
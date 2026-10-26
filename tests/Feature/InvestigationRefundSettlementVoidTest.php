<?php

namespace Tests\Feature;

use App\Models\InvestigationRefundSettlementCheck;
use App\Models\User;
use App\Services\Investigation\InvestigationRefundVerificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvestigationRefundSettlementVoidTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(
            'pgsql',
            DB::getDefaultConnection()
        );

        $this->assertSame(
            'testing',
            DB::selectOne(
                'select current_database() as name'
            )->name
        );
    }

    public function test_unauthorized_user_cannot_void_settlement(): void
    {
        $user = User::factory()->create([
            'role' => 'billing',
        ]);

        $settlement = new InvestigationRefundSettlementCheck();

        $this->expectException(
            \Symfony\Component\HttpKernel\Exception\HttpException::class
        );

        app(InvestigationRefundVerificationService::class)
            ->voidSettlement(
                $settlement,
                $user,
                'TEST ONLY - No actual payment was made.',
                'TEST-EVIDENCE-001'
            );
    }
}

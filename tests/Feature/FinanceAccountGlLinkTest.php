<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\GeneralLedgerAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinanceAccountGlLinkTest extends TestCase
{
    use DatabaseTransactions;

    private function financeAccount(string $code): FinanceAccount
    {
        return FinanceAccount::create([
            'code' => $code,
            'name' => $code,
            'account_type' => 'bank',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function glAccount(string $code): GeneralLedgerAccount
    {
        return GeneralLedgerAccount::create([
            'account_code' => $code,
            'account_name' => 'Test Bank '.$code,
            'account_type' => 'asset',
            'account_group' => 'Cash and Cash Equivalents',
            'normal_balance' => 'debit',
            'is_active' => true,
            'allow_posting' => true,
        ]);
    }

    private function updateAccount(
        FinanceAccount $account,
        ?int $glId
    ) {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        return $this->put(
            route('finance.master.accounts.update', $account),
            [
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => 'bank',
                'opening_balance' => 0,
                'is_active' => 1,
                'general_ledger_account_id' => $glId,
            ]
        );
    }

    public function test_bank_account_links_to_gl_in_both_directions(): void
    {
        $account = $this->financeAccount('TEST-BANK-LINK');
        $gl = $this->glAccount('TEST-GL-8140');

        $this->updateAccount($account, $gl->id)->assertSessionHasNoErrors();

        $this->assertEquals($gl->id, $account->fresh()->general_ledger_account_id);
        $this->assertEquals($account->id, $gl->fresh()->finance_account_id);
    }

    public function test_existing_link_is_preserved_when_selection_is_empty(): void
    {
        $account = $this->financeAccount('TEST-BANK-KEEP');
        $gl = $this->glAccount('TEST-GL-8141');

        $account->update(['general_ledger_account_id' => $gl->id]);
        $gl->update(['finance_account_id' => $account->id]);

        $this->updateAccount($account, null)->assertSessionHasNoErrors();

        $this->assertEquals($gl->id, $account->fresh()->general_ledger_account_id);
        $this->assertEquals($account->id, $gl->fresh()->finance_account_id);
    }

    public function test_same_gl_cannot_be_assigned_to_another_finance_account(): void
    {
        $first = $this->financeAccount('TEST-BANK-ONE');
        $second = $this->financeAccount('TEST-BANK-TWO');
        $gl = $this->glAccount('TEST-GL-8142');

        $this->updateAccount($first, $gl->id)->assertSessionHasNoErrors();

        $this->updateAccount($second, $gl->id)
            ->assertSessionHasErrors('general_ledger_account_id');

        $this->assertNull($second->fresh()->general_ledger_account_id);
        $this->assertEquals($first->id, $gl->fresh()->finance_account_id);
    }

    public function test_inactive_gl_account_is_rejected(): void
    {
        $account = $this->financeAccount('TEST-BANK-INACTIVE');
        $gl = $this->glAccount('TEST-GL-8143');
        $gl->update(['is_active' => false]);

        $this->updateAccount($account, $gl->id)
            ->assertSessionHasErrors('general_ledger_account_id');

        $this->assertNull($account->fresh()->general_ledger_account_id);
    }

    public function test_non_postable_gl_account_is_rejected(): void
    {
        $account = $this->financeAccount('TEST-BANK-NOPOST');
        $gl = $this->glAccount('TEST-GL-8144');
        $gl->update(['allow_posting' => false]);

        $this->updateAccount($account, $gl->id)
            ->assertSessionHasErrors('general_ledger_account_id');

        $this->assertNull($account->fresh()->general_ledger_account_id);
    }


    public function test_changing_gl_link_clears_previous_reverse_link(): void
    {
        $account = $this->financeAccount('TEST-BANK-SWITCH');
        $old = $this->glAccount('TEST-GL-8145');
        $new = $this->glAccount('TEST-GL-8146');

        $this->updateAccount($account, $old->id)
            ->assertSessionHasNoErrors();

        $this->updateAccount($account, $new->id)
            ->assertSessionHasNoErrors();

        $this->assertEquals(
            $new->id,
            $account->fresh()->general_ledger_account_id
        );
        $this->assertNull($old->fresh()->finance_account_id);
        $this->assertEquals(
            $account->id,
            $new->fresh()->finance_account_id
        );
    }

}

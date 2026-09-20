<?php

namespace App\Console\Commands;

use App\Models\FinanceHead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClassifyFinanceExpenseHeads extends Command
{
    protected $signature = 'finance:classify-expense-heads';

    protected $description = 'Apply initial break-even cost classifications to TCH finance expense heads';

    public function handle(): int
    {
        $classifications = [
            'EXP-SALARY' => [
                'cost_behavior' => 'fixed',
                'variable_percentage' => 0,
                'include_in_break_even' => true,
            ],

            'EXP-STAFF' => [
                'cost_behavior' => 'fixed',
                'variable_percentage' => 0,
                'include_in_break_even' => true,
            ],

            'EXP-MEDICINE' => [
                'cost_behavior' => 'variable',
                'variable_percentage' => 100,
                'include_in_break_even' => true,
            ],

            'EXP-CONSUMABLE' => [
                'cost_behavior' => 'variable',
                'variable_percentage' => 100,
                'include_in_break_even' => true,
            ],

            'EXP-LAB' => [
                'cost_behavior' => 'variable',
                'variable_percentage' => 100,
                'include_in_break_even' => true,
            ],

            'EXP-ELECTRICITY' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 40,
                'include_in_break_even' => true,
            ],

            'EXP-WATER' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 30,
                'include_in_break_even' => true,
            ],

            'EXP-FUEL' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 50,
                'include_in_break_even' => true,
            ],

            'EXP-INTERNET' => [
                'cost_behavior' => 'fixed',
                'variable_percentage' => 0,
                'include_in_break_even' => true,
            ],

            'EXP-MAINTENANCE' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 30,
                'include_in_break_even' => true,
            ],

            'EXP-FOOD' => [
                'cost_behavior' => 'variable',
                'variable_percentage' => 100,
                'include_in_break_even' => true,
            ],

            'EXP-OFFICE' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 50,
                'include_in_break_even' => true,
            ],

            'EXP-PROFESSIONAL' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 50,
                'include_in_break_even' => true,
            ],

            'EXP-TRANSPORT' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 60,
                'include_in_break_even' => true,
            ],

            'EXP-OTHER' => [
                'cost_behavior' => 'mixed',
                'variable_percentage' => 50,
                'include_in_break_even' => true,
            ],

            'EXP-EQUIPMENT' => [
                'cost_behavior' => null,
                'variable_percentage' => null,
                'include_in_break_even' => false,
            ],

            'EXP-FURNITURE' => [
                'cost_behavior' => null,
                'variable_percentage' => null,
                'include_in_break_even' => false,
            ],
        ];

        $updated = 0;
        $missing = [];

        DB::transaction(function () use (
            $classifications,
            &$updated,
            &$missing
        ) {
            foreach ($classifications as $code => $values) {
                $head = FinanceHead::query()
                    ->where('code', $code)
                    ->where('head_type', 'expense')
                    ->first();

                if (!$head) {
                    $missing[] = $code;
                    continue;
                }

                $head->update($values);

                $updated++;
            }
        });

        $this->newLine();

        $this->info(
            "Expense head classification completed. {$updated} head(s) updated."
        );

        if (!empty($missing)) {
            $this->newLine();

            $this->warn(
                'The following expense head codes were not found:'
            );

            foreach ($missing as $code) {
                $this->line(" - {$code}");
            }
        }

        $this->newLine();

        $this->table(
            [
                'Code',
                'Cost Behaviour',
                'Variable %',
                'Break-even',
            ],
            FinanceHead::query()
                ->where('head_type', 'expense')
                ->orderBy('code')
                ->get()
                ->map(function (FinanceHead $head) {
                    return [
                        $head->code,
                        $head->cost_behavior
                            ? ucfirst($head->cost_behavior)
                            : 'Not classified',
                        $head->variable_percentage !== null
                            ? number_format(
                                (float) $head->variable_percentage,
                                2
                            ) . '%'
                            : '—',
                        $head->include_in_break_even
                            ? 'Included'
                            : 'Excluded',
                    ];
                })
                ->all()
        );

        return self::SUCCESS;
    }
}
<?php

namespace App\Services\Sale;

class SalePaymentStateService
{
    public function moneyToCents($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    public function centsToMoney(int $value): float
    {
        return round($value / 100, 2);
    }

    public function resolveState(int $debtCents, int $paidOutCents): array
    {
        $state_mayment = 1;
        $date_completed = null;

        if ($debtCents <= 0) {
            $state_mayment = 3;
            $date_completed = now();
        } elseif ($paidOutCents > 0) {
            $state_mayment = 2;
        }

        return [$state_mayment, $date_completed];
    }

    public function buildPaymentPayload(int $totalCents, int $paidOutCents): array
    {
        $newDebtCents = max(0, $totalCents - $paidOutCents);
        [$state_mayment, $date_completed] = $this->resolveState($newDebtCents, $paidOutCents);

        return [
            'paid_out' => $this->centsToMoney($paidOutCents),
            'debt' => $this->centsToMoney($newDebtCents),
            'state_mayment' => $state_mayment,
            'date_completed' => $date_completed,
            'debt_cents' => $newDebtCents,
        ];
    }
}

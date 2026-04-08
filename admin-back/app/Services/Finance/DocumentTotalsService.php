<?php

namespace App\Services\Finance;

class DocumentTotalsService
{
    private const DEFAULT_IVA_RATE = 0.18;

    public function calculateFromImport(float $import, float $ivaRate = self::DEFAULT_IVA_RATE): array
    {
        $normalizedImport = round($import, 2);
        $iva = round($normalizedImport * $ivaRate, 2);

        return [
            'impote' => $normalizedImport,
            'immporte' => $normalizedImport,
            'iva' => $iva,
            'total' => round($normalizedImport + $iva, 2),
        ];
    }

    public function calculateFromDelta(float $currentImport, float $oldLineTotal, float $newLineTotal, float $ivaRate = self::DEFAULT_IVA_RATE): array
    {
        $newImport = ($currentImport - $oldLineTotal) + $newLineTotal;

        return $this->calculateFromImport($newImport, $ivaRate);
    }
}

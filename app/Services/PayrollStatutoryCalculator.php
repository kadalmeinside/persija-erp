<?php

namespace App\Services;

class PayrollStatutoryCalculator
{
    public const BPJS_KES_MAX_BASE = 12_000_000.0;
    public const BPJS_JP_MAX_BASE  = 10_042_300.0;

    /**
     * Calculate statutory employee deductions (BPJS Kesehatan, BPJS TK JHT+JP, and PPh 21 TER).
     *
     * @return array{bpjs_kesehatan: float, bpjs_ketenagakerjaan: float, pph21: float, total: float}
     */
    public function calculate(float $grossIncome, string $statusPtkp = 'TK/0'): array
    {
        if ($grossIncome <= 0) {
            return [
                'bpjs_kesehatan' => 0.0,
                'bpjs_ketenagakerjaan' => 0.0,
                'pph21' => 0.0,
                'total' => 0.0,
            ];
        }

        $kesBase = min($grossIncome, self::BPJS_KES_MAX_BASE);
        $bpjsKes = round($kesBase * 0.01, 2);

        $jht = round($grossIncome * 0.02, 2);
        $jp  = round(min($grossIncome, self::BPJS_JP_MAX_BASE) * 0.01, 2);
        $bpjsTk = $jht + $jp;

        $terRate = $this->resolveTerRate($grossIncome, strtoupper(trim($statusPtkp ?: 'TK/0')));
        $pph21 = round($grossIncome * $terRate, 2);

        return [
            'bpjs_kesehatan' => $bpjsKes,
            'bpjs_ketenagakerjaan' => $bpjsTk,
            'pph21' => $pph21,
            'total' => round($bpjsKes + $bpjsTk + $pph21, 2),
        ];
    }

    /**
     * PP 58/2023 TER category & effective monthly rate.
     */
    public function resolveTerRate(float $gross, string $ptkp): float
    {
        $category = match ($ptkp) {
            'TK/2', 'TK/3', 'K/1', 'K/2' => 'B',
            'K/3' => 'C',
            default => 'A', // TK/0, TK/1, K/0
        };

        $brackets = match ($category) {
            'A' => [
                [5_400_000, 0.0],
                [5_650_000, 0.0025],
                [5_950_000, 0.005],
                [6_300_000, 0.0075],
                [6_750_000, 0.01],
                [7_500_000, 0.0125],
                [8_550_000, 0.015],
                [9_650_000, 0.0175],
                [10_050_000, 0.02],
                [10_350_000, 0.0225],
                [10_700_000, 0.025],
                [11_050_000, 0.03],
                [11_600_000, 0.035],
                [12_500_000, 0.04],
                [13_750_000, 0.05],
                [15_100_000, 0.06],
                [16_950_000, 0.07],
                [19_750_000, 0.08],
                [24_150_000, 0.09],
                [26_450_000, 0.10],
                [28_000_000, 0.11],
                [30_050_000, 0.12],
                [32_400_000, 0.13],
                [35_400_000, 0.14],
                [39_100_000, 0.15],
                [43_850_000, 0.16],
                [47_800_000, 0.17],
                [51_400_000, 0.18],
                [56_300_000, 0.19],
                [62_200_000, 0.20],
                [68_600_000, 0.21],
                [77_500_000, 0.22],
                [89_000_000, 0.23],
                [103_000_000, 0.24],
                [125_000_000, 0.25],
                [157_000_000, 0.26],
                [206_000_000, 0.27],
                [337_000_000, 0.28],
                [454_000_000, 0.29],
                [550_000_000, 0.30],
                [695_000_000, 0.31],
                [910_000_000, 0.32],
                [1_400_000_000, 0.33],
            ],
            'B' => [
                [6_200_000, 0.0],
                [6_500_000, 0.0025],
                [6_850_000, 0.005],
                [7_300_000, 0.0075],
                [9_200_000, 0.015],
                [10_750_000, 0.02],
                [11_600_000, 0.03],
                [13_200_000, 0.04],
                [15_600_000, 0.05],
                [19_200_000, 0.07],
                [25_000_000, 0.09],
                [35_000_000, 0.13],
                [50_000_000, 0.17],
                [75_000_000, 0.21],
                [125_000_000, 0.25],
                [250_000_000, 0.28],
                [500_000_000, 0.30],
            ],
            'C' => [
                [6_600_000, 0.0],
                [6_950_000, 0.0025],
                [7_350_000, 0.005],
                [7_800_000, 0.0075],
                [8_850_000, 0.01],
                [9_800_000, 0.015],
                [10_950_000, 0.0175],
                [11_200_000, 0.02],
                [12_050_000, 0.03],
                [12_950_000, 0.04],
                [14_150_000, 0.05],
                [16_800_000, 0.06],
                [19_500_000, 0.08],
                [26_600_000, 0.10],
                [35_400_000, 0.14],
                [51_400_000, 0.18],
                [78_400_000, 0.22],
                [129_000_000, 0.25],
                [250_000_000, 0.28],
                [500_000_000, 0.30],
            ],
        };

        foreach ($brackets as [$limit, $rate]) {
            if ($gross <= $limit) {
                return $rate;
            }
        }

        return 0.34;
    }
}

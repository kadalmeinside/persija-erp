<?php

namespace App\Services;

class CalculationService
{
    /**
     * Calculate Tax and Totals
     * 
     * @param float $amount Nominal Item (DPP / Base Amount)
     * @param float $rate Tax Rate (e.g. 11 for 11%)
     * @param string $taxType 'PPN' or 'PPh'
     * @return array
     */
    public function calculateTax(float $amount, float $rate, string $taxType): array
    {
        $taxValue = 0;
        
        if ($rate > 0) {
            // Standard Calculation: Tax = Amount * Rate / 100
            $taxValue = round(($amount * $rate) / 100, 2);
        }

        // Logic PPN vs PPh for Total Payable
        // PPN adds to the amount needed to be paid to vendor.
        // PPh deducts from the amount paid to vendor (withheld), BUT the expense usually stays the same (gross) 
        // OR expense includes tax? 
        // In ERP: 
        // Expense = Nominal Item.
        // Tax Payable (Kewajiban) = PPh.
        // Cash Out = Nominal - PPh.
        
        // For PPN:
        // Expense = Nominal Item.
        // Tax Input (Piutang/Expense) = PPN.
        // Cash Out = Nominal + PPN.

        // This function returns the TAX AMOUNT strictly.
        // The interpretation of 'Total' depends on context (Expense Total or Payment Total).
        // Let's return components.

        return [
            'base_amount' => $amount,
            'tax_rate' => $rate,
            'tax_amount' => $taxValue,
            // 'gross_up_amount' => ... (If we implement gross up logic later)
        ];
    }

    /**
     * Calculate Gross Up Amount
     * Formula: Gross = Net / (1 - Rate/100)
     */
    public function calculateGrossUp(float $netAmount, float $rate): float
    {
        if ($rate >= 100 || $rate < 0) return $netAmount; // Safety check
        
        $grossAmount = $netAmount / (1 - ($rate / 100));
        
        // Round usually to ceil to ensure net is fully covered? Or simple round?
        // Usually ceil for payroll/withholding to be safe.
        return ceil($grossAmount);
    }
}

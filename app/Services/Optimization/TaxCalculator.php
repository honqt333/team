<?php

namespace App\Services\Optimization;

class TaxCalculator
{
    /**
     * Calculate tax for a single line item.
     * Rounds tax_amount to 2 decimals per standard practice (Line Level).
     *
     * @param float $unitPrice  Price per unit
     * @param float $qty        Quantity
     * @param float $discount   Total discount amount for the line
     * @param float $taxRate    Tax rate percentage (e.g., 15 for 15%)
     * @param bool $isInclusive Whether the unitPrice includes tax
     * @return array Result with detailed breakdown
     */
    /**
     * Calculate tax for a single line item.
     * Keeps high precision for aggregation later.
     */
    public function calculateLine(
        float $unitPrice,
        float $qty,
        float $discount,
        float $taxRate,
        bool $isInclusive
    ): array {
        // 1. Calculate Total Price (Before Tax Logic)
        $grossTotal = $unitPrice * $qty;
        $netTotal = max(0, $grossTotal - $discount);

        // 2. Tax Logic
        $rateDecimal = $taxRate / 100;

        if ($isInclusive) {
            $totalInclTax = $netTotal;
            $baseAmount = $totalInclTax / (1 + $rateDecimal);
            $taxAmount = $totalInclTax - $baseAmount;
            $totalExclTax = $baseAmount; 
        } else {
            $totalExclTax = $netTotal;
            $taxAmount = $totalExclTax * $rateDecimal;
            $totalInclTax = $totalExclTax + $taxAmount;
            $baseAmount = $totalExclTax;
        }

        // Return HIGH PRECISION values for document-level aggregation
        // We do NOT round here anymore for the final calculation, purely for display preview if needed.
        
        return [
            'unit_price' => $unitPrice,
            'qty' => $qty,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            
            // Raw high precision
            'raw_tax_amount' => $taxAmount,
            'raw_line_total_excl' => $totalExclTax,
            'raw_line_total_incl' => $totalInclTax,
            
            // Initial display values (rounded), will be reconciled later
            'tax_amount' => round($taxAmount, 2),
            'line_total_excl_tax' => round($totalExclTax, 2),
            'line_total_incl_tax' => round($totalInclTax, 2),
            'tax_category_code' => 'S', // Default
        ];
    }

    /**
     * Calculate document totals with Reconciliation.
     * 1. Sum raw taxable amounts per category.
     * 2. Calculate Total Tax on the SUM (Document Level).
     * 3. Distribute rounding differences back to lines.
     */
    public function calculateDocument(array $lines): array
    {
        $breakdown = [];
        $totalExclTax = 0;
        $totalInclTax = 0;
        $totalTaxable = 0;

        // Step 1: Group by Category & Rate
        $grouped = [];
        foreach ($lines as $index => $line) {
            $key = $line['tax_category_code'] . '_' . $line['tax_rate'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $line['tax_category_code'],
                    'rate' => $line['tax_rate'],
                    'lines_indices' => [],
                    'sum_excl' => 0,
                ];
            }
            $grouped[$key]['lines_indices'][] = $index;
            // Use rounded line totals for base to ensure consistency? 
            // ZATCA: Tax is calculated on the sum of taxable amounts.
            // Usually we sum the rounded exclusive totals of lines.
            $grouped[$key]['sum_excl'] += $line['line_total_excl_tax'];
        }

        // Step 2: Calculate Tax per Group (Document Level)
        $reconciledLines = $lines;
        $totalDocumentTax = 0;

        foreach ($grouped as $key => $group) {
            $rateDecimal = $group['rate'] / 100;
            
            // Calculate detailed tax on the group sum
            $groupTaxFull = $group['sum_excl'] * $rateDecimal;
            $groupTaxRounded = round($groupTaxFull, 2);
            
            // Calculate sum of line taxes currently
            $sumLineTaxes = 0;
            foreach ($group['lines_indices'] as $idx) {
                $sumLineTaxes += $reconciledLines[$idx]['tax_amount'];
            }
            
            // Find difference (The "Penny")
            $diff = $groupTaxRounded - $sumLineTaxes;
            
            // Distribute difference to first line(s) of this group
            // Usually diff is 0.01, -0.01, etc.
            if (abs($diff) > 0.0001) {
                // Apply to first line
                $firstIdx = $group['lines_indices'][0];
                $reconciledLines[$firstIdx]['tax_amount'] += $diff;
                $reconciledLines[$firstIdx]['line_total_incl_tax'] += $diff;
            }

            // Build Breakdown
            $cat = $group['category'];
            $breakdown[$cat] = [
                'category' => $cat,
                'rate' => $group['rate'],
                'taxable_amount' => $group['sum_excl'],
                'tax_amount' => $groupTaxRounded,
                'total_amount' => $group['sum_excl'] + $groupTaxRounded,
            ];

            $totalExclTax += $group['sum_excl'];
            $totalDocumentTax += $groupTaxRounded;
            
            if (in_array($cat, ['S', 'Z'])) {
                $totalTaxable += $group['sum_excl'];
            }
        }
        
        $totalInclTax = $totalExclTax + $totalDocumentTax;

        return [
            'total_excl_tax' => round($totalExclTax, 2),
            'total_tax' => round($totalDocumentTax, 2),
            'total_incl_tax' => round($totalInclTax, 2),
            'total_taxable_amount' => round($totalTaxable, 2),
            'tax_breakdown' => $breakdown,
            'reconciled_lines' => $reconciledLines, // Return lines with adjusted tax/totals
        ];
    }
}

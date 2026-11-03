/**
 * Composable for print totals computation.
 * Used by all print templates (A4, Thermal) to calculate document totals.
 */
export function usePrintTotals() {
    /**
     * Calculate line total incl. tax for services (qty=1)
     */
    function srvLineTotal(item, data = {}) {
        if (item.line_total_incl_tax !== undefined && item.line_total_incl_tax !== null) {
            return Number(item.line_total_incl_tax);
        }
        const qty = 1;
        const price = item.unit_price || 0;
        const discount = item.discount || 0;

        const pricingModeSnapshot = data.pricing_mode_snapshot;
        const taxEnabled = isTaxEnabled(data);

        if (pricingModeSnapshot === 'inclusive') {
            return Math.max(price * qty - discount, 0);
        }
        const excl = Math.max(price * qty - discount, 0);
        const rate =
            taxEnabled && item.is_taxable !== false ? (item.tax_rate_snapshot || 0) / 100 : 0;
        const vat = excl * rate;
        return excl + vat;
    }

    /**
     * Calculate line VAT for services
     */
    function srvLineVat(item, data = {}) {
        if (!isTaxEnabled(data) || item.is_taxable === false) return 0;
        if (item.tax_amount !== undefined && item.tax_amount !== null) {
            return Number(item.tax_amount);
        }
        const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;
        const showExclusive = isShowExclusive(data);

        if (data.pricing_mode_snapshot === 'inclusive') {
            const total = srvLineTotal(item, data);
            return total - total / (1 + rate);
        }
        return srvLineExclTax(item, data) * rate;
    }

    /**
     * Calculate line total excl. tax for services
     */
    function srvLineExclTax(item, data = {}) {
        if (item.line_total_excl_tax !== undefined && item.line_total_excl_tax !== null) {
            return Number(item.line_total_excl_tax);
        }
        if (data.pricing_mode_snapshot === 'inclusive') {
            return srvLineTotal(item, data) - srvLineVat(item, data);
        }
        const price = item.unit_price || 0;
        const discount = item.discount || 0;
        return Math.max(price * 1 - discount, 0);
    }

    /**
     * Calculate line total incl. tax for parts (qty can vary)
     */
    function partLineTotal(item, data = {}) {
        if (item.line_total_incl_tax !== undefined && item.line_total_incl_tax !== null) {
            return Number(item.line_total_incl_tax);
        }
        const qty = item.qty || 1;
        const price = item.unit_price || 0;
        const discount = item.discount || 0;

        const pricingModeSnapshot = data.pricing_mode_snapshot;
        const taxEnabled = isTaxEnabled(data);

        if (pricingModeSnapshot === 'inclusive') {
            return Math.max(price * qty - discount, 0);
        }
        const excl = Math.max(price * qty - discount, 0);
        const rate =
            taxEnabled && item.is_taxable !== false ? (item.tax_rate_snapshot || 0) / 100 : 0;
        const vat = excl * rate;
        return excl + vat;
    }

    /**
     * Calculate line VAT for parts
     */
    function partLineVat(item, data = {}) {
        if (!isTaxEnabled(data) || item.is_taxable === false) return 0;
        if (item.tax_amount !== undefined && item.tax_amount !== null) {
            return Number(item.tax_amount);
        }
        const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;
        return partLineExclTax(item, data) * rate;
    }

    /**
     * Calculate line total excl. tax for parts
     */
    function partLineExclTax(item, data = {}) {
        if (item.line_total_excl_tax !== undefined && item.line_total_excl_tax !== null) {
            return Number(item.line_total_excl_tax);
        }
        if (data.pricing_mode_snapshot === 'inclusive') {
            return partLineTotal(item, data) - partLineVat(item, data);
        }
        const qty = item.qty || 1;
        const price = item.unit_price || 0;
        const discount = item.discount || 0;
        return Math.max(price * qty - discount, 0);
    }

    /**
     * Get line discount display (always returns raw discount value)
     */
    function lineDiscountDisplay(item) {
        return item.discount || 0;
    }

    /**
     * Get line subtotal display (respects showExclusive and pricing mode)
     */
    function lineSubtotalDisplay(item, data = {}) {
        if (
            item.line_total_excl_tax !== undefined &&
            item.line_total_excl_tax !== null &&
            item.line_total_excl_tax !== 0
        ) {
            return isShowExclusive(data)
                ? Number(item.line_total_excl_tax)
                : Number(item.line_total_incl_tax);
        }
        const qty = item.qty || 1;
        const price = item.unit_price || 0;
        const discount = item.discount || 0;
        const lineTotalIncl = Math.max(price * qty - discount, 0);

        if (data.pricing_mode_snapshot === 'inclusive') {
            if (isShowExclusive(data)) {
                const rate =
                    isTaxEnabled(data) && item.is_taxable !== false
                        ? item.tax_rate_snapshot != null
                            ? item.tax_rate_snapshot / 100
                            : 0.15
                        : 0;
                const tax = lineTotalIncl - lineTotalIncl / (1 + rate);
                return lineTotalIncl - tax;
            }
            return lineTotalIncl;
        }
        return lineTotalIncl;
    }

    /**
     * Check if exclusive pricing mode is shown
     */
    function isShowExclusive(data = {}) {
        if (data.pricing_mode_snapshot !== 'inclusive') {
            return true;
        }
        const taxSettings = data?.tax_settings || {};
        return taxSettings.show_amount_before_vat !== false;
    }

    /**
     * Check if tax is enabled
     */
    function isTaxEnabled(data = {}) {
        const val = data.tax_enabled_snapshot;
        if (val === true || val === 1 || val === '1') return true;
        if (val === false || val === 0 || val === '0') return false;
        if (data.total_tax !== undefined && data.total_tax !== null) {
            return Number(data.total_tax) > 0;
        }
        return true;
    }

    /**
     * Calculate total from items array
     */
    function calculateTotals(items = [], data = {}) {
        // Prioritize database-stored totals to avoid line-item calculation mismatches
        if (data.total_incl_tax !== undefined && data.total_incl_tax !== null) {
            const taxEnabled = isTaxEnabled(data);
            const total = taxEnabled
                ? Number(data.total_incl_tax || 0)
                : Number(data.total_excl_tax || data.total_incl_tax || 0);
            const vat = taxEnabled ? Number(data.total_tax || 0) : 0;

            let discountVal = Number(data.discount_amount || data.discount || 0);
            if (discountVal === 0) {
                items.forEach((item) => {
                    discountVal += Number(item.discount || 0);
                });
            }

            const isInclusive = data.pricing_mode_snapshot === 'inclusive';
            let discount = discountVal;
            let subtotal = 0;

            if (isInclusive) {
                if (isShowExclusive(data)) {
                    discount = discountVal;
                    subtotal = Number(data.total_excl_tax || 0) + discountVal;
                } else {
                    discount = discountVal;
                    subtotal = total + discountVal;
                }
            } else {
                discount = discountVal;
                subtotal = Number(data.total_excl_tax || 0) + discount;
            }

            const paid = Number(data.total_paid !== undefined ? data.total_paid : 0);
            const balance = Number(
                data.balance !== undefined ? data.balance : Math.max(total - paid, 0)
            );

            return { subtotal, discount, vat, total, paid, balance };
        }

        // Fall back to line-item calculation
        const activeItems = items.filter((item) => item.status !== 'cancelled');
        let grossSubtotal = 0;
        let discountAmt = 0;
        let taxAmt = 0;
        let totalAmt = 0;

        const taxEnabled = isTaxEnabled(data);
        const isInclusive = data.pricing_mode_snapshot === 'inclusive';
        const showExclusive = isShowExclusive(data);

        activeItems.forEach((item) => {
            const itemQty = item.qty || 1;
            const itemPrice = item.unit_price || 0;
            const itemDiscount = item.discount || 0;
            const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;

            let lineTotal = 0;
            let lineTax = 0;
            let lineExcl = 0;
            let lineDisc = 0;

            if (isInclusive) {
                lineTotal = Math.max(itemPrice * itemQty - itemDiscount, 0);
                if (taxEnabled && item.is_taxable !== false) {
                    lineTax = lineTotal - lineTotal / (1 + rate);
                }
                lineExcl = lineTotal - lineTax;
                lineDisc =
                    taxEnabled && item.is_taxable !== false
                        ? itemDiscount / (1 + rate)
                        : itemDiscount;
            } else {
                lineExcl = Math.max(itemPrice * itemQty - itemDiscount, 0);
                if (taxEnabled && item.is_taxable !== false) {
                    lineTax = lineExcl * rate;
                }
                lineTotal = lineExcl + lineTax;
                lineDisc = itemDiscount;
            }

            taxAmt += lineTax;
            totalAmt += lineTotal;

            if (showExclusive) {
                grossSubtotal += lineExcl + lineDisc;
                discountAmt += lineDisc;
            } else {
                grossSubtotal += itemPrice * itemQty;
                discountAmt += itemDiscount;
            }
        });

        const paid = data.total_paid !== undefined ? data.total_paid : 0;
        const balance = data.balance !== undefined ? data.balance : Math.max(totalAmt - paid, 0);

        return {
            subtotal: grossSubtotal,
            discount: discountAmt,
            vat: taxAmt,
            total: totalAmt,
            paid,
            balance,
        };
    }

    return {
        srvLineTotal,
        srvLineVat,
        srvLineExclTax,
        partLineTotal,
        partLineVat,
        partLineExclTax,
        lineDiscountDisplay,
        lineSubtotalDisplay,
        isShowExclusive,
        isTaxEnabled,
        calculateTotals,
    };
}

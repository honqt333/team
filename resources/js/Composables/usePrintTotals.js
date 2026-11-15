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

    /**
     * Normalize line items from data.items, data.parts, data.services, or data.lines
     * without duplicating entries across sources, while preserving legitimate identical
     * items that have no `id` within the same array and preserving item order.
     */
    function normalizeItems(data = {}) {
        if (!data || typeof data !== 'object') return [];

        const hasItems = Array.isArray(data.items) && data.items.length > 0;
        const hasParts = Array.isArray(data.parts) && data.parts.length > 0;
        const hasServices = Array.isArray(data.services) && data.services.length > 0;
        const hasLines = Array.isArray(data.lines) && data.lines.length > 0;

        if (hasItems && !hasParts && !hasServices) {
            return data.items;
        }

        const merged = [];
        const seenIds = new Set();
        const primaryUnidentifiedCounts = new Map();

        const itemFingerprint = (item) =>
            `${item.service_name || item.description || ''}|${item.qty ?? 1}|${item.unit_price ?? 0}|${item.line_total_incl_tax ?? ''}|${Boolean(item.is_part)}`;

        const pushPrimary = (item) => {
            if (!item || typeof item !== 'object') return;
            merged.push(item);
            if (item.id != null) {
                seenIds.add(item.id);
            } else {
                const fp = itemFingerprint(item);
                primaryUnidentifiedCounts.set(fp, (primaryUnidentifiedCounts.get(fp) || 0) + 1);
            }
        };

        const pushSecondary = (item, fallbackIsPart) => {
            if (!item || typeof item !== 'object') return;
            const normalized =
                fallbackIsPart !== undefined && item.is_part === undefined
                    ? { ...item, is_part: fallbackIsPart }
                    : item;

            if (normalized.id != null) {
                if (seenIds.has(normalized.id)) return;
                seenIds.add(normalized.id);
                merged.push(normalized);
                return;
            }

            const fp = itemFingerprint(normalized);
            const remaining = primaryUnidentifiedCounts.get(fp) || 0;
            if (remaining > 0) {
                primaryUnidentifiedCounts.set(fp, remaining - 1);
                return;
            }

            merged.push(normalized);
        };

        if (hasItems) {
            data.items.forEach(pushPrimary);
        } else if (hasLines) {
            data.lines.forEach(pushPrimary);
        }

        if (hasServices) {
            data.services.forEach((item) => pushSecondary(item, false));
        }

        if (hasParts) {
            data.parts.forEach((item) => pushSecondary(item, true));
        }

        return merged;
    }

    return {
        normalizeItems,
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

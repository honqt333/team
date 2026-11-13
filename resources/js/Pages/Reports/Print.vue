<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';
import { usePrint } from '@/Composables/usePrint';

const { t, locale } = useI18n();
const { formatCurrency, formatDate } = usePrint();

const props = defineProps({
    reportType: { type: String, required: true },
    backRoute: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({}) },
    rows: { type: Array, default: () => [] },
    companyInfo: { type: Object, default: () => ({}) },
});

const formatNum = (val, decimals = 2) => {
    const num = Number(val ?? 0);
    if (Number.isNaN(num)) return '0';
    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
};

const formatQty = (val) => {
    const num = Number(val ?? 0);
    if (Number.isNaN(num)) return '0';
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });
};

const reportTitle = computed(() => {
    const map = {
        payments: t('reports.payments_report.title'),
        invoices: t('reports.invoices_report.title'),
        purchase_invoices: t('reports.purchase_invoices_report.title'),
        suppliers: t('reports.suppliers_report.title'),
        stock_activities: t('reports.stock_activities_report.title'),
        tickets: t('reports.tickets_report.title'),
        services: t('reports.services_report.title'),
        customers: t('reports.customers_report.title'),
        vehicles: t('reports.vehicles_report.title'),
        dealers: t('reports.dealers_report.title'),
        employees: t('reports.employees_report.title'),
        cash_register: t('reports.cash_register_report.title'),
        financial_summary: t('reports.financial_summary_report.title'),
        work_orders: t('work_orders.title'),
        quotes: t('quotes.title'),
        departments: t('departments.title'),
        purchasing_suppliers: t('purchasing.suppliers.title'),
        purchasing_sales: t('invoices.title'),
        purchasing_orders: t('purchasing.orders.title'),
        inventory_moves: t('inventory.moves.title'),
        inventory_transfers: t('inventory.transfers.title'),
        inventory_parts: t('inventory.parts.title'),
        inventory_stock: t('inventory.stock.title'),
    };
    return map[props.reportType] || t('reports.title');
});

const reportSubtitle = computed(() => {
    const from = props.filters?.start_date || props.filters?.from_date;
    const to = props.filters?.end_date || props.filters?.to_date;
    if (from && to) {
        return `${from} — ${to}`;
    }
    return '';
});

const kpiCards = computed(() => {
    const k = props.kpis || {};
    switch (props.reportType) {
        case 'payments':
            return [
                {
                    label: t('reports.payments_report.total_collected'),
                    value: formatCurrency(k.total_collected),
                },
                {
                    label: t('reports.payments_report.total_refunded'),
                    value: formatCurrency(k.total_refunded),
                },
                {
                    label: t('reports.payments_report.net_collected'),
                    value: formatCurrency(k.net_collected),
                },
                {
                    label: t('reports.payments_report.total_records'),
                    value: formatQty(k.total_count),
                },
            ];
        case 'invoices':
            return [
                {
                    label: t('reports.invoices_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.invoices_report.total_amount'),
                    value: formatCurrency(k.total_invoice_amount),
                },
                {
                    label: t('reports.invoices_report.total_paid'),
                    value: formatCurrency(k.total_paid),
                },
                {
                    label: t('reports.invoices_report.total_remaining'),
                    value: formatCurrency(k.total_payment_remaining),
                },
            ];
        case 'purchase_invoices':
            return [
                {
                    label: t('reports.purchase_invoices_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.purchase_invoices_report.total_amount'),
                    value: formatCurrency(k.total_invoice_amount),
                },
                {
                    label: t('reports.purchase_invoices_report.total_paid'),
                    value: formatCurrency(k.total_paid),
                },
                {
                    label: t('reports.purchase_invoices_report.total_remaining'),
                    value: formatCurrency(k.total_payment_remaining),
                },
            ];
        case 'suppliers':
            return [
                {
                    label: t('reports.suppliers_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.suppliers_report.total_amount'),
                    value: formatCurrency(k.total_invoice_amount),
                },
                {
                    label: t('reports.suppliers_report.total_paid'),
                    value: formatCurrency(k.total_paid),
                },
                {
                    label: t('reports.suppliers_report.total_remaining'),
                    value: formatCurrency(k.total_payment_remaining),
                },
            ];
        case 'stock_activities':
            return [
                {
                    label: t('reports.stock_activities_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.stock_activities_report.total_incoming'),
                    value: formatQty(k.total_incoming_qty),
                },
                {
                    label: t('reports.stock_activities_report.total_outgoing'),
                    value: formatQty(k.total_outgoing_qty),
                },
                {
                    label: t('reports.stock_activities_report.total_cost'),
                    value: formatCurrency(k.total_cost),
                },
            ];
        case 'tickets':
            return [
                {
                    label: t('reports.tickets_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.tickets_report.total_amount'),
                    value: formatCurrency(k.total_amount),
                },
                {
                    label: t('reports.tickets_report.amount_paid'),
                    value: formatCurrency(k.total_paid),
                },
                {
                    label: t('reports.tickets_report.remaining_balance'),
                    value: formatCurrency(k.total_remaining),
                },
            ];
        case 'services':
            return [
                {
                    label: t('reports.services_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.services_report.service_price'),
                    value: formatCurrency(k.total_service_price),
                },
                {
                    label: t('reports.services_report.service_vat'),
                    value: formatCurrency(k.total_service_vat),
                },
                {
                    label: t('reports.services_report.total_amount'),
                    value: formatCurrency(k.total_amount),
                },
            ];
        case 'customers':
            return [
                {
                    label: t('reports.customers_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.customers_report.payment_due'),
                    value: formatCurrency(k.total_payment_due),
                },
                {
                    label: t('reports.customers_report.amount_paid'),
                    value: formatCurrency(k.total_amount_paid),
                },
                {
                    label: t('reports.customers_report.payment_remaining'),
                    value: formatCurrency(k.total_payment_remaining),
                },
            ];
        case 'vehicles':
            return [
                {
                    label: t('reports.vehicles_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.vehicles_report.service_cost'),
                    value: formatCurrency(k.total_service_cost),
                },
                {
                    label: t('reports.vehicles_report.spare_part_cost'),
                    value: formatCurrency(k.total_spare_part_cost),
                },
                { label: t('reports.vehicles_report.total'), value: formatCurrency(k.grand_total) },
            ];
        case 'dealers':
            return [
                {
                    label: t('reports.dealers_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.dealers_report.total_payment_due'),
                    value: formatCurrency(k.total_payment_due),
                },
                {
                    label: t('reports.dealers_report.total_amount_paid'),
                    value: formatCurrency(k.total_amount_paid),
                },
                {
                    label: t('reports.dealers_report.total_payment_remaining'),
                    value: formatCurrency(k.total_payment_remaining),
                },
            ];
        case 'employees':
            return [
                {
                    label: t('reports.employees_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.employees_report.total_service_hours'),
                    value: formatNum(k.total_service_hours),
                },
                {
                    label: t('reports.employees_report.total_service_cost'),
                    value: formatCurrency(k.total_service_cost),
                },
                {
                    label: t('reports.employees_report.total_avg_percent_contribution'),
                    value: `${formatNum(k.total_avg_percent_contribution)}%`,
                },
            ];
        case 'cash_register':
            return [
                {
                    label: t('reports.cash_register_report.total_records'),
                    value: formatQty(k.total_records),
                },
                {
                    label: t('reports.cash_register_report.total_debit'),
                    value: formatCurrency(k.total_debit),
                },
                {
                    label: t('reports.cash_register_report.total_credit'),
                    value: formatCurrency(k.total_credit),
                },
                {
                    label: t('reports.cash_register_report.net_balance'),
                    value: formatCurrency(k.net_balance),
                },
            ];
        case 'financial_summary':
            return [
                {
                    label: t('reports.financial_summary_report.invoices_total'),
                    value: formatCurrency(k.invoicesSumNetAmount),
                },
                {
                    label: t('reports.financial_summary_report.net_purchases'),
                    value: formatCurrency(k.purchaseSparePartsSumNetAmount),
                },
                {
                    label: t('reports.financial_summary_report.vat_due'),
                    value: formatCurrency(k.sumVATAmount),
                },
                {
                    label: t('reports.financial_summary_report.net_profit'),
                    value: formatCurrency(k.profit),
                },
            ];
        default:
            return [];
    }
});

const columns = computed(() => {
    switch (props.reportType) {
        case 'payments':
            return [
                { key: 'voucher', label: t('reports.payments_report.voucher_no') },
                { key: 'center', label: t('reports.payments_report.center') },
                { key: 'date', label: t('reports.payments_report.datetime') },
                { key: 'party', label: t('reports.payments_report.party') },
                { key: 'ref', label: t('reports.payments_report.reference') },
                { key: 'method', label: t('reports.payments_report.payment_method') },
                { key: 'type', label: t('reports.payments_report.voucher_type') },
                { key: 'amount', label: t('reports.payments_report.amount'), align: 'end' },
                { key: 'received_by', label: t('reports.payments_report.received_by') },
            ];
        case 'invoices':
            return [
                { key: 'center', label: t('reports.invoices_report.center') },
                { key: 'invoice_no', label: t('reports.invoices_report.invoice_no') },
                { key: 'issue_date', label: t('reports.invoices_report.issue_date') },
                { key: 'customer', label: t('reports.invoices_report.customer') },
                { key: 'subtotal', label: t('reports.invoices_report.subtotal'), align: 'end' },
                { key: 'discount', label: t('reports.invoices_report.discount'), align: 'end' },
                { key: 'vat', label: t('reports.invoices_report.vat'), align: 'end' },
                { key: 'total', label: t('reports.invoices_report.total'), align: 'end' },
                { key: 'paid', label: t('reports.invoices_report.paid'), align: 'end' },
                { key: 'remaining', label: t('reports.invoices_report.remaining'), align: 'end' },
                { key: 'status', label: t('reports.invoices_report.status'), align: 'center' },
            ];
        case 'purchase_invoices':
            return [
                { key: 'center', label: t('reports.purchase_invoices_report.center') },
                { key: 'code', label: t('reports.purchase_invoices_report.code') },
                { key: 'issue_date', label: t('reports.purchase_invoices_report.issue_date') },
                { key: 'supplier', label: t('reports.purchase_invoices_report.supplier') },
                {
                    key: 'subtotal',
                    label: t('reports.purchase_invoices_report.subtotal'),
                    align: 'end',
                },
                {
                    key: 'discount',
                    label: t('reports.purchase_invoices_report.discount'),
                    align: 'end',
                },
                { key: 'vat', label: t('reports.purchase_invoices_report.vat'), align: 'end' },
                { key: 'total', label: t('reports.purchase_invoices_report.total'), align: 'end' },
                { key: 'paid', label: t('reports.purchase_invoices_report.paid'), align: 'end' },
                {
                    key: 'remaining',
                    label: t('reports.purchase_invoices_report.remaining'),
                    align: 'end',
                },
                {
                    key: 'status',
                    label: t('reports.purchase_invoices_report.status'),
                    align: 'center',
                },
            ];
        case 'suppliers':
            return [
                { key: 'type', label: t('common.type') },
                { key: 'center', label: t('reports.suppliers_report.center') },
                { key: 'invoice_no', label: t('reports.suppliers_report.invoice_no') },
                { key: 'supplier_doc_no', label: t('reports.suppliers_report.supplier_doc_no') },
                { key: 'supplier', label: t('reports.suppliers_report.supplier') },
                { key: 'invoice_date', label: t('reports.suppliers_report.invoice_date') },
                { key: 'subtotal', label: t('reports.suppliers_report.subtotal'), align: 'end' },
                { key: 'vat', label: t('reports.suppliers_report.vat'), align: 'end' },
                { key: 'total', label: t('reports.suppliers_report.payment_due'), align: 'end' },
                { key: 'paid', label: t('reports.suppliers_report.amount_paid'), align: 'end' },
                {
                    key: 'remaining',
                    label: t('reports.suppliers_report.payment_remaining'),
                    align: 'end',
                },
                {
                    key: 'status',
                    label: t('reports.suppliers_report.payment_status'),
                    align: 'center',
                },
            ];
        case 'stock_activities':
            return [
                { key: 'center', label: t('reports.stock_activities_report.center') },
                { key: 'part', label: t('reports.stock_activities_report.part') },
                { key: 'move_type', label: t('reports.stock_activities_report.move_type') },
                { key: 'posted_at', label: t('reports.stock_activities_report.date') },
                {
                    key: 'before',
                    label: t('reports.stock_activities_report.balance_before'),
                    align: 'end',
                },
                { key: 'qty', label: t('reports.stock_activities_report.qty'), align: 'end' },
                {
                    key: 'after',
                    label: t('reports.stock_activities_report.balance_after'),
                    align: 'end',
                },
                {
                    key: 'unit_cost',
                    label: t('reports.stock_activities_report.unit_cost'),
                    align: 'end',
                },
                {
                    key: 'total_cost',
                    label: t('reports.stock_activities_report.total_cost'),
                    align: 'end',
                },
            ];
        case 'tickets':
            return [
                { key: 'center', label: t('reports.tickets_report.center') },
                { key: 'work_order_no', label: t('reports.tickets_report.work_order_no') },
                { key: 'date', label: t('common.date') },
                { key: 'vehicle', label: t('reports.tickets_report.vehicle') },
                { key: 'customer', label: t('reports.tickets_report.customer_party') },
                { key: 'total', label: t('reports.tickets_report.total_amount'), align: 'end' },
                { key: 'parts_cost', label: t('reports.tickets_report.parts_cost'), align: 'end' },
                { key: 'net', label: t('reports.tickets_report.net_amount'), align: 'end' },
                { key: 'paid', label: t('reports.tickets_report.amount_paid'), align: 'end' },
                {
                    key: 'remaining',
                    label: t('reports.tickets_report.remaining_balance'),
                    align: 'end',
                },
            ];
        case 'services':
            return [
                { key: 'center', label: t('reports.services_report.center') },
                { key: 'vehicle', label: t('reports.services_report.vehicle') },
                { key: 'service', label: t('reports.services_report.service') },
                { key: 'customer', label: t('reports.services_report.customer') },
                { key: 'start_date', label: t('reports.services_report.start_date') },
                { key: 'due_date', label: t('reports.services_report.due_date') },
                { key: 'price', label: t('reports.services_report.service_price'), align: 'end' },
                {
                    key: 'discount',
                    label: t('reports.services_report.service_discount'),
                    align: 'end',
                },
                { key: 'vat', label: t('reports.services_report.service_vat'), align: 'end' },
                { key: 'total', label: t('reports.services_report.total_amount'), align: 'end' },
            ];
        case 'customers':
            return [
                { key: 'center', label: t('reports.customers_report.center') },
                { key: 'work_order_no', label: t('reports.customers_report.work_order_no') },
                { key: 'vehicle', label: t('reports.customers_report.vehicle_details') },
                { key: 'customer', label: t('reports.customers_report.customer_party') },
                { key: 'check_in_date', label: t('reports.customers_report.check_in_date') },
                { key: 'due', label: t('reports.customers_report.payment_due'), align: 'end' },
                { key: 'paid', label: t('reports.customers_report.amount_paid'), align: 'end' },
                {
                    key: 'adjusted',
                    label: t('reports.customers_report.amount_adjusted'),
                    align: 'end',
                },
                {
                    key: 'remaining',
                    label: t('reports.customers_report.payment_remaining'),
                    align: 'end',
                },
                {
                    key: 'status',
                    label: t('reports.customers_report.payment_status'),
                    align: 'center',
                },
            ];
        case 'vehicles':
            return [
                { key: 'center', label: t('reports.vehicles_report.center') },
                { key: 'vehicle', label: t('reports.vehicles_report.vehicle') },
                { key: 'ticket', label: t('reports.vehicles_report.ticket') },
                { key: 'section', label: t('reports.vehicles_report.section') },
                { key: 'service', label: t('reports.vehicles_report.service') },
                { key: 'due_date', label: t('reports.vehicles_report.due_date') },
                {
                    key: 'service_cost',
                    label: t('reports.vehicles_report.service_cost'),
                    align: 'end',
                },
                {
                    key: 'parts_cost',
                    label: t('reports.vehicles_report.spare_part_cost'),
                    align: 'end',
                },
                { key: 'total', label: t('reports.vehicles_report.total'), align: 'end' },
            ];
        case 'dealers':
            return [
                { key: 'center', label: t('reports.dealers_report.col_center') },
                { key: 'code', label: t('reports.dealers_report.col_code') },
                { key: 'dealer', label: t('reports.dealers_report.col_dealer') },
                { key: 'billing', label: t('reports.dealers_report.col_billing') },
                { key: 'customer', label: t('reports.dealers_report.col_customer') },
                { key: 'vehicle', label: t('reports.dealers_report.col_vehicle') },
                { key: 'entry_date', label: t('reports.dealers_report.col_entry_date') },
                { key: 'due', label: t('reports.dealers_report.col_due'), align: 'end' },
                { key: 'paid', label: t('reports.dealers_report.col_paid'), align: 'end' },
                {
                    key: 'remaining',
                    label: t('reports.dealers_report.col_remaining'),
                    align: 'end',
                },
                { key: 'status', label: t('reports.dealers_report.col_status'), align: 'center' },
            ];
        case 'employees':
            return [
                { key: 'center', label: t('reports.employees_report.center') },
                { key: 'employee', label: t('reports.employees_report.employee') },
                { key: 'service', label: t('reports.employees_report.service') },
                { key: 'service_date', label: t('reports.employees_report.service_date') },
                {
                    key: 'service_price',
                    label: t('reports.employees_report.service_price'),
                    align: 'end',
                },
                {
                    key: 'service_discount',
                    label: t('reports.employees_report.service_discount'),
                    align: 'end',
                },
                {
                    key: 'service_cost',
                    label: t('reports.employees_report.service_cost'),
                    align: 'end',
                },
                {
                    key: 'contribution',
                    label: t('reports.employees_report.service_contribution'),
                    align: 'center',
                },
                {
                    key: 'hours',
                    label: t('reports.employees_report.service_hours'),
                    align: 'center',
                },
                {
                    key: 'avg_pct',
                    label: t('reports.employees_report.avg_percent_contribution'),
                    align: 'end',
                },
            ];
        case 'cash_register':
            return [
                { key: 'ref_no', label: t('reports.cash_register_report.reference_no') },
                { key: 'description', label: t('reports.cash_register_report.description') },
                {
                    key: 'category',
                    label: t('reports.cash_register_report.category'),
                    align: 'center',
                },
                {
                    key: 'payment_mode',
                    label: t('reports.cash_register_report.payment_mode'),
                    align: 'center',
                },
                {
                    key: 'transaction_date',
                    label: t('reports.cash_register_report.transaction_date'),
                    align: 'center',
                },
                {
                    key: 'debit',
                    label: t('reports.cash_register_report.debit_amount'),
                    align: 'end',
                },
                {
                    key: 'credit',
                    label: t('reports.cash_register_report.credit_amount'),
                    align: 'end',
                },
                {
                    key: 'user',
                    label: t('reports.cash_register_report.user_modified'),
                    align: 'center',
                },
            ];
        case 'financial_summary':
            return [
                { key: 'center', label: t('reports.financial_summary_report.center') },
                {
                    key: 'invoices',
                    label: t('reports.financial_summary_report.invoices_total'),
                    align: 'end',
                },
                {
                    key: 'returns',
                    label: t('reports.financial_summary_report.returns_total'),
                    align: 'end',
                },
                {
                    key: 'purchases',
                    label: t('reports.financial_summary_report.net_purchases'),
                    align: 'end',
                },
                {
                    key: 'salaries',
                    label: t('reports.financial_summary_report.total_salaries'),
                    align: 'end',
                },
                {
                    key: 'expenses',
                    label: t('reports.financial_summary_report.total_expenses'),
                    align: 'end',
                },
                { key: 'vat', label: t('reports.financial_summary_report.vat_due'), align: 'end' },
                {
                    key: 'profit',
                    label: t('reports.financial_summary_report.net_profit'),
                    align: 'end',
                },
            ];
        case 'work_orders':
            return [
                { key: 'code', label: t('work_orders.columns.code') },
                { key: 'date', label: t('common.date') },
                { key: 'vehicle', label: t('work_orders.columns.vehicle') },
                { key: 'customer', label: t('work_orders.columns.customer') },
                { key: 'status', label: t('work_orders.columns.status'), align: 'center' },
                { key: 'total', label: t('work_orders.columns.total'), align: 'end' },
                { key: 'paid', label: t('work_orders.columns.paid'), align: 'end' },
                { key: 'balance', label: t('work_orders.columns.balance'), align: 'end' },
            ];
        case 'quotes':
            return [
                { key: 'code', label: t('quotes.columns.code') },
                { key: 'date', label: t('common.date') },
                { key: 'vehicle', label: t('quotes.columns.vehicle') },
                { key: 'customer', label: t('quotes.columns.customer') },
                { key: 'services', label: t('quotes.card.services'), align: 'center' },
                { key: 'total', label: t('quotes.columns.total'), align: 'end' },
            ];
        case 'departments':
            return [
                { key: 'name', label: t('departments.columns.name') },
                { key: 'description', label: t('departments.columns.description') },
                {
                    key: 'services_count',
                    label: t('departments.columns.services_count'),
                    align: 'center',
                },
                { key: 'status', label: t('departments.columns.status'), align: 'center' },
            ];
        case 'purchasing_suppliers':
            return [
                { key: 'name', label: t('purchasing.suppliers.name') },
                { key: 'phone', label: t('purchasing.suppliers.phone') },
                { key: 'email', label: t('purchasing.suppliers.email') },
                { key: 'type', label: t('purchasing.suppliers.type'), align: 'center' },
                { key: 'balance', label: t('purchasing.suppliers.balance'), align: 'end' },
                { key: 'status', label: t('common.status'), align: 'center' },
            ];
        case 'purchasing_sales':
            return [
                { key: 'invoice_number', label: t('invoices.invoice_number') },
                { key: 'customer', label: t('common.customer') },
                { key: 'issue_date', label: t('invoices.issue_date') },
                { key: 'total', label: t('invoices.grand_total'), align: 'end' },
                { key: 'status', label: t('common.status'), align: 'center' },
            ];
        case 'purchasing_orders':
            return [
                { key: 'code', label: t('purchasing.orders.code') },
                { key: 'supplier', label: t('purchasing.orders.supplier') },
                { key: 'order_date', label: t('purchasing.orders.date') },
                { key: 'total', label: t('purchasing.orders.total'), align: 'end' },
                { key: 'status', label: t('common.status'), align: 'center' },
            ];
        case 'inventory_moves':
            return [
                { key: 'part', label: t('inventory.moves.columns.part') },
                { key: 'action', label: t('inventory.moves.columns.action') },
                { key: 'qty', label: t('inventory.moves.columns.qty'), align: 'end' },
                {
                    key: 'stock_after',
                    label: t('inventory.moves.columns.stock_after'),
                    align: 'end',
                },
                { key: 'cost_price', label: t('inventory.moves.columns.cost_price'), align: 'end' },
                {
                    key: 'posted_at',
                    label: t('inventory.moves.columns.updated_at'),
                    align: 'center',
                },
            ];
        case 'inventory_transfers':
            return [
                { key: 'code', label: t('inventory.transfers.code') },
                { key: 'from', label: t('inventory.transfers.from') },
                { key: 'to', label: t('inventory.transfers.to') },
                { key: 'items', label: t('inventory.transfers.items'), align: 'center' },
                { key: 'status', label: t('common.status'), align: 'center' },
                { key: 'created_at', label: t('common.created_at'), align: 'center' },
            ];
        case 'inventory_parts':
            return [
                { key: 'sku', label: t('inventory.parts.sku'), align: 'center' },
                { key: 'name', label: t('inventory.parts.name') },
                { key: 'category', label: t('inventory.parts.category'), align: 'center' },
                { key: 'unit', label: t('inventory.parts.unit'), align: 'center' },
                { key: 'qty', label: t('inventory.stock.qty'), align: 'end' },
            ];
        case 'inventory_stock':
            return [
                { key: 'sku', label: t('inventory.parts.part_number'), align: 'center' },
                { key: 'name', label: t('inventory.parts.name') },
                { key: 'category', label: t('inventory.parts.category'), align: 'center' },
                { key: 'available', label: t('inventory.stock.available'), align: 'end' },
                { key: 'avg_cost', label: t('inventory.stock.avg_cost'), align: 'end' },
                { key: 'price', label: t('inventory.parts.price'), align: 'end' },
            ];
        default:
            return [];
    }
});

const getCell = (row, key) => {
    switch (props.reportType) {
        case 'payments':
            switch (key) {
                case 'voucher':
                    return row.reference || `REC-${row.id}`;
                case 'center':
                    return row.center?.name || '-';
                case 'date':
                    return formatDate(row.payment_date);
                case 'party':
                    return row.customer?.name || row.supplier?.name || '-';
                case 'ref':
                    return row.work_order?.code || row.invoice?.invoice_number || '-';
                case 'method':
                    return row.payment_method_label || row.payment_method || '-';
                case 'type':
                    if (row.type === 'refund') return t('reports.payments_report.refund_voucher');
                    if (row.type === 'bad_debt')
                        return t('reports.payments_report.bad_debt_voucher');
                    if (row.type === 'payment') return t('reports.payments_report.receipt_voucher');
                    return row.type_label || row.type || '-';
                case 'amount':
                    return formatNum(row.amount);
                case 'received_by':
                    return row.received_by?.name || row.creator?.name || '-';
            }
            break;
        case 'invoices':
            switch (key) {
                case 'center':
                    return row.center?.name || '-';
                case 'invoice_no':
                    return row.invoice_number || '-';
                case 'issue_date':
                    return row.issue_date || '-';
                case 'customer':
                    return row.customer?.name || '-';
                case 'subtotal':
                    return formatNum(row.total_excl_tax ?? row.subtotal);
                case 'discount':
                    return formatNum(row.total_discount ?? row.discount ?? 0);
                case 'vat':
                    return formatNum(row.total_tax ?? row.tax_amount);
                case 'total':
                    return formatNum(row.total_incl_tax);
                case 'paid':
                    return formatNum(row.total_paid);
                case 'remaining':
                    return formatNum(row.remaining);
                case 'status':
                    return (
                        row.payment_status_label ||
                        (row.payment_status
                            ? t(`reports.invoices_report.status_${row.payment_status}`)
                            : '-')
                    );
            }
            break;
        case 'purchase_invoices':
            switch (key) {
                case 'center':
                    return row.center?.name || '-';
                case 'code':
                    return row.code || '-';
                case 'issue_date':
                    return row.issue_date || '-';
                case 'supplier':
                    return row.supplier?.name || '-';
                case 'subtotal':
                    return formatNum(row.subtotal);
                case 'discount':
                    return formatNum(row.discount_amount ?? row.discount ?? 0);
                case 'vat':
                    return formatNum(row.tax_amount);
                case 'total':
                    return formatNum(row.total);
                case 'paid':
                    return formatNum(row.paid);
                case 'remaining':
                    return formatNum(row.balance);
                case 'status':
                    return (
                        row.payment_status_label ||
                        (row.payment_status
                            ? t(`reports.purchase_invoices_report.status_${row.payment_status}`)
                            : '-')
                    );
            }
            break;
        case 'suppliers':
            switch (key) {
                case 'type':
                    return row.record_type
                        ? t(`reports.suppliers_report.doc_${row.record_type}`)
                        : '-';
                case 'center':
                    return row.center?.name || '-';
                case 'invoice_no':
                    return row.code || '-';
                case 'supplier_doc_no':
                    return row.invoice_number || '-';
                case 'supplier':
                    return row.supplier?.name || '-';
                case 'invoice_date':
                    return row.issue_date || '-';
                case 'subtotal':
                    return formatNum(row.subtotal);
                case 'vat':
                    return formatNum(row.tax_amount);
                case 'total':
                    return formatNum(row.total);
                case 'paid':
                    return formatNum(row.paid);
                case 'remaining':
                    return formatNum(row.balance);
                case 'status':
                    return (
                        row.payment_status_label ||
                        (row.payment_status
                            ? t(`reports.suppliers_report.status_${row.payment_status}`)
                            : '-')
                    );
            }
            break;
        case 'stock_activities':
            switch (key) {
                case 'center':
                    return (
                        [row.center?.name, row.warehouse?.name].filter(Boolean).join(' / ') || '-'
                    );
                case 'part':
                    return (
                        (locale.value === 'en'
                            ? row.part?.name_en || row.part?.name_ar
                            : row.part?.name_ar || row.part?.name_en) || '-'
                    );
                case 'move_type':
                    return row.move_type_label || row.move_type || '-';
                case 'posted_at':
                    return row.posted_at || '-';
                case 'before':
                    return formatQty(row.balance_before);
                case 'qty':
                    return `${Number(row.qty) > 0 ? '+' : ''}${formatQty(row.qty)}`;
                case 'after':
                    return formatQty(row.balance_after);
                case 'unit_cost':
                    return formatNum(row.unit_cost);
                case 'total_cost':
                    return formatNum(row.total_cost);
            }
            break;
        case 'tickets':
            switch (key) {
                case 'center':
                    return row.center?.name || row.center_name || '-';
                case 'work_order_no':
                    return row.code || '-';
                case 'date':
                    return row.entry_date || row.opened_at || row.created_at || '-';
                case 'vehicle': {
                    const name = [
                        row.vehicle?.make_name,
                        row.vehicle?.model_name,
                        row.vehicle?.year ? `(${row.vehicle.year})` : '',
                    ]
                        .filter(Boolean)
                        .join(' ');
                    return (
                        [
                            name || row.vehicle?.name || row.vehicle?.display_name,
                            row.vehicle?.plate_number,
                        ]
                            .filter(Boolean)
                            .join(' - ') || '-'
                    );
                }
                case 'customer':
                    return row.customer?.name || '-';
                case 'total':
                    return formatNum(row.total_incl_tax ?? row.total_amount);
                case 'parts_cost':
                    return formatNum(row.parts_cost);
                case 'net':
                    return formatNum(row.net_amount);
                case 'paid':
                    return formatNum(row.total_paid ?? row.amount_paid);
                case 'remaining':
                    return formatNum(row.balance ?? row.remaining_balance);
            }
            break;
        case 'services':
            switch (key) {
                case 'center':
                    return row.center_name || '-';
                case 'vehicle':
                    return (
                        [row.vehicle?.display_name, row.vehicle?.plate_number]
                            .filter(Boolean)
                            .join(' - ') || '-'
                    );
                case 'service':
                    return row.service_name || row.name || '-';
                case 'customer':
                    return row.customer?.name || '-';
                case 'start_date':
                    return row.start_date || '-';
                case 'due_date':
                    return row.due_date || '-';
                case 'price':
                    return formatNum(row.service_price);
                case 'discount':
                    return formatNum(row.service_discount);
                case 'vat':
                    return formatNum(row.service_vat);
                case 'total':
                    return formatNum(row.total_amount);
            }
            break;
        case 'customers':
            switch (key) {
                case 'center':
                    return row.center_name || '-';
                case 'work_order_no':
                    return row.code || '-';
                case 'vehicle':
                    return (
                        [row.vehicle?.name, row.vehicle?.plate_number]
                            .filter(Boolean)
                            .join(' - ') || '-'
                    );
                case 'customer':
                    return row.customer?.name || '-';
                case 'check_in_date':
                    return row.entry_date || row.check_in_date || '-';
                case 'due':
                    return formatNum(row.payment_due);
                case 'paid':
                    return formatNum(row.amount_paid);
                case 'adjusted':
                    return formatNum(row.amount_adjusted);
                case 'remaining':
                    return formatNum(row.payment_remaining);
                case 'status':
                    return (
                        row.payment_status_label ||
                        (row.payment_status
                            ? t(`reports.invoices_report.status_${row.payment_status}`)
                            : '-')
                    );
            }
            break;
        case 'vehicles':
            switch (key) {
                case 'center':
                    return row.shop_name || '-';
                case 'vehicle':
                    return (
                        [row.vehicle_display_name, row.plate_no].filter(Boolean).join(' - ') || '-'
                    );
                case 'ticket':
                    return row.work_order_code || row.ticket_no || '-';
                case 'section':
                    return row.section_name || row.department_name || '-';
                case 'service':
                    return row.service_name || '-';
                case 'due_date':
                    return row.due_date || '-';
                case 'service_cost':
                    return formatNum(row.service_cost);
                case 'parts_cost':
                    return formatNum(row.spare_part_cost);
                case 'total':
                    return formatNum(row.total);
            }
            break;
        case 'dealers':
            switch (key) {
                case 'center':
                    return row.center_name || '-';
                case 'code':
                    return row.code || '-';
                case 'dealer':
                    return (
                        (locale.value === 'en'
                            ? row.dealer?.name_en || row.dealer?.name
                            : row.dealer?.name) || '-'
                    );
                case 'billing':
                    return row.is_paid_by_dealer
                        ? t('reports.dealers_report.billing_dealer')
                        : t('reports.dealers_report.billing_customer');
                case 'customer':
                    return row.customer?.name || '-';
                case 'vehicle':
                    return (
                        [row.vehicle?.name, row.vehicle?.plate_number]
                            .filter(Boolean)
                            .join(' - ') || '-'
                    );
                case 'entry_date':
                    return row.entry_date || '-';
                case 'due':
                    return formatNum(row.payment_due);
                case 'paid':
                    return formatNum(row.amount_paid);
                case 'remaining':
                    return formatNum(row.payment_remaining);
                case 'status':
                    return row.payment_status
                        ? t(`reports.dealers_report.${row.payment_status}`)
                        : '-';
            }
            break;
        case 'employees':
            switch (key) {
                case 'center':
                    return row.center_name || '-';
                case 'employee':
                    return row.employee_name || '-';
                case 'service':
                    return row.service_name || '-';
                case 'service_date':
                    return row.service_date || '-';
                case 'service_price':
                    return formatNum(row.service_price);
                case 'service_discount':
                    return formatNum(row.service_discount);
                case 'service_cost':
                    return formatNum(row.service_cost);
                case 'contribution':
                    return `${formatQty(row.service_contribution)}%`;
                case 'hours':
                    return formatNum(row.service_hours);
                case 'avg_pct':
                    return `${formatNum(row.avg_percent_contribution)}%`;
            }
            break;
        case 'cash_register':
            switch (key) {
                case 'ref_no':
                    return row.ref_no || '-';
                case 'description':
                    return [row.description, row.center_name].filter(Boolean).join(' — ') || '-';
                case 'category':
                    return row.category_label || row.category || '-';
                case 'payment_mode':
                    return row.payment_mode_label || row.payment_mode || '-';
                case 'transaction_date':
                    return row.transaction_date || '-';
                case 'debit':
                    return formatNum(row.debit);
                case 'credit':
                    return formatNum(row.credit);
                case 'user':
                    return row.user_name || '-';
            }
            break;
        case 'financial_summary': {
            const s = row.shopFinancialSummary || {};
            switch (key) {
                case 'center':
                    return row.center_name || row.shop?.name || '-';
                case 'invoices':
                    return formatNum(s.invoicesSumNetAmount);
                case 'returns':
                    return formatNum(s.returnInvoicesSumNetAmount);
                case 'purchases':
                    return formatNum(s.purchaseSparePartsSumNetAmount);
                case 'salaries':
                    return formatNum(s.totalSalary);
                case 'expenses':
                    return formatNum(s.expenseSumNetAmount);
                case 'vat':
                    return formatNum(s.sumVATAmount);
                case 'profit':
                    return formatNum(s.profit);
            }
            break;
        }
        case 'work_orders':
            switch (key) {
                case 'code':
                    return row.code || '-';
                case 'date':
                    return formatDate(row.entry_date || row.created_at);
                case 'vehicle':
                    return row.vehicle?.plate_number || '-';
                case 'customer':
                    return row.customer?.name || '-';
                case 'status':
                    return row.status ? t(`work_orders.status.${row.status}`) : '-';
                case 'total':
                    return formatNum(row.total);
                case 'paid':
                    return formatNum(row.total_paid ?? row.paid_amount);
                case 'balance':
                    return formatNum(
                        row.balance ??
                            (Number(row.total) || 0) -
                                (Number(row.total_paid ?? row.paid_amount) || 0)
                    );
            }
            break;
        case 'quotes':
            switch (key) {
                case 'code':
                    return row.code || '-';
                case 'date':
                    return formatDate(row.created_at);
                case 'vehicle':
                    return row.vehicle?.plate_number || '-';
                case 'customer':
                    return row.customer?.name || '-';
                case 'services':
                    return formatQty(row.lines_count || 0);
                case 'total':
                    return formatNum(row.total);
            }
            break;
        case 'departments':
            switch (key) {
                case 'name':
                    return (
                        (locale.value === 'en'
                            ? row.name_en || row.name_ar
                            : row.name_ar || row.name_en) ||
                        row.name ||
                        '-'
                    );
                case 'description':
                    return row.description || '-';
                case 'services_count':
                    return formatQty(row.services_count || 0);
                case 'status':
                    return row.is_active ? t('common.active') : t('common.inactive');
            }
            break;
        case 'purchasing_suppliers':
            switch (key) {
                case 'name':
                    return row.name || '-';
                case 'phone':
                    return row.phone || '-';
                case 'email':
                    return row.email || '-';
                case 'type':
                    return row.type === 'parts'
                        ? t('purchasing.suppliers.type_parts')
                        : t('purchasing.suppliers.type_services');
                case 'balance':
                    return formatNum(row.balance);
                case 'status':
                    return row.is_active ? t('common.active') : t('common.inactive');
            }
            break;
        case 'purchasing_sales':
            switch (key) {
                case 'invoice_number':
                    return row.invoice_number || '-';
                case 'customer':
                    return row.customer_name_snapshot || row.customer?.name || '-';
                case 'issue_date':
                    return formatDate(row.issue_date || row.created_at);
                case 'total':
                    return formatNum(row.total_incl_tax ?? row.total);
                case 'status':
                    return row.payment_status
                        ? t(`invoices.payment_status.${row.payment_status}`)
                        : row.status || '-';
            }
            break;
        case 'purchasing_orders':
            switch (key) {
                case 'code':
                    return row.code || '-';
                case 'supplier':
                    return row.supplier?.name || '-';
                case 'order_date':
                    return formatDate(row.order_date || row.created_at);
                case 'total':
                    return formatNum(row.total);
                case 'status':
                    return row.status || '-';
            }
            break;
        case 'inventory_moves':
            switch (key) {
                case 'part':
                    return (
                        [
                            locale.value === 'en'
                                ? row.part?.name_en || row.part?.name_ar
                                : row.part?.name_ar || row.part?.name_en,
                            row.part?.sku,
                        ]
                            .filter(Boolean)
                            .join(' - ') || '-'
                    );
                case 'action':
                    return row.move_type || '-';
                case 'qty':
                    return `${Number(row.qty) > 0 ? '+' : ''}${formatQty(row.qty)}`;
                case 'stock_after':
                    return formatQty(row.balance_after);
                case 'cost_price':
                    return formatNum(row.unit_cost);
                case 'posted_at':
                    return formatDate(row.posted_at || row.created_at);
            }
            break;
        case 'inventory_transfers':
            switch (key) {
                case 'code':
                    return row.code || '-';
                case 'from':
                    return row.from_warehouse?.name || '-';
                case 'to':
                    return row.to_warehouse?.name || '-';
                case 'items':
                    return formatQty(row.items_count ?? 0);
                case 'status':
                    return row.status || '-';
                case 'created_at':
                    return formatDate(row.created_at);
            }
            break;
        case 'inventory_parts':
            switch (key) {
                case 'sku':
                    return row.sku || '-';
                case 'name':
                    return (
                        (locale.value === 'en'
                            ? row.name_en || row.name_ar
                            : row.name_ar || row.name_en) || '-'
                    );
                case 'category':
                    return (
                        (locale.value === 'en'
                            ? row.category?.name_en || row.category?.name_ar
                            : row.category?.name_ar || row.category?.name_en) || '-'
                    );
                case 'unit':
                    return (
                        (locale.value === 'en'
                            ? row.unit?.name_en || row.unit?.name_ar
                            : row.unit?.name_ar || row.unit?.name_en) || '-'
                    );
                case 'qty':
                    return formatQty(row.qty_on_hand ?? row.total_qty ?? 0);
            }
            break;
        case 'inventory_stock':
            switch (key) {
                case 'sku':
                    return row.part?.sku || '-';
                case 'name':
                    return (
                        (locale.value === 'en'
                            ? row.part?.name_en || row.part?.name_ar
                            : row.part?.name_ar || row.part?.name_en) || '-'
                    );
                case 'category':
                    return (
                        (locale.value === 'en'
                            ? row.part?.category?.name_en || row.part?.category?.name_ar
                            : row.part?.category?.name_ar || row.part?.category?.name_en) || '-'
                    );
                case 'available':
                    return `${formatQty(row.qty_on_hand)} ${row.part?.unit?.name_ar || ''}`.trim();
                case 'avg_cost':
                    return formatNum(row.wac_cost);
                case 'price':
                    return formatNum(row.sale_price);
            }
            break;
    }
    return '-';
};
</script>

<template>
    <Head :title="reportTitle" />

    <StandardReportLayout
        :title="reportTitle"
        :subtitle="reportSubtitle"
        :total-count="rows.length"
        :custom-company="companyInfo"
        :back-route="backRoute"
        default-orientation="landscape"
        :show-orientation-toggle="true"
    >
        <!-- KPI Summary Strip -->
        <div v-if="kpiCards.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div
                v-for="(kpi, idx) in kpiCards"
                :key="idx"
                class="border border-gray-200 rounded-lg p-2.5 bg-gray-50/70"
            >
                <div class="text-[10px] font-semibold text-gray-500 mb-0.5">
                    {{ kpi.label }}
                </div>
                <div class="text-sm font-bold text-gray-900 font-mono">
                    {{ kpi.value }}
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-[11px] border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100 text-gray-800 font-bold">
                        <th class="border border-gray-300 px-2 py-2 text-center w-9">#</th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="border border-gray-300 px-2 py-2"
                            :class="{
                                'text-end': col.align === 'end',
                                'text-center': col.align === 'center',
                                'text-start': !col.align,
                            }"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td
                            :colspan="columns.length + 1"
                            class="border border-gray-300 px-3 py-6 text-center text-gray-500"
                        >
                            {{ $t('common.no_data') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(row, idx) in rows"
                        :key="row.id ?? idx"
                        class="odd:bg-white even:bg-gray-50/50 break-inside-avoid"
                    >
                        <td
                            class="border border-gray-300 px-2 py-1.5 text-center font-mono text-gray-500"
                        >
                            {{ idx + 1 }}
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="border border-gray-300 px-2 py-1.5"
                            :class="{
                                'text-end font-mono': col.align === 'end',
                                'text-center': col.align === 'center',
                                'text-start': !col.align,
                            }"
                        >
                            {{ getCell(row, col.key) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </StandardReportLayout>
</template>

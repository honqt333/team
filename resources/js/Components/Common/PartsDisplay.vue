<template>
    <div class="space-y-6">
        <!-- Header with View Toggle -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-surface-muted flex items-center justify-center text-text-muted border border-border/60 shadow-xs"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-text-primary uppercase tracking-wider">
                            {{ $t('quotes.show.tabs.spare_parts') }}
                        </h3>
                        <p class="text-[10px] font-bold text-text-muted uppercase tracking-[0.2em]">
                            {{ toEnglish(parts.length) }} {{ $t('quotes.show.tabs.spare_parts') }}
                        </p>
                    </div>
                </div>

                <!-- Add Part Button next to title -->
                <button
                    v-if="!readOnly"
                    @click="$emit('add')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-action-primary hover:bg-action-primary-hover active:bg-action-primary text-action-on text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    {{ addButtonText || $t('quotes.show.add_part') }}
                </button>
            </div>

            <!-- View Toggle Buttons -->
            <div
                class="flex items-center gap-1 bg-surface-muted p-1 rounded-xl border border-border/60"
            >
                <button
                    @click="viewMode = 'grid'"
                    :class="[
                        'p-2 rounded-lg transition-all cursor-pointer',
                        viewMode === 'grid'
                            ? 'bg-surface shadow-xs text-action-primary font-bold'
                            : 'text-text-muted hover:text-text-primary',
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                        />
                    </svg>
                </button>
                <button
                    @click="viewMode = 'table'"
                    :class="[
                        'p-2 rounded-lg transition-all cursor-pointer',
                        viewMode === 'table'
                            ? 'bg-surface shadow-xs text-action-primary font-bold'
                            : 'text-text-muted hover:text-text-primary',
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div
            v-if="viewMode === 'grid'"
            :class="[
                'grid gap-6',
                compactGrid
                    ? 'grid-cols-1 sm:grid-cols-2'
                    : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
            ]"
        >
            <div
                v-for="(part, index) in parts"
                :key="part.id"
                class="relative bg-surface rounded-[2.5rem] border border-border shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-500 group overflow-hidden"
            >
                <!-- Source Color Strip -->
                <div
                    :class="[
                        'absolute top-0 inset-x-0 h-1.5',
                        getSourceColorClass(part.source, 'strip'),
                    ]"
                ></div>

                <!-- Card Content -->
                <div class="p-6 space-y-5">
                    <!-- Header -->
                    <div class="flex items-start justify-between">
                        <div class="space-y-1 max-w-[70%]">
                            <h4
                                class="font-black text-text-primary leading-tight break-words"
                                :title="part.name"
                            >
                                {{ part.name }}
                            </h4>
                            <p
                                class="font-mono text-[10px] font-bold text-text-muted tracking-tighter uppercase"
                            >
                                {{ part.part_number || part.sku || '---' }}
                            </p>
                        </div>
                        <span
                            :class="[
                                'px-2.5 py-1 text-[10px] font-black rounded-lg uppercase tracking-wider',
                                getSourceColorClass(part.source, 'badge'),
                            ]"
                        >
                            {{ $t('quotes.parts.' + part.source) }}
                        </span>
                    </div>

                    <!-- Pricing Display (Simplified Grid 2x2) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-2.5 bg-surface-muted/60 rounded-xl border border-border/60">
                            <p
                                class="text-[8px] font-black text-text-muted uppercase tracking-widest mb-1"
                            >
                                {{ $t('inventory.parts.unit_price') }}
                            </p>
                            <p class="text-xs font-mono font-bold text-text-primary" dir="ltr">
                                {{ formatCurrency(part.unit_price) }}
                            </p>
                        </div>
                        <div class="p-2.5 bg-surface-muted/60 rounded-xl border border-border/60">
                            <p
                                class="text-[8px] font-black text-text-muted uppercase tracking-widest mb-1"
                            >
                                {{ $t('quotes.show.discount') }}
                            </p>
                            <p class="text-xs font-mono font-bold text-danger" dir="ltr">
                                - {{ formatCurrency(part.discount || 0) }}
                            </p>
                        </div>
                        <div class="p-2.5 bg-surface-muted/60 rounded-xl border border-border/60">
                            <p
                                class="text-[8px] font-black text-text-muted uppercase tracking-widest mb-1"
                            >
                                {{ $t('work_orders.item.qty') }}
                            </p>
                            <p class="text-xs font-mono font-black text-success">
                                {{ formatQuantity(part.qty) }}
                                <span class="text-[10px]">{{ getUnitName(part) }}</span>
                            </p>
                            <div
                                v-if="
                                    part.source === 'warehouse' &&
                                    (parseFloat(part.issued_qty) > 0 ||
                                        parseFloat(part.returned_qty) > 0)
                                "
                                class="mt-1 flex flex-wrap gap-x-1.5 text-[8px] text-text-muted font-bold leading-tight"
                            >
                                <span>
                                    {{ $t('inventory.parts.issued_qty_short') }}
                                    {{ formatQuantity(part.issued_qty || part.qty) }}
                                </span>
                                <span class="opacity-50">|</span>
                                <span>
                                    {{ $t('inventory.parts.returned_qty_short') }}
                                    {{ formatQuantity(part.returned_qty || 0) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-2.5 bg-surface-muted/60 rounded-xl border border-border/60">
                            <p
                                class="text-[8px] font-black text-text-muted uppercase tracking-widest mb-1"
                            >
                                {{ $t('quotes.tax_summary.vat') }}
                            </p>
                            <p class="text-xs font-mono font-bold text-info" dir="ltr">
                                + {{ formatCurrency(part.tax_amount || 0) }}
                            </p>
                        </div>
                    </div>

                    <!-- Linked Info -->
                    <div v-if="showService || isPending(part)" class="space-y-3 pt-1">
                        <div
                            v-if="showService && getLinkedServiceName(part)"
                            @click="hasWoItem(part) ? clickService(part) : null"
                            :class="[
                                'flex items-center gap-2 text-[10px] font-bold p-2 rounded-lg transition-colors',
                                hasWoItem(part)
                                    ? 'text-info bg-info-soft hover:bg-info/20 cursor-pointer'
                                    : 'text-text-muted bg-surface-muted',
                            ]"
                        >
                            <svg
                                class="w-3 h-3"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                            <span class="truncate">{{ getLinkedServiceName(part) }}</span>
                        </div>
                        <div
                            v-if="isPending(part)"
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-warning-soft text-warning text-[9px] font-black uppercase"
                        >
                            <span class="w-1 h-1 rounded-full bg-warning animate-pulse"></span>
                            {{ $t('common.pending') }}
                        </div>
                    </div>

                    <!-- External Part Mapped Purchase Allocation -->
                    <div
                        v-if="
                            part.source === 'external' &&
                            (part.mapped_allocation || getPartAllocation(part.id))
                        "
                        class="flex items-center justify-between gap-2 p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                    >
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="text-emerald-600 dark:text-emerald-400 shrink-0">🔗</span>
                            <span class="font-bold truncate">
                                {{ (part.mapped_allocation || getPartAllocation(part.id)).name }}
                            </span>
                            <span class="font-mono text-[11px] shrink-0" dir="ltr">
                                [{{
                                    formatCurrency(
                                        (part.mapped_allocation || getPartAllocation(part.id))
                                            .unit_cost
                                    )
                                }}
                                × {{ (part.mapped_allocation || getPartAllocation(part.id)).qty }} =
                                {{
                                    formatCurrency(
                                        (part.mapped_allocation || getPartAllocation(part.id)).total
                                    )
                                }}]
                            </span>
                        </div>
                        <button
                            v-if="!readOnly"
                            type="button"
                            @click.stop="$emit('unlink-part-allocation', part.id)"
                            class="text-rose-500 hover:text-rose-700 p-1 rounded transition-colors shrink-0 cursor-pointer"
                            :title="$t('work_orders.purchases.unlink_btn')"
                        >
                            🗑️
                        </button>
                    </div>
                </div>

                <!-- Card Footer (Total Bar) -->
                <div
                    :class="[
                        'px-6 py-5 flex items-center justify-between border-t border-border',
                        getSourceColorClass(part.source, 'footer'),
                    ]"
                >
                    <div class="space-y-0.5">
                        <p class="text-[10px] font-black opacity-60 uppercase tracking-[0.2em]">
                            {{ $t('quotes.show.total') }}
                        </p>
                        <div
                            v-if="part.tax_amount > 0"
                            class="text-[9px] font-bold opacity-50 flex items-center gap-1"
                        >
                            <svg
                                class="w-2.5 h-2.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                                />
                            </svg>
                            {{ formatCurrency(part.tax_amount) }} {{ $t('quotes.tax_summary.vat') }}
                        </div>
                    </div>
                    <div class="text-end">
                        <span
                            class="text-2xl font-black font-mono tracking-tighter text-text-primary"
                            dir="ltr"
                        >
                            {{
                                formatCurrency(
                                    part.total_incl_tax ||
                                        Number(part.unit_price) * Number(part.qty) -
                                            Number(part.discount || 0) +
                                            Number(part.tax_amount || 0)
                                )
                            }}
                        </span>
                    </div>
                </div>

                <!-- Actions Overlay -->
                <div
                    v-if="!readOnly"
                    class="absolute top-12 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0"
                >
                    <button
                        @click="$emit('edit', part)"
                        :title="
                            part.status === 'issued'
                                ? $t('quotes.parts.issue_more')
                                : $t('common.edit')
                        "
                        class="p-2.5 bg-surface border border-border shadow-md rounded-xl text-success hover:scale-110 active:scale-95 transition-all cursor-pointer"
                    >
                        <svg
                            v-if="part.status === 'issued'"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </button>
                    <button
                        @click="$emit('delete', part)"
                        :title="
                            part.status === 'issued'
                                ? $t('quotes.parts.return_to_warehouse')
                                : $t('common.delete')
                        "
                        class="p-2.5 bg-surface border border-border shadow-md rounded-xl text-danger hover:scale-110 active:scale-95 transition-all cursor-pointer"
                    >
                        <svg
                            v-if="part.status === 'issued'"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div
            v-else
            class="bg-surface rounded-[2.5rem] border border-border shadow-xs overflow-hidden"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-muted/60 border-b border-border">
                            <th
                                class="px-6 py-4 text-start text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                #
                            </th>
                            <th
                                class="px-6 py-4 text-start text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('inventory.parts.name') }}
                            </th>
                            <th
                                class="px-6 py-4 text-start text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('quotes.parts.source') }}
                            </th>
                            <th
                                v-if="showService"
                                class="px-6 py-4 text-start text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('quotes.parts_display.service') }}
                            </th>
                            <th
                                class="px-6 py-4 text-end text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('inventory.parts.unit_price') }}
                            </th>
                            <th
                                class="px-6 py-4 text-center text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('work_orders.item.qty') }}
                            </th>
                            <th
                                class="px-6 py-4 text-end text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('quotes.show.amount') }}
                            </th>
                            <th
                                v-if="showVat"
                                class="px-6 py-4 text-end text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('common.vat') }}
                            </th>
                            <th
                                class="px-6 py-4 text-end text-[10px] font-black text-text-secondary uppercase tracking-widest"
                            >
                                {{ $t('quotes.show.total') }}
                            </th>
                            <th v-if="!readOnly" class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="(part, index) in parts"
                            :key="part.id"
                            class="group hover:bg-surface-muted/50 transition-colors"
                        >
                            <td class="px-6 py-4 text-xs font-bold text-text-muted">
                                {{ toEnglish(index + 1) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-0.5">
                                    <p class="font-bold text-text-primary">
                                        {{ part.name }}
                                    </p>
                                    <p
                                        class="font-mono text-[9px] text-text-muted uppercase tracking-tighter"
                                    >
                                        {{ part.part_number || part.sku }}
                                    </p>
                                    <!-- External Part Mapped Purchase Allocation -->
                                    <div
                                        v-if="
                                            part.source === 'external' &&
                                            (part.mapped_allocation || getPartAllocation(part.id))
                                        "
                                        class="mt-1 flex items-center justify-between gap-1.5 px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-[10px] font-semibold text-emerald-800 dark:text-emerald-300"
                                    >
                                        <div class="flex items-center gap-1 truncate">
                                            <span
                                                class="text-emerald-600 dark:text-emerald-400 shrink-0"
                                            >
                                                🔗
                                            </span>
                                            <span class="font-bold truncate">
                                                {{
                                                    (
                                                        part.mapped_allocation ||
                                                        getPartAllocation(part.id)
                                                    ).name
                                                }}
                                            </span>
                                            <span class="font-mono text-[9px] shrink-0" dir="ltr">
                                                [{{
                                                    formatCurrency(
                                                        (
                                                            part.mapped_allocation ||
                                                            getPartAllocation(part.id)
                                                        ).unit_cost
                                                    )
                                                }}
                                                ×
                                                {{
                                                    (
                                                        part.mapped_allocation ||
                                                        getPartAllocation(part.id)
                                                    ).qty
                                                }}
                                                =
                                                {{
                                                    formatCurrency(
                                                        (
                                                            part.mapped_allocation ||
                                                            getPartAllocation(part.id)
                                                        ).total
                                                    )
                                                }}]
                                            </span>
                                        </div>
                                        <button
                                            v-if="!readOnly"
                                            type="button"
                                            @click.stop="$emit('unlink-part-allocation', part.id)"
                                            class="text-rose-500 hover:text-rose-700 p-0.5 rounded transition-colors shrink-0 cursor-pointer"
                                            :title="$t('work_orders.purchases.unlink_btn')"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'px-2 py-0.5 text-[9px] font-black rounded-md uppercase tracking-wider',
                                        getSourceColorClass(part.source, 'badge'),
                                    ]"
                                >
                                    {{ $t(`quotes.parts.${part.source}`) }}
                                </span>
                            </td>
                            <td v-if="showService" class="px-6 py-4">
                                <button
                                    v-if="hasWoItem(part)"
                                    @click.stop="clickService(part)"
                                    type="button"
                                    class="text-[10px] font-bold text-info hover:text-info/80 hover:underline max-w-[120px] truncate block text-start transition-colors cursor-pointer"
                                >
                                    {{ getLinkedServiceName(part) }}
                                </button>
                                <span
                                    v-else
                                    class="text-[10px] font-bold text-text-muted max-w-[120px] truncate block"
                                >
                                    {{ getLinkedServiceName(part) || '---' }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-end font-mono text-xs font-bold text-text-secondary"
                            >
                                {{ formatCurrency(part.unit_price) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-mono text-xs font-black text-success block">
                                    {{ formatQuantity(part.qty) }}
                                </span>
                                <div
                                    v-if="
                                        part.source === 'warehouse' &&
                                        (parseFloat(part.issued_qty) > 0 ||
                                            parseFloat(part.returned_qty) > 0)
                                    "
                                    class="text-[8px] text-text-muted font-bold mt-1 flex items-center justify-center gap-1.5 leading-tight"
                                >
                                    <span>
                                        {{ $t('inventory.parts.issued_qty_short') }}
                                        {{ formatQuantity(part.issued_qty || part.qty) }}
                                    </span>
                                    <span class="opacity-50">|</span>
                                    <span>
                                        {{ $t('inventory.parts.returned_qty_short') }}
                                        {{ formatQuantity(part.returned_qty || 0) }}
                                    </span>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-end font-mono text-xs font-bold text-text-primary"
                            >
                                {{
                                    formatCurrency(
                                        part.unit_price * part.qty - (part.discount || 0)
                                    )
                                }}
                            </td>
                            <td
                                v-if="showVat"
                                class="px-6 py-4 text-end font-mono text-xs font-bold text-info"
                            >
                                {{ formatCurrency(part.tax_amount || 0) }}
                            </td>
                            <td
                                class="px-6 py-4 text-end font-mono text-sm font-black text-text-primary"
                            >
                                {{
                                    formatCurrency(
                                        part.total_incl_tax ||
                                            Number(part.unit_price) * Number(part.qty) -
                                                Number(part.discount || 0) +
                                                Number(part.tax_amount || 0)
                                    )
                                }}
                            </td>
                            <td v-if="!readOnly" class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <button
                                        @click="$emit('edit', part)"
                                        :title="
                                            part.status === 'issued'
                                                ? $t('quotes.parts.issue_more')
                                                : $t('common.edit')
                                        "
                                        class="p-1.5 text-success hover:bg-success-soft rounded-lg transition-colors cursor-pointer"
                                    >
                                        <svg
                                            v-if="part.status === 'issued'"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 4v16m8-8H4"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="$emit('delete', part)"
                                        :title="
                                            part.status === 'issued'
                                                ? $t('quotes.parts.return_to_warehouse')
                                                : $t('common.delete')
                                        "
                                        class="p-1.5 text-danger hover:bg-danger-soft rounded-lg transition-colors cursor-pointer"
                                    >
                                        <svg
                                            v-if="part.status === 'issued'"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="parts.length === 0"
            class="flex flex-col items-center justify-center py-10 px-4 bg-surface-muted/40 rounded-[2rem] border-2 border-dashed border-border transition-all hover:bg-surface-muted/70"
        >
            <div
                class="w-14 h-14 mb-4 rounded-2xl bg-surface shadow-xs border border-border flex items-center justify-center text-text-muted group-hover:text-action-primary transition-colors"
            >
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                </svg>
            </div>
            <p class="text-sm font-black text-text-primary uppercase tracking-wider mb-1">
                {{ emptyMessage || $t('quotes.show.no_parts') }}
            </p>
            <p class="text-[11px] font-bold text-text-muted uppercase tracking-widest mb-5">
                {{ $t('quotes.parts_display.empty_hint') }}
            </p>
            <button
                v-if="!readOnly"
                @click="$emit('add')"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-action-on text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-md hover:-translate-y-0.5 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                {{ addButtonText || $t('quotes.show.add_part') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    parts: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: false },
    showVat: { type: Boolean, default: false },
    emptyMessage: { type: String, default: '' },
    addButtonText: { type: String, default: '' },
    storageKey: { type: String, default: 'parts_display_view_mode' },
    showService: { type: Boolean, default: true },
    compactGrid: { type: Boolean, default: false },
    pendingCheck: { type: Function, default: null },
    partAllocations: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['edit', 'delete', 'add', 'click-service', 'unlink-part-allocation']);

function getPartAllocation(partId) {
    if (props.partAllocations && props.partAllocations[partId]) {
        return props.partAllocations[partId];
    }
    return null;
}
const { getName } = useLocalized();
const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();

// View mode state
const viewMode = ref('grid');

onMounted(() => {
    const saved = localStorage.getItem(props.storageKey);
    if (saved && ['grid', 'table'].includes(saved)) viewMode.value = saved;
});

watch(viewMode, (newVal) => localStorage.setItem(props.storageKey, newVal));

const isPending = (part) => (props.pendingCheck ? props.pendingCheck(part) : !part.id);

const getUnitName = (part) => (part.unit ? getName(part.unit) : '');

const hasWoItem = (part) =>
    !!(
        part.work_order_item_id ||
        part.work_order_item ||
        part.workOrderItem ||
        part.quote_line_id ||
        part.quote_line
    );

function clickService(part) {
    emit('click-service', part);
}

function getLinkedServiceName(part) {
    if (part.quote_line) {
        return part.quote_line.description || getName(part.quote_line.service);
    }
    const woItem = part.work_order_item || part.workOrderItem;
    if (woItem) {
        return woItem.title || getName(woItem.service);
    }
    if (part.service) {
        return getName(part.service);
    }
    return '';
}

function getSourceColorClass(source, type) {
    const colors = {
        warehouse: {
            strip: 'bg-success',
            badge: 'bg-success text-white',
            footer: 'bg-success-soft text-text-primary',
        },
        external: {
            strip: 'bg-info',
            badge: 'bg-info text-white',
            footer: 'bg-info-soft text-text-primary',
        },
        customer: {
            strip: 'bg-warning',
            badge: 'bg-warning text-white',
            footer: 'bg-warning-soft text-text-primary',
        },
    };
    return colors[source]?.[type] || colors.external[type];
}
</script>

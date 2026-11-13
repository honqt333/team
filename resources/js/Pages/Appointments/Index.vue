<template>
    <AppLayout>
        <Head :title="$t('appointments.title')" />

        <div class="space-y-6">
            <PageHeader
                :title="$t('appointments.title')"
                :subtitle="$t('appointments.subtitle')"
                :total-count="toEnglish(appointments.length)"
                :count-label="$t('appointments.total_count')"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Glass Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <Link
                                v-if="can('crm.appointments.manage')"
                                :href="route('service-bays.index', { from: 'appointments' })"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-content-secondary hover:text-action-primary hover:bg-surface-primary transition-colors"
                            >
                                <svg
                                    class="w-4 h-4 text-action-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    />
                                </svg>
                                <span>{{ $t('service_bays.title') }}</span>
                            </Link>

                            <div class="w-px h-5 bg-border-subtle mx-1"></div>

                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="appointments_view_mode"
                            />
                        </div>

                        <!-- Outer-left CTA -->
                        <button
                            v-if="can('crm.appointments.manage')"
                            type="button"
                            @click="openCreate"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span class="text-sm">{{ $t('appointments.new') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div
                class="rounded-2xl border border-border-subtle bg-surface-primary p-4 shadow-2xs flex flex-wrap items-end gap-3"
            >
                <div>
                    <label class="block text-xs font-bold text-content-muted mb-1">
                        {{ $t('appointments.filter_date') }}
                    </label>
                    <CustomDatePicker v-model="filters.date" @change="reload" />
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs font-bold text-content-muted mb-1">
                        {{ $t('appointments.filter_status') }}
                    </label>
                    <SearchableSelect
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.all')"
                        :label="''"
                        :compact="true"
                        @change="reload"
                    />
                </div>
                <div class="min-w-[160px]">
                    <label class="block text-xs font-bold text-content-muted mb-1">
                        {{ $t('appointments.filter_bay') }}
                    </label>
                    <SearchableSelect
                        v-model="filters.service_bay_id"
                        :options="bayOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.all')"
                        :label="''"
                        :compact="true"
                        @change="reload"
                    />
                </div>
                <div class="min-w-[160px]">
                    <label class="block text-xs font-bold text-content-muted mb-1">
                        {{ $t('appointments.filter_tech') }}
                    </label>
                    <SearchableSelect
                        v-model="filters.assigned_user_id"
                        :options="techOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.all')"
                        :label="''"
                        :compact="true"
                        @change="reload"
                    />
                </div>
                <button
                    type="button"
                    @click="clearFilters"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold text-content-secondary bg-surface-secondary hover:bg-surface-tertiary border border-border-subtle transition-colors cursor-pointer"
                >
                    {{ $t('appointments.clear_filters') }}
                </button>
            </div>

            <!-- Empty State -->
            <AppEmptyState v-if="!appointments.length" :title="$t('appointments.empty')">
                <template #actions>
                    <button
                        v-if="can('crm.appointments.manage')"
                        type="button"
                        @click="openCreate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('appointments.new') }}</span>
                    </button>
                </template>
            </AppEmptyState>

            <!-- Content Views -->
            <template v-else>
                <!-- Table View -->
                <DataTable v-if="viewMode === 'list'" :items="appointments" :columns="columns">
                    <template #cell-status="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold',
                                statusClass(item.status),
                            ]"
                        >
                            {{ $t(`appointments.status.${item.status}`) }}
                        </span>
                    </template>

                    <template #cell-scheduled_at="{ item }">
                        <div class="text-xs font-medium text-content-primary" dir="ltr">
                            {{ formatTime(item.scheduled_at) }} →
                            {{ formatTime(item.estimated_end_at) }}
                        </div>
                    </template>

                    <template #cell-customer="{ item }">
                        <div class="font-medium text-content-primary">
                            {{ item.customer?.name || '—' }}
                        </div>
                        <div
                            v-if="item.customer?.phone"
                            class="text-xs text-content-muted"
                            dir="ltr"
                        >
                            {{ item.customer.phone }}
                        </div>
                    </template>

                    <template #cell-vehicle="{ item }">
                        <SaudiPlateDisplay
                            v-if="item.vehicle?.plate_number"
                            :plate-number="item.vehicle.plate_number"
                            size="sm"
                        />
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <template #cell-bay_tech="{ item }">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                v-if="item.service_bay"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-info-soft text-info"
                            >
                                {{ item.service_bay.name }}
                            </span>
                            <span
                                v-if="item.assigned_user"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-action-primary/10 text-action-primary"
                            >
                                {{ item.assigned_user.name }}
                            </span>
                            <span
                                v-if="!item.service_bay && !item.assigned_user"
                                class="text-xs text-content-muted"
                            >
                                —
                            </span>
                        </div>
                    </template>

                    <template #cell-services="{ item }">
                        <div class="max-w-xs">
                            <p
                                v-if="item.services?.length"
                                class="text-xs text-content-primary truncate"
                            >
                                {{ item.services.map((s) => s.name).join('، ') }}
                            </p>
                            <p v-if="item.notes" class="text-[11px] text-content-muted truncate">
                                {{ item.notes }}
                            </p>
                            <span
                                v-if="!item.services?.length && !item.notes"
                                class="text-xs text-content-muted"
                            >
                                —
                            </span>
                        </div>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                            <button
                                v-if="item.status === 'scheduled' || item.status === 'confirmed'"
                                type="button"
                                @click="checkIn(item)"
                                :disabled="busy"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-success-soft text-success hover:bg-success hover:text-white disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                {{ $t('appointments.check_in') }}
                            </button>
                            <button
                                v-if="canMarkNoShow(item)"
                                type="button"
                                @click="markNoShow(item)"
                                :disabled="busy"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-warning-soft text-warning hover:bg-warning hover:text-white disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                {{ $t('appointments.no_show') }}
                            </button>
                            <button
                                v-if="
                                    item.status === 'scheduled' ||
                                    item.status === 'confirmed' ||
                                    item.status === 'checked_in'
                                "
                                type="button"
                                @click="convert(item)"
                                :disabled="busy"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-action-primary hover:bg-action-primary-hover text-white disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                {{ $t('appointments.convert') }}
                            </button>
                            <button
                                v-if="item.status !== 'cancelled' && item.status !== 'converted'"
                                type="button"
                                @click="openCancel(item)"
                                :disabled="busy"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-danger-soft text-danger hover:bg-danger hover:text-white disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                {{ $t('appointments.cancel') }}
                            </button>
                        </div>
                    </template>
                </DataTable>

                <!-- Timeline / List View (Grid Mode) -->
                <div
                    v-else
                    class="rounded-2xl border border-border-subtle bg-surface-primary shadow-2xs overflow-hidden"
                >
                    <ul class="divide-y divide-border-subtle">
                        <li
                            v-for="appt in appointments"
                            :key="appt.id"
                            class="p-4 hover:bg-surface-secondary/40 transition-colors"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold',
                                                statusClass(appt.status),
                                            ]"
                                        >
                                            {{ $t(`appointments.status.${appt.status}`) }}
                                        </span>
                                        <span
                                            class="text-xs text-content-muted font-medium"
                                            dir="ltr"
                                        >
                                            {{ formatTime(appt.scheduled_at) }} →
                                            {{ formatTime(appt.estimated_end_at) }}
                                        </span>
                                        <span
                                            v-if="appt.service_bay"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-info-soft text-info"
                                        >
                                            {{ appt.service_bay.name }}
                                        </span>
                                        <span
                                            v-if="appt.assigned_user"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-action-primary/10 text-action-primary"
                                        >
                                            {{ appt.assigned_user.name }}
                                        </span>
                                    </div>
                                    <h4 class="mt-1.5 text-sm font-bold text-content-primary">
                                        {{ appt.customer?.name || '—' }}
                                        <SaudiPlateDisplay
                                            v-if="appt.vehicle?.plate_number"
                                            :plate-number="appt.vehicle.plate_number"
                                            size="sm"
                                            class="ms-2 align-middle inline-flex"
                                        />
                                    </h4>
                                    <p
                                        v-if="appt.services?.length"
                                        class="text-xs text-content-muted mt-0.5"
                                    >
                                        {{ appt.services.map((s) => s.name).join('، ') }}
                                    </p>
                                    <p
                                        v-if="appt.notes"
                                        class="text-xs text-content-muted mt-0.5 line-clamp-1"
                                    >
                                        {{ appt.notes }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-if="
                                            appt.status === 'scheduled' ||
                                            appt.status === 'confirmed'
                                        "
                                        type="button"
                                        @click="checkIn(appt)"
                                        :disabled="busy"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-success-soft text-success hover:bg-success hover:text-white disabled:opacity-50 transition-colors cursor-pointer"
                                    >
                                        {{ $t('appointments.check_in') }}
                                    </button>
                                    <button
                                        v-if="canMarkNoShow(appt)"
                                        type="button"
                                        @click="markNoShow(appt)"
                                        :disabled="busy"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-warning-soft text-warning hover:bg-warning hover:text-white disabled:opacity-50 transition-colors cursor-pointer"
                                    >
                                        {{ $t('appointments.no_show') }}
                                    </button>
                                    <button
                                        v-if="
                                            appt.status === 'scheduled' ||
                                            appt.status === 'confirmed' ||
                                            appt.status === 'checked_in'
                                        "
                                        type="button"
                                        @click="convert(appt)"
                                        :disabled="busy"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-action-primary hover:bg-action-primary-hover text-white disabled:opacity-50 transition-colors cursor-pointer"
                                    >
                                        {{ $t('appointments.convert') }}
                                    </button>
                                    <button
                                        v-if="
                                            appt.status !== 'cancelled' &&
                                            appt.status !== 'converted'
                                        "
                                        type="button"
                                        @click="openCancel(appt)"
                                        :disabled="busy"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-danger-soft text-danger hover:bg-danger hover:text-white disabled:opacity-50 transition-colors cursor-pointer"
                                    >
                                        {{ $t('appointments.cancel') }}
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </template>
        </div>

        <!-- Create modal -->
        <BaseModal
            :show="showForm"
            size="lg"
            :title="$t('appointments.new')"
            @close="showForm = false"
        >
            <div class="space-y-4">
                <!-- Quick Vehicle/Customer Search -->
                <div class="relative">
                    <div class="relative">
                        <input
                            v-model="quickSearchQuery"
                            type="text"
                            @input="onQuickSearchInput"
                            :placeholder="$t('appointments.search_vehicle_or_customer')"
                            class="w-full px-3 py-2 ps-9 text-xs rounded-xl border border-border-subtle bg-surface-secondary text-content-primary focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition"
                        />
                        <svg
                            class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-content-muted"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <span
                            v-if="searchingQuick"
                            class="absolute end-3 top-1/2 -translate-y-1/2 text-xs text-content-muted animate-pulse"
                        >
                            ...
                        </span>
                    </div>

                    <!-- Quick search dropdown -->
                    <div
                        v-if="quickSearchResults.length > 0"
                        class="absolute z-20 start-0 end-0 mt-1 bg-surface-primary border border-border-subtle rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-border-subtle"
                    >
                        <button
                            v-for="v in quickSearchResults"
                            :key="v.id"
                            type="button"
                            @click="selectQuickVehicle(v)"
                            class="w-full text-start p-2.5 hover:bg-surface-secondary/50 flex items-center justify-between transition text-xs cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                <SaudiPlateDisplay :plate-number="v.plate_number" size="sm" />
                                <div>
                                    <div class="font-medium text-content-primary">
                                        {{
                                            [v.make?.name, v.model?.name, v.year]
                                                .filter(Boolean)
                                                .join(' ')
                                        }}
                                    </div>
                                    <div class="text-[11px] text-content-muted">
                                        {{ v.customer?.name }}
                                        {{ v.customer?.phone ? '(' + v.customer.phone + ')' : '' }}
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] text-action-primary font-bold">
                                {{ $t('common.select') }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('appointments.customer') }}
                        </label>
                        <SearchableSelect
                            v-model="form.customer_id"
                            :options="customerOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.customer_placeholder')"
                            :search-placeholder="$t('common.search')"
                            :label="''"
                            @search="onCustomerSearch"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('appointments.vehicle') }}
                        </label>
                        <SearchableSelect
                            v-model="form.vehicle_id"
                            :options="vehicleOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="
                                form.customer_id
                                    ? vehicleOptions.length
                                        ? $t('appointments.vehicle_placeholder')
                                        : $t('appointments.no_vehicles')
                                    : $t('appointments.select_customer_first')
                            "
                            :search-placeholder="$t('common.search')"
                            :disabled="!form.customer_id || vehicleOptions.length === 0"
                            :label="''"
                        />
                    </div>

                    <!-- Selected Vehicle Banner -->
                    <div
                        v-if="selectedVehicle"
                        class="col-span-1 md:col-span-2 bg-action-primary/5 border border-action-primary/20 rounded-xl p-2.5 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <SaudiPlateDisplay
                                :plate-number="selectedVehicle.plate_number"
                                size="sm"
                            />
                            <div class="text-xs">
                                <span class="font-bold text-content-primary">
                                    {{
                                        [
                                            selectedVehicle.make?.name,
                                            selectedVehicle.model?.name,
                                            selectedVehicle.year,
                                        ]
                                            .filter(Boolean)
                                            .join(' ')
                                    }}
                                </span>
                                <span v-if="selectedCustomer" class="text-content-muted ms-2">
                                    ({{ selectedCustomer.name }})
                                </span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('appointments.bay') }}
                        </label>
                        <SearchableSelect
                            v-model="form.service_bay_id"
                            :options="bayFormOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.no_bay')"
                            :label="''"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('appointments.tech') }}
                        </label>
                        <SearchableSelect
                            v-model="form.assigned_user_id"
                            :options="techFormOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.no_tech')"
                            :label="''"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('appointments.scheduled_at') }}
                        </label>
                        <input
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            class="w-full rounded-xl border border-border-subtle bg-surface-secondary text-content-primary px-3 py-2 text-sm focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('appointments.estimated_end_at') }}
                        </label>
                        <input
                            v-model="form.estimated_end_at"
                            type="datetime-local"
                            class="w-full rounded-xl border border-border-subtle bg-surface-secondary text-content-primary px-3 py-2 text-sm focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-content-muted mb-1">
                        {{ $t('appointments.services') }}
                    </label>
                    <div
                        class="max-h-36 overflow-y-auto border border-border-subtle bg-surface-secondary/50 rounded-xl p-2.5 space-y-2"
                    >
                        <div
                            v-for="s in services"
                            :key="s.id"
                            class="flex items-center justify-between gap-2 text-xs"
                        >
                            <AppToggle :value="s.id" v-model="form.service_ids" :label="s.name" />
                            <span class="text-content-muted font-medium">
                                {{ formatPrice(s.base_price) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-content-muted mb-1">
                        {{ $t('appointments.notes') }}
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-xl border border-border-subtle bg-surface-secondary text-content-primary px-3 py-2 text-sm focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-2 w-full">
                    <button
                        type="button"
                        @click="showForm = false"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="create"
                        :disabled="
                            busy ||
                            !form.customer_id ||
                            !form.vehicle_id ||
                            !form.scheduled_at ||
                            !form.estimated_end_at
                        "
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        {{ $t('common.create') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- Cancel modal -->
        <BaseModal
            :show="showCancel"
            size="sm"
            :title="$t('appointments.cancel_title')"
            @close="showCancel = false"
        >
            <textarea
                v-model="cancelReason"
                rows="3"
                :placeholder="$t('appointments.cancel_reason_placeholder')"
                class="w-full rounded-xl border border-border-subtle bg-surface-secondary text-content-primary px-3 py-2 text-sm focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary"
            ></textarea>

            <template #footer>
                <div class="flex items-center justify-end gap-2 w-full">
                    <button
                        type="button"
                        @click="showCancel = false"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="confirmCancel"
                        :disabled="busy"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-danger hover:bg-danger/90 rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        {{ $t('appointments.cancel') }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import BaseModal from '@/Components/BaseModal.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { usePermission } from '@/Composables/usePermission';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { canMarkNoShow } from '@/Utils/appointments';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    bays: { type: Array, default: () => [] },
    techs: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermission();
const { confirm } = useConfirm();
const { success, error } = useToast();
const { toEnglish } = useNumberFormat();

const viewMode = ref('list');
const busy = ref(false);
const showForm = ref(false);
const showCancel = ref(false);
const cancelReason = ref('');
const cancellingAppointment = ref(null);

const columns = computed(() => [
    { key: 'status', label: t('appointments.filter_status'), class: 'w-28' },
    { key: 'scheduled_at', label: t('appointments.scheduled_at') },
    { key: 'customer', label: t('appointments.customer') },
    { key: 'vehicle', label: t('appointments.vehicle') },
    { key: 'bay_tech', label: `${t('appointments.bay')} / ${t('appointments.tech')}` },
    { key: 'services', label: t('appointments.services') },
    { key: 'actions', label: t('common.actions'), class: 'text-center' },
]);

const form = reactive({
    customer_id: '',
    vehicle_id: '',
    service_bay_id: '',
    assigned_user_id: '',
    scheduled_at: '',
    estimated_end_at: '',
    service_ids: [],
    notes: '',
});

const customerList = ref([...props.customers]);
const searchingCustomers = ref(false);

watch(
    () => props.customers,
    (newVal) => {
        if (Array.isArray(newVal)) {
            const existingIds = new Set(customerList.value.map((c) => c.id));
            newVal.forEach((c) => {
                if (!existingIds.has(c.id)) {
                    customerList.value.push(c);
                }
            });
        }
    },
    { immediate: true }
);

const customerOptions = computed(() => {
    return customerList.value.map((c) => ({
        value: c.id,
        label: `${c.name}${c.phone ? ' (' + c.phone + ')' : ''}`,
    }));
});

const selectedCustomer = computed(() => {
    return customerList.value.find((c) => String(c.id) === String(form.customer_id)) || null;
});

const vehicleOptions = computed(() => {
    if (!selectedCustomer.value || !Array.isArray(selectedCustomer.value.vehicles)) return [];
    return selectedCustomer.value.vehicles.map((v) => {
        const makeName = v.make?.name || '';
        const modelName = v.model?.name || '';
        const carInfo = [makeName, modelName, v.year].filter(Boolean).join(' ');
        const plate = v.plate_number || '';
        return {
            value: v.id,
            label: carInfo ? `${plate} - ${carInfo}` : plate || `#${v.id}`,
            plate: plate,
            makeModel: carInfo,
            raw: v,
        };
    });
});

const selectedVehicle = computed(() => {
    if (!form.vehicle_id || !selectedCustomer.value?.vehicles) return null;
    return (
        selectedCustomer.value.vehicles.find((v) => String(v.id) === String(form.vehicle_id)) ||
        null
    );
});

watch(
    () => form.customer_id,
    (newVal) => {
        if (!newVal) {
            form.vehicle_id = '';
            return;
        }
        const cust = customerList.value.find((c) => String(c.id) === String(newVal));
        if (cust?.vehicles?.length === 1) {
            form.vehicle_id = cust.vehicles[0].id;
        } else if (!cust?.vehicles?.some((v) => String(v.id) === String(form.vehicle_id))) {
            form.vehicle_id = '';
        }
    }
);

// Quick vehicle search
const quickSearchQuery = ref('');
const quickSearchResults = ref([]);
const searchingQuick = ref(false);
let quickSearchTimer = null;

function onQuickSearchInput() {
    clearTimeout(quickSearchTimer);
    const q = quickSearchQuery.value?.trim();
    if (!q || q.length < 2) {
        quickSearchResults.value = [];
        return;
    }
    quickSearchTimer = setTimeout(async () => {
        searchingQuick.value = true;
        try {
            const res = await axios.get('/app/api/vehicles/search', { params: { q } });
            quickSearchResults.value = Array.isArray(res.data) ? res.data : [];
        } catch (e) {
            console.error('Quick vehicle search error', e);
        } finally {
            searchingQuick.value = false;
        }
    }, 300);
}

function selectQuickVehicle(v) {
    if (v.customer) {
        const existingCust = customerList.value.find((c) => c.id === v.customer.id);
        if (!existingCust) {
            customerList.value.push({
                ...v.customer,
                vehicles: [v],
            });
        } else {
            if (!Array.isArray(existingCust.vehicles)) {
                existingCust.vehicles = [];
            }
            if (!existingCust.vehicles.some((ev) => ev.id === v.id)) {
                existingCust.vehicles.push(v);
            }
        }
        form.customer_id = v.customer.id;
    }
    form.vehicle_id = v.id;
    quickSearchQuery.value = '';
    quickSearchResults.value = [];
}

async function onCustomerSearch(query) {
    if (!query || query.length < 2) return;
    searchingCustomers.value = true;
    try {
        const res = await axios.get('/app/api/customers/search', { params: { q: query } });
        if (Array.isArray(res.data)) {
            const existingIds = new Set(customerList.value.map((c) => c.id));
            res.data.forEach((c) => {
                if (!existingIds.has(c.id)) {
                    customerList.value.push(c);
                }
            });
        }
    } catch (e) {
        console.error('Customer search error', e);
    } finally {
        searchingCustomers.value = false;
    }
}

const filters = reactive({ ...props.filters });

const statusOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.statuses.map((s) => ({ value: s, label: t(`appointments.status.${s}`) })),
]);

const bayOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.bays.map((b) => ({ value: b.id, label: b.name })),
]);

const bayFormOptions = computed(() => [
    { value: '', label: t('appointments.no_bay') },
    ...props.bays.map((b) => ({ value: b.id, label: b.name })),
]);

const techOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.techs.map((t) => ({ value: t.id, label: t.name })),
]);

const techFormOptions = computed(() => [
    { value: '', label: t('appointments.no_tech') },
    ...props.techs.map((t) => ({ value: t.id, label: t.name })),
]);

function reload() {
    const params = {};
    if (filters.date) params.date = filters.date;
    if (filters.status) params.status = filters.status;
    if (filters.service_bay_id) params.service_bay_id = filters.service_bay_id;
    if (filters.assigned_user_id) params.assigned_user_id = filters.assigned_user_id;
    router.get(route('appointments.index'), params, { preserveState: true });
}

function clearFilters() {
    filters.date = '';
    filters.status = '';
    filters.service_bay_id = '';
    filters.assigned_user_id = '';
    reload();
}

function openCreate() {
    form.customer_id = '';
    form.vehicle_id = '';
    form.service_bay_id = '';
    form.assigned_user_id = '';
    form.scheduled_at = '';
    form.estimated_end_at = '';
    form.service_ids = [];
    form.notes = '';
    quickSearchQuery.value = '';
    quickSearchResults.value = [];
    showForm.value = true;
}

async function create() {
    busy.value = true;
    try {
        await axios.post(route('appointments.store'), {
            ...form,
            customer_id: Number(form.customer_id),
            vehicle_id: Number(form.vehicle_id),
            service_bay_id: form.service_bay_id ? Number(form.service_bay_id) : null,
            assigned_user_id: form.assigned_user_id ? Number(form.assigned_user_id) : null,
            service_ids: form.service_ids.map(Number),
        });
        showForm.value = false;
        success(t('common.saved_success'));
        router.reload();
    } catch (e) {
        if (e.response?.data?.code) {
            error(
                t(`messages.${e.response.data.code}`) +
                    (e.response.data.error ? ': ' + e.response.data.error : '')
            );
        } else {
            error(e.response?.data?.message || e.message);
        }
    } finally {
        busy.value = false;
    }
}

async function checkIn(appt) {
    busy.value = true;
    try {
        await axios.post(route('appointments.check-in', { appointment: appt.id }));
        success(t('common.updated_success'));
        router.reload();
    } catch (e) {
        error(e.response?.data?.error || e.message);
    } finally {
        busy.value = false;
    }
}

async function markNoShow(appt) {
    const confirmed = await confirm({
        title: t('appointments.no_show'),
        message: t('appointments.confirm_no_show'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });
    if (!confirmed) return;

    busy.value = true;
    try {
        await axios.post(route('appointments.no-show', { appointment: appt.id }));
        success(t('common.updated_success'));
        router.reload();
    } catch (e) {
        if (e.response?.data?.code) {
            error(
                t(`messages.${e.response.data.code}`) +
                    (e.response.data.error ? ': ' + e.response.data.error : '')
            );
        } else {
            error(e.message);
        }
    } finally {
        busy.value = false;
    }
}

function openCancel(appt) {
    cancellingAppointment.value = appt;
    cancelReason.value = '';
    showCancel.value = true;
}

async function confirmCancel() {
    if (!cancellingAppointment.value) return;
    busy.value = true;
    try {
        await axios.post(
            route('appointments.cancel', { appointment: cancellingAppointment.value.id }),
            {
                reason: cancelReason.value,
            }
        );
        showCancel.value = false;
        success(t('common.updated_success'));
        router.reload();
    } catch (e) {
        error(e.response?.data?.error || e.message);
    } finally {
        busy.value = false;
    }
}

async function convert(appt) {
    const confirmed = await confirm({
        title: t('appointments.convert'),
        message: t('appointments.confirm_convert'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: 'primary',
    });
    if (!confirmed) return;

    busy.value = true;
    try {
        const res = await axios.post(route('appointments.convert', { appointment: appt.id }));
        const woId = res.data?.payload?.work_order_id;
        if (woId) {
            router.visit(route('work-orders.show', { workOrder: woId }));
        } else {
            router.reload();
        }
    } catch (e) {
        const code = e.response?.data?.code;
        error(code ? t(`messages.${code}`) : e.message);
    } finally {
        busy.value = false;
    }
}

function statusClass(status) {
    const map = {
        scheduled: 'bg-info-soft text-info',
        confirmed: 'bg-action-primary/10 text-action-primary',
        checked_in: 'bg-success-soft text-success',
        no_show: 'bg-warning-soft text-warning',
        cancelled: 'bg-danger-soft text-danger',
        converted: 'bg-surface-tertiary text-content-secondary',
    };
    return map[status] || 'bg-surface-tertiary text-content-secondary';
}

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    try {
        return new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' }).format(d);
    } catch (e) {
        return iso;
    }
}

function formatPrice(value) {
    if (value === null || value === undefined) return '';
    try {
        return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2 }).format(value);
    } catch (e) {
        return String(value);
    }
}
</script>

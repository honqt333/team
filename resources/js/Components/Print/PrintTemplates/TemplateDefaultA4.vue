<template>
    <div 
        class="bg-white w-[210mm] min-h-[297mm] p-[10mm] shadow-lg print:shadow-none print:m-0 print:p-0 relative flex flex-col"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Header: Logo & Center Info -->
        <div class="flex justify-between items-start pb-4 mb-6 border-b-2" :class="isModern ? 'border-dashed border-gray-300' : 'border-gray-800'">
            <div class="w-1/3">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ centerData.name || 'مركز اور كارز' }}</h1>
                <p v-if="centerData.tax_number" class="text-sm text-gray-600">
                    {{ isRtl ? 'الرقم الضريبي:' : 'Tax Number:' }} {{ centerData.tax_number }}
                </p>
                <p v-if="centerData.cr_number" class="text-sm text-gray-600">
                    {{ isRtl ? 'س.ت:' : 'C.R:' }} {{ centerData.cr_number }}
                </p>
                <p v-if="centerData.phone" class="text-sm text-gray-600 mt-2">{{ centerData.phone }}</p>
            </div>
            
            <div class="w-1/3 flex flex-col items-center justify-center">
                <div v-if="visualSettings.show_logo" class="mb-3">
                    <img v-if="centerData.logo" :src="centerData.logo" alt="Logo" class="w-24 h-24 object-contain" />
                    <div v-else class="w-24 h-24 bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-400 text-xs font-bold">
                        [Logo]
                    </div>
                </div>
                <h2 
                    class="text-xl font-bold text-center min-w-[150px] transition-all duration-300"
                    :class="isModern ? 'bg-slate-900 text-white px-6 py-1.5 rounded-full shadow-sm' : 'bg-gray-100 border border-gray-200 px-4 py-1 rounded shadow-sm text-gray-900'"
                    :style="isModern ? { backgroundColor: primaryColor } : {}"
                >
                    {{ isRtl ? (documentSettings.title_ar || getDocTypeTitle(documentType)) : (documentSettings.title_en || getDocTypeTitle(documentType)) }}
                </h2>
            </div>
            
            <div class="w-1/3 flex flex-col" :class="isRtl ? 'items-end' : 'items-start'">
                <div v-if="visualSettings.show_qr_code" class="w-20 h-20 bg-gray-100 border border-gray-300 p-1 mb-2">
                    <img v-if="data.qr_code_url" :src="data.qr_code_url" alt="QR" class="w-full h-full object-contain" />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">[QR]</div>
                </div>
                <div class="text-sm font-medium" :class="isRtl ? 'text-left' : 'text-right'">
                    <p class="text-gray-800 font-bold" :class="isRtl ? 'text-right' : 'text-left'">
                        {{ isRtl ? 'الرقم:' : 'Number:' }} <span class="font-mono">{{ data.code || 'T202620001' }}</span>
                    </p>
                    <p class="text-gray-600 mt-0.5" :class="isRtl ? 'text-right' : 'text-left'">
                        {{ isRtl ? 'التاريخ:' : 'Date:' }} {{ formatDate(data.created_at || new Date()) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Client & Vehicle Details Box -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div 
                class="border p-3 relative pt-4 transition-all duration-300"
                :class="isModern ? 'border-0 bg-slate-50/50 rounded-2xl' : 'border-gray-200 rounded-lg'"
            >
                <h3 
                    class="absolute top-0 text-xs font-bold px-3 py-1 transition-all duration-300"
                    :class="[
                        isModern 
                            ? 'bg-slate-900 text-white border-0' 
                            : 'bg-gray-100 text-gray-800 border-b border-gray-200',
                        isRtl 
                            ? 'right-0 border-l rounded-bl-lg' + (isModern ? ' rounded-tr-2xl' : ' rounded-tr-lg')
                            : 'left-0 border-r rounded-br-lg' + (isModern ? ' rounded-tl-2xl' : ' rounded-tl-lg')
                    ]"
                    :style="isModern ? { backgroundColor: primaryColor } : {}"
                >
                    {{ isRtl ? 'بيانات العميل' : 'Customer Details' }}
                </h3>
                <div class="grid grid-cols-2 gap-2 text-sm text-gray-700 mt-2">
                    <p><span class="text-gray-500">{{ isRtl ? 'الاسم:' : 'Name:' }}</span> {{ data.customer?.name || (isRtl ? 'احمد الزهراني' : 'Ahmad Alzahrani') }}</p>
                    <p><span class="text-gray-500">{{ isRtl ? 'الجوال:' : 'Phone:' }}</span> <span dir="ltr">{{ data.customer?.phone || '+966 555 785 658' }}</span></p>
                    <p v-if="documentSettings.show_customer_address !== false && data.customer?.address" class="col-span-2"><span class="text-gray-500">{{ isRtl ? 'العنوان:' : 'Address:' }}</span> {{ data.customer.address }}</p>
                    <p v-if="data.customer?.tax_number" class="col-span-2"><span class="text-gray-500">{{ isRtl ? 'الرقم الضريبي:' : 'Tax ID:' }}</span> {{ data.customer.tax_number }}</p>
                </div>
            </div>
            
            <div 
                class="border p-3 relative pt-4 transition-all duration-300"
                :class="isModern ? 'border-0 bg-slate-50/50 rounded-2xl' : 'border-gray-200 rounded-lg'"
            >
                <h3 
                    class="absolute top-0 text-xs font-bold px-3 py-1 transition-all duration-300"
                    :class="[
                        isModern 
                            ? 'bg-slate-900 text-white border-0' 
                            : 'bg-gray-100 text-gray-800 border-b border-gray-200',
                        isRtl 
                            ? 'right-0 border-l rounded-bl-lg' + (isModern ? ' rounded-tr-2xl' : ' rounded-tr-lg')
                            : 'left-0 border-r rounded-br-lg' + (isModern ? ' rounded-tl-2xl' : ' rounded-tl-lg')
                    ]"
                    :style="isModern ? { backgroundColor: primaryColor } : {}"
                >
                    {{ isRtl ? 'بيانات المركبة' : 'Vehicle Details' }}
                </h3>
                <div class="grid grid-cols-2 gap-2 text-sm text-gray-700 mt-2 items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">{{ isRtl ? 'الماركة:' : 'Make:' }}</span>
                        <span class="font-bold flex items-center gap-1.5">
                            <img v-if="data.vehicle?.make_logo" :src="data.vehicle.make_logo" alt="Make Logo" class="w-5 h-5 object-contain" />
                            {{ data.vehicle?.make || (isRtl ? 'مرسيدس' : 'Mercedes') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">{{ isRtl ? 'اللوحة:' : 'Plate:' }}</span>
                        <SaudiPlateDisplay v-if="data.vehicle?.plate" :plate-number="data.vehicle.plate" size="sm" />
                        <span v-else class="font-bold">-</span>
                    </div>
                    <p v-if="data.vehicle?.color"><span class="text-gray-500">{{ isRtl ? 'اللون:' : 'Color:' }}</span> {{ getLocalizedColor(data.vehicle.color, isRtl) }}</p>
                </div>
            </div>
        </div>

        <!-- Document Content Body -->
        <div class="mb-6">
            <!-- Scenario 1: Receipt -->
            <div v-if="documentType === 'receipt'" class="bg-gray-50/50 p-6 rounded-lg border border-gray-200 leading-loose text-base text-gray-800">
                <p>{{ isRtl ? 'استلمنا من السيد/ة:' : 'Received from Mr./Ms.:' }} <strong class="underline underline-offset-4">{{ data.customer?.name || (isRtl ? 'احمد الزهراني' : 'Ahmad Alzahrani') }}</strong></p>
                <p class="mt-2">{{ isRtl ? 'مبلغاً وقدره:' : 'An amount of:' }} <strong class="font-mono">{{ formatCurrency(data.amount || 250) }}</strong></p>
                <p class="mt-2 text-gray-600">
                    {{ isRtl ? 'وذلك لقاء:' : 'For:' }} 
                    <span>{{ data.notes || (isRtl ? 'دفعة مقدمة لصيانة السيارة المذكورة أعلاه وبدء أعمال الفحص والبرمجة.' : 'Down payment for maintenance of the above-mentioned vehicle and starting inspection and programming.') }}</span>
                </p>
            </div>

            <!-- Scenario 2: Invoice (Separated Services and Parts) -->
            <div v-else-if="['invoice', 'proforma_invoice', 'quotation', 'parts_invoice'].includes(documentType)" class="space-y-6">

                <!-- ── Services Table ── -->
                <div v-if="services.length > 0">
                    <h3 class="font-bold text-gray-800 mb-2">{{ isRtl ? 'أجور الخدمات واليد العاملة' : 'Labor & Services' }}</h3>
                    <table class="w-full text-xs border-collapse" :class="isRtl ? 'text-right' : 'text-left'">
                        <thead>
                            <tr class="text-white" :style="{ backgroundColor: isModern ? primaryColor : '#1f2937' }">
                                <th class="p-2 border border-gray-700 w-7 text-center">#</th>
                                <th class="p-2 border border-gray-700">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                                <th class="p-2 border border-gray-700 w-20 text-center">{{ isRtl ? 'السعر' : 'Price' }}</th>
                                <th class="p-2 border border-gray-700 w-20 text-center">{{ isRtl ? 'الخصم' : 'Discount' }}</th>
                                <th class="p-2 border border-gray-700 w-20 text-center">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                                <th v-if="hasServicesVat" class="p-2 border border-gray-700 w-20 text-center">VAT</th>
                                <th class="p-2 border border-gray-700 w-22 text-center">{{ isRtl ? 'المجموع' : 'Total' }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            <tr v-for="(item, index) in services" :key="'srv-' + index" class="border-b border-gray-100 hover:bg-gray-50/50">
                                <td class="p-2 border border-gray-200 text-center text-gray-500">{{ index + 1 }}</td>
                                <td class="p-2 border border-gray-200 font-medium">
                                    <span>{{ item.service_name || item.description }}</span>
                                    <span v-if="item.description && item.service_name" class="block text-[10px] text-gray-400 mt-0.5 font-normal">{{ item.description }}</span>
                                </td>
                                <!-- Unit Price -->
                                <td class="p-2 border border-gray-200 text-center font-mono">
                                    {{ formatRawPrice(item.unit_price || 0) }}
                                </td>
                                <!-- Discount -->
                                <td class="p-2 border border-gray-200 text-center font-mono text-red-600">
                                    {{ (item.discount || 0) > 0 ? '- ' + formatRawPrice(item.discount) : '-' }}
                                </td>
                                <!-- Amount (before VAT) -->
                                <td class="p-2 border border-gray-200 text-center font-mono">
                                    {{ formatRawPrice(srvLineExclTax(item)) }}
                                </td>
                                <!-- VAT -->
                                <td v-if="hasServicesVat" class="p-2 border border-gray-200 text-center font-mono text-gray-500">
                                    {{ formatRawPrice(srvLineVat(item)) }}
                                </td>
                                <!-- Total incl. VAT -->
                                <td class="p-2 border border-gray-200 text-center font-bold font-mono">
                                    {{ formatRawPrice(srvLineTotal(item)) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-50 text-gray-700 border-t-2 border-gray-300">
                                <td :colspan="hasServicesVat ? 6 : 5" class="p-2 border border-gray-200 text-center">{{ isRtl ? 'إجمالي الخدمات' : 'Services Total' }}</td>
                                <td class="p-2 border border-gray-200 text-center font-mono font-bold">
                                    {{ formatRawPrice(services.reduce((s, i) => s + srvLineTotal(i), 0)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ── Parts Table ── -->
                <div v-if="parts.length > 0">
                    <h3 class="font-bold text-gray-800 mb-2">{{ isRtl ? 'قطع الغيار المستبدلة' : 'Replaced Spare Parts' }}</h3>
                    <table class="w-full text-xs border-collapse" :class="isRtl ? 'text-right' : 'text-left'">
                        <thead>
                            <tr class="text-white" :style="{ backgroundColor: isModern ? primaryColor : '#1f2937' }">
                                <th class="p-2 border border-gray-700 w-7 text-center">#</th>
                                <th class="p-2 border border-gray-700">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                                <th class="p-2 border border-gray-700 w-20 text-center">{{ isRtl ? 'السعر' : 'Price' }}</th>
                                <th class="p-2 border border-gray-700 w-20 text-center">{{ isRtl ? 'الخصم' : 'Discount' }}</th>
                                <th class="p-2 border border-gray-700 w-14 text-center">{{ isRtl ? 'الكمية' : 'Qty' }}</th>
                                <th class="p-2 border border-gray-700 w-22 text-center">{{ isRtl ? 'المجموع الفرعي' : 'Subtotal' }}</th>
                                <th v-if="hasPartsVat" class="p-2 border border-gray-700 w-20 text-center">VAT</th>
                                <th class="p-2 border border-gray-700 w-22 text-center">{{ isRtl ? 'المجموع' : 'Total' }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            <tr v-for="(item, index) in parts" :key="'part-' + index" class="border-b border-gray-100 hover:bg-gray-50/50">
                                <td class="p-2 border border-gray-200 text-center text-gray-500">{{ index + 1 }}</td>
                                <td class="p-2 border border-gray-200 font-medium">
                                    <span>{{ item.service_name || item.description }}</span>
                                    <span v-if="item.description && item.service_name" class="block text-[10px] text-gray-400 mt-0.5 font-normal">{{ item.description }}</span>
                                </td>
                                <!-- Unit Price -->
                                <td class="p-2 border border-gray-200 text-center font-mono">
                                    {{ formatRawPrice(item.unit_price || 0) }}
                                </td>
                                <!-- Discount -->
                                <td class="p-2 border border-gray-200 text-center font-mono text-red-600">
                                    {{ (item.discount || 0) > 0 ? '- ' + formatRawPrice(item.discount) : '-' }}
                                </td>
                                <!-- Qty -->
                                <td class="p-2 border border-gray-200 text-center font-mono">
                                    {{ item.qty || 1 }}
                                </td>
                                <!-- Subtotal (excl. VAT) -->
                                <td class="p-2 border border-gray-200 text-center font-mono">
                                    {{ formatRawPrice(partLineExclTax(item)) }}
                                </td>
                                <!-- VAT -->
                                <td v-if="hasPartsVat" class="p-2 border border-gray-200 text-center font-mono text-gray-500">
                                    {{ formatRawPrice(partLineVat(item)) }}
                                </td>
                                <!-- Total incl. VAT -->
                                <td class="p-2 border border-gray-200 text-center font-bold font-mono">
                                    {{ formatRawPrice(partLineTotal(item)) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-50 text-gray-700 border-t-2 border-gray-300">
                                <td :colspan="hasPartsVat ? 7 : 6" class="p-2 border border-gray-200 text-center">{{ isRtl ? 'إجمالي قطع الغيار' : 'Parts Total' }}</td>
                                <td class="p-2 border border-gray-200 text-center font-mono font-bold">
                                    {{ formatRawPrice(parts.reduce((s, i) => s + partLineTotal(i), 0)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>


            <!-- Scenario 3: Vehicle Condition Report -->
            <div v-else-if="documentType === 'condition_report'" class="space-y-6">
                <!-- Entry Info (Odometer, Fuel, Date) -->
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div class="bg-gray-50/50 p-3 rounded-xl border border-gray-100 flex flex-col">
                        <span class="text-xs text-gray-500 mb-0.5">{{ isRtl ? 'تاريخ الدخول' : 'Entry Date' }}</span>
                        <span class="font-bold text-gray-800 text-sm">{{ formatDate(data.entry_date || data.created_at) }}</span>
                    </div>
                    <div class="bg-gray-50/50 p-3 rounded-xl border border-gray-100 flex flex-col">
                        <span class="text-xs text-gray-500 mb-0.5">{{ isRtl ? 'قراءة العداد' : 'Odometer' }}</span>
                        <span class="font-bold text-gray-800 text-sm" dir="ltr">{{ data.mileage ? Number(data.mileage).toLocaleString() + (isRtl ? ' كم' : ' km') : '-' }}</span>
                    </div>
                    <div class="bg-gray-50/50 p-3 rounded-xl border border-gray-100 flex flex-col">
                        <span class="text-xs text-gray-500 mb-0.5">{{ isRtl ? 'مستوى الوقود' : 'Fuel Level' }}</span>
                        <span class="font-bold text-gray-800 text-sm">{{ data.fuel_level ? data.fuel_level + '%' : '-' }}</span>
                    </div>
                </div>

                <!-- Vehicle Diagram with Damage Marks -->
                <div class="mb-6">
                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                        <svg viewBox="0 0 400 300" class="w-full h-auto max-w-lg mx-auto">
                            <image href="/images/vehicle-diagram.png" x="0" y="0" width="400" height="300" preserveAspectRatio="xMidYMid meet" />
                            <g v-for="(mark, index) in damageMarks" :key="index">
                                <circle :cx="mark.x" :cy="mark.y" r="12" :fill="getColorValue(mark.color)" />
                                <text :x="mark.x" :y="mark.y + 4" text-anchor="middle" class="text-xs font-bold fill-white">{{ index + 1 }}</text>
                            </g>
                        </svg>

                        <!-- Legend -->
                        <div class="flex items-center justify-center gap-6 mt-4 text-sm border-t pt-4">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full bg-red-500"></span>
                                <span class="text-gray-600">{{ isRtl ? 'تلفيات / صدمات' : 'Damages / Dents' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full bg-blue-500"></span>
                                <span class="text-gray-600">{{ isRtl ? 'خدوش / حكات' : 'Scratches / Scuffs' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full bg-gray-500"></span>
                                <span class="text-gray-600">{{ isRtl ? 'ملاحظات عامة' : 'General Notes' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Damage Marks List -->
                <div v-if="damageMarks.length > 0" class="mb-6">
                    <h4 class="font-bold text-gray-855 mb-2">{{ isRtl ? 'تفاصيل حالة المركبة:' : 'Vehicle Condition Details:' }}</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div v-for="(mark, index) in damageMarks" :key="index" class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl">
                            <span 
                                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                :style="{ backgroundColor: getColorValue(mark.color) }"
                            >{{ index + 1 }}</span>
                            <span class="text-gray-700 font-medium">{{ mark.description || (isRtl ? 'بدون تفاصيل' : 'No details') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenario 4: Work Order / Standard Items Table -->
            <div v-else class="space-y-4">

                <!-- ── Services Table (with inline status + dates for work_order) ── -->
                <div>
                    <h3 class="font-bold text-gray-800 mb-2 flex items-center gap-2">
                        {{ documentType === 'work_order' ? (isRtl ? 'الخدمات المطلوبة' : 'Requested Services') : (isRtl ? 'أجور الخدمات' : 'Labor & Services') }}
                    </h3>
                    <table class="w-full text-sm border-collapse" :class="isRtl ? 'text-right' : 'text-left'">
                        <thead>
                            <tr class="text-white" :class="isModern ? '' : 'bg-gray-800'" :style="{ backgroundColor: isModern ? primaryColor : '#1f2937' }">
                                <th class="p-2 border border-gray-700 w-8 text-center">#</th>
                                <th class="p-2 border border-gray-700">{{ isRtl ? 'وصف الخدمة' : 'Service Description' }}</th>
                                <!-- Status / Dates columns — work_order only -->
                                <template v-if="documentType === 'work_order'">
                                    <th class="p-2 border border-gray-700 w-24 text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                    <th class="p-2 border border-gray-700 w-24 text-center">{{ isRtl ? 'البداية' : 'Start' }}</th>
                                    <th class="p-2 border border-gray-700 w-24 text-center">{{ isRtl ? 'الاستحقاق' : 'Due' }}</th>
                                </template>
                                <th v-if="showPricingColumns" class="p-2 border border-gray-700 w-28 text-center">{{ isRtl ? 'المجموع' : 'Total' }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            <tr v-for="(item, index) in data.items || dummyItems" :key="index" class="border-b border-gray-200">
                                <!-- # -->
                                <td class="p-2 border border-gray-200 text-center text-gray-500">{{ index + 1 }}</td>
                                <!-- Description -->
                                <td class="p-2 border border-gray-200 font-medium">
                                    <span>{{ item.service_name || item.description }}</span>
                                    <span v-if="item.description && item.service_name" class="block text-xs text-gray-500 mt-0.5 font-normal">{{ item.description }}</span>
                                    <!-- All technicians, one per line -->
                                    <template v-if="Array.isArray(item.technicians) && item.technicians.length">
                                        <span
                                            v-for="(tech, ti) in item.technicians"
                                            :key="ti"
                                            class="flex items-center gap-1 text-[10px] text-indigo-600 font-semibold mt-0.5"
                                        >
                                            <svg class="w-2.5 h-2.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                            {{ tech }}
                                        </span>
                                    </template>
                                    <!-- Backwards compat: single technician string -->
                                    <span v-else-if="item.technician" class="flex items-center gap-1 text-[10px] text-indigo-600 font-semibold mt-0.5">
                                        <svg class="w-2.5 h-2.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        {{ item.technician }}
                                    </span>
                                </td>
                                <!-- Status badge per item -->
                                <template v-if="documentType === 'work_order'">
                                    <td class="p-2 border border-gray-200 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap"
                                            :class="{
                                                'bg-blue-100 text-blue-700':    item.status === 'in_progress',
                                                'bg-emerald-100 text-emerald-700': item.status === 'completed',
                                                'bg-amber-100 text-amber-700':  item.status === 'pending' || item.status === 'on_hold',
                                                'bg-red-100 text-red-700':      item.status === 'cancelled',
                                                'bg-gray-100 text-gray-600':    !item.status || item.status === 'open',
                                            }"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                :class="{
                                                    'bg-blue-500':    item.status === 'in_progress',
                                                    'bg-emerald-500': item.status === 'completed',
                                                    'bg-amber-500':   item.status === 'pending' || item.status === 'on_hold',
                                                    'bg-red-500':     item.status === 'cancelled',
                                                    'bg-gray-400':    !item.status || item.status === 'open',
                                                }"
                                            ></span>
                                            {{ getStatusLabel(item.status) }}
                                        </span>
                                    </td>
                                    <!-- Start Date -->
                                    <td class="p-2 border border-gray-200 text-center font-mono text-xs text-gray-600">
                                        {{ item.started_at ? formatDate(item.started_at) : '-' }}
                                    </td>
                                    <!-- Due Date -->
                                    <td class="p-2 border border-gray-200 text-center font-mono text-xs"
                                        :class="item.due_date && new Date(item.due_date) < new Date() && item.status !== 'completed' ? 'text-red-600 font-bold' : 'text-gray-600'">
                                        {{ item.due_date ? formatDate(item.due_date) : '-' }}
                                    </td>
                                </template>
                                <!-- Price (non-work_order) -->
                                <td v-if="showPricingColumns" class="p-2 border border-gray-200 text-center font-bold font-mono">
                                    {{ formatRawPrice((item.unit_price * (item.qty || 1)) - (item.discount || 0)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Customer Complaint + Initial Assessment (work_order only) ── -->
                <div v-if="documentType === 'work_order' && (data.customer_complaint || data.initial_assessment)"
                    class="grid gap-3"
                    :class="data.customer_complaint && data.initial_assessment ? 'grid-cols-2' : 'grid-cols-1'"
                >
                    <!-- Complaint -->
                    <div v-if="data.customer_complaint" class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                        <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            {{ isRtl ? 'شكوى العميل' : 'Customer Complaint' }}
                        </p>
                        <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap">{{ data.customer_complaint }}</p>
                    </div>
                    <!-- Assessment -->
                    <div v-if="data.initial_assessment" class="rounded-xl border border-blue-200 bg-blue-50/60 p-3">
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 000-2h-3z" clip-rule="evenodd"/></svg>
                            {{ isRtl ? 'التقييم الأولي' : 'Initial Assessment' }}
                        </p>
                        <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap">{{ data.initial_assessment }}</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Totals Block & Summary (For pricing documents) -->
        <div v-if="showPricingColumns && documentType !== 'receipt'" class="flex justify-between items-start mb-12">
            <div class="w-1/2 pr-4 text-sm text-gray-500">
                <div v-if="documentSettings.print_terms && (documentSettings.terms?.length > 0 || dummyTerms.length > 0)">
                    <h4 class="text-xs font-bold text-gray-700 mb-1">{{ isRtl ? 'الشروط والأحكام:' : 'Terms & Conditions:' }}</h4>
                    <ol class="list-decimal list-inside text-[10px] text-gray-500 space-y-1 leading-normal pr-2">
                        <li v-for="(term, idx) in documentSettings.terms?.length > 0 ? documentSettings.terms : dummyTerms" :key="idx">
                            {{ isRtl ? (term.text_ar || term) : (term.text_en || term.text_ar || term) }}
                        </li>
                    </ol>
                </div>
            </div>
            
            <div class="w-1/2 lg:w-1/3">
                <table class="w-full text-sm">
                    <tr class="text-gray-500 border-b border-gray-200">
                        <td class="py-1 px-2">{{ isRtl ? 'المجموع الفرعي:' : 'Subtotal:' }}</td>
                        <td class="py-1 px-2 text-left font-mono">{{ formatCurrency(totals.subtotal) }}</td>
                    </tr>
                    <tr v-if="totals.discount > 0" class="text-red-500 border-b border-gray-200">
                        <td class="py-1 px-2">{{ isRtl ? 'الخصم الإجمالي:' : 'Total Discount:' }}</td>
                        <td class="py-1 px-2 text-left font-mono">-{{ formatCurrency(totals.discount) }}</td>
                    </tr>
                    <tr v-if="totals.vat > 0" class="text-gray-500 border-b border-gray-200">
                        <td class="py-1 px-2">{{ isRtl ? 'الضريبة (VAT):' : 'VAT:' }}</td>
                        <td class="py-1 px-2 text-left font-mono">{{ formatCurrency(totals.vat) }}</td>
                    </tr>
                    <tr 
                        class="font-bold text-lg border-b-2 transition-all duration-300"
                        :class="isModern ? 'bg-slate-50 border-slate-900' : 'bg-gray-100 border-gray-800'"
                        :style="isModern ? { borderBottomColor: primaryColor } : {}"
                    >
                        <td class="py-2 px-2 text-gray-900">{{ isRtl ? 'المبلغ الإجمالي:' : 'Total Amount:' }}</td>
                        <td class="py-2 px-2 text-left text-gray-900 font-mono">{{ formatCurrency(totals.total) }}</td>
                    </tr>
                </table>
                <div v-if="centerData.iban && documentSettings.show_iban" class="bg-gray-50 p-2 rounded border border-gray-200 mt-2 text-[9px] text-gray-500 leading-normal font-mono">
                    <span class="block font-bold text-gray-600">{{ isRtl ? 'IBAN للسداد:' : 'IBAN for Payment:' }}</span>
                    {{ centerData.iban }}
                </div>
            </div>
        </div>
        
        <!-- For non-pricing documents, print terms in full width -->
        <div v-else-if="documentSettings.print_terms && (documentSettings.terms?.length > 0 || dummyTerms.length > 0)" class="mb-12">
            <h4 class="text-xs font-bold text-gray-700 mb-2">{{ isRtl ? 'الشروط والأحكام:' : 'Terms & Conditions:' }}</h4>
            <ol class="list-decimal list-inside text-xs text-gray-500 space-y-1.5 leading-relaxed pr-2">
                <li v-for="(term, idx) in documentSettings.terms?.length > 0 ? documentSettings.terms : dummyTerms" :key="idx">
                    {{ isRtl ? (term.text_ar || term) : (term.text_en || term.text_ar || term) }}
                </li>
            </ol>
        </div>

        <!-- Signatures & Official Stamp Grid -->
        <div class="mt-auto pt-8 border-t-2 border-gray-800">
            <div class="grid gap-8 text-center text-sm font-bold text-gray-800 mb-8 relative" :class="getSignatureGridClass(documentSettings.signatures?.length || 2)">
                
                <!-- Official Stamp positioned absolutely over signatures grid -->
                <div v-if="visualSettings.show_stamp" class="absolute inset-0 flex items-center justify-center opacity-80 pointer-events-none -rotate-12 z-10 select-none">
                    <img v-if="centerData.stamp_url || visualSettings.stamp_url" :src="centerData.stamp_url || visualSettings.stamp_url" class="w-28 h-28 object-contain" />
                    <!-- Fallback premium stamp design in SVG if image not provided -->
                    <svg v-else class="w-28 h-28 text-emerald-600/80" fill="none" viewBox="0 0 100 100" stroke="currentColor">
                        <circle cx="50" cy="50" r="45" stroke-width="2.5" stroke-dasharray="3 3"/>
                        <circle cx="50" cy="50" r="38" stroke-width="1.5"/>
                        <text x="50" y="38" font-size="6" font-weight="bold" fill="currentColor" text-anchor="middle" font-family="sans-serif">خدمة برو</text>
                        <text x="50" y="48" font-size="7" font-weight="black" fill="currentColor" text-anchor="middle" font-family="sans-serif">APPROVED</text>
                        <text x="50" y="58" font-size="7" font-weight="black" fill="currentColor" text-anchor="middle" font-family="sans-serif">مـعـتـمـد</text>
                        <path d="M25 68 Q50 78 75 68" stroke-width="1.5"/>
                        <text x="50" y="76" font-size="5" fill="currentColor" text-anchor="middle" font-family="sans-serif">KHIDMA PRO</text>
                    </svg>
                </div>

                <!-- Signature roles -->
                <div v-for="(sig, index) in documentSettings.signatures?.length > 0 ? documentSettings.signatures : defaultSignatures" :key="index" class="flex flex-col items-center">
                    <p class="mb-4">{{ isRtl ? sig.name_ar : (sig.name_en || sig.name_ar) }}</p>
                    <div v-if="isClientSignature(isRtl ? sig.name_ar : (sig.name_en || sig.name_ar)) && (data.reception_signature || data.delivery_signature)" class="h-16 flex items-center justify-center mb-2">
                        <img :src="'/storage/' + (data.reception_signature || data.delivery_signature)" class="max-h-full max-w-[120px] object-contain" />
                    </div>
                    <div v-else class="h-12 w-1/2 border-b border-gray-400 mb-6"></div>
                </div>
            </div>
            
            <!-- Footer Text -->
            <div v-if="visualSettings.footer_text" class="text-center text-[10px] text-gray-400 leading-normal border-t border-gray-100 pt-2 mt-4">
                {{ visualSettings.footer_text }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    documentType: {
        type: String,
        default: 'invoice'
    },
    data: {
        type: Object,
        default: () => ({})
    },
    centerData: {
        type: Object,
        default: () => ({})
    },
    documentSettings: {
        type: Object,
        default: () => ({})
    },
    visualSettings: {
        type: Object,
        default: () => ({})
    },
    previewMode: {
        type: Boolean,
        default: false
    }
});

// Decide if we show pricing columns (e.g., hide on work_order, checklist)
const showPricingColumns = computed(() => {
    const hiddenTypes = ['work_order', 'checklist', 'job_card', 'condition_report'];
    return !hiddenTypes.includes(props.documentType);
});

// Status label map
function getStatusLabel(status) {
    const labelsAr = {
        open: 'مفتوح',
        in_progress: 'قيد التنفيذ',
        pending: 'معلق',
        on_hold: 'متوقف',
        completed: 'مكتمل',
        closed: 'مغلق',
        cancelled: 'ملغي',
    };
    const labelsEn = {
        open: 'Open',
        in_progress: 'In Progress',
        pending: 'Pending',
        on_hold: 'On Hold',
        completed: 'Completed',
        closed: 'Closed',
        cancelled: 'Cancelled',
    };
    if (!status) return isRtl.value ? 'مفتوح' : 'Open';
    return isRtl.value ? (labelsAr[status] || status) : (labelsEn[status] || status);
}

// Is due date in the past?
const isDueDatePast = computed(() => {
    if (!props.data.expected_end_date) return false;
    const due = new Date(props.data.expected_end_date);
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    return due < now;
});

// ── Invoice line helpers ──────────────────────────────────────────
// Services (qty is always 1 for labor lines)
function srvLineExclTax(item) {
    const base = (item.unit_price || 0) * 1;
    const disc = item.discount || 0;
    return Math.max(base - disc, 0);
}
function srvLineVat(item) {
    if (props.data.tax_enabled_snapshot === false || item.is_taxable === false) return 0;
    const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;
    return srvLineExclTax(item) * rate;
}
function srvLineTotal(item) {
    return srvLineExclTax(item) + srvLineVat(item);
}

// Parts (qty can be > 1)
function partLineExclTax(item) {
    const base = (item.unit_price || 0) * (item.qty || 1);
    const disc = item.discount || 0;
    return Math.max(base - disc, 0);
}
function partLineVat(item) {
    if (props.data.tax_enabled_snapshot === false || item.is_taxable === false) return 0;
    const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;
    return partLineExclTax(item) * rate;
}
function partLineTotal(item) {
    return partLineExclTax(item) + partLineVat(item);
}

// Services and Parts computeds for Invoice separation
const services = computed(() => {
    const items = props.data.items || dummyItems;
    return items.filter(item => !item.is_part);
});

const parts = computed(() => {
    const items = props.data.items || dummyItems;
    return items.filter(item => item.is_part);
});

// Show VAT column only when at least one item has VAT
const hasServicesVat = computed(() =>
    services.value.some(i => srvLineVat(i) > 0)
);
const hasPartsVat = computed(() =>
    parts.value.some(i => partLineVat(i) > 0)
);

const primaryColor = computed(() => props.visualSettings.primary_color || '#1e293b');
const isModern = computed(() => props.visualSettings.active_template === 'TemplateModernA4');

const damageMarks = computed(() => props.data.damage_marks || []);
const getColorValue = (color) => ({ red: '#ef4444', blue: '#3b82f6', gray: '#6b7280' }[color] || '#ef4444');

// Format Currency
function formatCurrency(value) {
    const suffix = isRtl.value ? ' ر.س' : ' SAR';
    if (value === undefined || value === null) return '0.00' + suffix;
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + suffix;
}

// Format Raw Price (no currency symbol for cells)
function formatRawPrice(value) {
    if (value === undefined || value === null) return '0.00';
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Format Date
function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Compute dynamic signature columns
function getSignatureGridClass(count) {
    if (count === 1) return 'grid-cols-1 justify-items-center';
    if (count === 2) return 'grid-cols-2';
    if (count === 3) return 'grid-cols-3';
    return 'grid-cols-4';
}

// Map document keys to localized Titles
function getDocTypeTitle(type) {
    const titlesAr = {
        invoice: 'فاتورة ضريبية',
        proforma_invoice: 'فاتورة أولية',
        quotation: 'عرض سعر',
        parts_invoice: 'فاتورة قطع غيار',
        work_order: 'كرت صيانة',
        receipt: 'سند قبض مالي',
        checklist: 'تقرير فحص',
        delivery_note: 'سند تسليم',
        condition_report: 'تقرير حالة المركبة'
    };
    const titlesEn = {
        invoice: 'Tax Invoice',
        proforma_invoice: 'Proforma Invoice',
        quotation: 'Quotation',
        parts_invoice: 'Parts Invoice',
        work_order: 'Work Order',
        receipt: 'Receipt',
        checklist: 'Checklist',
        delivery_note: 'Delivery Note',
        condition_report: 'Vehicle Condition Report'
    };
    if (isRtl.value) {
        return titlesAr[type] || 'وثيقة رسمية';
    } else {
        return titlesEn[type] || 'Official Document';
    }
}

// Dummy Fallback Data
const dummyItems = [
    { service_name: 'تغيير اقمشة', description: 'قطع غيار أصلية مع ضبط الميزانية والفرامل', qty: 1, unit_price: 172.50 }
];

const dummyTerms = [
    'الضمان يسري على الأجزاء والخدمات المستبدلة فقط.',
    'المركز غير مسؤول عن فقدان أي مقتنيات شخصية تترك داخل السيارة.'
];

const defaultSignatures = [
    { name_ar: 'المدير', name_en: 'Manager' },
    { name_ar: 'العميل', name_en: 'Customer' }
];

// Computed Totals
const totals = computed(() => {
    const items = props.data.items || dummyItems;
    let subtotal = 0;
    let discount = 0;
    let vat = 0;
    
    const taxEnabled = props.data.tax_enabled_snapshot !== false;

    items.forEach(item => {
        const itemQty = item.qty || 1;
        const itemPrice = item.unit_price || 0;
        const itemDiscount = item.discount || 0;

        subtotal += itemPrice * itemQty;
        discount += itemDiscount;

        if (taxEnabled && item.is_taxable !== false) {
            const lineExclTax = Math.max((itemPrice * itemQty) - itemDiscount, 0);
            const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;
            vat += lineExclTax * rate;
        }
    });

    const subtotalAfterDiscount = Math.max(subtotal - discount, 0);
    const total = subtotalAfterDiscount + vat;

    return {
        subtotal,
        discount,
        vat,
        total
    };
});

function isClientSignature(name) {
    if (!name) return false;
    const n = name.toLowerCase();
    return n.includes('عميل') || n.includes('العميل') || n.includes('customer') || n.includes('client');
}

function getLocalizedColor(colorName, isRtlVal) {
    if (!colorName) return '';
    const colorMap = {
        'white': { ar: 'أبيض', en: 'White' },
        'أبيض': { ar: 'أبيض', en: 'White' },
        'black': { ar: 'أسود', en: 'Black' },
        'أسود': { ar: 'أسود', en: 'Black' },
        'silver': { ar: 'فضي', en: 'Silver' },
        'فضي': { ar: 'فضي', en: 'Silver' },
        'gray': { ar: 'رمادي', en: 'Gray' },
        'grey': { ar: 'رمادي', en: 'Gray' },
        'رمادي': { ar: 'رمادي', en: 'Gray' },
        'red': { ar: 'أحمر', en: 'Red' },
        'أحمر': { ar: 'أحمر', en: 'Red' },
        'blue': { ar: 'أزرق', en: 'Blue' },
        'أزرق': { ar: 'أزرق', en: 'Blue' },
        'brown': { ar: 'بني', en: 'Brown' },
        'بني': { ar: 'بني', en: 'Brown' },
        'green': { ar: 'أخضر', en: 'Green' },
        'أخضر': { ar: 'أخضر', en: 'Green' },
        'beige': { ar: 'بيج', en: 'Beige' },
        'بيج': { ar: 'بيج', en: 'Beige' },
        'orange': { ar: 'برتقالي', en: 'Orange' },
        'برتقالي': { ar: 'برتقالي', en: 'Orange' },
        'gold': { ar: 'ذهبي', en: 'Gold' },
        'ذهبي': { ar: 'ذهبي', en: 'Gold' },
        'yellow': { ar: 'أصفر', en: 'Yellow' },
        'أصفر': { ar: 'أصفر', en: 'Yellow' },
        'purple': { ar: 'بنفسجي', en: 'Purple' },
        'بنفسجي': { ar: 'بنفسجي', en: 'Purple' },
        'bronze': { ar: 'برونزي', en: 'Bronze' },
        'برونزي': { ar: 'برونزي', en: 'Bronze' },
        'maroon': { ar: 'كستنائي', en: 'Maroon' },
        'كستنائي': { ar: 'كستنائي', en: 'Maroon' },
        'navy': { ar: 'كحلي', en: 'Navy' },
        'كحلي': { ar: 'كحلي', en: 'Navy' }
    };
    const key = colorName.trim().toLowerCase();
    const match = colorMap[key];
    if (match) {
        return isRtlVal ? match.ar : match.en;
    }
    return colorName;
}
</script>

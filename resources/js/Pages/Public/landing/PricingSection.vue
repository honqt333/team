<script setup>
/**
 * Pricing section.
 *
 * The monthly/yearly switch is a real toggle (role="switch") so it is
 * reachable by keyboard and announced correctly — previously it was a plain
 * `<button>` with a nested `<span>` and no accessible name.
 */
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    settings: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
});

const billingCycle = ref('monthly');

function toggleBilling() {
    billingCycle.value = billingCycle.value === 'monthly' ? 'yearly' : 'monthly';
}
</script>

<template>
    <section v-if="settings.pricing_enabled" id="pricing" class="section-pad">
        <div class="container">
            <div class="section-head sr">
                <p class="section-tag">{{ isRtl ? 'خطط الاشتراك' : 'Plans' }}</p>
                <h2 class="section-h2">{{ t('pricing_title_ar', 'pricing_title_en') }}</h2>
                <p class="section-desc">{{ t('pricing_subtitle_ar', 'pricing_subtitle_en') }}</p>
            </div>

            <div class="billing-toggle sr">
                <span :class="billingCycle === 'monthly' ? 'active' : ''">
                    {{ isRtl ? 'شهري' : 'Monthly' }}
                </span>

                <button
                    type="button"
                    role="switch"
                    :aria-checked="billingCycle === 'yearly'"
                    data-track="pricing_toggle"
                    data-track-placement="billing"
                    :aria-label="
                        isRtl
                            ? 'التبديل بين الدفع الشهري والسنوي'
                            : 'Toggle between monthly and yearly billing'
                    "
                    @click="toggleBilling"
                    class="toggle-switch"
                    :class="billingCycle === 'yearly' ? 'on' : ''"
                >
                    <span class="toggle-knob"></span>
                </button>

                <span :class="billingCycle === 'yearly' ? 'active' : ''">
                    {{ isRtl ? 'سنوي' : 'Yearly' }}
                    <em class="save-badge">{{ isRtl ? 'وفر ٢٠٪' : 'Save 20%' }}</em>
                </span>
            </div>

            <div class="pricing-grid">
                <div
                    v-for="(plan, i) in plans || []"
                    :key="plan.id"
                    :class="['plan-card sr', plan.is_featured ? 'featured' : '']"
                    :style="`--d:${i * 120}ms`"
                >
                    <div v-if="plan.is_featured" class="plan-badge">
                        {{ isRtl ? 'الأكثر طلباً' : 'Most Popular' }}
                    </div>
                    <h3 class="plan-name">{{ isRtl ? plan.name_ar : plan.name_en }}</h3>
                    <div class="plan-price">
                        <span class="price-num">
                            {{
                                billingCycle === 'yearly'
                                    ? plan.price_yearly || Math.round(plan.price_monthly * 0.8)
                                    : plan.price_monthly
                            }}
                        </span>
                        <span class="price-unit">{{ isRtl ? 'ر.س / شهرياً' : 'SAR / month' }}</span>
                    </div>
                    <p class="plan-desc">{{ isRtl ? plan.description_ar : plan.description_en }}</p>
                    <ul class="plan-features">
                        <li v-for="(ft, fi) in plan.features || []" :key="fi">
                            <span class="check" aria-hidden="true">✓</span>
                            {{ ft }}
                        </li>
                    </ul>
                    <Link
                        :href="route('register', { plan: plan.slug })"
                        :class="[
                            'plan-btn',
                            plan.is_featured ? 'plan-btn-primary' : 'plan-btn-ghost',
                        ]"
                        data-track="trial_start"
                        data-track-placement="pricing"
                    >
                        {{ isRtl ? 'ابدأ الآن' : 'Start Now' }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

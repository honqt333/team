<script setup>
/**
 * Pricing section (Annual-only billing).
 */
import { Link } from '@inertiajs/vue3';

defineProps({
    settings: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
});
</script>

<template>
    <section v-if="settings.pricing_enabled" id="pricing" class="section-pad">
        <div class="container">
            <div class="section-head sr">
                <p class="section-tag">{{ isRtl ? 'خطط الاشتراك' : 'Plans' }}</p>
                <h2 class="section-h2">{{ t('pricing_title_ar', 'pricing_title_en') }}</h2>
                <p class="section-desc">{{ t('pricing_subtitle_ar', 'pricing_subtitle_en') }}</p>
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
                            {{ plan.price_yearly }}
                        </span>
                        <span class="price-unit">{{ isRtl ? 'ر.س / سنوياً' : 'SAR / year' }}</span>
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

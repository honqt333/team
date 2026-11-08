<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
});
</script>

<template>
    <section class="cta-section sr">
        <div class="container">
            <div class="cta-box">
                <h2 class="cta-h2">
                    {{ isRtl ? 'ابدأ بإدارة مركزك بوضوح' : 'Run Your Workshop With Clarity' }}
                </h2>
                <p class="cta-sub">
                    {{
                        isRtl
                            ? 'تجربة مجانية ١٤ يوماً، بدون عقود ولا بطاقة ائتمان.'
                            : '14-day free trial. No contracts, no credit card required.'
                    }}
                </p>
                <Link :href="route('register')" class="btn-cta-white">
                    {{ t('hero_cta_text_ar', 'hero_cta_text_en') }}
                </Link>
            </div>
        </div>
    </section>
</template>

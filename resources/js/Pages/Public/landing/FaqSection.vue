<script setup>
/**
 * FAQ section.
 *
 * Rendered as `<details>/<summary>` so it works with no JavaScript at all —
 * keyboard accessible, screen-reader friendly, and crawlable for the
 * FAQPage JSON-LD added in the SEO pass.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
});
</script>

<template>
    <section v-if="settings.faq_enabled" class="section-pad">
        <div class="container faq-container">
            <div class="section-head sr">
                <p class="section-tag">{{ isRtl ? 'الأسئلة الشائعة' : 'FAQs' }}</p>
                <h2 class="section-h2">
                    {{ isRtl ? 'كل ما تحتاج معرفته' : 'Everything You Need to Know' }}
                </h2>
            </div>

            <div class="faq-list">
                <details
                    v-for="(q, i) in settings.faq_list || []"
                    :key="i"
                    class="faq-item sr"
                    :style="`--d:${i * 80}ms`"
                >
                    <summary
                        class="faq-q"
                        data-track="faq_open"
                        :data-track-placement="`faq-${i + 1}`"
                    >
                        <span class="faq-num" aria-hidden="true">0{{ i + 1 }}</span>
                        <span>{{ isRtl ? q.q_ar : q.q_en }}</span>
                    </summary>
                    <p class="faq-a">{{ isRtl ? q.a_ar : q.a_en }}</p>
                </details>
            </div>
        </div>
    </section>
</template>

<script setup>
/**
 * Real testimonials.
 *
 * This section exists for the testimonials the operator has actually
 * collected. There are no placeholder entries and no invented quotes: an
 * entry is only rendered when it carries a name AND a quote, and when
 * nothing qualifies the whole section stays hidden.
 *
 * That rule is the point. The landing page used to ship four invented
 * clients pointing at other people's trademarks; the section was empty
 * instead, which is honest but says nothing. This gives the operator a
 * place to put the real thing — a workshop name, who said it, and their
 * words.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
    li: { type: Function, required: true },
});

/**
 * Only the entries that carry a quote AND a name.
 *
 * @param {Array} list
 */
function realTestimonials(list) {
    return (list || []).filter((entry) => {
        if (!entry || !entry.name) {
            return false;
        }

        return Boolean((entry.quote_ar || '').trim() || (entry.quote_en || '').trim());
    });
}
</script>

<template>
    <section
        v-if="
            settings.testimonials_enabled && realTestimonials(settings.testimonials_list).length > 0
        "
        id="testimonials"
        class="section-pad"
    >
        <div class="container">
            <div class="section-head sr">
                <p class="section-tag">
                    {{ t('testimonials_tag_ar', 'testimonials_tag_en') }}
                </p>
                <h2 class="section-h2">
                    {{ t('testimonials_title_ar', 'testimonials_title_en') }}
                </h2>
                <p class="section-desc">
                    {{ t('testimonials_subtitle_ar', 'testimonials_subtitle_en') }}
                </p>
            </div>

            <div class="testimonials-grid">
                <figure
                    v-for="(entry, i) in realTestimonials(settings.testimonials_list)"
                    :key="i"
                    class="testimonial-card sr"
                    :style="`--d:${i * 80}ms`"
                >
                    <span class="testimonial-quote-mark" aria-hidden="true">”</span>

                    <blockquote class="testimonial-quote">
                        {{ li(entry, 'quote') }}
                    </blockquote>

                    <figcaption class="testimonial-meta">
                        <img
                            v-if="entry.photo_url"
                            :src="entry.photo_url"
                            :alt="entry.name"
                            class="testimonial-photo"
                            loading="lazy"
                            decoding="async"
                            width="56"
                            height="56"
                        />
                        <img
                            v-else-if="entry.logo_url"
                            :src="entry.logo_url"
                            :alt="entry.name"
                            class="testimonial-photo testimonial-logo"
                            loading="lazy"
                            decoding="async"
                            width="56"
                            height="56"
                        />

                        <div class="testimonial-who">
                            <span class="testimonial-name">{{ entry.name }}</span>
                            <span v-if="li(entry, 'role')" class="testimonial-role">
                                {{ li(entry, 'role') }}
                            </span>
                        </div>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</template>

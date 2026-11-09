<script setup>
/**
 * "Who this is for" — and, more importantly, who it is NOT for.
 *
 * The section is deliberately NOT another card grid: the features section
 * above it is a grid of cards, and a page that repeats the same layout
 * three times reads like a template. Here the audience is a numbered list
 * with hairline separators, which reads like an editorial column.
 *
 * The note at the bottom ("this might not be for you if…") is the point of
 * the whole section. A landing page that never says no is a landing page
 * that is trying to sell to everybody, and everybody is nobody.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
    li: { type: Function, required: true },
});
</script>

<template>
    <section v-if="settings.audience_enabled" id="audience" class="section-pad">
        <div class="container">
            <div class="audience-layout">
                <div class="audience-aside sr">
                    <p class="section-tag">
                        {{ t('audience_tag_ar', 'audience_tag_en') }}
                    </p>
                    <h2 class="section-h2">{{ t('audience_title_ar', 'audience_title_en') }}</h2>
                    <p class="section-desc">
                        {{ t('audience_subtitle_ar', 'audience_subtitle_en') }}
                    </p>

                    <div class="audience-note">
                        <p class="audience-note-title">
                            {{ t('audience_note_title_ar', 'audience_note_title_en') }}
                        </p>
                        <p class="audience-note-text">
                            {{ t('audience_note_text_ar', 'audience_note_text_en') }}
                        </p>
                    </div>
                </div>

                <ol class="audience-list">
                    <li
                        v-for="(item, i) in settings.audience_list || []"
                        :key="i"
                        class="audience-item sr"
                        :style="`--d:${i * 80}ms`"
                    >
                        <span class="audience-num" aria-hidden="true">
                            {{ String(i + 1).padStart(2, '0') }}
                        </span>
                        <div>
                            <h3 class="audience-title">{{ li(item, 'title') }}</h3>
                            <p class="audience-desc">{{ li(item, 'desc') }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>

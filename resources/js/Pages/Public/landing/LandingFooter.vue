<script setup>
/**
 * Landing footer.
 *
 * ACCESSIBILITY FIXES:
 *   - `footer-h4` was an `<h4>` with no `<h2>`/`<h3>` before it, which broke
 *     the heading order. They are now `<h2>` with a visually identical class.
 *   - The social links were `<a>` elements whose only content was an
 *     injected SVG, so they had no accessible name (axe: `link-name`).
 *     Each one now carries an `aria-label` and `rel="noopener noreferrer"`.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
    getSocialIcon: { type: Function, required: true },
    activeLegalPage: { type: String, default: null },
});

const emit = defineEmits(['open-legal']);

const PLATFORM_NAMES = {
    facebook: 'Facebook',
    twitter: 'Twitter',
    x: 'X',
    instagram: 'Instagram',
    linkedin: 'LinkedIn',
    youtube: 'YouTube',
    tiktok: 'TikTok',
    snapchat: 'Snapchat',
    whatsapp: 'WhatsApp',
};
</script>

<template>
    <footer class="lp-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">{{ t('title_ar', 'title_en') }}</div>
                <p class="footer-about">{{ t('footer_about_ar', 'footer_about_en') }}</p>

                <div v-if="(settings.social_links || []).length" class="socials">
                    <a
                        v-for="(link, i) in settings.social_links"
                        :key="link.url || i"
                        :href="link.url"
                        class="social-link"
                        target="_blank"
                        rel="noopener noreferrer"
                        :aria-label="
                            PLATFORM_NAMES[link.platform] || link.platform || 'Social media'
                        "
                        v-safe-html="getSocialIcon(link.platform)"
                    ></a>
                </div>
            </div>

            <div>
                <h2 class="footer-h4">{{ isRtl ? 'روابط سريعة' : 'Navigation' }}</h2>
                <ul class="footer-links">
                    <li v-for="m in settings.footer_menu || []" :key="m.url">
                        <a :href="m.url || '#'">{{ isRtl ? m.label_ar : m.label_en }}</a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="footer-h4">{{ isRtl ? 'قانوني' : 'Legal' }}</h2>
                <ul class="footer-links">
                    <li>
                        <a href="#" @click.prevent="emit('open-legal', 'terms')">
                            {{ isRtl ? 'الشروط والأحكام' : 'Terms & Conditions' }}
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="emit('open-legal', 'privacy')">
                            {{ isRtl ? 'سياسة الخصوصية' : 'Privacy Policy' }}
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="footer-h4">{{ isRtl ? 'تواصل معنا' : 'Contact' }}</h2>
                <ul class="footer-links">
                    <li v-if="settings.contact_email">
                        <a :href="'mailto:' + settings.contact_email">
                            {{ settings.contact_email }}
                        </a>
                    </li>
                    <li v-if="settings.contact_phone">
                        <a :href="'tel:' + settings.contact_phone">{{ settings.contact_phone }}</a>
                    </li>
                    <li v-if="t('address_ar', 'address_en')">
                        {{ t('address_ar', 'address_en') }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>{{ t('copyright_text_ar', 'copyright_text_en') }}</p>
        </div>
    </footer>
</template>

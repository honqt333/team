<script setup>
/**
 * Client logos.
 *
 * IMPORTANT — the previous version of this section shipped four INVENTED
 * clients ("AutoCare", "SpeedFix", "ProGarage", "StarMotors") pointing at
 * random Wikimedia car logos (BMW, Volkswagen, Mercedes, Suzuki).
 *
 * That is not a placeholder, it is a false claim: it tells a visitor that
 * those workshops are customers, and it displays a competitor's trademark
 * to do it. It was also an instant "AI-generated template" tell.
 *
 * The section now renders ONLY logos the operator has explicitly configured
 * (each entry must carry a `logo_url` AND a `name`). If nothing real is
 * configured, the section is hidden instead of showing invented clients.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
});

function realClients(list) {
    return (list || []).filter((c) => {
        if (!c || !c.logo_url || !c.name) {
            return false;
        }

        // An entry that carries a quote is a testimonial, and the
        // testimonial section renders it. Showing it here as well would
        // put the same workshop on the page twice.
        const hasQuote = Boolean((c.quote_ar || '').trim() || (c.quote_en || '').trim());

        return !hasQuote;
    });
}
</script>

<template>
    <section
        v-if="settings.testimonials_enabled && realClients(settings.testimonials_list).length > 0"
        class="section-pad clients-section"
    >
        <div class="container clients-inner">
            <div class="section-head sr">
                <p class="section-tag">{{ isRtl ? 'شركاء النجاح' : 'Our Clients' }}</p>
                <h2 class="section-h2">{{ isRtl ? 'يثقون بنظامنا' : 'Trusted By Leaders' }}</h2>
            </div>

            <ul class="clients-list sr">
                <li v-for="(client, i) in realClients(settings.testimonials_list)" :key="i">
                    <img
                        :src="client.logo_url"
                        :alt="client.name"
                        loading="lazy"
                        decoding="async"
                        width="150"
                        height="60"
                    />
                </li>
            </ul>
        </div>
    </section>
</template>

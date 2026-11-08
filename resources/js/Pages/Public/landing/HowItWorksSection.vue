<script setup>
/**
 * "How it works" — a real timeline.
 *
 * This section exists because it is the one thing no SaaS template has: a
 * concrete, honest description of what actually happens in the first week.
 * Generic "Sign up / Customise / Enjoy" steps are the clearest sign of a
 * generated page, and they answer a question the visitor does not have.
 *
 * The copy is about the customer's own workflow (importing vehicles,
 * pricing, training), not about the software's features.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
});

const STEPS = [
    {
        ar: {
            title: 'يوم واحد: إدخال بياناتك',
            body: 'تستورد السيارات والعملاء والقوائم من ملف Excel، وتحدد الضريبة وأسعار الخدمات. لا تحتاج team تقني.',
        },
        en: {
            title: 'Day one: bring your data',
            body: 'Import vehicles, customers and price lists from a spreadsheet, then set your VAT rate and service prices. No technical team needed.',
        },
    },
    {
        ar: {
            title: 'أيام ٢-٣: تشغيل أول كرت عمل',
            body: 'الفني يسحب السيارة، يسجل الفحص والقطع، وتظهر الفاتورة جاهزة مع الضريبة — دون Excel أو ورقي.',
        },
        en: {
            title: 'Days 2–3: your first work order',
            body: 'A technician books the vehicle in, logs the inspection and the parts, and the VAT invoice is ready — no spreadsheets, no paper.',
        },
    },
    {
        ar: {
            title: 'أسبوع: تدريب الفريق',
            body: 'تدريب عن بُعد أو في الموقع حسب الباقة، مع كتيّب مختصر بالعربي،和支持 فني يعمل في توقيت يومك.',
        },
        en: {
            title: 'Week one: train the team',
            body: 'Remote or on-site training depending on your plan, a short Arabic handbook, and support that works on your schedule.',
        },
    },
];
</script>

<template>
    <section id="how-it-works" class="section-pad how-section">
        <div class="container">
            <div class="section-head sr">
                <p class="section-tag">
                    {{ isRtl ? 'من التسجيل إلى التشغيل' : 'From signup to go-live' }}
                </p>
                <h2 class="section-h2">
                    {{
                        isRtl
                            ? 'كم يستغرق تشغيل النظام في مركزك؟'
                            : 'How long does it take to go live?'
                    }}
                </h2>
                <p class="section-desc">
                    {{
                        isRtl
                            ? 'جدول زمني واضح بلا مبالغة — هذا ما يحدث فعلياً بعد التسجيل.'
                            : 'A realistic timeline, no hype — this is what actually happens after you sign up.'
                    }}
                </p>
            </div>

            <ol class="how-steps">
                <li
                    v-for="(step, i) in STEPS"
                    :key="i"
                    class="how-step sr"
                    :style="`--d:${i * 120}ms`"
                >
                    <span class="how-num" aria-hidden="true">{{ i + 1 }}</span>
                    <h3 class="how-title">{{ isRtl ? step.ar.title : step.en.title }}</h3>
                    <p class="how-body">{{ isRtl ? step.ar.body : step.en.body }}</p>
                </li>
            </ol>
        </div>
    </section>
</template>

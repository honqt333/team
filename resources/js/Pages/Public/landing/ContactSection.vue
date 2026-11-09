<script setup>
/**
 * Contact section.
 *
 * ACCESSIBILITY FIX: every field now has a real `<label for>` bound to the
 * input's `id`. Previously the labels were rendered but never associated
 * with their inputs, so axe reported 4+ `label` failures and screen readers
 * announced the fields as unlabelled.
 */

const props = defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    form: { type: Object, required: true },
    success: { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);

const labels = {
    name: props.isRtl ? 'الاسم الكريم' : 'Full Name',
    email: props.isRtl ? 'البريد الإلكتروني' : 'Email Address',
    phone: props.isRtl ? 'رقم الهاتف' : 'Phone Number',
    subject: props.isRtl ? 'الموضوع' : 'Subject',
    message: props.isRtl ? 'الرسالة' : 'Message',
};
</script>

<template>
    <section v-if="settings.contact_enabled" id="contact" class="section-pad">
        <div class="container max-w-4xl mx-auto px-4 md:px-6">
            <div class="section-head sr">
                <p class="section-tag">{{ isRtl ? 'تواصل معنا' : 'Contact Us' }}</p>
                <h2 class="section-h2">{{ isRtl ? 'نحن هنا لمساعدتك' : 'We are here to help' }}</h2>
            </div>

            <div class="sr contact-card">
                <div v-if="success" class="contact-success" role="status" aria-live="polite">
                    {{
                        isRtl
                            ? 'تم إرسال رسالتك بنجاح! شكراً لتواصلك معنا.'
                            : 'Message sent successfully! Thank you for contacting us.'
                    }}
                </div>

                <form
                    class="grid grid-cols-1 md:grid-cols-2 gap-6"
                    @submit.prevent="emit('submit')"
                >
                    <div class="md:col-span-1">
                        <label for="lp-contact-name" class="form-label">
                            {{ labels.name }}
                        </label>
                        <input
                            id="lp-contact-name"
                            v-model="form.name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            required
                            class="form-input"
                        />
                    </div>

                    <div class="md:col-span-1">
                        <label for="lp-contact-email" class="form-label">
                            {{ labels.email }}
                        </label>
                        <input
                            id="lp-contact-email"
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            dir="ltr"
                            class="form-input text-left"
                        />
                    </div>

                    <div class="md:col-span-1">
                        <label for="lp-contact-phone" class="form-label">
                            {{ labels.phone }}
                        </label>
                        <input
                            id="lp-contact-phone"
                            v-model="form.phone"
                            name="phone"
                            type="tel"
                            autocomplete="tel"
                            dir="ltr"
                            class="form-input text-left"
                        />
                    </div>

                    <div class="md:col-span-1">
                        <label for="lp-contact-subject" class="form-label">
                            {{ labels.subject }}
                        </label>
                        <input
                            id="lp-contact-subject"
                            v-model="form.subject"
                            name="subject"
                            type="text"
                            class="form-input"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label for="lp-contact-message" class="form-label">
                            {{ labels.message }}
                        </label>
                        <textarea
                            id="lp-contact-message"
                            v-model="form.message"
                            name="message"
                            rows="4"
                            required
                            class="form-input resize-y"
                        ></textarea>
                    </div>

                    <div class="md:col-span-2 text-center mt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="maint-btn contact-submit"
                            data-track="contact_submit"
                            data-track-placement="contact-form"
                        >
                            <span v-if="form.processing">
                                {{ isRtl ? 'جاري الإرسال...' : 'Sending...' }}
                            </span>
                            <span v-else>{{ isRtl ? 'إرسال الرسالة' : 'Send Message' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

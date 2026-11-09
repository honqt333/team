<script setup>
import Modal from '@/Components/Modal.vue';
/**
 * Legal pages modal.
 *
 * ACCESSIBILITY: the dialog now has `role="dialog"`, `aria-modal="true"` and
 * an accessible name, and the close button has a label. Escape closes it.
 */

defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    activeLegalPage: { type: String, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <transition name="modal">
        <Modal :show="!!activeLegalPage" unstyled @close="$emit('close')">
            <div v-if="activeLegalPage" class="modal-overlay" @click.self="emit('close')">
                <div
                    class="modal-box"
                    :aria-label="
                        activeLegalPage === 'terms'
                            ? isRtl
                                ? 'الشروط والأحكام'
                                : 'Terms & Conditions'
                            : isRtl
                              ? 'سياسة الخصوصية'
                              : 'Privacy Policy'
                    "
                >
                    <div class="modal-head">
                        <h2>
                            {{
                                activeLegalPage === 'terms'
                                    ? isRtl
                                        ? 'الشروط والأحكام'
                                        : 'Terms & Conditions'
                                    : isRtl
                                      ? 'سياسة الخصوصية'
                                      : 'Privacy Policy'
                            }}
                        </h2>
                        <button
                            type="button"
                            class="modal-close"
                            :aria-label="isRtl ? 'إغلاق' : 'Close'"
                            @click="emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <div
                        class="modal-body overflow-y-auto max-h-[60vh] p-6 leading-relaxed text-gray-700 dark:text-gray-300"
                    >
                        {{
                            activeLegalPage === 'terms'
                                ? isRtl
                                    ? settings.page_terms_ar
                                    : settings.page_terms_en
                                : isRtl
                                  ? settings.page_privacy_ar
                                  : settings.page_privacy_en
                        }}
                    </div>
                </div>
            </div>
        </Modal>
    </transition>
</template>

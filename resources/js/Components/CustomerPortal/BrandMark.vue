<template>
    <!--
        BrandMark — small, square, accessible brand tile.

        Two shapes:
          1. Tenant has uploaded a logo: render the image on a
             white card so the logo keeps its native contrast.
          2. Tenant has no logo: render a colored monogram tile
             using the tenant's primary color. The monogram is
             the first character of the site name; the customer
             can still tell whose portal this is at a glance.

        The size is controlled by the parent via the `size` prop.
    -->
    <div
        class="flex items-center justify-center overflow-hidden flex-shrink-0"
        :class="[
            size === 'sm' ? 'h-9 w-9 rounded-xl' : 'h-12 w-12 sm:h-14 sm:w-14 rounded-2xl',
            tenant?.has_logo
                ? 'bg-white border border-slate-200 shadow-sm'
                : 'border border-transparent',
        ]"
        :style="
            tenant?.has_logo
                ? {}
                : {
                      backgroundColor:
                          'color-mix(in srgb, var(--tenant-primary, #4f46e5) 12%, transparent)',
                      color: 'var(--tenant-primary, #4f46e5)',
                  }
        "
    >
        <img
            v-if="tenant?.has_logo"
            :src="tenant.logo_url"
            :alt="tenant?.site_name"
            class="h-full w-full object-contain p-1.5"
        />
        <span
            v-else
            :class="size === 'sm' ? 'text-sm' : 'text-base sm:text-lg'"
            class="font-extrabold leading-none"
        >
            {{ initial }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    // 'sm' = header (h-9 w-9), 'lg' = login/verify card
    size: { type: String, default: 'sm' },
});

const initial = computed(() => {
    const n = props.tenant?.site_name || '';
    const ch = n.trim().charAt(0);
    return ch ? ch.toUpperCase() : '?';
});
</script>

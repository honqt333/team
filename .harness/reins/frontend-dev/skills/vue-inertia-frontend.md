# Skill: Vue + Inertia Frontend - Carag V2

دليل سريع لقواعد الـ frontend في Carag V2. كل تغيير في `resources/js/**` لازم يلتزم بها.

## المشروع

- **Framework:** Vue 3 (Composition API) + Inertia.js
- **Build:** Vite
- **Styling:** Tailwind CSS
- **Language:** JavaScript (TS مقبول لكن مو مفروض)
- **i18n:** Arabic (RTL) أساسي + English (LTR) ثانوي
- **UI lib (اختياري):** shadcn-vue / Flowbite / Headless UI

## القاعدة رقم 1: Inertia.js — لا SPA Router

**لا** تستخدم vue-router. Inertia.js يستبدله:

```vue
<!-- ❌ لا تستخدم -->
<router-link :to="`/customers/${c.id}`">View</router-link>

<!-- ✅ صح -->
<Link :href="route('customers.show', c.id)">View</Link>
```

```js
// ❌ لا تستدعي axios مباشرة في معظم الحالات
import axios from 'axios'
axios.post('/api/work-orders', data)

// ✅ استخدم Inertia form — يحصل على errors و redirects مجاناً
import { useForm } from '@inertiajs/vue3'
const form = useForm({ customer_id: 1, items: [] })
form.post(route('work-orders.store'), { preserveScroll: true })
```

## القاعدة رقم 2: route() helper من Ziggy

```js
// resources/js/app.js — تأكد من وجود Ziggy plugin
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.es'

createInertiaApp({
  // ...
})
  .use(plugin)
  .use(ZiggyVue, Ziggy)

// في الـ component
route('work-orders.show', 5)        // → /app/work-orders/5
route('customers.update', customer) // → /app/customers/5
```

## القاعدة رقم 3: Composition API + `<script setup>`

```vue
<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  workOrder: { type: Object, required: true },
  customers: { type: Array, default: () => [] },
})

const emit = defineEmits(['saved'])

const form = useForm({
  notes: props.workOrder.notes ?? '',
  customer_id: props.workOrder.customer_id,
})

const submit = () => {
  form.put(route('work-orders.update', props.workOrder.id), {
    onSuccess: () => emit('saved'),
  })
}
</script>
```

## القاعدة رقم 4: RTL — logical properties فقط

```vue
<template>
  <!-- ❌ ممنوع -->
  <div class="ml-4 mr-2 pl-3 pr-1 text-left">
  
  <!-- ✅ صح -->
  <div class="ms-4 me-2 ps-3 pe-1 text-start" dir="rtl">
</template>
```

| ممنوع (LTR) | البديل RTL-safe |
|---|---|
| `ml-*` / `mr-*` | `ms-*` / `me-*` |
| `pl-*` / `pr-*` | `ps-*` / `pe-*` |
| `left-*` / `right-*` | `start-*` / `end-*` |
| `text-left` / `text-right` | `text-start` / `text-end` |
| `border-l` / `border-r` | `border-s` / `border-e` |

## القاعدة رقم 5: Forms — Inertia useForm

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  // ...
})

const submit = () => {
  form.post(route('customers.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.name" :class="{ 'border-red-500': form.errors.name }">
    <p v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</p>
    <button :disabled="form.processing">حفظ</button>
  </form>
</template>
```

`form.errors` يجي من `FormRequest` validation في backend. **لا** تتحقق client-side فقط.

## القاعدة رقم 6: Inertia shared data

```js
// في الـ layout component
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const user = computed(() => page.props.auth.user)
const tenant = computed(() => page.props.tenant)
```

## القاعدة رقم 7: Components قابلة لإعادة الاستخدام

```vue
<!-- resources/js/Components/Button.vue -->
<script setup>
defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
})
</script>
<template>
  <button :class="['btn', `btn-${variant}`, `btn-${size}`]" :disabled="loading">
    <span v-if="loading">...</span>
    <slot />
  </button>
</template>
```

## القاعدة رقم 8: لا تخترق layers

- **لا** تستدعي `$fetch` للـ routes الخاصة بـ Inertia (استخدم `useForm`)
- **لا** تخزن secrets في الـ frontend
- **لا** تخفي auth checks في الـ UI — backend هو المرجع

## هيكل المجلدات

```
resources/js/
├── Pages/
│   ├── WorkOrders/
│   │   ├── Index.vue
│   │   ├── Show.vue
│   │   ├── Create.vue
│   │   └── Partials/          # مكوّنات خاصة بالـ page
│   ├── Customers/
│   └── Invoices/
├── Components/                # مكوّنات عامة
│   ├── Button.vue
│   ├── DataTable.vue
│   └── FormFields/
├── Layouts/
│   ├── AppLayout.vue
│   ├── AuthLayout.vue
│   └── PublicLayout.vue
├── composables/               # (اختياري) منطق قابل لإعادة الاستخدام
│   ├── useFormatters.js
│   └── useDebounce.js
└── app.js
```

## Handoff

بعد ما تخلص، اكتب في `deliverable.md`:

```markdown
## Handoff to tester
- Pages changed: `resources/js/Pages/WorkOrders/Show.vue`
- New components: `resources/js/Components/CustomerSearch.vue`
- Manual test:
  1. Visit `/app/work-orders/1`
  2. Change notes → click save
  3. Verify redirect back + flash message
  4. Toggle browser dir → verify layout flips
- RTL: ✓ / ✗ (with screenshot)
```

---

*Built for the full-stack team rollout — 2026-06-06*

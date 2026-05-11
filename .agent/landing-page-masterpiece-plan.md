# 🎨 Landing Page Masterpiece — خطة التنفيذ

## الهدف
بناء أجمل صفحة هبوط في العالم لـ Carag Pro
الملف: `resources/js/Pages/Public/LandingPreview.vue`

---

## المراحل

### ✅ المرحلة 1: Script + Logic (مكتملة)
### ✅ المرحلة 2: Template — Header + Hero + Features + Stats + Pricing + FAQ + CTA + Footer (مكتملة)
### ✅ المرحلة 3: Styles — CSS الاحترافي الكامل (مكتملة)
### ✅ المرحلة 4: إصلاح Scroll Reveal Bug (مكتملة)

### ⏳ المرحلة 5: تحسينات بصرية إضافية (اختياري)
- تحسين قسم Features إذا كانت البيانات فارغة (placeholder cards أجمل)
- إضافة Testimonials Carousel إذا وُجدت بيانات
- تحسين RTL (العربية) على جميع الأقسام
- تحسين صفحة CMS (Website Settings Index.vue)

**ما يجب كتابته:**
```
<script setup> ... </script>
```
يشمل:
- Particle System (canvas-based)
- Magnetic Button Effect
- Animated Counter (stats)
- Cursor Glow Follower
- Pricing Toggle (Monthly/Yearly)
- Scroll Reveal (IntersectionObserver)
- Language + Theme + Mobile Menu
- Staggered animation delays
- Typed text effect for hero

### ⏳ المرحلة 2: Template — الجزء الأول (Header + Hero)
```
<template> ... Hero section
```
يشمل:
- Canvas Particle Background (full screen)
- Aurora gradient background blobs
- Animated cursor follower div
- Premium Header: logo + nav + actions (glassmorphism)
- Hero: Left=Title+CTA / Right=3D floating dashboard mockup
- Floating stats badge (live counter)
- Animated scrolling ticker (marquee)

### ⏳ المرحلة 3: Template — الجزء الثاني (Features + Stats)
يشمل:
- Features Grid: 3D Tilt cards on hover
- Stats Section: animated counters
- "How it works" — 3-step visual flow
- Social proof logos strip (marquee)

### ⏳ المرحلة 4: Template — الجزء الثالث (Pricing + FAQ + CTA)
يشمل:
- Pricing cards with Monthly/Yearly toggle
- Animated price switch
- FAQ accordion
- Pre-footer big CTA section
- Footer with columns + socials

### ⏳ المرحلة 5: Styles
```
<style> ... </style>
```
يشمل:
- Particle canvas styles
- Aurora animation keyframes
- Magnetic button CSS vars
- 3D tilt card perspective
- Cursor glow
- Scroll reveal
- Typed text animation
- Mobile menu transition
- Legal modal transition
- RTL adjustments
- Dark/Light mode polish

---

## تقنيات سيتم استخدامها

| التقنية | الوصف |
|---|---|
| Canvas API | Particle system متحرك في الخلفية |
| CSS Custom Properties | Magnetic button + cursor tracking |
| IntersectionObserver | Scroll reveal مع stagger |
| CSS Keyframes | Aurora, float, pulse, shimmer |
| CSS Perspective/Transform3D | بطاقات ثلاثية الأبعاد عند hover |
| requestAnimationFrame | Smooth counter animation |
| Marquee CSS | شريط الشركاء والمميزات |
| Glassmorphism | Header + Cards |
| Gradient Text | عناوين ملونة متدرجة |
| Google Fonts | Cairo (AR) + Outfit (EN) |

---

## ترتيب الأوامر للاكتمال

1. `write_to_file` — كتابة الـ Script section كاملاً
2. `write_to_file` — كتابة الـ Template Part 1 (Header + Hero)
3. `multi_replace_file_content` — إضافة Features + Stats
4. `multi_replace_file_content` — إضافة Pricing + FAQ + CTA + Footer
5. `multi_replace_file_content` — إضافة Styles section
6. اختبار في المتصفح

---

## الملفات الأخرى (Website CMS)
`resources/js/Pages/System/Website/Index.vue`
- إعادة تصميم شاملة بنمط Enterprise CRM الحديث
- إضافة Live Preview banner أعلى الصفحة
- تحسين Sidebar إلى نظام Icon+Text احترافي
- إضافة Color Picker للألوان الأساسية
- تصميم tabs بـ animated indicator

---

## ملاحظات مهمة
- الصفحة الحالية `/` تبقى كما هي (لا تُمس)
- الصفحة الجديدة على `/landing-preview`
- عند الإطلاق: تبديل الـ routes
- Controller: `app/Http/Controllers/Public/PublicLandingController.php`
- Route: `routes/web.php` → `public.landing.preview`

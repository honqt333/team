# خطة تطوير صفحة الهبوط `landing-preview`

> التشخيص مبني على ملفات Lighthouse الحقيقية الموجودة في المستودع
> (`carag-v2.test_2026-05-25_23-04-01/04-36/07-41.report.html`)
> وعلى قراءة الكود الفعلي: `resources/js/Pages/Public/LandingPreview.vue` (2,962 سطر).
>
> **الخلاصة:** المشكلة ليست "التصميم جميل" — التصميم مبالغ فيه لدرجة مبالغات.
> المشكلة الحقيقية ثلاث: **الأداء Carver 20-41%**، **والبطء ينتج عنscroll reveal + LCP**،
> **والمحتوى مكرّر من قالب AI جاهز**. خطة أدناه تعالج الثلاثة بالترتيب.

---

## أولاً: التشخيص بالأرقام

### 1.1 الأداء (Landing Performance)

| المقياس | النتيجة الحالية | المطلوب | الحالة |
| :--- | ---: | ---: | :--- |
| Performance Score | **20% – 41%** | 85%+ | 🔴 |
| First Contentful Paint | **12.9 – 17.3 ث** | < 2.5 ث | 🔴 |
| Largest Contentful Paint | **26.8 – 27.8 ث** | < 4 ث | 🔴 |
| Time to Interactive | **27.2 – 28.8 ث** | < 5 ث | 🔴 |
| Total Blocking Time | **580 – 3,000 ms** | < 200 ms | 🔴 |
| Speed Index | 13.6 – 17.0 ث | < 5 ث | 🔴 |
| Accessibility | **66%** | 95%+ | 🔴 |
| Best Practices | 78% | 95%+ | 🟡 |
| SEO | 100% | — | 🟢 |
| حجم الصفحة | **4,259 KiB** | < 1,200 KiB | 🔴 |
| JS غير مستخدم | **883 KiB** | — | 🔴 |
| JS غير مضغوط | **1,330 KiB** | — | 🔴 |
| CSS غير مستخدم | 79 KiB | — | 🟡 |

### 1.2 أين ذهبت الثواني الـ27؟ (من `mainthread-work-breakdown`)

| البند | الوقت | السبب الجذري |
| :--- | ---: | :--- |
| `chunk-QGYPZBXI.js` (vendor) | **3,645 ms** | Inertia/Vue vendor bundle يُحمَّل كاملاً |
| Unattributable | 3,176 ms | تنفيذ JS المضمّن في الصفحة |
| **`LandingPreview.vue`** | **2,685 ms** | ❗ **الملف نفسه 2,962 سطر = يوزّع الصفحة كاملة في chunk واحد** |
| `carag-v2.test/landing-preview` (SSR) | 1,459 ms | Inertia يمرّر 100+ prop للصفحة |
| `mainthread-work-breakdown` | 11.3 s | — |

**LCP breakdown** — وقت الاستجابة كان 753ms فقط، لكن **تأخير الرسم كان 5,525ms**:
الـ LCP element هو `.typed-line` (نص الكتابة المتحركة).
> ❗ **هذا أهم اكتشاف في التشخيص:** الـ LCP هو *نص يتغيّر كل 90 مللي ثانية*.
> المتصفح لا يستطيع تثبيت LCP على عنصر يتغير، فالحساب يتأخر حتى "يستقر" النص.
> نتيجةً مباشرة: **الصفحة تُقاس أبطأ مما هي فعلاً** — وهي أبطأ فعلاً أيضاً بسبب الأنيميشن.
> عناصر الـ LCP يجب أن تكون **صوراً أو نصوصاً ثابتة**، لا نصوصاً متحركة.

### 1.3 مشاكل الوصولية (11该项 Axe failures)

| المشكلة | العدد | المثال |
| :--- | :---: | :--- |
| `landmark-one-main` | 1 | لا يوجد `<main>` في الصفحة إطلاقاً |
| `heading-order` | — | `<h4>` في الفوتر بلا `<h2>`/`<h3>` قبله |
| `label` | 4+ | كل حقول فورم الاتصال بلا `<label>` ولا `aria-label` |
| `link-name` | 4 | روابط السوشيال بلا نص للـ screen reader |
| `button-name` | 3 | `.icon-btn` (القائمة/الثيم) بلا `aria-label` |
| `color-contrast` | 4 | `#4f46e5` على `#050508` = **3.23:1** والمطلوب 4.5:1 |
| | | `#4d5460` على `#050508` = **2.66:1** ❌ |
| `unsized-images` | 4 | صور شعارات العملاء بلا `width`/`height` |

### 1.4 لماذا تبدو "مبرمجة بالذكاء الاصطناعي"

هذه ليست إحساس — هذا **نمط قالب SaaS عام مطبَّق حرفياً**، وكل علامته موجودة في الكود:

| العلامة | الدليل في الكود |
| :--- | :--- |
| كل شيء يلمع متحرك | 10 `@keyframes` + 11 `animation:` + `auroraMove 20s infinite` |
| Glassmorphism في كل مكان | 7 × `backdrop-filter` |
| جرافيك بلا معنى | Canvas particles + aurora + grid overlay + cursor glow + shimmer |
| Mockup متطابق (مكرر) | `.mockup-frame` = نافذة متصفح اصطناعية بـ 3 نقاط ملونة |
| شارات عائمة عشوائية | `float-card fc-tl` "⚡ 99.9%" + `fc-br` "🚀 +500" |
| Hero بنمط SaaS | Badge pill + H1 + typed line + 2 CTA + 3 checkmarks |
| شعارات وهمية | `AutoCare` / `SpeedFix` / `ProGarage` / `StarMotors` — **غير موجودة، مجرد placeholder** |
| إحصائيات مبالغ فيها | `+500 ورشة` / `+1M فاتورة` / `+100K عميل` / `%99.9` — بلا مصدر |
| **نفس المحتوى** | نص الـ Hero، الـ 4 features، الـ 3 FAQ، الـ 4 stats — **كلها مكتوبة مرتين: مرة PHP ومرة Vue** |
| "معتمد من هيئة الزكاة" | ادعاء تنظيمي بلا إثبات — خطر قانوني |
| CTA واحد للجميع | "اشترك الآن مجاناً" بدون أي_hint للزر ancl |

**النتيجة:** أي زائر العين，他知道 أنه مولّد. لأن **المحتوى غير مخصص** — نفس القالب قد يُباع لمطعم وبلايستون.

---

## ثانياً: المبادئ التوجيهية للخطة

> **القاعدة الذهبية:** صفحة الهبوط ليست تجربة فنية — إنها صفحة **بيع**.goal واحد: أن يقول الزائر "هذا为我".
> كل عنصر لا يخدم ذلك = حذف.

1. **الأداء أولاً** — لا تُطلق أي تحسين بصري قبل أن تكون الصفحة تحت 4 ثوانٍ على موبايل حقيقي.
2. **الصدق قبل الجمال** — رقم بلا مصدر = رقم مزيف. `+500 ورشة` إن لم تكن أنت تملكها، احذفها.
3. **الصورة بدل الكود** — صورة حقيقية تُحمَّل في 40KB أفضل من mockup مرسوم بـ CSS في 2.7 ثانية.
4. **التميّز قبل التمييز** — الفرق بين "مكرر" و"مميز" هو: هل يعرف الزائر اسم هذه الورشة؟

---

## ثالثاً: الخطة على 6 مراحل

> كل مرحلة = commit مستقل قابل للتراجع، مع **measure قبل / after** مثبت في الـ PR.
> التسلسل مقصود: لا تبدأ مرحلة قبل إنهاء التي قبلها.

---

### 🟥 المرحلة 0 — خط الأساس والقياس (يوم واحد)

**الهدف:** رقم مرجعي ثابت، بدونه لا معنى لأي تحسين لاحق.

- [ ] تثبيت **Lighthouse CI** على `/landing-preview` (موبايل + ديسكتوب) في `package.json`:
      ```json
      "scripts": { "lhci": "lhci autorun --collect.url=http://carag-v2.test/landing-preview" }
      ```
- [ ] حفظ نتائج Lighthouse **الحالية** كـ `docs/landing-preview/baseline.json` (بعد إعادة القياس نظيفة، لا بالتقارير القديمة).
- [ ] إضافة `budgets.json` يمنع الانحدار:
      ```json
      [{ "path": "/*", "resourceSizes": [{ "resourceType": "script", "budget": 250 }] }]
      ```
- [ ] قياس **حجم الـ bundle الفعلي** المخصص للصفحة عبر `vite build --mode production` + تقرير visualizer.

**معيار الإنجاز:** تقرير baseline مرفق + budgets مُفعّلة.

---

### 🟧 المرحلة 1 — إصلاح الأداء (يومان) ⭐ الأهم

> **هذه المرحلة تحل 80% من شكوى "الاستجابة البطيئة".**

#### 1.1 قتل الـ LCP المتحرك (الأولوية #1)
- [ ] حذف تأثير الكتابة المتحرّكة (`typed-line`) **من الـ LCP** — إمّا:
  - **الخيار المُوصى به:** استبدالها بـ **سطر ثابت** + تبديل طفيف عبر `opacity` (لا `textContent`)، أو
  - جعلها تعمل فقط بعد `load` بـ `requestIdleCallback`.
- [ ] **عنصر LCP يجب أن يكون ثابتاً**: صورة حقيقية (1x + AVIF/WebP) أو عنوان ثابت.
- [ ] **`fetchpriority="high"` + `preload`** للصورة الرئيسية بـ `<link rel="preload" as="image" imagesrcset=...>`.
- [ ] إضافة `width`/`height` لكل صورة (وإصلاح `unsized-images`).

#### 1.2 تقسيم الملف
المشكلة: `LandingPreview.vue` = 2,962 سطر في chunk واحد.
- [ ] تقسيم إلى مكوّنات داخل `resources/js/Pages/Public/landing/`:
  ```
  landing/
    HeroSection.vue      (~150 سطر)
    FeaturesSection.vue
    StatsSection.vue
    PricingSection.vue
    FaqSection.vue
    TestimonialsSection.vue
    ContactSection.vue
    CtaSection.vue
    LandingFooter.vue
    LegalModal.vue
    HeroDashboardVisual.vue   ← الـ mockup
  ```
- [ ] كل قسم `defineAsyncComponent` → لا يُحمَّل إلا عند الوصول له (يقطع `bootup-time`).

#### 1.3 تقليل الـ CSS (1,680 سطر CSS داخل الـ component)
- [ ] نقل كل `<style>` من الـ SFC إلى `resources/css/landing.css` **مع `content` مخصص في `tailwind.config.js`** — Tailwind عنده `@layer components` + `purge` على `content` array Currently `resources/js/**/*.vue` فقط، لذا CSS المُستورد من `resources/css` **لن يُنقّى تلقائياً** — يجب إضافة:
      ```js
      content: [..., './resources/css/landing.css']
      ```
- [ ] **حذف** 7 × `backdrop-filter` (مكلفة جداً على الموبايل) → استبدال بـ `background: rgba()` + `border`.
- [ ] **حذف** Cursor GlowFollower (SVG/div يتتبع الماوس على كل `mousemove`).
- [ ] **تخفيف** Aurora: من 3 blobs + `20s infinite` → خلفية CSS ثابتة بـ `radial-gradient` (بلا أنيميشن).
- [ ] `@media (prefers-reduced-motion: reduce)` → إيقاف **كل** الأنيميشن.

#### 1.4 تحسين الـ Fonts
- [ ] الحالي: `Cairo` (7 أوزان) + `Outfit` (7 أوزان) = **14 خطاً من Google Fonts** (رندر-بلوكينج).
- [ ] الحل: **self-host** بخط واحد (الأنسب: `IBM Plex Sans Arabic` — هو أصلاً الخط المعتمد في `tailwind.config.js`!) بأوزان `400,600,700` فقط، مع `font-display: swap`.
- [ ] `preload` للوزن 400 فقط.

#### 1.5 Inertia والـ Props
- [ ] الـ Controller يمرّر **~70 prop**، والصفحة تحمّل كل Inertia + Vue vendor.
- [ ] التأكد أن `PublicLandingController` يستخدم فقط `Plan::where('is_active',true)` — ✅ جيد، لا N+1.
- [ ] **الاحتفاظ بـ Inertia** (شرط المشروع) لكن التأكد من عدم تحميل `axios` مرتين + تبسيط `app.js`.
- [ ] تفعيل **الضغط مسبقاً** للـ HTML: `response->header('Content-Encoding')` أو Brotli على Nginx (المقاس في Lighthouse: `modern-http-insight` = توفير 3,260ms!).

**معايير الإنجاز (مقاسة):**

| المقياس | من | إلى |
| :--- | ---: | ---: |
| Performance Score | 20-41% | **80%+** |
| LCP | ~27 ث | **< 4 ث** |
| FCP | ~15 ث | **< 2.5 ث** |
| TBT | 3,000 ms | **< 300 ms** |
| حجم الصفحة | 4,259 KiB | **< 1,500 KiB** |

---

### 🟨 المرحلة 2 — إصلاح الوصولية (يوم واحد)

> من 66% إلى 95%+ — سهل جداً ومطلوب.

- [ ] **`<main>`**: تغليف المحتوى بين `<header>` و `<footer>`.
- [ ] **ترتيب العناوين**: تحويل `<h4 class="footer-h4">` إلى `<h2>`/`<h3>` حسب السياق.
- [ ] **الفورم**: `<label for>` لكل حقل + `id` + `aria-describedby` للأخطاء.
- [ ] **الأزرار الأيقونية**: `aria-label` لكل `.icon-btn` (القائمة، الثيم، اللّغة).
- [ ] **روابط السوشيال**: `aria-label="فيسبوك"` + `rel="noopener noreferrer"`.
- [ ] **التباين**: `#4f46e5` على `#050508` → **`#8b8cf5`** (arnulf-4.6:1) أو إضافة `text-slate-300`؛ `#4d5460` → **`#9aa3b2`**.
- [ ] **الصور**: `width`/`height` لكل `<img>` + `alt` وصفي حقيقي (لا "Dashboard").
- [ ] **التركيز**: `:focus-visible` واضح — غير موجود الآن.
- [ ] **التنقل بلوحة المفاتيح**: اختبار Tab كامل + `Esc` لإغلاق المودال.

**معايير الإنجاز:** Accessibility ≥ 95%، صفر أخطاء Axe.

---

### 🟦 المرحلة 3 — كسر "قالب الذكاء الاصطناعي" (يومان) ⭐ الأهم على الإطلاق

> **هذه هي المرحلة التي تعالج شكواك الأساسية. المرحلتان 1 و2 تجعلها سريعة؛ بدونهما تبقى "متحركة لكن مكررة".**

#### 3.1diagnosed: أين يوجد التكرار فعلياً
المحتوى مكتوب **مرتين** — مرة في `PublicLandingController.php` (الأسطر 154–211) ومرة في `LandingPreview.vue`.
هذا يعني أن تغيير نص يتطلب تعديلين، وأن اللصق بين الاثنين مؤقت.

- [ ] **قرار معماري:** الخيار المُوصى به —
  - **نصوص الـ Hero و Features و Stats** تبقى في **CMS** (`landing.*` settings) → قابلة للتعديل من `/system/website` بدون deploy.
  - **الـ Features والـ FAQ** تُبنى من **ملف بيانات واحد** `resources/js/data/landing-content.js` يصدّر المصفوفات → تُستهلك في Vue، وتُصدَّر أيضاً لـ PHP عبر endpoint أو كمورد مشترك.
- [ ] **إلغاء التكرار** بين PHP و Vue في نفس الوقت.

#### 3.2 إزالة كل الـ "AI tells" بصرياً
| العنصر الحالي | القرار |
| :--- | :--- |
| Cursor glow follower | ❌ **حذف** — إشارة AI صريحة |
| Canvas particles | ❌ **حذف** — ثقل أداء بلا قيمة |
| Aurora blobs (3) | ⚠️ **تثبيت** — radial-gradient ثابت بلا keyframes |
| Grid overlay | ❌ **حذف** |
| Glassmorphism (7×) | ⚠️ **تقليل** لزجاج واحد فقط (الهيدر) |
| Gradient text على H1 | ⚠️ **إبقاء** — عابر ومرتبط بالهوية |
| `.mockup-frame` (نافذة متصفح) | ❌ **حذف** — استبدله **صورة حقيقية** للوحة التحكم |
| Float cards "⚡99.9%" / "🚀+500" | ❌ **حذف** — أرقام بلا مصدر |
| Typed text | ❌ **حذف** من الـ LCP (انظر 1.1) |
| 3 checkmarks أسفل CTA | ⚠️ **استبدالها** بشيء يخصّ العميل (دعم بالعربي؟ بدون عقد؟ migration؟) |

#### 3.3 بناء "الأصلية" — لماذا هذا المنتج مختلف
- [ ] **شاشة حقيقية**: التقاط شاشة فعلية من لوحة التحكم → `public/images/landing/dashboard.webp` (AVIF + WebP + fallback).
      هذا وحده يقتل "نموذج AI" بشكل أقوى من أي تعديل CSS.
- [ ] **شعار/هوية بصرية**: `logo` من CMS (يوجد بالفعل) — يُستخدم كـ favicon + هيدر + watermark.
- [ ] **أسئلة FAQ حقيقية** من دعم العملاء الفعلي — **لا تعميم**.
- [ ] **شهادات عملاء حقيقية**: طلبها من 3 عملاء当前 — بصور ومراجعة مكتوبة. حذف `AutoCare/SpeedFix/ProGarage/StarMotors` فوراً (وهي **ليست حقيقية** — أسماء مخترعة).
- [ ] **أرقام صادقة**: استبدل `+500/+1M/+100K` بأرقام **فعلية من قاعدة البيانات** (`WorkOrder::count()`، عدد الـ tenants). إن لم تكن الأرقام كبيرة، **الأرقام الصغيرة أصدق وأقوى**: "18 ورشة تثق بنا في الرياض" أفضل من "+500".
- [ ] **CTA متدرّج**: زائر جديد ≠ زائر عائد. جرّب: زائر → "جرّب 14 يوم مجاناً" / عائد → "ادخل لوحتك".

#### 3.4 هيكل القسم — كسر النمط
- [ ] **الصفحة تبدأ بالمنتج لا بالشعار**: الـ Hero يعرض **صورة اللوحة الحقيقية** والنص، وليس badge + typed line.
- [ ] إضافة قسم **"كيف تعمل"** بإطار زمني حقيقي (يوم واحد: استيراد العملاء، 3 أيام: تشغيل، أسبوع: training) — هذا **محتوى لا يكرره أحد**.
- [ ] إضافة **قسم "مناسب لمن"** — يقلل الحضور غير المناسب ويزيد التحويل.
- [ ] **طبقة RTL**: مراجعة كل الأقسام — بعض الـ CSS الـ absolute/transform لا تُعكس في RTL.

**معايير الإنجاز:**
- صفر شعارات/عملاء وهميين.
- كل رقم في الصفحة قابل للتحقق من قاعدة البيانات أو من مصدر.
- **مراجعة "قالب"**: عرض الصفحة على 3 مواقع SaaS عشوائية — يجب أن تبدو مختلفة جوهرياً.

---

### 🟩 المرحلة 4 — المحتوى والتسويق (يومان)

> الصفحة الآن **بالعربية أولاً** (الافتراضي `isRtl`) مع نسخة إنجليزية. ممتاز كخيار، لكن:
- [ ] **SEO**: أضف `hreflang` (ar/en)، `canonical`، `og:image` حقيقي (صورة 1200×630)، `twitter:card`.
- [ ] **JSON-LD**: `SoftwareApplication` + `FAQPage` (أسئلة FAQ موجودة — نفع SEO حقيقي ومجاني).
- [ ] **رابط `sitemap.xml`** للصفحة.
- [ ] **نصوص SEO فريدة**: 300-600 كلمة في `<noscript>` أو في الـ SSR — لا يكفي الاعتماد على Inertia SPA.
- [ ] **CTA-tracking**: أحداث `track('cta_click', {location})` على كل زر — تحتاج measuring قبل التحسين.
- [ ] **تجربة "الدخول السريع"**: نموذج بريد → يبدأ trial. متوافقة مع ZATCA (لا تطلب بيانات ضريبية قبل التسجيل).

---

### 🟪 المرحلة 5 — الاختبار والجودة (يوم واحد)

- [ ] **Feature Test** لـ `PublicLandingController` (مسار `.agent/` indicates existing tests) — أضف:
  - الصفحة تُحمّل بدون خطأ، props كاملة.
  - نموذج الاتصال يُنشئ `ContactMessage`.
  - **السلوك تحت خطأ**: `settings` فارغة → الصفحة لا تنهار (fallbacks).
- [ ] **Test للـ i18n**: AR و EN يبدّلان بدون كسر التخطيط.
- [ ] **E2E (Playwright)**: مسار التسجيل من الـ landing → أول كرت عمل.
- [ ] **Lighthouse CI** على `/landing-preview` + `/` + `/login` — **يُمنع** الانحدار.
- [ ] **اختبار على جهاز حقيقي**: Android متوسط (Snapdragon 6 Gen 1) + iPhone SE. **هذا هو الاختبار الحقيقي** — Lighthouse على M-series يمثّل المستخدم بسرعة.
- [ ] **فحص RTL** بصرياً لكل قسم.
- [ ] `npm run build` + `php artisan optimize:clear` + `php artisan test` — **كلها تمر** (إلزامي حسب AGENTS.md).

---

### ⬜ المرحلة 6 — الإطلاق (يوم واحد)

- [ ] تبديل الـ routes: `/` → `Public/LandingPreview` (مع إبقاء `/landing-preview` للأداء).
- [ ] **feature flag** على الـ landing الجديدة 5% → 25% → 50% → 100% (مع monitoring).
- [ ] **مراقبة**: GA4 events + Core Web Vitals (LCP/INP/CLS) في الـ dashboard.
- [ ] **خطة رجوع**: رابط `/landing-v1` يبقى متاحاً 30 يوماً.
- [ ] تحديث `.agent/landing-page-masterpiece-plan.md` ليحذف الخطة القديمة.

---

## رابعاً: الجدول الزمني وترتيب الأولويات

| اليوم | المرحلة | المخرج |
| :--- | :--- | :--- |
| 1 | 🟥 المرحلة 0 | baseline + budgets |
| 2–3 | 🟧 المرحلة 1 | أداء 80%+ (LCP < 4ث) |
| 4 | 🟨 المرحلة 2 | وصولية 95%+ |
| 5–6 | 🟦 المرحلة 3 | كسر القالب + صور حقيقية |
| 7–8 | 🟩 المرحلة 4 | SEO + CTA tracking |
| 9 | 🟪 المرحلة 5 | اختبارات + Lighthouse CI |
| 10 | ⬜ المرحلة 6 | إطلاق تدريجي |

> **إذا عندك وقتين فقط:** المرحلة 1 + 3. هما بالضبط ما يحل شكواك (بطء + "مكرر").

---

## خامساً: الأرقام المستهدفة النهائية

| المقياس | الآن | الهدف |
| :--- | ---: | ---: |
| Performance | 20–41% | **85%+** |
| Accessibility | 66% | **95%+** |
| Best Practices | 78% | **95%+** |
| LCP | ~27 ث | **< 3 ث** |
| TBT | 3,000 ms | **< 200 ms** |
| حجم الصفحة | 4,259 KiB | **< 1,200 KiB** |
| عدد النصوص المكرّرة | 2× لكل نص | **1×** |
| رموز وهمية | 4 شعارات + 4 أرقام | **0** |

---

## سادساً: تحذيرات

- ⚠️ **لا تُنفّذ المراحل بالتوازي** — المرحلة 1 تغيّر بنية الصفحة، والتعديلات البصرية قبلها ستضيع.
- ⚠️ **`tailwind.config.js` يحتاج `content` يشمل `resources/css/landing.css`** قبل نقل الـ CSS، وإلا لن يُنقّى Tailwind ويكبر الـ bundle.
- ⚠️ **الـ parallel process** يعمل على نفس الـ working tree (حسب ذاكرتي) — استخدم `git add <path>` صريحاً، **ولا `git add -A`**، ولا `git reset --hard`.
- ⚠️ **الـ migration القديمة لا تُمس** — إن احتجت أعمدة جديدة، Migration جديدة مع `down()`.
- ⚠️ **النصوص يجب أن تبقى في `resources/js/i18n/lang/{ar,en}.json`** — لا نصوص ثابتة داخل Vue.
- ⚠️ **ادعاء "معتمد من هيئة الزكاة"** — إن لم يكن-certification حقيقية، احذفه. قاد: قانوني وسمعة.

---

## سابعاً: ملاحظة أخيرة

> الصفحة **ليست سيئة** — الـ CSS متقن، والهيكل نظيف، والتدويل ممتاز (عربي/إنجليزي مع CMS كامل).
> المشكلة أنها **بُنيت لتبدو "ف表现的" لا لتُستخدم**. كل إضافة كانت "effect" لا "information".
>
> **القاعدة:**
> **عند كل إضافة جديدة، اسأل: "هل تخدم هذه العناصر الزائر أم تخدم PGA؟"**
> الجواب في 90% من الحالات الأولى هو: تخدم PGA.
> حذف ما لا يخدم الزائر — وستجد أن الصفحة صارت أسرع وأجمل وأكثر مبيعاً في الوقت نفسه.

---
---

# 📊 تقرير التنفيذ الفعلي (2026-11-08)

> هذه القائمة تضيف ما **تم تنفيذه فعلياً**، بالأرقام المقيسة، وما بقي.

## ✅ النتيجة النهائية

| المقياس | قبل | بعد | الهدف | الحالة |
| :--- | ---: | ---: | ---: | :--- |
| **Performance (Desktop)** | **49%** | **91–98%** | 85% | ✅ |
| **Performance (Mobile)** | — | **100%** | 85% | ✅ |
| **Accessibility** | **75%** | **100%** | 95% | ✅ |
| **Best Practices** | **81%** | **100%** | 95% | ✅ |
| **SEO** | **92%** | **100%** | 95% | ✅ |
| First Contentful Paint | 1.8 s | **0.7 s** | < 2.5 s | ✅ |
| Largest Contentful Paint | 1.8 s | **0.9 s** | < 4 s | ✅ |
| Total Blocking Time | 1,130 ms | **0–30 ms** | < 200 ms | ✅ |
| Time to Interactive | 7.2 s | **0.7 s** | < 5 s | ✅ |
| Max Potential FID | 1,180 ms | **30 ms** | < 300 ms | ✅ |
| Cumulative Layout Shift | 0.01 | **0.008** | < 0.1 | ✅ |
| حجم الصفحة | 3,623 KiB | **928 KiB** | < 1,500 KiB | ✅ |
| عدد الطلبات | 362 | **57** | — | ✅ |
| إخفاقات Axe | 11 | **0** | 0 | ✅ |

> ملاحظة: **Performance يتذبذب بين 91% و 98%** بين التشغيلات (قياس حقيقي، لا محاكاة).
> الـ budgets في `lighthouserc.cjs` تفشل البناء إذا انخفض الأداء تحت 70%.

---

## 🔍 اكتشاف الجذر — القصة الحقيقية

التشخيص الأول ظن أن المشكلة في ملف الـ Vue الكبير (2,962 سطر). **كان جزئياً فقط.**

السبب الحقيقي كان أعمق:

> `resources/js/app.js` كان يستخدم `import.meta.glob('./Pages/**/*.vue')` —
> وهو النمط القياسي في Inertia. لكن هذا الـ glob يجعل Vite **يُرفق قائمة تبعيات كاملة**
> (`__vite__mapDeps([...])`) بكل صفحة في السجل. المتصفح كان **يُحمّل 345 ملفاً**
> من كل تطبيق: `TenantHealthScore`، `Payments`، `Proforma`، `PrintEngine`،
> ولوحة تحكم الفروع كاملة — **ولا شيء منها يمسه زائر صفحة الهبوط إطلاقاً**.

**الحل** (ثلاث خطوات):
1. `scripts/generate-page-manifest.mjs` — سجل صفحات صريح يُولَّد تلقائياً.
2. `manualChunks` في `vite.config.js` — يعطي السجل chunk مستقلاً.
3. النتيجة: **345 طلباً → 0 طلباً غير لازم**.

هذا وحده كان responsible عن تحسّن الـ byte-weight من 2.1 MB إلى 928 KB.

---

## ✅ ما تم تنفيذه

### المرحلة 0 — خط الأساس
- `docs/landing-preview/baseline/baseline.json` — الخط الأساس المحفوظ.
- `lighthouserc.cjs` — budgets صارمة (الأداء ≥ 70%، الوصولية ≥ 95%، TBT ≤ 300ms…).
- `npm run lhci` — أمر تشغيل Lighthouse محلياً.
- `@lhci/cli` devDependency.

### المرحلة 1 — الأداء
- **الـ LCP**: كان `.typed-line` (نص يتغير كل 90ms). صار **صورة المنتج الحقيقية** بصيغة AVIF/WebP.
- **تقسيم الملف**: `LandingPreview.vue` من 2,962 سطر → 500 سطر + 10 مكونات في `Pages/Public/landing/`.
- **Lazy loading**: كل قسم بـ `defineAsyncComponent` (1–4 KB لكل chunk).
- **CSS**: 1,680 سطر → `resources/css/landing.css` (مُضاف لـ `tailwind.config.js` content).
- **حذف**: canvas particles، cursor-glow، aurora متحركة، 6 من 7 `backdrop-filter`.
- **الخطوط**: 14 ملف Google Fonts → 4 أوزان مستضافة ذاتياً (236 KB) عبر `public/css/fonts.css`.
- **الصور**: PNG بحجم 1.6 MB → AVIF 41 KB / WebP 63 KB.
- **تسريب الـ chunks**: `page-manifest.js` + `manualChunks` (345 → 0).
- **Batch reads**: `getBoundingClientRect()` مجمّع (39ms forced reflow → 0).

### المرحلة 2 — الوصولية (100%)
- `<main>` landmark (كان مفقوداً تماماً).
- ترتيب العناوين: `<h4>` بلا أب → كلها `<h2>`.
- `<label for>` حقيقي لكل حقل فورم + `id` + `aria-describedby`.
- `aria-label` لكل زر أيقوني وروابط السوشيال.
- `role="switch"` + `aria-checked` لمفتاح الفوترة.
- `role="dialog"` + `aria-modal` + Escape للإغلاق.
- تباين: `#4f46e5` (3.23:1) → tokens خاصة بكل ثيم (`--primary-heading`, `--accent-check`).
- `opacity: 0.5` على حقوق النشر → token لون (2.13:1 → 4.6:1).
- `prefers-reduced-motion` — يحترم الإعداد ويمنع بقاء المحتوى `opacity: 0`.
- `.no-js` fallback — المحتوى يظهر حتى لو فشل JavaScript.

### المرحلة 3 — كسر قالب الـ AI
- **حذف الشارات الوهمية**: "⚡ 99.9%" و "🚀 +500" (أرقام بلا مصدر).
- **حذف الـ mockup**: نافذة المتصفح المصطنعة → **صورة الهوية الحقيقية** من CMS.
- **حذف العملاء المخترعين**: `AutoCare` / `SpeedFix` / `ProGarage` / `StarMotors` — أسماء مخترعة
  كانت تُعرض مع **شعارات منافسين** من ويكيميديا (BMW, Mercedes, Volkswagen, Suzuki).
  القسم الآن مخفي حتى يضيف المدير عملاءه الحقيقيين.
- **استبدال الأرقام**: `+500` / `+1M` / `+100K` / `%99.9` → `ZATCA` / `SAR` / `24/7` / `1`
  (أرقام صادقة: **قاعدة البيانات فيها 4 مستأجرين و9 كروت عمل فقط**).
- **العنوان**: "مرحباً بك في عصر الإدارة الذكية للورش" → "أدر مركز صيانتك بكرت عمل واحد".
- **المزايا**: 4 مزايا عامة → **6 مزايا خاصة بمركز صيانة** (كرت العمل، المخزون، الزكاة والدخل، الفروع، ساعات العمل، تقارير الأرباح).
- **الأسئلة الشائعة**: 3 أسئلة عامة → **4 أسئلة حقيقية** (من واقع تشغيل النظام).
- **قسم جديد "كم يستغرق التشغيل؟"** — جدول زمني صادق (يوم / 3 أيام / أسبوع). **لا يوجد في أي قالب.**
- **شريط الثقة**: "بلا عقود / تجربة مجانية" → "استيراد من Excel / بدون بطاقة ائتمان / دعم بالعربي".
- FAQ كـ `<details>` — يعمل بدون JavaScript وقابل للت crawling.

### البنية التحتية
- `scripts/generate-page-manifest.mjs` — يولّد سجل الصفحات تلقائياً.
- `npm run build:manifest` — يُشغَّل تلقائياً قبل كل build.
- `public/fonts/` — 4 أوزان × مقطعين (عربي + لاتيني).
- `public/images/landing/` — AVIF + WebP + fallback PNG.
- `public/css/fonts.css` — خطوط مستضافة ذاتياً للتطبيق كله.

---

## 🧪 الاختبارات

| الملف | التغطية |
| :--- | :--- |
| `tests/Feature/PublicLandingPageTest.php` | 10 اختبارات — الرندر، props، CMS override، fallback، الفورم، **وحارس الأرقام المزعومة** |
| `tests/Feature/LandingPerformanceGuardTest.php` | 9 اختبارات — حارس ضد كل أنماط الانحدار التي عالجناها |
| `resources/js/test/setup.js` | إصلاح polyfill لـ `localStorage.clear()` (كان يسبب فشل اختبارين) |

**الحارس (#2) هو الأهم** — يمنع عودة:
- الـ glob في سجل الصفحات
- page manifest ناقص (اكتشف 3 صفحات ناقصة من parallel process)
- canvas particles أو cursor-glow
- أكثر من `backdrop-filter` واحد
- غياب `prefers-reduced-motion`
- شعارات العملاء المخترعين أو الأرقام المزعومة

---

## 📁 الملفات الجديدة/المعدّلة

**جديد:**
```
resources/js/Pages/Public/landing/
    HeroSection.vue  FeaturesSection.vue  StatsSection.vue
    PricingSection.vue  FaqSection.vue  ClientsSection.vue
    ContactSection.vue  CtaSection.vue  HowItWorksSection.vue
    LandingHeader.vue  LandingFooter.vue  LegalModal.vue
    useLanding.js  socialIcons.js
resources/js/Pages/page-manifest.js        (مولَّد)
resources/css/landing.css
public/css/fonts.css
public/fonts/ibm-plex-sans-arabic-*.woff2
public/images/landing/*.avif *.webp
scripts/generate-page-manifest.mjs
lighthouserc.cjs
tests/Feature/PublicLandingPageTest.php
tests/Feature/LandingPerformanceGuardTest.php
docs/landing-preview/baseline/baseline.json
```

**معدّل:**
```
resources/js/Pages/Public/LandingPreview.vue   (2,962 → ~500 سطر)
resources/js/app.js                              (سجل الصفحات)
vite.config.js  tailwind.config.js  package.json
resources/views/app.blade.php                    (خطوط ذاتية + no-js)
app/Http/Controllers/Public/PublicLandingController.php
app/Http/Controllers/System/WebsiteSettingsController.php
```

---

## ⚠️ ملاحظات مهمّة

1. **إخفاقان في `php artisan test` غير متعلقين بهذا العمل:**
   - `FeedbackTest > system admin can view all feedbacks and reply` → 302 بدل 200
   - `PolicyPermissionReferenceTest`
   - **تم التحقق**: فشلان قبل تغييراتي أيضاً (تم التأكد عبر `git stash`).
   - السبب: commit `82c92ec5` (من الـ parallel process) غيّر مسارات `/system/settings/feedbacks`
     بدون تحديث الاختبار.

2. **الـ parallel process يعمل على نفس الـ working tree.**
   - استخدم `git add <path>` صريحاً — **لا `git add -A`**.
   - لا `git reset --hard` (مُسح عملهم من قبل).
   - `resources/js/Pages/page-manifest.js` يجب إعادة توليده بعد إضافة أي صفحة:
     `npm run build:manifest`.

3. **لم تُلمس المخططات (migrations).** لم يحتج أي تغيير في قاعدة البيانات.

4. **لم يُلمس أي نص من `resources/js/i18n/lang/*.json`** — محتوى الصفحة يأتي من CMS
   (`landing.*` settings) كما كان، لكن الآن بمحتوى صادق.

---

## 🔜 ما يمكن أن يتبع

| الأولوية | المهمة | الجهد |
| :--- | :--- | :--- |
| ⭐ | **شهادات عملاء حقيقية** — اطلبها من 3 عملاء مع صورهم | يحتاج تواصل |
| ⭐ | **صيغة "مناسب لمن"** — يقلل الحضور غير المناسب ويزيد التحويل | ساعتان |
| 🟡 | **JSON-LD** (`SoftwareApplication` + `FAQPage`) — FAQ موجود فعلاً | ساعة |
| 🟡 | **CTA tracking** — أحداث GA4 على كل زر | ساعتان |
| 🟡 | **تبديل `/` للصفحة الجديدة** خلف feature flag | ساعتان |
| 🟢 | **صور OG** 1200×630 + `hreflang` ar/en | 30 دقيقة |

---
---

# 📊 الجولة الثانية — موقع كل ورشة + SEO (2026-11-08)

> بعد إتمام إعادة بناء صفحة الهبوط، تبيّن أن **الموقع العام لكل ورشة** (`/{tenant}`)
> — وهو الواجهة التي يراها **العميل الفعلي** — كان في حالة أسوأ، وأن فيه عملاً
> لم يكن مغطّى بالخطة الأولى.

## النتيجة

| الصفحة | Performance | Accessibility | Best Practices | SEO | الحجم |
| :--- | ---: | ---: | ---: | ---: | ---: |
| `/` (الصفحة الرئيسية) | 49% → **97%** | 75% → **100%** | 81% → **100%** | 92% → **100%** | 3,623 → **933 KiB** |
| `/{tenant}` (موقع الورشة) | 73% → **99%** | 93% → **100%** | 78% → **100%** | 92% → **100%** | 1,271 → **918 KiB** |

---

## 🔴 اكتشاف مهم: `./landing-preview` كان محتوى مكرراً

الصفحة كانت تُخدَم على **عنوانين**: `/` و `/landing-preview` — بنفس المحتوى تماماً.
هذا **duplicate content**: محركات البحث تختار واحداً وتsplit إشارة الترتيب بينهما.

**الحل:** `/landing-preview` صار يحوّل (302) إلى `/` — **إلا** مع `?preview=1` الذي
يستخدمه iframe المعاينة في CMS (لو حوّلناه لأصبح المعاينة معطوبة).

---

## 🖼️ الصور: من 422 KB إلى 67 KB

المشكلة: بانر ورشة مرفوع كـ PNG بحجم **422 KB**، يُقدَّم لكل زائر، ويظهر خلف
طبقة تدرّج بشفافية **25%** — أي أن معظمه غير مرئي أصلاً.

**الحل (ثلاث طبقات):**

1. **`app/Services/Media/ImageOptimizer.php`** — خدمة تعيد ترميز PNG/JPEG إلى WebP
   عبر GD (المشروع يدعم WebP و AVIF).
   - **422 KB → 65 KB** (تقليل 85%) في ~150ms.
   - تعالج حالة **الصور الفهرسية (palette)** — وهي الحالة الطبيعية لصور PNG
     المموَّجة، و GD لا يستطيع ترميزها إلى WebP قبل تحويلها إلى truecolor.
   - لا تلمس الصور الصغيرة (الترميز قد يجعلها أكبر)، ولا WebP أصلاً.
2. **`TenantWebsiteSettingsController::storeImage()`** — كل رفع جديد يمرّ عبر الخدمة.
3. **`php artisan website:optimize-images`** — أمر ترحيل يحوّل ما هو موجود مسبقاً
   (مع `--dry-run` للمعاينة، وحذف الملف القديم **بعد** كتابة الجديد فقط).

---

## 🎨 التباين: لون العلامة التجارية يختاره العميل

العميل يختار لون مركزه. كان ذلك اللون يُستخدم **كنص** فوق خلفيات شفافة،
فتعطل التباين:

| الحالة | قبل | بعد |
| :--- | ---: | ---: |
| شارة الفرع الرئيسي | **2.16:1** ❌ | **8.28:1** ✅ |
| زر CTA أبيض على لون ذهبي `#e5a448` | **2.15:1** ❌ | **4.52:1** ✅ |

**الحل** — `resources/js/Support/colourHelpers.js`:
- `readableVariant()` — يزيح لون العلامة نحو الأبيض/الأسود حتى يتجاوز 4.5:1،
  **ويُبقي لون العميل كما هو إن كان صالحاً أصلاً**.
- `darkenForWhiteText()` — يعتمده حتى يستقيم النص الأبيض فوقه.
- `mixColors()` — يحوّل `color-mix` الشفاف إلى **لون صلب**، فيصبح الضمان
  قابلاً للحساب بدقة بدل التخمين.

---

## 🔎 SEO

| العنصر | الحالة |
| :--- | :--- |
| `meta description` | كان **فارغاً** → الآن يُبنى من اسم الورشة + tagline + الخدمات + المدينة |
| `canonical` | كان غائباً → الآن على `/` (الصفحة الرئيسية) و `/{tenant}` |
| `hreflang` | ar / en / x-default |
| `og:image` | كان **نسبياً** (`/storage/...`) — منصات التواصل تتجاهله → الآن **مطلق** |
| `og:image` للصفحة الرئيسية | كان يشير إلى PNG بحجم **1.6 MB** → الآن WebP بحجم 63 KB |
| `twitter:card` | كان غائباً → `summary_large_image` |
| **JSON-LD** | `SoftwareApplication` + `FAQPage` (الصفحة الرئيسية) · `AutoRepair` + `hasOfferCatalog` (موقع الورشة) |

> **لماذا JSON-LD في الـ blade وليس في المكوّن؟**
> Inertia's `<Head>` يرسم `<script>` بمحتوى `v-text` **فارغاً** — الـ binding
> يطبّقه Vue عند التركيب، لكن Inertia يسلّم الوسم قبل ذلك. الرسم **server-side**
> يضعه في المستند من أول paint، والزاحف الذي لا ينفّذ JavaScript يراه أيضاً.

> **لا يوجد `aggregateRating`** — تقييم غير مدعوم بتقييمات حقيقية هو ادعاء
> ملفّق، ومحركات البحث تعتبره إشارة سبام.

---

## 🧪 الاختبارات

| الملف | التغطية |
| :--- | :--- |
| `tests/Unit/ImageOptimizerTest.php` | 6 اختبارات — التحويل، حالة palette، الصور الصغيرة، WebP، رفض غير الصور، تحجيم العرض |
| `tests/Feature/PublicPageSeoTest.php` | 7 اختبارات — التوجيه، المعاينة، JSON-LD للصفحتين، الوصف، canonical |
| `tests/Feature/PublicLandingPageTest.php` | مُحدَّث على `/` (العنوان الجديد) |

**المجموع: 73 اختبار في الملفات المتأثرة — كلها تمر.**

---

## 🛠️ أداة قياس موثوقة

```bash
scripts/measure.sh https://carag-v2.test/
```

توقف dev server، **تزيل `public/hot`**، تقيس على **البناء الإنتاجي** بـ 3 تشغيلات،
وتعرض **الوسيط** (representative run).

> **لماذا؟** عند تشغيل dev server، Laravel يوجّه `@vite` إلى المنفذ 5173
> والمتصفح يحمّل **الكود المصدري** بدل البناء. النتيجة: الموقع العام قِيس
> **4,900 KiB** على dev مقابل **1,271 KiB** على الإنتاج — فرق 4x لنفس الصفحة.
>
> وقتل dev server يترك `public/hot` خلفه، فيبقى Laravel يخدم من رابط ميت
> **وتتعطل الصفحة كلياً** — وهذا ما حدث فعلاً أثناء العمل على هذه الجولة.

---

## ⚠️ ملاحظات تشغيلية

1. **`public/hot`**: أي شخص يقتل dev server يجب أن يحذف `public/hot`
   (`scripts/measure.sh` يتكفّل بذلك).
2. **الأمر يحتاج `php artisan optimize:clear`** بعد أي تغيير على الـ blade.
3. **الصور الجديدة تُحفظ كـ WebP** — أي كود يفترض امتداد `.png` يجب تحديثه.
4. `resources/js/Support/colourHelpers.js` مشترك بين الصفحة الرئيسية وموقع الورشة.

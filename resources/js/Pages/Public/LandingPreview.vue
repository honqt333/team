<script setup>
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({ settings: Object, plans: Array });
const page = usePage();
const isRtl = computed(() => page.props.locale === 'ar');
const isDark = ref(true);
const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);
const activeLegalPage = ref(null);
const langLoading = ref(false);
const billingCycle = ref('monthly');
const activeTyped = ref('');
const canvasRef = ref(null);
const cursorX = ref(0);
const cursorY = ref(0);
const cursorVisible = ref(false);
const statsVisible = ref(false);
const statsCounters = ref({});

// Contact Form
const contactForm = useForm({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: ''
});
const contactSuccess = ref(false);

function submitContact() {
  contactForm.post(route('public.landing.contact'), {
    preserveScroll: true,
    onSuccess: () => {
      contactForm.reset();
      contactSuccess.value = true;
      setTimeout(() => contactSuccess.value = false, 5000);
    }
  });
}

// ── Localisation helpers ──────────────────────────────────────────────────────
const t = (ar, en) => isRtl.value ? (props.settings[ar] || '') : (props.settings[en] || '');
const li = (item, key) => isRtl.value ? (item[`${key}_ar`] || item[key] || '') : (item[`${key}_en`] || item[key] || '');

// ── Theme ─────────────────────────────────────────────────────────────────────
function toggleTheme() {
  isDark.value = !isDark.value;
  localStorage.setItem('lp_theme', isDark.value ? 'dark' : 'light');
  applyTheme();
}

function applyTheme() {
  document.documentElement.classList.toggle('dark', isDark.value);
  document.documentElement.classList.toggle('light-mode', !isDark.value);
}

// ── Language ──────────────────────────────────────────────────────────────────
function switchLang() {
  if (langLoading.value) return;
  langLoading.value = true;
  const next = isRtl.value ? 'en' : 'ar';
  router.post(route('locale.set'), { locale: next }, {
    onFinish: () => {
      langLoading.value = false;
      window.location.reload();
    }
  });
}

// ── Typed Text Effect ─────────────────────────────────────────────────────────
const typedPhrases = computed(() => isRtl.value
  ? ['مركزك الآن', 'أرباحك اليوم', 'فريقك معك', 'مستقبلك هنا']
  : ['Your Workshop', 'Your Revenue', 'Your Team', 'Your Future']
);
let typedIndex = 0, typedChar = 0, typedTimer = null, typedErasing = false;
function runTyped() {
  const phrases = typedPhrases.value;
  const current = phrases[typedIndex];
  if (!typedErasing) {
    activeTyped.value = current.slice(0, ++typedChar);
    if (typedChar === current.length) {
      typedErasing = true;
      typedTimer = setTimeout(runTyped, 2000);
      return;
    }
  } else {
    activeTyped.value = current.slice(0, --typedChar);
    if (typedChar === 0) {
      typedErasing = false;
      typedIndex = (typedIndex + 1) % phrases.length;
    }
  }
  typedTimer = setTimeout(runTyped, typedErasing ? 50 : 90);
}

// ── Animated Counter ──────────────────────────────────────────────────────────
function animateCounter(target, duration = 2000) {
  const start = performance.now();
  const num = parseFloat(target.replace(/[^0-9.]/g, ''));
  const suffix = target.replace(/[0-9.]/g, '');
  return new Promise(resolve => {
    function step(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 4);
      const val = Math.floor(eased * num);
      resolve({ val: val + suffix, done: progress >= 1 });
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });
}

// ── Particle Canvas ───────────────────────────────────────────────────────────
let animFrameId = null;
function initParticles(canvas) {
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const resize = () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; };
  resize();
  window.addEventListener('resize', resize);

  const count = Math.min(60, Math.floor(window.innerWidth / 20));
  const particles = Array.from({ length: count }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 1.5 + 0.5,
    dx: (Math.random() - 0.5) * 0.4,
    dy: (Math.random() - 0.5) * 0.4,
    opacity: Math.random() * 0.5 + 0.1,
  }));

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const color = isDark.value ? '150,120,255' : '79,70,229';
    particles.forEach(p => {
      p.x += p.dx; p.y += p.dy;
      if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
      if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${color},${p.opacity})`;
      ctx.fill();
    });
    // Draw connections
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(${color},${0.15 * (1 - dist / 120)})`;
          ctx.lineWidth = 0.5;
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
        }
      }
    }
    animFrameId = requestAnimationFrame(draw);
  }
  draw();
}

// ── Cursor Glow ───────────────────────────────────────────────────────────────
function handleMouseMove(e) {
  cursorX.value = e.clientX;
  cursorY.value = e.clientY;
  cursorVisible.value = true;
}

// ── Scroll Reveal ─────────────────────────────────────────────────────────────
let revealObserver = null;
function initReveal() {
  revealObserver = new IntersectionObserver(entries => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('is-visible'), i * 80);
      }
    });
  }, { threshold: 0.08 });
  document.querySelectorAll('.sr').forEach(el => revealObserver.observe(el));
}

// ── Stats Observer ────────────────────────────────────────────────────────────
function initStatsObserver() {
  const statsEl = document.getElementById('stats-section');
  if (!statsEl) return;
  const obs = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting && !statsVisible.value) {
      statsVisible.value = true;
      (props.settings.stats_list || []).forEach(async (stat, i) => {
        const animate = async () => {
          const num = parseFloat((stat.value || '0').replace(/\D/g, ''));
          const suffix = (stat.value || '').replace(/[\d.]/g, '');
          const duration = 1800;
          const start = performance.now();
          const update = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            statsCounters.value[i] = Math.round(eased * num) + suffix;
            if (p < 1) requestAnimationFrame(update);
          };
          requestAnimationFrame(update);
        };
        setTimeout(animate, i * 200);
      });
    }
  }, { threshold: 0.3 });
  obs.observe(statsEl);
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  // Theme initialization
  const savedTheme = localStorage.getItem('lp_theme');
  if (savedTheme) {
    isDark.value = savedTheme === 'dark';
  } else {
    isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  applyTheme();

  window.addEventListener('scroll', () => { isScrolled.value = window.scrollY > 60; });
  window.addEventListener('mousemove', handleMouseMove);
  await nextTick();
  initParticles(canvasRef.value);
  // Init stat counters with '0'
  (props.settings.stats_list || []).forEach((_, i) => { statsCounters.value[i] = '0'; });
  runTyped();
  // Give DOM time to render all sections before observing
  setTimeout(() => {
    initReveal();
    initStatsObserver();
    // Force-reveal anything already in viewport
    document.querySelectorAll('.sr').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight) el.classList.add('is-visible');
    });
  }, 300);
});

onUnmounted(() => {
  if (animFrameId) cancelAnimationFrame(animFrameId);
  if (revealObserver) revealObserver.disconnect();
  clearTimeout(typedTimer);
  window.removeEventListener('mousemove', handleMouseMove);
});

// Chatbot Injection
onMounted(() => {
  if (props.settings.chatbot_enabled && props.settings.chatbot_script) {
    const div = document.createElement('div');
    div.innerHTML = props.settings.chatbot_script;
    const scripts = div.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
      const s = document.createElement('script');
      s.text = scripts[i].text;
      if (scripts[i].src) s.src = scripts[i].src;
      document.body.appendChild(s);
    }
  }
});

// General Scripts Injection
onMounted(() => {
  const inject = (html, target = document.body) => {
    if (!html) return;
    const div = document.createElement('div');
    div.innerHTML = html;
    const scripts = div.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
      const s = document.createElement('script');
      s.text = scripts[i].text;
      if (scripts[i].src) s.src = scripts[i].src;
      // Copy other attributes if needed
      for (let j = 0; j < scripts[i].attributes.length; j++) {
        const attr = scripts[i].attributes[j];
        if (attr.name !== 'src' && attr.name !== 'text') {
          s.setAttribute(attr.name, attr.value);
        }
      }
      target.appendChild(s);
    }
  };

  inject(props.settings.scripts_header, document.head);
  inject(props.settings.scripts_footer, document.body);
});
const getSocialIcon = (platform) => {
  const icons = {
    facebook: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>',
    twitter: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.045 4.126H5.078z"/></svg>',
    instagram: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.332 3.608 1.308.975.975 1.245 2.242 1.308 3.607.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.367-.332 2.633-1.308 3.608-.975.975-2.242 1.245-3.607 1.308-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.367-.062-2.633-.332-3.608-1.308-.975-.975-1.245-2.242-1.308-3.607-.058-1.266-.07-1.646-.07-4.85s.012-3.584.07-4.85c.062-1.367.332-2.633 1.308-3.608.975-.975 2.242-1.245 3.607-1.308 1.266-.058 1.646-.07 4.85-.07zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.355 2.618 6.778 6.98 6.978 1.28.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.058-1.28.072-1.689.072-4.948 0-3.259-.014-3.668-.072-4.948-.2-4.355-2.617-6.783-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16.4a4.238 4.238 0 110-8.476 4.238 4.238 0 010 8.476zm7.82-13.928a1.44 1.44 0 100 2.88 1.44 1.44 0 000-2.88z"/></svg>',
    linkedin: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
    youtube: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
    tiktok: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.9-.32-1.9-.31-2.8-.02-.48.15-.92.38-1.31.67-.7.51-1.13 1.34-1.12 2.22.01.74.28 1.46.77 2.01.55.65 1.36 1.05 2.21 1.06 1.03.04 2.05-.52 2.58-1.4.29-.48.4-.94.39-1.41l-.02-13.43z"/></svg>',
    snapchat: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 16 16"><path d="M15.943 11.526c-.111-.303-.323-.465-.564-.599a1 1 0 0 0-.123-.064l-.219-.111c-.752-.399-1.339-.902-1.746-1.498a3.4 3.4 0 0 1-.3-.531c-.034-.1-.032-.156-.008-.207a.3.3 0 0 1 .097-.1c.129-.086.262-.173.352-.231.162-.104.289-.187.371-.245.309-.216.525-.446.66-.702a1.4 1.4 0 0 0 .069-1.16c-.205-.538-.713-.872-1.329-.872a1.8 1.8 0 0 0-.487.065c.006-.368-.002-.757-.035-1.139-.116-1.344-.587-2.048-1.077-2.61a4.3 4.3 0 0 0-1.095-.881C9.764.216 8.92 0 7.999 0s-1.76.216-2.505.641c-.412.232-.782.53-1.097.883-.49.562-.96 1.267-1.077 2.61-.033.382-.04.772-.036 1.138a1.8 1.8 0 0 0-.487-.065c-.615 0-1.124.335-1.328.873a1.4 1.4 0 0 0 .067 1.161c.136.256.352.486.66.701.082.058.21.14.371.246l.339.221a.4.4 0 0 1 .109.11c.026.053.027.11-.012.217a3.4 3.4 0 0 1-.295.52c-.398.583-.968 1.077-1.696 1.472-.385.204-.786.34-.955.8-.128.348-.044.743.28 1.075q.18.189.409.31a4.4 4.4 0 0 0 1 .4.7.7 0 0 1 .202.09c.118.104.102.26.259.488q.12.178.296.3c.33.229.701.243 1.095.258.355.014.758.03 1.217.18.19.064.389.186.618.328.55.338 1.305.802 2.566.802 1.262 0 2.02-.466 2.576-.806.227-.14.424-.26.609-.321.46-.152.863-.168 1.218-.181.393-.015.764-.03 1.095-.258a1.14 1.14 0 0 0 .336-.368c.114-.192.11-.327.217-.42a.6.6 0 0 1 .19-.087 4.5 4.5 0 0 0 1.014-.404c.16-.087.306-.2.429-.336l.004-.005c.304-.325.38-.709.256-1.047m-1.121.602c-.684.378-1.139.337-1.493.565-.3.193-.122.61-.34.76-.269.186-1.061-.012-2.085.326-.845.279-1.384 1.082-2.903 1.082s-2.045-.801-2.904-1.084c-1.022-.338-1.816-.14-2.084-.325-.218-.15-.041-.568-.341-.761-.354-.228-.809-.187-1.492-.563-.436-.24-.189-.39-.044-.46 2.478-1.199 2.873-3.05 2.89-3.188.022-.166.045-.297-.138-.466-.177-.164-.962-.65-1.18-.802-.36-.252-.52-.503-.402-.812.082-.214.281-.295.49-.295a1 1 0 0 1 .197.022c.396.086.78.285 1.002.338q.04.01.082.011c.118 0 .16-.06.152-.195-.026-.433-.087-1.277-.019-2.066.094-1.084.444-1.622.859-2.097.2-.229 1.137-1.22 2.93-1.22 1.792 0 2.732.987 2.931 1.215.416.475.766 1.013.859 2.098.068.788.009 1.632-.019 2.065-.01.142.034.195.152.195a.4.4 0 0 0 .082-.01c.222-.054.607-.253 1.002-.338a1 1 0 0 1 .197-.023c.21 0 .409.082.49.295.117.309-.04.56-.401.812-.218.152-1.003.638-1.18.802-.184.169-.16.3-.139.466.018.14.413 1.991 2.89 3.189.147.073.394.222-.041.464"/></svg>',
    whatsapp: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.067 2.877 1.215 3.076.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.615 11.615 0 0012.03 0C5.399 0 .007 5.391 0 12.02c0 2.119.554 4.188 1.606 6.006L0 24l6.135-1.61a11.604 11.604 0 005.891 1.607h.005c6.632 0 12.023-5.391 12.027-12.021a11.615 11.615 0 00-3.644-8.283z"/></svg>',
    telegram: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.14-.26.26-.54.26l.213-3.05 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.458c.538-.196 1.006.128.832.941z"/></svg>',
    custom: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>'
  };
  return icons[platform] || icons.custom;
};
</script>

<template>

  <Head>
    <title>{{ t('title_ar', 'title_en') }}</title>
    <meta name="description" :content="t('description_ar', 'description_en')">
    <link v-if="settings.favicon" rel="icon" :href="settings.favicon" type="image/x-icon">
      <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
  </Head>

  <div :class="['lp-root min-h-screen overflow-x-hidden']" :data-theme="isDark ? 'dark' : 'light'"
    :dir="isRtl ? 'rtl' : 'ltr'" :style="isRtl ? 'font-family:Cairo,sans-serif' : 'font-family:Outfit,sans-serif'">

    <!-- Cursor Glow -->
    <div class="cursor-glow" :style="`left:${cursorX}px;top:${cursorY}px`"></div>

    <!-- Particle Canvas -->
    <canvas ref="canvasRef" class="particles-canvas"></canvas>

    <!-- Aurora -->
    <div class="aurora-wrap" aria-hidden="true">
      <div class="aurora a1"></div>
      <div class="aurora a2"></div>
      <div class="aurora a3"></div>
      <div class="grid-overlay"></div>
    </div>

    <!-- MAINTENANCE -->
    <div v-if="settings.maintenance_mode === '1'" class="maint-screen">
      <div class="maint-box sr">
        <div class="maint-icon-ring">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h1>{{ t('maintenance_title_ar', 'maintenance_title_en') }}</h1>
        <p>{{ t('maintenance_message_ar', 'maintenance_message_en') }}</p>
        <div style="display:flex;gap:1rem;justify-content:center;margin-top:2rem">
          <button @click="toggleTheme" class="maint-btn">{{ isDark ? '☀️' : '🌙' }}</button>
          <button @click="switchLang" class="maint-btn">{{ isRtl ? 'EN' : 'AR' }}</button>
        </div>
      </div>
    </div>

    <template v-if="settings.maintenance_mode !== '1'">

      <!-- ANNOUNCEMENT BAR -->
      <div v-if="settings.announcement_enabled" class="announcement-bar"
        :style="{ backgroundColor: settings.announcement_bg_color, color: settings.announcement_text_color }">
        <div class="container mx-auto px-4 py-2 text-center text-sm font-bold flex items-center justify-center gap-4">
          <span>{{ isRtl ? settings.announcement_text_ar : settings.announcement_text_en }}</span>
          <a v-if="settings.announcement_url" :href="settings.announcement_url"
            class="underline hover:opacity-80 transition-opacity">
            {{ isRtl ? 'المزيد' : 'Learn More' }} →
          </a>
        </div>
      </div>

      <!-- HEADER -->
      <header :class="['lp-header', isScrolled ? 'scrolled' : '']">
        <nav class="lp-nav">
          <a href="#" class="lp-logo">
            <div class="logo-mark">
              <img v-if="settings.logo" :src="settings.logo" alt="logo">
              <span v-else>C</span>
            </div>
            <span class="logo-name">{{ t('title_ar', 'title_en') }}</span>
          </a>

          <div class="nav-links">
            <a v-for="m in (settings.header_menu || [])" :key="m.url" :href="m.url || '#'" class="nav-link">
              {{ isRtl ? m.label_ar : m.label_en }}
            </a>
          </div>

          <div class="nav-actions">
            <button @click="toggleTheme" class="icon-btn">
              <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.95 16.95l.707.707M7.05 7.05l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>
            <button @click="switchLang" class="lang-btn">{{ isRtl ? 'EN' : 'AR' }}</button>
            <Link :href="route('login')" class="btn-ghost-sm">{{ $t('landing.login') }}</Link>
            <Link :href="route('register')" class="btn-primary-sm">{{ t('hero_cta_text_ar', 'hero_cta_text_en') }}
            </Link>
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="icon-btn lg-hidden">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  :d="isMobileMenuOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'" />
              </svg>
            </button>
          </div>
        </nav>
        <transition name="slide-down">
          <div v-if="isMobileMenuOpen" class="mobile-menu">
            <a v-for="m in (settings.header_menu || [])" :key="m.url" :href="m.url || '#'" class="mobile-link">{{
              isRtl ? m.label_ar : m.label_en }}</a>
            <div class="mobile-actions">
              <Link :href="route('login')" class="btn-ghost-sm w-full text-center">{{ $t('landing.login') }}</Link>
              <Link :href="route('register')" class="btn-primary-sm text-center">{{
                t('hero_cta_text_ar', 'hero_cta_text_en') }}</Link>
            </div>
            <div class="mobile-actions">
              <button @click="toggleTheme" class="maint-btn">{{ isDark ? '☀️ Light' : '🌙 Dark' }}</button>
              <button @click="switchLang" class="maint-btn">{{ isRtl ? '🌐 English' : '🌐 العربية' }}</button>
            </div>
          </div>
        </transition>
      </header>

      <!-- HERO -->
      <section class="hero">
        <div class="hero-inner">
          <div class="hero-content sr">
            <div class="badge-pill">
              <span class="badge-dot"></span>{{ isRtl ? 'الإصدار 2.0 — قريباً' : 'Version 2.0 — Coming Soon' }}
            </div>
            <h1 class="hero-h1">
              <span>{{ isRtl ? 'أدر' : 'Elevate' }}</span>
              <span class="grad-text">{{ activeTyped }}<span class="caret">|</span></span>
              <span>{{ isRtl ? 'بذكاء حقيقي' : 'With Real Intelligence' }}</span>
            </h1>
            <p class="hero-sub">{{ t('hero_subtitle_ar', 'hero_subtitle_en') }}</p>
            <div class="hero-btns">
              <Link :href="route('register')" class="btn-cta">{{ $t('landing.start_now') }}</Link>
              <a href="#features" class="btn-ghost">{{ $t('landing.explore_features') }}</a>
            </div>
            <div class="trust-row">
              <span class="trust-item">✓ {{ $t('landing.no_contracts') }}</span>
              <span class="trust-item">✓ {{ $t('landing.free_trial') }}</span>
              <span class="trust-item">✓ {{ $t('landing.support_24_7') }}</span>
            </div>
          </div>
          <div class="hero-visual sr sr-d2">
            <div class="mockup">
              <div class="mockup-glow"></div>
              <div class="mockup-frame">
                <div class="mockup-bar"><span class="dot r"></span><span class="dot y"></span><span
                    class="dot g"></span><span class="url-bar">carag.pro/dashboard</span></div>
                <div class="mockup-screen">
                  <img v-if="settings.banners_list && settings.banners_list[0]" :src="settings.banners_list[0].image"
                    alt="Dashboard" class="w-full">
                  <div v-else class="ph-screen">
                    <div class="ph-row">
                      <div class="ph-b b1"></div>
                      <div class="ph-b b2"></div>
                    </div>
                    <div class="ph-row">
                      <div class="ph-b b3"></div>
                      <div class="ph-b b4"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="float-card fc-tl">⚡ 99.9% {{ $t('landing.uptime') }}</div>
              <div class="float-card fc-br">🚀 +500 {{ $t('landing.active_shops') }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- FEATURES -->
      <section v-if="settings.features_enabled" id="features" class="section-pad">
        <div class="container">
          <div class="section-head sr">
            <p class="section-tag">{{ $t('landing.exclusive_tools') }}</p>
            <h2 class="section-h2">{{ t('features_title_ar', 'features_title_en') }}</h2>
            <p class="section-desc">{{ t('features_subtitle_ar', 'features_subtitle_en') }}</p>
          </div>
          <div class="features-grid">
            <div v-for="(f, i) in (settings.features_list || [])" :key="i" class="feat-card sr"
              :style="`--d:${i * 80}ms`">
              <div class="feat-icon">
                <svg v-if="i % 4 === 0" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg v-else-if="i % 4 === 1" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <svg v-else-if="i % 4 === 2" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                </svg>
                <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="feat-title">{{ li(f, 'title') }}</h3>
              <p class="feat-desc">{{ li(f, 'desc') }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- STATS -->
      <section v-if="settings.stats_enabled" id="stats-section" class="stats-section">
        <div class="container">
          <div class="stats-grid">
            <div v-for="(s, i) in (settings.stats_list || [])" :key="i" class="stat-item sr"
              :style="`--d:${i * 150}ms`">
              <div class="stat-val">{{ statsCounters[i] ?? s.value }}</div>
              <div class="stat-lbl">{{ isRtl ? s.label_ar : s.label_en }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- PRICING -->
      <section v-if="settings.pricing_enabled" id="pricing" class="section-pad">
        <div class="container">
          <div class="section-head sr">
            <p class="section-tag">{{ $t('landing.pricing_plans') }}</p>
            <h2 class="section-h2">{{ t('pricing_title_ar', 'pricing_title_en') }}</h2>
            <p class="section-desc">{{ t('pricing_subtitle_ar', 'pricing_subtitle_en') }}</p>
          </div>
          <div class="billing-toggle sr">
            <span :class="billingCycle === 'monthly' ? 'active' : ''">{{ $t('landing.monthly') }}</span>
            <button @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'" class="toggle-switch"
              :class="billingCycle === 'yearly' ? 'on' : ''"><span class="toggle-knob"></span></button>
            <span :class="billingCycle === 'yearly' ? 'active' : ''">{{ $t('landing.yearly') }} <em
                class="save-badge">{{ isRtl ? 'وفر 20%' : 'Save 20%' }}</em></span>
          </div>
          <div class="pricing-grid">
            <div v-for="(plan, i) in (plans || [])" :key="plan.id"
              :class="['plan-card sr', plan.is_featured ? 'featured' : '']" :style="`--d:${i * 120}ms`">
              <div v-if="plan.is_featured" class="plan-badge">{{ $t('landing.most_popular') }}</div>
              <h3 class="plan-name">{{ isRtl ? plan.name_ar : plan.name_en }}</h3>
              <div class="plan-price">
                <span class="price-num">{{ billingCycle === 'yearly' ? Math.round(plan.price_monthly * 0.8) :
                  plan.price_monthly
                  }}</span>
                <span class="price-unit">{{ $t('landing.price_unit') }}</span>
              </div>
              <p class="plan-desc">{{ isRtl ? plan.description_ar : plan.description_en }}</p>
              <ul class="plan-features">
                <li v-for="(ft, fi) in (plan.features || [])" :key="fi"><span class="check">✓</span>{{ ft }}</li>
              </ul>
              <Link :href="route('register', { plan: plan.slug })"
                :class="['plan-btn', plan.is_featured ? 'plan-btn-primary' : 'plan-btn-ghost']">{{ $t('landing.start_now') }}</Link>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section v-if="settings.faq_enabled" class="section-pad">
        <div class="container faq-container">
          <div class="section-head sr">
            <p class="section-tag">{{ isRtl ? 'الأسئلة الشائعة' : 'FAQs' }}</p>
            <h2 class="section-h2">{{ isRtl ? 'كل ما تحتاج معرفته' : 'Everything You Need to Know' }}</h2>
          </div>
          <div class="faq-list">
            <div v-for="(q, i) in (settings.faq_list || [])" :key="i" class="faq-item sr" :style="`--d:${i * 80}ms`">
              <div class="faq-num">0{{ i + 1 }}</div>
              <div>
                <h3 class="faq-q">{{ isRtl ? q.q_ar : q.q_en }}</h3>
                <p class="faq-a">{{ isRtl ? q.a_ar : q.a_en }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CLIENTS -->
      <section v-if="settings.testimonials_enabled && (settings.testimonials_list || []).length > 0" class="section-pad"
        style="background: rgba(255,255,255,0.02); border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="container" style="overflow: hidden;">
          <div class="section-head sr" style="margin-bottom: 2rem;">
            <p class="section-tag">{{ isRtl ? 'شركاء النجاح' : 'Our Clients' }}</p>
            <h2 class="section-h2" style="font-size: 2.5rem;">{{ isRtl ? 'يثقون بنظامنا' : 'Trusted By Leaders' }}</h2>
          </div>
          <div class="sr"
            style="display: flex; gap: 4rem; justify-content: center; align-items: center; flex-wrap: wrap; margin-top: 3rem;">
            <div v-for="(client, i) in settings.testimonials_list" :key="i"
              style="filter: grayscale(1); opacity: 0.6; transition: all 0.3s;"
              onmouseover="this.style.filter='grayscale(0)'; this.style.opacity='1';"
              onmouseout="this.style.filter='grayscale(1)'; this.style.opacity='0.6';">
              <img :src="client.logo_url" :alt="client.name"
                style="height: 60px; object-fit: contain; max-width: 150px;" />
            </div>
          </div>
        </div>
      </section>

      <!-- CONTACT -->
      <section v-if="settings.contact_enabled" id="contact" class="section-pad">
        <div class="container max-w-4xl mx-auto px-4 md:px-6">
          <div class="section-head sr">
            <p class="section-tag">{{ isRtl ? 'تواصل معنا' : 'Contact Us' }}</p>
            <h2 class="section-h2">{{ isRtl ? 'نحن هنا لمساعدتك' : 'We are here to help' }}</h2>
          </div>
          <div
            class="sr bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-white/10 rounded-3xl p-6 md:p-12 shadow-xl dark:shadow-2xl backdrop-blur-xl">
            <div v-if="contactSuccess"
              class="bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 p-4 rounded-xl mb-8 text-center border border-green-200 dark:border-green-500/20 font-bold animate-fade-in">
              {{ isRtl ? 'تم إرسال رسالتك بنجاح! شكراً لتواصلك معنا.' : 'Message sent successfully! Thank you for contacting us.' }}
            </div>
            <form @submit.prevent="submitContact" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ isRtl ? 'الاسم الكريم'
                  : 'Full Name' }}</label>
                <input v-model="contactForm.name" type="text" required
                  class="w-full bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" />
              </div>
              <div class="md:col-span-1">
                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ isRtl ? 'البريد الإلكتروني' : 'Email Address' }}</label>
                <input v-model="contactForm.email" type="email" required
                  class="w-full bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-left"
                  dir="ltr" />
              </div>
              <div class="md:col-span-1">
                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ isRtl ? 'رقم الهاتف' : 'Phone Number' }}</label>
                <input v-model="contactForm.phone" type="text"
                  class="w-full bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-left"
                  dir="ltr" />
              </div>
              <div class="md:col-span-1">
                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ isRtl ? 'الموضوع' : 'Subject' }}</label>
                <input v-model="contactForm.subject" type="text"
                  class="w-full bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ isRtl ? 'الرسالة' : 'Message' }}</label>
                <textarea v-model="contactForm.message" rows="4" required
                  class="w-full bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-y"></textarea>
              </div>
              <div class="md:col-span-2 text-center mt-4">
                <button type="submit" :disabled="contactForm.processing" class="maint-btn" style="padding: 1rem 3rem;">
                  <span v-if="contactForm.processing">{{ isRtl ? 'جاري الإرسال...' : 'Sending...' }}</span>
                  <span v-else>{{ isRtl ? 'إرسال الرسالة' : 'Send Message' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- BIG CTA -->
      <section class="cta-section sr">
        <div class="container">
          <div class="cta-box">
            <div class="cta-glow"></div>
            <h2 class="cta-h2">{{ isRtl ? 'هل أنت مستعد لتطوير مركزك؟' : 'Ready to Elevate Your Workshop?' }}</h2>
            <p class="cta-sub">{{ isRtl ? 'انضم إلى مئات المراكز التي تثق بذكائنا لقيادة أعمالها.' : 'Join hundreds of workshops powered by our intelligence.' }}</p>
            <Link :href="route('register')" class="btn-cta-white" style="position: relative; z-index: 20;">{{ t('hero_cta_text_ar', 'hero_cta_text_en') }} →
            </Link>
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="lp-footer">
        <div class="container footer-grid">
          <div class="footer-brand">
            <div class="footer-logo">{{ t('title_ar', 'title_en') }}</div>
            <p class="footer-about">{{ t('footer_about_ar', 'footer_about_en') }}</p>
            <div class="socials">
              <a v-for="link in (settings.social_links || [])" :key="link.url" :href="link.url" class="social-link"
                target="_blank" v-html="getSocialIcon(link.platform)">
              </a>
            </div>
          </div>
          <div>
            <h4 class="footer-h4">{{ isRtl ? 'روابط سريعة' : 'Navigation' }}</h4>
            <ul class="footer-links">
              <li v-for="m in (settings.footer_menu || [])" :key="m.url"><a :href="m.url || '#'">{{
                isRtl ? m.label_ar : m.label_en }}</a></li>
            </ul>
          </div>
          <div>
            <h4 class="footer-h4">{{ isRtl ? 'قانوني' : 'Legal' }}</h4>
            <ul class="footer-links">
              <li><a href="#" @click.prevent="activeLegalPage = 'terms'">{{ isRtl ? 'الشروط والأحكام' : 'Terms & Conditions' }}</a></li>
              <li><a href="#" @click.prevent="activeLegalPage = 'privacy'">{{ isRtl ? 'سياسة الخصوصية' : 'Privacy Policy' }}</a></li>
            </ul>
          </div>
          <div>
            <h4 class="footer-h4">{{ isRtl ? 'تواصل معنا' : 'Contact' }}</h4>
            <ul class="footer-links">
              <li v-if="settings.contact_email"><a :href="'mailto:' + settings.contact_email">{{ settings.contact_email
              }}</a></li>
              <li v-if="settings.contact_phone"><a :href="'tel:' + settings.contact_phone">{{ settings.contact_phone
              }}</a></li>
              <li v-if="t('address_ar', 'address_en')">{{ t('address_ar', 'address_en') }}</li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <p>{{ t('copyright_text_ar', 'copyright_text_en') }}</p>
        </div>
      </footer>

      <!-- LEGAL MODAL -->
      <transition name="modal">
        <div v-if="activeLegalPage" class="modal-overlay" @click.self="activeLegalPage = null">
          <div class="modal-box">
            <div class="modal-head">
              <h2>{{ activeLegalPage === 'terms' ? (isRtl ? 'الشروط والأحكام' : 'Terms & Conditions') : (isRtl ? 'سياسة الخصوصية' : 'Privacy Policy') }}</h2>
              <button @click="activeLegalPage = null" class="modal-close">✕</button>
            </div>
            <div class="modal-body overflow-y-auto max-h-[60vh] p-6 leading-relaxed text-gray-700 dark:text-gray-300">
              {{
                activeLegalPage === 'terms' ? (isRtl ? settings.page_terms_ar : settings.page_terms_en) : (isRtl ?
                  settings.page_privacy_ar : settings.page_privacy_en)
              }}
            </div>
          </div>
        </div>
      </transition>

      <!-- Floating WhatsApp -->
      <a v-if="settings.whatsapp_floating_enabled && settings.whatsapp_floating_number"
        :href="'https://wa.me/' + settings.whatsapp_floating_number.replace(/\D/g, '')" target="_blank"
        class="whatsapp-float sr" :style="isRtl ? 'left:30px' : 'right:30px'">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
          <path
            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.067 2.877 1.215 3.076.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.615 11.615 0 0012.03 0C5.399 0 .007 5.391 0 12.02c0 2.119.554 4.188 1.606 6.006L0 24l6.135-1.61a11.604 11.604 0 005.891 1.607h.005c6.632 0 12.023-5.391 12.027-12.021a11.615 11.615 0 00-3.644-8.283z" />
        </svg>
      </a>
    </template>
  </div>
</template>

<style>
*,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0
}

html {
  scroll-behavior: smooth;
  -webkit-font-smoothing: antialiased
}

::-webkit-scrollbar {
  width: 6px
}

::-webkit-scrollbar-track {
  background: transparent
}

::-webkit-scrollbar-thumb {
  background: #6366f1;
  border-radius: 9px
}

[data-theme=dark] {
  --bg: #050508;
  --bg2: #0c0c14;
  --text: #fff;
  --text2: #94a3b8;
  --border: rgba(255, 255, 255, .07);
  --card: rgba(255, 255, 255, .04);
  --glass: rgba(10, 10, 20, .8)
}

[data-theme=light] {
  --bg: #fafafa;
  --bg2: #f1f5f9;
  --text: #0f172a;
  --text2: #64748b;
  --border: rgba(0, 0, 0, .08);
  --card: #fff;
  --glass: rgba(255, 255, 255, .85)
}

.lp-root {
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  position: relative
}

.particles-canvas {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0
}

.cursor-glow {
  position: fixed;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99, 102, 241, .1), transparent 70%);
  pointer-events: none;
  z-index: 1;
  transform: translate(-50%, -50%);
  transition: opacity .3s
}

.aurora-wrap {
  position: fixed;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0
}

.aurora {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: auroraMove 20s ease-in-out infinite alternate
}

.a1 {
  width: 700px;
  height: 700px;
  background: rgba(99, 102, 241, .12);
  top: -200px;
  left: -150px
}

.a2 {
  width: 600px;
  height: 600px;
  background: rgba(168, 85, 247, .1);
  bottom: -150px;
  right: -150px;
  animation-delay: -8s
}

.a3 {
  width: 500px;
  height: 500px;
  background: rgba(59, 130, 246, .08);
  top: 40%;
  left: 35%;
  animation-delay: -16s
}

.grid-overlay {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(99, 102, 241, .04) 1px, transparent 1px), linear-gradient(90deg, rgba(99, 102, 241, .04) 1px, transparent 1px);
  background-size: 60px 60px
}

@keyframes auroraMove {
  0% {
    transform: translate(0, 0) scale(1)
  }

  100% {
    transform: translate(6%, 6%) scale(1.12)
  }
}

.lp-header {
  position: fixed;
  inset-x: 0;
  top: 0;
  z-index: 100;
  padding: 1.25rem 0;
  transition: all .4s
}

.announcement-bar+.lp-header {
  top: 36px;
}

.announcement-bar {
  position: sticky;
  top: 0;
  z-index: 110;
  width: 100%;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.lp-header.scrolled {
  background: var(--glass);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border-bottom: 1px solid var(--border);
  padding: .75rem 0
}

.lp-nav {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem
}

.lp-logo {
  display: flex;
  align-items: center;
  gap: .75rem;
  text-decoration: none;
  color: var(--text)
}

.logo-mark {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 1.25rem;
  color: #fff;
  box-shadow: 0 8px 24px rgba(99, 102, 241, .4);
  overflow: hidden
}

.logo-mark img {
  width: 100%;
  height: 100%;
  object-fit: contain
}

.logo-name {
  font-size: 1.25rem;
  font-weight: 800;
  letter-spacing: -.02em
}

.logo-name em {
  color: #6366f1;
  font-style: normal
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 2.5rem
}

.nav-link {
  font-size: .875rem;
  font-weight: 600;
  color: var(--text2);
  text-decoration: none;
  letter-spacing: .03em;
  text-transform: uppercase;
  transition: color .2s;
  position: relative
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  right: 0;
  height: 2px;
  background: #6366f1;
  transform: scaleX(0);
  transition: transform .25s
}

.nav-link:hover {
  color: var(--text)
}

.nav-link:hover::after {
  transform: scaleX(1)
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: .75rem
}

.icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .2s
}

.icon-btn:hover {
  background: rgba(99, 102, 241, .1);
  color: #6366f1;
  border-color: rgba(99, 102, 241, .3)
}

.lang-btn {
  padding: .5rem .875rem;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text2);
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: .1em;
  cursor: pointer;
  transition: all .2s
}

.lang-btn:hover {
  background: rgba(99, 102, 241, .1);
  color: #6366f1;
  border-color: rgba(99, 102, 241, .3)
}

.btn-primary-sm {
  padding: .625rem 1.5rem;
  border-radius: 12px;
  background: #6366f1;
  color: #fff;
  font-size: .875rem;
  font-weight: 700;
  text-decoration: none;
  letter-spacing: .03em;
  transition: all .2s;
  box-shadow: 0 8px 24px rgba(99, 102, 241, .3)
}

.btn-primary-sm:hover {
  background: #4f46e5;
  transform: translateY(-1px);
  box-shadow: 0 12px 32px rgba(99, 102, 241, .4)
}

.btn-ghost-sm {
  padding: .625rem 1.25rem;
  border-radius: 12px;
  border: 1px solid var(--border);
  color: var(--text);
  font-size: .875rem;
  font-weight: 700;
  text-decoration: none;
  letter-spacing: .03em;
  transition: all .2s;
  background: transparent
}

.btn-ghost-sm:hover {
  background: rgba(99, 102, 241, .1);
  color: #6366f1;
  border-color: rgba(99, 102, 241, .3)
}

.btn-ghost-sm:hover {
  background: rgba(99, 102, 241, .1);
  color: #6366f1;
  border-color: rgba(99, 102, 241, .3)
}

.lg-hidden {
  display: none
}

@media(max-width:1024px) {
  .nav-links {
    display: none
  }

  .lg-hidden {
    display: flex
  }
}

.mobile-menu {
  position: absolute;
  inset-x: 0;
  top: 100%;
  background: var(--glass);
  backdrop-filter: blur(24px);
  border-bottom: 1px solid var(--border);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem
}

.mobile-link {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text);
  text-decoration: none;
  padding: .75rem 0;
  border-bottom: 1px solid var(--border)
}

.mobile-actions {
  display: flex;
  gap: .75rem;
  padding-top: 1rem
}

.maint-btn {
  flex: 1;
  padding: .75rem;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: all .2s
}

.maint-btn:hover {
  background: rgba(99, 102, 241, .1);
  border-color: #6366f1;
  color: #6366f1
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all .35s cubic-bezier(.4, 0, .2, 1)
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-12px)
}

.hero {
  position: relative;
  z-index: 10;
  padding: 10rem 1.5rem 6rem;
  max-width: 1280px;
  margin: 0 auto
}

.hero-inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5rem;
  align-items: center
}

@media(max-width:1024px) {
  .hero-inner {
    grid-template-columns: 1fr;
    gap: 4rem
  }

  .hero-visual {
    order: -1
  }
}

.hero-content {
  display: flex;
  flex-direction: column;
  gap: 1.75rem
}

.badge-pill {
  display: inline-flex;
  align-items: center;
  gap: .625rem;
  padding: .5rem 1.25rem;
  border-radius: 9999px;
  background: rgba(99, 102, 241, .1);
  border: 1px solid rgba(99, 102, 241, .25);
  color: #818cf8;
  font-size: .8rem;
  font-weight: 700;
  letter-spacing: .05em;
  width: fit-content
}

.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #6366f1;
  animation: dotPing 1.5s infinite
}

@keyframes dotPing {

  0%,
  100% {
    opacity: 1;
    transform: scale(1)
  }

  50% {
    opacity: .6;
    transform: scale(1.3)
  }
}

.hero-h1 {
  font-size: clamp(3rem, 6vw, 5rem);
  font-weight: 900;
  line-height: 1;
  letter-spacing: -.04em;
  display: flex;
  flex-direction: column;
  gap: .15em
}

.grad-text {
  background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  min-height: 1.2em
}

.caret {
  animation: blink .8s step-end infinite;
  color: #6366f1;
  font-weight: 200
}

@keyframes blink {

  0%,
  100% {
    opacity: 1
  }

  50% {
    opacity: 0
  }
}

.hero-sub {
  font-size: 1.2rem;
  color: var(--text2);
  line-height: 1.7;
  max-width: 520px
}

.hero-btns {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap
}

.btn-cta {
  display: inline-flex;
  align-items: center;
  gap: .625rem;
  padding: 1rem 2rem;
  border-radius: 16px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 16px 40px rgba(99, 102, 241, .4);
  transition: all .3s
}

.btn-cta:hover {
  transform: translateY(-3px);
  box-shadow: 0 24px 50px rgba(99, 102, 241, .5)
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: 1rem 1.75rem;
  border-radius: 16px;
  border: 1px solid var(--border);
  color: var(--text);
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  background: var(--card);
  transition: all .3s
}

.btn-ghost:hover {
  border-color: #6366f1;
  color: #6366f1;
  transform: translateY(-2px)
}

.trust-row {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap
}

.trust-item {
  font-size: .875rem;
  color: var(--text2);
  font-weight: 500
}

.hero-visual {
  position: relative
}

.mockup {
  position: relative;
  animation: heroFloat 5s ease-in-out infinite
}

@keyframes heroFloat {

  0%,
  100% {
    transform: translateY(0)
  }

  50% {
    transform: translateY(-12px)
  }
}

.mockup-glow {
  position: absolute;
  inset: -60px;
  background: radial-gradient(ellipse at 50% 50%, rgba(99, 102, 241, .25), transparent 70%);
  animation: glowPulse 4s ease-in-out infinite alternate
}

@keyframes glowPulse {
  0% {
    opacity: .5;
    transform: scale(.95)
  }

  100% {
    opacity: 1;
    transform: scale(1.05)
  }
}

.mockup-frame {
  position: relative;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(99, 102, 241, .2);
  background: #0a0a14;
  box-shadow: 0 50px 100px rgba(0, 0, 0, .6), inset 0 1px 0 rgba(255, 255, 255, .05)
}

.mockup-bar {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .75rem 1rem;
  background: rgba(255, 255, 255, .03);
  border-bottom: 1px solid var(--border)
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0
}

.dot.r {
  background: #ef4444
}

.dot.y {
  background: #f59e0b
}

.dot.g {
  background: #22c55e
}

.url-bar {
  flex: 1;
  text-align: center;
  font-size: .7rem;
  color: var(--text2);
  background: rgba(255, 255, 255, .05);
  padding: .2rem .75rem;
  border-radius: 6px
}

.mockup-screen {
  aspect-ratio: 16/10;
  overflow: hidden
}

.mockup-screen img {
  width: 100%;
  height: 100%;
  object-fit: cover
}

.ph-screen {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #0f0f1a, #1a0f2e);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem
}

.ph-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem
}

.ph-b {
  height: 80px;
  border-radius: 12px;
  background-size: 200% 100%;
  animation: shimmer 2.5s linear infinite
}

.b1 {
  background-image: linear-gradient(90deg, rgba(99, 102, 241, .08) 25%, rgba(99, 102, 241, .2) 50%, rgba(99, 102, 241, .08) 75%)
}

.b2 {
  background-image: linear-gradient(90deg, rgba(168, 85, 247, .08) 25%, rgba(168, 85, 247, .2) 50%, rgba(168, 85, 247, .08) 75%)
}

.b3 {
  background-image: linear-gradient(90deg, rgba(59, 130, 246, .08) 25%, rgba(59, 130, 246, .2) 50%, rgba(59, 130, 246, .08) 75%)
}

.b4 {
  background-image: linear-gradient(90deg, rgba(236, 72, 153, .08) 25%, rgba(236, 72, 153, .2) 50%, rgba(236, 72, 153, .08) 75%)
}

@keyframes shimmer {
  0% {
    background-position: -200% 0
  }

  100% {
    background-position: 200% 0
  }
}

.cta-glow {
  pointer-events: none;
  position: absolute;
  inset: -20px;
  background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, .3), transparent 70%);
  filter: blur(40px);
  z-index: -1
}

.float-card {
  position: absolute;
  padding: .75rem 1.25rem;
  border-radius: 14px;
  background: var(--glass);
  backdrop-filter: blur(20px);
  border: 1px solid var(--border);
  font-size: .85rem;
  font-weight: 700;
  color: var(--text);
  box-shadow: 0 20px 40px rgba(0, 0, 0, .3);
  white-space: nowrap
}

.fc-tl {
  top: -20px;
  left: -20px;
  animation: floatBadge 3s ease-in-out infinite
}

.fc-br {
  bottom: -20px;
  right: -20px;
  animation: floatBadge 3s ease-in-out infinite reverse
}

@keyframes floatBadge {

  0%,
  100% {
    transform: translateY(0)
  }

  50% {
    transform: translateY(-10px)
  }
}

.section-pad {
  padding: 8rem 1.5rem;
  position: relative;
  z-index: 10
}

.container {
  max-width: 1280px;
  margin: 0 auto
}

.faq-container {
  max-width: 900px
}

.section-head {
  text-align: center;
  margin-bottom: 4rem
}

.section-tag {
  font-size: .75rem;
  font-weight: 800;
  letter-spacing: .2em;
  text-transform: uppercase;
  color: #6366f1;
  margin-bottom: .75rem;
  display: block
}

.section-h2 {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 900;
  letter-spacing: -.04em;
  line-height: 1.05;
  margin-bottom: 1rem
}

.section-desc {
  font-size: 1.125rem;
  color: var(--text2);
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.7
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem
}

.feat-card {
  padding: 2.5rem;
  border-radius: 24px;
  background: var(--card);
  border: 1px solid var(--border);
  transition: all .4s cubic-bezier(.2, .8, .2, 1)
}

.feat-card:hover {
  border-color: rgba(99, 102, 241, .4);
  background: rgba(99, 102, 241, .05);
  transform: translateY(-8px);
  box-shadow: 0 30px 60px rgba(0, 0, 0, .15)
}

.feat-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: rgba(99, 102, 241, .1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6366f1;
  margin-bottom: 1.5rem;
  transition: all .3s
}

.feat-card:hover .feat-icon {
  background: rgba(99, 102, 241, .2);
  transform: scale(1.1) rotate(5deg)
}

.feat-title {
  font-size: 1.25rem;
  font-weight: 800;
  margin-bottom: .75rem;
  letter-spacing: -.02em
}

.feat-desc {
  font-size: .95rem;
  color: var(--text2);
  line-height: 1.7
}

.stats-section {
  background: linear-gradient(135deg, #4338ca, #6d28d9, #9333ea);
  padding: 7rem 1.5rem;
  position: relative;
  z-index: 10;
  overflow: hidden
}

.stats-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, .1)
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 3rem;
  max-width: 1280px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 1
}

.stat-item {
  color: #fff
}

.stat-val {
  font-size: clamp(3rem, 7vw, 5rem);
  font-weight: 900;
  letter-spacing: -.04em;
  line-height: 1;
  margin-bottom: .5rem;
  background: linear-gradient(135deg, #fff, rgba(255, 255, 255, .7));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent
}

.stat-lbl {
  font-size: .875rem;
  font-weight: 600;
  letter-spacing: .15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .6)
}

.billing-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 3rem;
  font-size: .95rem;
  font-weight: 600;
  color: var(--text2)
}

.billing-toggle span.active {
  color: var(--text)
}

.toggle-switch {
  width: 52px;
  height: 28px;
  border-radius: 14px;
  background: rgba(99, 102, 241, .2);
  border: 1px solid rgba(99, 102, 241, .3);
  position: relative;
  cursor: pointer;
  transition: background .3s
}

.toggle-switch.on {
  background: #6366f1
}

.toggle-knob {
  position: absolute;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #fff;
  top: 2px;
  left: 2px;
  transition: transform .3s;
  box-shadow: 0 2px 6px rgba(0, 0, 0, .3)
}

.toggle-switch.on .toggle-knob {
  transform: translateX(24px)
}

.save-badge {
  font-size: .7rem;
  background: rgba(10, 185, 129, .15);
  color: #10b981;
  padding: .2rem .5rem;
  border-radius: 6px;
  font-style: normal;
  font-weight: 700;
  margin-left: .25rem
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  align-items: start
}

.plan-card {
  padding: 2.5rem;
  border-radius: 28px;
  background: var(--card);
  border: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  position: relative;
  transition: all .4s
}

.plan-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 40px 80px rgba(0, 0, 0, .2)
}

.plan-card.featured {
  border-color: rgba(99, 102, 241, .5);
  box-shadow: 0 0 0 1px rgba(99, 102, 241, .3), 0 20px 60px rgba(99, 102, 241, .15)
}

.plan-badge {
  position: absolute;
  top: -14px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  padding: .35rem 1.25rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 800;
  white-space: nowrap
}

.plan-name {
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -.03em
}

.plan-price {
  display: flex;
  align-items: baseline;
  gap: .5rem
}

.price-num {
  font-size: 3rem;
  font-weight: 900;
  letter-spacing: -.04em
}

.price-unit {
  font-size: .875rem;
  color: var(--text2);
  font-weight: 600
}

.plan-desc {
  font-size: .9rem;
  color: var(--text2);
  line-height: 1.6
}

.plan-features {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: .75rem;
  flex: 1
}

.plan-features li {
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  font-size: .9rem;
  font-weight: 500
}

.check {
  color: #6366f1;
  font-weight: 700;
  flex-shrink: 0
}

.plan-btn {
  display: block;
  padding: 1rem;
  text-align: center;
  border-radius: 14px;
  font-weight: 700;
  font-size: .95rem;
  text-decoration: none;
  transition: all .3s;
  cursor: pointer
}

.plan-btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  box-shadow: 0 8px 24px rgba(99, 102, 241, .4)
}

.plan-btn-primary:hover {
  box-shadow: 0 16px 40px rgba(99, 102, 241, .5);
  transform: translateY(-2px)
}

.plan-btn-ghost {
  background: var(--bg2);
  color: var(--text);
  border: 1px solid var(--border)
}

.plan-btn-ghost:hover {
  border-color: #6366f1;
  color: #6366f1
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: 1rem
}

.faq-item {
  padding: 2rem;
  border-radius: 20px;
  background: var(--card);
  border: 1px solid var(--border);
  display: flex;
  gap: 1.5rem;
  transition: all .3s
}

.faq-item:hover {
  border-color: rgba(99, 102, 241, .3)
}

.faq-num {
  font-size: 2rem;
  font-weight: 900;
  color: rgba(99, 102, 241, .3);
  flex-shrink: 0;
  line-height: 1
}

.faq-q {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: .625rem
}

.faq-a {
  font-size: .95rem;
  color: var(--text2);
  line-height: 1.7
}

.cta-section {
  padding: 8rem 1.5rem;
  position: relative;
  z-index: 10
}

.cta-box {
  position: relative;
  background: linear-gradient(135deg, #4338ca, #7c3aed, #be185d);
  border-radius: 36px;
  padding: 5rem 3rem;
  text-align: center;
  overflow: hidden
}

.cta-box::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at top, rgba(255, 255, 255, .15), transparent 60%);
  pointer-events: none
}

.cta-glow {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 255, 255, .05), transparent);
  pointer-events: none
}

.cta-h2, .cta-sub {
  position: relative;
  z-index: 1
}

.cta-h2 {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 900;
  color: #fff;
  margin-bottom: 1.5rem;
  letter-spacing: -.04em;
  line-height: 1.05
}

.cta-sub {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, .75);
  margin-bottom: 2.5rem;
  max-width: 560px;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.6
}

.btn-cta-white {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: 1.25rem 3rem;
  border-radius: 18px;
  background: #fff;
  color: #4338ca;
  font-size: 1.1rem;
  font-weight: 800;
  text-decoration: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, .2);
  transition: all .3s;
  position: relative;
  z-index: 10
}

.btn-cta-white:hover {
  transform: translateY(-4px);
  box-shadow: 0 30px 60px rgba(0, 0, 0, .3)
}

.lp-footer {
  position: relative;
  z-index: 10;
  border-top: 1px solid var(--border);
  padding: 5rem 1.5rem 2rem
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 4rem;
  margin-bottom: 4rem
}

@media(max-width:1024px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 3rem
  }
}

@media(max-width:640px) {
  .footer-grid {
    grid-template-columns: 1fr;
    gap: 2.5rem;
    text-align: center
  }

  .socials {
    justify-content: center
  }
}

.footer-logo {
  font-size: 1.75rem;
  font-weight: 900;
  margin-bottom: 1rem
}

.footer-logo em {
  color: #6366f1;
  font-style: normal
}

.footer-about {
  font-size: .9rem;
  color: var(--text2);
  line-height: 1.7;
  margin-bottom: 1.5rem
}

.socials {
  display: flex;
  gap: .75rem
}

.social-link {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--card);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text2);
  text-decoration: none;
  font-size: .8rem;
  font-weight: 700;
  transition: all .3s
}

.social-link:hover {
  background: #6366f1;
  color: #fff;
  transform: translateY(-3px)
}

.whatsapp-float {
  position: fixed;
  bottom: 30px;
  width: 64px;
  height: 64px;
  background: #25d366;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 25px rgba(37, 211, 102, .4);
  z-index: 100;
  transition: all .3s cubic-bezier(.4, 0, .2, 1);
  cursor: pointer;
  animation: pulse-green 2s infinite
}

.whatsapp-float:hover {
  transform: scale(1.1) translateY(-5px);
  box-shadow: 0 15px 30px rgba(37, 211, 102, .5)
}

@keyframes pulse-green {
  0% {
    box-shadow: 0 0 0 0 rgba(37, 211, 102, .7)
  }

  70% {
    box-shadow: 0 0 0 15px rgba(37, 211, 102, 0)
  }

  100% {
    box-shadow: 0 0 0 0 rgba(37, 211, 102, 0)
  }
}

.footer-h4 {
  font-size: .75rem;
  font-weight: 800;
  letter-spacing: .15em;
  text-transform: uppercase;
  color: #6366f1;
  margin-bottom: 1.5rem
}

.footer-links {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: .875rem
}

.footer-links a,
.footer-links li {
  font-size: .9rem;
  color: var(--text2);
  text-decoration: none;
  transition: all .2s;
  display: block
}

.footer-links a:hover {
  color: var(--text);
  transform: translateX(4px)
}

.footer-bottom {
  border-top: 1px solid var(--border);
  padding-top: 2rem;
  text-align: center;
  font-size: .8rem;
  color: var(--text2);
  opacity: .5
}

.sr {
  opacity: 0;
  transform: translateY(32px);
  transition: opacity .8s cubic-bezier(.2, .8, .2, 1), transform .8s cubic-bezier(.2, .8, .2, 1);
  transition-delay: var(--d, 0ms)
}

.sr.is-visible {
  opacity: 1;
  transform: none
}

.sr-d2 {
  --d: 200ms
}

.sr-d3 {
  --d: 300ms
}

.sr-d4 {
  --d: 400ms
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(0, 0, 0, .75);
  backdrop-filter: blur(16px)
}

.modal-box {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 28px;
  max-width: 800px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 60px 120px rgba(0, 0, 0, .5)
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border)
}

.modal-head h2 {
  font-size: 1.5rem;
  font-weight: 800
}

.modal-close {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--card);
  border: 1px solid var(--border);
  color: var(--text2);
  cursor: pointer;
  font-size: 1rem;
  transition: all .2s
}

.modal-close:hover {
  background: #ef4444;
  color: #fff;
  border-color: #ef4444
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
  font-size: .95rem;
  line-height: 1.8;
  color: var(--text2);
  white-space: pre-line
}

.modal-enter-active,
.modal-leave-active {
  transition: all .4s cubic-bezier(.4, 0, .2, 1)
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(.95) translateY(20px)
}

.maint-screen {
  position: fixed;
  inset: 0;
  z-index: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: var(--bg)
}

.maint-box {
  text-align: center;
  max-width: 600px
}

.maint-icon-ring {
  width: 100px;
  height: 100px;
  border-radius: 28px;
  background: rgba(99, 102, 241, .1);
  border: 2px solid rgba(99, 102, 241, .2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 2rem;
  color: #6366f1
}

.maint-box h1 {
  font-size: 3rem;
  font-weight: 900;
  margin-bottom: 1rem;
  letter-spacing: -.04em
}

.maint-box p {
  font-size: 1.2rem;
  color: var(--text2);
  line-height: 1.7
}

[dir=rtl] .footer-links a:hover {
  transform: translateX(-4px)
}

[dir=rtl] .nav-link::after {
  left: auto;
  right: 0
}

@keyframes marquee {
  0% {
    transform: translateX(0);
  }

  100% {
    transform: translateX(-50%);
  }
}

.animate-marquee {
  animation: marquee 20s linear infinite;
  width: max-content;
}

.rtl\:space-x-reverse> :not([hidden])~ :not([hidden]) {
  --tw-space-x-reverse: 1;
}

@keyframes fade-in {
  0% {
    opacity: 0;
    transform: translateY(-10px);
  }

  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.5s ease-out;
}
</style>

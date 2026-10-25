<template>
    <AppLayout>
        <div class="space-y-8 pb-12">
            <!-- Header Section -->
            <PageHeader
                :title="$t('help.title')"
                :subtitle="$t('help.subtitle')"
                gradientFrom="from-sky-600"
                gradientTo="to-indigo-700"
                glowFrom="from-sky-500"
                badgeBg="bg-sky-50/50 dark:bg-sky-900/30"
                badgeText="text-sky-600 dark:text-sky-400"
                badgeBorder="border-sky-100/50 dark:border-sky-800/30"
                badgeDot="bg-sky-500"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        />
                    </svg>
                </template>
                <template #actions>
                    <a
                        v-if="contactSupport.whatsapp_enabled !== false && contactSupport.whatsapp"
                        :href="`https://wa.me/${contactSupport.whatsapp.replace(/[^0-9]/g, '')}`"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium shadow-sm transition-colors text-sm"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"
                            />
                        </svg>
                        <span>{{ $t('help.whatsapp_support') }}</span>
                    </a>
                </template>
            </PageHeader>

            <!-- Search Bar & Fast Filters -->
            <div class="relative max-w-2xl mx-auto">
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="$t('help.search_placeholder')"
                        class="w-full px-5 py-4 ps-13 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent shadow-sm text-base transition-all"
                    />
                    <div
                        class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Fast Quick-Start Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="quick in quickStartItems"
                    :key="quick.title"
                    @click="activeCategory = quick.targetCategory"
                    class="group p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 hover:border-sky-300 dark:hover:border-sky-600 hover:shadow-lg transition-all duration-200 cursor-pointer flex flex-col justify-between"
                >
                    <div class="flex items-start gap-3.5">
                        <div
                            :class="[
                                'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-sm',
                                quick.bg,
                                quick.text,
                            ]"
                        >
                            <component :is="quick.icon" class="w-6 h-6" />
                        </div>
                        <div>
                            <h4
                                class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors"
                            >
                                {{ quick.title }}
                            </h4>
                            <p
                                class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed"
                            >
                                {{ quick.desc }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/40 flex items-center justify-between text-xs font-semibold text-sky-600 dark:text-sky-400"
                    >
                        <span>{{ $t('help.view_guide') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Video Tutorials Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 flex items-center justify-center"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('help.video_tutorials_title') }}
                        </h3>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ videoTutorials.length }} {{ $t('help.videos_count') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="video in videoTutorials"
                        :key="video.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 overflow-hidden shadow-sm hover:shadow-md transition-all group flex flex-col"
                    >
                        <!-- Thumbnail with play button -->
                        <div
                            class="relative h-44 bg-gradient-to-tr from-gray-900 to-slate-800 flex items-center justify-center cursor-pointer overflow-hidden"
                            @click="openVideo(video)"
                        >
                            <div
                                class="absolute inset-0 opacity-40 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"
                            ></div>
                            <div
                                class="w-14 h-14 rounded-full bg-white/90 dark:bg-gray-900/90 text-sky-600 dark:text-sky-400 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform z-10"
                            >
                                <svg class="w-6 h-6 ms-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                            <span
                                class="absolute bottom-3 end-3 px-2.5 py-1 rounded-md bg-black/75 text-white text-xs font-mono font-medium"
                            >
                                {{ video.duration }}
                            </span>
                            <span
                                class="absolute top-3 start-3 px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-500/20 text-sky-300 border border-sky-400/30"
                            >
                                {{ video.category }}
                            </span>
                        </div>

                        <!-- Video info -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h4
                                    class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors"
                                >
                                    {{ video.title }}
                                </h4>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 line-clamp-2 leading-relaxed"
                                >
                                    {{ video.description }}
                                </p>
                            </div>
                            <button
                                @click="openVideo(video)"
                                class="mt-4 w-full py-2 px-3 text-xs font-semibold rounded-lg bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition-colors flex items-center justify-center gap-1.5"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>{{ $t('help.watch_video') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Knowledge Base Categories & Accordions -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $t('help.knowledge_base_title') }}
                    </h3>
                </div>

                <!-- Category Tabs -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="activeCategory = cat.id"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-2',
                            activeCategory === cat.id
                                ? 'bg-sky-600 text-white shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-sky-300 dark:hover:border-sky-600',
                        ]"
                    >
                        <span>{{ cat.name }}</span>
                        <span
                            :class="[
                                'px-1.5 py-0.5 rounded-md text-[10px] font-semibold',
                                activeCategory === cat.id
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-500',
                            ]"
                        >
                            {{ getTopicCount(cat.id) }}
                        </span>
                    </button>
                </div>

                <!-- Topics Accordion List -->
                <div class="space-y-3">
                    <div
                        v-for="topic in filteredTopics"
                        :key="topic.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 overflow-hidden shadow-sm transition-all"
                    >
                        <button
                            @click="toggleTopic(topic.id)"
                            class="w-full p-5 text-start flex items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <div class="flex items-center gap-3.5">
                                <span
                                    class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 font-bold text-xs flex items-center justify-center shrink-0"
                                >
                                    {{ topic.stepNumber }}
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ topic.title }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ topic.summary }}
                                    </p>
                                </div>
                            </div>
                            <svg
                                :class="[
                                    'w-5 h-5 text-gray-400 transition-transform duration-200 shrink-0',
                                    openedTopics.includes(topic.id) ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7 7"
                                />
                            </svg>
                        </button>

                        <div
                            v-if="openedTopics.includes(topic.id)"
                            class="p-5 pt-0 border-t border-gray-100 dark:border-gray-700/40 text-xs text-gray-600 dark:text-gray-300 leading-relaxed space-y-3 mt-3"
                        >
                            <div
                                class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800"
                            >
                                <h5
                                    class="font-bold text-gray-900 dark:text-white mb-2 text-xs flex items-center gap-1.5"
                                >
                                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                    <span>{{ $t('help.steps_header') }}:</span>
                                </h5>
                                <ol class="list-decimal list-inside space-y-1.5 ps-1">
                                    <li v-for="(step, sIdx) in topic.steps" :key="sIdx">
                                        {{ step }}
                                    </li>
                                </ol>
                            </div>

                            <div
                                v-if="topic.tip"
                                class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200/50 dark:border-amber-800/40 text-amber-800 dark:text-amber-300 flex items-start gap-2.5"
                            >
                                <svg
                                    class="w-4 h-4 shrink-0 mt-0.5 text-amber-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>{{ topic.tip }}</span>
                            </div>

                            <div v-if="topic.link" class="pt-2">
                                <a
                                    :href="topic.link.url"
                                    class="inline-flex items-center gap-1 text-sky-600 dark:text-sky-400 font-bold hover:underline"
                                >
                                    <span>{{ topic.link.label }}</span>
                                    <svg
                                        class="w-3.5 h-3.5 rtl:rotate-180"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3"
                                        />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $t('help.faq_title') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="faq in faqs"
                        :key="faq.q"
                        class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm"
                    >
                        <h4
                            class="text-sm font-bold text-gray-900 dark:text-white flex items-start gap-2"
                        >
                            <span class="text-sky-600 dark:text-sky-400 font-extrabold">Q:</span>
                            <span>{{ faq.q }}</span>
                        </h4>
                        <p
                            class="text-xs text-gray-600 dark:text-gray-300 mt-2.5 leading-relaxed ps-5"
                        >
                            {{ faq.a }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Support Contact Footer Card -->
            <div
                class="rounded-3xl bg-gradient-to-br from-slate-900 to-indigo-950 p-8 text-white relative overflow-hidden shadow-xl"
            >
                <div
                    class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-sky-500/10 blur-3xl"
                ></div>
                <div
                    class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
                >
                    <div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/20 text-sky-300 border border-sky-400/30"
                        >
                            {{ $t('help.support_always_here') }}
                        </span>
                        <h3 class="text-xl font-bold mt-2">
                            {{ $t('help.need_more_help') }}
                        </h3>
                        <p class="text-sm text-slate-300 mt-1 max-w-xl">
                            {{ $t('help.support_desc') }} ({{ contactSupport.hours }})
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            v-if="contactSupport.whatsapp"
                            :href="`https://wa.me/${contactSupport.whatsapp.replace(/[^0-9]/g, '')}`"
                            target="_blank"
                            rel="noopener"
                            class="px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 font-bold text-sm text-white transition-colors flex items-center gap-2 shadow-lg shadow-emerald-500/20"
                        >
                            <span>واتساب الدعم الفوري</span>
                        </a>
                        <a
                            v-if="contactSupport.phone"
                            :href="`tel:${contactSupport.phone}`"
                            class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 font-bold text-sm text-white transition-colors flex items-center gap-2 backdrop-blur"
                        >
                            <span>{{ contactSupport.phone }}</span>
                        </a>
                        <a
                            v-if="contactSupport.email"
                            :href="`mailto:${contactSupport.email}`"
                            class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 font-bold text-sm text-white transition-colors flex items-center gap-2 backdrop-blur"
                        >
                            <span>{{ contactSupport.email }}</span>
                        </a>
                        <a
                            v-if="contactSupport.ticket_url"
                            :href="contactSupport.ticket_url"
                            target="_blank"
                            rel="noopener"
                            class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-sm text-white transition-colors flex items-center gap-2 shadow-lg shadow-indigo-600/30"
                        >
                            <span>فتح تذكرة دعم</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Video Modal Preview -->
            <div
                v-if="activeVideo"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                @click.self="activeVideo = null"
            >
                <div
                    class="relative bg-gray-900 rounded-3xl max-w-3xl w-full overflow-hidden shadow-2xl border border-gray-800"
                >
                    <div class="p-4 flex items-center justify-between border-b border-gray-800">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/20 text-sky-400"
                            >
                                {{ activeVideo.category }}
                            </span>
                            <h4 class="text-sm font-bold text-white">
                                {{ activeVideo.title }}
                            </h4>
                        </div>
                        <button
                            @click="activeVideo = null"
                            class="p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Video Player or Placeholder -->
                    <div
                        v-if="activeVideoEmbed"
                        class="relative w-full aspect-video bg-black overflow-hidden flex items-center justify-center"
                    >
                        <iframe
                            v-if="['youtube', 'vimeo', 'iframe'].includes(activeVideoEmbed.type)"
                            :src="activeVideoEmbed.src"
                            class="w-full h-full border-0"
                            allow="
                                accelerometer;
                                autoplay;
                                clipboard-write;
                                encrypted-media;
                                gyroscope;
                                picture-in-picture;
                                web-share;
                            "
                            allowfullscreen
                        ></iframe>
                        <video
                            v-else-if="activeVideoEmbed.type === 'video'"
                            controls
                            autoplay
                            class="w-full h-full"
                            :src="activeVideoEmbed.src"
                        >
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                    </div>

                    <div
                        v-else
                        class="p-8 bg-black/60 flex flex-col items-center justify-center min-h-[300px] text-center"
                    >
                        <div
                            class="w-16 h-16 rounded-full bg-sky-500/20 text-sky-400 flex items-center justify-center mb-4"
                        >
                            <svg class="w-8 h-8 ms-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </div>
                        <h4 class="text-base font-bold text-white mb-2">
                            {{ activeVideo.title }}
                        </h4>
                        <p class="text-xs text-gray-400 max-w-md leading-relaxed mb-4">
                            {{
                                activeVideo.description ||
                                'لم يتم إضافة رابط تشغيل مباشر لهذا الفيديو بعد.'
                            }}
                        </p>
                        <span
                            class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/30 px-3.5 py-1.5 rounded-full"
                        >
                            الفيديو قيد الإعداد أو يمكنك إضافة الرابط من لوحة إدارة النظام
                        </span>
                    </div>

                    <!-- Bottom Details Bar -->
                    <div
                        class="p-4 bg-gray-900 border-t border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="px-2.5 py-1 rounded-lg text-xs font-mono bg-gray-800 text-sky-300 border border-gray-700"
                            >
                                المدة: {{ activeVideo.duration }}
                            </span>
                            <p
                                v-if="activeVideo.description && activeVideoEmbed"
                                class="text-xs text-gray-400 max-w-md line-clamp-1"
                            >
                                {{ activeVideo.description }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <a
                                v-if="activeVideo.video_url"
                                :href="activeVideo.video_url"
                                target="_blank"
                                rel="noopener"
                                class="px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-semibold transition-colors inline-flex items-center gap-1.5"
                            >
                                <span>فتح المصدر</span>
                                <svg
                                    class="w-3.5 h-3.5 rtl:rotate-180"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                    />
                                </svg>
                            </a>
                            <button
                                @click="activeVideo = null"
                                class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition-colors shadow-sm"
                            >
                                {{ $t('common.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    contactSupport: {
        type: Object,
        default: () => ({
            whatsapp: '+966 555 785 658',
            whatsapp_enabled: true,
            email: 'support@carag.sa',
            phone: '920000000',
            hours: 'السبت - الخميس: 8:00 ص - 10:00 م',
            ticket_url: '',
        }),
    },
    quickStartItems: {
        type: Array,
        default: () => null,
    },
    videoTutorials: {
        type: Array,
        default: () => null,
    },
    categories: {
        type: Array,
        default: () => null,
    },
    topics: {
        type: Array,
        default: () => null,
    },
    faqs: {
        type: Array,
        default: () => null,
    },
});

const { t } = useI18n();
const searchQuery = ref('');
const activeCategory = ref(props.categories?.[0]?.id || 'work_orders');
const openedTopics = ref([1, 10]);
const activeVideo = ref(null);

const toggleTopic = (id) => {
    if (openedTopics.value.includes(id)) {
        openedTopics.value = openedTopics.value.filter((tId) => tId !== id);
    } else {
        openedTopics.value.push(id);
    }
};

const openVideo = (video) => {
    activeVideo.value = video;
};

const getVideoEmbed = (url) => {
    if (!url || typeof url !== 'string') return null;
    const trimmed = url.trim();

    // YouTube: watch?v=ID, youtu.be/ID, embed/ID, shorts/ID, live/ID
    const ytMatch = trimmed.match(
        /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=|shorts\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i
    );
    if (ytMatch && ytMatch[1]) {
        return {
            type: 'youtube',
            src: `https://www.youtube-nocookie.com/embed/${ytMatch[1]}?autoplay=1&rel=0`,
        };
    }

    // Vimeo: vimeo.com/ID
    const vimeoMatch = trimmed.match(
        /vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|video\/|)(\d+)/i
    );
    if (vimeoMatch && vimeoMatch[1]) {
        return {
            type: 'vimeo',
            src: `https://player.vimeo.com/video/${vimeoMatch[1]}?autoplay=1`,
        };
    }

    // Direct video formats (.mp4, .webm, .ogg)
    if (/\.(mp4|webm|ogg)($|\?)/i.test(trimmed)) {
        return {
            type: 'video',
            src: trimmed,
        };
    }

    // Already an embed or player URL
    if (trimmed.includes('/embed/') || trimmed.includes('player.')) {
        return {
            type: 'iframe',
            src: trimmed,
        };
    }

    return null;
};

const activeVideoEmbed = computed(() => {
    if (!activeVideo.value || !activeVideo.value.video_url) return null;
    return getVideoEmbed(activeVideo.value.video_url);
});

// Icons as render functions
const IconFileText = () =>
    h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        }),
    ]);

const IconActivity = () =>
    h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M13 10V3L4 14h7v7l9-11h-7z',
        }),
    ]);

const IconCheckCircle = () =>
    h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        }),
    ]);

const IconUserCheck = () =>
    h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        }),
    ]);

// Quick-start items
const defaultQuickStartItems = [
    {
        title: 'فتح كرت عمل جديد',
        desc: 'خطوات استلام سيارة العميل، وإضافة الخدمات والقطع وتحديد تاريخ التسليم المتوقع.',
        icon: IconFileText,
        bg: 'bg-blue-50 dark:bg-blue-900/30',
        text: 'text-blue-600 dark:text-blue-400',
        targetCategory: 'work_orders',
    },
    {
        title: 'لوحة الورشة ومؤقتات الفنيين',
        desc: 'تشغيل مؤقتات العمل للفنيين، تتبع مراحل الصيانة، ومراقبة الورشة على الشاشة الكبيرة.',
        icon: IconActivity,
        bg: 'bg-emerald-50 dark:bg-emerald-900/30',
        text: 'text-emerald-600 dark:text-emerald-400',
        targetCategory: 'workshop',
    },
    {
        title: 'الربط مع هيئة الزكاة (ZATCA)',
        desc: 'إصدار الفاتورة الضريبية المبسطة المتوافقة 100% مع المرحلة الثانية ورمز QR.',
        icon: IconCheckCircle,
        bg: 'bg-amber-50 dark:bg-amber-900/30',
        text: 'text-amber-600 dark:text-amber-400',
        targetCategory: 'billing',
    },
    {
        title: 'بوابة العميل الذكية',
        desc: 'كيفية دخول العميل برقم جواله، حجز المواعيد، وتتبع حالة الصيانة خطوة بخطوة.',
        icon: IconUserCheck,
        bg: 'bg-purple-50 dark:bg-purple-900/30',
        text: 'text-purple-600 dark:text-purple-400',
        targetCategory: 'portal',
    },
];

// Video Tutorials
const defaultVideoTutorials = [
    {
        id: 'v1',
        title: 'جولة سريعة في النظام وضبط المركز',
        category: 'الأساسيات',
        duration: '05:20',
        description:
            'نظرة شاملة على لوحة التحكم الرئيسية، الإعدادات، وإضافة بيانات المركز ومعلومات التواصل.',
    },
    {
        id: 'v2',
        title: 'دورة حياة كرت العمل من الدخول حتى الخروج',
        category: 'كروت العمل',
        duration: '07:45',
        description:
            'شرح تفصيلي لإنشاء كرت عمل، إضافة أجور اليد وقطع الغيار، الفحص المبدئي، واعتماد الجودة.',
    },
    {
        id: 'v3',
        title: 'إدارة الورشة الحية ومؤقتات الفنيين',
        category: 'لوحة الورشة',
        duration: '04:30',
        description:
            'كيف يضغط الفني على بدء العمل وإيقافه من جهازه، وكيف تظهر الإنتاجية على لوحة الكانبان.',
    },
    {
        id: 'v4',
        title: 'الفوترة الإلكترونية والربط مع هيئة الزكاة',
        category: 'الفوترة والضريبة',
        duration: '06:15',
        description:
            'خطوات إصدار الفاتورة الضريبية، السداد عبر مدى والنقد والآجل، ومطابقة متطلبات هيئة الزكاة.',
    },
    {
        id: 'v5',
        title: 'إدارة المخزون وقطع الغيار وأوامر الشراء',
        category: 'المخزون',
        duration: '08:10',
        description:
            'إضافة المنتجات، تتبع الأرصدة بالمستودعات، ربط القطع بكروت العمل، وإصدار أوامر الشراء للموردين.',
    },
    {
        id: 'v6',
        title: 'بوابة العميل الذكية والحجز الذاتي',
        category: 'بوابة العميل',
        duration: '03:50',
        description:
            'تجربة العميل من الجوال: طلب حجز موعد صيانة، تلقي رمز التحقق OTP، ومتابعة جاهزية السيارة.',
    },
];

// Knowledge base categories
const defaultCategories = [
    { id: 'work_orders', name: 'كروت العمل والخدمات' },
    { id: 'workshop', name: 'لوحة الورشة والفنيين' },
    { id: 'portal', name: 'بوابة العميل الذكية' },
    { id: 'billing', name: 'الفوترة وضريبة ZATCA' },
    { id: 'inventory', name: 'المخزون وقطع الغيار' },
    { id: 'customers', name: 'العملاء وسجل المركبات' },
    { id: 'appointments', name: 'المواعيد والبايات' },
];

// Topics
const defaultAllTopics = [
    // Work Orders
    {
        id: 1,
        category: 'work_orders',
        stepNumber: '01',
        title: 'كيف تنشئ كرت عمل جديد للمركبة؟',
        summary: 'خطوات فتح أمر شغل رسمي واستلام السيارة في المركز.',
        steps: [
            'توجه إلى قائمة كروت العمل (/app/work-orders) واضغط على زر "كرت عمل جديد".',
            'اختر العميل بالاسم أو رقم الجوال، أو اضغط "إضافة عميل جديد" للتسجيل الفوري.',
            'اختر المركبة التابعة للعميل وحدد قراءة العداد الحالي (الكيلومترات).',
            'أضف شكوى العميل وملاحظات الفحص المبدئي وأرفق صور المركبة إن وجدت.',
            'حدد تاريخ ووقت التسليم المتوقع ثم اضغط "حفظ وإنشاء الكرت".',
        ],
        tip: 'يمكنك إرفاق صور أضرار المركبة وتوثيق الخدوش عبر ميزة فحص المركبة قبل بدء الصيانة.',
        link: { label: 'الانتقال إلى كروت العمل', url: '/app/work-orders' },
    },
    {
        id: 2,
        category: 'work_orders',
        stepNumber: '02',
        title: 'إضافة الخدمات وقطع الغيار إلى كرت العمل',
        summary: 'تسعير أجور اليد وربط القطع المصروفة من المستودع.',
        steps: [
            'من صفحة كرت العمل، انتقل إلى تبويب "الخدمات والقطع".',
            'اضغط على "إضافة خدمة"، واختر القسم المعني (ميكانيكا، كهرباء، صيانة سريعة).',
            'حدد السعر والكمية والخصم إن وُجد.',
            'في خانة قطع الغيار، اختر القطعة المصروفة من المستودع؛ سيتم خصمها من المخزون تلقائياً عند اعتماد الكرت.',
        ],
        tip: 'يمكنك توزيع بنود كرت العمل على باقات جاهزة بضغطة زر لتسريع العملية.',
    },
    {
        id: 3,
        category: 'work_orders',
        stepNumber: '03',
        title: 'فحص الجودة (QC) وتسليم المركبة',
        summary: 'التحقق من اكتمال كافة البنود واعتماد خروج السيارة.',
        steps: [
            'بعد انتهاء الفنيين من كافة البنود، تتحول حالة الكرت إلى "جاهز لفحص الجودة".',
            'يقوم مشرف الورشة بمراجعة بنود الفحص واعتمادها أو إعادتها مع كتابة الملاحظة.',
            'عند الاعتماد، يُتاح إصدار الفاتورة النهائية وإغلاق الكرت لخروج السيارة.',
        ],
    },

    // Workshop
    {
        id: 10,
        category: 'workshop',
        stepNumber: '01',
        title: 'استخدام لوحة كانبان للورشة ومتابعة الفنيين',
        summary: 'إدارة العمليات الميدانية على الشاشة الكبيرة داخل صالة الصيانة.',
        steps: [
            'ادخل إلى مسار لوحة الورشة (/app/workshop).',
            'تظهر الأعمدة الأربعة للعمليات: قيد الانتظار، جاري العمل، معلق، وجاهز لفحص الجودة.',
            'يعرض أعلى اللوحة ملخص المواعيد القادمة اليوم وزر التسجيل السريع (Check-in).',
            'تتحدث الكروت تلقائياً وتُظهر اسم الفني الفعال والوقت المنقضي.',
        ],
        link: { label: 'فتح لوحة الورشة الآن', url: '/app/workshop' },
    },
    {
        id: 11,
        category: 'workshop',
        stepNumber: '02',
        title: 'تسجيل ساعات العمل بمؤقت الفني (Labor Timers)',
        summary: 'حساب الإنتاجية الحقيقية لكل فني عبر أزرار البدء والتوقف.',
        steps: [
            'يفتح الفني حسابه ويختار البند المكلف به.',
            'يضغط على زر "بدء العمل" لبدء عداد الثواني.',
            'في حال الراحة أو انتظار قطعة الغيار، يضغط على "إيقاف مؤقت".',
            'عند إنهاء الإصلاح يضغط "إنهاء العمل"، فيتم حفظ مجموع الساعات الفعالة آلياً في تقرير الفني.',
        ],
        tip: 'يمنع النظام الفني من تشغيل مؤقتين متزامنين في وقت واحد لمنع التضارب وحساب الإنتاجية بدقة.',
    },

    // Customer Portal
    {
        id: 20,
        category: 'portal',
        stepNumber: '01',
        title: 'كيف يدخل العميل إلى بوابته الخاصة؟',
        summary: 'الدخول الآمن برقم الجوال عبر رمز التحقق OTP دون الحاجة لكلمة مرور.',
        steps: [
            'يزور العميل الرابط المخصص للمركز: /{tenant_slug}/portal.',
            'يدخل رقم جواله المسجل في النظام، ويتلقى رمز تحقق OTP مكون من 4 أرقام عبر SMS.',
            'بعد التحقق، تفتح لوحة تحكم مخصصة تعرض سياراته ومواعيده السابقة والحالية.',
        ],
        tip: 'بوابة العميل معزولة أمنياً عن لوحة موظفي المركز وتعمل بسلاسة على شاشات الجوال.',
    },
    {
        id: 21,
        category: 'portal',
        stepNumber: '02',
        title: 'حجز موعد صيانة جديد وإعادة الجدولة ذاتياً',
        summary: 'تمكين العميل من اختيار الفرع والخدمات والموعد المناسب له.',
        steps: [
            'يضغط العميل على زر "حجز موعد صيانة".',
            'يختار مركبته، والفرع الأقرب، والخدمات المطلوبة.',
            'يختار التاريخ والوقت المتاح وفق الطاقة الاستيعابية للبايات.',
            'يحصل العميل على إشعار فوري برقم الحجز مع إمكانية تعديل الموعد أو إلغائه من نفس الصفحة.',
        ],
    },

    // Billing & ZATCA
    {
        id: 30,
        category: 'billing',
        stepNumber: '01',
        title: 'إصدار الفاتورة الضريبية وتحصيل المبالغ',
        summary:
            'إصدار الفاتورة الضريبية المبسطة المتوافقة مع متطلبات هيئة الزكاة والضريبة والجمارك.',
        steps: [
            'من صفحة كرت العمل المنجز، اضغط على زر "إصدار فاتورة".',
            'يقوم النظام بحساب ضريبة القيمة المضافة 15% تلقائياً وتوليد رمز الاستجابة السريعة (QR Code).',
            'اختر طريقة الدفع: نقدي، مدى، بطاقة ائتمانية، تحويل بنكي، أو ذمم مدينة (آجل).',
            'اطبع الفاتورة بالشكل الحراري السريع (Thermal Print) أو مقاس A4 المعتمد.',
        ],
        tip: 'يدعم النظام طباعة الباركود المشفر المتوافق مع تطبيق التحقق الرسمي لهيئة الزكاة (ZATCA).',
    },

    // Inventory
    {
        id: 40,
        category: 'inventory',
        stepNumber: '01',
        title: 'إدارة المستودعات وجرد قطع الغيار',
        summary: 'متابعة حركة المخزون، تنبيهات النواقص، والتسويات المخزنية.',
        steps: [
            'من قائمة المخزون (/app/inventory/stock)، استعرض كافة القطع وأرصدتها في كل مستودع.',
            'يمكنك إنشاء أمر شراء جديد للمورد عند وصول القطعة إلى حد الطلب الأدنى.',
            'عند استلام البضائع، سجل مذكرة استلام بضاعة (GRN) ليتم تحديث الأرصدة تلقائياً.',
        ],
    },

    // Appointments & Bays
    {
        id: 50,
        category: 'appointments',
        stepNumber: '01',
        title: 'جدولة البايات (الرافعات) وإدارة السعة',
        summary: 'منع التعارضات وحساب السعة اليومية لرافعات المركز.',
        steps: [
            'عرف رافعات المركز وبقع الخدمة من صفحة البايات (/app/service-bays).',
            'عند حجز أي موعد، يتحقق النظام من خلو الباي والفني والمركبة في الوقت المحدد.',
            'عند حضور العميل، اضغط على "تسجيل وصول" (Check-in)، ثم "تحويل إلى كرت عمل" بضغطة زر واحدة.',
        ],
    },
];

// FAQ List
const defaultFaqs = [
    {
        q: 'هل يمكن تشغيل لوحة الورشة على شاشة تلفاز معلقة داخل المركز؟',
        a: 'نعم بالتأكيد! صُممت لوحة الورشة (/app/workshop) لتكون متجاوبة وتعمل بوضوح تام على الشاشات الكبيرة في صالة الصيانة وتتحدث فورياً.',
    },
    {
        q: 'كيف أربط الفواتير بمتطلبات هيئة الزكاة والضريبة والجمارك (ZATCA)؟',
        a: 'النظام يدعم المرحلة الأولى والمرحلة الثانية، ويولد QR Code مشفر يحتوي على اسم المنشأة والرقم الضريبي والتوقيت والمجموع والضريبة تلقائياً.',
    },
    {
        q: 'هل يستطيع الفني العمل على أكثر من سيارة في نفس اللحظة؟',
        a: 'لا، يمنع النظام تشغيل مؤقتين نشطين في نفس الوقت للفني الواحد؛ يجب عليه إيقاف العمل مؤقتاً على السيارة الأولى قبل بدء العمل على الثانية لضمان دقة احتساب الإنتاجية.',
    },
    {
        q: 'كيف يتلقى العميل رمز الـ OTP لدخول بوابته الذكية؟',
        a: 'يدخل العميل رقم جواله في بوابة العميل، ويصله الرمز السداسي عبر رسالة SMS فورية على هاتفه المسجل للدخول الآمن دون الحاجة لتذكر أي كلمة مرور.',
    },
];

const iconMap = {
    IconFileText,
    IconActivity,
    IconCheckCircle,
    IconUserCheck,
};

const quickStartItems = computed(() => {
    const list =
        props.quickStartItems && props.quickStartItems.length > 0
            ? props.quickStartItems
            : defaultQuickStartItems;

    return list.map((item) => ({
        ...item,
        icon:
            typeof item.icon === 'string'
                ? iconMap[item.icon] || IconFileText
                : item.icon || IconFileText,
    }));
});

const videoTutorials = computed(() => {
    return props.videoTutorials && props.videoTutorials.length > 0
        ? props.videoTutorials
        : defaultVideoTutorials;
});

const categories = computed(() => {
    return props.categories && props.categories.length > 0 ? props.categories : defaultCategories;
});

const allTopics = computed(() => {
    return props.topics && props.topics.length > 0 ? props.topics : defaultAllTopics;
});

const faqs = computed(() => {
    return props.faqs && props.faqs.length > 0 ? props.faqs : defaultFaqs;
});

// Computed filtered topics
const filteredTopics = computed(() => {
    let list = allTopics.value;
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        return list.filter(
            (t) =>
                t.title.toLowerCase().includes(q) ||
                t.summary.toLowerCase().includes(q) ||
                (Array.isArray(t.steps) && t.steps.some((s) => s.toLowerCase().includes(q)))
        );
    }
    return list.filter((t) => t.category === activeCategory.value);
});

const getTopicCount = (categoryId) => {
    return allTopics.value.filter((t) => t.category === categoryId).length;
};
</script>

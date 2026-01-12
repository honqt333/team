<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-6 text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $t('auth.set_password') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('auth.set_password_hint', { name: user.name }) }}
                </p>
            </div>

            <form @submit.prevent="submit">
                <div>
                    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                        {{ $t('common.email') }}
                    </label>
                    <input
                        type="email"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 focus:ring-violet-500 rounded-md shadow-sm opacity-75"
                        :value="user.email"
                        disabled
                    />
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                        {{ $t('auth.password') }}
                    </label>
                    <input
                        id="password"
                        type="password"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 focus:ring-violet-500 rounded-md shadow-sm"
                        v-model="form.password"
                        required
                        autofocus
                        autocomplete="new-password"
                    />
                    <p v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</p>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                        {{ $t('auth.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 focus:ring-violet-500 rounded-md shadow-sm"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button
                        class="inline-flex items-center px-4 py-2 bg-violet-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-violet-700 focus:bg-violet-700 active:bg-violet-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ $t('auth.set_password_action') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    signature_params: Object,
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('invitations.accept', {
        user: props.user.id,
        ...props.signature_params
    }), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход" />

        <h1 class="title mb-2">Вход</h1>
        <p class="context mb-6">
            Войдите в аккаунт, чтобы продолжить работу.
        </p>

        <div v-if="status" class="mb-4 t-small text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="t-mini text-gray-600 mb-1 block">Email</label>
                <input
                    id="email"
                    type="email"
                    class="input block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="t-mini text-gray-600 mb-1 block">Пароль</label>
                <input
                    id="password"
                    type="password"
                    class="input block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <Checkbox
                    name="remember"
                    v-model:checked="form.remember"
                    class="rounded border-gray-300 text-[#e97358] focus:ring-[#e97358]"
                />
                <span class="ms-2 t-small text-gray-600">Запомнить меня</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="t-small text-gray-600 hover:text-gray-900 transition order-2 sm:order-1"
                >
                    Забыли пароль?
                </Link>
                <button
                    type="submit"
                    class="primary-btn w-full sm:w-auto order-1 sm:order-2"
                    :disabled="form.processing"
                >
                    Войти
                </button>
            </div>
        </form>
    </GuestLayout>
</template>

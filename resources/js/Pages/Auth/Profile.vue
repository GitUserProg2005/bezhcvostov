<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Avatar from '@/Components/Avatar.vue';
import Back from '@/Components/BackButton.vue';

const props = defineProps({
    user: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const isOwnProfile = computed(() => currentUser.value && currentUser.value.id === props.user.id);

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
}
</script>

<template>
    <AppLayout>
        <div class="w-full min-h-screen bg-body">
            <!-- Шапка: кнопка назад -->
            <div class="sticky top-0 z-20 w-full px-4 pt-4 pb-2 flex items-center">
                <Back @back="goBack" />
            </div>

            <!-- Блок профиля в стиле TikTok -->
            <header class="relative w-full px-4 pt-2 pb-6">
                <div class="bg-content rounded-2xl p-6 flex flex-col items-center text-center">
                    <!-- Аватар крупно -->
                    <div class="mb-4 inline-block">
                        <Avatar
                            :name="user.name"
                            :src="user.avatar_url"
                            :userId="user.id"
                            size="lg"
                        />
                    </div>
                    <h1 class="title text-xl mb-0.5">{{ user.name }}</h1>
                    <p v-if="user.email && isOwnProfile" class="context text-xs mb-4">{{ user.email }}</p>
                    <p v-else class="context text-xs mb-4">Профиль пользователя</p>
                </div>
            </header>
        </div>
    </AppLayout>
</template>

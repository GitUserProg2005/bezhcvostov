<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import ProjectUserSearch from './components/ProjectUserSearch.vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const users = ref(Array.isArray(props.project?.users) ? props.project.users : []);

function onUserAdded(user) {
    if (!users.value.some((member) => member.id === user.id)) {
        users.value.push(user);
    }
}
</script>

<template>
    <DashboardLayout>
        <div class="grid h-[calc(100vh-6.5rem)] grid-cols-1 gap-4 p-4 lg:grid-cols-[1fr_20rem]">
            <section class="content-outline min-h-0 space-y-4 overflow-y-auto p-4">
                <h1 class="title">{{ project.title }}</h1>

                <div>
                    <h2 class="title-2 mb-4">Чаты проекта</h2>
                    <div class="space-y-2 mt-2">
                        <Link
                            v-for="chat in project.chats"
                            :key="chat.id"
                            :href="route('projects.chats.index', [project.id, chat.id])"
                            class="content !px-6 !py-4 !rounded-lg flex items-center justify-between"
                        >
                            {{ chat.title }}
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        </Link>
                    </div>
                </div>
            </section>

            <aside class="content-outline min-h-0 space-y-4 overflow-y-auto p-3">
                <div>
                    <h2 class="title-2 mb-2">Участники</h2>

                    <ProjectUserSearch :project-id="project.id" @user-added="onUserAdded" />

                    <div class="space-y-2 mt-4">
                        <div v-for="member in users" :key="member.id" class="flex items-center gap-2 rounded-xl bg-content px-3 py-2">
                            <Avatar :name="member.name" :src="member.avatar_url" :user-id="member.id" size="sm" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ member.name }}</p>
                                <p class="t-mini text-gray-500">{{ member.account_code }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </DashboardLayout>
</template>

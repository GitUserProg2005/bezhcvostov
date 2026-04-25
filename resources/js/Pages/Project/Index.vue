<script setup>
import { onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateProjectModal from './components/CreateProjectModal.vue';
import { useFetchProjects } from '@/composables/useFetchProjects';

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);
const { projects, isLoading, error, fetchProjects } = useFetchProjects();

async function deleteProject(project) {
    if (!window.confirm(`Удалить проект "${project.title}"?`)) {
        return;
    }

    try {
        await axios.delete(route('projects.delete', project.id));
        await fetchProjects();
    } catch (e) {
        console.error(e);
    }
}

onMounted(fetchProjects);
</script>

<template>
    <DashboardLayout>
        <div class="flex min-h-0 w-full max-w-full min-w-0 flex-col gap-4 overflow-x-hidden p-4 md:h-[calc(100vh-6.5rem)]">
            <div class="flex items-center justify-between gap-3">
                <h1 class="title">Проекты</h1>
                <CreateProjectModal @created="fetchProjects" />
            </div>

            <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

            <div class="min-h-0 flex-1 overflow-y-auto p-3 md:p-4">
                <div v-if="isLoading" class="context">Загружаем проекты...</div>

                <div v-else-if="!projects.length" class="context">У вас пока нет проектов.</div>

                <div v-else class="flex flex-col gap-3">
                    <div v-for="project in projects" :key="project.id" class="bg-body rounded-xl flex items-center justify-between gap-3 p-4">
                        <Link :href="route('projects.show', project.id)" class="min-w-0 flex-1">
                            <p class="title-2 truncate">{{ project.title }}</p>
                            <p class="t-small text-gray-400 mt-1">
                                Участники: {{ project.users_count ?? 0 }} | Чаты: {{ project.chats_count ?? 0 }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
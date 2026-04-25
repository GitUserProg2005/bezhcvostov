<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

const props = defineProps({
    isOpenSidebar: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['update:isOpenSidebar']);

const closeSidebar = () => {
    emit('update:isOpenSidebar', false);
};
</script>

<template>
    <div 
        v-if="isOpenSidebar"
        class="fixed inset-0 z-[100] bg-black/40 lg:hidden"
        @click="emit('update:isOpenSidebar', false)"
    />

    <!-- Sidebar -->
    <aside
      class="
        fixed inset-y-0 left-0
        transform transition-transform duration-300
        lg:translate-x-0 lg:relative 
        z-[100]
        lg:z-[50]
        bg-content
        w-4/5 sm:w-72 lg:w-full
      "
      :class="isOpenSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="h-full rounded-r-3xl p-0 flex flex-col">
        <div class="p-4 flex items-center  flex-col h-full gap-2">
          <!-- Top card -->
          <div class="p-0">
            <div class="flex items-center justify-between lg:justify-center">
              <div class="flex items-center justify-center gap-3">
                <img src="/img/bez_lgoo.png" class="w-14 object-contain" alt="">
                <span class="title-font-4">ХВОСТОВ</span>
              </div>

              <!-- Mobile close -->
              <button
                class="lg:hidden p-2"
                @click="closeSidebar"
                aria-label="Close sidebar"
              >
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
          </div>

          <!-- Nav card -->
          <nav class="flex-1 min-h-0 p-0 overflow-hidden">
            <div class="px-2 pt-2 pb-1 h-full flex flex-col">
              <div class="flex-1 min-h-0 overflow-y-auto custom-scroll">
                <div class="space-y-1 px-1">
                  <Link
                    :href="route('tasks.index')"
                    class="sidebar-nav-link-nested sidebar-nav-link-active"
                    @click="closeSidebar"
                  >
                    <i class="fa-solid fa-list-check text-sm"></i>
                    Задачи
                  </Link>

                  <Link
                    :href="route('folders.index')"
                    class="sidebar-nav-link-nested"
                    @click="closeSidebar"
                  >
                    <i class="fa-solid fa-hashtag text-sm"></i>
                    Заметки
                  </Link>

                  <Link
                    :href="route('video.index.room')"
                    class="sidebar-nav-link-nested"
                    @click="closeSidebar"
                  >
                    <i class="fa-solid fa-video text-sm"></i>
                     Коннект
                  </Link>

                  <Link
                    :href="route('video.insights.index')"
                    class="sidebar-nav-link-nested"
                    @click="closeSidebar"
                  >
                    <i class="fa-solid fa-note-sticky text-sm"></i>
                    Инсайты звонков
                  </Link>

                  <Link
                    :href="route('projects.index')"
                    class="sidebar-nav-link-nested"
                    @click="closeSidebar"
                  >
                    <i class="fa-solid fa-diagram-project text-sm"></i>
                    Проекты
                  </Link>
                </div>
              </div>
            </div>
          </nav>

          <!--<div class="content-dark-gradient space-y-4 mb-4">
            <div>
              <h2 class="title-2-on-dark">Скачивайте моб. приложение!</h2>
              <p class="context pt-1">Пользуйтесь нашей платформой с телефона</p>
            </div>

            <button class="primary-btn-white w-full">Установить</button>
          </div>-->

          <Link :href="route('game.index')">
            <img src="/img/game/preview_nora.png" class="flex items-center justify-center w-full object-contain pointer-events-none" alt="">
          </Link>
        </div>
      </div>
    </aside>
</template>
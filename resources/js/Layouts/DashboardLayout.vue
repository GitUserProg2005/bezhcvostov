<script setup>
import '../../css/custom.css';

import Sidebar from '@/Components/Sidebar.vue';
import RightInfo from '@/Pages/RightInfo.vue';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';
import Chat from '@/Pages/AiChat/Chat.vue';

import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3';

const isOpenSidebar = ref(false)
const isOpenRightInfo = ref(false)

// Stub search for now: пока не подключаем серверный поиск
const searchFn = async () => []

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);
const username = computed(() => currentUser.value?.name ?? 'Username');

</script>

<template>
  <div
    class="
      h-screen overflow-hidden
      grid grid-cols-1
      lg:grid-cols-[16rem_1fr_16rem]
      select-none
      gap-2
    "
  >
    <!-- Sidebar -->
    <Sidebar
      :is-open-sidebar="isOpenSidebar"
      @update:isOpenSidebar="isOpenSidebar = $event"
    />

    <!-- Main -->
    <main class="relative overflow-y-auto bg-content">
      <header class="sticky top-0 z-50 backdrop-blur-xl px-4">
        <div class="py-3">
          <div class="flex items-center justify-between gap-6 w-full">
            <!-- Mobile burger -->
            <button
              class="lg:hidden w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex flex-col p-3 items-center justify-center"
              @click="isOpenSidebar = true"
              aria-label="Open menu"
            >
              <span class="block w-5 h-[2px] bg-[#e97358]"></span>
              <span class="block w-5 h-[2px] bg-[#e97358] mt-1"></span>
            </button>

            <!-- Wide search -->
            <div class="hidden lg:flex flex-1 min-w-0 w-full">
              <Search :search-fn="searchFn" />
            </div>

            <!-- Notifications + user -->
            <div class="flex lg:hidden items-center gap-4 shrink-0">
              <div class="hidden lg:flex items-center gap-4">
              </div>

              <div class="relative shrink-0 flex items-center gap-3">
                <div
                  class="cursor-pointer"
                  role="button"
                  tabindex="0"
                  aria-label="Open right sidebar"
                  @click.stop="isOpenRightInfo = true"
                  @keydown.enter.stop="isOpenRightInfo = true"
                >
                  <Avatar
                    :name="username"
                    :src="currentUser?.avatar_url"
                    :userId="null"
                    :no-link="true"
                    size="md"
                  />
                </div>

                <div class="min-w-0 pointer-events-none">
                  <div class="font-semibold truncate">
                    Дмитрий
                  </div>
                  <div class="flex items-center gap-2">
                    <span>344</span> 
                    <img src="/img/crystal.png" class="w-3 object-contain" alt="">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <div class="w-full">
        <div class="space-y-6">
          <slot />
        </div>
      </div>

      <Chat />
    </main>
    
    <RightInfo
      :is-open-right-info="isOpenRightInfo"
      @update:isOpenRightInfo="isOpenRightInfo = $event"
    />
  </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';
import SpiderChart from '@/Components/SpiderChart.vue';
import Graph from '@/Components/Graph.vue';

const props = defineProps({
  isOpenRightInfo: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:isOpenRightInfo']);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const username = computed(() => currentUser.value?.name ?? 'Пользователь');
const friends = ref([]);
const friendsLoading = ref(false);
const tasks = ref([]);
const notes = ref([]);

onMounted(async () => {
  if (!currentUser.value) return;
  friendsLoading.value = true;
  try {
    const { data } = await axios.get(route('friends.index'));
    friends.value = data;
  } catch {
    friends.value = [];
  } finally {
    friendsLoading.value = false;
  }

  try {
    const [tasksResponse, notesResponse] = await Promise.all([
      axios.get(route('tasks.get')),
      axios.get(route('notes.get')),
    ]);

    tasks.value = tasksResponse.data?.tasks ?? [];
    notes.value = notesResponse.data?.notes ?? [];
  } catch {
    tasks.value = [];
    notes.value = [];
  }
});

const closeRightInfo = () => {
  emit('update:isOpenRightInfo', false);
};
</script>

<template>
  <div
    v-if="isOpenRightInfo"
    class="fixed inset-0 z-[100] bg-black/40 lg:hidden"
    @click="closeRightInfo"
  />

  <aside
    class="fixed inset-0 right-0 w-full bg-content lg:p-4 transform transition-transform duration-300 z-[110] lg:hidden overflow-y-auto"
    :class="isOpenRightInfo ? 'translate-x-0' : 'translate-x-full'"
  >
    <div class="flex items-center gap-4 p-4">
      <button type="button" @click="closeRightInfo">
        <i class="fa-solid fa-arrow-left"></i>
      </button>
      <h2 class="title-2">Профиль</h2>
    </div>

    <div v-if="currentUser" class="space-y-4">
      <div class="flex items-start justify-between gap-4 px-4 py-4 bg-body">
        <div class="flex items-center gap-3">
          <Avatar
              :name="username"
              :src="currentUser?.avatar_url"
              :userId="null"
              :no-link="true"
              size="md"
            />
          <div class="flex flex-col">
            <h3 class="title-3">{{ username }}</h3>
            <span class="flex items-center gap-1">
              <img src="/img/crystal.png" class="w-3 object-contain" alt="">
              <span>2345</span>
            </span>
          </div>
        </div>

        <button
          class="relative w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex items-center justify-center shrink-0"
          aria-label="Notifications"
        >
          <i class="fa-solid fa-bell text-sm" />
          <span class="absolute top-2 right-2 w-2 h-2 bg-[#e97358] rounded-full" />
        </button>
      </div>

      <div class="bg-content rounded-3xl p-2">
        <h3 class="mb-2">Твои навыки</h3>
        <SpiderChart class="content-glass" />
      </div>

      <div class="bg-content rounded-3xl p-2">
        <h3 class="mb-2">Граф (база знаний)</h3>
        <Graph :tasks="tasks" :notes="notes" class="content-glass" />
      </div>
    </div>
  </aside>

  <aside class="hidden lg:flex h-full flex flex-col bg-content p-4 overflow-hidden">
    <!-- Друзья (только для авторизованных) -->
    <div v-if="currentUser" class="mb-4 shrink-0">
      <div class="flex justify-between items-center gap-2">
        <button
          class="relative w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex items-center justify-center"
          aria-label="Notifications"
        >
          <i class="fa-solid fa-bell text-sm" />
          <span class="absolute top-2 right-2 w-2 h-2 bg-[#e97358] rounded-full" />
        </button>   

        <div class="flex items-center gap-2">
          <Avatar
              :name="username"
              :src="currentUser?.avatar_url"
              :userId="null"
              :no-link="true"
              size="md"
            />
          <div class="flex flex-col">
            <h3>{{ username }}</h3>
            <span class="flex items-center gap-1">
              <img src="/img/crystal.png" class="w-3 object-contain" alt="">
              <span>2345</span> 
            </span>
          </div>
        </div>     
      </div>
    </div>

    <div class="flex-1 mt-auto content-glass">
      <div class="flex flex-col h-full justify-between">
        <h3>Твои навыки</h3>
        <SpiderChart />
        
        <h3 class="mb-2">Граф знаний</h3>
        <Graph :tasks="tasks" :notes="notes" />
      </div>
    </div>
  </aside>
</template>

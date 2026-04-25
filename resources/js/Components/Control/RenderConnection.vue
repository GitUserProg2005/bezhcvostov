<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/Avatar.vue';

const page = usePage();

const peers = computed(() => page.props.auth?.user?.connection_peers ?? []);
const myRole = computed(() => page.props.auth?.user?.role ?? null);

const heading = computed(() => {
  if (myRole.value === 'student') {
    return 'Родитель';
  }
  if (myRole.value === 'parent') {
    return 'Подключённые ученики';
  }
  return 'Связь';
});
</script>

<template>
  <div v-if="peers.length" class="content space-y-3">
    <h3 class="title-3 text-base font-semibold">{{ heading }}</h3>
    <ul class="space-y-2">
      <li
        v-for="peer in peers"
        :key="peer.id"
        class="flex items-center gap-3"
      >
        <Avatar
          :name="peer.name"
          :src="peer.avatar_url"
          :user-id="peer.id"
          size="sm"
        />
        <div class="min-w-0">
          <p class="text-sm font-medium truncate">{{ peer.name }}</p>
        </div>
      </li>
    </ul>
  </div>
</template>

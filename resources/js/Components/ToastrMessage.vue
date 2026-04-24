<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    isShow: Boolean,
    title: String
});

const isShowing = ref(false);
const showInterval = null;

watch(() => props.isShow, (val) => {
    if (!val) return;

    isShowing.value = isShow ? true : false;

    showInterval = clearInterval(showInterval);
    showInterval = setInterval(() => {
        isShowing.value = false;
    }, 3000);
});
</script>

<template>
  <div
    v-if="isShowing"
    class="fixed bottom-4 left-1/2 -translate-x-1/2"
  >
    <div class="bg-green-500 text-black p-4 rounded-2xl shadow-lg">
      {{ title }}
    </div>
  </div>
</template>
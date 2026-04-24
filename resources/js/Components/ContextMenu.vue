<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    position: {
        type: String,
        default: 'right',
        validator: (value) => ['left', 'right'].includes(value),
    },
});

const isOpen = ref(false);
const menuRef = ref(null);

function toggleOpen(e) {
    e?.preventDefault?.();
    e?.stopPropagation?.();
    isOpen.value = !isOpen.value;
}

function closeMenu() {
    isOpen.value = false;
}

const menuPositionClass = computed(() =>
    props.position === 'left' ? 'top-full mt-2 right-0' : 'top-full mt-2 left-0',
);

function handleClickOutside(e) {
    if (!menuRef.value) return;

    if (!menuRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

function handleEscape(e) {
    if (e.key === 'Escape') {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div class="relative inline-block" @contextmenu.prevent="toggleOpen">
        <!--Контент-->
        <slot :toggleOpen="toggleOpen" :closeMenu="closeMenu" :isOpen="isOpen" />

        <!--Мини-модалка-->
        <div
            v-if="isOpen"
            ref="menuRef"
            @click.stop
            class="absolute z-50"
            :class="menuPositionClass"
        >
            <div class="bg-content-outline rounded-2xl">
                <slot name="menu" :toggleOpen="toggleOpen" :closeMenu="closeMenu" />
            </div>
        </div>
    </div>
</template>
<script setup>
const model = defineModel({
    type: Boolean,
    required: true,
});

const props = defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
    /** Не менять v-model по клику — только emit toggle-request(новое значение). */
    manual: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: undefined,
    },
    label: {
        type: String,
        default: undefined,
    },
});

const emit = defineEmits(['toggle-request']);

function toggle() {
    if (props.disabled) {
        return;
    }
    if (props.manual) {
        emit('toggle-request', !model.value);
        return;
    }
    model.value = !model.value;
}
</script>

<template>
    <button
        :id="id"
        type="button"
        role="switch"
        :aria-checked="model"
        :aria-disabled="disabled || undefined"
        :aria-label="label"
        :disabled="disabled"
        class="relative h-8 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e97358] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        :class="model ? 'bg-[#e97358]' : 'bg-gray-200'"
        @click="toggle"
    >
        <span
            class="pointer-events-none absolute left-1 top-1 block h-6 w-6 rounded-full bg-white shadow transition-transform duration-200 ease-in-out"
            :class="model ? 'translate-x-6' : 'translate-x-0'"
        />
    </button>
</template>

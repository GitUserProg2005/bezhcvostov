<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const showModal = ref(false);

const isRecording = ref(false);
const mediaRecorder = ref(null);
const mediaStream = ref(null);
const audioChunks = ref([]);
const audioBlob = ref(null);
const audioUrl = ref('');
const errorMessage = ref('');
const wave1 = ref(null);
const wave2 = ref(null);
const points = 50;
let waveAnimation1 = null;
let waveAnimation2 = null;

function closeModal() {
    if (isRecording.value) {
        stopRecording();
    }
    showModal.value = false;
}

function clearAudio() {
    audioChunks.value = [];
    audioBlob.value = null;
    if (audioUrl.value) {
        URL.revokeObjectURL(audioUrl.value);
    }
    audioUrl.value = '';
}

async function startRecording() {
    errorMessage.value = '';
    clearAudio();

    if (!navigator.mediaDevices?.getUserMedia) {
        errorMessage.value = 'Запись голоса не поддерживается в этом браузере.';
        return;
    }

    try {
        mediaStream.value = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder.value = new MediaRecorder(mediaStream.value);
        audioChunks.value = [];

        mediaRecorder.value.ondataavailable = (event) => {
            if (event.data?.size > 0) {
                audioChunks.value.push(event.data);
            }
        };

        mediaRecorder.value.onstop = () => {
            if (!audioChunks.value.length) {
                return;
            }

            audioBlob.value = new Blob(audioChunks.value, { type: 'audio/webm' });
            audioUrl.value = URL.createObjectURL(audioBlob.value);

            createTaskViaVoice();
        };

        mediaRecorder.value.start();
        isRecording.value = true;
    } catch (error) {
        errorMessage.value = 'Не удалось получить доступ к микрофону.';
    }
}

function stopRecording() {
    if (!mediaRecorder.value || mediaRecorder.value.state === 'inactive') {
        return;
    }

    mediaRecorder.value.stop();
    isRecording.value = false;

    mediaStream.value?.getTracks()?.forEach((track) => track.stop());
    mediaStream.value = null;
}

async function toggleRecording() {
    if (isRecording.value) {
        stopRecording();
    } else {
        await startRecording();
    }
}

async function createTaskViaVoice() {
    if (!audioBlob.value) {
        return;
    }

    const formData = new FormData();
    formData.append('audio_file', audioBlob.value);

    try {
        const response = await axios.post(route('tasks.create-via-voice'), formData)

        if (response.data.success) {
            console.log(response.data.result);
            // emit('created', response.data.result);
            closeModal();
        }
    } catch (error) {
        console.error('Error creating task via voice:', error);
    }
}

function generateWavePath(phase = 0, amplitude = 2) {
    let path = 'M0,10 ';
    for (let i = 0; i <= points; i += 1) {
        const x = (i / points) * 100;
        const y = 10 + Math.sin((i / points) * Math.PI * 2 + phase) * amplitude;
        path += `L${x},${y} `;
    }
    path += 'L100,20 L0,20 Z';
    return path;
}

function startWave(pathRef, speed = 0.02, amplitude = 4) {
    let phase = 0;
    let frameId = null;

    const tick = () => {
        phase += speed;
        if (pathRef.value) {
            pathRef.value.setAttribute('d', generateWavePath(phase, amplitude));
        }
        frameId = requestAnimationFrame(tick);
    };

    tick();
    return () => {
        if (frameId) {
            cancelAnimationFrame(frameId);
        }
    };
}

onMounted(() => {
    waveAnimation1 = startWave(wave1, 0.02, 3);
    waveAnimation2 = startWave(wave2, 0.01, 5);
});

onBeforeUnmount(() => {
    if (waveAnimation1) {
        waveAnimation1();
    }
    if (waveAnimation2) {
        waveAnimation2();
    }
    if (isRecording.value) {
        stopRecording();
    }
    clearAudio();
});

function openModal() {
    showModal.value = true;
}
</script>

<template>
    <button type="button" class="primary-btn-white-blur w-full justify-between text-sm" @click="openModal">
        Записать голос
        <i class="fa-solid fa-microphone" />
    </button>

    <Modal :show="showModal" max-width="2xl" :mobile-full-screen="true" @close="closeModal">
        <div class="min-h-dvh p-6 md:min-h-0 md:p-8 space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="title-2">Запись голоса</h2>
                <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" @click="closeModal">
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <div class="flex flex-col items-center justify-center gap-2">
                <div class="flex flex-col items-center justify-center gap-4 min-h-[calc(100dvh-10rem)]">
                    <button
                        type="button"
                        class="h-24 w-24 rounded-full border-2 transition-colors duration-200 flex items-center justify-center"
                        :class="isRecording ? 'bg-[var(--accent)] border-[var(--accent)] text-white' : 'bg-content-glass border-white text-[var(--accent)]'"
                        @click="toggleRecording"
                    >
                        <i class="fa-solid text-2xl" :class="isRecording ? 'fa-stop' : 'fa-microphone'" />
                    </button>

                    <div v-if="isRecording" class="w-full max-w-sm rounded-full overflow-hidden">
                        <svg viewBox="0 0 100 20" preserveAspectRatio="none" class="w-full h-5 overflow-hidden">
                            <path ref="wave1" fill="rgba(233,115,88,0.25)" />
                            <path ref="wave2" fill="rgba(233,115,88,0.45)" />
                        </svg>
                    </div>

                    <p class="context text-center">
                        {{ isRecording ? 'Идет запись... нажмите кнопку, чтобы остановить.' : 'Нажмите кнопку для старта записи.' }}
                    </p>
                </div>

                <div v-if="errorMessage" class="badge badge-pending w-full justify-center">
                    {{ errorMessage }}
                </div>

                <div v-if="audioUrl" class="space-y-2">
                    <p class="t-small">Дебаг: прослушивание записи</p>
                    <audio class="w-full" :src="audioUrl" controls />
                </div>
            </div>
        </div>
    </Modal>
</template>

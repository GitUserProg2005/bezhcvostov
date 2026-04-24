<script setup>
import { onBeforeUnmount, ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const emit = defineEmits(['created']);

const showModal = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const mediaStream = ref(null);
const capturedImageUrl = ref('');
const isBusy = ref(false);
const errorMessage = ref('');

async function openModal() {
    showModal.value = true;
    await startCamera();
}

function closeModal() {
    stopCamera();
    resetCapture();
    showModal.value = false;
}

function resetCapture() {
    if (capturedImageUrl.value) {
        URL.revokeObjectURL(capturedImageUrl.value);
    }
    capturedImageUrl.value = '';
    errorMessage.value = '';
}

async function startCamera() {
    errorMessage.value = '';

    try {
        mediaStream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false,
        });

        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream.value;
            await videoRef.value.play();
        }
    } catch (error) {
        errorMessage.value = 'Не удалось открыть камеру.';
    }
}

function stopCamera() {
    mediaStream.value?.getTracks()?.forEach((track) => track.stop());
    mediaStream.value = null;
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
}

function capturePhoto() {
    if (!videoRef.value || !canvasRef.value) {
        return;
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = 300;
    canvas.height = 300;

    const context = canvas.getContext('2d');
    if (!context) {
        return;
    }

    const sourceWidth = video.videoWidth;
    const sourceHeight = video.videoHeight;
    const sourceSize = Math.min(sourceWidth, sourceHeight);
    const sx = (sourceWidth - sourceSize) / 2;
    const sy = (sourceHeight - sourceSize) / 2;

    context.drawImage(video, sx, sy, sourceSize, sourceSize, 0, 0, canvas.width, canvas.height);
    canvas.toBlob((blob) => {
        if (!blob) {
            return;
        }

        resetCapture();
        capturedImageUrl.value = URL.createObjectURL(blob);
    }, 'image/jpeg', 0.9);
}

function retakePhoto() {
    resetCapture();
}

function canvasToBlob() {
    return new Promise((resolve) => {
        canvasRef.value?.toBlob((blob) => resolve(blob), 'image/jpeg', 0.8);
    });
}

async function sendPhoto() {
    if (!canvasRef.value) {
        return;
    }

    isBusy.value = true;
    errorMessage.value = '';

    try {
        const blob = await canvasToBlob();
        if (!blob) {
            throw new Error('Не удалось сформировать изображение');
        }

        const formData = new FormData();
        formData.append('image_file', blob, 'task-photo.jpg');

        const response = await axios.post(route('tasks.create-via-photo'), formData);
        if (response.data?.success) {
            emit('created');
            closeModal();
            return;
        }

        throw new Error('Сервис вернул ошибку');
    } catch (error) {
        errorMessage.value = 'Не удалось обработать фото.';
    } finally {
        isBusy.value = false;
    }
}

onBeforeUnmount(() => {
    stopCamera();
    resetCapture();
});
</script>

<template>
    <button type="button" class="primary-btn-white-blur w-full justify-between text-sm" @click="openModal">
        Сфотографировать
        <i class="fa-solid fa-camera" />
    </button>

    <Modal :show="showModal" max-width="3xl" :mobile-full-screen="true" @close="closeModal">
        <div class="min-h-dvh md:min-h-0 p-4 md:p-6 space-y-4 bg-content-dark text-white">
            <div class="flex items-center justify-between">
                <h2 class="title-2-on-dark">Фото задачи</h2>
                <button type="button" class="round-btn round-btn-neutral" @click="closeModal">
                    <i class="fa-solid fa-xmark" />
                </button>
            </div>

            <div class="rounded-2xl overflow-hidden bg-black/60 border border-white/10 aspect-[3/4] md:aspect-video">
                <video v-show="!capturedImageUrl" ref="videoRef" class="h-full w-full object-cover" playsinline muted autoplay />
                <img v-if="capturedImageUrl" :src="capturedImageUrl" alt="Captured photo" class="h-full w-full object-cover" />
            </div>

            <canvas ref="canvasRef" class="hidden" />

            <div class="flex items-center gap-2">
                <button v-if="!capturedImageUrl" type="button" class="primary-btn w-full justify-center" @click="capturePhoto">
                    Сделать фото
                </button>
                <button v-if="capturedImageUrl" type="button" class="primary-btn-white-blur w-full justify-center" @click="retakePhoto">
                    Переснять
                </button>
                <button
                    v-if="capturedImageUrl"
                    type="button"
                    class="primary-btn w-full justify-center"
                    :disabled="isBusy"
                    @click="sendPhoto"
                >
                    {{ isBusy ? 'Обработка...' : 'Отправить' }}
                </button>
            </div>

            <p v-if="errorMessage" class="badge badge-pending w-full justify-center">{{ errorMessage }}</p>
        </div>
    </Modal>
</template>
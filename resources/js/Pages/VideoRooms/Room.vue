<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    link_id: { type: String, required: true },
    room: { type: Object, required: true },
    is_host: { type: Boolean, default: false },
});

const { props: pageProps } = usePage();
const user = pageProps.auth.user;

const isConnecting = ref(false);
const isStarted = ref(false);
const connectError = ref('');
const copied = ref(false);
const isMuted = ref(false);
const isVideoOff = ref(false);
const pendingCandidates = ref([]);
const videoDevices = ref([]);
const selectedCameraId = ref(null);
const isAnalyzing = ref(false);
const isRecordingInsight = ref(false);
const insightError = ref('');
const latestInsight = ref(null);

let pc = null;
let channel = null;
let insightRecorder = null;
let insightChunks = [];

const localStream = ref(null);
const remoteStream = ref(null);
const localVideo = ref(null);
const remoteVideo = ref(null);

const roomLink = computed(() => route('video.join.room', props.link_id));
const hasRemoteMedia = computed(() => Boolean(remoteStream.value?.getTracks?.().length));

function debugLog(step, payload = null) {
    const stamp = new Date().toISOString();
    const prefix = `[VideoRoom][${stamp}][link:${props.link_id}][user:${user?.id}] ${step}`;
    if (payload === null || payload === undefined) {
        console.log(prefix);
        return;
    }
    console.log(prefix, payload);
}

async function bindVideoElement(el, stream, label) {
    if (!el) {
        debugLog(`${label}:bind:skip:no-element`);
        return;
    }

    el.srcObject = stream ?? null;
    debugLog(`${label}:bind:set-src`, {
        hasStream: Boolean(stream),
        tracks: stream?.getTracks?.().map((t) => t.kind) ?? [],
    });

    if (!stream) {
        return;
    }

    try {
        await el.play();
        debugLog(`${label}:bind:play:ok`);
    } catch (error) {
        debugLog(`${label}:bind:play:error`, {
            name: error?.name,
            message: error?.message,
        });
    }
}

function normalizeSessionDescription(desc, fallbackType) {
    if (!desc) {
        return null;
    }

    const type = String(desc.type ?? fallbackType ?? '').trim();
    const rawSdp = String(desc.sdp ?? '');
    const normalizedLf = rawSdp
        .replace(/\u0000/g, '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n');

    const lines = normalizedLf
        .split('\n')
        .map((line) => line.trimEnd())
        .filter((line) => line.length > 0 && /^[a-z]=/i.test(line));

    const sdp = lines.join('\r\n') + '\r\n';
    return type && sdp ? { type, sdp } : null;
}

function ensureChannel() {
    debugLog('ensureChannel:attempt', { hasChannel: Boolean(channel), hasEcho: typeof window.Echo !== 'undefined' });
    if (channel || typeof window.Echo === 'undefined') {
        debugLog('ensureChannel:skip', { reason: channel ? 'already_initialized' : 'echo_undefined' });
        return;
    }

    debugLog('ensureChannel:subscribe', { channel: `video-call.${props.link_id}` });
    channel = window.Echo.private(`video-call.${props.link_id}`).listen('.video-call.signal', async (e) => {
        debugLog('socket:event:received', e);
        if (e.from === user.id) {
            debugLog('socket:event:ignored-own');
            return;
        }

        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;

        switch (e.type) {
            case 'ready':
                if (props.is_host && isStarted.value && pc && !pc.currentRemoteDescription) {
                    await sendOffer();
                }
                break;
            case 'offer':
                await handleOffer(data);
                break;
            case 'answer':
                await handleAnswer(data);
                break;
            case 'candidate':
                await handleCandidate(data);
                break;
            default:
                debugLog('socket:event:unknown-type', { type: e.type });
                break;
        }
    });
}

async function copyRoomLink() {
    debugLog('copyRoomLink:attempt', { roomLink: roomLink.value });
    try {
        await navigator.clipboard.writeText(roomLink.value);
        copied.value = true;
        debugLog('copyRoomLink:success');
        setTimeout(() => {
            copied.value = false;
        }, 1800);
    } catch {
        copied.value = false;
        debugLog('copyRoomLink:error');
    }
}

function createPeerConnectionIfNeeded() {
    debugLog('pc:createIfNeeded', { exists: Boolean(pc) });
    if (pc) {
        return;
    }

    pc = new RTCPeerConnection({
        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
    });
    debugLog('pc:created');

    pc.onicecandidate = (e) => {
        debugLog('pc:onicecandidate', { hasCandidate: Boolean(e.candidate) });
        if (e.candidate) {
            sendSignal('candidate', e.candidate);
        }
    };

    pc.ontrack = (e) => {
        debugLog('pc:ontrack', {
            streams: e.streams?.length ?? 0,
            trackKind: e.track?.kind,
            trackId: e.track?.id,
        });

        if (e.streams?.[0]) {
            remoteStream.value = e.streams[0];
            return;
        }

        // Fallback для кейсов, где браузер не присылает streams[].
        if (!remoteStream.value) {
            remoteStream.value = new MediaStream();
        }

        const exists = remoteStream.value.getTracks().some((t) => t.id === e.track.id);
        if (!exists) {
            remoteStream.value.addTrack(e.track);
        }
    };

    pc.onconnectionstatechange = () => {
        debugLog('pc:connectionstatechange', { state: pc?.connectionState });
    };

    pc.oniceconnectionstatechange = () => {
        debugLog('pc:iceconnectionstatechange', { state: pc?.iceConnectionState });
    };

    localStream.value?.getTracks().forEach((track) => {
        debugLog('pc:addTrack', { kind: track.kind, enabled: track.enabled });
        pc.addTrack(track, localStream.value);
    });
}

async function sendSignal(type, data) {
    debugLog('signal:send:attempt', { type, hasData: data !== undefined && data !== null });
    try {
        const payload = {
            link_id: props.link_id,
            type,
            data,
        };
        const response = await axios.post(route('video.signal'), payload);
        debugLog('signal:send:success', { type, status: response.status });
    } catch (error) {
        debugLog('signal:send:error', {
            type,
            message: error?.message,
            status: error?.response?.status,
            response: error?.response?.data,
        });
        if (isStarted.value) {
            connectError.value = 'Проблема с каналом сигналинга. Проверьте соединение и попробуйте снова.';
        }
        throw error;
    }
}

async function loadDevices() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        videoDevices.value = devices.filter((d) => d.kind === 'videoinput');
        debugLog('media:devices:loaded', {
            count: videoDevices.value.length,
            current: selectedCameraId.value,
        });

        if (!selectedCameraId.value && videoDevices.value.length) {
            selectedCameraId.value = videoDevices.value[0].deviceId;
            debugLog('media:devices:auto-selected', { selected: selectedCameraId.value });
        }
    } catch (error) {
        debugLog('media:devices:error', { message: error?.message });
    }
}

async function getStream() {
    return navigator.mediaDevices.getUserMedia({
        video: {
            deviceId: selectedCameraId.value
                ? { exact: selectedCameraId.value }
                : undefined,
        },
        audio: {
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true,
            sampleRate: 48000,
            channelCount: 1,
        },
    });
}

async function ensureLocalMedia() {
    debugLog('media:ensure:attempt', { hasStream: Boolean(localStream.value) });
    if (localStream.value) {
        return;
    }

    // Сначала запрашиваем доступ к камере, затем корректно получаем список устройств.
    const permissionProbe = await navigator.mediaDevices.getUserMedia({ video: true });
    permissionProbe.getTracks().forEach((t) => t.stop());
    await loadDevices();

    localStream.value = await getStream();
    debugLog('media:ensure:success', {
        audioTracks: localStream.value.getAudioTracks().length,
        videoTracks: localStream.value.getVideoTracks().length,
    });
}

async function sendOffer() {
    debugLog('offer:send:attempt', { hasPc: Boolean(pc) });
    if (!pc) {
        return;
    }
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    debugLog('offer:localDescription:set', { type: offer.type });
    await sendSignal('offer', {
        type: offer.type,
        sdp: offer.sdp,
    });
}

async function connectToCall() {
    debugLog('connect:attempt', { isConnecting: isConnecting.value, isStarted: isStarted.value, isHost: props.is_host });
    if (isConnecting.value || isStarted.value) {
        debugLog('connect:skip');
        return;
    }

    isConnecting.value = true;
    connectError.value = '';

    try {
        ensureChannel();
        await ensureLocalMedia();
        createPeerConnectionIfNeeded();
        isStarted.value = true;
        debugLog('connect:started');

        if (props.is_host) {
            debugLog('connect:host-send-offer');
            await sendOffer();
        } else {
            debugLog('connect:guest-send-ready');
            await sendSignal('ready', { joined: true });
        }
    } catch (error) {
        debugLog('connect:error', { message: error?.message, status: error?.response?.status });
        connectError.value = 'Не удалось получить доступ к камере/микрофону. Разрешите доступ и попробуйте снова.';
        isStarted.value = false;
    } finally {
        isConnecting.value = false;
    }
}

async function handleOffer(offer) {
    debugLog('offer:handle:received');
    if (!isStarted.value && !isConnecting.value) {
        debugLog('offer:handle:autoconnect');
        await connectToCall();
    }
    createPeerConnectionIfNeeded();
    const normalizedOffer = normalizeSessionDescription(offer, 'offer');
    if (!normalizedOffer) {
        debugLog('offer:handle:invalid-payload', offer);
        throw new Error('Получен некорректный offer SDP');
    }

    try {
        await pc.setRemoteDescription(normalizedOffer);
    } catch (error) {
        debugLog('offer:handle:setRemoteDescription:error', {
            message: error?.message,
            offerType: normalizedOffer.type,
            sdpPreview: normalizedOffer.sdp.split('\r\n').slice(0, 12),
        });
        throw error;
    }
    debugLog('offer:handle:remoteDescription:set');
    if (pendingCandidates.value.length) {
        debugLog('offer:handle:flush-candidates', { count: pendingCandidates.value.length });
        for (const queued of pendingCandidates.value) {
            await pc.addIceCandidate(new RTCIceCandidate(queued));
        }
        pendingCandidates.value = [];
    }
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    debugLog('offer:handle:answer-created');
    await sendSignal('answer', {
        type: answer.type,
        sdp: answer.sdp,
    });
}

async function handleAnswer(answer) {
    debugLog('answer:handle:received', { hasPc: Boolean(pc) });
    if (!pc) {
        return;
    }
    const normalizedAnswer = normalizeSessionDescription(answer, 'answer');
    if (!normalizedAnswer) {
        debugLog('answer:handle:invalid-payload', answer);
        return;
    }

    try {
        await pc.setRemoteDescription(normalizedAnswer);
    } catch (error) {
        debugLog('answer:handle:setRemoteDescription:error', {
            message: error?.message,
            answerType: normalizedAnswer.type,
            sdpPreview: normalizedAnswer.sdp.split('\r\n').slice(0, 12),
        });
        throw error;
    }
    debugLog('answer:handle:remoteDescription:set');
    if (pendingCandidates.value.length) {
        debugLog('answer:handle:flush-candidates', { count: pendingCandidates.value.length });
        for (const queued of pendingCandidates.value) {
            await pc.addIceCandidate(new RTCIceCandidate(queued));
        }
        pendingCandidates.value = [];
    }
}

async function handleCandidate(candidate) {
    debugLog('candidate:handle:received', { hasRemoteDescription: Boolean(pc?.remoteDescription) });
    if (pc?.remoteDescription) {
        await pc.addIceCandidate(new RTCIceCandidate(candidate));
        debugLog('candidate:handle:added');
        return;
    }
    pendingCandidates.value.push(candidate);
    debugLog('candidate:handle:queued', { queueLength: pendingCandidates.value.length });
}

function toggleMute() {
    debugLog('toggleMute:attempt', { hasStream: Boolean(localStream.value) });
    if (!localStream.value) {
        return;
    }
    isMuted.value = !isMuted.value;
    localStream.value.getAudioTracks().forEach((track) => {
        track.enabled = !isMuted.value;
    });
    debugLog('toggleMute:done', { isMuted: isMuted.value });
}

function toggleVideo() {
    debugLog('toggleVideo:attempt', { hasStream: Boolean(localStream.value) });
    if (!localStream.value) {
        return;
    }
    isVideoOff.value = !isVideoOff.value;
    localStream.value.getVideoTracks().forEach((track) => {
        track.enabled = !isVideoOff.value;
    });
    debugLog('toggleVideo:done', { isVideoOff: isVideoOff.value });
}

function endCall() {
    debugLog('endCall:attempt');
    if (pc) {
        pc.close();
        pc = null;
    }

    if (localStream.value) {
        localStream.value.getTracks().forEach((t) => t.stop());
    }
    if (isRecordingInsight.value) {
        stopInsightRecording();
    }
    if (remoteStream.value) {
        remoteStream.value.getTracks().forEach((t) => t.stop());
    }

    localStream.value = null;
    remoteStream.value = null;
    isStarted.value = false;
    isConnecting.value = false;
    isMuted.value = false;
    isVideoOff.value = false;
    pendingCandidates.value = [];
    debugLog('endCall:done');
}

function canUseRecorder() {
    return typeof window !== 'undefined' && typeof window.MediaRecorder !== 'undefined';
}

function startInsightRecording() {
    insightError.value = '';

    if (!localStream.value) {
        insightError.value = 'Нет локального аудио-потока для анализа.';
        return;
    }

    if (!canUseRecorder()) {
        insightError.value = 'Браузер не поддерживает запись аудио (MediaRecorder).';
        return;
    }

    const audioTracks = localStream.value.getAudioTracks();
    if (!audioTracks.length) {
        insightError.value = 'Микрофон недоступен для записи.';
        return;
    }

    try {
        const stream = new MediaStream(audioTracks);
        const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
            ? 'audio/webm;codecs=opus'
            : (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus') ? 'audio/ogg;codecs=opus' : 'audio/webm');

        insightChunks = [];
        insightRecorder = new MediaRecorder(stream, { mimeType, audioBitsPerSecond: 128000 });
        insightRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                insightChunks.push(event.data);
            }
        };
        insightRecorder.onerror = (event) => {
            debugLog('insight:record:error', event);
            insightError.value = 'Ошибка записи аудио.';
            isRecordingInsight.value = false;
        };
        insightRecorder.start(1000);
        isRecordingInsight.value = true;
        debugLog('insight:record:start');
    } catch (error) {
        debugLog('insight:record:start:error', { message: error?.message });
        insightError.value = 'Не удалось запустить запись.';
    }
}

async function uploadInsightRecording() {
    if (!insightChunks.length) {
        insightError.value = 'Запись пуста. Повторите попытку.';
        return;
    }

    isAnalyzing.value = true;
    insightError.value = '';
    const recordedType = insightChunks[0]?.type || 'audio/webm';
    const blob = new Blob(insightChunks, { type: recordedType });
    debugLog('insight:upload:blob', { type: blob.type, size: blob.size });
    const formData = new FormData();
    formData.append('audio_file', blob, `insight-${Date.now()}.webm`);
    formData.append('link_id', props.link_id);

    try {
        const { data } = await axios.post(route('video.insights.create-from-audio'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        if (data?.success) {
            latestInsight.value = data.insight;
            debugLog('insight:upload:success', { id: data.insight?.id });
        }
    } catch (error) {
        debugLog('insight:upload:error', {
            status: error?.response?.status,
            message: error?.message,
            data: error?.response?.data,
        });
        insightError.value = error?.response?.data?.message ?? 'Не удалось обработать запись.';
    } finally {
        isAnalyzing.value = false;
    }
}

function stopInsightRecording() {
    if (!insightRecorder || insightRecorder.state !== 'recording') {
        isRecordingInsight.value = false;
        return;
    }

    insightRecorder.onstop = async () => {
        debugLog('insight:record:stop', { chunks: insightChunks.length });
        isRecordingInsight.value = false;
        await uploadInsightRecording();
        insightRecorder = null;
    };
    insightRecorder.stop();
}

function toggleInsightRecording() {
    if (isAnalyzing.value) {
        return;
    }

    if (isRecordingInsight.value) {
        stopInsightRecording();
        return;
    }

    startInsightRecording();
}

watch(localStream, (stream) => {
    bindVideoElement(localVideo.value, stream, 'localVideo');
});

watch(remoteStream, (stream) => {
    bindVideoElement(remoteVideo.value, stream, 'remoteVideo');
});

watch(localVideo, (el) => {
    if (el) {
        bindVideoElement(el, localStream.value, 'localVideo');
    }
});

watch(remoteVideo, (el) => {
    if (el) {
        bindVideoElement(el, remoteStream.value, 'remoteVideo');
    }
});

watch(selectedCameraId, async (nextId, prevId) => {
    if (!nextId || nextId === prevId || !isStarted.value) {
        return;
    }
    debugLog('media:camera:changed', { prevId, nextId });

    try {
        const nextStream = await getStream();
        const nextVideoTrack = nextStream.getVideoTracks()[0] ?? null;
        const nextAudioTracks = nextStream.getAudioTracks();

        if (!nextVideoTrack || !localStream.value) {
            nextStream.getTracks().forEach((t) => t.stop());
            return;
        }

        // Переключаем только видеотрек, чтобы не ронять текущий звонок.
        const oldVideoTracks = localStream.value.getVideoTracks();
        oldVideoTracks.forEach((track) => {
            localStream.value.removeTrack(track);
            track.stop();
        });
        localStream.value.addTrack(nextVideoTrack);

        if (pc) {
            const sender = pc.getSenders().find((s) => s.track?.kind === 'video');
            if (sender) {
                await sender.replaceTrack(nextVideoTrack);
            }
        }

        nextAudioTracks.forEach((track) => track.stop());
        debugLog('media:camera:switched');
    } catch (error) {
        debugLog('media:camera:switch:error', { message: error?.message });
    }
});

onMounted(() => {
    debugLog('lifecycle:mounted', { isHost: props.is_host, roomId: props.room?.id });
    ensureChannel();
    connectToCall();
});

onBeforeUnmount(() => {
    debugLog('lifecycle:beforeUnmount');
    if (channel) {
        window.Echo.leave(`private-video-call.${props.link_id}`);
        channel = null;
    }
    endCall();
});
</script>

<template>
    <div class="flex-1 h-[100vh]">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="hidden lg:flex title-2">{{ room?.title ?? 'Комната' }}</h2>
                <span class="badge badge-neutral">{{ is_host ? 'Вы ведущий' : 'Участник' }}</span>
            </div>

            <div v-if="!isStarted" class="content-glass p-5 flex flex-col gap-3">
                <p class="context">Нажмите кнопку ниже, чтобы подключиться к комнате.</p>
                <button type="button" class="primary-btn w-full md:w-auto" :disabled="isConnecting" @click="connectToCall">
                    {{ isConnecting ? 'Подключаю...' : 'Подключиться к звонку' }}
                </button>
                <p v-if="connectError" class="text-sm text-red-400">{{ connectError }}</p>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 min-h-[18rem]">
                <div :class="isStarted ? '' : 'content-dark'" class="relative flex items-center justify-center min-h-[10rem] rounded-2xl overflow-hidden">
                    <video ref="localVideo" class="w-full h-full object-cover" autoplay muted playsinline />
                    <span v-if="!isStarted" class="absolute context px-3 text-center">Подключение локального видео...</span>
                </div>

                <div
                    v-if="hasRemoteMedia"
                    :class="isStarted ? '' : 'content-dark'"
                    class="relative flex items-center justify-center min-h-[10rem] rounded-2xl overflow-hidden"
                >
                    <video ref="remoteVideo" class="w-full h-full object-cover" autoplay playsinline />
                </div>
                <div
                    v-else
                    class="content-glass p-4 md:p-5 rounded-2xl flex flex-col justify-center gap-4"
                >
                    <h3 class="title-2">Ссылка на комнату</h3>
                    <p class="context">Чтобы пригласить участника, отправьте ссылку на встречу.</p>
                    <div class="content-outline p-3 break-all text-sm">{{ roomLink }}</div>

                    <button type="button" class="primary-btn w-full md:w-auto" @click="copyRoomLink">
                        {{ copied ? 'Ссылка скопирована' : 'Скопировать ссылку' }}
                        <i class="fa-solid fa-paper-plane ml-2"></i>
                    </button>

                    <div class="space-y-2">
                        <p class="context">Камера</p>
                        <select v-model="selectedCameraId" class="input w-full">
                            <option v-for="cam in videoDevices" :key="cam.deviceId" :value="cam.deviceId">
                                {{ cam.label || 'Камера' }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between gap-2 md:gap-0">
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button class="primary-btn-white-blur w-full" :disabled="!isStarted" @click="toggleMute">
                        <i class="fa-solid" :class="isMuted ? 'fa-microphone-slash' : 'fa-microphone'" />
                    </button>
                    <button class="primary-btn-white-blur w-full md:w-auto" :disabled="!isStarted" @click="toggleVideo">
                        <i class="fa-solid" :class="isVideoOff ? 'fa-video-slash' : 'fa-video'" />
                    </button>
                </div>

                <button
                    v-if="!is_host"
                    class="primary-btn flex items-center justify-center gap-2 w-full md:w-auto"
                    :disabled="!isStarted || isAnalyzing"
                    @click="toggleInsightRecording"
                >
                    {{ isRecordingInsight ? 'Остановить и анализировать' : (isAnalyzing ? 'Анализируем...' : 'Начать анализ урока') }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                        aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                    </svg> 
                </button>

                <button class="primary-btn-red-blur w-full md:w-auto" :disabled="!isStarted" @click="endCall">
                    <i class="fa-solid fa-phone-slash" />
                </button>
            </div>

            <div v-if="insightError" class="content-outline p-3">
                <p class="text-sm text-red-400">{{ insightError }}</p>
            </div>

            <div v-if="latestInsight" class="content-glass p-3">
                <p class="context text-sm">
                    Инсайт сохранен.
                    <a :href="route('video.insights.index')" class="underline decoration-dotted">Открыть историю</a>
                </p>
            </div>
        </div>
    </div>
</template>

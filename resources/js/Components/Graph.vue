<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import * as d3 from 'd3-force';
import { VNetworkGraph, defineConfigs } from 'v-network-graph';
import 'v-network-graph/lib/style.css';

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    notes: {
        type: Array,
        default: () => [],
    },
});
const currentZoomLevel = ref(1);

function difficultyColor(difficulty) {
    if (difficulty === 'easy') return '#58ee69';
    if (difficulty === 'hard') return '#ee5862';
    return '#eeec58';
}

function difficultyClusterX(difficulty) {
    if (difficulty === 'easy') return -90;
    if (difficulty === 'hard') return 90;
    return 0;
}

const taskNodes = computed(() =>
    props.tasks.map((task) => ({
        id: `task-${task.id}`,
        rawId: task.id,
        type: 'task',
        title: task.title,
        difficulty: task.difficulty ?? 'medium',
        color: difficultyColor(task.difficulty),
        size: 16,
    })),
);

const noteNodes = computed(() =>
    props.notes.map((note) => ({
        id: `note-${note.id}`,
        rawId: note.id,
        type: 'note',
        title: note.title,
        taskId: note.task_id,
        color: '#7fb3ff',
        size: 12,
    })),
);

const nodeMap = computed(() => {
    const out = {};
    [...taskNodes.value, ...noteNodes.value].forEach((node) => {
        out[node.id] = node;
    });
    return out;
});

const edgeMap = computed(() => {
    const taskNodeIds = new Set(taskNodes.value.map((node) => node.rawId));
    const out = {};
    noteNodes.value
        .filter((note) => note.taskId && taskNodeIds.has(note.taskId))
        .forEach((note) => {
            out[`edge-note-${note.rawId}-task-${note.taskId}`] = {
                source: note.id,
                target: `task-${note.taskId}`,
            };
        });
    return out;
});

const configs = reactive(defineConfigs({
    view: {
        autoPanAndZoomOnLoad: 'fit-content',
        panEnabled: true,
        zoomEnabled: true,
        mouseWheelZoomEnabled: true,
        minZoomLevel: 0.1,
        maxZoomLevel: 15,
        onSvgPanZoomInitialized: (instance) => {
            currentZoomLevel.value = instance.getRealZoom?.() ?? 1;

            const panZoom = instance;
            if (typeof panZoom.setZoomScaleSensitivity === 'function') {
                panZoom.setZoomScaleSensitivity(0.35);
            }
            if (typeof panZoom.setOnZoom === 'function') {
                panZoom.setOnZoom((zoom) => {
                    currentZoomLevel.value = zoom ?? 1;
                });
            }
        },
        grid: { visible: false },
    },
    node: {
        normal: {
            type: 'circle',
            radius: (node) => (node.size ?? 12) / 2,
            color: (node) => node.color ?? '#7fb3ff',
            strokeColor: 'rgba(255,255,255,0.14)',
            strokeWidth: 1,
        },
        hover: {
            radius: (node) => (node.size ?? 12) / 2 + 1,
        },
        label: {
            visible: false,
            text: (node) => node.title ?? '',
            direction: 'south',
            fontSize: 11,
            lineHeight: 1.1,
            color: 'var(--content-secondary)',
            margin: 6,
        },
    },
    edge: {
        normal: {
            color: 'rgba(255,255,255,0.12)',
            width: 0.8,
        },
    },
}));

watch(currentZoomLevel, (zoom) => {
    configs.node.label.visible = zoom >= 1.35;
}, { immediate: true });

const layouts = reactive({ nodes: {} });
let simulation = null;

function rebuildSimulation() {
    simulation?.stop();

    const nodes = Object.entries(nodeMap.value).map(([id, node]) => ({
        id,
        ...node,
        x: layouts.nodes[id]?.x ?? (Math.random() - 0.5) * 220,
        y: layouts.nodes[id]?.y ?? (Math.random() - 0.5) * 220,
    }));

    const edges = Object.values(edgeMap.value).map((edge) => ({
        source: edge.source,
        target: edge.target,
    }));

    if (!nodes.length) {
        layouts.nodes = {};
        return;
    }

    const radius = 115;
    simulation = d3
        .forceSimulation(nodes)
        .alpha(1)
        .alphaDecay(0.035)
        .force('link', d3.forceLink(edges).id((d) => d.id).distance(48).strength(0.42))
        .force('charge', d3.forceManyBody().strength(-78))
        .force('collision', d3.forceCollide().radius((d) => ((d.size ?? 12) / 2) + 3))
        .force('cluster-x', d3.forceX((d) => (d.type === 'task' ? difficultyClusterX(d.difficulty) : 0)).strength(0.06))
        .force('center', d3.forceCenter(0, 0).strength(0.2))
        .force('radial', d3.forceRadial((d) => (d.type === 'note' ? radius * 0.55 : radius), 0, 0).strength(0.07))
        .on('tick', () => {
            const next = {};
            nodes.forEach((node) => {
                next[node.id] = { x: node.x, y: node.y };
            });
            layouts.nodes = next;
        });
}

onMounted(() => {
    rebuildSimulation();
});

watch([nodeMap, edgeMap], () => {
    rebuildSimulation();
}, { deep: true });

onBeforeUnmount(() => {
    simulation?.stop();
});
</script>

<template>
    <VNetworkGraph
        :nodes="nodeMap"
        :edges="edgeMap"
        :configs="configs"
        :layouts="layouts"
        class="fox-circle-graph"
    />
</template>

<style scoped>
.fox-circle-graph {
    width: 100%;
    height: 300px;
    background: transparent;
}
</style>

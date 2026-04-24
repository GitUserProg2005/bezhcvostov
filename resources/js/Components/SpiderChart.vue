<script setup>
import { onMounted, ref } from 'vue'
import {
  Chart,
  RadarController,
  RadialLinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip
} from 'chart.js'

Chart.register(
  RadarController,
  RadialLinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip
)

const props = defineProps({
  values: {
    type: Array,
    default: () => [0, 100, 100, 100, 100, 100]
  }
})

const chartRef = ref(null)

onMounted(() => {
  new Chart(chartRef.value, {
    type: 'radar',
    data: {
      labels: [
        'Хаос',
        'Мудрость',
        'Логика',
        'Творчество',
        'Сила воли',
        'Интуиция'
      ],
      datasets: [
        {
          data: props.values,
          backgroundColor: 'rgba(233, 115, 88, 0.12)',
          borderColor: '#e97358',
          borderWidth: 2,
          pointRadius: 0
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,

      plugins: {
        legend: {
          display: false
        }
      },

      elements: {
        line: {
          tension: 0
        }
      },

      scales: {
        r: {
          beginAtZero: true,
          min: 0,
          max: 100,

          grid: {
            color: 'rgba(0,0,0,0.08)'
          },

          angleLines: {
            color: 'rgba(0,0,0,0.08)'
          },

          ticks: {
            display: false
          }
        }
      }
    }
  })
})
</script>

<template>
  <div class="w-full h-[260px]">
    <canvas ref="chartRef"></canvas>
  </div>
</template>
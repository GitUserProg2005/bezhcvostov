<script setup>
import { computed } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  autoplay: {
    type: Boolean,
    default: true,
  },
  navigation: {
    type: Boolean,
    default: true,
  },
  pagination: {
    type: [Boolean, Object],
    default: true,
  },
  slidesPerView: {
    type: Number,
    default: 1,
  },
  spaceBetween: {
    type: Number,
    default: 14,
  },
  breakpoints: {
    type: Object,
    default: undefined,
  },
  /** Отступ снизу под пагинацию, боковые отступы под стрелки (лендинг / outline-карусель) */
  spacedLayout: {
    type: Boolean,
    default: false,
  },
});

const modules = [Navigation, Pagination, Autoplay];

const autoplayOptions = computed(() => {
  if (!props.autoplay) return false;
  return { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true };
});

const paginationOptions = computed(() => {
  if (props.pagination === false) return false;
  if (props.pagination === true) return { clickable: true };
  return props.pagination;
});
</script>

<template>
  <Swiper
    :class="['ui-carousel', { 'ui-carousel--spaced': spacedLayout }]"
    :modules="modules"
    :slides-per-view="slidesPerView"
    :space-between="spaceBetween"
    :breakpoints="breakpoints"
    :navigation="navigation"
    :pagination="paginationOptions"
    :autoplay="autoplayOptions"
  >
    <SwiperSlide v-for="(item, idx) in items" :key="idx">
      <slot :item="item" :index="idx" />
    </SwiperSlide>
  </Swiper>
</template>

<style>
.ui-carousel {
  --swiper-navigation-color: #111827;
  --swiper-pagination-color: #111827;
}

.ui-carousel .swiper-button-prev,
.ui-carousel .swiper-button-next {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.6);
}

.ui-carousel .swiper-button-prev:after,
.ui-carousel .swiper-button-next:after {
  font-size: 14px;
  font-weight: 700;
}

.ui-carousel .swiper-pagination-bullet {
  background: rgba(17, 24, 39, 0.35);
  opacity: 1;
}

.ui-carousel .swiper-pagination-bullet-active {
  background: rgba(17, 24, 39, 0.9);
}

html.dark .ui-carousel {
  --swiper-navigation-color: #f3f4f6;
  --swiper-pagination-color: #f3f4f6;
}

html.dark .ui-carousel .swiper-pagination-bullet {
  background: rgba(255, 255, 255, 0.28);
}

html.dark .ui-carousel .swiper-pagination-bullet-active {
  background: rgba(255, 255, 255, 0.88);
}

/* Тёмная тема: стрелки заметнее (не сливаются с фоном) */
html.dark .ui-carousel .swiper-button-prev,
html.dark .ui-carousel .swiper-button-next {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background-color: rgba(255, 255, 255, 0.12);
  background-image: none;
  border: 1px solid rgba(255, 255, 255, 0.45);
  box-shadow:
    0 8px 28px rgba(0, 0, 0, 0.55),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
  --swiper-navigation-color: #ffffff;
}

html.dark .ui-carousel .swiper-button-prev:hover,
html.dark .ui-carousel .swiper-button-next:hover {
  background-color: rgba(233, 115, 88, 0.35);
  border-color: rgba(233, 115, 88, 0.75);
  --swiper-navigation-color: #ffffff;
}

html.dark .ui-carousel .swiper-pagination-bullet {
  margin: 0 7px !important;
}

/* Лендинг: пагинация ниже карточек, стрелки в «гуттере» */
.ui-carousel--spaced.swiper {
  box-sizing: border-box;
  padding-bottom: 3.75rem;
  padding-left: 42px;
  padding-right: 42px;
}

@media (min-width: 768px) {
  .ui-carousel--spaced.swiper {
    padding-left: 54px;
    padding-right: 54px;
    padding-bottom: 4rem;
  }
}

.ui-carousel--spaced .swiper-pagination {
  bottom: 0.5rem !important;
}

.ui-carousel--spaced .swiper-pagination-bullet {
  margin: 0 9px !important;
}

.ui-carousel--spaced .swiper-button-prev,
.ui-carousel--spaced .swiper-button-next {
  top: 42%;
}

.ui-carousel--spaced .swiper-button-prev,
.ui-carousel--spaced .swiper-button-next {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
}

html.dark .ui-carousel--spaced .swiper-pagination-bullet {
  background: rgba(255, 255, 255, 0.32);
  margin: 0 10px !important;
}

html.dark .ui-carousel--spaced .swiper-pagination-bullet-active {
  background: var(--accent, #e97358);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
}
</style>


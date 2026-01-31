<template>
  <div class="relative group bg-slate-200 dark:bg-slate-700 rounded-lg overflow-hidden h-96">
    <!-- Carousel Items -->
    <div class="relative w-full h-full overflow-hidden">
      <transition-group
        name="carousel"
        tag="div"
        class="relative w-full h-full"
      >
        <div
          v-for="(item, index) in items"
          v-show="index === currentIndex"
          :key="index"
          class="absolute inset-0 w-full h-full"
        >
          <Image
            :src="item.thumbnail"
            :alt="item.title"
            container-class="w-full h-full"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-8 text-white">
            <h3 class="text-3xl font-bold mb-2">{{ item.title }}</h3>
            <p class="text-lg text-gray-300 mb-2">{{ item.author }}</p>
            <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ item.description }}</p>
            <div class="flex gap-3">
              <UButton
                :to="`/comic/${item.id}`"
                color="primary"
                label="Đọc ngay"
                icon="i-heroicons-arrow-right"
              />
              <UButton
                color="primary"
                variant="outline"
                icon="i-heroicons-heart"
              />
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <!-- Navigation Buttons -->
    <button
      class="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-2 bg-black/50 hover:bg-black/70 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-opacity"
      @click="prevSlide"
    >
      <UIcon name="i-heroicons-chevron-left" class="w-6 h-6" />
    </button>
    <button
      class="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-2 bg-black/50 hover:bg-black/70 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-opacity"
      @click="nextSlide"
    >
      <UIcon name="i-heroicons-chevron-right" class="w-6 h-6" />
    </button>

    <!-- Dots -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2">
      <button
        v-for="(_, index) in items"
        :key="index"
        :class="[
          'w-2 h-2 rounded-full transition-all',
          index === currentIndex
            ? 'bg-white w-8'
            : 'bg-white/50 hover:bg-white/70'
        ]"
        @click="currentIndex = index"
      ></button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CarouselItem {
  id: string
  title: string
  author: string
  thumbnail: string
  description: string
}

interface Props {
  items: CarouselItem[]
  autoplay?: boolean
  autoplayInterval?: number
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: true,
  autoplayInterval: 5000
})

const currentIndex = ref(0)
let autoplayTimer: NodeJS.Timeout

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % props.items.length
  resetAutoplay()
}

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + props.items.length) % props.items.length
  resetAutoplay()
}

const startAutoplay = () => {
  if (props.autoplay) {
    autoplayTimer = setInterval(() => {
      nextSlide()
    }, props.autoplayInterval)
  }
}

const resetAutoplay = () => {
  clearInterval(autoplayTimer)
  startAutoplay()
}

onMounted(() => {
  startAutoplay()
})

onUnmounted(() => {
  clearInterval(autoplayTimer)
})
</script>

<style scoped>
.carousel-enter-active,
.carousel-leave-active {
  transition: opacity 0.5s ease;
}

.carousel-enter-from,
.carousel-leave-to {
  opacity: 0;
}
</style>

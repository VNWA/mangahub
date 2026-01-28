<template>
  <section class="relative bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12 md:py-20">
    <div class="max-w-7xl mx-auto px-4">
      <div class="grid md:grid-cols-2 gap-8 items-center">
        <!-- Left Content -->
        <div>
          <h2 class="text-4xl md:text-5xl font-bold mb-4">
            Khám Phá Thế Giới Truyện Tranh
          </h2>
          <p class="text-lg text-blue-100 mb-6">
            Hàng ngàn bộ truyện tranh từ các tác giả nổi tiếng. Đọc miễn phí, cập nhật hàng ngày.
          </p>
          <div class="flex gap-4">
            <UButton
              size="lg"
              color="primary"
              variant="soft"
              label="Khám phá ngay"
              icon="i-heroicons-arrow-right"
              to="/category/list"
            />
            <UButton
              size="lg"
              color="neutral"
              variant="outline"
              label="Thể loại"
              icon="i-heroicons-list-bullet"
              to="/category/list"
            />
          </div>
        </div>

        <!-- Right Image -->
        <div class="hidden md:block">
          <div class="relative h-64 md:h-80">
            <div class="absolute inset-0 bg-white/10 rounded-lg backdrop-blur-sm flex items-center justify-center">
              <UIcon name="i-heroicons-book-open" class="w-32 h-32 text-white/50" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// Hero Section - No script needed
</script>

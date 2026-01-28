<template>
  <footer class="bg-slate-900 text-slate-300 py-12">
    <div class="max-w-7xl mx-auto px-4">
      <div class="grid md:grid-cols-4 gap-8 mb-8">
        <!-- Company Info -->
        <div>
          <div class="flex items-center gap-2 mb-4">
            <UIcon name="i-heroicons-book-open" class="w-6 h-6 text-blue-400" />
            <h3 class="text-xl font-bold text-white">WebTruyện</h3>
          </div>
          <p class="text-sm text-slate-400">
            Nền tảng đọc truyện tranh online lớn nhất Việt Nam
          </p>
        </div>

        <!-- Links -->
        <div>
          <h4 class="font-semibold text-white mb-4">Về chúng tôi</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/" class="hover:text-blue-400 transition-colors">Trang chủ</NuxtLink></li>
            <li><a href="#" class="hover:text-blue-400 transition-colors">Giới thiệu</a></li>
            <li><a href="#" class="hover:text-blue-400 transition-colors">Liên hệ</a></li>
          </ul>
        </div>

        <!-- Categories -->
        <div>
          <h4 class="font-semibold text-white mb-4">Thể loại</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/category/list" class="hover:text-blue-400 transition-colors">Tất cả thể loại</NuxtLink></li>
            <li><NuxtLink to="/trending" class="hover:text-blue-400 transition-colors">Truyện hot</NuxtLink></li>
            <li><NuxtLink to="/collection" class="hover:text-blue-400 transition-colors">Mới cập nhật</NuxtLink></li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <h4 class="font-semibold text-white mb-4">Hỗ trợ</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="hover:text-blue-400 transition-colors">Chính sách riêng tư</a></li>
            <li><a href="#" class="hover:text-blue-400 transition-colors">Điều khoản sử dụng</a></li>
            <li><a href="#" class="hover:text-blue-400 transition-colors">DMCA</a></li>
          </ul>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-t border-slate-700 pt-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <p class="text-sm text-slate-400">
            &copy; 2024 WebTruyện. Tất cả các quyền được bảo lưu.
          </p>
          <div class="flex gap-4">
            <UButton
              icon="i-heroicons-globe-alt"
              color="neutral"
              variant="ghost"
              aria-label="Website"
            />
            <UButton
              icon="i-heroicons-globe-alt"
              color="neutral"
              variant="ghost"
              aria-label="Facebook"
            />
            <UButton
              icon="i-heroicons-globe-alt"
              color="neutral"
              variant="ghost"
              aria-label="Twitter"
            />
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
// Footer - No script needed
</script>

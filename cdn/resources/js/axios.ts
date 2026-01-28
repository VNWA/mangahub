import axios from 'axios'
import { ref } from 'vue'

export const isLoading = ref(false)

const instance = axios.create({
  baseURL: '/',
  timeout: 30000, // 30 seconds (tăng từ 10s để phù hợp với addSyncManga)
})

// Request → bắt đầu loading
instance.interceptors.request.use(
  (config) => {
    isLoading.value = true
    return config
  },
  (error) => {
    isLoading.value = false
    return Promise.reject(error)
  }
)

// Response → kết thúc loading
instance.interceptors.response.use(
  (response) => {
    isLoading.value = false
    return response
  },
  (error) => {
    isLoading.value = false
    return Promise.reject(error)
  }
)

export default instance

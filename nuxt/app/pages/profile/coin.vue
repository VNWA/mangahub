<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Quản lý Coin</h2>
      <div class="flex items-center gap-2 px-4 py-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <UIcon name="i-heroicons-currency-dollar" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
        <span class="text-lg font-bold text-yellow-700 dark:text-yellow-300">{{ formatCoin(balance) }} coin</span>
      </div>
    </div>

    <!-- Deposit Coin Section -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Nạp Coin</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Số lượng coin
          </label>
          <UInput
            v-model.number="depositAmount"
            type="number"
            min="1"
            placeholder="Nhập số coin muốn nạp"
            :disabled="depositing"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Mô tả (tùy chọn)
          </label>
          <UInput
            v-model="depositDescription"
            placeholder="Ví dụ: Nạp coin để mở khóa chapter"
            :disabled="depositing"
          />
        </div>
        <UButton
          @click="handleDeposit"
          :loading="depositing"
          :disabled="!depositAmount || depositAmount < 1"
          color="primary"
          size="lg"
          block
        >
          <UIcon name="i-heroicons-plus-circle" class="w-5 h-5" />
          Nạp Coin
        </UButton>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
      <div class="flex gap-6">
        <button
          @click="activeTab = 'transactions'"
          :class="[
            'py-3 px-2 font-semibold text-sm border-b-2 transition-colors',
            activeTab === 'transactions'
              ? 'border-primary text-primary'
              : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          ]"
        >
          Lịch sử giao dịch
        </button>
        <button
          @click="activeTab = 'unlocks'"
          :class="[
            'py-3 px-2 font-semibold text-sm border-b-2 transition-colors',
            activeTab === 'unlocks'
              ? 'border-primary text-primary'
              : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          ]"
        >
          Lịch sử mở khóa
        </button>
      </div>
    </div>

    <!-- Transactions Tab -->
    <div v-if="activeTab === 'transactions'" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Lịch sử giao dịch</h3>
        <USelect
          v-model="transactionType"
          :options="transactionTypeOptions"
          value-attribute="value"
          option-attribute="label"
          size="sm"
          class="w-40"
          @change="loadTransactions"
        />
      </div>

      <div v-if="loadingTransactions" class="space-y-4">
        <div v-for="i in 5" :key="i" class="animate-pulse">
          <div class="h-16 bg-slate-200 dark:bg-slate-700 rounded"></div>
        </div>
      </div>

      <div v-else-if="transactions.length === 0" class="text-center py-8">
        <UIcon name="i-heroicons-document-text" class="w-12 h-12 text-slate-400 mx-auto mb-3" />
        <p class="text-slate-600 dark:text-slate-400">Chưa có giao dịch nào</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="transaction in transactions"
          :key="transaction.id"
          class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
        >
          <div class="flex items-center gap-3 flex-1">
            <div
              :class="[
                'w-10 h-10 rounded-full flex items-center justify-center',
                transaction.type === 'deposit'
                  ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                  : 'bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400'
              ]"
            >
              <UIcon
                :name="transaction.type === 'deposit' ? 'i-heroicons-arrow-down-circle' : 'i-heroicons-arrow-up-circle'"
                class="w-5 h-5"
              />
            </div>
            <div class="flex-1">
              <p class="font-medium text-slate-900 dark:text-white">{{ transaction.description }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ formatTime(transaction.created_at) }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p
              :class="[
                'font-semibold',
                transaction.type === 'deposit'
                  ? 'text-green-600 dark:text-green-400'
                  : 'text-red-600 dark:text-red-400'
              ]"
            >
              {{ transaction.type === 'deposit' ? '+' : '-' }}{{ formatCoin(transaction.amount) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Còn: {{ formatCoin(transaction.balance_after) }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="hasMoreTransactions" class="mt-6 text-center">
        <UButton @click="loadMoreTransactions" :loading="loadingMoreTransactions" variant="ghost" color="primary">
          Tải thêm
        </UButton>
      </div>
    </div>

    <!-- Unlocks Tab -->
    <div v-if="activeTab === 'unlocks'" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Lịch sử mở khóa</h3>

      <div v-if="loadingUnlocks" class="space-y-4">
        <div v-for="i in 5" :key="i" class="animate-pulse">
          <div class="h-20 bg-slate-200 dark:bg-slate-700 rounded"></div>
        </div>
      </div>

      <div v-else-if="unlocks.length === 0" class="text-center py-8">
        <UIcon name="i-heroicons-lock-open" class="w-12 h-12 text-slate-400 mx-auto mb-3" />
        <p class="text-slate-600 dark:text-slate-400">Chưa mở khóa chapter nào</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="unlock in unlocks"
          :key="unlock.id"
          class="flex items-center gap-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
        >
          <div class="w-12 h-16 bg-slate-200 dark:bg-slate-700 rounded flex-shrink-0"></div>
          <div class="flex-1 min-w-0">
            <NuxtLink
              :to="`/${unlock.manga.slug}/${unlock.chapter.slug}`"
              class="font-medium text-slate-900 dark:text-white hover:text-primary block truncate"
            >
              {{ unlock.chapter.name }}
            </NuxtLink>
            <NuxtLink
              :to="`/${unlock.manga.slug}`"
              class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary block truncate"
            >
              {{ unlock.manga.name }}
            </NuxtLink>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ formatTime(unlock.created_at) }}
            </p>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="font-semibold text-yellow-600 dark:text-yellow-400">
              -{{ formatCoin(unlock.coin_spent) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">coin</p>
          </div>
        </div>
      </div>

      <div v-if="hasMoreUnlocks" class="mt-6 text-center">
        <UButton @click="loadMoreUnlocks" :loading="loadingMoreUnlocks" variant="ghost" color="primary">
          Tải thêm
        </UButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const toast = useToast()

const balance = ref(0)
const activeTab = ref<'transactions' | 'unlocks'>('transactions')
const depositAmount = ref<number | null>(null)
const depositDescription = ref('')
const depositing = ref(false)

// Transactions
const transactions = ref<any[]>([])
const transactionType = ref<'all' | 'deposit' | 'spend'>('all')
const transactionTypeOptions = [
  { label: 'Tất cả', value: 'all' },
  { label: 'Nạp coin', value: 'deposit' },
  { label: 'Tiêu coin', value: 'spend' }
]
const loadingTransactions = ref(false)
const loadingMoreTransactions = ref(false)
const currentTransactionPage = ref(1)
const hasMoreTransactions = ref(false)

// Unlocks
const unlocks = ref<any[]>([])
const loadingUnlocks = ref(false)
const loadingMoreUnlocks = ref(false)
const currentUnlockPage = ref(1)
const hasMoreUnlocks = ref(false)

const formatCoin = (coin: number) => {
  return new Intl.NumberFormat('vi-VN').format(coin)
}

const formatTime = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Vừa xong'
  if (minutes < 60) return `${minutes} phút trước`
  if (hours < 24) return `${hours} giờ trước`
  if (days < 7) return `${days} ngày trước`
  return d.toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' })
}

// Load balance
const loadBalance = async () => {
  try {
    const data = await $http<{ ok: boolean; data: { coin: number } }>('/coins/balance')
    if (data?.ok && data.data) {
      balance.value = data.data.coin
      if (auth.user) {
        auth.user.coin = data.data.coin
      }
    }
  } catch (error) {
    console.error('Failed to load balance:', error)
  }
}

// Deposit coin
const handleDeposit = async () => {
  if (!depositAmount.value || depositAmount.value < 1) return

  depositing.value = true
  try {
    const data = await $http<{
      ok: boolean
      message: string
      data: {
        balance: number
      }
    }>('/coins/deposit', {
      method: 'POST',
      body: {
        amount: depositAmount.value,
        description: depositDescription.value || undefined
      }
    })

    if (data?.ok) {
      toast.add({
        title: 'Thành công',
        description: data.message || 'Nạp coin thành công',
        color: 'success'
      })

      balance.value = data.data.balance
      if (auth.user) {
        auth.user.coin = data.data.balance
      }

      depositAmount.value = null
      depositDescription.value = ''

      // Reload transactions
      await loadTransactions()
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể nạp coin',
      color: 'error'
    })
  } finally {
    depositing.value = false
  }
}

// Load transactions
const loadTransactions = async (page = 1, append = false) => {
  if (page === 1) {
    loadingTransactions.value = true
  } else {
    loadingMoreTransactions.value = true
  }

  try {
    const data = await $http<{
      ok: boolean
      data: any[]
      pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>('/coins/transactions', {
      query: {
        type: transactionType.value === 'all' ? undefined : transactionType.value,
        page,
        per_page: 20
      }
    })

    if (data?.ok && data.data) {
      if (append) {
        transactions.value = [...transactions.value, ...data.data]
      } else {
        transactions.value = data.data
      }

      hasMoreTransactions.value = data.pagination.current_page < data.pagination.last_page
      currentTransactionPage.value = data.pagination.current_page
    }
  } catch (error) {
    console.error('Failed to load transactions:', error)
  } finally {
    loadingTransactions.value = false
    loadingMoreTransactions.value = false
  }
}

const loadMoreTransactions = () => {
  loadTransactions(currentTransactionPage.value + 1, true)
}

// Load unlocks
const loadUnlocks = async (page = 1, append = false) => {
  if (page === 1) {
    loadingUnlocks.value = true
  } else {
    loadingMoreUnlocks.value = true
  }

  try {
    const data = await $http<{
      ok: boolean
      data: any[]
      pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>('/coins/unlock-history', {
      query: {
        page,
        per_page: 20
      }
    })

    if (data?.ok && data.data) {
      if (append) {
        unlocks.value = [...unlocks.value, ...data.data]
      } else {
        unlocks.value = data.data
      }

      hasMoreUnlocks.value = data.pagination.current_page < data.pagination.last_page
      currentUnlockPage.value = data.pagination.current_page
    }
  } catch (error) {
    console.error('Failed to load unlocks:', error)
  } finally {
    loadingUnlocks.value = false
    loadingMoreUnlocks.value = false
  }
}

const loadMoreUnlocks = () => {
  loadUnlocks(currentUnlockPage.value + 1, true)
}

// Watch tab changes
watch(activeTab, (newTab) => {
  if (newTab === 'transactions' && transactions.value.length === 0) {
    loadTransactions()
  } else if (newTab === 'unlocks' && unlocks.value.length === 0) {
    loadUnlocks()
  }
})

// Watch transaction type changes
watch(transactionType, () => {
  loadTransactions()
})

onMounted(() => {
  loadBalance()
  loadTransactions()
})
</script>

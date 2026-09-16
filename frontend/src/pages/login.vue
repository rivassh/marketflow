<template>
  <div class="auth-container rtl" dir="rtl">
    <div class="auth-card">
      <h2 class="auth-title">ورود به مارکت‌فلو</h2>
      
      <form @submit.prevent="handleLogin" novalidate>
        <div class="form-group">
          <label for="mobile" class="form-label">شماره موبایل</label>
          <input
            id="mobile"
            type="tel"
            v-model="form.mobile"
            placeholder="+989123456789"
            required
            :disabled="authStore.loading"
            class="form-input"
          />
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">رمز عبور</label>
          <input
            id="password"
            type="password"
            v-model="form.password"
            placeholder="رمز عبور خود را وارد کنید"
            required
            :disabled="authStore.loading"
            class="form-input"
          />
        </div>
        
        <div v-if="errorMessage" class="error-banner">
          {{ errorMessage }}
        </div>
        
        <button
          type="submit"
          class="btn-primary w-full"
          :disabled="authStore.loading"
        >
          <span v-if="authStore.loading">در حال ورود...</span>
          <span v-else>ورود</span>
        </button>
        
        <div class="form-links">
          <span>حساب کاربری ندارید؟</span>
          <NuxtLink to="/register" class="link-register">ثبت‌نام</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: false,
})

const authStore = useAuthStore()
const router = useRouter()

const form = reactive({
  mobile: '',
  password: '',
})

const errors = ref({})

const errorMessage = computed(() => {
  if (Object.keys(errors.value).length === 0) return ''
  const firstError = Object.values(errors.value)[0]
  return Array.isArray(firstError) ? firstError[0] : firstError
})

const handleLogin = async () => {
  errors.value = {}
  
  if (!form.mobile.trim()) {
    errors.value = { mobile: 'شماره موبایل الزامی است' }
    return
  }
  
  if (!form.password) {
    errors.value = { password: 'رمز عبور الزامی است' }
    return
  }
  
  try {
    await authStore.login({
      mobile: form.mobile,
      password: form.password,
    })
    
    // Redirect to homepage on success
    router.push('/')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = { general: 'شماره موبایل یا رمز عبور اشتباه است' }
    } else {
      errors.value = { general: 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.' }
    }
  }
}
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  background: #f0f2f5;
  padding: 2rem;
}

.auth-card {
  max-width: 400px;
  margin: 0 auto;
  padding: 2rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.auth-title {
  text-align: center;
  margin-bottom: 2rem;
  color: #333;
  font-size: 1.5rem;
  font-weight: 700;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #666;
  font-weight: 500;
}

.form-input {
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  box-sizing: border-box;
  direction: ltr;
  text-align: right;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.form-input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.btn-primary {
  width: 100%;
  padding: 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 1rem;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.form-links {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.9rem;
  color: #666;
}

.link-register {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  margin-right: 0.5rem;
}

.link-register:hover {
  text-decoration: underline;
}

.error-banner {
  background: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
  text-align: center;
}
</style>

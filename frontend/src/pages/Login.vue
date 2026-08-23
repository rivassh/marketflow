<template>
  <div class="auth-container rtl" dir="rtl">
    <div class="auth-card">
      <h2 class="auth-title">ورود به حساب کاربری</h2>
      
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="mobile" class="form-label">شماره موبایل</label>
          <input 
            id="mobile" 
            type="text" 
            v-model="mobile"
            placeholder="+98XXX-XXXXXXX-XX"
            @input="formatMobile"
            required
            class="form-input"
          />
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">رمز عبور</label>
          <input 
            id="password" 
            type="password" 
            v-model="password"
            placeholder="رمز عبور خود را وارد کنید"
            required
            class="form-input"
          />
        </div>
        
        <button type="submit" class="btn-primary w-full">
          ورود
        </button>
        
        <div class="form-links">
          <a href="/register" class="link-register">حساب کاربری ندارید؟ ثبت‌نام</a>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useStore } from '@/store/pinia/authStore'

const mobile = ref('')
const password = ref('')
const errors = ref({})

const store = useStore()

const handleLogin = async () => {
  errors.value = {}
  
  try {
    await store.login({ mobile: mobile.value, password: password.value })
    // Redirect or show success
    console.log('Login successful:', store.user)
  } catch (err) {
    if (err.response) {
      errors.value = err.response.data.errors || { general: err.response.data.message }
    } else {
      errors.value = { general: 'خطا در ارتباط با سرور' }
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
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.form-input {
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #42b983;
  box-shadow: 0 0 0 2px rgba(66, 185, 131, 0.2);
}

.btn-primary {
  width: 100%;
  padding: 1rem;
  background: #42b983;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 1rem;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #36a477;
}

.btn-primary:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(66, 185, 131, 0.4);
}

.form-links {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.9rem;
  color: #666;
}

.link-register {
  color: #42b983;
  text-decoration: none;
  font-weight: 500;
}

.link-register:hover {
  text-decoration: underline;
}
</style>
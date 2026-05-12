<script setup>
import { useForm } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

function submit() {
  form.post('/login-operator-saas');
}
</script>

<template>
  <div>
    <section class="min-h-[calc(100vh-200px)] flex">
      <div class="flex-1 grid lg:grid-cols-2">
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 flex items-center justify-center px-6 py-16 lg:py-0">
          <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-purple-400/20 rounded-full blur-3xl"></div>
          <div class="absolute top-1/2 right-1/4 w-40 h-40 bg-indigo-300/20 rounded-full blur-2xl"></div>
          <div class="relative text-center text-white max-w-sm">
            <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl">
              <i class="fas fa-cloud text-5xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold mb-3">Operator SaaS</h2>
            <p class="text-indigo-100 leading-relaxed text-sm md:text-base">
              Kelola semua tenant RT/RW Net dari satu dashboard. Pantau performa, atur langganan, dan bantu perusahaan tumbuh.
            </p>
            <div class="mt-8 flex justify-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-white/60"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-white/40"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
          <div class="w-full max-w-md">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
              <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk Operator SaaS</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Masukkan kredensial operator Anda.
                </p>
              </div>

              <form class="space-y-4" @submit.prevent="submit">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="fas fa-envelope text-gray-400 text-sm"></i>
                    </div>
                    <input
                      v-model="form.email"
                      type="email"
                      placeholder="admin@rtrwnet.id"
                      class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
                      :class="{ 'border-red-400 focus:ring-red-500': form.errors.email }"
                    />
                  </div>
                  <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="fas fa-lock text-gray-400 text-sm"></i>
                    </div>
                    <input
                      v-model="form.password"
                      type="password"
                      placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                      class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
                    />
                  </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                  <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer">
                    <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500" />
                    <span>Ingat saya</span>
                  </label>
                  <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:underline">Lupa password?</a>
                </div>

                <button
                  type="submit"
                  :disabled="form.processing"
                  class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50"
                >
                  <i v-if="!form.processing" class="fas fa-sign-in-alt mr-2"></i>
                  <i v-else class="fas fa-spinner fa-spin mr-2"></i>
                  {{ form.processing ? 'Memproses...' : 'Masuk' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

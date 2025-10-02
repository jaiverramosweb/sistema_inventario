<script setup>

import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2LoginMaskDark from '@images/pages/auth-v2-login-mask-dark.png'
import authV2LoginMaskLight from '@images/pages/auth-v2-login-mask-light.png'
import authImge from '@images/pages/login.jpeg'
import logoImge from '@images/logo.sitec.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const route = useRoute()
const router = useRouter()

const error_exists = ref(false)

const login = async () => {
  try {
    error_exists.value = false

    const response = await $api('auth/login', {
      method: 'POST',
      body: {
        email: form.value.email,
        password: form.value.password,
      },
      onResponseError({ response }) {
        console.log(response)
        error_exists.value = true
      },
    })

    // console.log('Login response:', response)
    const { access_token, user } = response

    localStorage.setItem('token', access_token)
    localStorage.setItem('user', JSON.stringify(user))

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })

  } catch (error) {
    console.error('Login error:', error)
  }
}

definePage({ meta: { layout: 'blank', unauthenticatedOnly: true } })

const isPasswordVisible = ref(false)
const authV2LoginMask = useGenerateImageVariant(authV2LoginMaskLight, authV2LoginMaskDark)
const authV2LoginIllustration = useGenerateImageVariant(authV2LoginIllustrationLight, authV2LoginIllustrationDark, authV2LoginIllustrationBorderedLight, authV2LoginIllustrationBorderedDark, true)
</script>

<template>
  <RouterLink to="/">
    <div class="app-logo auth-logo">
      <!-- <VNodeRenderer :nodes="logoImge" /> -->
      <img :src="logoImge" class="w-auto" style="height: 40px;" alt="logo">

      <h1 class="app-logo-title">
        <!-- {{ themeConfig.app.title }} -->
      </h1>
    </div>
  </RouterLink>

  <VRow no-gutters class="auth-wrapper">
    <VCol md="8" class="d-none d-md-flex align-center">
      <div class=""  style="width: 50vw; height: 100vh;">
        <img :src="authImge" class="" style="width: 66vw; height: 100vh;" alt="auth-illustration">
      </div>
      <!-- <VImg :src="authV2LoginMask" class="d-none d-md-flex auth-footer-mask" alt="auth-mask" /> -->
    </VCol>
    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center"
      style="background-color: rgb(var(--v-theme-surface));">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-5 pa-lg-7">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Bienvenido a <span class="text-capitalize">{{ themeConfig.app.title }}! 👋🏻</span>
          </h4>

          <p class="mb-0">
            Un mundo de.
          </p>
        </VCardText>

        <VCardText>
          <VForm @submit.prevent="login">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <VTextField v-model="form.email" autofocus label="Email" type="email" placeholder="johndoe@email.com" />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <VTextField v-model="form.password" label="Contraseña" placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" />

                <VAlert class="my-2" type="error" closable v-if="error_exists">
                  No se puede iniciar sesión, credenciasles incorrectas
                </VAlert>

                <!-- login button -->
                <VBtn class="my-2" block type="submit">
                  Login
                </VBtn>
              </VCol>


              <!-- <VCol cols="12" class="d-flex align-center">
                <VDivider />
                <span class="mx-4 text-high-emphasis">or</span>
                <VDivider />
              </VCol> -->

              <!-- auth providers -->
              <!-- <VCol cols="12" class="text-center">
                <AuthProvider />
              </VCol> -->
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";
</style>

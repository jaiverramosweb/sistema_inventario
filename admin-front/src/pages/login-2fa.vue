<script setup>
definePage({ meta: { layout: 'blank', unauthenticatedOnly: true } })

const route = useRoute()
const router = useRouter()

const mode = ref('totp')
const code = ref('')
const errorMessage = ref('')
const loading = ref(false)

const submit = async () => {
  const mfaToken = sessionStorage.getItem('mfa_token')
  if (!mfaToken) {
    router.replace('/login')
    return
  }

  errorMessage.value = ''
  loading.value = true

  try {
    const endpoint = mode.value === 'totp' ? 'auth/2fa/verify' : 'auth/2fa/recovery'
    const body = mode.value === 'totp'
      ? { mfa_token: mfaToken, code: code.value }
      : { mfa_token: mfaToken, recovery_code: code.value }

    const response = await $api(endpoint, {
      method: 'POST',
      body,
      onResponseError({ response }) {
        if (response?.status === 429) {
          errorMessage.value = 'Demasiados intentos de verificacion. Espera 1 minuto y vuelve a intentar.'
          return
        }

        errorMessage.value = response?._data?.error || 'No fue posible validar el codigo.'
      },
    })

    const { access_token, user } = response

    localStorage.setItem('token', access_token)
    localStorage.setItem('user', JSON.stringify(user))
    sessionStorage.removeItem('mfa_token')
    sessionStorage.removeItem('mfa_email')

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })
  } catch (error) {
    if (!errorMessage.value) {
      errorMessage.value = 'No fue posible validar el codigo.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <VCard
      flat
      :max-width="450"
      class="auth-card pa-6 pa-sm-10"
      style="background-color: rgb(var(--v-theme-surface)); border: 1px solid rgba(var(--v-border-color), 0.12);"
    >
      <VCardText class="d-flex flex-column align-center text-center">
        <VAvatar
          size="72"
          color="primary"
          variant="tonal"
          class="mb-6"
        >
          <VIcon
            size="36"
            icon="ri-shield-keyhole-line"
          />
        </VAvatar>

        <h4 class="text-h4 mb-2 font-weight-medium">
          Verificación en dos pasos
        </h4>
        <p class="mb-0 text-body-1">
          Ingresa el código de Google Authenticator o un código de recuperación para continuar.
        </p>
      </VCardText>

      <VCardText class="pt-2">
        <div class="d-flex justify-center mb-6">
          <VBtnToggle
            v-model="mode"
            mandatory
            color="primary"
            variant="tonal"
            rounded="xl"
            class="border w-100"
            style="max-width: 350px;"
          >
            <VBtn
              value="totp"
              prepend-icon="ri-smartphone-line"
              class="flex-grow-1"
            >
              App
            </VBtn>
            <VBtn
              value="recovery"
              prepend-icon="ri-key-2-line"
              class="flex-grow-1"
            >
              Recovery
            </VBtn>
          </VBtnToggle>
        </div>

        <VForm @submit.prevent="submit">
          <VTextField
            v-model="code"
            :label="mode === 'totp' ? 'Código de 6 dígitos' : 'Código de recuperación'"
            :placeholder="mode === 'totp' ? '123456' : 'XXXXXXXX-XXXXXXXX'"
            :prepend-inner-icon="mode === 'totp' ? 'ri-fingerprint-line' : 'ri-lock-password-line'"
            autofocus
            class="mb-6"
            persistent-placeholder
          />

          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            closable
            class="mb-6"
          >
            {{ errorMessage }}
          </VAlert>

          <VBtn
            block
            size="large"
            type="submit"
            :loading="loading"
            prepend-icon="ri-login-box-line"
            class="mb-4"
          >
            Verificar y Entrar
          </VBtn>

          <VBtn
            block
            variant="text"
            color="secondary"
            prepend-icon="ri-arrow-left-line"
            @click="router.replace('/login')"
          >
            Volver al login
          </VBtn>
        </VForm>
      </VCardText>
    </VCard>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";

.auth-wrapper {
  min-height: 100vh;
  background-color: rgb(var(--v-theme-background));
}

</style>

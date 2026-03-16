<script setup>
definePage({ meta: { permission: 'all' } })

const loading = ref(false)
const saving = ref(false)
const profileSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const profileErrorMessage = ref('')
const profileSuccessMessage = ref('')

const profileForm = ref({
  name: '',
  phone: '',
  document: '',
})

const avatarPreview = ref('')
const currentAvatarUrl = ref('')
const avatarFile = ref(null)

const status = ref({
  two_factor_enabled: false,
  has_pending_setup: false,
  recovery_codes_remaining: 0,
})

const setup = ref({
  manual_key: '',
  otpauth_url: '',
  qr_svg: '',
})

const verifyCode = ref('')
const disableForm = ref({ password: '', code: '' })
const regeneratePassword = ref('')
const recoveryCodes = ref([])

const loadCurrentUser = async () => {
  const localUser = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null

  if (localUser) {
    profileForm.value.name = localUser.full_name || ''
    profileForm.value.phone = localUser.phone || ''
    profileForm.value.document = localUser.document || ''
    avatarPreview.value = localUser.avatar || ''
    currentAvatarUrl.value = localUser.avatar || ''
  }

  try {
    const response = await $api('auth/me', { method: 'POST' })

    profileForm.value.name = response.full_name || ''
    profileForm.value.phone = response.phone || ''
    profileForm.value.document = response.document || ''
    avatarPreview.value = response.avatar || ''
    currentAvatarUrl.value = response.avatar || ''

    const updatedUser = {
      ...(localUser || {}),
      ...response,
    }

    localStorage.setItem('user', JSON.stringify(updatedUser))
    window.dispatchEvent(new Event('user-updated'))
  } catch (error) {
    profileErrorMessage.value = 'No fue posible cargar tu informacion de perfil.'
  }
}

const loadStatus = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await $api('auth/2fa/status', { method: 'GET' })
    status.value = response
  } catch (error) {
    errorMessage.value = 'No fue posible cargar el estado de seguridad.'
  } finally {
    loading.value = false
  }
}

const parseApiError = response => {
  if (response?.status === 429)
    return 'Demasiados intentos. Espera 1 minuto y vuelve a intentar.'

  const data = response?._data

  if (typeof data === 'string') {
    try {
      const parsed = JSON.parse(data)
      const firstError = Object.values(parsed || {})[0]
      if (Array.isArray(firstError) && firstError.length)
        return firstError[0]
    } catch (error) {
      return data
    }
  }

  if (data && typeof data === 'object') {
    if (data.error)
      return data.error

    const firstError = Object.values(data)[0]
    if (Array.isArray(firstError) && firstError.length)
      return firstError[0]
  }

  return response?._data?.error || 'No fue posible completar la operacion.'
}

const onAvatarSelected = fileInput => {
  if (!fileInput) {
    avatarFile.value = null
    avatarPreview.value = currentAvatarUrl.value || ''
    return
  }

  const file = Array.isArray(fileInput) ? fileInput[0] : fileInput
  avatarFile.value = file || null

  if (avatarFile.value) {
    avatarPreview.value = URL.createObjectURL(avatarFile.value)
  }
}

const clearSelectedAvatar = () => {
  avatarFile.value = null
  avatarPreview.value = currentAvatarUrl.value || ''
}

const copyRecoveryCodes = async () => {
  if (!recoveryCodes.value.length)
    return

  const content = recoveryCodes.value.join('\n')

  try {
    await navigator.clipboard.writeText(content)
    successMessage.value = 'Codigos de recuperacion copiados al portapapeles.'
  } catch (error) {
    errorMessage.value = 'No fue posible copiar los codigos. Copialos manualmente.'
  }
}

const saveProfile = async () => {
  profileSaving.value = true
  profileErrorMessage.value = ''
  profileSuccessMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('name', profileForm.value.name || '')
    formData.append('phone', profileForm.value.phone || '')
    formData.append('document', profileForm.value.document || '')

    if (avatarFile.value) {
      formData.append('image', avatarFile.value)
    }

    const response = await $api('auth/profile/update', {
      method: 'POST',
      body: formData,
    })

    localStorage.setItem('user', JSON.stringify(response.user))
    window.dispatchEvent(new Event('user-updated'))
    currentAvatarUrl.value = response.user?.avatar || ''
    avatarPreview.value = currentAvatarUrl.value
    avatarFile.value = null
    profileSuccessMessage.value = response.message || 'Perfil actualizado correctamente.'
  } catch (error) {
    profileErrorMessage.value = parseApiError(error?.response)
  } finally {
    profileSaving.value = false
  }
}

const initSetup = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await $api('auth/2fa/setup/init', { method: 'POST' })
    setup.value = response
    status.value.has_pending_setup = true
    successMessage.value = 'Configuracion iniciada. Escanea el QR y confirma tu codigo.'
  } catch (error) {
    errorMessage.value = parseApiError(error?.response)
  } finally {
    saving.value = false
  }
}

const confirmSetup = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await $api('auth/2fa/setup/verify', {
      method: 'POST',
      body: { code: verifyCode.value },
    })

    recoveryCodes.value = response.recovery_codes || []
    verifyCode.value = ''
    setup.value = { manual_key: '', otpauth_url: '', qr_svg: '' }
    await loadStatus()
    successMessage.value = 'Autenticacion de doble factor activada correctamente.'
  } catch (error) {
    errorMessage.value = parseApiError(error?.response)
  } finally {
    saving.value = false
  }
}

const disableTwoFactor = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await $api('auth/2fa/disable', {
      method: 'POST',
      body: {
        password: disableForm.value.password,
        code: disableForm.value.code,
      },
    })

    disableForm.value = { password: '', code: '' }
    recoveryCodes.value = []
    setup.value = { manual_key: '', otpauth_url: '', qr_svg: '' }
    await loadStatus()
    successMessage.value = 'Autenticacion de doble factor desactivada.'
  } catch (error) {
    errorMessage.value = parseApiError(error?.response)
  } finally {
    saving.value = false
  }
}

const regenerateCodes = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await $api('auth/2fa/recovery/regenerate', {
      method: 'POST',
      body: { password: regeneratePassword.value },
    })
    recoveryCodes.value = response.recovery_codes || []
    regeneratePassword.value = ''
    await loadStatus()
    successMessage.value = 'Codigos de recuperacion regenerados.'
  } catch (error) {
    errorMessage.value = parseApiError(error?.response)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadCurrentUser()
  loadStatus()
})
</script>

<template>
  <div>
    <VCard title="Perfil y Seguridad" :loading="loading">
      <VCardText>
        <VAlert v-if="profileErrorMessage" type="error" variant="tonal" class="mb-4">
          {{ profileErrorMessage }}
        </VAlert>
        <VAlert v-if="profileSuccessMessage" type="success" variant="tonal" class="mb-4">
          {{ profileSuccessMessage }}
        </VAlert>

        <h5 class="text-h5 mb-3">Datos personales</h5>

        <VRow>
          <VCol cols="12" md="3" class="d-flex flex-column align-center">
            <div class="position-relative">
              <VAvatar size="120" class="mb-4 border border-primary border-opacity-25 shadow-sm">
                <VImg :src="avatarPreview || '/images/avatars/avatar-1.png'" cover />
              </VAvatar>
              
              <VBtn
                icon="ri-camera-line"
                color="primary"
                size="small"
                class="position-absolute"
                style="bottom: 15px; right: -5px;"
                @click="$refs.avatarInput.click()"
              />
            </div>

            <input
              ref="avatarInput"
              type="file"
              accept="image/*"
              class="d-none"
              @change="e => onAvatarSelected(e.target.files[0])"
            />

            <div class="text-caption text-center mb-2 px-4">
              JPG, GIF o PNG. Máx 800kB
            </div>

            <VBtn
              v-if="avatarFile"
              variant="tonal"
              color="error"
              size="x-small"
              prepend-icon="ri-delete-bin-line"
              @click="clearSelectedAvatar"
            >
              Quitar seleccionada
            </VBtn>
          </VCol>

          <VCol cols="12" md="9">
            <VRow class="mb-4">
              <VCol cols="12" md="6">
                <VTextField v-model="profileForm.name" label="Nombre completo" placeholder="Tu nombre" prepend-inner-icon="ri-user-line" />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField v-model="profileForm.phone" label="Teléfono" placeholder="Tu teléfono" prepend-inner-icon="ri-phone-line" />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField v-model="profileForm.document" label="Número de documento" placeholder="Tu documento" prepend-inner-icon="ri-id-card-line" />
              </VCol>
            </VRow>

            <div class="d-flex justify-start pt-2">
              <VBtn color="primary" :loading="profileSaving" prepend-icon="ri-save-line" @click="saveProfile">
                Guardar datos personales
              </VBtn>
            </div>
          </VCol>
        </VRow>

        <VDivider class="my-6" />

        <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">
          {{ errorMessage }}
        </VAlert>
        <VAlert v-if="successMessage" type="success" variant="tonal" class="mb-4">
          {{ successMessage }}
        </VAlert>

        <VAlert :type="status.two_factor_enabled ? 'success' : 'info'" variant="tonal" class="mb-4">
          Estado 2FA: <strong>{{ status.two_factor_enabled ? 'Activado' : 'Desactivado' }}</strong>
        </VAlert>

        <div v-if="!status.two_factor_enabled" class="d-flex flex-wrap gap-3 mb-4">
          <VBtn :loading="saving" @click="initSetup">Activar autenticacion de doble factor</VBtn>
        </div>

        <div v-if="status.has_pending_setup || setup.manual_key" class="mb-6">
          <h5 class="text-h5 mb-2">Configurar Google Authenticator</h5>
          <p class="mb-3">Escanea el QR o ingresa la clave manual en tu aplicacion autenticadora.</p>

          <div class="mb-4" v-if="setup.qr_svg" v-html="setup.qr_svg" />

          <VAlert color="warning" variant="tonal" class="mb-4" v-if="setup.manual_key">
            <template #title>{{ setup.manual_key }}</template>
            Clave manual para configurar la aplicacion
          </VAlert>

          <VTextField
            v-model="verifyCode"
            label="Codigo de verificacion"
            placeholder="123456"
            maxlength="6"
            class="mb-3"
          />

          <VBtn color="success" :loading="saving" @click="confirmSetup">Confirmar activacion</VBtn>
        </div>

        <div v-if="status.two_factor_enabled" class="mb-6">
          <h5 class="text-h5 mb-2">Desactivar 2FA</h5>
          <p class="mb-3">Por seguridad, confirma tu contrasena y un codigo TOTP o recovery.</p>

          <VRow>
            <VCol cols="12" md="6">
              <VTextField
                v-model="disableForm.password"
                type="password"
                label="Contrasena"
                placeholder="********"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="disableForm.code"
                label="Codigo TOTP o recovery"
                placeholder="123456 o XXXXXXXX-XXXXXXXX"
              />
            </VCol>
          </VRow>

          <VBtn color="error" :loading="saving" class="mt-4" @click="disableTwoFactor">Desactivar autenticacion</VBtn>
        </div>

        <div v-if="status.two_factor_enabled" class="mb-4">
          <h5 class="text-h5 mb-2">Regenerar codigos de recuperacion</h5>
          <VTextField
            v-model="regeneratePassword"
            type="password"
            label="Confirma tu contrasena"
            class="mb-3"
          />
          <VBtn variant="outlined" :loading="saving" @click="regenerateCodes">Generar nuevos codigos</VBtn>
        </div>

        <div v-if="recoveryCodes.length">
          <VAlert color="warning" variant="tonal" class="mb-3">
            Guarda estos codigos de recuperacion en un lugar seguro. Solo se muestran una vez.
          </VAlert>

          <VBtn color="secondary" variant="outlined" class="mb-3" prepend-icon="ri-file-copy-line" @click="copyRecoveryCodes">
            Copiar codigos
          </VBtn>

          <VList class="border rounded">
            <VListItem v-for="code in recoveryCodes" :key="code">
              <VListItemTitle class="font-weight-bold">{{ code }}</VListItemTitle>
            </VListItem>
          </VList>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>

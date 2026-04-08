<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import avatar1 from '@images/avatars/avatar-1.png'

const router = useRouter()

const userProfileList = [
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'ri-user-line',
    title: 'Perfil',
    to: { name: 'perfil' },
  },
  // {
  //   type: 'navItem',
  //   icon: 'ri-settings-4-line',
  //   title: 'Settings',
  //   href: '#',
  // },
  // {
  //   type: 'navItem',
  //   icon: 'ri-file-text-line',
  //   title: 'Billing Plan',
  //   href: '#',
  //   chipsProps: {
  //     color: 'error',
  //     text: '4',
  //     size: 'small',
  //   },
  // },
  // { type: 'divider' },
  // {
  //   type: 'navItem',
  //   icon: 'ri-money-dollar-circle-line',
  //   title: 'Pricing',
  //   href: '#',
  // },
  // {
  //   type: 'navItem',
  //   icon: 'ri-question-line',
  //   title: 'FAQ',
  //   href: '#',
  // },
]

const user = ref(null)

const loadUserFromStorage = () => {
  user.value = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null
}

const clearAuthSession = () => {
  localStorage.removeItem('user')
  localStorage.removeItem('token')
}

const logout = async () => {
  const token = localStorage.getItem('token')

  try {
    if (token) {
      await $api('auth/logout', {
        method: 'POST',
      })
    }
  } catch (error) {
    console.warn('No se pudo cerrar sesion en backend, se limpia sesion local.', error)
  } finally {
    clearAuthSession()

    try {
      await router.replace({ name: 'login' })
    } catch (navigationError) {
      window.location.reload()
    }
  }
}

const goToProfileItem = item => {
  if (item.to) {
    router.push(item.to)
    return
  }

  if (item.href) {
    window.location.href = item.href
  }
}

const onStorageUpdate = () => {
  loadUserFromStorage()
}

onMounted(() => {
  loadUserFromStorage()
  window.addEventListener('storage', onStorageUpdate)
  window.addEventListener('user-updated', onStorageUpdate)
})

onBeforeUnmount(() => {
  window.removeEventListener('storage', onStorageUpdate)
  window.removeEventListener('user-updated', onStorageUpdate)
})
</script>

<template>
  <VBadge dot bordered location="bottom right" offset-x="2" offset-y="2" color="success" class="user-profile-badge">
    <VAvatar class="cursor-pointer" size="38">
      <VImg :src="user && user.avatar ? user.avatar : avatar1" />

      <!-- SECTION Menu -->
      <VMenu activator="parent" width="230" location="bottom end" offset="15px">
        <VList>
          <VListItem class="px-4">
            <div class="d-flex gap-x-2 align-center" v-if="user">
              <VAvatar>
                <VImg :src="user.avatar ? user.avatar : avatar1" />
              </VAvatar>

              <div>
                <div class="text-body-2 font-weight-medium text-high-emphasis">
                  {{ user?.full_name }}
                </div>
                <div class="text-capitalize text-caption text-disabled">
                  {{ user.role.name }}
                </div>
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template v-for="item in userProfileList" :key="item.title">
              <VListItem
                v-if="item.type === 'navItem'"
                class="px-4"
                link
                @click="goToProfileItem(item)"
              >
                <template #prepend>
                  <VIcon :icon="item.icon" size="22" />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template v-if="item.chipsProps" #append>
                  <VChip v-bind="item.chipsProps" variant="elevated" />
                </template>
              </VListItem>

              <VDivider v-else class="my-1" />
            </template>

            <VListItem class="px-4">
              <VBtn block color="error" size="small" append-icon="ri-logout-box-r-line" @click="logout">
                Cerrar Sesión
              </VBtn>
            </VListItem>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>

<style lang="scss">
.user-profile-badge {
  &.v-badge--bordered.v-badge--dot .v-badge__badge::after {
    color: rgb(var(--v-theme-background));
  }
}
</style>

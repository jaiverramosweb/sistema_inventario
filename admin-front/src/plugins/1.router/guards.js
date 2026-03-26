import { $api } from '@/utils/api'

export const setupGuards = router => {
  // 👉 router.beforeEach
  // Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards
  router.beforeEach(to => {
    /*
         * If it's a public route, continue navigation. This kind of pages are allowed to visited by login & non-login users. Basically, without any restrictions.
         * Examples of public routes are, 404, under maintenance, etc.
         */

    if (to.meta.public)
      return

    /**
         * Check if user is logged in by checking if token & user data exists in local storage
         * Feel free to update this logic to suit your needs
         */
    // const isLoggedIn = !!(useCookie('userData').value && useCookie('accessToken').value) 
    const isLoggedIn = localStorage.getItem("token") && localStorage.getItem("user")

    /*
          If user is logged in and is trying to access login like page, redirect to home
          else allow visiting the page
          (WARN: Don't allow executing further by return statement because next code will check for permissions)
         */
    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/'
      else
        return undefined
    }

    let USER = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null
    if(USER && USER.role.name != 'Super-Admin'){
      // lista de permisos del usuario autenticado
      let permissions = USER.permissions
      if(permissions.includes(to.meta.permission) || to.meta.permission == 'all'){
        return true
      } else {
        return { name: 'not-authorized' }
      }
    }

    if (!isLoggedIn && to.matched.length) {
      /* eslint-disable indent */ 
        return isLoggedIn
            ? { name: 'not-authorized' }
            : {
                name: 'login',
                query: {
                    ...to.query,
                    to: to.fullPath !== '/' ? to.path : undefined,
                },
            }
        /* eslint-enable indent */
    }
  })

  router.afterEach((to, from) => {
    const userRaw = localStorage.getItem('user')
    const token = localStorage.getItem('token')

    if (!userRaw || !token || to.fullPath === from.fullPath) {
      return
    }

    const now = Date.now()
    const lastPath = sessionStorage.getItem('audit_last_path')
    const lastAt = Number(sessionStorage.getItem('audit_last_at') || '0')

    if (lastPath === to.fullPath && now - lastAt < 45000) {
      return
    }

    sessionStorage.setItem('audit_last_path', to.fullPath)
    sessionStorage.setItem('audit_last_at', String(now))

    const cleanPath = (to.path || '/').replace(/^\//, '')
    const rawModule = cleanPath.split('/')[0] || 'dashboard'
    const module = rawModule === 'audit' ? 'auditoria' : rawModule

    $api('audit/navigation', {
      method: 'POST',
      body: {
        route_name: to.name ? String(to.name) : null,
        module,
        path: to.fullPath,
        title: to.meta?.title || null,
      },
    }).catch(() => {
      // noop
    })
  })
}

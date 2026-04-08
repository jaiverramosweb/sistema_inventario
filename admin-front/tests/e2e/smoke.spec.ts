import { expect, test } from '@playwright/test'
import { E2E_PASSWORD, login, logout } from './helpers/auth'

const criticalModules = [
  {
    path: '/configuration/sucursales',
    cardTitle: 'CES',
  },
  {
    path: '/product/list',
    cardTitle: 'Productos',
  },
  {
    path: '/purchase/list',
    cardTitle: 'Compras',
  },
  {
    path: '/sales/list',
    cardTitle: 'Ventas / Cotización',
  },
  {
    path: '/transport/list',
    cardTitle: 'traslado',
  },
  {
    path: '/crm/leads',
    cardTitle: 'Gestión de Leads',
  },
]

test.describe('Smoke E2E - modulos criticos', () => {
  test.skip(!E2E_PASSWORD, 'Definir PW_E2E_PASSWORD para ejecutar la suite smoke.')

  test('login y cierre de sesion por invalidacion local', async ({ page }) => {
    await login(page)
    await expect(page).toHaveURL(/\/$|\/dashboard/)
    await logout(page)
  })

  test('acceso rapido a listados criticos', async ({ page }) => {
    await login(page)

    for (const module of criticalModules) {
      await test.step(`abre ${module.path}`, async () => {
        await page.goto(module.path)
        await expect(page).toHaveURL(new RegExp(`${module.path.replace('/', '\\/')}`))
        await expect(page.locator('.v-card-title', { hasText: module.cardTitle }).first()).toBeVisible()
        await expect(page.locator('table').first()).toBeVisible()
      })
    }
  })

  test('auditoria: acceso y trigger de export XLSX', async ({ page }) => {
    await login(page)
    await page.goto('/audit/logs')

    await expect(page).toHaveURL(/\/audit\/logs/)
    await expect(page.getByText(/Auditoria del Sistema/i)).toBeVisible()

    const exportXlsxButton = page.getByRole('button', { name: /Exportar XLSX/i })
    await expect(exportXlsxButton).toBeVisible()

    const exportRequest = page.waitForRequest(request => {
      const url = request.url()
      return url.includes('/audit/logs/export') && url.includes('format=xlsx')
    })

    await exportXlsxButton.click()
    await exportRequest
  })
})

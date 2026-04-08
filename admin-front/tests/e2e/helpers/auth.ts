import { expect, Page } from '@playwright/test'

export const E2E_EMAIL = process.env.PW_E2E_EMAIL || 'superadmin@sitecsas.com'
export const E2E_PASSWORD = process.env.PW_E2E_PASSWORD || ''

export async function login(page: Page) {
  await page.goto('/login')

  await expect(page.getByLabel('Email')).toBeVisible()
  await page.getByLabel('Email').fill(E2E_EMAIL)
  await page.locator('input[type="password"]').first().fill(E2E_PASSWORD)

  await page.getByRole('button', { name: 'Iniciar sesión' }).click()

  await expect
    .poll(async () => page.evaluate(() => localStorage.getItem('token')), { timeout: 20000 })
    .not.toBeNull()

  await page.goto('/')
  await expect(page).toHaveURL(/\/$|\/dashboard/)
}

export async function logout(page: Page) {
  await page.evaluate(() => {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  })

  await page.goto('/dashboard')
  await expect(page).toHaveURL(/\/login/)
}

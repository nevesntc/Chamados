import { test, expect } from '@playwright/test';

test('cria, consulta e resolve um chamado pelo navegador', async ({ page }, testInfo) => {
  const title = `Chamado automatizado ${testInfo.project.name}`;
  await page.goto('/chamados/create');
  await page.getByLabel('Título').fill(title);
  await page.getByLabel('Descrição').fill('Solicitação criada para verificar o fluxo completo com CSRF e Inertia.');
  await page.getByLabel('Prioridade').selectOption('high');
  await page.getByRole('button', { name: 'Criar chamado' }).click();
  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  await page.getByRole('link', { name: 'Editar chamado' }).click();
  await page.getByLabel('Status').selectOption('resolved');
  await page.getByRole('button', { name: 'Salvar alterações' }).click();
  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  await expect(page.getByText('Resolvido', { exact: true }).first()).toBeVisible();
  await expect(page.locator('body')).toHaveJSProperty('scrollWidth', await page.evaluate(() => window.innerWidth));
});

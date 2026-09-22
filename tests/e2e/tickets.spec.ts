import { test, expect } from '@playwright/test';

test('cria, consulta e resolve um chamado pelo navegador', async ({ page }, testInfo) => {
  const title = `Chamado automatizado ${testInfo.project.name}`;
  await page.goto('/cadastro');
  await page.getByLabel('Nome completo').fill('Pessoa de teste');
  await page.getByLabel('E-mail').fill(`teste-${testInfo.project.name}@example.test`);
  await page.getByLabel('Senha', { exact: true }).fill('SenhaSegura123');
  await page.getByLabel('Confirmar senha').fill('SenhaSegura123');
  await page.getByRole('button', { name: 'Criar conta' }).click();
  await expect(page).toHaveURL(/\/workspace$/);
  await expect(page.getByRole('heading', { name: 'Painel' })).toBeVisible();
  await expect(page.getByText('Ainda não há chamados')).toBeVisible();
  if (testInfo.project.name === 'mobile') {
    await page.getByRole('button', { name: 'Alternar navegação' }).click();
    await expect(page.getByRole('navigation', { name: 'Navegação principal' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('button', { name: 'Alternar navegação' })).toBeFocused();
  }
  await page.goto('/workspace/chamados/create');
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
  await page.goto('/workspace/equipe');
  await expect(page.locator('h1')).toContainText('Equipe');
  await page.goto('/workspace/perfil');
  await expect(page.locator('h1')).toContainText('Perfil');
  if (testInfo.project.name === 'mobile') {
    await page.getByRole('button', { name: 'Alternar navegação' }).click();
  }
  await page.getByRole('button', { name: 'Sair' }).click();
  await expect(page).toHaveURL(/\/entrar$/);
  await page.getByLabel('E-mail').fill(`teste-${testInfo.project.name}@example.test`);
  await page.getByLabel('Senha').fill('SenhaIncorreta123');
  await page.getByRole('button', { name: 'Entrar', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('E-mail ou senha inválidos');
  await page.getByLabel('Senha').fill('SenhaSegura123');
  await page.getByRole('button', { name: 'Entrar', exact: true }).click();
  await expect(page).toHaveURL(/\/workspace$/);
  await page.goto('/workspace/chamados');
  await expect(page.getByText(title)).toBeVisible();
});

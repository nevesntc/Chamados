import { test, expect, type Page } from '@playwright/test';

async function register(page: Page, name: string, email: string) {
  await page.goto('/cadastro');
  await page.getByLabel('Nome completo').fill(name);
  await page.getByLabel('E-mail').fill(email);
  await page.getByLabel('Senha', { exact: true }).fill('SenhaSegura123');
  await page.getByLabel('Confirmar senha').fill('SenhaSegura123');
  await page.getByRole('button', { name: 'Criar conta' }).click();
  await expect(page).toHaveURL(/\/workspace$/);
}

async function loginAs(page: Page, email: string) {
  await page.goto('/workspace');
  // No celular a barra lateral só aparece depois do botão de navegação.
  const toggle = page.getByRole('button', { name: 'Alternar navegação' });
  if (await toggle.isVisible()) await toggle.click();
  await page.getByRole('button', { name: 'Sair', exact: true }).click();
  await expect(page).toHaveURL(/\/entrar$/);
  await page.getByLabel('E-mail').fill(email);
  await page.getByLabel('Senha').fill('SenhaSegura123');
  await page.getByRole('button', { name: 'Entrar', exact: true }).click();
  await expect(page).toHaveURL(/\/workspace$/);
}

async function invite(page: Page): Promise<string> {
  await page.goto('/workspace/equipe');
  await page.getByRole('button', { name: 'Gerar código' }).click();
  const code = await page.locator('.invite-code strong').innerText();
  expect(code).toHaveLength(48);

  return code;
}

test('renomeia a equipe, remove um membro e permite sair', async ({ page }, testInfo) => {
  // As confirmações de remover e sair usam diálogo nativo.
  page.on('dialog', (dialog) => dialog.accept());
  const suffix = testInfo.project.name;
  const ownerEmail = `dono-${suffix}@example.test`;
  const memberEmail = `membro-${suffix}@example.test`;
  const teamName = `Suporte ${suffix}`;

  await register(page, 'Dona da Equipe', ownerEmail);
  await page.goto('/workspace/equipe');
  await page.getByRole('button', { name: 'Editar nome da equipe' }).click();
  await page.getByLabel('Nome da equipe').fill(teamName);
  await page.getByRole('button', { name: 'Salvar nome' }).click();
  await expect(page.getByText(teamName).first()).toBeVisible();

  const firstCode = await invite(page);
  await register(page, 'Pessoa Convidada', memberEmail);
  await page.goto('/workspace/equipe');
  await page.getByLabel('Código de convite').fill(firstCode);
  await page.getByRole('button', { name: 'Entrar na equipe' }).click();
  await expect(page.locator('.team-person')).toHaveCount(2);
  // Quem não é dono não convida nem renomeia, mas pode sair.
  await expect(page.getByRole('button', { name: 'Gerar código' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Sair da equipe' })).toBeVisible();

  await loginAs(page, ownerEmail);
  await page.goto('/workspace/equipe');
  await expect(page.locator('.team-person')).toHaveCount(2);
  await page.getByRole('button', { name: 'Remover Pessoa Convidada da equipe' }).click();
  await expect(page.locator('.team-person')).toHaveCount(1);
  await expect(page.getByText('saiu da equipe')).toBeVisible();

  const secondCode = await invite(page);
  await loginAs(page, memberEmail);
  await page.goto('/workspace/equipe');
  await page.getByLabel('Código de convite').fill(secondCode);
  await page.getByRole('button', { name: 'Entrar na equipe' }).click();
  await expect(page.locator('.team-person')).toHaveCount(2);

  await page.getByRole('button', { name: 'Sair da equipe' }).click();
  await expect(page).toHaveURL(/\/workspace$/);
  await page.goto('/workspace/equipe');
  await expect(page.getByText(teamName)).toHaveCount(0);

  await loginAs(page, ownerEmail);
  await page.goto('/workspace/equipe');
  await expect(page.locator('.team-person')).toHaveCount(1);
});

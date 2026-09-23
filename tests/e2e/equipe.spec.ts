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

async function logout(page: Page) {
  await page.goto('/workspace');
  await page.getByRole('button', { name: 'Sair', exact: true }).click();
  await expect(page).toHaveURL(/\/entrar$/);
}

async function loginAs(page: Page, email: string) {
  await logout(page);
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
  // Uma passagem basta: cadastrar duas contas em cada projeto estouraria o limite
  // de tentativas por minuto que protege a rota de cadastro.
  test.skip(testInfo.project.name !== 'desktop', 'Fluxo verificado uma vez, no desktop.');
  // As confirmações de remover e de sair usam diálogo nativo.
  page.on('dialog', (dialog) => dialog.accept());
  const ownerEmail = 'dona-equipe@example.test';
  const memberEmail = 'membro-equipe@example.test';
  const teamName = 'Suporte Interno';

  await register(page, 'Dona da Equipe', ownerEmail);
  await page.goto('/workspace/equipe');
  await page.getByRole('button', { name: 'Editar nome da equipe' }).click();
  await page.getByLabel('Nome da equipe').fill(teamName);
  await page.getByRole('button', { name: 'Salvar nome' }).click();
  await expect(page.locator('.workspace strong')).toHaveText(teamName);

  const firstCode = await invite(page);
  await logout(page);
  await register(page, 'Pessoa Convidada', memberEmail);
  await page.goto('/workspace/equipe');
  await page.getByLabel('Código de convite').fill(firstCode);
  await page.getByRole('button', { name: 'Entrar na equipe' }).click();
  await expect(page.locator('.team-person')).toHaveCount(2);
  // Quem não é dono não convida nem renomeia, mas pode sair.
  await expect(page.getByRole('button', { name: 'Gerar código' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Editar nome da equipe' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Sair da equipe' })).toBeVisible();

  await loginAs(page, ownerEmail);
  await page.goto('/workspace/equipe');
  await expect(page.locator('.team-person')).toHaveCount(2);
  await page.getByRole('button', { name: 'Remover Pessoa Convidada da equipe' }).click();
  await expect(page.locator('.team-person')).toHaveCount(1);

  const secondCode = await invite(page);
  await loginAs(page, memberEmail);
  await page.goto('/workspace/equipe');
  await page.getByLabel('Código de convite').fill(secondCode);
  await page.getByRole('button', { name: 'Entrar na equipe' }).click();
  await expect(page.locator('.team-person')).toHaveCount(2);

  await page.getByRole('button', { name: 'Sair da equipe' }).click();
  await expect(page).toHaveURL(/\/workspace$/);
  // Fora da equipe, volta para o espaço pessoal e não vê mais os chamados dela.
  await expect(page.locator('.workspace strong')).not.toHaveText(teamName);
  await page.goto('/workspace/equipe');
  await expect(page.locator('.team-person')).toHaveCount(1);
});

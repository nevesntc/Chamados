import { readFileSync, writeFileSync } from 'node:fs';
import process from 'node:process';

const required = ['CLOUDFLARE_API_TOKEN', 'CLOUDFLARE_ACCOUNT_ID', 'APP_KEY', 'DB_PASSWORD', 'APP_URL', 'DB_HOST', 'DB_USERNAME'];
for (const key of required) {
  if (!process.env[key] || /[\r\n]/.test(process.env[key])) throw new Error(`Configuração ausente ou inválida: ${key}`);
}
if (!/^[a-f0-9]{32}$/.test(process.env.CLOUDFLARE_ACCOUNT_ID)) throw new Error('Account ID inválido');
if (!process.env.APP_URL.startsWith('https://')) throw new Error('APP_URL deve usar HTTPS');
const config = JSON.parse(readFileSync('wrangler.jsonc', 'utf8'));
for (const key of ['APP_URL', 'DB_HOST', 'DB_USERNAME']) config.vars[key] = process.env[key];
writeFileSync('wrangler.jsonc', JSON.stringify(config, null, 2));
writeFileSync('.secrets.deploy.json', JSON.stringify({ APP_KEY: process.env.APP_KEY, DB_PASSWORD: process.env.DB_PASSWORD }), { mode: 0o600 });
const values = { ...config.vars, APP_KEY: process.env.APP_KEY, DB_PASSWORD: process.env.DB_PASSWORD, APP_ENV: 'production', APP_DEBUG: 'false', DB_CONNECTION: 'pgsql', DB_SSLMODE: 'require', CACHE_STORE: 'array' };
writeFileSync('.env.deploy', Object.entries(values).map(([key, value]) => `${key}=${value}`).join('\n') + '\n', { mode: 0o600 });

import { Container, getContainer } from '@cloudflare/containers';
import { env } from 'cloudflare:workers';

export class LaravelContainer extends Container {
  defaultPort = 80;
  sleepAfter = '5m';
  envVars = {
    APP_KEY: env.APP_KEY,
    APP_URL: env.APP_URL,
    APP_LOCALE: 'pt_BR',
    APP_FALLBACK_LOCALE: 'pt_BR',
    TRUST_PROXY: 'true',
    DB_CONNECTION: 'pgsql',
    DB_HOST: env.DB_HOST,
    DB_PORT: env.DB_PORT,
    DB_DATABASE: env.DB_DATABASE,
    DB_USERNAME: env.DB_USERNAME,
    DB_PASSWORD: env.DB_PASSWORD,
    DB_SCHEMA: env.DB_SCHEMA,
    DB_SSLMODE: 'require',
    SESSION_DRIVER: 'database',
    SESSION_SECURE_COOKIE: 'true',
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
  };
}

export default {
  async fetch(request, bindings) {
    if (!bindings.APP_KEY || !bindings.DB_PASSWORD || bindings.APP_URL.endsWith('.invalid')) {
      return new Response('Deploy ainda não configurado.', { status: 503 });
    }
    const upstream = new Request(request);
    upstream.headers.set('X-Forwarded-Proto', new URL(request.url).protocol.slice(0, -1));
    return getContainer(bindings.LARAVEL, 'app').fetch(upstream);
  },
} satisfies ExportedHandler<Env>;

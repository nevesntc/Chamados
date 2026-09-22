<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, LockKeyhole } from '@lucide/vue';
const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
</script>

<template>
  <Head title="Criar conta" />
  <main class="auth-page">
    <section class="auth-intro">
      <Link href="/entrar" class="auth-brand">
        <span class="brand-icon"><LockKeyhole :size="22" /></span>
        central
        <span class="title-dot">.</span>
      </Link>
      <div>
        <p class="eyebrow">CENTRAL DE CHAMADOS</p>
        <h1>Comece com clareza.</h1>
        <p>Seu espaço começa privado. Convide colegas quando quiser trabalhar em equipe.</p>
      </div>
      <small>Você controla quem entra no seu espaço.</small>
    </section>
    <section class="auth-content">
      <div class="auth-card">
        <p class="eyebrow">PRIMEIRO ACESSO</p>
        <h2>Crie sua conta</h2>
        <p class="muted">Leva menos de um minuto para começar.</p>
        <form
          @submit.prevent="
            form.post('/cadastro', {
              onSuccess: () => form.reset('password', 'password_confirmation'),
            })
          "
        >
          <div class="form-field">
            <label for="name">Nome completo</label>
            <input
              id="name"
              v-model="form.name"
              autocomplete="name"
              required
              maxlength="120"
              autofocus
              :aria-invalid="!!form.errors.name"
            />
            <p v-if="form.errors.name" class="field-error" role="alert">{{ form.errors.name }}</p>
          </div>
          <div class="form-field">
            <label for="email">E-mail</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              :aria-invalid="!!form.errors.email"
            />
            <p v-if="form.errors.email" class="field-error" role="alert">{{ form.errors.email }}</p>
          </div>
          <div class="form-field">
            <label for="password">Senha</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              required
              minlength="8"
              :aria-invalid="!!form.errors.password"
            />
            <p class="field-hint">Mínimo de 8 caracteres, com letras e números.</p>
            <p v-if="form.errors.password" class="field-error" role="alert">
              {{ form.errors.password }}
            </p>
          </div>
          <div class="form-field">
            <label for="password_confirmation">Confirmar senha</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
            />
          </div>
          <button
            class="button button-primary auth-submit"
            type="submit"
            :disabled="form.processing"
          >
            Criar conta
            <ArrowRight :size="17" />
          </button>
        </form>
        <p class="auth-switch">
          Já tem conta?
          <Link href="/entrar">Entrar</Link>
        </p>
      </div>
    </section>
  </main>
</template>

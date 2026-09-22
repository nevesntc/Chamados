<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, LockKeyhole } from '@lucide/vue';
const form = useForm({ email: '', password: '' });
</script>

<template>
  <Head title="Entrar" />
  <main class="auth-page">
    <section class="auth-intro">
      <Link href="/entrar" class="auth-brand">
        <span class="brand-icon"><LockKeyhole :size="22" /></span>
        central
        <span class="title-dot">.</span>
      </Link>
      <div>
        <p class="eyebrow">CENTRAL DE CHAMADOS</p>
        <h1>Seu trabalho, em ordem.</h1>
        <p>Organize solicitações, acompanhe o andamento e mantenha sua equipe conectada.</p>
      </div>
      <small>Um espaço privado para cada equipe.</small>
    </section>
    <section class="auth-content">
      <div class="auth-card">
        <p class="eyebrow">BEM-VINDO DE VOLTA</p>
        <h2>Entre na sua conta</h2>
        <p class="muted">Acesse seus chamados e sua equipe.</p>
        <form @submit.prevent="form.post('/entrar')">
          <div class="form-field">
            <label for="email">E-mail</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              autofocus
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
              autocomplete="current-password"
              required
              :aria-invalid="!!form.errors.password"
            />
            <p v-if="form.errors.password" class="field-error" role="alert">
              {{ form.errors.password }}
            </p>
          </div>
          <button
            class="button button-primary auth-submit"
            type="submit"
            :disabled="form.processing"
          >
            Entrar
            <ArrowRight :size="17" />
          </button>
        </form>
        <p class="auth-switch">
          Ainda não tem conta?
          <Link href="/cadastro">Criar conta</Link>
        </p>
      </div>
    </section>
  </main>
</template>

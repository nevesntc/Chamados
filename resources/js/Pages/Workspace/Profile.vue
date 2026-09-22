<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { UserRound, ShieldCheck } from '@lucide/vue';
const props = defineProps<{ account: { name: string; email: string } }>();
const profile = useForm({ name: props.account.name });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });
</script>

<template>
  <Head title="Perfil" />
  <div class="page-heading">
    <div>
      <p class="eyebrow">SUA CONTA</p>
      <h1>
        Perfil
        <span class="title-dot">.</span>
      </h1>
      <p class="page-description">Mantenha seus dados e sua senha atualizados.</p>
    </div>
  </div>
  <div class="workspace-columns">
    <form class="panel workspace-panel" @submit.prevent="profile.patch('/workspace/perfil')">
      <div class="panel-heading">
        <h2>
          <UserRound :size="18" />
          Dados pessoais
        </h2>
      </div>
      <div class="workspace-panel-body">
        <div class="form-field">
          <label for="profile-name">Nome</label>
          <input
            id="profile-name"
            v-model="profile.name"
            required
            maxlength="120"
            :aria-invalid="!!profile.errors.name"
          />
          <p v-if="profile.errors.name" class="field-error" role="alert">
            {{ profile.errors.name }}
          </p>
        </div>
        <div class="form-field">
          <label for="profile-email">E-mail</label>
          <input id="profile-email" :value="account.email" disabled />
          <p class="field-hint">O e-mail identifica sua conta.</p>
        </div>
        <button class="button button-primary" :disabled="profile.processing">Salvar perfil</button>
      </div>
    </form>
    <form
      class="panel workspace-panel"
      @submit.prevent="
        password.put('/workspace/perfil/senha', { onSuccess: () => password.reset() })
      "
    >
      <div class="panel-heading">
        <h2>
          <ShieldCheck :size="18" />
          Segurança
        </h2>
      </div>
      <div class="workspace-panel-body">
        <div class="form-field">
          <label for="current-password">Senha atual</label>
          <input
            id="current-password"
            v-model="password.current_password"
            type="password"
            autocomplete="current-password"
            required
            :aria-invalid="!!password.errors.current_password"
          />
          <p v-if="password.errors.current_password" class="field-error" role="alert">
            {{ password.errors.current_password }}
          </p>
        </div>
        <div class="form-field">
          <label for="new-password">Nova senha</label>
          <input
            id="new-password"
            v-model="password.password"
            type="password"
            autocomplete="new-password"
            required
            minlength="8"
            :aria-invalid="!!password.errors.password"
          />
          <p v-if="password.errors.password" class="field-error" role="alert">
            {{ password.errors.password }}
          </p>
        </div>
        <div class="form-field">
          <label for="confirm-password">Confirmar nova senha</label>
          <input
            id="confirm-password"
            v-model="password.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </div>
        <button class="button button-primary" :disabled="password.processing">Alterar senha</button>
      </div>
    </form>
  </div>
</template>

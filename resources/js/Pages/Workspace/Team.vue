<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { UsersRound, Copy, KeyRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import { initials } from '../../types';
defineProps<{
  people: { id: number; name: string; active_count: number }[];
  workspaces: { id: number; name: string }[];
  isOwner: boolean;
}>();
const page = usePage<{
  auth: { workspace: { id: number; name: string } };
  flash: { invite_code?: string };
}>();
const code = computed(() => page.props.flash?.invite_code);
const join = useForm({ code: '' });
const invite = useForm({});
const switching = useForm({ workspace_id: 0 });
const copied = ref(false);
async function copyCode() {
  if (code.value) {
    await navigator.clipboard.writeText(code.value);
    copied.value = true;
  }
}
function switchTo(id: number) {
  switching.workspace_id = id;
  switching.post('/workspace/trocar');
}
</script>

<template>
  <Head title="Equipe" />
  <div class="page-heading">
    <div>
      <p class="eyebrow">PESSOAS E ACESSO</p>
      <h1>
        Equipe
        <span class="title-dot">.</span>
      </h1>
      <p class="page-description">Gerencie quem pode trabalhar neste espaço.</p>
    </div>
  </div>
  <div class="workspace-columns">
    <section class="panel workspace-panel">
      <div class="panel-heading">
        <h2>
          Pessoas neste espaço
          <span class="count-pill">{{ people.length }}</span>
        </h2>
      </div>
      <div class="team-list">
        <div v-for="person in people" :key="person.id" class="team-person">
          <span class="avatar avatar-0">{{ initials(person.name) }}</span>
          <div>
            <strong>{{ person.name }}</strong>
            <small>{{ person.active_count }} chamados ativos</small>
          </div>
        </div>
      </div>
    </section>
    <div class="workspace-side">
      <section v-if="isOwner" class="panel workspace-panel">
        <div class="panel-heading">
          <h2>
            <KeyRound :size="18" />
            Convidar pessoa
          </h2>
        </div>
        <div class="workspace-panel-body">
          <p>
            Crie um código válido por 7 dias. Compartilhe-o com quem deve entrar nesta equipe. Um
            novo código substitui o anterior.
          </p>
          <button
            class="button button-primary"
            type="button"
            :disabled="invite.processing"
            @click="invite.post('/workspace/equipe/convites')"
          >
            Gerar código
          </button>
          <div v-if="code" class="invite-code">
            <strong>{{ code }}</strong>
            <button
              type="button"
              class="icon-button"
              :aria-label="copied ? 'Copiado' : 'Copiar código'"
              @click="copyCode"
            >
              <Copy :size="17" />
            </button>
          </div>
          <p v-if="code" class="field-hint">Copie agora: o código não aparece novamente.</p>
        </div>
      </section>
      <section class="panel workspace-panel">
        <div class="panel-heading"><h2>Entrar em outra equipe</h2></div>
        <form
          class="workspace-panel-body"
          @submit.prevent="join.post('/workspace/equipe/entrar', { onSuccess: () => join.reset() })"
        >
          <p>Recebeu um código de convite? Insira-o para acessar o workspace da equipe.</p>
          <div class="form-field">
            <label for="invite-code">Código de convite</label>
            <input
              id="invite-code"
              v-model="join.code"
              required
              maxlength="48"
              :aria-invalid="!!join.errors.code"
            />
            <p v-if="join.errors.code" class="field-error" role="alert">{{ join.errors.code }}</p>
          </div>
          <button class="button button-primary" :disabled="join.processing">
            Entrar na equipe
          </button>
        </form>
      </section>
      <section v-if="workspaces.length > 1" class="panel workspace-panel">
        <div class="panel-heading">
          <h2>
            <UsersRound :size="18" />
            Meus workspaces
          </h2>
        </div>
        <div class="workspace-panel-body workspace-list">
          <button
            v-for="space in workspaces"
            :key="space.id"
            type="button"
            :disabled="space.id === page.props.auth.workspace.id || switching.processing"
            @click="switchTo(space.id)"
          >
            {{ space.name }}
            <span v-if="space.id === page.props.auth.workspace.id">Atual</span>
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Users, Sparkles, ArrowRight } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { initials, type Assignee } from '../../types';
const props = defineProps<{ assignees: Assignee[] }>();
const total = computed(() => props.assignees.reduce((sum, person) => sum + person.active_count, 0));
const max = computed(() => Math.max(1, ...props.assignees.map((person) => person.active_count)));
</script>
<template>
  <section class="panel workload-panel" aria-labelledby="workload-title">
    <div class="panel-heading">
      <h2 id="workload-title">
        <Users :size="18" />
        Carga da equipe
      </h2>
      <span class="subtle-pill">{{ assignees.length }} pessoas</span>
    </div>
    <p class="muted panel-subtitle">Chamados abertos e em andamento.</p>
    <div v-if="!assignees.length" class="empty-small">Nenhum responsável disponível.</div>
    <div v-for="(person, index) in assignees" :key="person.id" class="workload-person">
      <div class="person-row">
        <span class="avatar" :class="'avatar-' + (index % 3)">{{ initials(person.name) }}</span>
        <div class="person-info">
          <strong>{{ person.name }}</strong>
          <span>
            {{ person.active_count }}
            {{ person.active_count === 1 ? 'chamado ativo' : 'chamados ativos' }}
          </span>
        </div>
        <span class="workload-number">{{ person.active_count }}</span>
      </div>
      <div class="workload-track" aria-hidden="true">
        <div
          :style="{ width: (person.active_count / max) * 100 + '%' }"
          :class="'bar-' + (index % 3)"
        ></div>
      </div>
    </div>
    <div class="workload-total">
      <span>Total em atendimento</span>
      <strong>{{ total }}</strong>
    </div>
    <div class="distribution-tip">
      <Sparkles :size="18" />
      <div>
        <strong>Trabalho bem distribuído</strong>
        <p>
          A atribuição automática direciona o próximo chamado para quem tem menos solicitações
          ativas.
        </p>
      </div>
    </div>
    <Link href="/workspace/chamados/create" class="workload-link">
      Criar com atribuição automática
      <ArrowRight :size="15" />
    </Link>
  </section>
</template>

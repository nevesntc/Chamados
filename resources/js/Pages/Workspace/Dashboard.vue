<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { ArrowUpRight, Plus, Ticket, UsersRound, CircleDot, CheckCheck } from '@lucide/vue';
import { ticketCode, formatDate, type Ticket as TicketType } from '../../types';
defineProps<{
  summary: { total: number; active: number; completed: number; team: number };
  recent: TicketType[];
}>();
let refreshTimer: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
  refreshTimer = setInterval(() => {
    if (document.visibilityState === 'visible')
      router.reload({ only: ['summary', 'recent'], async: true });
  }, 10000);
});
onUnmounted(() => clearInterval(refreshTimer));
</script>

<template>
  <Head title="Painel" />
  <div class="page-heading">
    <div>
      <p class="eyebrow">SEU ESPAÇO DE TRABALHO</p>
      <h1>
        Painel
        <span class="title-dot">.</span>
      </h1>
      <p class="page-description">Uma visão clara do que a equipe precisa resolver.</p>
    </div>
    <Link href="/workspace/chamados/create" class="button button-primary">
      <Plus :size="18" />
      Novo chamado
    </Link>
  </div>
  <section class="stats-grid" aria-label="Resumo do workspace">
    <div class="stat-card">
      <div class="stat-top">
        <span>Todos os chamados</span>
        <span class="stat-icon neutral"><Ticket :size="18" /></span>
      </div>
      <strong>{{ summary.total }}</strong>
      <span class="stat-note">Neste workspace</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span>Em atendimento</span>
        <span class="stat-icon purple"><CircleDot :size="18" /></span>
      </div>
      <strong>{{ summary.active }}</strong>
      <span class="stat-note">Abertos e em andamento</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span>Concluídos</span>
        <span class="stat-icon green"><CheckCheck :size="18" /></span>
      </div>
      <strong>{{ summary.completed }}</strong>
      <span class="stat-note">Resolvidos e fechados</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span>Equipe</span>
        <span class="stat-icon amber"><UsersRound :size="18" /></span>
      </div>
      <strong>{{ summary.team }}</strong>
      <span class="stat-note">Pessoas neste espaço</span>
    </div>
  </section>
  <section class="panel dashboard-panel">
    <div class="panel-heading">
      <h2>Atividade recente</h2>
      <Link href="/workspace/chamados" class="text-link">
        Ver todos
        <ArrowUpRight :size="15" />
      </Link>
    </div>
    <div v-if="recent.length" class="recent-list">
      <Link
        v-for="item in recent"
        :key="item.id"
        :href="'/workspace/chamados/' + item.id"
        class="recent-item"
      >
        <span class="ticket-code">{{ ticketCode(item.id) }}</span>
        <strong>{{ item.title }}</strong>
        <small>{{ formatDate(item.created_at) }}</small>
        <ArrowUpRight :size="16" />
      </Link>
    </div>
    <div v-else class="empty-state">
      <span><Ticket :size="30" /></span>
      <h3>Ainda não há chamados</h3>
      <p>Abra a primeira solicitação para acompanhar o trabalho por aqui.</p>
      <Link href="/workspace/chamados/create" class="button button-primary">Criar chamado</Link>
    </div>
  </section>
</template>

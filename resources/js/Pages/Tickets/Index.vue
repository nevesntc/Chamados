<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import {
  Plus,
  Ticket,
  CircleDot,
  Clock3,
  CheckCheck,
  ArrowUpRight,
  ArrowRight,
  ChevronLeft,
  ChevronRight,
  Inbox,
  Sparkles,
} from '@lucide/vue';
import StatusBadge from '../../Components/Tickets/StatusBadge.vue';
import PriorityBadge from '../../Components/Tickets/PriorityBadge.vue';
import WorkloadPanel from '../../Components/Tickets/WorkloadPanel.vue';
import TicketFilters from '../../Components/Tickets/TicketFilters.vue';
import {
  formatDate,
  initials,
  ticketCode,
  type FormOptions,
  type Filters,
  type PaginatedTickets,
} from '../../types';
defineProps<
  FormOptions & {
    filters: Filters;
    tickets: PaginatedTickets;
    summary: { total: number; open: number; in_progress: number; completed: number };
  }
>();
let refreshTimer: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
  refreshTimer = setInterval(() => {
    if (document.visibilityState === 'visible') router.reload({ only: ['tickets', 'summary', 'assignees'] });
  }, 10000);
});
onUnmounted(() => clearInterval(refreshTimer));
</script>
<template>
  <Head title="Chamados" />
  <div class="page-heading">
    <div>
      <p class="eyebrow">ORGANIZE. ACOMPANHE. RESOLVA.</p>
      <h1>
        Central de chamados
        <span class="title-dot">.</span>
      </h1>
      <p class="page-description">Menos pedidos perdidos. Mais soluções, juntos.</p>
    </div>
    <Link href="/workspace/chamados/create" class="button button-primary">
      <Plus :size="18" />
      Novo chamado
    </Link>
  </div>
  <section class="stats-grid" aria-label="Resumo de todos os chamados">
    <div class="stat-card">
      <div class="stat-top">
        <span>Total de chamados</span>
        <span class="stat-icon neutral"><Ticket :size="18" /></span>
      </div>
      <strong>{{ summary.total }}</strong>
      <span class="stat-note">Todas as solicitações</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span>Em aberto</span>
        <span class="stat-icon purple"><CircleDot :size="18" /></span>
      </div>
      <strong>{{ summary.open }}</strong>
      <span class="stat-note">
        <span class="tiny-dot purple-dot"></span>
        Aguardando atendimento
      </span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span>Em andamento</span>
        <span class="stat-icon amber"><Clock3 :size="18" /></span>
      </div>
      <strong>{{ summary.in_progress }}</strong>
      <span class="stat-note">
        <span class="tiny-dot amber-dot"></span>
        A equipe está cuidando
      </span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span>Concluídos</span>
        <span class="stat-icon green"><CheckCheck :size="18" /></span>
      </div>
      <strong>{{ summary.completed }}</strong>
      <span class="stat-note">
        <span class="tiny-dot green-dot"></span>
        Resolvidos e fechados
      </span>
    </div>
  </section>
  <div class="content-grid">
    <section class="panel tickets-panel" aria-labelledby="tickets-heading">
      <div class="panel-heading">
        <h2 id="tickets-heading">
          Todos os chamados
          <span class="count-pill">{{ tickets.total }}</span>
        </h2>
        <span class="subtle-caption">Mais recentes primeiro</span>
      </div>
      <TicketFilters
        :filters="filters"
        :assignees="assignees"
        :statuses="statuses"
        :priorities="priorities"
      />
      <div
        v-if="tickets.data.length"
        class="table-scroll"
        role="region"
        aria-label="Tabela de chamados; deslize para ver mais colunas"
        tabindex="0"
      >
        <table class="tickets-table">
          <thead>
            <tr>
              <th scope="col">Chamado</th>
              <th scope="col">Status</th>
              <th scope="col">Prioridade</th>
              <th scope="col">Responsável</th>
              <th scope="col">Abertura</th>
              <th scope="col"><span class="sr-only">Abrir</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ticket in tickets.data" :key="ticket.id">
              <td>
                <Link :href="'/workspace/chamados/' + ticket.id" class="ticket-title-link">
                  <span class="ticket-code">{{ ticketCode(ticket.id) }}</span>
                  <strong>{{ ticket.title }}</strong>
                </Link>
              </td>
              <td><StatusBadge :value="ticket.status" :options="statuses" /></td>
              <td><PriorityBadge :value="ticket.priority" :options="priorities" /></td>
              <td>
                <span class="table-person">
                  <span
                    class="avatar avatar-small"
                    :class="'avatar-' + ((ticket.assignee_id - 1) % 3)"
                  >
                    {{ initials(ticket.assignee.name) }}
                  </span>
                  <span>{{ ticket.assignee.name.split(' ')[0] }}</span>
                </span>
              </td>
              <td class="date-cell">{{ formatDate(ticket.created_at) }}</td>
              <td>
                <Link
                  :href="'/workspace/chamados/' + ticket.id"
                  class="row-arrow"
                  :aria-label="'Ver ' + ticketCode(ticket.id)"
                >
                  <ArrowUpRight :size="17" />
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <span><Inbox :size="30" /></span>
        <h3>
          {{
            Object.values(filters).some(Boolean)
              ? 'Nenhum chamado encontrado'
              : 'Tudo começa com um chamado'
          }}
        </h3>
        <p>
          {{
            Object.values(filters).some(Boolean)
              ? 'Tente ajustar os filtros para encontrar o que procura.'
              : 'Abra sua primeira solicitação e deixe a organização com a gente.'
          }}
        </p>
        <Link
          v-if="!Object.values(filters).some(Boolean)"
          href="/workspace/chamados/create"
          class="button button-primary"
        >
          <Plus :size="16" />
          Criar chamado
        </Link>
      </div>
      <div class="table-footer">
        <span>{{ tickets.from ?? 0 }}–{{ tickets.to ?? 0 }} de {{ tickets.total }} chamados</span>
        <nav class="pagination" aria-label="Paginação">
          <Link
            v-if="tickets.prev_page_url"
            :href="tickets.prev_page_url"
            class="icon-button"
            aria-label="Página anterior"
          >
            <ChevronLeft :size="16" />
          </Link>
          <button v-else disabled class="icon-button" aria-label="Página anterior">
            <ChevronLeft :size="16" />
          </button>
          <span>{{ tickets.current_page }} / {{ tickets.last_page }}</span>
          <Link
            v-if="tickets.next_page_url"
            :href="tickets.next_page_url"
            class="icon-button"
            aria-label="Próxima página"
          >
            <ChevronRight :size="16" />
          </Link>
          <button v-else disabled class="icon-button" aria-label="Próxima página">
            <ChevronRight :size="16" />
          </button>
        </nav>
      </div>
    </section>
    <div class="right-column">
      <WorkloadPanel :assignees="assignees" />
      <div class="help-card">
        <Sparkles :size="20" />
        <h3>Um fluxo mais leve.</h3>
        <p>Do primeiro pedido à solução: cada chamado tem um lugar e alguém para cuidar.</p>
        <Link href="/workspace/chamados/create">
          Vamos começar
          <ArrowRight :size="15" />
        </Link>
        <div class="help-orbit" aria-hidden="true"></div>
      </div>
    </div>
  </div>
</template>

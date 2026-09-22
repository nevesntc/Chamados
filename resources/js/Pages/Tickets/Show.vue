<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
  ArrowLeft,
  Pencil,
  CalendarDays,
  Clock3,
  UserRound,
  AlignLeft,
  CircleDot,
} from '@lucide/vue';
import StatusBadge from '../../Components/Tickets/StatusBadge.vue';
import PriorityBadge from '../../Components/Tickets/PriorityBadge.vue';
import { formatDate, ticketCode, initials, type Ticket, type Option } from '../../types';
defineProps<{ ticket: Ticket; statuses: Option[]; priorities: Option[] }>();
</script>
<template>
  <Head :title="ticketCode(ticket.id)" />
  <Link href="/workspace/chamados" class="back-link">
    <ArrowLeft :size="16" />
    Voltar para chamados
  </Link>
  <div class="page-heading">
    <div>
      <p class="eyebrow">{{ ticketCode(ticket.id) }}</p>
      <h1 class="detail-title">{{ ticket.title }}</h1>
      <p class="page-description">Cada atualização aproxima seu pedido de uma solução.</p>
    </div>
    <Link :href="'/workspace/chamados/' + ticket.id + '/edit'" class="button button-primary">
      <Pencil :size="16" />
      Editar chamado
    </Link>
  </div>
  <div class="content-grid">
    <section class="panel description-panel">
      <div class="panel-heading">
        <h2>
          <AlignLeft :size="18" />
          Sobre este chamado
        </h2>
      </div>
      <p class="ticket-description">{{ ticket.description }}</p>
      <div class="description-footer">
        <Clock3 :size="15" />
        Última atualização em {{ formatDate(ticket.updated_at, true) }}
      </div>
    </section>
    <aside class="panel detail-panel">
      <h2>Informações do chamado</h2>
      <dl>
        <div>
          <dt>
            <CircleDot :size="16" />
            Status
          </dt>
          <dd><StatusBadge :value="ticket.status" :options="statuses" /></dd>
        </div>
        <div>
          <dt>Prioridade</dt>
          <dd><PriorityBadge :value="ticket.priority" :options="priorities" /></dd>
        </div>
        <div>
          <dt>
            <UserRound :size="16" />
            Responsável
          </dt>
          <dd class="detail-person">
            <span class="avatar avatar-0">{{ initials(ticket.assignee.name) }}</span>
            {{ ticket.assignee.name }}
          </dd>
        </div>
        <div>
          <dt>
            <CalendarDays :size="16" />
            Aberto em
          </dt>
          <dd>{{ formatDate(ticket.created_at, true) }}</dd>
        </div>
      </dl>
      <p class="detail-tip">
        Abertos e em andamento entram na carga da equipe. Resolvidos e fechados são considerados
        concluídos.
      </p>
    </aside>
  </div>
</template>

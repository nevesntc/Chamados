<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Info } from '@lucide/vue';
import TicketForm from '../../Components/Tickets/TicketForm.vue';
import WorkloadPanel from '../../Components/Tickets/WorkloadPanel.vue';
import { ticketCode, type Ticket, type FormOptions } from '../../types';
defineProps<FormOptions & { ticket: Ticket }>();
</script>
<template>
  <Head :title="'Editar ' + ticketCode(ticket.id)" />
  <Link :href="'/chamados/' + ticket.id" class="back-link">
    <ArrowLeft :size="16" />
    Voltar para o chamado
  </Link>
  <div class="page-heading">
    <div>
      <p class="eyebrow">{{ ticketCode(ticket.id) }}</p>
      <h1>
        Editar chamado
        <span class="title-dot">.</span>
      </h1>
      <p class="page-description">Mantenha as informações e o andamento sempre em dia.</p>
    </div>
  </div>
  <div class="content-grid form-grid">
    <TicketForm
      :ticket="ticket"
      :assignees="assignees"
      :statuses="statuses"
      :priorities="priorities"
    />
    <div class="right-column">
      <WorkloadPanel :assignees="assignees" />
      <div class="quiet-tip">
        <Info :size="18" />
        <p>
          O responsável atual é mantido. Para redistribuir, selecione a opção automática. A contagem
          desconsidera este chamado ao fazer a escolha.
        </p>
      </div>
    </div>
  </div>
</template>

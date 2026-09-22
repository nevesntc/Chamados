<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2 } from '@lucide/vue';
import TicketForm from '../../Components/Tickets/TicketForm.vue';
import WorkloadPanel from '../../Components/Tickets/WorkloadPanel.vue';
import type { FormOptions } from '../../types';
defineProps<FormOptions>();
</script>
<template>
  <Head title="Novo chamado" />
  <Link href="/chamados" class="back-link">
    <ArrowLeft :size="16" />
    Voltar para chamados
  </Link>
  <div class="page-heading">
    <div>
      <p class="eyebrow">O PRIMEIRO PASSO PARA RESOLVER</p>
      <h1>
        Novo chamado
        <span class="title-dot">.</span>
      </h1>
      <p class="page-description">Descreva seu pedido. A gente organiza o próximo passo.</p>
    </div>
  </div>
  <div class="content-grid form-grid">
    <TicketForm :assignees="assignees" :statuses="statuses" :priorities="priorities" />
    <div class="right-column">
      <WorkloadPanel :assignees="assignees" />
      <div class="quiet-tip">
        <CheckCircle2 :size="18" />
        <p>
          Seu chamado será aberto com data, hora e responsável definidos. Tudo pronto para
          acompanhar.
        </p>
      </div>
    </div>
  </div>
</template>

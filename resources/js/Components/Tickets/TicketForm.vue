<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Sparkles, UserRound, ArrowRight, LoaderCircle, Info } from '@lucide/vue';
import type { Ticket, FormOptions } from '../../types';
const props = defineProps<FormOptions & { ticket?: Ticket }>();
const form = useForm({
  title: props.ticket?.title ?? '',
  description: props.ticket?.description ?? '',
  priority: props.ticket?.priority ?? 'medium',
  status: props.ticket?.status ?? 'open',
  assignment_mode: props.ticket ? 'manual' : 'automatic',
  assignee_id: props.ticket?.assignee_id ?? ('' as number | string),
});
function submit() {
  if (props.ticket) form.put('/workspace/chamados/' + props.ticket.id);
  else form.post('/workspace/chamados');
}
</script>
<template>
  <form class="ticket-form panel" @submit.prevent="submit">
    <div class="form-section">
      <div class="section-number">01</div>
      <div class="section-content">
        <h2>Conte o que você precisa</h2>
        <p class="muted">Quanto mais contexto, mais fácil encontrar a solução.</p>
        <div class="form-field">
          <label for="title">
            Título
            <span aria-hidden="true">*</span>
          </label>
          <input
            id="title"
            v-model="form.title"
            required
            maxlength="150"
            placeholder="Ex.: Impressora do escritório não está funcionando"
            :aria-invalid="!!form.errors.title"
            aria-describedby="title-error"
          />
          <p v-if="form.errors.title" id="title-error" class="field-error">
            {{ form.errors.title }}
          </p>
        </div>
        <div class="form-field">
          <label for="description">
            Descrição
            <span aria-hidden="true">*</span>
          </label>
          <textarea
            id="description"
            v-model="form.description"
            required
            maxlength="5000"
            rows="6"
            placeholder="Descreva a situação, onde acontece e o que você já tentou..."
            :aria-invalid="!!form.errors.description"
            aria-describedby="description-error"
          ></textarea>
          <div class="field-hint">
            <span>Inclua as informações que podem ajudar no atendimento.</span>
            <span>{{ form.description.length }}/5000</span>
          </div>
          <p v-if="form.errors.description" id="description-error" class="field-error">
            {{ form.errors.description }}
          </p>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label for="priority">
              Prioridade
              <span aria-hidden="true">*</span>
            </label>
            <select
              id="priority"
              v-model="form.priority"
              :aria-invalid="!!form.errors.priority"
              aria-describedby="priority-error"
            >
              <option v-for="option in priorities" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p v-if="form.errors.priority" id="priority-error" class="field-error">
              {{ form.errors.priority }}
            </p>
          </div>
          <div v-if="ticket" class="form-field">
            <label for="status">
              Status
              <span aria-hidden="true">*</span>
            </label>
            <select
              id="status"
              v-model="form.status"
              :aria-invalid="!!form.errors.status"
              aria-describedby="status-error"
            >
              <option v-for="option in statuses" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p v-if="form.errors.status" id="status-error" class="field-error">
              {{ form.errors.status }}
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="form-section">
      <div class="section-number">02</div>
      <div class="section-content">
        <h2>Quem vai cuidar disso?</h2>
        <p class="muted">Escolha uma pessoa ou deixe a Central equilibrar a equipe.</p>
        <fieldset class="assignment-options">
          <legend class="sr-only">Forma de atribuição</legend>
          <label
            class="assignment-option"
            :class="{ selected: form.assignment_mode === 'automatic' }"
          >
            <input
              v-model="form.assignment_mode"
              type="radio"
              value="automatic"
              name="assignment_mode"
            />
            <Sparkles :size="20" />
            <span>
              <strong>
                Distribuição automática
                <small>Recomendado</small>
              </strong>
              <span>Direciona para quem tem menos chamados ativos.</span>
            </span>
          </label>
          <label class="assignment-option" :class="{ selected: form.assignment_mode === 'manual' }">
            <input
              v-model="form.assignment_mode"
              type="radio"
              value="manual"
              name="assignment_mode"
            />
            <UserRound :size="20" />
            <span>
              <strong>Escolher responsável</strong>
              <span>Selecione a pessoa mais indicada para atender.</span>
            </span>
          </label>
        </fieldset>
        <p v-if="form.errors.assignment_mode" class="field-error" role="alert">
          {{ form.errors.assignment_mode }}
        </p>
        <div v-if="form.assignment_mode === 'manual'" class="form-field">
          <label for="assignee_id">
            Responsável
            <span aria-hidden="true">*</span>
          </label>
          <select
            id="assignee_id"
            v-model="form.assignee_id"
            required
            :aria-invalid="!!form.errors.assignee_id"
            aria-describedby="assignee-error"
          >
            <option disabled value="">Selecione uma pessoa</option>
            <option v-for="person in assignees" :key="person.id" :value="person.id">
              {{ person.name }} · {{ person.active_count }} ativos
            </option>
          </select>
          <p v-if="form.errors.assignee_id" id="assignee-error" class="field-error">
            {{ form.errors.assignee_id }}
          </p>
        </div>
        <div v-else class="inline-info">
          <Info :size="16" />
          <span>O responsável será definido ao salvar, com a carga atual da equipe.</span>
        </div>
      </div>
    </div>
    <div v-if="Object.keys(form.errors).length" class="form-error-summary" role="alert">
      Revise os campos indicados antes de continuar.
    </div>
    <div class="form-footer">
      <span>* Campos obrigatórios</span>
      <div>
        <Link
          :href="ticket ? '/workspace/chamados/' + ticket.id : '/workspace/chamados'"
          class="button button-secondary"
        >
          Cancelar
        </Link>
        <button :disabled="form.processing" type="submit" class="button button-primary">
          <LoaderCircle v-if="form.processing" class="spin" :size="17" />
          {{ form.processing ? 'Salvando...' : ticket ? 'Salvar alterações' : 'Criar chamado' }}
          <ArrowRight v-if="!form.processing" :size="17" />
        </button>
      </div>
    </div>
  </form>
</template>

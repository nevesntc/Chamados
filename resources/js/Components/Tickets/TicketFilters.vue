<script setup lang="ts">
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search, SlidersHorizontal, X } from '@lucide/vue';
import type { Filters, FormOptions } from '../../types';
const props = defineProps<FormOptions & { filters: Filters }>();
const values = reactive({
  search: '',
  status: '',
  priority: '',
  assignee_id: '' as string | number,
  ...props.filters,
});
watch(
  () => props.filters,
  (filters) =>
    Object.assign(values, { search: '', status: '', priority: '', assignee_id: '' }, filters),
);
function apply() {
  router.get(
    '/workspace/chamados',
    Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '' && value != null)),
    { preserveState: true, preserveScroll: true, replace: true },
  );
}
function reset() {
  Object.assign(values, { search: '', status: '', priority: '', assignee_id: '' });
  apply();
}
</script>
<template>
  <form class="filters" aria-label="Filtrar chamados" @submit.prevent="apply">
    <div class="search-field">
      <Search :size="17" />
      <input
        v-model="values.search"
        aria-label="Buscar por título"
        placeholder="Buscar por título..."
        maxlength="150"
      />
      <button type="submit" class="search-submit" aria-label="Buscar">↵</button>
    </div>
    <label class="sr-only" for="status-filter">Status</label>
    <select id="status-filter" v-model="values.status" @change="apply">
      <option value="">Todos os status</option>
      <option v-for="option in statuses" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
    <label class="sr-only" for="priority-filter">Prioridade</label>
    <select id="priority-filter" v-model="values.priority" @change="apply">
      <option value="">Prioridade</option>
      <option v-for="option in priorities" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
    <label class="sr-only" for="assignee-filter">Responsável</label>
    <select id="assignee-filter" v-model="values.assignee_id" @change="apply">
      <option value="">Responsável</option>
      <option v-for="person in assignees" :key="person.id" :value="person.id">
        {{ person.name }}
      </option>
    </select>
    <button
      v-if="Object.values(filters).some(Boolean)"
      type="button"
      class="icon-button"
      aria-label="Limpar filtros"
      title="Limpar filtros"
      @click="reset"
    >
      <X :size="17" />
    </button>
    <SlidersHorizontal v-else class="filter-decoration" :size="17" aria-hidden="true" />
  </form>
</template>

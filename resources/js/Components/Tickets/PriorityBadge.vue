<script setup lang="ts">
import { ArrowUp, Minus, ArrowDown } from '@lucide/vue';
import type { Priority, Option } from '../../types';
defineProps<{ value: Priority; options: Option[] }>();
</script>
<template>
  <span class="priority-badge" :class="'priority-' + value">
    <ArrowUp v-if="value === 'high'" :size="14" />
    <ArrowDown v-else-if="value === 'low'" :size="14" />
    <Minus v-else :size="14" />
    {{ options.find((option) => option.value === value)?.label ?? value }}
  </span>
</template>

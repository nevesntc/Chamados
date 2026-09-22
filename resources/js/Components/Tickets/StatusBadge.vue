<script setup lang="ts">
import type { Status, Option } from '../../types';
defineProps<{ value: Status; options: Option[] }>();
</script>
<template>
  <span class="status-badge" :class="'status-' + value">
    <span class="status-dot"></span>
    {{ options.find((option) => option.value === value)?.label ?? value }}
  </span>
</template>

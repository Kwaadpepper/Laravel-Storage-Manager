<script setup lang="ts">
defineOptions({
  name: 'FieldError',
});
import { computed } from 'vue';

const props = defineProps<{
  fieldError?: { message?: string } | string;
  message?: string;
}>();

const errorMessage = computed(() => {
  if (typeof props.fieldError === 'string') return props.fieldError;
  return props.message ?? props.fieldError?.message;
});
</script>

<template>
  <small
    v-if="errorMessage"
    class="validator-hint mx-auto my-0 visible text-[var(--color-error)]"
  >
    {{ errorMessage }}
  </small>
</template>

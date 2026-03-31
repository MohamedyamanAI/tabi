<script setup lang="ts">
import DialogModal from '@/packages/ui/src/DialogModal.vue';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    select: [arch: 'arm64' | 'x64'];
}>();
</script>

<template>
    <DialogModal :show="show" max-width="xl" closeable @close="emit('close')">
        <template #title>Choose your macOS version</template>

        <template #content>
            <p class="text-sm">
                To make sure desktop tracking runs smoothly, pick the build that matches your Mac.
            </p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    class="rounded-lg border border-card-border bg-card-background px-4 py-3 text-left transition-colors hover:bg-default-background"
                    @click="emit('select', 'arm64')">
                    <div class="text-sm font-semibold text-text-primary">Apple Silicon (ARM64)</div>
                    <div class="mt-1 text-xs text-text-secondary">M1, M2, M3, and newer Macs</div>
                </button>

                <button
                    type="button"
                    class="rounded-lg border border-card-border bg-card-background px-4 py-3 text-left transition-colors hover:bg-default-background"
                    @click="emit('select', 'x64')">
                    <div class="text-sm font-semibold text-text-primary">Intel (x64)</div>
                    <div class="mt-1 text-xs text-text-secondary">Older Intel-based Macs</div>
                </button>
            </div>
        </template>

        <template #footer>
            <button
                type="button"
                class="rounded-lg border border-card-border px-4 py-2 text-sm font-medium text-text-primary transition-colors hover:bg-card-background"
                @click="emit('close')">
                Cancel
            </button>
        </template>
    </DialogModal>
</template>

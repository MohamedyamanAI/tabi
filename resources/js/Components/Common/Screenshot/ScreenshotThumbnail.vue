<script setup lang="ts">
import { TrashIcon } from '@heroicons/vue/16/solid';

defineProps<{
    id: string;
    imageUrl: string;
    capturedAt: string;
    canDelete: boolean;
}>();

const emit = defineEmits<{
    click: [];
    delete: [];
}>();
</script>

<template>
    <div
        class="group relative rounded-lg overflow-hidden border border-card-border bg-card-background shadow-card cursor-pointer"
        @click="emit('click')">
        <img
            :src="imageUrl"
            :alt="'Screenshot captured at ' + capturedAt"
            class="w-full aspect-video object-cover" />
        <div
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-1.5">
            <span class="text-xs text-white">{{ capturedAt }}</span>
        </div>
        <button
            v-if="canDelete"
            class="absolute top-1.5 right-1.5 p-1 rounded bg-red-600 text-white opacity-0 group-hover:opacity-100 transition-opacity"
            @click.stop="emit('delete')">
            <TrashIcon class="w-3.5 h-3.5" />
        </button>
    </div>
</template>

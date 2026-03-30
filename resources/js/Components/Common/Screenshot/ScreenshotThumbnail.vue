<script setup lang="ts">
import { computed } from 'vue';
import { TrashIcon } from '@heroicons/vue/16/solid';

const props = defineProps<{
    id: string;
    imageUrl: string;
    capturedAt: string;
    canDelete: boolean;
    activityLevel?: number | null;
}>();

const emit = defineEmits<{
    click: [];
    delete: [];
}>();

const activityDotClass = computed(() => {
    const v = props.activityLevel;
    if (v === null || v === undefined) return '';
    if (v >= 70) return 'bg-green-500';
    if (v >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
});
</script>

<template>
    <div
        class="group relative rounded-lg overflow-hidden border border-card-border bg-card-background shadow-card cursor-pointer"
        @click="emit('click')">
        <img
            :src="imageUrl"
            :alt="'Screenshot captured at ' + capturedAt"
            loading="lazy"
            class="w-full aspect-video object-cover" />
        <div
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-1.5 flex justify-between items-end gap-2">
            <span class="text-xs text-white truncate">{{ capturedAt }}</span>
            <div
                v-if="activityLevel != null"
                class="shrink-0 text-xs font-medium px-1.5 py-0.5 rounded bg-black/60 text-white flex items-center gap-1">
                <span>{{ activityLevel }}%</span>
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activityDotClass"></span>
            </div>
        </div>
        <button
            v-if="canDelete"
            class="absolute top-1.5 right-1.5 p-1 rounded bg-red-600 text-white opacity-0 group-hover:opacity-100 transition-opacity"
            @click.stop="emit('delete')">
            <TrashIcon class="w-3.5 h-3.5" />
        </button>
    </div>
</template>

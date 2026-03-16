<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useTasksStore } from '@/utils/useTasks';
import type { Task, UpdateTaskBody } from '@/packages/api/src';
import EstimatedTimeSection from '@/packages/ui/src/EstimatedTimeSection.vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { Field, FieldGroup, FieldLabel } from '@/packages/ui/src/field';
import { UserIcon } from '@heroicons/vue/24/outline';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useProjectMembersQuery } from '@/utils/useProjectMembersQuery';

const { updateTask } = useTasksStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    task: Task;
}>();

const { projectMembers } = useProjectMembersQuery(computed(() => props.task.project_id));

const TASK_STATUS_OPTIONS = [
    { value: 'active', label: 'Active' },
    { value: 'for_review', label: 'For review' },
    { value: 'for_later', label: 'For later' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'done', label: 'Done' },
] as const;

const taskBody = ref<UpdateTaskBody>({
    name: props.task.name,
    estimated_time: props.task.estimated_time,
    assignee_id: props.task.assignee_id ?? '__unassigned__',
    status: props.task.status ?? 'active',
});

watch(
    () => [show.value, props.task.id],
    () => {
        if (show.value) {
            taskBody.value = {
                name: props.task.name,
                estimated_time: props.task.estimated_time,
                assignee_id: props.task.assignee_id ?? '__unassigned__',
                status: props.task.status ?? 'active',
            };
        }
    },
    { immediate: true }
);

function assigneeIdForSubmit(): string | null {
    const v = taskBody.value.assignee_id;
    return v && v !== '__unassigned__' ? v : null;
}

async function submit() {
    await updateTask(props.task.id, {
        ...taskBody.value,
        assignee_id: assigneeIdForSubmit(),
    });
    show.value = false;
}

const taskNameInput = ref<HTMLInputElement | null>(null);

useFocus(taskNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Task </span>
            </div>
        </template>

        <template #content>
            <FieldGroup>
                <Field>
                    <FieldLabel for="taskName">Task name</FieldLabel>
                    <TextInput
                        id="taskName"
                        ref="taskNameInput"
                        v-model="taskBody.name"
                        type="text"
                        placeholder="Task Name"
                        class="block w-full"
                        required
                        autocomplete="taskName"
                        @keydown.enter="submit()" />
                </Field>
                <Field class="w-full">
                    <FieldLabel :icon="UserIcon" for="assignee">Assign to</FieldLabel>
                    <Select v-model="taskBody.assignee_id">
                        <SelectTrigger id="assignee">
                            <SelectValue placeholder="Unassigned" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__unassigned__">Unassigned</SelectItem>
                            <SelectItem
                                v-for="pm in projectMembers"
                                :key="pm.id"
                                :value="pm.member_id">
                                {{ pm.member_name ?? pm.member_id }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
                <Field class="w-full">
                    <FieldLabel for="status">Status</FieldLabel>
                    <Select v-model="taskBody.status">
                        <SelectTrigger id="status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in TASK_STATUS_OPTIONS"
                                :key="opt.value"
                                :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
                <EstimatedTimeSection
                    v-if="isAllowedToPerformPremiumAction()"
                    v-model="taskBody.estimated_time"
                    @submit="submit()"></EstimatedTimeSection>
            </FieldGroup>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Update Task
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>

<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useTasksStore } from '@/utils/useTasks';
import ProjectDropdown from '@/Components/Common/Project/ProjectDropdown.vue';
import EstimatedTimeSection from '@/packages/ui/src/EstimatedTimeSection.vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { Field, FieldGroup, FieldLabel } from '@/packages/ui/src/field';
import { Button } from '@/packages/ui/src/Buttons';
import { ChevronDown } from 'lucide-vue-next';
import { FolderIcon } from '@heroicons/vue/20/solid';
import { UserIcon } from '@heroicons/vue/24/outline';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useProjectMembersQuery } from '@/utils/useProjectMembersQuery';
import { Checkbox } from '@/packages/ui/src';
import { computed, ref, watch } from 'vue';

const { createTask } = useTasksStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const taskName = ref('');
const estimatedTime = ref<number | null>(null);
const assigneeId = ref<string | null>('__unassigned__');
const markAsLater = ref(false);
const markAsReview = ref(false);

const status = computed(() =>
    markAsReview.value ? 'for_review' : markAsLater.value ? 'for_later' : 'active'
);

const props = defineProps<{
    projectId: string;
}>();

const taskProjectId = ref<string>(props.projectId);
const { projectMembers } = useProjectMembersQuery(taskProjectId);

watch(
    () => props.projectId,
    (value) => {
        taskProjectId.value = value;
    }
);

watch(show, (isShow) => {
    if (isShow) {
        assigneeId.value = '__unassigned__';
        markAsLater.value = false;
        markAsReview.value = false;
    }
});

function setMarkAsLater(checked: boolean) {
    markAsLater.value = checked;
    if (checked) markAsReview.value = false;
}
function setMarkAsReview(checked: boolean) {
    markAsReview.value = checked;
    if (checked) markAsLater.value = false;
}

async function submit() {
    await createTask({
        name: taskName.value,
        project_id: taskProjectId.value,
        estimated_time: estimatedTime.value,
        assignee_id:
            assigneeId.value && assigneeId.value !== '__unassigned__'
                ? assigneeId.value
                : undefined,
        status: status.value,
    });
    show.value = false;
    taskName.value = '';
    assigneeId.value = '__unassigned__';
    markAsLater.value = false;
    markAsReview.value = false;
}

const taskNameInput = ref<HTMLInputElement | null>(null);

useFocus(taskNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Task </span>
            </div>
        </template>

        <template #content>
            <FieldGroup>
                <Field class="w-full">
                    <FieldLabel for="taskName">Task name</FieldLabel>
                    <TextInput
                        id="taskName"
                        ref="taskNameInput"
                        v-model="taskName"
                        type="text"
                        placeholder="Task Name"
                        class="block w-full"
                        required
                        autocomplete="taskName"
                        @keydown.enter="submit()" />
                </Field>
                <Field class="w-auto">
                    <FieldLabel :icon="FolderIcon" for="project">Project</FieldLabel>
                    <ProjectDropdown v-model="taskProjectId">
                        <template #trigger="{ selectedProjectName, selectedProjectColor }">
                            <Button variant="input" class="w-full justify-between">
                                <span class="flex items-center gap-2 truncate">
                                    <span
                                        :style="{ backgroundColor: selectedProjectColor }"
                                        class="w-3 h-3 rounded-full shrink-0"></span>
                                    <span class="truncate">{{ selectedProjectName }}</span>
                                </span>
                                <ChevronDown class="w-4 h-4 text-icon-default" />
                            </Button>
                        </template>
                    </ProjectDropdown>
                </Field>
                <Field class="w-full">
                    <FieldLabel :icon="UserIcon" for="assignee">Assign to</FieldLabel>
                    <Select v-model="assigneeId">
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
                <div class="flex flex-row flex-wrap items-center gap-6">
                    <label
                        for="markAsLater"
                        class="flex cursor-pointer items-center gap-3 text-text-primary">
                        <Checkbox
                            id="markAsLater"
                            :checked="markAsLater"
                            class="shrink-0"
                            @update:checked="setMarkAsLater" />
                        <span class="text-sm font-medium">For later</span>
                    </label>
                    <label
                        for="markAsReview"
                        class="flex cursor-pointer items-center gap-3 text-text-primary">
                        <Checkbox
                            id="markAsReview"
                            :checked="markAsReview"
                            class="shrink-0"
                            @update:checked="setMarkAsReview" />
                        <span class="text-sm font-medium">For review</span>
                    </label>
                </div>
                <EstimatedTimeSection
                    v-if="isAllowedToPerformPremiumAction()"
                    v-model="estimatedTime"
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
                Create Task
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>

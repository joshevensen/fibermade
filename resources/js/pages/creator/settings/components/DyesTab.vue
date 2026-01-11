<script setup lang="ts">
import { destroy } from '@/actions/App/Http/Controllers/DyeController';
import ListItem from '@/components/ListItem.vue';
import ListItemWrapper from '@/components/ListItemWrapper.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DyeModal from './DyeModal.vue';

interface Dye {
    id: number;
    name: string;
    manufacturer?: string | null;
    notes?: string | null;
    does_bleed: boolean;
    do_like: boolean;
}

interface Props {
    dyes: Dye[];
}

const props = defineProps<Props>();

const { IconList } = useIcon();
const { requireDelete } = useConfirm();

const modalVisible = ref(false);
const selectedDye = ref<Dye | null>(null);

function handleDyeClick(dye: Dye): void {
    selectedDye.value = dye;
    modalVisible.value = true;
}

function handleCreateClick(): void {
    selectedDye.value = null;
    modalVisible.value = true;
}

function handleDelete(dye: Dye, event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${dye.name}?`,
        onAccept: () => {
            router.delete(destroy.url(dye.id), {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['dyes'] });
                },
            });
        },
    });
}

function getListItemProps(dye: Dye) {
    const metadata: string[] = [];
    if (dye.manufacturer) {
        metadata.push(dye.manufacturer);
    }
    if (dye.does_bleed) {
        metadata.push('Bleeds');
    }
    if (!dye.do_like) {
        metadata.push("Don't Like");
    }

    return {
        title: dye.name,
        metadata: metadata.length > 0 ? metadata : undefined,
    };
}

const sortedDyes = computed(() => {
    return [...props.dyes].sort((a, b) => a.name.localeCompare(b.name));
});
</script>

<template>
    <UiCard>
        <template #title>
            <div class="flex w-full items-center justify-between">
                <span>Dyes</span>
                <div class="flex w-full items-center justify-between">
                    <UiButton
                        variant="destructive"
                        size="small"
                        :icon="IconList.Trash"
                        text
                        :disabled="!selectedDye"
                        aria-label="Delete"
                        @click="
                            selectedDye && handleDelete(selectedDye, $event)
                        "
                    />
                    <UiButton
                        size="small"
                        :icon="IconList.Plus"
                        label="Create"
                        @click="handleCreateClick"
                    />
                </div>
            </div>
        </template>

        <template #content>
            <UiDataView
                :value="sortedDyes"
                layout="list"
                data-key="id"
                paginator
                :rows="20"
                empty-message="No dyes found"
            >
                <template #list="{ items }">
                    <ListItemWrapper>
                        <ListItem
                            v-for="dye in items"
                            :key="dye.id"
                            v-bind="getListItemProps(dye)"
                            @click="handleDyeClick(dye)"
                        />
                    </ListItemWrapper>
                </template>
            </UiDataView>
        </template>
    </UiCard>

    <DyeModal
        :visible="modalVisible"
        :dye="selectedDye"
        @update:visible="
            (value) => {
                modalVisible = value;
                if (!value) {
                    selectedDye = null;
                }
            }
        "
    />
</template>

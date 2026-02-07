<script setup lang="ts">
import UiImage from '@/components/ui/UiImage.vue';
import UiTag from '@/components/ui/UiTag.vue';
import { computed } from 'vue';

interface Image {
    src?: string | null;
    alt?: string;
}

interface Tag {
    severity?:
        | 'secondary'
        | 'info'
        | 'success'
        | 'warn'
        | 'danger'
        | 'contrast';
    value: string;
}

interface MetadataItem {
    label: string;
    value: string | number | null | undefined;
}

interface Props {
    title: string;
    image?: Image;
    metadata?: MetadataItem[];
    tag?: Tag;
    description?: string | null;
    clickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    clickable: true,
});

defineEmits<{
    click: [];
}>();

const filteredMetadata = computed(() => {
    return (
        props.metadata?.filter(
            (item) => item.value != null && item.value !== '',
        ) ?? []
    );
});
</script>

<template>
    <div
        :class="[
            'rounded-lg border border-surface-200 bg-surface-0 p-4 transition-all',
            props.clickable
                ? 'cursor-pointer hover:border-primary-500 hover:shadow-md'
                : '',
        ]"
        @click="props.clickable && $emit('click')"
    >
        <div class="flex flex-col gap-2">
            <div v-if="props.tag" class="flex justify-start">
                <UiTag
                    :severity="props.tag.severity"
                    :value="props.tag.value"
                />
            </div>
            <h3 class="text-lg font-semibold text-surface-900">
                {{ props.title }}
            </h3>
            <p
                v-if="props.description"
                class="line-clamp-2 text-sm text-surface-600"
            >
                {{ props.description }}
            </p>
            <UiImage
                v-if="props.image"
                :src="props.image.src"
                :alt="props.image.alt"
                class="aspect-square w-full overflow-hidden rounded"
                image-class="h-full w-full object-cover"
            >
                <template #placeholder>
                    <span class="text-2xl text-surface-400">—</span>
                </template>
            </UiImage>
            <div
                v-if="filteredMetadata.length > 0"
                class="mt-2 flex flex-col gap-1 border-t border-surface-200 pt-2"
            >
                <div
                    v-for="item in filteredMetadata"
                    :key="item.label"
                    class="flex justify-between text-sm"
                >
                    <span class="text-surface-500">{{ item.label }}:</span>
                    <span class="font-medium text-surface-900">
                        {{ item.value }}
                    </span>
                </div>
            </div>
            <div
                v-if="$slots.actions"
                class="mt-2 flex justify-end border-t border-surface-200 pt-2"
                @click.stop
            >
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import UiImage from '@/components/ui/UiImage.vue';
import UiTag from '@/components/ui/UiTag.vue';

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

interface Props {
    title: string;
    image?: Image;
    metadata?: string[];
    tag?: Tag;
    clickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    clickable: true,
});

defineEmits<{
    click: [];
}>();
</script>

<template>
    <div
        :class="[
            'flex items-center gap-4 rounded-lg border border-surface-200 p-2 pr-4 transition-colors',
            props.clickable ? 'cursor-pointer hover:bg-surface-50' : '',
        ]"
        @click="props.clickable && $emit('click')"
    >
        <div v-if="props.image" class="flex-shrink-0">
            <UiImage
                :src="props.image.src"
                :alt="props.image.alt"
                class="h-14 w-14 overflow-hidden rounded"
                image-class="h-full w-full object-cover"
            >
                <template #placeholder>
                    <span class="text-xs text-surface-400">—</span>
                </template>
            </UiImage>
        </div>
        <div class="min-w-0 flex-1">
            <div class="font-semibold text-surface-900">
                {{ props.title }}
            </div>
            <div
                v-if="props.metadata && props.metadata.length > 0"
                class="mt-1 flex gap-4 text-sm text-surface-600"
            >
                <span v-for="(meta, index) in props.metadata" :key="index">
                    {{ meta }}
                </span>
            </div>
        </div>
        <div v-if="props.tag" class="flex-shrink-0">
            <UiTag :severity="props.tag.severity" :value="props.tag.value" />
        </div>
    </div>
</template>

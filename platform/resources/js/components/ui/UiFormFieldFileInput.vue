<script setup lang="ts">
import UiFileUpload from '@/components/ui/UiFileUpload.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import { ref } from 'vue';

interface Props {
    // UiFormField props
    name: string;
    label?: string;
    serverError?: string;
    initialValue?: any;
    resolver?: (params: { value: any }) => {
        errors?: Array<{ message: string; type?: string }>;
    };
    as?: string;
    asChild?: boolean;
    validateOnBlur?: boolean;
    validateOnSubmit?: boolean;
    validateOnValueUpdate?: boolean;
    validateOnMount?: boolean;
    // File input specific props
    accept?: string;
    required?: boolean;
    disabled?: boolean;
    size?: 'small' | 'large';
}

const props = defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});

const emit = defineEmits<{
    change: [event: Event];
}>();

const selectedFile = ref<File | null>(null);
const fileUploadRef = ref<InstanceType<typeof UiFileUpload> | null>(null);

function handleFileSelect(event: { files: File[] }): void {
    if (event.files && event.files.length > 0) {
        selectedFile.value = event.files[0];
        // Create a mock FileList-like object
        const fileList = {
            0: event.files[0],
            length: 1,
            item: (index: number) => (index === 0 ? event.files[0] : null),
            [Symbol.iterator]: function* () {
                yield event.files[0];
            },
        } as FileList;
        
        // Create a mock HTMLInputElement
        const mockInput = {
            files: fileList,
        } as HTMLInputElement;
        
        // Create a custom event object with a non-writable target property
        // We can't modify Event.target, so we create a plain object that mimics Event
        const syntheticEvent = Object.create(Event.prototype, {
            target: {
                value: mockInput,
                enumerable: true,
                configurable: true,
            },
            type: {
                value: 'change',
                enumerable: true,
                configurable: true,
            },
        }) as Event & {
            target: HTMLInputElement;
        };
        
        emit('change', syntheticEvent);
    } else {
        selectedFile.value = null;
    }
}

function clearFiles(): void {
    selectedFile.value = null;
    // Try to clear the file upload component
    if (fileUploadRef.value) {
        const component = fileUploadRef.value as any;
        // PrimeFileUpload has a clear method accessible via $refs or direct access
        if (component.$refs?.fileupload?.clear) {
            component.$refs.fileupload.clear();
        } else if (component.clear) {
            component.clear();
        }
    }
}

defineExpose({
    selectedFile,
    clearFiles,
});
</script>

<template>
    <UiFormField
        :name="name"
        :label="label"
        :serverError="serverError"
        :initialValue="initialValue"
        :resolver="resolver"
        :as="as"
        :asChild="asChild"
        :validateOnBlur="validateOnBlur"
        :validateOnSubmit="validateOnSubmit"
        :validateOnValueUpdate="validateOnValueUpdate"
        :validateOnMount="validateOnMount"
        :required="required"
        :size="size"
    >
        <template #default="{ id }">
            <UiFileUpload
                ref="fileUploadRef"
                v-bind="$attrs"
                :id="id"
                mode="basic"
                :accept="accept"
                :disabled="disabled"
                :custom-upload="true"
                :show-upload-button="false"
                :show-cancel-button="false"
                @select="handleFileSelect"
            />
        </template>
    </UiFormField>
</template>

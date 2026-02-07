<script setup lang="ts">
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { useIcon } from '@/composables/useIcon';

interface ContactFieldConfig {
    name: string;
    label: string;
    type?: string;
    placeholder?: string;
    required?: boolean;
    autocomplete?: string;
    component?: 'input' | 'textarea' | 'phone';
    colSpan?: '1' | '2';
}

interface TestimonialConfig {
    quote: string;
    authorName: string;
    authorRole: string;
    authorImageUrl: string;
    logoUrlLight: string;
    logoUrlDark: string;
}

interface Props {
    variant?: 'simple' | 'withTestimonial';
    title: string;
    description: string;
    fields: ContactFieldConfig[];
    phoneCountryOptions?: string[];
    submitButtonText?: string;
    privacyPolicyLink: string;
    privacyPolicyText?: string;
    privacyCheckboxLabel?: string;
    testimonial?: TestimonialConfig;
    formAction?: string;
    formMethod?: string;
    showDecorativeBackground?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'simple',
    submitButtonText: "Let's talk",
    formMethod: 'POST',
    showDecorativeBackground: true,
});

const { IconList } = useIcon();

function handleSubmit(event: {
    valid: boolean;
    values: Record<string, any>;
    errors: Record<string, any>;
    states: Record<string, any>;
    reset: () => void;
}): void {
    if (!event.valid) {
        return;
    }

    if (props.formAction) {
        const form = document.createElement('form');
        form.method = props.formMethod || 'POST';
        form.action = props.formAction;
        Object.entries(event.values).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = String(value || '');
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<template>
    <!-- Simple variant -->
    <div
        v-if="variant === 'simple'"
        class="isolate px-6 py-24 sm:py-32 lg:px-8"
    >
        <div
            v-if="showDecorativeBackground"
            class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
            aria-hidden="true"
        >
            <div
                class="relative left-1/2 -z-10 aspect-1155/678 w-144.5 max-w-none -translate-x-1/2 rotate-30 bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-40rem)] sm:w-288.75 dark:opacity-20"
                style="
                    clip-path: polygon(
                        74.1% 44.1%,
                        100% 61.6%,
                        97.5% 26.9%,
                        85.5% 0.1%,
                        80.7% 2%,
                        72.5% 32.5%,
                        60.2% 62.4%,
                        52.4% 68.1%,
                        47.5% 58.3%,
                        45.2% 34.5%,
                        27.5% 76.7%,
                        0.1% 64.9%,
                        17.9% 100%,
                        27.6% 76.8%,
                        76.1% 97.7%,
                        74.1% 44.1%
                    );
                "
            ></div>
        </div>
        <div class="mx-auto max-w-2xl text-center">
            <h2
                class="text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl dark:text-white"
            >
                {{ title }}
            </h2>
            <p class="mt-2 text-lg/8 text-gray-600 dark:text-gray-400">
                {{ description }}
            </p>
        </div>
        <div class="mx-auto mt-16 max-w-xl sm:mt-20">
            <UiForm :initialValues="{}" @submit="handleSubmit">
                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    <template v-for="field in fields" :key="field.name">
                        <!-- Phone field with country selector -->
                        <div
                            v-if="field.component === 'phone'"
                            :class="[
                                'sm:col-span-2',
                                field.colSpan === '1' ? 'sm:col-span-1' : '',
                            ]"
                        >
                            <label
                                :for="field.name"
                                class="block text-sm/6 font-semibold text-gray-900 dark:text-white"
                            >
                                {{ field.label }}
                            </label>
                            <div class="mt-2.5">
                                <div
                                    class="flex rounded-md outline-1 -outline-offset-1 outline-gray-300 has-[input:focus-within]:outline-2 has-[input:focus-within]:-outline-offset-2 has-[input:focus-within]:outline-indigo-600 dark:bg-white/5 dark:outline-white/10 dark:has-[input:focus-within]:outline-indigo-500"
                                >
                                    <div
                                        class="grid shrink-0 grid-cols-1 focus-within:relative"
                                    >
                                        <UiFormField
                                            name="country"
                                            :label="undefined"
                                        >
                                            <template
                                                #default="{
                                                    props: fieldProps,
                                                    id,
                                                }"
                                            >
                                                <select
                                                    v-bind="fieldProps"
                                                    :id="id"
                                                    autocomplete="country"
                                                    aria-label="Country"
                                                    class="col-start-1 row-start-1 w-full appearance-none rounded-md py-2 pr-7 pl-3.5 text-base text-gray-500 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-transparent dark:text-gray-400 dark:placeholder:text-gray-500 dark:focus:outline-indigo-500"
                                                >
                                                    <option
                                                        v-for="option in phoneCountryOptions"
                                                        :key="option"
                                                        :value="option"
                                                    >
                                                        {{ option }}
                                                    </option>
                                                </select>
                                            </template>
                                        </UiFormField>
                                        <i
                                            :class="[
                                                IconList.Down,
                                                'pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4 dark:text-gray-400',
                                            ]"
                                            aria-hidden="true"
                                        ></i>
                                    </div>
                                    <UiFormFieldInput
                                        :name="field.name"
                                        type="text"
                                        :placeholder="field.placeholder"
                                        class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none sm:text-sm/6 dark:bg-transparent dark:text-white dark:placeholder:text-gray-500"
                                        :label="undefined"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Textarea fields -->
                        <div
                            v-else-if="field.component === 'textarea'"
                            :class="[
                                'sm:col-span-2',
                                field.colSpan === '1' ? 'sm:col-span-1' : '',
                            ]"
                        >
                            <UiFormField
                                :name="field.name"
                                :label="field.label"
                            >
                                <template #default="{ props: fieldProps, id }">
                                    <textarea
                                        v-bind="fieldProps"
                                        :id="id"
                                        rows="4"
                                        :placeholder="field.placeholder"
                                        :required="field.required"
                                        class="block w-full rounded-md px-3.5 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 dark:bg-white/5 dark:text-white dark:outline-white/10 dark:placeholder:text-gray-500 dark:focus:outline-indigo-500"
                                    />
                                </template>
                            </UiFormField>
                        </div>

                        <!-- Regular input fields -->
                        <div
                            v-else
                            :class="[
                                field.colSpan === '2' ? 'sm:col-span-2' : '',
                            ]"
                        >
                            <UiFormFieldInput
                                :name="field.name"
                                :label="field.label"
                                :type="field.type"
                                :placeholder="field.placeholder"
                                :required="field.required"
                                :autocomplete="field.autocomplete"
                            />
                        </div>
                    </template>

                    <!-- Privacy checkbox (Simple variant) -->
                    <div
                        v-if="privacyCheckboxLabel"
                        class="flex gap-x-4 sm:col-span-2"
                    >
                        <div class="flex h-6 items-center">
                            <div
                                class="group relative inline-flex w-8 shrink-0 rounded-full bg-gray-200 p-px inset-ring inset-ring-gray-900/5 outline-offset-2 outline-indigo-600 transition-colors duration-200 ease-in-out has-checked:bg-indigo-600 has-focus-visible:outline-2 dark:bg-white/5 dark:inset-ring-white/10 dark:outline-indigo-500 dark:has-checked:bg-indigo-500"
                            >
                                <span
                                    class="size-4 rounded-full shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out group-has-checked:translate-x-3.5"
                                ></span>
                                <UiFormField
                                    name="agree-to-policies"
                                    :label="undefined"
                                >
                                    <template
                                        #default="{ props: fieldProps, id }"
                                    >
                                        <input
                                            v-bind="fieldProps"
                                            :id="id"
                                            type="checkbox"
                                            aria-label="Agree to policies"
                                            class="absolute inset-0 size-full appearance-none focus:outline-hidden"
                                        />
                                    </template>
                                </UiFormField>
                            </div>
                        </div>
                        <label
                            class="text-sm/6 text-gray-600 dark:text-gray-400"
                            for="agree-to-policies"
                        >
                            {{ privacyCheckboxLabel }}
                            <UiLink
                                :href="privacyPolicyLink"
                                class="font-semibold whitespace-nowrap text-indigo-600 dark:text-indigo-400"
                                >privacy policy</UiLink
                            >.
                        </label>
                    </div>
                </div>
                <div class="mt-10">
                    <button
                        type="submit"
                        class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                    >
                        {{ submitButtonText }}
                    </button>
                </div>
            </UiForm>
        </div>
    </div>

    <!-- WithTestimonial variant -->
    <div
        v-else
        class="mx-auto max-w-xl px-6 py-24 sm:py-32 lg:max-w-4xl lg:px-8"
    >
        <h2
            class="text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl dark:text-white"
        >
            {{ title }}
        </h2>
        <p class="mt-2 text-lg/8 text-gray-600 dark:text-gray-400">
            {{ description }}
        </p>
        <div class="mt-16 flex flex-col gap-16 sm:gap-y-20 lg:flex-row">
            <div class="lg:flex-auto">
                <UiForm :initialValues="{}" @submit="handleSubmit">
                    <div
                        class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2"
                    >
                        <template v-for="field in fields" :key="field.name">
                            <!-- Textarea fields -->
                            <div
                                v-if="field.component === 'textarea'"
                                :class="[
                                    'sm:col-span-2',
                                    field.colSpan === '1'
                                        ? 'sm:col-span-1'
                                        : '',
                                ]"
                            >
                                <UiFormField
                                    :name="field.name"
                                    :label="field.label"
                                >
                                    <template
                                        #default="{ props: fieldProps, id }"
                                    >
                                        <textarea
                                            v-bind="fieldProps"
                                            :id="id"
                                            rows="4"
                                            :placeholder="field.placeholder"
                                            :required="field.required"
                                            class="block w-full rounded-md px-3.5 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 backdrop-blur-sm placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 dark:bg-white/5 dark:text-white dark:outline-white/10 dark:placeholder:text-gray-500 dark:focus:outline-indigo-500"
                                        />
                                    </template>
                                </UiFormField>
                            </div>

                            <!-- Regular input fields -->
                            <div
                                v-else
                                :class="[
                                    field.colSpan === '2'
                                        ? 'sm:col-span-2'
                                        : '',
                                ]"
                            >
                                <UiFormField
                                    :name="field.name"
                                    :label="field.label"
                                >
                                    <template
                                        #default="{ props: fieldProps, id }"
                                    >
                                        <input
                                            v-bind="fieldProps"
                                            :id="id"
                                            :type="field.type"
                                            :placeholder="field.placeholder"
                                            :required="field.required"
                                            :autocomplete="field.autocomplete"
                                            class="block w-full rounded-md px-3.5 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 backdrop-blur-sm placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 dark:bg-white/5 dark:text-white dark:outline-white/10 dark:placeholder:text-gray-500 dark:focus:outline-indigo-500"
                                        />
                                    </template>
                                </UiFormField>
                            </div>
                        </template>
                    </div>
                    <div class="mt-10">
                        <button
                            type="submit"
                            class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                        >
                            {{ submitButtonText }}
                        </button>
                    </div>
                    <p
                        v-if="privacyPolicyText"
                        class="mt-4 text-sm/6 text-gray-500 dark:text-gray-400"
                    >
                        {{ privacyPolicyText }}
                        <UiLink
                            :href="privacyPolicyLink"
                            class="font-semibold whitespace-nowrap text-indigo-600 dark:text-indigo-400"
                            >privacy policy</UiLink
                        >.
                    </p>
                </UiForm>
            </div>
            <div v-if="testimonial" class="lg:mt-6 lg:w-80 lg:flex-none">
                <img
                    class="h-12 w-auto dark:hidden"
                    :src="testimonial.logoUrlLight"
                    alt=""
                />
                <img
                    class="h-12 w-auto not-dark:hidden"
                    :src="testimonial.logoUrlDark"
                    alt=""
                />
                <figure class="mt-10">
                    <blockquote
                        class="text-lg/8 font-semibold text-gray-900 dark:text-white"
                    >
                        <p>"{{ testimonial.quote }}"</p>
                    </blockquote>
                    <figcaption class="mt-10 flex gap-x-6">
                        <img
                            :src="testimonial.authorImageUrl"
                            alt=""
                            class="size-12 flex-none rounded-full bg-gray-50 dark:bg-gray-800"
                        />
                        <div>
                            <div
                                class="text-base font-semibold text-gray-900 dark:text-white"
                            >
                                {{ testimonial.authorName }}
                            </div>
                            <div
                                class="text-sm/6 text-gray-600 dark:text-gray-400"
                            >
                                {{ testimonial.authorRole }}
                            </div>
                        </div>
                    </figcaption>
                </figure>
            </div>
        </div>
    </div>
</template>

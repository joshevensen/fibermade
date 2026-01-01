<script setup lang="ts">
import { computed } from 'vue';
import { countries } from '@/utils/countries';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';

interface Props {
    showLine2?: boolean;
    showCountry?: boolean;
    errors?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    showLine2: false,
    showCountry: false,
});

const countryOptions = countries.map((country) => ({
    label: country.name,
    value: country.code,
}));

const errorFor = computed(() => (field: string): string | undefined => {
    return props.errors?.[field];
});
</script>

<template>
    <UiFormFieldInput
        name="address_line1"
        label="Address"
        :server-error="errorFor('address_line1')"
    />

    <UiFormFieldInput
        v-if="showLine2"
        name="address_line2"
        label="Line 2"
        :server-error="errorFor('address_line2')"
    />

    <UiFormFieldInput
        name="city"
        label="City"
        :server-error="errorFor('city')"
    />

    <div class="grid grid-cols-2 gap-4">
        <UiFormFieldInput
            name="state_region"
            label="State"
            :server-error="errorFor('state_region')"
        />

        <UiFormFieldInput
            name="postal_code"
            label="Zipcode"
            :server-error="errorFor('postal_code')"
        />
    </div>

    <UiFormFieldSelect
        v-if="showCountry"
        name="country_code"
        label="Country"
        :options="countryOptions"
        :server-error="errorFor('country_code')"
        placeholder="Select a country"
        filter
        filter-placeholder="Search countries"
    />
</template>


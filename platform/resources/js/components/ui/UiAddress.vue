<script setup lang="ts">
import { computed } from 'vue';

interface AddressEntity {
    address_line1?: string | null;
    address_line2?: string | null;
    city?: string | null;
    state_region?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
}

interface Props {
    entity: AddressEntity;
    variant?: 'oneLine' | 'multiLine';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'oneLine',
});

const cityLine = computed((): string | null => {
    const parts: string[] = [];
    if (props.entity.city) parts.push(props.entity.city);
    if (props.entity.state_region) parts.push(props.entity.state_region);
    if (props.entity.postal_code) parts.push(props.entity.postal_code);
    if (parts.length === 0) return null;
    // "Portland, OR 97201"
    if (props.entity.city && props.entity.state_region) {
        const city = props.entity.city;
        const rest = [props.entity.state_region, props.entity.postal_code].filter(Boolean).join(' ');
        return `${city}, ${rest}`;
    }
    return parts.join(' ');
});

const oneLine = computed((): string => {
    return [
        props.entity.address_line1,
        props.entity.address_line2,
        cityLine.value,
        props.entity.country_code,
    ]
        .filter(Boolean)
        .join(', ');
});

const hasAnyAddress = computed((): boolean => oneLine.value.length > 0);
</script>

<template>
    <template v-if="hasAnyAddress">
        <p v-if="variant === 'oneLine'">{{ oneLine }}</p>

        <div v-else>
            <p v-if="entity.address_line1">{{ entity.address_line1 }}</p>
            <p v-if="entity.address_line2">{{ entity.address_line2 }}</p>
            <p v-if="cityLine">{{ cityLine }}</p>
            <p v-if="entity.country_code">{{ entity.country_code }}</p>
        </div>
    </template>
</template>

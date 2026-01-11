<script setup lang="ts">
import ListItem from '@/components/ListItem.vue';
import ListItemWrapper from '@/components/ListItemWrapper.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDataView from '@/components/ui/UiDataView.vue';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Props {
    users: User[];
}

const props = defineProps<Props>();

function getListItemProps(user: User) {
    const metadata: string[] = [];
    if (user.email) {
        metadata.push(user.email);
    }
    if (user.role) {
        metadata.push(user.role.charAt(0).toUpperCase() + user.role.slice(1));
    }

    return {
        title: user.name,
        metadata: metadata.length > 0 ? metadata : undefined,
    };
}
</script>

<template>
    <UiCard>
        <template #title>Account Users</template>
        <template #subtitle>Users associated with this account</template>
        <template #content>
            <UiDataView
                :value="users"
                layout="list"
                data-key="id"
                paginator
                :rows="20"
                empty-message="No users found"
            >
                <template #list="{ items }">
                    <ListItemWrapper>
                        <ListItem
                            v-for="user in items"
                            :key="user.id"
                            v-bind="getListItemProps(user)"
                        />
                    </ListItemWrapper>
                </template>
            </UiDataView>
        </template>
    </UiCard>
</template>

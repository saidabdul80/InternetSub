<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/routes/admin/users';

interface UserRow {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_admin: boolean;
    created_at: string;
}

interface Props {
    users: {
        data: UserRow[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
    {
        title: 'Users',
        href: index().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Users</h1>
                <p class="text-sm text-muted-foreground">
                    Review registered hotspot accounts.
                </p>
            </div>

            <div class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span>{{ user.email ?? 'No email' }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ user.phone ?? 'No phone' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="user.is_admin ? 'default' : 'secondary'">
                                    {{ user.is_admin ? 'Admin' : 'User' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                {{ new Date(user.created_at).toLocaleDateString() }}
                            </td>
                        </tr>

                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No users yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import { Button } from '@/components/ui/button';

const page = usePage();
const appName = page.props.name as string;
const authUser = page.props.auth?.user;
</script>

<template>
    <AppShell variant="header">
        <header class="border-b border-border">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-lg font-semibold text-foreground">
                    {{ appName }}
                </Link>
                <div class="flex items-center gap-3">
                    <Button v-if="!authUser" variant="ghost" as-child>
                        <Link href="/login">Log in</Link>
                    </Button>
                    <Button v-if="!authUser" as-child>
                        <Link href="/register">Create account</Link>
                    </Button>
                    <Button v-if="authUser" variant="ghost" as-child>
                        <Link href="/dashboard">Dashboard</Link>
                    </Button>
                    <Button v-if="authUser" variant="ghost" as-child>
                        <Link href="/logout" method="post" as="button">
                            Log out
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <AppContent variant="header" class="px-6 py-8">
            <slot />
        </AppContent>
    </AppShell>
</template>

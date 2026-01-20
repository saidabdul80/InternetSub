<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import PortalLayout from '@/layouts/PortalLayout.vue';

interface Plan {
    name: string;
    price_kobo: number;
    speed_mbps: number;
    duration_minutes: number;
}

interface Payment {
    id: number;
    reference: string;
    amount_kobo: number;
    currency: string;
    status: string;
    plan: Plan;
}

interface Props {
    payment: Payment;
    authorizationUrl: string | null;
}

const props = defineProps<Props>();

const isLoading = ref(false);
const errorMessage = ref<string | null>(null);

const formatPrice = (priceKobo: number) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(priceKobo / 100);

const pay = async () => {
    if (!props.authorizationUrl) {
        errorMessage.value =
            'Unable to start the payment. Please try again.';
        return;
    }

    errorMessage.value = null;
    isLoading.value = true;

    try {
        window.location.href = props.authorizationUrl;
    } catch (error) {
        errorMessage.value =
            'Unable to start the payment. Please check your connection.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <PortalLayout>
        <Head title="Checkout" />

        <div class="flex flex-col gap-6">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold text-foreground">
                    Complete payment
                </h1>
                <p class="text-sm text-muted-foreground">
                    Pay securely with Paystack to activate your plan instantly.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ payment.plan.name }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <div class="text-2xl font-semibold text-foreground">
                        {{ formatPrice(payment.plan.price_kobo) }}
                    </div>
                    <div>{{ payment.plan.speed_mbps }} Mbps speed</div>
                    <div>{{ payment.plan.duration_minutes }} minutes</div>
                </CardContent>
            </Card>

            <div class="flex flex-wrap gap-3">
                <Button :disabled="isLoading" @click="pay">
                    <Spinner v-if="isLoading" />
                    Pay now
                </Button>
                <Button variant="ghost" as-child>
                    <Link href="/plans">Back to plans</Link>
                </Button>
            </div>

            <p v-if="errorMessage" class="text-sm text-red-600">
                {{ errorMessage }}
            </p>
        </div>
    </PortalLayout>
</template>

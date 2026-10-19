<script lang="ts" setup>
import {Head, Link} from '@inertiajs/vue3';
import {Card, CardContent, CardDescription, CardHeader, CardTitle,} from '@/components/ui/card';
import {dashboard, login} from '@/routes';
</script>

<template>
    <Head title="Welcome to Krishna Engineers">
        <meta
            content="Krishna Engineers — a smarter way to manage production, attendance, and daily operations."
            name="description"
        />
    </Head>

    <main
        class="flex min-h-svh items-center justify-center bg-muted p-6 md:p-10"
    >
        <div class="w-full max-w-md">
            <Card class="rounded-xl shadow-lg">
                <CardHeader class="px-8 pt-8 text-center sm:px-10">
<!--                    <img
                        alt="Krishna Engineers"
                        class="mx-auto mb-4 h-20 w-auto max-w-56 object-contain"
                        src="/logo.svg"
                    />-->

                    <CardTitle class="text-2xl font-bold tracking-tight">
                        Krishna Engineers
                    </CardTitle>

                    <CardDescription
                        class="mx-auto mt-2 max-w-sm text-sm leading-6"
                    >
                        Manage production, track attendance, and streamline
                        daily operations — all in one place.
                    </CardDescription>
                </CardHeader>

                <CardContent class="px-8 pt-4 pb-8 sm:px-10">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex w-full items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="inline-flex w-full items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        Login
                    </Link>

                    <p class="mt-6 text-center text-xs text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} Krishna Engineers.
                        All rights reserved.
                    </p>
                </CardContent>
            </Card>
        </div>
    </main>
</template>

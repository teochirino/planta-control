<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-10 sm:justify-center sm:pt-0"
    >
        <div class="mb-8">
            <Link href="/">
                <ApplicationLogo class="h-32 w-auto fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="w-full overflow-hidden bg-white px-8 py-8 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>

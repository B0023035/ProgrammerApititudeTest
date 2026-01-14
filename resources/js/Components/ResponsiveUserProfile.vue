<!-- resources/js/Components/ResponsiveUserProfile.vue -->
<!-- <template>
    <div class="border-t border-gray-200 pb-1 pt-4">
        <div class="px-4">
            <div class="text-base font-medium text-gray-800">
                {{ user.name }}
            </div>
            <div class="text-sm font-medium text-gray-500">
                {{ user.email }}
            </div>
        </div>

        <div class="mt-3 space-y-1">
            <ResponsiveNavLink :href="route('profile.edit')">
                Profile
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('logout')"
                method="post"
                as="button"
            >
                Log Out
            </ResponsiveNavLink>
        </div>
    </div>
</template>

<script setup>
defineProps({
    user: Object
});
</script> -->
<template>
    <!-- <Dropdown align="right" width="48">
        <template #trigger>
            <span class="inline-flex rounded-md cursor-pointer">
                {{ user.name }}
            </span>
        </template>

        <template #content>
            <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>

            <form method="POST" :action="route('logout')">
                <button
                    type="submit"
                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                >
                    Log Out
                </button>
            </form>
        </template>
    </Dropdown> -->
    <Dropdown align="right" width="48">
        <template #trigger>
            <button
                type="button"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
            >
                {{ $page.props.auth.user.name }}
                <svg
                    class="ms-2 h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>
        </template>

        <template #content>
            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
            <DropdownLink :href="route('my-results')">リザルト</DropdownLink>

            <button
                type="button"
                class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                @click="logout"
            >
                Log Out
            </button>
        </template>
    </Dropdown>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
defineProps<{ user: any }>();
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";

const logout = () => {
    router.post(
        route("logout"),
        {},
        {
            preserveState: false,
            preserveScroll: false,
        }
    );
};
</script>

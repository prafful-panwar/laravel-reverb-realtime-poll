<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ref } from "vue";

defineProps({
    polls: {
        type: Object,
        required: true,
    },
});

const copiedPollId = ref(null);

const copyUrl = (id) => {
    const url = route("polls.show", id);

    // Create absolute URL if route() returns relative
    const absoluteUrl = url.startsWith("http")
        ? url
        : window.location.origin + url;

    // Use modern clipboard API if secure (HTTPS or localhost)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard
            .writeText(absoluteUrl)
            .then(() => {
                window.dispatchEvent(
                    new CustomEvent("toast", { detail: absoluteUrl }),
                );
                copiedPollId.value = id;
                setTimeout(() => {
                    if (copiedPollId.value === id) {
                        copiedPollId.value = null;
                    }
                }, 2000);
            })
            .catch((err) => {
                console.error("Failed to copy text: ", err);
            });
    } else {
        // Fallback for local HTTP development (e.g. http://realtime-poll.test)
        const textArea = document.createElement("textarea");
        textArea.value = absoluteUrl;
        textArea.style.position = "absolute";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand("copy");
            window.dispatchEvent(
                new CustomEvent("toast", { detail: absoluteUrl }),
            );
            copiedPollId.value = id;
            setTimeout(() => {
                if (copiedPollId.value === id) {
                    copiedPollId.value = null;
                }
            }, 2000);
        } catch (err) {
            console.error("Fallback: Oops, unable to copy", err);
        }

        document.body.removeChild(textArea);
    }
};
</script>

<template>
    <Head title="My Polls" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    My Polls
                </h2>
                <Link
                    :href="route('admin.polls.create')"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    Create Poll
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div
                            v-if="polls.data.length === 0"
                            class="text-center py-10"
                        >
                            <p class="text-gray-500">
                                You haven't created any polls yet.
                            </p>
                        </div>
                        <ul v-else role="list" class="divide-y divide-gray-100">
                            <li
                                v-for="poll in polls.data"
                                :key="poll.id"
                                class="flex items-center justify-between gap-x-6 py-5"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-start gap-x-3">
                                        <p
                                            class="text-sm/6 font-semibold text-gray-900"
                                        >
                                            {{ poll.title }}
                                        </p>
                                    </div>
                                    <div
                                        class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500"
                                    >
                                        <p class="truncate">
                                            {{ poll.description }}
                                        </p>
                                        <svg
                                            viewBox="0 0 2 2"
                                            class="size-0.5 fill-current"
                                        >
                                            <circle cx="1" cy="1" r="1" />
                                        </svg>
                                        <p class="whitespace-nowrap">
                                            {{ poll.options_count }} Options
                                        </p>
                                        <svg
                                            viewBox="0 0 2 2"
                                            class="size-0.5 fill-current"
                                        >
                                            <circle cx="1" cy="1" r="1" />
                                        </svg>
                                        <p class="whitespace-nowrap">
                                            {{
                                                poll.options_sum_votes_count ||
                                                0
                                            }}
                                            Total Votes
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-none items-center gap-x-4"
                                >
                                    <button
                                        @click.prevent="copyUrl(poll.slug)"
                                        class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none"
                                    >
                                        {{
                                            copiedPollId === poll.slug
                                                ? "Copied!"
                                                : "Copy URL"
                                        }}
                                        <span class="sr-only"
                                            >, {{ poll.title }}</span
                                        >
                                    </button>
                                    <a
                                        :href="
                                            route('admin.polls.show', poll.slug)
                                        "
                                        class="rounded-md bg-indigo-50 px-2.5 py-1.5 text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-indigo-600/20 hover:bg-indigo-100"
                                    >
                                        View Live Results<span class="sr-only"
                                            >, {{ poll.title }}</span
                                        >
                                    </a>
                                    <a
                                        :href="route('polls.show', poll.slug)"
                                        target="_blank"
                                        class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                    >
                                        Public Page<span class="sr-only"
                                            >, {{ poll.title }}</span
                                        >
                                    </a>
                                </div>
                            </li>
                        </ul>

                        <!-- Minimal Pagination -->
                        <div class="mt-4" v-if="polls.last_page > 1">
                            <div class="flex justify-between">
                                <Link
                                    v-if="polls.prev_page_url"
                                    :href="polls.prev_page_url"
                                    class="text-indigo-600 hover:text-indigo-900"
                                    >&laquo; Previous</Link
                                >
                                <span v-else class="text-gray-400"
                                    >&laquo; Previous</span
                                >

                                <Link
                                    v-if="polls.next_page_url"
                                    :href="polls.next_page_url"
                                    class="text-indigo-600 hover:text-indigo-900"
                                    >Next &raquo;</Link
                                >
                                <span v-else class="text-gray-400"
                                    >Next &raquo;</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref } from "vue";

const props = defineProps({
    poll: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const copied = ref(false);
const options = ref(props.poll.options.map((o) => ({ ...o })));

const copyUrl = (slug) => {
    // If called from the template without args, fallback to props
    const targetSlug = typeof slug === "string" ? slug : props.poll.slug;
    const url = route("polls.show", targetSlug);
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
                copied.value = true;
                setTimeout(() => {
                    copied.value = false;
                }, 2000);
            })
            .catch((err) => console.error("Failed to copy text: ", err));
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
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } catch (err) {
            console.error("Fallback: Oops, unable to copy", err);
        }

        document.body.removeChild(textArea);
    }
};

const totalVotes = computed(() => {
    return options.value.reduce((sum, option) => sum + option.votes_count, 0);
});

const getPercentage = (count) => {
    if (totalVotes.value === 0) return 0;
    return Math.round((count / totalVotes.value) * 100);
};

// Listen to the PrivateChannel for this Admin's incoming votes!
onMounted(() => {
    window.Echo.private(`admin.polls.${user.value.id}`).listen(
        "VoteSubmitted",
        (e) => {
            // Check if the incoming event belongs to THIS specific poll
            if (e.poll_id === props.poll.id) {
                const option = options.value.find((o) => o.id === e.option_id);
                if (option) option.votes_count = e.votes_count;
            }
        },
    );
});

onUnmounted(() => {
    window.Echo.leave(`admin.polls.${user.value.id}`);
});
</script>

<template>
    <Head :title="`Results: ${poll.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Live Results: {{ poll.title }}
                </h2>
                <span
                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                >
                    <span class="mr-1.5 flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex rounded-full h-2 w-2 bg-green-500"
                        ></span>
                    </span>
                    Listening (Live)
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 border-b border-gray-200">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900 mb-2"
                        >
                            Overview
                        </h3>
                        <p
                            v-if="poll.description"
                            class="mt-1 text-sm text-gray-500 mb-4"
                        >
                            {{ poll.description }}
                        </p>

                        <div
                            class="bg-gray-50 rounded-lg p-4 flex items-center justify-between"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Votes Cast
                                </p>
                                <p
                                    class="mt-1 text-3xl font-semibold text-gray-900"
                                >
                                    {{ totalVotes }}
                                </p>
                            </div>
                            <!-- Embed link to the public facing vote page here for convenience -->
                            <div class="text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        @click.prevent="copyUrl(poll.slug)"
                                        class="rounded bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none"
                                    >
                                        {{
                                            copied
                                                ? "Copied!"
                                                : "Copy Public URL"
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900 mb-6"
                        >
                            Live Standings
                        </h3>
                        <div class="space-y-6">
                            <div
                                v-for="option in options"
                                :key="option.id"
                                class="relative"
                            >
                                <div
                                    class="flex items-center justify-between text-sm font-medium mb-2"
                                >
                                    <span class="text-gray-900">{{
                                        option.text
                                    }}</span>
                                    <span class="text-gray-500">
                                        {{ option.votes_count }}
                                        {{
                                            option.votes_count === 1
                                                ? "vote"
                                                : "votes"
                                        }}
                                        ({{
                                            getPercentage(option.votes_count)
                                        }}%)
                                    </span>
                                </div>
                                <div
                                    class="w-full bg-gray-200 rounded-full h-4 overflow-hidden"
                                >
                                    <div
                                        class="bg-indigo-600 h-4 transition-all duration-700 ease-out"
                                        :style="`width: ${getPercentage(option.votes_count)}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

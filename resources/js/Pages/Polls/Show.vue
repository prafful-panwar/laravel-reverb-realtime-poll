<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    poll: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const errors = computed(() => page.props.errors);

// Track local completion natively bridging the gap between XHR pushes
const voteCompleted = ref(false);

const form = useForm({
    option_id: null,
});

const submitVote = () => {
    if (!form.option_id) return;

    form.post(
        route("polls.vote", {
            poll: props.poll.slug,
            optionId: form.option_id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                const selectedOption = props.poll.options.find(
                    (opt) => opt.id === form.option_id,
                );
                if (selectedOption) {
                    selectedOption.votes_count += 1;
                }

                voteCompleted.value = true;
            },
        },
    );
};

const totalVotes = computed(() => {
    return props.poll.options.reduce(
        (sum, option) => sum + option.votes_count,
        0,
    );
});

const getPercentage = (count) => {
    if (totalVotes.value === 0) return 0;
    return Math.round((count / totalVotes.value) * 100);
};

// Calculate whether the user has successfully voted
const hasVoted = computed(() => {
    return voteCompleted.value || flash.value?.success || errors.value?.vote;
});
</script>

<template>
    <Head :title="poll.title" />

    <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Success/Error Messages -->
            <div
                v-if="flash?.success"
                class="mb-4 bg-green-50 border-l-4 border-green-400 p-4"
            >
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            {{ flash.success }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="errors?.vote"
                class="mb-4 bg-red-50 border-l-4 border-red-400 p-4"
            >
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            {{ errors.vote }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">
                        {{ poll.title }}
                    </h1>
                    <p
                        v-if="poll.description"
                        class="mt-2 max-w-2xl text-sm text-gray-500"
                    >
                        {{ poll.description }}
                    </p>
                    <p class="mt-2 text-sm text-gray-400">
                        Total Votes: {{ totalVotes }}
                    </p>
                </div>

                <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                    <form @submit.prevent="submitVote" class="p-4 sm:p-6">
                        <div class="space-y-4">
                            <label
                                v-for="option in poll.options"
                                :key="option.id"
                                class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none"
                                :class="[
                                    form.option_id === option.id
                                        ? 'border-indigo-600 ring-2 ring-indigo-600'
                                        : 'border-gray-300',
                                    hasVoted
                                        ? 'cursor-default'
                                        : 'hover:border-indigo-400',
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="poll_option"
                                    :value="option.id"
                                    v-model="form.option_id"
                                    class="sr-only"
                                    :disabled="hasVoted || form.processing"
                                />
                                <span class="flex flex-1">
                                    <span
                                        class="flex flex-col w-full relative z-10"
                                    >
                                        <span
                                            class="block text-sm font-medium text-gray-900"
                                            >{{ option.text }}</span
                                        >

                                        <!-- Progress Bar (Visible after voting) -->
                                        <div v-if="hasVoted" class="mt-2">
                                            <div
                                                class="flex items-center justify-between text-xs text-gray-500 mb-1"
                                            >
                                                <span
                                                    >{{ option.votes_count }}
                                                    {{
                                                        option.votes_count === 1
                                                            ? "vote"
                                                            : "votes"
                                                    }}</span
                                                >
                                                <span
                                                    >{{
                                                        getPercentage(
                                                            option.votes_count,
                                                        )
                                                    }}%</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-gray-200 rounded-full h-2"
                                            >
                                                <div
                                                    class="bg-indigo-600 h-2 rounded-full transition-all duration-500 ease-out"
                                                    :style="`width: ${getPercentage(option.votes_count)}%`"
                                                ></div>
                                            </div>
                                        </div>
                                    </span>
                                </span>
                                <!-- Check circle for selected state -->
                                <svg
                                    v-if="form.option_id === option.id"
                                    class="h-5 w-5 text-indigo-600 relative z-10"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </label>
                        </div>

                        <div class="mt-6 flex items-center justify-end">
                            <button
                                v-if="!hasVoted"
                                type="submit"
                                class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                :disabled="!form.option_id || form.processing"
                            >
                                <span v-if="form.processing"
                                    >Submitting...</span
                                >
                                <span v-else>Submit Vote</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

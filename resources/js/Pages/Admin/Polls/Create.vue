<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const form = useForm({
    title: "",
    description: "",
    options: ["", ""], // Start with two empty options
});

const addOption = () => {
    form.options.push("");
};

const removeOption = (index) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
    }
};

const submit = () => {
    form.post(route("admin.polls.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Poll" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Create Poll
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel
                                    for="title"
                                    value="Poll Question / Title"
                                />
                                <TextInput
                                    id="title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.title"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.title"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="description"
                                    value="Description (Optional)"
                                />
                                <textarea
                                    id="description"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.description"
                                    rows="3"
                                ></textarea>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.description"
                                />
                            </div>

                            <div class="border-t pt-4">
                                <h3
                                    class="text-lg font-medium text-gray-900 mb-4"
                                >
                                    Poll Options
                                </h3>

                                <div class="space-y-3">
                                    <div
                                        v-for="(option, index) in form.options"
                                        :key="index"
                                        class="flex items-center gap-3"
                                    >
                                        <div class="flex-grow">
                                            <TextInput
                                                type="text"
                                                class="block w-full"
                                                v-model="form.options[index]"
                                                :placeholder="`Option ${index + 1}`"
                                                required
                                            />
                                            <InputError
                                                class="mt-1"
                                                :message="
                                                    form.errors[
                                                        `options.${index}`
                                                    ]
                                                "
                                            />
                                        </div>
                                        <button
                                            v-if="form.options.length > 2"
                                            type="button"
                                            @click="removeOption(index)"
                                            class="inline-flex items-center justify-center p-2 rounded-md text-red-500 hover:bg-red-50 focus:outline-none"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.options"
                                />

                                <button
                                    type="button"
                                    @click="addOption"
                                    class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center gap-1"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="size-4"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 4.5v15m7.5-7.5h-15"
                                        />
                                    </svg>
                                    Add Option
                                </button>
                            </div>

                            <div
                                class="flex items-center justify-end border-t pt-4"
                            >
                                <PrimaryButton
                                    class="ms-4"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Create Poll
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

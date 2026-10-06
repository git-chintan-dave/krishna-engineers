<script setup lang="ts">
import {Head, useForm} from '@inertiajs/vue3';
import {CheckCircle2, FileSpreadsheet, Loader2, UploadCloud, X,} from '@lucide/vue';
import {computed, ref} from 'vue';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import {Card, CardContent} from '@/components/ui/card';
import {Button} from '@/components/ui/button';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {attendancePage} from '@/config/attendances';
import {index as attendances} from '@/routes/attendances';
import {store as submitImport} from '@/routes/attendances/import';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        acceptedFiles?: string;
        maxFileSizeText?: string;
        submitButtonText?: string;
    }>(),
    {
        title: 'Import Attendance',
        description:
            'Upload the attendance report to import employee attendance records.',
        acceptedFiles: '.xlsx,.xls,.csv',
        maxFileSizeText: 'Maximum 10MB',
        submitButtonText: 'Import Attendance',
    },
);

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: attendancePage.title,
            resourceUrl: attendances().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    file: null as File | null,
});

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const selectedFileName = computed(
    () => form.file?.name ?? '',
);

const acceptedFileLabels = computed(() =>
    props.acceptedFiles
        .split(',')
        .map((extension) =>
            extension.trim().replace('.', '').toUpperCase(),
        )
        .join(', '),
);

/*
|--------------------------------------------------------------------------
| File Selection
|--------------------------------------------------------------------------
*/

const openFilePicker = () => {
    if (!form.processing) {
        fileInput.value?.click();
    }
};

const setFile = (file: File | null) => {
    if (!file || form.processing) {
        return;
    }

    form.file = file;
    form.clearErrors('file');
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    setFile(target.files?.[0] ?? null);
};

const onDrop = (event: DragEvent) => {
    isDragging.value = false;

    if (form.processing) {
        return;
    }

    setFile(event.dataTransfer?.files?.[0] ?? null);
};

const removeFile = () => {
    if (form.processing) {
        return;
    }

    form.reset('file');

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const submit = () => {
    if (!form.file || form.processing) {
        return;
    }

    form.post(submitImport().url, {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="props.title" />

    <div class="space-y-6 p-6">
        <!--
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        -->

        <AppPageHeader
            :title="props.title"
            :description="props.description"
        />

        <!--
        |--------------------------------------------------------------------------
        | Import Card
        |--------------------------------------------------------------------------
        -->

        <Card>
            <CardContent class="space-y-6 p-6">
                <form
                    class="space-y-6"
                    @submit.prevent="submit"
                >
                    <!--
                    |--------------------------------------------------------------------------
                    | Upload Area
                    |--------------------------------------------------------------------------
                    -->

                    <div
                        :class="[
                            isDragging
                                ? 'border-primary bg-primary/5'
                                : 'border-border bg-muted/20 hover:border-primary/40',
                            form.errors.file
                                ? 'border-destructive'
                                : '',
                        ]"
                        class="relative flex min-h-72 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 transition-colors"
                        @click="openFilePicker"
                        @dragenter.prevent="
                            !form.processing &&
                            (isDragging = true)
                        "
                        @dragover.prevent="
                            !form.processing &&
                            (isDragging = true)
                        "
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                    >
                        <input
                            ref="fileInput"
                            :accept="props.acceptedFiles"
                            class="hidden"
                            type="file"
                            @change="onFileChange"
                        />

                        <!-- Empty State -->

                        <template v-if="!form.file">
                            <div
                                class="mb-5 rounded-xl border bg-background p-4 shadow-sm"
                            >
                                <UploadCloud
                                    class="size-10 text-primary"
                                    aria-hidden="true"
                                />
                            </div>

                            <h3 class="text-base font-semibold">
                                Upload attendance report
                            </h3>

                            <p
                                class="mt-2 text-center text-sm text-muted-foreground"
                            >
                                Drag and drop your attendance spreadsheet
                                here, or click to browse.
                            </p>

                            <p
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                {{ acceptedFileLabels }}
                                &bull;
                                {{ props.maxFileSizeText }}
                            </p>
                        </template>

                        <!-- Selected File -->

                        <template v-else>
                            <div class="flex flex-col items-center">
                                <div
                                    class="mb-4 rounded-full bg-emerald-100 p-3 dark:bg-emerald-950"
                                >
                                    <CheckCircle2
                                        class="size-8 text-emerald-600"
                                        aria-hidden="true"
                                    />
                                </div>

                                <div
                                    class="flex max-w-full items-center gap-2"
                                >
                                    <FileSpreadsheet
                                        class="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />

                                    <span
                                        class="max-w-xs truncate text-sm font-medium sm:max-w-md"
                                    >
                                        {{ selectedFileName }}
                                    </span>
                                </div>

                                <Button
                                    class="mt-4"
                                    size="sm"
                                    type="button"
                                    variant="ghost"
                                    :disabled="form.processing"
                                    @click.stop="removeFile"
                                >
                                    <X
                                        class="size-4"
                                        aria-hidden="true"
                                    />

                                    <span>Remove file</span>
                                </Button>
                            </div>
                        </template>
                    </div>

                    <!--
                    |--------------------------------------------------------------------------
                    | Validation Error
                    |--------------------------------------------------------------------------
                    -->

                    <p
                        v-if="form.errors.file"
                        class="text-center text-sm font-medium text-destructive"
                        role="alert"
                    >
                        {{ form.errors.file }}
                    </p>

                    <!--
                    |--------------------------------------------------------------------------
                    | Import Information
                    |--------------------------------------------------------------------------
                    -->

                    <div
                        class="rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground"
                    >
                        <p class="font-medium text-foreground">
                            Before importing
                        </p>

                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li>
                                Use the attendance report generated by the
                                attendance system.
                            </li>

                            <li>
                                Make sure the employee code matches an
                                employee in the system.
                            </li>

                            <li>
                                Each employee and attendance date can be
                                imported only once.
                            </li>

                            <li>
                                Importing an attendance that already exists
                                will show an error.
                            </li>

                            <li>
                                Attendance belonging to a paid salary period
                                cannot be changed.
                            </li>
                        </ul>
                    </div>

                    <!--
                    |--------------------------------------------------------------------------
                    | Submit
                    |--------------------------------------------------------------------------
                    -->

                    <Button
                        :disabled="!form.file || form.processing"
                        class="h-10 w-full cursor-pointer"
                        type="submit"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />

                        <span>
                            {{
                                form.processing
                                    ? 'Importing...'
                                    : props.submitButtonText
                            }}
                        </span>
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

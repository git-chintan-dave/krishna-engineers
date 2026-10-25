<script lang="ts" setup>
import {useForm} from '@inertiajs/vue3';
import {CheckCircle2, Download, FileSpreadsheet, Loader2, UploadCloud, X,} from '@lucide/vue';
import {computed, ref} from 'vue';

import {Button} from '@/components/ui/button';
import {Card, CardContent} from '@/components/ui/card';

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
        uploadText?: string;
        uploadDescription?: string;
        sampleFile?: string;
        sampleFileName?: string;
        informationTitle?: string;
        informationItems?: string[];
        submitUrl: string;
    }>(),
    {
        title: 'Import',
        description: 'Upload a file to import records.',
        acceptedFiles: '.xlsx,.xls,.csv',
        maxFileSizeText: 'Maximum 10MB',
        submitButtonText: 'Import',
        uploadText: 'Upload file',
        uploadDescription:
            'Drag and drop your spreadsheet here, or click to browse.',
        sampleFile: undefined,
        sampleFileName: 'samples.xlsx',
        informationTitle: 'Before importing',
        informationItems: () => [],
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    success: [];
}>();

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

const selectedFileName = computed(() => form.file?.name ?? '');

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

    form.post(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
        },
    });
};
</script>

<template>
    <Card>
        <CardContent class="space-y-6 p-6">
            <form class="space-y-6" @submit.prevent="submit">
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
                        form.errors.file ? 'border-destructive' : '',
                    ]"
                    class="relative flex min-h-72 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 transition-colors"
                    @click="openFilePicker"
                    @dragenter.prevent="
                        !form.processing && (isDragging = true)
                    "
                    @dragover.prevent="
                        !form.processing && (isDragging = true)
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
                                aria-hidden="true"
                                class="size-10 text-primary"
                            />
                        </div>

                        <h3 class="text-base font-semibold">
                            {{ props.uploadText }}
                        </h3>

                        <p
                            class="mt-2 text-center text-sm text-muted-foreground"
                        >
                            {{ props.uploadDescription }}
                        </p>

                        <p class="mt-2 text-xs text-muted-foreground">
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
                                    aria-hidden="true"
                                    class="size-8 text-emerald-600"
                                />
                            </div>

                            <div class="flex max-w-full items-center gap-2">
                                <FileSpreadsheet
                                    aria-hidden="true"
                                    class="size-4 shrink-0 text-muted-foreground"
                                />

                                <span
                                    class="max-w-xs truncate text-sm font-medium sm:max-w-md"
                                >
                                    {{ selectedFileName }}
                                </span>
                            </div>

                            <Button
                                :disabled="form.processing"
                                class="mt-4"
                                size="sm"
                                type="button"
                                variant="ghost"
                                @click.stop="removeFile"
                            >
                                <X aria-hidden="true" class="size-4" />

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
                | Sample File
                |--------------------------------------------------------------------------
                -->

                <div
                    v-if="props.sampleFile"
                    class="flex items-center justify-between gap-4 rounded-lg border bg-muted/30 p-4"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium">
                            Need a sample file?
                        </p>

                        <p class="mt-1 truncate text-xs text-muted-foreground">
                            {{ props.sampleFileName }}
                        </p>
                    </div>

                    <Button
                        as="a"
                        :href="props.sampleFile"
                        :download="props.sampleFileName"
                        class="shrink-0"
                        size="sm"
                        variant="outline"
                    >
                        <Download aria-hidden="true" class="size-4" />
                        <span>Download Sample</span>
                    </Button>
                </div>

                <!--
                |--------------------------------------------------------------------------
                | Import Information
                |--------------------------------------------------------------------------
                -->

                <div
                    v-if="props.informationItems.length"
                    class="rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground"
                >
                    <p class="font-medium text-foreground">
                        {{ props.informationTitle }}
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li
                            v-for="item in props.informationItems"
                            :key="item"
                        >
                            {{ item }}
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
                        aria-hidden="true"
                        class="size-4 animate-spin"
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
</template>

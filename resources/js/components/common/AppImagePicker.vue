<script lang="ts" setup>
import {computed, onBeforeUnmount, ref} from 'vue';
import {Image, Trash2, Upload, X} from '@lucide/vue';

// Props
const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        currentImage?: string | null;
        accept?: string;
        maxSize?: number;
        disabled?: boolean;
        error?: string;
        placeholder?: string;
        removeLabel?: string;
    }>(),
    {
        modelValue: null,
        currentImage: null,
        accept: 'image/jpeg,image/png,image/webp',
        maxSize: 5,
        disabled: false,
        error: '',
        placeholder: 'Choose an image',
        removeLabel: 'Remove image',
    },
);

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: File | null];
    change: [value: File | null];
    remove: [];
}>();

// State
const inputRef = ref<HTMLInputElement | null>(null);
const localPreview = ref<string | null>(null);
const validationError = ref('');
const imageRemoved = ref(false);

// Computed
const previewUrl = computed(() => {
    if (localPreview.value) {
        return localPreview.value;
    }

    if (imageRemoved.value) {
        return null;
    }

    return props.currentImage ?? null;
});

const hasImage = computed(() => Boolean(previewUrl.value));

const displayError = computed(() => validationError.value || props.error);

// Actions
const openPicker = () => {
    if (props.disabled) {
        return;
    }

    inputRef.value?.click();
};

const revokePreview = () => {
    if (!localPreview.value) {
        return;
    }

    URL.revokeObjectURL(localPreview.value);
    localPreview.value = null;
};

const validateFile = (file: File): string | null => {
    if (!file.type.startsWith('image/')) {
        return 'Please select a valid image file.';
    }

    const maxBytes = props.maxSize * 1024 * 1024;

    if (file.size > maxBytes) {
        return `Image size must not exceed ${props.maxSize} MB.`;
    }

    return null;
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    validationError.value = '';

    if (!file) {
        return;
    }

    const error = validateFile(file);

    if (error) {
        validationError.value = error;
        target.value = '';

        emit('update:modelValue', null);
        emit('change', null);

        return;
    }

    revokePreview();

    localPreview.value = URL.createObjectURL(file);
    imageRemoved.value = false;

    emit('update:modelValue', file);
    emit('change', file);
};

const removeImage = () => {
    if (props.disabled) {
        return;
    }

    revokePreview();

    if (inputRef.value) {
        inputRef.value.value = '';
    }

    imageRemoved.value = true;
    validationError.value = '';

    emit('update:modelValue', null);
    emit('change', null);
    emit('remove');
};

// Lifecycle
onBeforeUnmount(() => {
    revokePreview();
});
</script>

<template>
    <div class="min-w-0 space-y-3">
        <input
            ref="inputRef"
            :accept="accept"
            :disabled="disabled"
            class="hidden"
            type="file"
            @change="handleFileChange"
        />

        <!-- Image preview -->
        <div
            v-if="hasImage"
            class="relative flex h-36 w-full max-w-52 items-center justify-center overflow-hidden rounded-md border bg-muted/20 sm:w-52"
        >
            <img
                :src="previewUrl ?? undefined"
                alt="Selected image"
                class="h-full w-full object-contain p-2"
                loading="lazy"
            />

            <button
                v-if="!disabled"
                :aria-label="removeLabel"
                :title="removeLabel"
                class="absolute top-2 right-2 inline-flex size-8 items-center justify-center rounded-md border bg-background/95 shadow-sm transition hover:bg-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                type="button"
                @click="removeImage"
            >
                <X class="size-4"/>
            </button>
        </div>

        <!-- Empty state -->
        <button
            v-if="!hasImage"
            :disabled="disabled"
            class="flex h-36 w-full max-w-52 flex-col items-center justify-center gap-2 rounded-md border border-dashed bg-muted/20 px-4 text-center transition hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 sm:w-52"
            type="button"
            @click="openPicker"
        >
            <div
                class="flex size-10 items-center justify-center rounded-md border bg-background"
            >
                <Image
                    aria-hidden="true"
                    class="size-5 text-muted-foreground"
                />
            </div>

            <div class="space-y-1">
                <p class="text-sm font-medium">
                    {{ placeholder }}
                </p>

                <p class="text-xs text-muted-foreground">PNG, JPG or WebP</p>

                <p class="text-xs text-muted-foreground">
                    Max {{ maxSize }} MB
                </p>
            </div>
        </button>

        <!-- Image actions -->
        <div v-else-if="!disabled" class="flex flex-wrap items-center gap-2">
            <button
                class="inline-flex min-h-9 items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                type="button"
                @click="openPicker"
            >
                <Upload class="size-4"/>
                Change image
            </button>

            <button
                class="inline-flex min-h-9 items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium text-destructive transition hover:bg-destructive/10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                type="button"
                @click="removeImage"
            >
                <Trash2 class="size-4"/>
                {{ removeLabel }}
            </button>
        </div>

        <!-- Error -->
        <p v-if="displayError" class="text-sm text-destructive">
            {{ displayError }}
        </p>
    </div>
</template>

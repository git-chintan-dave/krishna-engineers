<script setup lang="ts">
import { computed, ref } from 'vue';
import {
    Image,
    Trash2,
    Upload,
    X,
} from '@lucide/vue';

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

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
    change: [value: File | null];
    remove: [];
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const localPreview = ref<string | null>(null);
const validationError = ref('');
const imageRemoved = ref(false);

const previewUrl = computed(() => {
    if (localPreview.value) {
        return localPreview.value;
    }

    if (imageRemoved.value) {
        return null;
    }

    return props.currentImage ?? null;
});

const hasImage = computed(() =>
    Boolean(previewUrl.value),
);

const openPicker = () => {
    if (props.disabled) {
        return;
    }

    inputRef.value?.click();
};

const validateFile = (
    file: File,
): string | null => {
    if (!file.type.startsWith('image/')) {
        return 'Please select a valid image file.';
    }

    const maxBytes =
        props.maxSize * 1024 * 1024;

    if (file.size > maxBytes) {
        return `Image size must not exceed ${props.maxSize} MB.`;
    }

    return null;
};

const handleFileChange = (
    event: Event,
) => {
    const target =
        event.target as HTMLInputElement;

    const file =
        target.files?.[0] ?? null;

    validationError.value = '';

    if (!file) {
        return;
    }

    const error = validateFile(file);

    if (error) {
        validationError.value = error;
        target.value = '';

        emit(
            'update:modelValue',
            null,
        );

        emit('change', null);

        return;
    }

    if (localPreview.value) {
        URL.revokeObjectURL(
            localPreview.value,
        );
    }

    localPreview.value =
        URL.createObjectURL(file);

    imageRemoved.value = false;

    emit(
        'update:modelValue',
        file,
    );

    emit('change', file);
};

const removeImage = () => {
    if (props.disabled) {
        return;
    }

    if (localPreview.value) {
        URL.revokeObjectURL(
            localPreview.value,
        );

        localPreview.value = null;
    }

    if (inputRef.value) {
        inputRef.value.value = '';
    }

    imageRemoved.value = true;

    emit(
        'update:modelValue',
        null,
    );

    emit('change', null);
    emit('remove');
};
</script>

<template>
    <div class="space-y-3">
        <input
            ref="inputRef"
            type="file"
            :accept="accept"
            :disabled="disabled"
            class="hidden"
            @change="handleFileChange"
        />

        <!-- Image thumbnail -->
        <div
            v-if="hasImage"
            class="relative flex h-36 w-48 items-center justify-center overflow-hidden rounded-lg border bg-muted/20"
        >
            <img
                :src="previewUrl ?? undefined"
                alt="Selected image"
                class="h-full w-full object-contain p-2"
            />

            <button
                v-if="!disabled"
                type="button"
                class="absolute right-2 top-2 inline-flex size-7 items-center justify-center rounded-md border bg-background/90 shadow-sm transition hover:bg-background"
                :aria-label="removeLabel"
                :title="removeLabel"
                @click="removeImage"
            >
                <X class="size-4" />
            </button>
        </div>

        <!-- Empty state -->
        <button
            v-if="!hasImage"
            type="button"
            class="flex h-36 w-48 flex-col items-center justify-center gap-2 rounded-lg border border-dashed bg-muted/20 px-4 text-center transition hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="disabled"
            @click="openPicker"
        >
            <div
                class="flex size-10 items-center justify-center rounded-full border bg-background"
            >
                <Image
                    class="size-5 text-muted-foreground"
                />
            </div>

            <div class="space-y-1">
                <p
                    class="text-sm font-medium"
                >
                    {{ placeholder }}
                </p>

                <p
                    class="text-xs text-muted-foreground"
                >
                    PNG, JPG or WebP
                </p>

                <p
                    class="text-xs text-muted-foreground"
                >
                    Max {{ maxSize }} MB
                </p>
            </div>
        </button>

        <!-- Image actions -->
        <div
            v-else-if="!disabled"
            class="flex items-center gap-2"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium transition hover:bg-muted"
                @click="openPicker"
            >
                <Upload class="size-4" />
                Change image
            </button>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium text-destructive transition hover:bg-destructive/10"
                @click="removeImage"
            >
                <Trash2 class="size-4" />
                {{ removeLabel }}
            </button>
        </div>

        <p
            v-if="error || validationError"
            class="text-sm text-destructive"
        >
            {{ validationError || error }}
        </p>
    </div>
</template>

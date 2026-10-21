<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {ArrowLeftRight, Box, Hash, MessageSquare, Settings2, User, Wrench,} from '@lucide/vue';
import {computed, ref, watch} from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';

import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppToolSelect from '@/components/selects/AppToolSelect.vue';
import AppToolTransactionTypeSelect from '@/components/selects/AppToolTransactionTypeSelect.vue';

import {toolTransactionPage} from '@/config/toolTransactions';
import type {ToolTransactionValidationField,} from '@/schemas/toolTransactionSchema';
import {toolTransactionSchema} from '@/schemas/toolTransactionSchema';
import type {ToolTransactionType,} from '@/types/toolTransaction';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

export type ToolTransactionFormData = {
    toolId: number | null;
    type: ToolTransactionType | '';
    quantity: number | null;
    fromUserId: number | null;
    toUserId: number | null;
    partId: number | null;
    setupId: number | null;
    returnScrap: boolean | null;
    remarks: string;
};

type BackendErrorField =
    | 'tool_id'
    | 'type'
    | 'quantity'
    | 'from_user_id'
    | 'to_user_id'
    | 'part_id'
    | 'setup_id'
    | 'return_scrap'
    | 'remarks';

/*
|--------------------------------------------------------------------------
| Props & Events
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        form: InertiaForm<ToolTransactionFormData>;
        errors: Record<string, string>;
        processing: boolean;
        submitLabel: string;
        isEditing?: boolean;
    }>(),
    {
        isEditing: false,
    },
);

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const isIssue = computed(() => props.form.type === 'issue');

const isAdjustment = computed(() => props.form.type === 'adjustment');

const showUsers = computed(() => isIssue.value);

const showPartAndSetup = computed(() => isIssue.value);

const showReturnScrap = computed(() => isIssue.value);

const quantityMin = computed(() => (isAdjustment.value ? -2147483648 : 1));

const quantityMax = 4294967295;

/*
|--------------------------------------------------------------------------
| Frontend Validation
|--------------------------------------------------------------------------
*/

const hasSubmitted = ref(false);

const frontendErrors = ref<
    Partial<Record<ToolTransactionValidationField, string>>
>({});

const validateForm = (): boolean => {
    const result = toolTransactionSchema.safeParse({
        toolId: props.form.toolId,
        type: props.form.type,
        quantity: props.form.quantity,
        fromUserId: props.form.fromUserId,
        toUserId: props.form.toUserId,
        partId: props.form.partId,
        setupId: props.form.setupId,
        returnScrap: props.form.returnScrap,
        remarks: props.form.remarks,
    });

    if (result.success) {
        frontendErrors.value = {};

        return true;
    }

    const errors: Partial<
        Record<ToolTransactionValidationField, string>
    > = {};

    for (const issue of result.error.issues) {
        const field = issue.path[0];

        if (
            typeof field === 'string' &&
            field in props.form &&
            !errors[field as ToolTransactionValidationField]
        ) {
            errors[field as ToolTransactionValidationField] = issue.message;
        }
    }

    frontendErrors.value = errors;

    return false;
};

const backendErrorFieldMap: Record<
    ToolTransactionValidationField,
    BackendErrorField
> = {
    toolId: 'tool_id',
    type: 'type',
    quantity: 'quantity',
    fromUserId: 'from_user_id',
    toUserId: 'to_user_id',
    partId: 'part_id',
    setupId: 'setup_id',
    returnScrap: 'return_scrap',
    remarks: 'remarks',
};

const getError = (
    field: ToolTransactionValidationField,
): string | undefined => {
    return (
        frontendErrors.value[field] ??
        props.errors[backendErrorFieldMap[field]]
    );
};

const handleSubmit = (): void => {
    hasSubmitted.value = true;

    if (!validateForm()) {
        return;
    }

    emit('submit');
};

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

// A setup belongs to its selected part.
// Clear the old setup when the part changes.
watch(
    () => props.form.partId,
    (newPartId, oldPartId) => {
        if (newPartId !== oldPartId) {
            props.form.setupId = null;
        }
    },
);

// Revalidate after the first submit when form values change.
watch(
    () => [
        props.form.toolId,
        props.form.type,
        props.form.quantity,
        props.form.fromUserId,
        props.form.toUserId,
        props.form.partId,
        props.form.setupId,
        props.form.returnScrap,
        props.form.remarks,
    ],
    () => {
        if (hasSubmitted.value) {
            validateForm();
        }
    },
);

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleTypeChange = (value: string | number | null): void => {
    if (props.isEditing || props.processing) {
        return;
    }

    const nextType =
        typeof value === 'string' ? (value as ToolTransactionType) : '';

    if (props.form.type === nextType) {
        return;
    }

    props.form.type = nextType;

    // Reset fields that depend on the transaction type.
    // Keep fromUserId: Create initializes it from the logged-in user.
    props.form.toolId = null;
    props.form.quantity = null;
    props.form.toUserId = null;
    props.form.partId = null;
    props.form.setupId = null;
    props.form.returnScrap = null;
};
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        novalidate
        @submit.prevent="handleSubmit"
    >
        <!-- Transaction Details -->

        <FormSection
            :description="toolTransactionPage.form.sectionDescription"
            :title="toolTransactionPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Transaction Type -->

                <AppField
                    :error="getError('type')"
                    :label="toolTransactionPage.form.typeLabel"
                    input-id="tool-transaction-type"
                    required
                >
                    <AppToolTransactionTypeSelect
                        id="tool-transaction-type"
                        :clearable="!props.isEditing"
                        :disabled="props.processing || props.isEditing"
                        :error="getError('type')"
                        :icon="ArrowLeftRight"
                        :model-value="props.form.type || null"
                        :placeholder="toolTransactionPage.form.typePlaceholder"
                        searchable
                        @update:model-value="handleTypeChange"
                    />
                </AppField>

                <!-- Tool -->

                <AppField
                    :error="getError('toolId')"
                    :label="toolTransactionPage.form.toolLabel"
                    input-id="tool-transaction-tool"
                    required
                >
                    <AppToolSelect
                        id="tool-transaction-tool"
                        v-model="props.form.toolId"
                        :clearable="!props.isEditing"
                        :disabled="props.processing || props.isEditing"
                        :error="getError('toolId')"
                        :icon="Wrench"
                        :placeholder="toolTransactionPage.form.toolPlaceholder"
                        searchable
                    />
                </AppField>

                <!-- Quantity -->

                <AppField
                    :error="getError('quantity')"
                    :label="toolTransactionPage.form.quantityLabel"
                    input-id="tool-transaction-quantity"
                    required
                >
                    <AppInput
                        id="tool-transaction-quantity"
                        v-model.number="props.form.quantity"
                        :disabled="props.processing"
                        :error="getError('quantity')"
                        :icon="Hash"
                        :max="quantityMax"
                        :min="quantityMin"
                        :placeholder="
                            toolTransactionPage.form.quantityPlaceholder
                        "
                        step="1"
                        type="number"
                    />
                </AppField>

                <!-- From User -->

                <AppField
                    v-if="showUsers"
                    :error="getError('fromUserId')"
                    :label="toolTransactionPage.form.fromUserLabel"
                    input-id="tool-transaction-from-user"
                >
                    <AppEmployeeSelect
                        id="tool-transaction-from-user"
                        v-model="props.form.fromUserId"
                        :disabled="props.processing || props.isEditing"
                        :error="getError('fromUserId')"
                        :icon="User"
                        :placeholder="
                            toolTransactionPage.form.fromUserPlaceholder
                        "
                        clearable
                        searchable
                    />
                </AppField>

                <!-- To User -->

                <AppField
                    v-if="showUsers"
                    :error="getError('toUserId')"
                    :label="toolTransactionPage.form.toUserLabel"
                    input-id="tool-transaction-to-user"
                    required
                >
                    <AppEmployeeSelect
                        id="tool-transaction-to-user"
                        v-model="props.form.toUserId"
                        :disabled="props.processing || props.isEditing"
                        :error="getError('toUserId')"
                        :icon="User"
                        :placeholder="
                            toolTransactionPage.form.toUserPlaceholder
                        "
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Part -->

                <AppField
                    v-if="showPartAndSetup"
                    :error="getError('partId')"
                    :label="toolTransactionPage.form.partLabel"
                    input-id="tool-transaction-part"
                >
                    <AppPartSelect
                        id="tool-transaction-part"
                        v-model="props.form.partId"
                        :disabled="props.processing || props.isEditing"
                        :error="getError('partId')"
                        :icon="Box"
                        :placeholder="toolTransactionPage.form.partPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Setup -->

                <AppField
                    v-if="showPartAndSetup"
                    :error="getError('setupId')"
                    :label="toolTransactionPage.form.setupLabel"
                    input-id="tool-transaction-setup"
                >
                    <AppSetupSelect
                        id="tool-transaction-setup"
                        v-model="props.form.setupId"
                        :disabled="
                            props.processing ||
                            props.isEditing ||
                            !props.form.partId
                        "
                        :error="getError('setupId')"
                        :icon="Settings2"
                        :part-id="props.form.partId"
                        :placeholder="toolTransactionPage.form.setupPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Return / Scrap -->

                <div v-if="showReturnScrap" class="md:col-span-2">
                    <ToggleCardInput
                        v-model="props.form.returnScrap"
                        :active-text="
                            toolTransactionPage.form.returnScrapActiveText
                        "
                        :description="
                            toolTransactionPage.form.returnScrapDescription
                        "
                        :disabled="props.processing || props.isEditing"
                        :error="getError('returnScrap')"
                        :inactive-text="
                            toolTransactionPage.form.returnScrapInactiveText
                        "
                        :label="toolTransactionPage.form.returnScrapLabel"
                        :title="toolTransactionPage.form.returnScrapTitle"
                    />
                </div>
            </div>
        </FormSection>

        <!-- Additional Information -->

        <FormSection
            description="Add an optional note for this tool transaction."
            title="Additional Information"
        >
            <div class="grid min-w-0 gap-5">
                <AppField
                    :error="getError('remarks')"
                    :label="toolTransactionPage.form.remarksLabel"
                    input-id="tool-transaction-remarks"
                >
                    <AppInput
                        id="tool-transaction-remarks"
                        v-model="props.form.remarks"
                        :disabled="props.processing"
                        :error="getError('remarks')"
                        :icon="MessageSquare"
                        :placeholder="
                            toolTransactionPage.form.remarksPlaceholder
                        "
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->

        <FormActions
            :cancel-label="toolTransactionPage.form.cancelLabel"
            :processing="props.processing"
            :submit-label="props.submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>

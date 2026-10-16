<script setup lang="ts">
import type {InertiaForm} from '@inertiajs/vue3';
import {ArrowLeftRight, Box, Hash, MessageSquare, Settings2, User, Wrench,} from '@lucide/vue';
import {computed, watch} from 'vue';

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
import type {ToolTransactionType} from '@/types/toolTransaction';

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

const isAdjustment = computed(
    () => props.form.type === 'adjustment',
);

const showUsers = computed(() => isIssue.value);

const showPartAndSetup = computed(() => isIssue.value);

const showReturnScrap = computed(() => isIssue.value);

const quantityMin = computed(() =>
    isAdjustment.value ? -2147483648 : 1,
);

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

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleTypeChange = (
    value: string | number | null,
): void => {
    if (props.isEditing || props.processing) {
        return;
    }

    const nextType =
        typeof value === 'string'
            ? (value as ToolTransactionType)
            : '';

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
        @submit.prevent="emit('submit')"
    >
        <!-- Transaction Details -->
        <FormSection
            :title="toolTransactionPage.form.sectionTitle"
            :description="toolTransactionPage.form.sectionDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Transaction Type -->
                <AppField
                    :label="toolTransactionPage.form.typeLabel"
                    input-id="tool-transaction-type"
                    :error="props.errors.type"
                    required
                >
                    <AppToolTransactionTypeSelect
                        id="tool-transaction-type"
                        :model-value="props.form.type || null"
                        :placeholder="toolTransactionPage.form.typePlaceholder"
                        :disabled="props.processing || props.isEditing"
                        :icon="ArrowLeftRight"
                        :error="props.errors.type"
                        searchable
                        :clearable="!props.isEditing"
                        @update:model-value="handleTypeChange"
                    />
                </AppField>

                <!-- Tool -->
                <AppField
                    :label="toolTransactionPage.form.toolLabel"
                    input-id="tool-transaction-tool"
                    :error="props.errors.tool_id"
                    required
                >
                    <AppToolSelect
                        id="tool-transaction-tool"
                        v-model="props.form.toolId"
                        :placeholder="toolTransactionPage.form.toolPlaceholder"
                        :disabled="props.processing || props.isEditing"
                        :icon="Wrench"
                        :error="props.errors.tool_id"
                        searchable
                        :clearable="!props.isEditing"
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    :label="toolTransactionPage.form.quantityLabel"
                    input-id="tool-transaction-quantity"
                    :error="props.errors.quantity"
                    required
                >
                    <AppInput
                        id="tool-transaction-quantity"
                        v-model.number="props.form.quantity"
                        type="number"
                        :min="quantityMin"
                        :max="2147483647"
                        step="1"
                        :placeholder="toolTransactionPage.form.quantityPlaceholder"
                        :disabled="props.processing"
                        :icon="Hash"
                        :error="props.errors.quantity"
                    />
                </AppField>

                <!-- From User -->
                <AppField
                    v-if="showUsers"
                    :label="toolTransactionPage.form.fromUserLabel"
                    input-id="tool-transaction-from-user"
                    :error="props.errors.from_user_id"
                >
                    <AppEmployeeSelect
                        id="tool-transaction-from-user"
                        v-model="props.form.fromUserId"
                        :placeholder="toolTransactionPage.form.fromUserPlaceholder"
                        :disabled="props.processing || props.isEditing"
                        :icon="User"
                        :error="props.errors.from_user_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- To User -->
                <AppField
                    v-if="showUsers"
                    :label="toolTransactionPage.form.toUserLabel"
                    input-id="tool-transaction-to-user"
                    :error="props.errors.to_user_id"
                    required
                >
                    <AppEmployeeSelect
                        id="tool-transaction-to-user"
                        v-model="props.form.toUserId"
                        :placeholder="toolTransactionPage.form.toUserPlaceholder"
                        :disabled="props.processing || props.isEditing"
                        :icon="User"
                        :error="props.errors.to_user_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    v-if="showPartAndSetup"
                    :label="toolTransactionPage.form.partLabel"
                    input-id="tool-transaction-part"
                    :error="props.errors.part_id"
                >
                    <AppPartSelect
                        id="tool-transaction-part"
                        v-model="props.form.partId"
                        :placeholder="toolTransactionPage.form.partPlaceholder"
                        :disabled="props.processing || props.isEditing"
                        :icon="Box"
                        :error="props.errors.part_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Setup -->
                <AppField
                    v-if="showPartAndSetup"
                    :label="toolTransactionPage.form.setupLabel"
                    input-id="tool-transaction-setup"
                    :error="props.errors.setup_id"
                >
                    <AppSetupSelect
                        id="tool-transaction-setup"
                        v-model="props.form.setupId"
                        :part-id="props.form.partId"
                        :placeholder="toolTransactionPage.form.setupPlaceholder"
                        :disabled="
                            props.processing ||
                            props.isEditing ||
                            !props.form.partId
                        "
                        :icon="Settings2"
                        :error="props.errors.setup_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Return / Scrap -->
                <div
                    v-if="showReturnScrap"
                    class="md:col-span-2"
                >
                    <ToggleCardInput
                        v-model="props.form.returnScrap"
                        :title="toolTransactionPage.form.returnScrapTitle"
                        :label="toolTransactionPage.form.returnScrapLabel"
                        :description="toolTransactionPage.form.returnScrapDescription"
                        :active-text="toolTransactionPage.form.returnScrapActiveText"
                        :inactive-text="toolTransactionPage.form.returnScrapInactiveText"
                        :error="props.errors.return_scrap"
                        :disabled="props.processing || props.isEditing"
                    />
                </div>
            </div>
        </FormSection>

        <!-- Additional Information -->
        <FormSection
            title="Additional Information"
            description="Add an optional note for this tool transaction."
        >
            <div class="grid min-w-0 gap-5">
                <AppField
                    :label="toolTransactionPage.form.remarksLabel"
                    input-id="tool-transaction-remarks"
                    :error="props.errors.remarks"
                >
                    <AppInput
                        id="tool-transaction-remarks"
                        v-model="props.form.remarks"
                        :placeholder="toolTransactionPage.form.remarksPlaceholder"
                        :disabled="props.processing"
                        :icon="MessageSquare"
                        :error="props.errors.remarks"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="props.submitLabel"
            :cancel-label="toolTransactionPage.form.cancelLabel"
            :processing="props.processing"
            @cancel="emit('cancel')"
        />
    </form>
</template>

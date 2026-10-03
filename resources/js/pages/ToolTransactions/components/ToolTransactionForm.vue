<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Box,
    Hash,
    MessageSquare,
    Settings2,
    User,
    Wrench,
} from '@lucide/vue';
import { computed } from 'vue';

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
import { toolTransactionPage } from '@/config/toolTransactions';
import type { ToolTransactionType } from '@/types/toolTransaction';

// Types
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

// Props
const props = defineProps<{
    form: InertiaForm<ToolTransactionFormData>;
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
}>();

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Computed
const showUsers = computed(() =>
    ['issue', 'return'].includes(props.form.type),
);

const showPartAndSetup = computed(() =>
    ['issue', 'return'].includes(props.form.type),
);

const showReturnScrap = computed(
    () => props.form.type === 'return',
);

// Type Change
const handleTypeChange = (
    value: string | number | null,
): void => {
    props.form.type =
        typeof value === 'string'
            ? (value as ToolTransactionType)
            : '';

    props.form.toolId = null;

    if (!['issue', 'return'].includes(props.form.type)) {
        props.form.fromUserId = null;
        props.form.toUserId = null;
        props.form.partId = null;
        props.form.setupId = null;
        props.form.returnScrap = null;
    }

    if (props.form.type !== 'return') {
        props.form.returnScrap = null;
    }
};
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Transaction -->
        <FormSection
            :title="toolTransactionPage.form.sectionTitle"
            :description="
                toolTransactionPage.form.sectionDescription
            "
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Transaction Type -->
                <AppField
                    :label="
                        toolTransactionPage.form.typeLabel
                    "
                    input-id="tool-transaction-type"
                    :error="props.errors.type"
                    required
                >
                    <AppToolTransactionTypeSelect
                        id="tool-transaction-type"
                        :model-value="
                            props.form.type || null
                        "
                        :placeholder="
                            toolTransactionPage.form.typePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="ArrowLeftRight"
                        :error="props.errors.type"
                        searchable
                        clearable
                        @update:model-value="
                            handleTypeChange
                        "
                    />
                </AppField>

                <!-- Tool -->
                <AppField
                    :label="
                        toolTransactionPage.form.toolLabel
                    "
                    input-id="tool-transaction-tool"
                    :error="props.errors.toolId"
                    required
                >
                    <AppToolSelect
                        id="tool-transaction-tool"
                        v-model="props.form.toolId"
                        :type="props.form.type || null"
                        :placeholder="
                            toolTransactionPage.form.toolPlaceholder
                        "
                        :icon="Wrench"
                        :error="props.errors.toolId"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    :label="
                        toolTransactionPage.form.quantityLabel
                    "
                    input-id="tool-transaction-quantity"
                    :error="props.errors.quantity"
                    required
                >
                    <AppInput
                        id="tool-transaction-quantity"
                        v-model.number="props.form.quantity"
                        type="number"
                        min="1"
                        step="1"
                        :placeholder="
                            toolTransactionPage.form.quantityPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Hash"
                        :error="props.errors.quantity"
                    />
                </AppField>

                <!-- From User -->
                <AppField
                    v-if="showUsers"
                    :label="
                        toolTransactionPage.form.fromUserLabel
                    "
                    input-id="tool-transaction-from-user"
                    :error="props.errors.fromUserId"
                    required
                >
                    <AppEmployeeSelect
                        id="tool-transaction-from-user"
                        v-model="props.form.fromUserId"
                        :placeholder="
                            toolTransactionPage.form.fromUserPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="User"
                        :error="props.errors.fromUserId"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- To User -->
                <AppField
                    v-if="showUsers"
                    :label="
                        toolTransactionPage.form.toUserLabel
                    "
                    input-id="tool-transaction-to-user"
                    :error="props.errors.toUserId"
                    required
                >
                    <AppEmployeeSelect
                        id="tool-transaction-to-user"
                        v-model="props.form.toUserId"
                        :placeholder="
                            toolTransactionPage.form.toUserPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="User"
                        :error="props.errors.toUserId"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    v-if="showPartAndSetup"
                    :label="
                        toolTransactionPage.form.partLabel
                    "
                    input-id="tool-transaction-part"
                    :error="props.errors.partId"
                    required
                >
                    <AppPartSelect
                        id="tool-transaction-part"
                        v-model="props.form.partId"
                        :placeholder="
                            toolTransactionPage.form.partPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Box"
                        :error="props.errors.partId"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Setup -->
                <AppField
                    v-if="showPartAndSetup"
                    :label="
                        toolTransactionPage.form.setupLabel
                    "
                    input-id="tool-transaction-setup"
                    :error="props.errors.setupId"
                    required
                >
                    <AppSetupSelect
                        id="tool-transaction-setup"
                        v-model="props.form.setupId"
                        :placeholder="
                            toolTransactionPage.form.setupPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Settings2"
                        :error="props.errors.setupId"
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
                        :title="
                            toolTransactionPage.form.returnScrapTitle
                        "
                        :label="
                            toolTransactionPage.form.returnScrapLabel
                        "
                        :description="
                            toolTransactionPage.form
                                .returnScrapDescription
                        "
                        :active-text="
                            toolTransactionPage.form
                                .returnScrapActiveText
                        "
                        :inactive-text="
                            toolTransactionPage.form
                                .returnScrapInactiveText
                        "
                        :error="props.errors.returnScrap"
                        :disabled="props.processing"
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
                <!-- Remarks -->
                <AppField
                    label="Remarks"
                    input-id="tool-transaction-remarks"
                    :error="props.errors.remarks"
                >
                    <AppInput
                        id="tool-transaction-remarks"
                        v-model="props.form.remarks"
                        :placeholder="
                            toolTransactionPage.form.remarksPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="MessageSquare"
                        :error="props.errors.remarks"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="submitLabel"
            :cancel-label="
                toolTransactionPage.form.cancelLabel
            "
            :processing="processing"
            @cancel="emit('cancel')"
        />
    </form>
</template>

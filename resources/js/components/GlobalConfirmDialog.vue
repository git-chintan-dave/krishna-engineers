<script lang="ts" setup>
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';

import { useConfirmState } from '@/composables/useConfirm';

const { state, onConfirm, onCancel } = useConfirmState();
</script>

<template>
    <AlertDialog
        :open="state.isOpen"
        @update:open="(open) => !open && onCancel()"
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    {{ state.title }}
                </AlertDialogTitle>

                <AlertDialogDescription>
                    {{ state.description }}
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <Button
                    class="cursor-pointer"
                    type="button"
                    variant="outline"
                    @click="onCancel"
                >
                    {{ state.cancelText }}
                </Button>

                <Button
                    class="cursor-pointer"
                    type="button"
                    variant="destructive"
                    @click="onConfirm"
                >
                    {{ state.confirmText }}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

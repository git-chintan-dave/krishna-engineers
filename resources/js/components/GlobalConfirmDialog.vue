<script setup lang="ts">
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
                    type="button"
                    variant="outline"
                    class="cursor-pointer"
                    @click="onCancel"
                >
                    {{ state.cancelText }}
                </Button>

                <Button
                    type="button"
                    variant="destructive"
                    class="cursor-pointer"
                    @click="onConfirm"
                >
                    {{ state.confirmText }}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

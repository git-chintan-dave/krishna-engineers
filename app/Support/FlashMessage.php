<?php

namespace App\Support;

class FlashMessage
{
    /**
     * Generate a success message.
     */
    public static function success(
        string $action,
        string $module,
    ): string {
        return match ($action) {
            'added' => "{$module} added successfully.",
            'created' => "{$module} created successfully.",
            'updated' => "{$module} updated successfully.",
            'deleted' => "{$module} deleted successfully.",
            'bulk_deleted' => "Selected {$module} records deleted successfully.",
            'status_updated' => "{$module} status updated successfully.",
            'permissions_updated' => "{$module} permissions updated successfully.",
            'default_updated' => "Default {$module} updated successfully.",
            'imported' => "{$module} imported successfully.",
            'exported' => "{$module} exported successfully.",
            'assigned' => "{$module} assigned successfully.",
            'unassigned' => "{$module} unassigned successfully.",
            'restored' => "{$module} restored successfully.",
            'archived' => "{$module} archived successfully.",
            'duplicated' => "{$module} duplicated successfully.",
            'activated' => "{$module} activated successfully.",
            'deactivated' => "{$module} deactivated successfully.",
            default => "{$module} action completed successfully.",
        };
    }

    /**
     * Generate an error message.
     */
    public static function error(
        string $action,
        string $module,
    ): string {
        return match ($action) {
            'added' => "Failed to add {$module}.",
            'created' => "Failed to create {$module}.",
            'updated' => "Failed to update {$module}.",
            'deleted' => "Failed to delete {$module}.",
            'bulk_deleted' => "Failed to delete selected {$module} records.",
            'status_updated' => "Failed to update {$module} status.",
            'permissions_updated' => "Failed to update {$module} permissions.",
            'imported' => "Failed to import {$module}.",
            'exported' => "Failed to export {$module}.",
            'assigned' => "Failed to assign {$module}.",
            'unassigned' => "Failed to unassign {$module}.",
            'restored' => "Failed to restore {$module}.",
            'archived' => "Failed to archive {$module}.",
            'duplicated' => "Failed to duplicate {$module}.",
            'activated' => "Failed to activate {$module}.",
            'deactivated' => "Failed to deactivate {$module}.",
            default => "{$module} action failed.",
        };
    }
}

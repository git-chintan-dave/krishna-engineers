import { createInertiaApp } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import vConfirm from '@/directives/confirm';
import { initializeFlashToast } from '@/lib/flashToast';
import { initializeTheme } from '@/composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
        app.directive('confirm', vConfirm);
    },
    progress: {
        color: '#4B5563',
    },
});

// Initialize application theme.
initializeTheme();

// Initialize flash toast notifications.
initializeFlashToast();

// Register the service worker.
if (
    'serviceWorker' in navigator &&
    import.meta.env.PROD
) {
    window.addEventListener('load', () => {
        void navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                console.info(
                    'Service worker registered:',
                    registration.scope,
                );

                registration.addEventListener(
                    'updatefound',
                    () => {
                        const newWorker =
                            registration.installing;

                        if (!newWorker) {
                            return;
                        }

                        newWorker.addEventListener(
                            'statechange',
                            () => {
                                if (
                                    newWorker.state ===
                                    'installed' &&
                                    navigator.serviceWorker.controller
                                ) {
                                    console.info(
                                        'New service worker available. Reload to update.',
                                    );
                                }
                            },
                        );
                    },
                );
            })
            .catch((error) => {
                console.error(
                    'Service worker registration failed:',
                    error,
                );
            });
    });
}

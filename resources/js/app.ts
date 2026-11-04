import {createInertiaApp, router} from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import vConfirm from '@/directives/confirm';
import {initializeFlashToast} from '@/lib/flashToast';
import {initializeTheme} from '@/composables/useAppearance';
import {useLookup} from '@/composables/useLookup';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

let enumOptionsPrefetched = false;

function prefetchEnumOptionsForAuthenticatedUser(
    isAuthenticated: boolean,
): void {
    if (!isAuthenticated || enumOptionsPrefetched) {
        return;
    }

    enumOptionsPrefetched = true;

    void useLookup().prefetchEnumOptions();
}

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

// Prefetch enum lookups only for authenticated users.
router.on('navigate', (event) => {
    const user = event.detail.page.props.auth?.user;

    prefetchEnumOptionsForAuthenticatedUser(!!user);
});

// Service worker:
// - Production: register and update the PWA service worker.
// - Development: remove any previously registered service worker so it
//   cannot serve stale production assets while using Vite HMR.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        if (import.meta.env.PROD) {
            void navigator.serviceWorker
                .register('/sw.js')
                .then((registration) => {
                    console.info(
                        'Service worker registered:',
                        registration.scope,
                    );

                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;

                        if (!newWorker) {
                            return;
                        }

                        newWorker.addEventListener('statechange', () => {
                            if (
                                newWorker.state === 'installed' &&
                                navigator.serviceWorker.controller
                            ) {
                                console.info(
                                    'New service worker available. Reload to update.',
                                );
                            }
                        });
                    });
                })
                .catch((error) => {
                    console.error(
                        'Service worker registration failed:',
                        error,
                    );
                });

            return;
        }

        // Development: unregister previously installed service workers.
        void navigator.serviceWorker
            .getRegistrations()
            .then((registrations) => {
                if (!registrations.length) {
                    return;
                }

                return Promise.all(
                    registrations.map((registration) =>
                        registration.unregister(),
                    ),
                );
            })
            .then(() => {
                console.info(
                    'Development mode: existing service workers unregistered.',
                );
            })
            .catch((error) => {
                console.error(
                    'Development service worker cleanup failed:',
                    error,
                );
            });
    });
}

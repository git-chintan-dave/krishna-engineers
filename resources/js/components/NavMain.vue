<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

const isItemActive = (item: NavItem): boolean => {
    if (item.href && isCurrentUrl(item.href)) {
        return true;
    }

    return item.items?.some((subItem) => isItemActive(subItem)) ?? false;
};

const hasChildren = (item: NavItem): boolean => {
    return Boolean(item.items?.length);
};
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel> Navigation </SidebarGroupLabel>

        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Direct navigation item -->
                <SidebarMenuItem v-if="!hasChildren(item)">
                    <SidebarMenuButton
                        as-child
                        :is-active="isItemActive(item)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href!">
                            <component
                                v-if="item.icon"
                                :is="item.icon"
                                aria-hidden="true"
                            />

                            <span>
                                {{ item.title }}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- Navigation group -->
                <Collapsible
                    v-else
                    as-child
                    :default-open="isItemActive(item)"
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="isItemActive(item)"
                                :tooltip="item.title"
                            >
                                <component
                                    v-if="item.icon"
                                    :is="item.icon"
                                    aria-hidden="true"
                                />

                                <span>
                                    {{ item.title }}
                                </span>

                                <ChevronRight
                                    class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                    aria-hidden="true"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="subItem in item.items"
                                    :key="subItem.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isItemActive(subItem)"
                                    >
                                        <Link :href="subItem.href!">
                                            <component
                                                v-if="subItem.icon"
                                                :is="subItem.icon"
                                                aria-hidden="true"
                                            />

                                            <span>
                                                {{ subItem.title }}
                                            </span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

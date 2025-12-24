<script setup lang="ts">
import type { Component } from "vue"
import type { SidebarMenuButtonProps } from "./SidebarMenuButtonChild.vue"
import { reactiveOmit } from "@vueuse/core"
import SidebarMenuButtonChild from "./SidebarMenuButtonChild.vue"
import { useSidebar } from "./utils"
import { computed } from "vue"

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(defineProps<SidebarMenuButtonProps & {
  tooltip?: string | Component
}>(), {
  as: "button",
  variant: "default",
  size: "default",
})

const { isMobile, state } = useSidebar()

const delegatedProps = reactiveOmit(props, "tooltip")

const tooltipValue = computed(() => {
  if (!props.tooltip) {
    return '';
  }
  if (typeof props.tooltip !== 'string') {
    return '';
  }
  if (state.value === 'collapsed' && !isMobile.value) {
    return props.tooltip;
  }
  return '';
})
</script>

<template>
  <SidebarMenuButtonChild
    v-bind="{ ...delegatedProps, ...$attrs }"
    v-tooltip.right="tooltipValue"
  >
    <slot />
  </SidebarMenuButtonChild>
</template>

# UI Components

This directory contains PrimeVue wrapper components that provide a consistent API and styling across the application.

## Component Patterns

### File Structure

- Components use a **flat file structure** - no nested directories or index.ts files
- Each component is a single `.vue` file in this directory
- All components use the **`Ui` prefix** to avoid confusion with HTML tags (e.g., `UiButton.vue`, `UiCard.vue`, `UiInput.vue`)
- Components are grouped naturally by filename

### Component Structure

All components follow this pattern:

- Use `v-bind="$attrs"` to pass through HTML attributes (like `class`, `id`, `data-*`, `tabindex`, etc.)
- Define only the PrimeVue props you need in the `Props` interface
- Set `inheritAttrs: false` to control attribute passing
- Pass props explicitly to the PrimeVue component

### Key Patterns

1. **Use PrimeVue Native API**: Components use PrimeVue's native props directly (`severity`, `outlined`, `text`, `size`, etc.) rather than creating custom abstraction layers.

2. **Pass Through Props**: Use `v-bind="$attrs"` to pass through HTML attributes (like `class`, `id`, `data-*`, `tabindex`, etc.) automatically.

3. **Inherit Attrs**: Set `inheritAttrs: false` to have full control over attribute passing.

4. **Styling**: 
   - Use Tailwind CSS classes via the `class` attribute (automatically passed through `$attrs`)
   - Dark mode support is handled automatically by PrimeVue's Aura theme

5. **TypeScript**: All components use TypeScript with proper type definitions for props (no JSDoc comments needed).

### Example: UiButton Component

See [`UiButton.vue`](./UiButton.vue) for a complete example of a PrimeVue wrapper component.

### Usage

Import components directly:

```vue
<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
</script>

<template>
    <UiButton severity="primary" size="large" class="mt-4">
        Click Me
    </UiButton>
</template>
```

### Available Components

- **UiButton** (`UiButton.vue`) - PrimeVue Button wrapper with variants, sizes, and custom styling support

## Guidelines for Creating New Components

When creating new PrimeVue wrapper components:

1. ✅ Use flat file structure (single `.vue` file in this directory)
2. ✅ Use `Ui` prefix for component names (e.g., `UiButton.vue`, `UiCard.vue`)
3. ✅ Accept PrimeVue native props directly
4. ✅ Use `v-bind="$attrs"` for pass-through attributes
5. ✅ Set `inheritAttrs: false`
6. ✅ Include proper TypeScript types (without JSDoc comments)
7. ✅ Support dark mode (handled automatically by PrimeVue Aura theme)
8. ✅ Follow the UiButton component as a template


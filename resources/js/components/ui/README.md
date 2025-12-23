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

### Example: UiTabs Component

The `UiTabs` component uses a simplified API with a `tabs` prop to define tab headers. It supports two variants:

**Default variant (with content panels):**
```vue
<script setup lang="ts">
import UiTabs from '@/components/ui/UiTabs.vue';
import UiTabPanel from '@/components/ui/UiTabPanel.vue';

const tabs = [
    { value: '0', label: 'Tab 1', icon: 'pi pi-home' },
    { value: '1', label: 'Tab 2', disabled: true },
    { value: '2', label: 'Tab 3' },
];
</script>

<template>
    <UiTabs :tabs="tabs" value="0">
        <UiTabPanel value="0">Content 1</UiTabPanel>
        <UiTabPanel value="1">Content 2</UiTabPanel>
        <UiTabPanel value="2">Content 3</UiTabPanel>
    </UiTabs>
</template>
```

**Menu variant (navigation menu without panels):**
```vue
<script setup lang="ts">
import UiTabs from '@/components/ui/UiTabs.vue';
import { dashboard } from '@/routes';

const tabs = [
    { value: '/dashboard', label: 'Dashboard', icon: 'pi pi-home', href: dashboard() },
    { value: '/transactions', label: 'Transactions', icon: 'pi pi-credit-card', href: '/transactions' },
    { value: '/products', label: 'Products', icon: 'pi pi-box', href: '/products' },
];
</script>

<template>
    <UiTabs variant="menu" :tabs="tabs" value="/dashboard" />
</template>
```

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

#### Form Components
- **UiCheckbox** (`UiCheckbox.vue`) - PrimeVue Checkbox wrapper
- **UiDatePicker** (`UiDatePicker.vue`) - PrimeVue DatePicker wrapper
- **UiEditor** (`UiEditor.vue`) - PrimeVue Editor wrapper (rich text editor)
- **UiInputGroup** (`UiInputGroup.vue`) - PrimeVue InputGroup wrapper
- **UiInputNumber** (`UiInputNumber.vue`) - PrimeVue InputNumber wrapper
- **UiInputText** (`UiInputText.vue`) - PrimeVue InputText wrapper
- **UiMultiSelect** (`UiMultiSelect.vue`) - PrimeVue MultiSelect wrapper
- **UiPassword** (`UiPassword.vue`) - PrimeVue Password wrapper
- **UiRadioButton** (`UiRadioButton.vue`) - PrimeVue RadioButton wrapper
- **UiSelect** (`UiSelect.vue`) - PrimeVue Select wrapper
- **UiSelectButton** (`UiSelectButton.vue`) - PrimeVue SelectButton wrapper
- **UiTextarea** (`UiTextarea.vue`) - PrimeVue Textarea wrapper
- **UiToggleSwitch** (`UiToggleSwitch.vue`) - PrimeVue ToggleSwitch wrapper

#### Data Display Components
- **UiCard** (`UiCard.vue`) - PrimeVue Card wrapper
- **UiDataTable** (`UiDataTable.vue`) - PrimeVue DataTable wrapper
- **UiDataView** (`UiDataView.vue`) - PrimeVue DataView wrapper
- **UiDivider** (`UiDivider.vue`) - PrimeVue Divider wrapper
- **UiPanel** (`UiPanel.vue`) - PrimeVue Panel wrapper
- **UiTabs** (`UiTabs.vue`) - PrimeVue Tabs wrapper (simplified API with `tabs` prop)
- **UiTabPanel** (`UiTabPanel.vue`) - PrimeVue TabPanel wrapper (used with UiTabs)

#### Overlay Components
- **UiConfirmPopup** (`UiConfirmPopup.vue`) - PrimeVue ConfirmPopup wrapper
- **UiDialog** (`UiDialog.vue`) - PrimeVue Dialog wrapper
- **UiDrawer** (`UiDrawer.vue`) - PrimeVue Drawer wrapper

#### Navigation Components
- **UiBreadcrumb** (`UiBreadcrumb.vue`) - PrimeVue Breadcrumb wrapper
- **UiMenu** (`UiMenu.vue`) - PrimeVue Menu wrapper

#### File Upload
- **UiFileUpload** (`UiFileUpload.vue`) - PrimeVue FileUpload wrapper

#### Feedback Components
- **UiMessage** (`UiMessage.vue`) - PrimeVue Message wrapper

#### Display Components
- **UiAvatar** (`UiAvatar.vue`) - PrimeVue Avatar wrapper
- **UiChip** (`UiChip.vue`) - PrimeVue Chip wrapper
- **UiProgressSpinner** (`UiProgressSpinner.vue`) - PrimeVue ProgressSpinner wrapper
- **UiTag** (`UiTag.vue`) - PrimeVue Tag wrapper

#### Icon Component
- **UiIcon** (`UiIcon.vue`) - PrimeIcons wrapper component (special case - wraps CSS classes)

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


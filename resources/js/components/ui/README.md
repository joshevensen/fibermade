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
- **UiForm** (`UiForm.vue`) - PrimeVue Form wrapper for client-side validation
- **UiFormField** (`UiFormField.vue`) - PrimeVue FormField wrapper for flexible field binding
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

## Tooltips

PrimeVue Tooltip is implemented as a directive, not a component. The directive is registered globally in `resources/js/app.ts`.

### Usage

Use the `v-tooltip` directive directly on any element:

```vue
<template>
  <!-- Basic tooltip -->
  <button v-tooltip="'Tooltip text'">Hover me</button>
  
  <!-- Tooltip with position -->
  <button v-tooltip.top="'Top tooltip'">Top</button>
  <button v-tooltip.bottom="'Bottom tooltip'">Bottom</button>
  <button v-tooltip.left="'Left tooltip'">Left</button>
  <button v-tooltip.right="'Right tooltip'">Right</button>
  
  <!-- Conditional tooltip -->
  <button v-tooltip="shouldShow ? 'Tooltip text' : null">Conditional</button>
</template>
```

### Migration from reka-ui Tooltip

The old reka-ui Tooltip component pattern:
```vue
<Tooltip>
  <TooltipTrigger as-child>
    <button>Hover me</button>
  </TooltipTrigger>
  <TooltipContent>Tooltip text</TooltipContent>
</Tooltip>
```

Should be replaced with:
```vue
<button v-tooltip="'Tooltip text'">Hover me</button>
```

## Toast Notifications

PrimeVue Toast is implemented using a service and composable pattern. The Toast component is added to `AppLayout.vue` and the `useToast` composable provides helper functions.

### Usage

Import and use the `useToast` composable:

```vue
<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { showSuccess, showError, showInfo, showWarn } = useToast();

function handleSuccess() {
  showSuccess('Operation completed successfully');
}

function handleError() {
  showError('Something went wrong', 'Error');
}

function handleInfo() {
  showInfo('Here is some information');
}

function handleWarning() {
  showWarn('Please be careful');
}
</script>
```

### Toast Helper Functions

The `useToast` composable provides four helper functions:

- `showSuccess(message: string, summary?: string)` - Shows a success toast (3 second duration)
- `showError(message: string, summary?: string)` - Shows an error toast (5 second duration)
- `showInfo(message: string, summary?: string)` - Shows an info toast (3 second duration)
- `showWarn(message: string, summary?: string)` - Shows a warning toast (4 second duration)

All functions accept an optional `summary` parameter. If not provided, default summaries are used ('Success', 'Error', 'Information', 'Warning').

### Advanced Usage

For more control, you can access the toast instance directly:

```vue
<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toast } = useToast();

function showCustomToast() {
  toast.add({
    severity: 'info',
    summary: 'Custom Summary',
    detail: 'Custom message',
    life: 5000,
    sticky: false,
  });
}
</script>
```

## Forms

PrimeVue Forms provides client-side validation using Zod schemas. When using Inertia.js, you can combine PrimeVue Forms for **client-side validation** with Inertia Forms for **server-side submission** (hybrid approach).

### Hybrid Approach

- **PrimeVue Forms**: Handles client-side validation (immediate feedback, better UX)
- **Inertia Forms**: Handles server-side submission (server validation, CSRF tokens, etc.)
- Both validation errors can be displayed (client-side via UiMessage, server-side via InputError)

### Usage

**Basic form with client-side validation and Inertia submission:**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiMessage from '@/components/ui/UiMessage.vue';
import InputError from '@/components/InputError.vue';
import { zodResolver } from '@/lib/zodResolver';
import { z } from 'zod';

// Zod schema for client-side validation
const schema = z.object({
  name: z.string().min(1, { message: 'Name is required.' }),
  email: z.string().email({ message: 'Invalid email address.' }),
});

const resolver = zodResolver(schema);

const initialValues = {
  name: '',
  email: '',
};

// Inertia form for server-side submission
const form = useForm({
  name: '',
  email: '',
});

// Handle PrimeVue Form submission (client-side validation)
function onClientSubmit({ valid, values }) {
  if (valid) {
    // Update Inertia form with validated values
    form.name = values.name;
    form.email = values.email;
    // Submit to server via Inertia
    form.post('/users');
  }
}
</script>

<template>
  <UiForm
    :initialValues="initialValues"
    :resolver="resolver"
    @submit="onClientSubmit"
    class="flex flex-col gap-4"
  >
    <UiFormField v-slot="$field" name="name" class="flex flex-col gap-1">
      <UiInputText
        type="text"
        placeholder="Name"
        v-bind="$field.props"
      />
      <!-- Client-side validation error -->
      <UiMessage
        v-if="$field?.invalid"
        severity="error"
        size="small"
        variant="simple"
      >
        {{ $field.error?.message }}
      </UiMessage>
      <!-- Server-side validation error (from Inertia) -->
      <InputError :message="form.errors.name" />
    </UiFormField>
    
    <UiFormField v-slot="$field" name="email" class="flex flex-col gap-1">
      <UiInputText
        type="email"
        placeholder="Email"
        v-bind="$field.props"
      />
      <!-- Client-side validation error -->
      <UiMessage
        v-if="$field?.invalid"
        severity="error"
        size="small"
        variant="simple"
      >
        {{ $field.error?.message }}
      </UiMessage>
      <!-- Server-side validation error (from Inertia) -->
      <InputError :message="form.errors.email" />
    </UiFormField>

    <button type="submit" :disabled="form.processing">
      {{ form.processing ? 'Submitting...' : 'Submit' }}
    </button>
  </UiForm>
</template>
```

**Using PrimeVue components directly (without FormField wrapper):**

PrimeVue components support the `name` prop directly when inside a PrimeVue Form:

```vue
<script setup lang="ts">
import UiForm from '@/components/ui/UiForm.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import UiMessage from '@/components/ui/UiMessage.vue';
import { zodResolver } from '@/lib/zodResolver';
import { z } from 'zod';

const schema = z.object({
  username: z.string().min(1, { message: 'Username is required.' }),
});

const resolver = zodResolver(schema);
const initialValues = { username: '' };

function onSubmit({ valid, values }) {
  if (valid) {
    // Handle submission
  }
}
</script>

<template>
  <UiForm
    v-slot="$form"
    :initialValues="initialValues"
    :resolver="resolver"
    @submit="onSubmit"
    class="flex flex-col gap-4"
  >
    <div class="flex flex-col gap-1">
      <UiInputText name="username" placeholder="Username" />
      <UiMessage
        v-if="$form.username?.invalid"
        severity="error"
        size="small"
        variant="simple"
      >
        {{ $form.username.error?.message }}
      </UiMessage>
    </div>
  </UiForm>
</template>
```

### Validation Triggers

UiForm supports flexible validation triggers (defaults shown):

- `validateOnBlur: true` - Validate when field loses focus
- `validateOnSubmit: true` - Validate on form submission
- `validateOnValueUpdate: false` - Don't validate on every keystroke
- `validateOnMount: false` - Don't validate on component mount

These can be overridden at the form level or per field:

```vue
<!-- Form-level validation triggers -->
<UiForm
  :validateOnBlur="true"
  :validateOnSubmit="true"
  :validateOnValueUpdate="false"
  :validateOnMount="false"
  ...
>

<!-- Field-level override -->
<UiFormField
  name="email"
  :validateOnValueUpdate="true"
  ...
>
```

### Individual Field Resolvers

Each field can have its own resolver:

```vue
<script setup lang="ts">
import { zodResolver } from '@/lib/zodResolver';
import { z } from 'zod';

const emailResolver = zodResolver(z.string().email({ message: 'Invalid email.' }));
</script>

<template>
  <UiFormField
    name="email"
    :resolver="emailResolver"
    ...
  >
    ...
  </UiFormField>
</template>
```

### Available Components

- **UiForm** (`UiForm.vue`) - PrimeVue Form wrapper for client-side validation
- **UiFormField** (`UiFormField.vue`) - PrimeVue FormField wrapper for flexible field binding

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


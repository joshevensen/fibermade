# PrimeVue Migration - Six Phase Plan

## Overview

This document outlines the six-phase approach to migrating from reka-ui to PrimeVue v4 with Aura theme, and replacing lucide-vue-next with PrimeIcons. Each phase is designed to be completed independently with clear deliverables and testing checkpoints.

**Phase Strategy:**
- Phase 1: Setup and preparation
- Phase 2: Create and fine-tune Button component (template for others)
- Phase 3: Create all remaining PrimeVue components using Button as inspiration
- Phase 4: Migrate all components and icons throughout the application
- Phase 5: Add Tooltips and Toasts (directive and provider patterns)
- Phase 6: Add PrimeVue Forms with Form and FormField components

---

## Phase 1: PrimeVue Preparation & Setup

**Goal**: Get PrimeVue installed, configured, and ready for component development

**Status**: Not Started

### Objectives
- Install all PrimeVue dependencies
- Configure PrimeVue in the application
- Reorganize component structure
- Update type system
- Archive old components for reference

### Tasks

#### 1.1 Install Dependencies
- Install PrimeVue v4: `npm install primevue@^4`
- Install PrimeIcons: `npm install primeicons`
- Install Aura theme: `npm install @primevue/themes`
- Install Tailwind integration: `npm install tailwindcss-primeui`

#### 1.2 Configure PrimeVue
- Update `resources/js/app.ts`:
  - Import PrimeVue: `import PrimeVue from 'primevue/config'`
  - Import Aura theme: `import Aura from '@primevue/themes/aura'`
  - Import PrimeIcons CSS: `import 'primeicons/primeicons.css'`
  - Register PrimeVue plugin with Aura theme in `createApp()` chain
- Configure Tailwind integration in `resources/css/app.css` if needed

#### 1.3 Reorganize Component Structure
- Rename `resources/js/components/ui/` → `resources/js/components/lib/`
  - This archives all reka-ui components for reference during migration
- Create new `resources/js/components/ui/` directory
  - This will house all new PrimeVue wrapper components

#### 1.4 Update Type System
- Update `resources/js/types/index.d.ts`:
  - Remove `import type { LucideIcon } from 'lucide-vue-next'`
  - Replace `LucideIcon` type with `Component` or appropriate PrimeIcon type
  - Update `NavItem` interface to use new icon type

#### 1.5 Remove Unused Configuration
- Delete `components.json` file (no longer using shadcn-vue)

**Note**: Icon component migration will happen in Phase 2 when we create the Button component.

### Deliverables
- ✅ PrimeVue v4 installed and configured
- ✅ Aura theme active
- ✅ PrimeIcons CSS imported
- ✅ Old components archived in `lib/` directory
- ✅ New `ui/` directory ready for PrimeVue components
- ✅ Type definitions updated (icon types will be updated in Phase 2)
- ✅ Application builds and runs (may have broken imports, but PrimeVue is ready)

**Goal**: Create and fine-tune the Button component

**Status**: Not Started

**Note**: This phase will use Agent mode for iterative fine-tuning of the Button component.

### Objectives
- Create PrimeVue Button wrapper component
- Fine-tune Button component API, styling, and behavior
- Establish component patterns and conventions for Phase 3

### Tasks

#### 2.1 Create Button Component
- Create `resources/js/components/ui/button/Button.vue`:
  - Wrap `primevue/button`
  - Support variants: default, secondary, destructive, ghost, outline
  - Support sizes: default, sm, lg, icon
  - Maintain similar API to existing reka-ui Button
  - Use Tailwind classes for styling
  - Include proper TypeScript types
  - Export via `resources/js/components/ui/button/index.ts`

#### 2.2 Fine-tune Button Component
- Test Button component in various scenarios
- Refine API to match existing usage patterns
- Adjust styling to match design expectations
- Ensure all variants and sizes work correctly
- Verify TypeScript types are correct

### Deliverables
- ✅ Button component created and fine-tuned
- ✅ Component patterns established for Phase 3
- ✅ Button component serves as template for other components
- ✅ Components properly exported via index.ts files
- ✅ TypeScript types defined

### Testing Checklist
- [ ] Button component can be imported: `import { Button } from '@/components/ui/button'`
- [ ] Button renders without errors
- [ ] All variants work: default, secondary, destructive, ghost, outline
- [ ] All sizes work: default, sm, lg, icon
- [ ] Button maintains expected prop interfaces
- [ ] Styling matches design expectations
- [ ] TypeScript types are correct (no type errors)

---

## Phase 3: Build All PrimeVue Components

**Goal**: Create all PrimeVue wrapper components using Button component as inspiration

**Status**: Not Started

### Objectives
- Create all PrimeVue wrapper components
- Create Icon component wrapper for PrimeIcons
- Use Button component patterns and conventions
- Use Tailwind for styling
- Ensure components are properly exported

**Note**: Component mapping to Reka components will happen in Phase 4.

### Component List

Create PrimeVue wrapper components in `resources/js/components/ui/` directory. Use the Button component from Phase 2 as a template for:
- Component structure
- TypeScript patterns
- Styling approach
- Export patterns

#### Form Components
- **Checkbox** - `resources/js/components/ui/checkbox/Checkbox.vue` (wrap `primevue/checkbox`)
- **DatePicker** - `resources/js/components/ui/date-picker/DatePicker.vue` (wrap `primevue/datepicker`)
- **Editor** - `resources/js/components/ui/editor/Editor.vue` (wrap `primevue/editor`)
- **InputGroup** - `resources/js/components/ui/input-group/InputGroup.vue` (wrap `primevue/inputgroup`)
- **InputNumber** - `resources/js/components/ui/input-number/InputNumber.vue` (wrap `primevue/inputnumber`)
- **InputText** - `resources/js/components/ui/input-text/InputText.vue` (wrap `primevue/inputtext`)
- **MultiSelect** - `resources/js/components/ui/multi-select/MultiSelect.vue` (wrap `primevue/multiselect`)
- **Password** - `resources/js/components/ui/password/Password.vue` (wrap `primevue/password`)
- **RadioButton** - `resources/js/components/ui/radio-button/RadioButton.vue` (wrap `primevue/radiobutton`)
- **Select** - `resources/js/components/ui/select/Select.vue` (wrap `primevue/select`)
- **SelectButton** - `resources/js/components/ui/select-button/SelectButton.vue` (wrap `primevue/selectbutton`)
- **Textarea** - `resources/js/components/ui/textarea/Textarea.vue` (wrap `primevue/textarea`)
- **ToggleSwitch** - `resources/js/components/ui/toggle-switch/ToggleSwitch.vue` (wrap `primevue/toggleswitch`)

#### Data Display Components
- **DataTable** - `resources/js/components/ui/data-table/DataTable.vue` (wrap `primevue/datatable`)
- **DataView** - `resources/js/components/ui/data-view/DataView.vue` (wrap `primevue/dataview`)
- **Card** - `resources/js/components/ui/card/Card.vue` (wrap `primevue/card`)
- **Divider** - `resources/js/components/ui/divider/Divider.vue` (wrap `primevue/divider`)
- **Panel** - `resources/js/components/ui/panel/Panel.vue` (wrap `primevue/panel`)
- **Tabs** - `resources/js/components/ui/tabs/Tabs.vue` (wrap `primevue/tabs`)

#### Overlay Components
- **ConfirmPopup** - `resources/js/components/ui/confirm-popup/ConfirmPopup.vue` (wrap `primevue/confirmpopup`)
- **Dialog** - `resources/js/components/ui/dialog/Dialog.vue` (wrap `primevue/dialog`)
- **Drawer** - `resources/js/components/ui/drawer/Drawer.vue` (wrap `primevue/drawer`)

#### File Upload
- **Upload** - `resources/js/components/ui/upload/Upload.vue` (wrap `primevue/fileupload`)

#### Navigation Components
- **Breadcrumb** - `resources/js/components/ui/breadcrumb/Breadcrumb.vue` (wrap `primevue/breadcrumb`)
- **Menu** - `resources/js/components/ui/menu/Menu.vue` (wrap `primevue/menu`)

#### Feedback Components
- **Message** - `resources/js/components/ui/message/Message.vue` (wrap `primevue/message`)

#### Display Components
- **Avatar** - `resources/js/components/ui/avatar/Avatar.vue` (wrap `primevue/avatar`)
- **Chip** - `resources/js/components/ui/chip/Chip.vue` (wrap `primevue/chip`)
- **ProgressSpinner** - `resources/js/components/ui/progress-spinner/ProgressSpinner.vue` (wrap `primevue/progressspinner`)
- **Tag** - `resources/js/components/ui/tag/Tag.vue` (wrap `primevue/tag`)

#### Icon Component
- **Icon** - `resources/js/components/ui/icon/Icon.vue`
  - Create wrapper component for PrimeIcons
  - Map icon names to PrimeIcons CSS classes (`pi pi-icon-name`)
  - Support size, color, and other props
  - Make it easy to swap icon libraries in the future
  - Export via `resources/js/components/ui/icon/index.ts`

### Deliverables
- ✅ All PrimeVue wrapper components created
- ✅ Icon component created
- ✅ Components properly exported via index.ts files
- ✅ Components follow Button component patterns
- ✅ Components styled with Tailwind
- ✅ TypeScript types defined for all components

---

## Phase 4: Migrate from Reka to PrimeVue

**Goal**: Replace all reka-ui usage with PrimeVue components throughout the app

**Status**: Not Started

### Objectives
- Map PrimeVue components to existing reka-ui component usage
- Replace all reka-ui component imports with PrimeVue components
- Replace all Lucide icon imports with PrimeIcons (using Icon component)
- Update type definitions for icons
- Remove old component library
- Clean up unused dependencies
- Verify everything works

### Tasks

#### 4.1 Map Components to Reka Usage
- Review all reka-ui component usage in the codebase
- Map each reka-ui component to the appropriate PrimeVue component:
  - Determine which PrimeVue component replaces each reka-ui component
  - Note any API differences that need to be handled
  - Document any missing functionality that needs custom implementation

#### 4.2 Update Type Definitions
- Update `resources/js/types/index.d.ts`:
  - Remove `import type { LucideIcon } from 'lucide-vue-next'`
  - Replace `LucideIcon` type with `Component` or appropriate PrimeIcon type
  - Update `NavItem` interface to use new icon type

#### 4.3 Replace Components in Layouts
- Update `resources/js/layouts/AppLayout.vue`:
  - Replace Breadcrumb components
  - Replace Sidebar components
  - Replace SidebarTrigger
- Update `resources/js/layouts/AuthLayout.vue`:
  - Replace Card components

#### 4.4 Replace Components in Pages
- Update `resources/js/pages/Settings.vue`:
  - Replace Button, Card, Dialog, Input, Label components
  - Replace Lucide icons (Monitor, Moon, Sun)
- Update `resources/js/pages/Dashboard.vue`:
  - Replace any components used
- Update `resources/js/pages/Welcome.vue`:
  - Replace any components used
- Update `resources/js/pages/auth/Login.vue`:
  - Replace Button, Checkbox, Input, Label, Spinner components
- Update `resources/js/pages/auth/Register.vue`:
  - Replace Button, Input, Label, Spinner components
- Update `resources/js/pages/auth/ForgotPassword.vue`:
  - Replace Button, Input, Label, Spinner components
- Update `resources/js/pages/auth/ConfirmPassword.vue`:
  - Replace Button, Input, Label, Spinner components
- Update `resources/js/pages/auth/ResetPassword.vue`:
  - Replace Button, Input, Label, Spinner components

#### 4.5 Replace Components in App Components
- Update `resources/js/components/AppSidebar.vue`:
  - Replace Avatar components
  - Replace DropdownMenu components
  - Replace Sidebar components
  - Replace Lucide icons (ChevronsUpDown, LayoutGrid, LogOut, Settings)

#### 4.6 Replace All Icons
- Find all `lucide-vue-next` imports and replace with Icon component:
  - `Monitor, Moon, Sun` → Use Icon component with PrimeIcons names
  - `ChevronsUpDown, LayoutGrid, LogOut, Settings` → Use Icon component with PrimeIcons names
  - `ChevronDown, ChevronRight, X, Check, Circle, PanelLeft, Loader2Icon, MinusIcon` → Use Icon component with PrimeIcons names
- Update all `<component :is="Icon" />` usage to use new Icon component
- Update all direct icon component usage
- Map Lucide icon names to PrimeIcons names (create mapping if needed)

#### 4.7 Cleanup Old Components
- Delete `resources/js/components/lib/` directory (archived reka-ui components)
- Verify no imports reference old components

#### 4.8 Remove Dependencies
- Uninstall `reka-ui`: `npm uninstall reka-ui`
- Uninstall `lucide-vue-next`: `npm uninstall lucide-vue-next`
- Evaluate `class-variance-authority`:
  - Check if still needed (used in button, badge, alert, sidebar, navigation-menu)
  - Remove if not used by PrimeVue components
- Keep `clsx` and `tailwind-merge` (used in `lib/utils.ts` for `cn()` utility)

#### 4.9 Code Quality
- Run Pint: `vendor/bin/pint --dirty`
- Run Prettier: `npm run format`
- Run ESLint: `npm run lint`
- Fix any formatting or linting issues

#### 4.10 Build Verification
- Run build: `npm run build`
- Verify no build errors
- Test SSR build if applicable: `npm run build:ssr`

### Deliverables
- ✅ All reka-ui components replaced with PrimeVue
- ✅ All Lucide icons replaced with PrimeIcons
- ✅ Old component library removed
- ✅ Unused dependencies uninstalled
- ✅ All tests passing
- ✅ Application builds successfully
- ✅ All pages render correctly
- ✅ All functionality works as expected

### Testing Checklist
- [ ] Build succeeds
- [ ] Code is formatted and linted

---

## Phase 5: Add Tooltips and Toasts

**Goal**: Implement PrimeVue Tooltips (directive) and Toasts (provider) patterns

**Status**: Not Started

### Objectives
- Set up PrimeVue Tooltip directive
- Set up PrimeVue Toast provider/service
- Create wrapper utilities or composables for easier usage
- Integrate into the application

### Tasks

#### 5.1 Set Up Tooltip Directive
- Import and register PrimeVue Tooltip directive in `resources/js/app.ts`
- Create wrapper utility or composable for tooltip usage if needed
- Document tooltip usage patterns
- Test tooltip directive on various elements

#### 5.2 Set Up Toast Provider
- Import and register PrimeVue Toast service in `resources/js/app.ts`
- Create wrapper utility or composable for toast notifications
- Set up Toast component/provider in the application layout
- Create helper functions for common toast types (success, error, info, warn)
- Document toast usage patterns
- Test toast notifications

#### 5.3 Integration
- Add Toast component to main layout if needed
- Update any existing tooltip usage to use PrimeVue directive
- Update any existing toast/notification usage to use PrimeVue Toast
- Ensure tooltips and toasts work with dark mode

### Deliverables
- ✅ Tooltip directive registered and working
- ✅ Toast provider/service set up and working
- ✅ Wrapper utilities/composables created (if needed)
- ✅ Documentation for usage patterns
- ✅ Tooltips and toasts integrated into application

---

## Phase 6: Add PrimeVue Forms

**Goal**: Install and set up PrimeVue Forms library with Form and FormField components using Zod resolver

**Status**: Not Started

### Objectives
- Install PrimeVue Forms library and Zod
- Create Form component wrapper
- Create FormField component wrapper
- Configure Zod resolver
- Set up validation triggers (ValidateOnBlur and ValidateOnSubmit)

### Tasks

#### 6.1 Install Dependencies
- Install PrimeVue Forms: `npm install @primevue/forms`
- Install Zod: `npm install zod`
- Verify dependencies are installed correctly

#### 6.2 Create Form Component
- Create `resources/js/components/ui/form/Form.vue`:
  - Wrap `@primevue/forms` Form component
  - Configure default validation triggers: `validateOnBlur` and `validateOnSubmit`
  - Support Zod resolver integration
  - Maintain similar API to existing form patterns where possible
  - Use Tailwind classes for styling
  - Include proper TypeScript types
  - Export via `resources/js/components/ui/form/index.ts`

#### 6.3 Create FormField Component
- Create `resources/js/components/ui/form-field/FormField.vue`:
  - Wrap `@primevue/forms` FormField component
  - Support built-in PrimeVue components
  - Support non-PrimeVue components (custom inputs)
  - Support individual field resolvers
  - Support template customization (as, asChild props)
  - Include proper TypeScript types
  - Export via `resources/js/components/ui/form-field/index.ts`

#### 6.4 Set Up Zod Resolver
- Create utility file for Zod resolver configuration
- Import Zod resolver from `@primevue/forms/resolvers`
- Create example usage patterns
- Document Zod schema validation patterns

#### 6.5 Configure Validation Triggers
- Configure form-level validation:
  - `validateOnBlur: true` - validate when field loses focus
  - `validateOnSubmit: true` - validate on form submission
  - `validateOnValueUpdate: false` - don't validate on every keystroke
  - `validateOnMount: false` - don't validate on component mount
- Document validation trigger options

### Deliverables
- ✅ PrimeVue Forms library installed
- ✅ Zod installed and configured
- ✅ Form component created and exported
- ✅ FormField component created and exported
- ✅ Zod resolver configured and working
- ✅ Validation triggers configured (ValidateOnBlur and ValidateOnSubmit)
- ✅ Components properly exported via index.ts files
- ✅ TypeScript types defined
- ✅ Documentation for usage patterns

---

## Migration Notes

### Component API Differences
- PrimeVue components use different prop names than reka-ui - will need to map accordingly
- Some components may need custom wrappers to maintain existing API
- PrimeVue uses different event names in some cases

### Icon System Differences
- PrimeIcons use CSS classes (`pi pi-icon-name`) rather than Vue components
- Icon component needs to map icon names to PrimeIcons classes
- Some icon names may need to be adjusted

### Styling Considerations
- Aura theme provides modern styling that works with Tailwind
- Some components may need custom Tailwind classes to match existing design
- Dark mode should work automatically with PrimeVue Aura theme

### Complex Components
- Sidebar is the most complex component - may need custom implementation
- Some components (like InputOTP) may stay as-is if PrimeVue doesn't have equivalent
- NavigationMenu may need custom implementation

### Testing Strategy
- Test each component as it's created (Phase 2)
- Test each page as it's migrated (Phase 3)
- Run full test suite after migration complete
- Manual testing of all user flows

---

## Success Criteria

### Phase 1 Success
- PrimeVue is installed and configured
- Application builds and runs
- Icon component works with PrimeIcons
- Old components are safely archived

### Phase 2 Success
- Button component is created and fine-tuned
- Icon component is created
- Component patterns are established
- Button serves as perfect template for Phase 3

### Phase 3 Success
- All remaining PrimeVue wrapper components are created
- Components follow Button component patterns
- Components can be imported and used
- Components maintain expected APIs
- TypeScript types are correct

### Phase 4 Success
- All reka-ui components are replaced
- All Lucide icons are replaced
- Application functions identically to before
- All tests pass
- No unused dependencies remain
- Code is properly formatted

### Phase 5 Success
- Tooltip directive is registered and functional
- Toast provider is set up and functional
- Wrapper utilities are created and documented
- Tooltips and toasts work throughout the application
- Dark mode support works correctly

### Phase 6 Success
- PrimeVue Forms library is installed and functional
- Form component is created and working
- FormField component is created and working
- Zod resolver is configured and working
- Validation triggers work correctly (ValidateOnBlur and ValidateOnSubmit)
- Form state management works as expected
- Components can be used throughout the application

---

## Rollback Plan

If issues arise during migration:

1. **Phase 1 Rollback**: Revert `app.ts` changes, restore `ui/` directory from `lib/`
2. **Phase 2 Rollback**: Delete Button and Icon components from `ui/` directory
3. **Phase 3 Rollback**: Delete all new components from `ui/` directory, restore from `lib/`
4. **Phase 4 Rollback**: Git revert to before migration, restore dependencies
5. **Phase 5 Rollback**: Remove tooltip directive and toast provider from `app.ts`
6. **Phase 6 Rollback**: Remove Form and FormField components, uninstall `@primevue/forms` and `zod`

All old components are preserved in `lib/` directory until Phase 4 is complete, making rollback easier.

---

## Next Steps

1. Review this plan
2. Create detailed plan for Phase 1
3. Execute Phase 1
4. Test and verify Phase 1 deliverables
5. Create detailed plan for Phase 2 (Button + Icon components)
6. Execute Phase 2 (use Agent mode for fine-tuning)
7. Test and verify Phase 2 deliverables
8. Create detailed plan for Phase 3 (all remaining components)
9. Execute Phase 3
10. Test and verify Phase 3 deliverables
11. Create detailed plan for Phase 4 (migration)
12. Execute Phase 4
13. Test and verify Phase 4 deliverables
14. Create detailed plan for Phase 5 (Tooltips and Toasts)
15. Execute Phase 5
16. Test and verify Phase 5 deliverables
17. Create detailed plan for Phase 6 (PrimeVue Forms)
18. Execute Phase 6
19. Final testing and verification


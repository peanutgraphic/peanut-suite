# UI/UX Improvements Documentation

This document describes the new UI/UX improvements added to the Peanut Suite application.

## Table of Contents

1. [Toast Notification System](#toast-notification-system)
2. [Skeleton Loaders](#skeleton-loaders)
3. [Keyboard Shortcuts](#keyboard-shortcuts)
4. [Dark Mode](#dark-mode)
5. [Step Indicator](#step-indicator)
6. [Accessibility Improvements](#accessibility-improvements)

---

## Toast Notification System

A reusable toast notification component with Zustand state management.

### Features
- **Types**: success, error, warning, info
- **Auto-dismiss**: Configurable duration (default 5 seconds)
- **Stackable**: Multiple toasts can appear simultaneously
- **Animations**: Smooth slide-in/out transitions
- **Dark mode support**

### Usage

```typescript
import { toast } from '@/store';

// Show success toast
toast.success('Changes saved successfully!');

// Show error toast
toast.error('Failed to save changes', 10000); // Custom 10s duration

// Show warning toast
toast.warning('Please review your settings');

// Show info toast
toast.info('Update available');
```

### Store Access

```typescript
import { useToastStore } from '@/store';

const { toasts, addToast, removeToast, clearToasts } = useToastStore();

// Manual toast management
addToast({
  type: 'success',
  message: 'Custom toast',
  duration: 3000
});
```

### Setup
The `ToastContainer` is already added to `main.tsx`, so toasts will display automatically.

---

## Skeleton Loaders

Comprehensive skeleton loader components for various UI elements.

### Available Components

1. **TextSkeleton** - Loading text lines
2. **CardSkeleton** - Loading cards
3. **TableSkeleton** - Loading tables with rows/columns
4. **StatCardSkeleton** - Loading stat cards
5. **ChartSkeleton** - Loading charts
6. **AvatarSkeleton** - Loading avatars (sm, md, lg)
7. **ListItemSkeleton** - Loading list items
8. **FormSkeleton** - Loading forms
9. **PageSkeleton** - Loading entire pages
10. **SkeletonLoader** - Generic loader with variants

### Usage Examples

```typescript
import {
  TextSkeleton,
  TableSkeleton,
  CardSkeleton,
  SkeletonLoader
} from '@/components/common';

// Text skeleton
<TextSkeleton lines={3} />

// Table skeleton
<TableSkeleton rows={10} columns={5} />

// Card skeleton
<CardSkeleton />

// Stat cards
<div className="grid grid-cols-4 gap-4">
  {Array.from({ length: 4 }).map((_, i) => (
    <StatCardSkeleton key={i} />
  ))}
</div>

// Generic loader with variant
<SkeletonLoader variant="table" rows={8} columns={6} />
<SkeletonLoader variant="chart" height="h-80" />
```

### Loading States in Components

```typescript
const { data, isLoading } = useQuery({...});

if (isLoading) {
  return <TableSkeleton rows={10} columns={6} />;
}

return <Table data={data} />;
```

---

## Keyboard Shortcuts

A custom hook for registering keyboard shortcuts with automatic cleanup.

### Features
- **Custom shortcuts**: Define any key combination
- **Form element handling**: Can enable/disable on form inputs
- **Prevent default**: Optional event.preventDefault()
- **Auto cleanup**: Removes listeners on unmount

### Basic Usage

```typescript
import useKeyboardShortcuts from '@/hooks/useKeyboardShortcuts';

function MyComponent() {
  const handleSave = () => {
    // Save logic
  };

  const handleClose = () => {
    // Close logic
  };

  useKeyboardShortcuts([
    {
      key: 's',
      ctrlKey: true,
      callback: handleSave,
      description: 'Save',
    },
    {
      key: 'Escape',
      callback: handleClose,
      description: 'Close',
    }
  ]);

  return <div>Content</div>;
}
```

### Common Shortcuts Helper

```typescript
import useKeyboardShortcuts, { commonShortcuts } from '@/hooks/useKeyboardShortcuts';

useKeyboardShortcuts([
  commonShortcuts.save(handleSave),
  commonShortcuts.escape(handleClose),
  commonShortcuts.delete(handleDelete),
  commonShortcuts.search(handleSearch),
]);
```

### Options

```typescript
useKeyboardShortcuts(
  [...shortcuts],
  {
    enabled: true, // Enable/disable shortcuts
    enableOnFormElements: false, // Allow on input/textarea/select
  }
);
```

### Modal Integration

The Modal component already includes Escape key support to close modals.

---

## Dark Mode

Full dark mode support with system preference detection.

### Features
- **Three modes**: Light, Dark, System
- **Persistent**: Saves preference in localStorage
- **System preference**: Auto-detects system theme
- **Tailwind integration**: Uses Tailwind's dark: classes

### Usage in Settings

The ThemeToggle component is already added to the Settings page under General Settings.

### Toggle Component

```typescript
import { ThemeToggle } from '@/components/common';

// Simple toggle button
<ThemeToggle />

// With label
<ThemeToggle showLabel />

// Dropdown variant (light/dark/system)
<ThemeToggle variant="dropdown" showLabel />
```

### Manual Theme Control

```typescript
import { useThemeStore } from '@/store';

const { theme, isDark, setTheme, toggleTheme } = useThemeStore();

// Set theme
setTheme('dark');
setTheme('light');
setTheme('system');

// Toggle between light/dark
toggleTheme();
```

### Adding Dark Mode to Components

Use Tailwind's `dark:` prefix for dark mode styles:

```typescript
<div className="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
  Content
</div>
```

### CSS Variables

Dark mode colors are handled via Tailwind. The theme is applied to the `<html>` element via the `dark` class.

---

## Step Indicator

A component for multi-step forms and wizards.

### Features
- **Two variants**: Horizontal (default) and Vertical
- **Interactive**: Clickable steps (previous steps only)
- **Visual feedback**: Current, completed, and pending states
- **Descriptions**: Optional step descriptions
- **Accessible**: Full ARIA support

### Basic Usage

```typescript
import { StepIndicator } from '@/components/common';

const steps = [
  { id: 'content', label: 'Content', description: 'Add your content' },
  { id: 'trigger', label: 'Trigger', description: 'Set trigger conditions' },
  { id: 'targeting', label: 'Targeting', description: 'Choose audience' },
  { id: 'style', label: 'Style', description: 'Customize appearance' },
];

function PopupBuilder() {
  const [currentStep, setCurrentStep] = useState(0);

  return (
    <StepIndicator
      steps={steps}
      currentStep={currentStep}
      onStepClick={setCurrentStep}
      allowClickPrevious={true}
    />
  );
}
```

### Vertical Variant

```typescript
<StepIndicator
  steps={steps}
  currentStep={currentStep}
  onStepClick={setCurrentStep}
  variant="vertical"
/>
```

### Compact Variant

For space-constrained layouts:

```typescript
import { CompactStepIndicator } from '@/components/common';

<CompactStepIndicator steps={steps} currentStep={currentStep} />
// Output: "Step 2 of 4" with progress dots
```

### Integration Example

```typescript
function MultiStepForm() {
  const [step, setStep] = useState(0);

  const steps = [
    { id: 'basic', label: 'Basic Info' },
    { id: 'details', label: 'Details' },
    { id: 'review', label: 'Review' },
  ];

  return (
    <div>
      <StepIndicator
        steps={steps}
        currentStep={step}
        onStepClick={setStep}
      />

      <div className="mt-8">
        {step === 0 && <BasicInfoForm />}
        {step === 1 && <DetailsForm />}
        {step === 2 && <ReviewForm />}
      </div>

      <div className="flex justify-between mt-6">
        <Button
          onClick={() => setStep(step - 1)}
          disabled={step === 0}
        >
          Previous
        </Button>
        <Button
          onClick={() => setStep(step + 1)}
          disabled={step === steps.length - 1}
        >
          Next
        </Button>
      </div>
    </div>
  );
}
```

---

## Accessibility Improvements

### Modal Enhancements

1. **Keyboard support**
   - Escape key closes modal
   - Tab key focus trapping
   - Focus restoration on close

2. **ARIA attributes**
   - `role="dialog"`
   - `aria-modal="true"`
   - `aria-labelledby` and `aria-describedby`

3. **Focus management**
   - Auto-focus modal on open
   - Trap focus within modal
   - Restore focus on close

### Button Component

1. **Dark mode support** - All button variants support dark mode
2. **Focus indicators** - Visible focus rings on all buttons
3. **Loading states** - Accessible loading spinner with disabled state

### Focus Indicators

Global focus indicators added to `index.css`:

```css
#peanut-app *:focus-visible {
  outline: 2px solid var(--color-primary-500);
  outline-offset: 2px;
}
```

### Icon Buttons

Always add `aria-label` to icon-only buttons:

```typescript
<button
  onClick={handleAction}
  aria-label="Close modal"
>
  <X className="w-5 h-5" aria-hidden="true" />
</button>
```

### Table Keyboard Navigation

For tables with keyboard navigation:

```typescript
<table role="grid" aria-label="Data table">
  <thead>
    <tr role="row">
      <th role="columnheader" tabIndex={0}>Name</th>
    </tr>
  </thead>
  <tbody>
    <tr role="row" tabIndex={0}>
      <td role="gridcell">Data</td>
    </tr>
  </tbody>
</table>
```

### Screen Reader Announcements

Use toast notifications for important updates:

```typescript
// This will be announced to screen readers
toast.success('Form submitted successfully');
toast.error('Validation failed');
```

### Color Contrast

All components follow WCAG AA standards for color contrast in both light and dark modes.

---

## Examples

### Complete Form with All Features

```typescript
import { useState } from 'react';
import {
  StepIndicator,
  Button,
  Input,
  toast
} from '@/components/common';
import useKeyboardShortcuts, { commonShortcuts } from '@/hooks/useKeyboardShortcuts';

export default function MultiStepForm() {
  const [currentStep, setCurrentStep] = useState(0);
  const [formData, setFormData] = useState({});

  const steps = [
    { id: 'info', label: 'Information' },
    { id: 'settings', label: 'Settings' },
    { id: 'review', label: 'Review' },
  ];

  const handleSave = async () => {
    try {
      // Save logic
      toast.success('Saved successfully!');
    } catch (error) {
      toast.error('Failed to save');
    }
  };

  // Keyboard shortcuts
  useKeyboardShortcuts([
    commonShortcuts.save(handleSave),
    {
      key: 'ArrowRight',
      callback: () => {
        if (currentStep < steps.length - 1) {
          setCurrentStep(currentStep + 1);
        }
      },
      description: 'Next step',
    },
    {
      key: 'ArrowLeft',
      callback: () => {
        if (currentStep > 0) {
          setCurrentStep(currentStep - 1);
        }
      },
      description: 'Previous step',
    },
  ]);

  return (
    <div className="max-w-4xl mx-auto p-6">
      <StepIndicator
        steps={steps}
        currentStep={currentStep}
        onStepClick={setCurrentStep}
      />

      <div className="mt-8">
        {/* Step content */}
        {currentStep === 0 && <InformationStep data={formData} />}
        {currentStep === 1 && <SettingsStep data={formData} />}
        {currentStep === 2 && <ReviewStep data={formData} />}
      </div>

      <div className="flex justify-between mt-8">
        <Button
          variant="outline"
          onClick={() => setCurrentStep(currentStep - 1)}
          disabled={currentStep === 0}
        >
          Previous
        </Button>
        <div className="flex gap-3">
          {currentStep < steps.length - 1 ? (
            <Button onClick={() => setCurrentStep(currentStep + 1)}>
              Next
            </Button>
          ) : (
            <Button onClick={handleSave}>
              Save
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}
```

---

## Migration Guide

### Updating Existing Components

1. **Add dark mode support**
   ```typescript
   // Before
   <div className="bg-white text-slate-900">

   // After
   <div className="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
   ```

2. **Replace loading states**
   ```typescript
   // Before
   {isLoading && <div>Loading...</div>}

   // After
   import { TableSkeleton } from '@/components/common';
   {isLoading && <TableSkeleton />}
   ```

3. **Add keyboard shortcuts**
   ```typescript
   import useKeyboardShortcuts from '@/hooks/useKeyboardShortcuts';

   useKeyboardShortcuts([
     { key: 's', ctrlKey: true, callback: handleSave }
   ]);
   ```

4. **Replace alerts with toasts**
   ```typescript
   // Before
   alert('Success!');

   // After
   import { toast } from '@/store';
   toast.success('Success!');
   ```

---

## Best Practices

1. **Always use toast notifications** instead of alerts for user feedback
2. **Add keyboard shortcuts** to all major actions (save, close, delete)
3. **Use skeleton loaders** for all async data loading states
4. **Support dark mode** in all new components using Tailwind's dark: prefix
5. **Add ARIA labels** to all icon-only buttons
6. **Use StepIndicator** for multi-step forms and wizards
7. **Test keyboard navigation** in all interactive components
8. **Maintain focus indicators** - never remove focus outlines without replacement

---

## Browser Support

All features are tested and supported in:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

Dark mode uses the standard `prefers-color-scheme` media query for system preference detection.

---

## Future Enhancements

Potential additions for future releases:
- Command palette (Cmd+K)
- Keyboard shortcut help modal
- Toast queue management with priorities
- More skeleton variants (breadcrumbs, headers, etc.)
- Animation preferences (respect prefers-reduced-motion)
- High contrast mode support

# Quick Reference Card

Fast reference for the new UI/UX features. Keep this handy while developing!

---

## Toast Notifications

```typescript
import { toast } from '@/store';

toast.success('Success message');
toast.error('Error message');
toast.warning('Warning message');
toast.info('Info message');

// Custom duration (default: 5000ms)
toast.success('Message', 3000);
```

---

## Skeleton Loaders

```typescript
import {
  TableSkeleton,
  CardSkeleton,
  FormSkeleton,
  PageSkeleton,
  StatCardSkeleton,
  TextSkeleton,
  ListItemSkeleton,
} from '@/components/common';

// In loading states
{isLoading ? <TableSkeleton rows={10} /> : <Table />}
{isLoading ? <CardSkeleton /> : <Card />}
{isLoading ? <PageSkeleton /> : <Content />}
```

---

## Keyboard Shortcuts

```typescript
import useKeyboardShortcuts, { commonShortcuts } from '@/hooks';

useKeyboardShortcuts([
  commonShortcuts.save(handleSave),        // Ctrl+S
  commonShortcuts.escape(handleClose),     // Esc
  commonShortcuts.delete(handleDelete),    // Delete
  commonShortcuts.search(handleSearch),    // Ctrl+K

  // Custom shortcut
  {
    key: 'p',
    ctrlKey: true,
    callback: handlePreview,
    description: 'Preview'
  }
]);
```

---

## Dark Mode

```typescript
import { useThemeStore } from '@/store';
import { ThemeToggle } from '@/components/common';

// Component
<ThemeToggle />
<ThemeToggle showLabel />
<ThemeToggle variant="dropdown" showLabel />

// Programmatic
const { theme, setTheme, toggleTheme } = useThemeStore();
setTheme('dark');    // or 'light' or 'system'
toggleTheme();       // toggle between light/dark

// In JSX - add dark: classes
<div className="bg-white dark:bg-slate-800">
  <h1 className="text-slate-900 dark:text-white">Title</h1>
</div>
```

---

## Step Indicator

```typescript
import { StepIndicator, CompactStepIndicator } from '@/components/common';

const steps = [
  { id: '1', label: 'Step 1', description: 'Optional' },
  { id: '2', label: 'Step 2' },
];

// Horizontal (default)
<StepIndicator
  steps={steps}
  currentStep={currentIndex}
  onStepClick={setCurrentIndex}
/>

// Vertical
<StepIndicator
  steps={steps}
  currentStep={currentIndex}
  variant="vertical"
/>

// Compact
<CompactStepIndicator steps={steps} currentStep={currentIndex} />
```

---

## Common Patterns

### Loading State with Skeleton

```typescript
const { data, isLoading } = useQuery({ ... });

if (isLoading) return <TableSkeleton rows={10} columns={6} />;
return <Table data={data} />;
```

### Save with Toast

```typescript
const mutation = useMutation({
  mutationFn: saveData,
  onSuccess: () => toast.success('Saved!'),
  onError: (err) => toast.error(err.message),
});
```

### Modal with Keyboard

```typescript
// Modal already supports Escape - no extra code needed!
<Modal isOpen={isOpen} onClose={handleClose}>
  {/* Escape key automatically closes */}
</Modal>
```

### Form with Shortcuts

```typescript
function Form() {
  useKeyboardShortcuts([
    commonShortcuts.save(handleSave),
    commonShortcuts.escape(handleCancel),
  ]);

  return <form>...</form>;
}
```

---

## Component Props Quick Ref

### ToastContainer
```typescript
// Auto-included in main.tsx - no props needed
<ToastContainer />
```

### TableSkeleton
```typescript
<TableSkeleton
  rows={10}          // number of rows
  columns={6}        // number of columns
  className=""       // optional
/>
```

### StepIndicator
```typescript
<StepIndicator
  steps={steps}              // array of { id, label, description? }
  currentStep={number}       // 0-based index
  onStepClick={(i) => ...}   // optional
  variant="horizontal"       // or "vertical"
  allowClickPrevious={true}  // default true
/>
```

### ThemeToggle
```typescript
<ThemeToggle
  variant="button"      // or "dropdown"
  showLabel={false}     // show text label
  className=""          // optional
/>
```

---

## Keyboard Shortcut Keys

### Built-in Common Shortcuts

| Shortcut | Function | Code |
|----------|----------|------|
| Ctrl+S | Save | `commonShortcuts.save(fn)` |
| Ctrl+S (Mac) | Save | `commonShortcuts.saveAlt(fn)` |
| Escape | Close/Cancel | `commonShortcuts.escape(fn)` |
| Delete | Delete | `commonShortcuts.delete(fn)` |
| Ctrl+Enter | Submit | `commonShortcuts.enter(fn)` |
| Ctrl+Z | Undo | `commonShortcuts.undo(fn)` |
| Ctrl+Y | Redo | `commonShortcuts.redo(fn)` |
| Ctrl+K | Search | `commonShortcuts.search(fn)` |
| Ctrl+R | Refresh | `commonShortcuts.refresh(fn)` |

### Modal Shortcuts

| Shortcut | Function |
|----------|----------|
| Escape | Close modal |
| Tab | Navigate forward |
| Shift+Tab | Navigate backward |

---

## Dark Mode Classes

### Common Patterns

```typescript
// Backgrounds
"bg-white dark:bg-slate-800"
"bg-slate-50 dark:bg-slate-900"
"bg-slate-100 dark:bg-slate-700"

// Text
"text-slate-900 dark:text-white"
"text-slate-700 dark:text-slate-300"
"text-slate-500 dark:text-slate-400"

// Borders
"border-slate-200 dark:border-slate-700"
"border-slate-300 dark:border-slate-600"

// Hover States
"hover:bg-slate-50 dark:hover:bg-slate-800"
"hover:text-slate-900 dark:hover:text-white"

// Buttons (use Button component - already has dark mode)
<Button variant="primary">Styled for dark mode</Button>
```

---

## Accessibility Quick Tips

### ARIA Labels for Icon Buttons

```typescript
<button onClick={fn} aria-label="Close">
  <X className="w-5 h-5" aria-hidden="true" />
</button>
```

### Focus Indicators

```typescript
// Already global - but can add custom:
className="focus:outline-none focus:ring-2 focus:ring-primary-500"
```

### Loading States

```typescript
// Skeleton has built-in aria-busy
<TableSkeleton /> // has aria-busy="true" aria-live="polite"
```

### Live Regions

```typescript
// Toasts automatically use aria-live="assertive"
toast.success('Message'); // Screen readers will announce
```

---

## Import Paths

```typescript
// Components
import {
  ToastContainer,
  SkeletonLoader, TableSkeleton, CardSkeleton,
  ThemeToggle,
  StepIndicator, CompactStepIndicator
} from '@/components/common';

// Stores
import {
  useToastStore, toast,
  useThemeStore
} from '@/store';

// Hooks
import useKeyboardShortcuts, {
  commonShortcuts,
  formatShortcut
} from '@/hooks';

import useTableKeyboardNavigation, {
  makeTableAccessible
} from '@/hooks';
```

---

## Common Mistakes to Avoid

❌ **Don't**
```typescript
// Don't use window.alert
alert('Success!');

// Don't forget dark mode classes on new components
<div className="bg-white">

// Don't add keyboard listeners manually
useEffect(() => {
  const handler = (e) => { ... };
  window.addEventListener('keydown', handler);
}, []);
```

✅ **Do**
```typescript
// Use toast instead
toast.success('Success!');

// Always add dark mode
<div className="bg-white dark:bg-slate-800">

// Use the hook
useKeyboardShortcuts([{ key: 's', ctrlKey: true, callback: fn }]);
```

---

## Performance Tips

1. **Toasts**: Auto-dismiss after duration - no cleanup needed
2. **Skeletons**: Use specific variants instead of generic loader
3. **Shortcuts**: Hooks auto-cleanup on unmount
4. **Theme**: Persisted in localStorage - no flash on load

---

## Browser DevTools

### Check Dark Mode
```javascript
// Console
document.documentElement.classList.contains('dark') // true/false
```

### Check Theme Store
```javascript
// Console (after opening app)
window.localStorage.getItem('peanut-theme-storage')
```

### Trigger Toast
```javascript
// Console
useToastStore.getState().addToast({ type: 'success', message: 'Test' })
```

---

## Need Help?

- 📖 Full docs: `UI_UX_IMPROVEMENTS.md`
- 💻 Example code: `EXAMPLE_INTEGRATION.tsx`
- 📋 Summary: `IMPLEMENTATION_SUMMARY.md`
- ⚡ This card: `QUICK_REFERENCE.md`

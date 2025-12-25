import { useEffect, useRef, useCallback } from 'react';

export interface KeyboardShortcut {
  key: string;
  ctrlKey?: boolean;
  shiftKey?: boolean;
  altKey?: boolean;
  metaKey?: boolean;
  callback: (event: KeyboardEvent) => void;
  description?: string;
  preventDefault?: boolean;
}

interface UseKeyboardShortcutsOptions {
  enabled?: boolean;
  enableOnFormElements?: boolean;
}

/**
 * Custom hook for registering keyboard shortcuts
 *
 * @example
 * useKeyboardShortcuts([
 *   { key: 's', ctrlKey: true, callback: handleSave, description: 'Save' },
 *   { key: 'Escape', callback: handleClose, description: 'Close modal' }
 * ]);
 */
export default function useKeyboardShortcuts(
  shortcuts: KeyboardShortcut[],
  options: UseKeyboardShortcutsOptions = {}
) {
  const { enabled = true, enableOnFormElements = false } = options;
  const shortcutsRef = useRef(shortcuts);

  // Update shortcuts ref when they change
  useEffect(() => {
    shortcutsRef.current = shortcuts;
  }, [shortcuts]);

  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      if (!enabled) return;

      // Check if the event originated from a form element
      const target = event.target as HTMLElement;
      const isFormElement =
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT' ||
        target.isContentEditable;

      // Skip if on form element and not explicitly enabled
      if (isFormElement && !enableOnFormElements) {
        // Allow Escape key even in form elements
        if (event.key !== 'Escape') {
          return;
        }
      }

      // Find matching shortcut
      const matchingShortcut = shortcutsRef.current.find((shortcut) => {
        const keyMatches = event.key.toLowerCase() === shortcut.key.toLowerCase();
        const ctrlMatches = shortcut.ctrlKey === undefined || shortcut.ctrlKey === event.ctrlKey;
        const shiftMatches = shortcut.shiftKey === undefined || shortcut.shiftKey === event.shiftKey;
        const altMatches = shortcut.altKey === undefined || shortcut.altKey === event.altKey;
        const metaMatches = shortcut.metaKey === undefined || shortcut.metaKey === event.metaKey;

        return keyMatches && ctrlMatches && shiftMatches && altMatches && metaMatches;
      });

      if (matchingShortcut) {
        if (matchingShortcut.preventDefault !== false) {
          event.preventDefault();
        }
        matchingShortcut.callback(event);
      }
    },
    [enabled, enableOnFormElements]
  );

  useEffect(() => {
    if (!enabled) return;

    window.addEventListener('keydown', handleKeyDown);

    return () => {
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [enabled, handleKeyDown]);
}

/**
 * Helper function to format keyboard shortcut for display
 */
export function formatShortcut(shortcut: KeyboardShortcut): string {
  const parts: string[] = [];

  if (shortcut.ctrlKey) parts.push('Ctrl');
  if (shortcut.shiftKey) parts.push('Shift');
  if (shortcut.altKey) parts.push('Alt');
  if (shortcut.metaKey) parts.push('Cmd');

  parts.push(shortcut.key.toUpperCase());

  return parts.join('+');
}

/**
 * Common keyboard shortcuts for reuse
 */
/**
 * Convenience hook for handling Escape key
 */
export function useEscapeKey(callback: () => void, enabled: boolean = true): void {
  useKeyboardShortcuts(
    [{ key: 'Escape', callback, description: 'Close/Cancel', preventDefault: false }],
    { enabled }
  );
}

export const commonShortcuts = {
  save: (callback: () => void): KeyboardShortcut => ({
    key: 's',
    ctrlKey: true,
    callback,
    description: 'Save',
  }),

  saveAlt: (callback: () => void): KeyboardShortcut => ({
    key: 's',
    metaKey: true,
    callback,
    description: 'Save',
  }),

  escape: (callback: () => void): KeyboardShortcut => ({
    key: 'Escape',
    callback,
    description: 'Close/Cancel',
    preventDefault: false,
  }),

  delete: (callback: () => void): KeyboardShortcut => ({
    key: 'Delete',
    callback,
    description: 'Delete',
  }),

  enter: (callback: () => void): KeyboardShortcut => ({
    key: 'Enter',
    ctrlKey: true,
    callback,
    description: 'Submit',
  }),

  undo: (callback: () => void): KeyboardShortcut => ({
    key: 'z',
    ctrlKey: true,
    callback,
    description: 'Undo',
  }),

  redo: (callback: () => void): KeyboardShortcut => ({
    key: 'y',
    ctrlKey: true,
    callback,
    description: 'Redo',
  }),

  search: (callback: () => void): KeyboardShortcut => ({
    key: 'k',
    ctrlKey: true,
    callback,
    description: 'Search',
  }),

  refresh: (callback: () => void): KeyboardShortcut => ({
    key: 'r',
    ctrlKey: true,
    callback,
    description: 'Refresh',
  }),
};

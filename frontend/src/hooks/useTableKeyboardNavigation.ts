import { useEffect, useRef, useCallback } from 'react';

interface UseTableKeyboardNavigationOptions {
  enabled?: boolean;
  onRowSelect?: (rowIndex: number) => void;
  onRowActivate?: (rowIndex: number) => void;
}

/**
 * Hook for adding keyboard navigation to tables
 *
 * Supports:
 * - Arrow keys to navigate between cells
 * - Enter to activate/select a row
 * - Home/End to jump to first/last cell in row
 * - Page Up/Down to jump up/down by 10 rows
 *
 * @example
 * const tableRef = useTableKeyboardNavigation({
 *   enabled: true,
 *   onRowActivate: (index) => handleRowClick(data[index])
 * });
 *
 * <table ref={tableRef} role="grid">...</table>
 */
export default function useTableKeyboardNavigation(
  options: UseTableKeyboardNavigationOptions = {}
) {
  const { enabled = true, onRowSelect, onRowActivate } = options;
  const tableRef = useRef<HTMLTableElement>(null);
  const currentCellRef = useRef<{ row: number; col: number }>({ row: 0, col: 0 });

  const getCells = useCallback(() => {
    if (!tableRef.current) return [];
    const tbody = tableRef.current.querySelector('tbody');
    if (!tbody) return [];

    return Array.from(tbody.querySelectorAll('tr')).map((row) =>
      Array.from(row.querySelectorAll('td, th'))
    );
  }, []);

  const focusCell = useCallback((row: number, col: number) => {
    const cells = getCells();
    if (!cells[row] || !cells[row][col]) return;

    const cell = cells[row][col] as HTMLElement;

    // Find focusable element within cell (button, link, etc.)
    const focusable = cell.querySelector<HTMLElement>(
      'button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    if (focusable) {
      focusable.focus();
    } else {
      // Make cell focusable if no focusable children
      cell.setAttribute('tabindex', '0');
      cell.focus();
    }

    currentCellRef.current = { row, col };

    // Call onRowSelect if row changed
    if (onRowSelect && currentCellRef.current.row !== row) {
      onRowSelect(row);
    }
  }, [getCells, onRowSelect]);

  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      if (!enabled || !tableRef.current) return;

      const cells = getCells();
      if (cells.length === 0) return;

      const { row, col } = currentCellRef.current;
      const maxRow = cells.length - 1;
      const maxCol = cells[row]?.length - 1 || 0;

      let handled = false;

      switch (event.key) {
        case 'ArrowUp':
          if (row > 0) {
            focusCell(row - 1, col);
            handled = true;
          }
          break;

        case 'ArrowDown':
          if (row < maxRow) {
            focusCell(row + 1, col);
            handled = true;
          }
          break;

        case 'ArrowLeft':
          if (col > 0) {
            focusCell(row, col - 1);
            handled = true;
          }
          break;

        case 'ArrowRight':
          if (col < maxCol) {
            focusCell(row, col + 1);
            handled = true;
          }
          break;

        case 'Home':
          focusCell(row, 0);
          handled = true;
          break;

        case 'End':
          focusCell(row, maxCol);
          handled = true;
          break;

        case 'PageUp':
          focusCell(Math.max(0, row - 10), col);
          handled = true;
          break;

        case 'PageDown':
          focusCell(Math.min(maxRow, row + 10), col);
          handled = true;
          break;

        case 'Enter':
        case ' ':
          if (onRowActivate) {
            onRowActivate(row);
            handled = true;
          }
          break;
      }

      if (handled) {
        event.preventDefault();
      }
    },
    [enabled, getCells, focusCell, onRowActivate]
  );

  useEffect(() => {
    const table = tableRef.current;
    if (!enabled || !table) return;

    table.addEventListener('keydown', handleKeyDown);

    // Set up initial focus on first cell
    const cells = getCells();
    if (cells.length > 0 && cells[0].length > 0) {
      const firstCell = cells[0][0] as HTMLElement;
      firstCell.setAttribute('tabindex', '0');
    }

    return () => {
      table.removeEventListener('keydown', handleKeyDown);
    };
  }, [enabled, handleKeyDown, getCells]);

  return tableRef;
}

/**
 * Helper function to make table rows keyboard-navigable
 */
export function makeTableAccessible(tableElement: HTMLTableElement) {
  // Add ARIA attributes
  tableElement.setAttribute('role', 'grid');
  tableElement.setAttribute('aria-label', 'Data table with keyboard navigation');

  const tbody = tableElement.querySelector('tbody');
  if (tbody) {
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, index) => {
      row.setAttribute('role', 'row');
      row.setAttribute('aria-rowindex', String(index + 1));

      const cells = row.querySelectorAll('td, th');
      cells.forEach((cell, cellIndex) => {
        cell.setAttribute('role', 'gridcell');
        cell.setAttribute('aria-colindex', String(cellIndex + 1));
      });
    });
  }

  const thead = tableElement.querySelector('thead');
  if (thead) {
    const headerCells = thead.querySelectorAll('th');
    headerCells.forEach((cell, index) => {
      cell.setAttribute('role', 'columnheader');
      cell.setAttribute('aria-colindex', String(index + 1));
    });
  }
}

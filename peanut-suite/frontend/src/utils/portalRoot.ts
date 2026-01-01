/**
 * Portal Root Manager
 *
 * Creates a dedicated container element for React portals to prevent
 * "Failed to execute 'removeChild' on 'Node'" errors that occur when
 * WordPress admin scripts or browser extensions manipulate the DOM.
 */

let portalRoot: HTMLElement | null = null;

export function getPortalRoot(): HTMLElement {
  if (!portalRoot) {
    // Check if it already exists (e.g., from a previous mount)
    portalRoot = document.getElementById('peanut-portal-root');

    if (!portalRoot) {
      // Create the portal container inside the React app root
      // This prevents conflicts with WordPress admin scripts that manipulate document.body
      const appRoot = document.getElementById('peanut-app');
      const parent = appRoot || document.body;

      portalRoot = document.createElement('div');
      portalRoot.id = 'peanut-portal-root';
      portalRoot.style.position = 'relative';
      portalRoot.style.zIndex = '99999';
      parent.appendChild(portalRoot);
    }
  }

  return portalRoot;
}

/**
 * Cleanup function - removes the portal root when no longer needed
 * Call this when unmounting the entire application
 */
export function cleanupPortalRoot(): void {
  if (portalRoot && portalRoot.parentNode) {
    portalRoot.parentNode.removeChild(portalRoot);
    portalRoot = null;
  }
}

/**
 * Peanut Accessibility Widget
 */
(function() {
    'use strict';

    const STORAGE_KEY = 'peanut_a11y_preferences';

    // Default preferences
    let preferences = {
        fontSize: 100,
        contrast: 'normal',
        highlightLinks: false,
        readableFont: false,
        focusMode: false,
        pauseAnimations: false,
        bigCursor: false,
        textSpacing: false
    };

    // Load saved preferences
    function loadPreferences() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                preferences = { ...preferences, ...JSON.parse(saved) };
            }
        } catch (e) {
            console.warn('Could not load accessibility preferences');
        }
    }

    // Save preferences
    function savePreferences() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        } catch (e) {
            console.warn('Could not save accessibility preferences');
        }
    }

    // Apply all preferences
    function applyPreferences() {
        const body = document.body;

        // Font size
        document.documentElement.style.fontSize = preferences.fontSize + '%';

        // Contrast
        body.classList.remove('peanut-high-contrast', 'peanut-inverted');
        if (preferences.contrast === 'high') {
            body.classList.add('peanut-high-contrast');
        } else if (preferences.contrast === 'invert') {
            body.classList.add('peanut-inverted');
        }

        // Toggle classes
        body.classList.toggle('peanut-highlight-links', preferences.highlightLinks);
        body.classList.toggle('peanut-readable-font', preferences.readableFont);
        body.classList.toggle('peanut-focus-mode', preferences.focusMode);
        body.classList.toggle('peanut-pause-animations', preferences.pauseAnimations);
        body.classList.toggle('peanut-big-cursor', preferences.bigCursor);
        body.classList.toggle('peanut-text-spacing', preferences.textSpacing);

        // Update UI
        updateUI();
    }

    // Update widget UI to match preferences
    function updateUI() {
        const panel = document.querySelector('.peanut-a11y-panel');
        if (!panel) return;

        // Update contrast buttons
        panel.querySelectorAll('[data-action^="contrast-"]').forEach(btn => {
            const mode = btn.dataset.action.replace('contrast-', '');
            btn.classList.toggle('active', preferences.contrast === mode);
        });

        // Update checkboxes
        const checkboxMap = {
            'highlight-links': 'highlightLinks',
            'readable-font': 'readableFont',
            'focus-mode': 'focusMode',
            'pause-animations': 'pauseAnimations',
            'big-cursor': 'bigCursor',
            'text-spacing': 'textSpacing'
        };

        Object.entries(checkboxMap).forEach(([action, pref]) => {
            const checkbox = panel.querySelector(`[data-action="${action}"]`);
            if (checkbox) {
                checkbox.checked = preferences[pref];
            }
        });
    }

    // Handle actions
    function handleAction(action) {
        switch (action) {
            case 'font-increase':
                preferences.fontSize = Math.min(150, preferences.fontSize + 10);
                break;
            case 'font-decrease':
                preferences.fontSize = Math.max(80, preferences.fontSize - 10);
                break;
            case 'font-reset':
                preferences.fontSize = 100;
                break;
            case 'contrast-normal':
                preferences.contrast = 'normal';
                break;
            case 'contrast-high':
                preferences.contrast = 'high';
                break;
            case 'contrast-invert':
                preferences.contrast = 'invert';
                break;
            case 'highlight-links':
                preferences.highlightLinks = !preferences.highlightLinks;
                break;
            case 'readable-font':
                preferences.readableFont = !preferences.readableFont;
                break;
            case 'focus-mode':
                preferences.focusMode = !preferences.focusMode;
                break;
            case 'pause-animations':
                preferences.pauseAnimations = !preferences.pauseAnimations;
                break;
            case 'big-cursor':
                preferences.bigCursor = !preferences.bigCursor;
                break;
            case 'text-spacing':
                preferences.textSpacing = !preferences.textSpacing;
                break;
            case 'reset-all':
                preferences = {
                    fontSize: 100,
                    contrast: 'normal',
                    highlightLinks: false,
                    readableFont: false,
                    focusMode: false,
                    pauseAnimations: false,
                    bigCursor: false,
                    textSpacing: false
                };
                break;
        }

        savePreferences();
        applyPreferences();
    }

    // Initialize widget
    function init() {
        loadPreferences();
        applyPreferences();

        const widget = document.getElementById('peanut-a11y-widget');
        if (!widget) return;

        const toggle = widget.querySelector('.peanut-a11y-toggle');
        const panel = widget.querySelector('.peanut-a11y-panel');
        const closeBtn = widget.querySelector('.peanut-a11y-close');

        // Toggle panel
        toggle.addEventListener('click', () => {
            const isOpen = panel.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen);

            if (isOpen) {
                // Focus first interactive element
                const firstButton = panel.querySelector('button, input');
                if (firstButton) firstButton.focus();
            }
        });

        // Close button
        closeBtn.addEventListener('click', () => {
            panel.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        });

        // Handle all actions
        widget.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            if (action) {
                handleAction(action);
            }
        });

        // Handle checkbox changes
        widget.addEventListener('change', (e) => {
            const action = e.target.dataset.action;
            if (action) {
                handleAction(action);
            }
        });

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && panel.classList.contains('open')) {
                panel.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!widget.contains(e.target) && panel.classList.contains('open')) {
                panel.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Trap focus in panel when open
        panel.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            const focusable = panel.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
            const firstFocusable = focusable[0];
            const lastFocusable = focusable[focusable.length - 1];

            if (e.shiftKey && document.activeElement === firstFocusable) {
                e.preventDefault();
                lastFocusable.focus();
            } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                e.preventDefault();
                firstFocusable.focus();
            }
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

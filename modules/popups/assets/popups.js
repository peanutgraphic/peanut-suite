/**
 * Peanut Popups Frontend Engine
 *
 * Handles popup display triggers, animations, and form submissions.
 * Enhanced with advanced exit intent, scroll depth, and engagement triggers.
 */

(function() {
    'use strict';

    // Exit if no popups data
    if (typeof peanutPopups === 'undefined' || !peanutPopups.popups) {
        return;
    }

    const { ajaxUrl, nonce, popups } = peanutPopups;

    // Track shown popups to prevent duplicates
    const shownPopups = new Set();

    // Page view counter for page_views trigger
    let pageViews = parseInt(localStorage.getItem('peanut_page_views') || '0', 10) + 1;
    localStorage.setItem('peanut_page_views', pageViews.toString());

    // Engagement tracking
    const engagement = {
        startTime: Date.now(),
        maxScroll: 0,
        clicks: 0,
        hasScrolled: false,
        mousePosition: { x: 0, y: 0 },
        mouseVelocity: { x: 0, y: 0 },
        lastMouseTime: Date.now()
    };

    // Track engagement metrics
    function trackEngagement() {
        // Track clicks
        document.addEventListener('click', () => {
            engagement.clicks++;
        }, { passive: true });

        // Track scroll depth
        window.addEventListener('scroll', () => {
            engagement.hasScrolled = true;
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (window.scrollY / scrollHeight) * 100;
            engagement.maxScroll = Math.max(engagement.maxScroll, scrollPercent);
        }, { passive: true });

        // Track mouse position and velocity (for exit intent)
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            const dt = now - engagement.lastMouseTime;

            if (dt > 0) {
                engagement.mouseVelocity.x = (e.clientX - engagement.mousePosition.x) / dt;
                engagement.mouseVelocity.y = (e.clientY - engagement.mousePosition.y) / dt;
            }

            engagement.mousePosition.x = e.clientX;
            engagement.mousePosition.y = e.clientY;
            engagement.lastMouseTime = now;
        }, { passive: true });
    }

    /**
     * Initialize popup triggers
     */
    function init() {
        trackEngagement();

        popups.forEach(popup => {
            setupTrigger(popup);
        });

        // Setup close handlers
        setupCloseHandlers();

        // Setup form handlers
        setupFormHandlers();
    }

    /**
     * Setup trigger for a popup
     */
    function setupTrigger(popup) {
        const trigger = popup.triggers;
        if (!trigger || !trigger.type) return;

        switch (trigger.type) {
            case 'time_delay':
                setTimeout(() => showPopup(popup.id), trigger.delay || 5000);
                break;

            case 'time_on_page':
                setupTimeOnPageTrigger(popup.id, trigger);
                break;

            case 'scroll_percent':
                setupScrollPercentTrigger(popup.id, trigger.percent || 50);
                break;

            case 'scroll_depth':
                setupScrollDepthTrigger(popup.id, trigger);
                break;

            case 'scroll_element':
                setupScrollElementTrigger(popup.id, trigger.selector, trigger.offset || 0);
                break;

            case 'exit_intent':
                setupExitIntentTrigger(popup.id, trigger);
                break;

            case 'aggressive_exit':
                setupAggressiveExitTrigger(popup.id, trigger);
                break;

            case 'click':
                setupClickTrigger(popup.id, trigger.selector);
                break;

            case 'page_views':
                if (pageViews >= (trigger.count || 3)) {
                    setTimeout(() => showPopup(popup.id), 1000);
                }
                break;

            case 'inactivity':
                setupInactivityTrigger(popup.id, trigger.timeout || 30000);
                break;

            case 'engagement':
                setupEngagementTrigger(popup.id, trigger);
                break;
        }
    }

    /**
     * Setup time on page trigger
     */
    function setupTimeOnPageTrigger(popupId, trigger) {
        const minTime = trigger.minTime || 30000;
        const requireScroll = trigger.requireScroll || false;
        const requireEngagement = trigger.requireEngagement || false;

        function checkConditions() {
            const timeSpent = Date.now() - engagement.startTime;

            if (timeSpent < minTime) return false;
            if (requireScroll && !engagement.hasScrolled) return false;
            if (requireEngagement && engagement.clicks === 0 && !engagement.hasScrolled) return false;

            return true;
        }

        // Check periodically
        const interval = setInterval(() => {
            if (checkConditions()) {
                clearInterval(interval);
                showPopup(popupId);
            }
        }, 1000);
    }

    /**
     * Setup scroll percentage trigger
     */
    function setupScrollPercentTrigger(popupId, percent) {
        let triggered = false;

        function checkScroll() {
            if (triggered) return;

            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (window.scrollY / scrollHeight) * 100;

            if (scrollPercent >= percent) {
                triggered = true;
                showPopup(popupId);
                window.removeEventListener('scroll', checkScroll);
            }
        }

        window.addEventListener('scroll', checkScroll, { passive: true });
    }

    /**
     * Setup advanced scroll depth trigger
     */
    function setupScrollDepthTrigger(popupId, trigger) {
        let triggered = false;
        let lastScrollY = window.scrollY;
        let scrollStartTime = 0;
        let scrollPauseTimer = null;

        const targetPercent = trigger.percent || 50;
        const direction = trigger.direction || 'down';
        const minTime = trigger.minTime || 0;
        const requireStop = trigger.requireStop || false;

        function checkScroll() {
            if (triggered) return;

            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const currentPercent = (window.scrollY / scrollHeight) * 100;
            const scrollDirection = window.scrollY > lastScrollY ? 'down' : 'up';

            lastScrollY = window.scrollY;

            // Check direction
            if (direction !== 'both' && direction !== scrollDirection) {
                return;
            }

            // Check if we've reached the target
            if (currentPercent >= targetPercent) {
                // Check time requirement
                const timeSpent = Date.now() - engagement.startTime;
                if (minTime > 0 && timeSpent < minTime) {
                    return;
                }

                // Check if we need to wait for scroll pause
                if (requireStop) {
                    clearTimeout(scrollPauseTimer);
                    scrollPauseTimer = setTimeout(() => {
                        triggered = true;
                        showPopup(popupId);
                    }, 500);
                } else {
                    triggered = true;
                    showPopup(popupId);
                    window.removeEventListener('scroll', checkScroll);
                }
            }
        }

        window.addEventListener('scroll', checkScroll, { passive: true });
    }

    /**
     * Setup scroll to element trigger
     */
    function setupScrollElementTrigger(popupId, selector, offset) {
        if (!selector) return;

        let triggered = false;
        const element = document.querySelector(selector);

        if (!element) return;

        function checkScroll() {
            if (triggered) return;

            const rect = element.getBoundingClientRect();
            const triggerPoint = window.innerHeight - offset;

            if (rect.top <= triggerPoint) {
                triggered = true;
                showPopup(popupId);
                window.removeEventListener('scroll', checkScroll);
            }
        }

        window.addEventListener('scroll', checkScroll, { passive: true });
    }

    /**
     * Setup exit intent trigger with mobile support
     */
    function setupExitIntentTrigger(popupId, trigger) {
        let triggered = false;
        let delayTimer = null;

        const sensitivity = trigger.sensitivity || 20;
        const delay = trigger.delay || 0;
        const mobileEnabled = trigger.mobileEnabled !== false;

        // Desktop: Mouse leave detection
        function handleMouseLeave(e) {
            if (triggered) return;

            // Only trigger when mouse leaves through top of page
            if (e.clientY <= sensitivity) {
                // Check for fast upward mouse movement (more accurate detection)
                const movingUp = engagement.mouseVelocity.y < -0.5;

                if (movingUp || e.clientY <= 5) {
                    if (delay > 0) {
                        delayTimer = setTimeout(() => {
                            triggered = true;
                            showPopup(popupId);
                        }, delay);
                    } else {
                        triggered = true;
                        showPopup(popupId);
                    }
                }
            }
        }

        function handleMouseEnter() {
            if (delayTimer) {
                clearTimeout(delayTimer);
                delayTimer = null;
            }
        }

        document.addEventListener('mouseleave', handleMouseLeave);
        document.addEventListener('mouseenter', handleMouseEnter);

        // Mobile: Various exit signals
        if (mobileEnabled && isMobile()) {
            // Visibility change (switching apps/tabs)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden' && !triggered) {
                    triggered = true;
                    showPopup(popupId);
                }
            });

            // Back button detection
            window.addEventListener('popstate', () => {
                if (!triggered) {
                    triggered = true;
                    showPopup(popupId);
                    // Re-push state to prevent actual navigation
                    history.pushState(null, '', location.href);
                }
            });

            // Add a state to detect back button
            history.pushState(null, '', location.href);

            // Orientation change (often precedes closing)
            window.addEventListener('orientationchange', () => {
                // Small delay - orientation change sometimes means switching apps
                setTimeout(() => {
                    if (document.visibilityState === 'hidden' && !triggered) {
                        triggered = true;
                        showPopup(popupId);
                    }
                }, 100);
            });
        }
    }

    /**
     * Setup aggressive exit detection (catches more exit attempts)
     */
    function setupAggressiveExitTrigger(popupId, trigger) {
        let triggered = false;

        const sensitivity = trigger.sensitivity || 10;
        const delay = trigger.delay || 0;
        const trackTabs = trigger.trackTabs !== false;
        const trackBack = trigger.trackBack !== false;
        const trackIdle = trigger.trackIdle || false;
        const idleTimeout = trigger.idleTimeout || 60000;

        // Mouse leaving top of page
        document.addEventListener('mouseleave', (e) => {
            if (triggered) return;
            if (e.clientY <= sensitivity) {
                triggerWithDelay();
            }
        });

        // Mouse moving fast toward top
        document.addEventListener('mousemove', (e) => {
            if (triggered) return;

            // Fast upward movement near top
            if (e.clientY < 100 && engagement.mouseVelocity.y < -1) {
                triggerWithDelay();
            }
        }, { passive: true });

        // Tab/window switching
        if (trackTabs) {
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden' && !triggered) {
                    triggerWithDelay();
                }
            });

            window.addEventListener('blur', () => {
                if (!triggered) {
                    triggerWithDelay();
                }
            });
        }

        // Back button
        if (trackBack) {
            history.pushState(null, '', location.href);
            window.addEventListener('popstate', () => {
                if (!triggered) {
                    triggered = true;
                    showPopup(popupId);
                    history.pushState(null, '', location.href);
                }
            });
        }

        // Idle detection
        if (trackIdle) {
            let idleTimer;

            function resetIdleTimer() {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(() => {
                    if (!triggered) {
                        triggerWithDelay();
                    }
                }, idleTimeout);
            }

            ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(event => {
                document.addEventListener(event, resetIdleTimer, { passive: true });
            });

            resetIdleTimer();
        }

        function triggerWithDelay() {
            if (delay > 0) {
                setTimeout(() => {
                    if (!triggered) {
                        triggered = true;
                        showPopup(popupId);
                    }
                }, delay);
            } else {
                triggered = true;
                showPopup(popupId);
            }
        }
    }

    /**
     * Setup click trigger
     */
    function setupClickTrigger(popupId, selector) {
        if (!selector) return;

        document.addEventListener('click', (e) => {
            if (e.target.matches(selector) || e.target.closest(selector)) {
                e.preventDefault();
                showPopup(popupId);
            }
        });
    }

    /**
     * Setup inactivity trigger
     */
    function setupInactivityTrigger(popupId, timeout) {
        let timer;

        function resetTimer() {
            clearTimeout(timer);
            timer = setTimeout(() => showPopup(popupId), timeout);
        }

        ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetTimer, { passive: true });
        });

        resetTimer();
    }

    /**
     * Setup engagement-based trigger
     */
    function setupEngagementTrigger(popupId, trigger) {
        const minScrollPercent = trigger.minScrollPercent || 25;
        const minTime = trigger.minTime || 15000;
        const minClicks = trigger.minClicks || 0;

        function checkEngagement() {
            const timeSpent = Date.now() - engagement.startTime;

            if (timeSpent < minTime) return false;
            if (engagement.maxScroll < minScrollPercent) return false;
            if (engagement.clicks < minClicks) return false;

            return true;
        }

        // Check periodically
        const interval = setInterval(() => {
            if (checkEngagement()) {
                clearInterval(interval);
                showPopup(popupId);
            }
        }, 2000);
    }

    /**
     * Show a popup
     */
    function showPopup(popupId) {
        if (shownPopups.has(popupId)) return;

        const popup = document.getElementById(`peanut-popup-${popupId}`);
        if (!popup) return;

        shownPopups.add(popupId);

        // Show popup with animation
        popup.style.display = '';
        requestAnimationFrame(() => {
            popup.classList.add('peanut-popup-visible');
        });

        // Track view
        trackInteraction(popupId, 'view');

        // Prevent body scroll for modals and fullscreen
        if (popup.classList.contains('peanut-popup-modal') ||
            popup.classList.contains('peanut-popup-fullscreen')) {
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Hide a popup
     */
    function hidePopup(popupId, reason = 'dismiss') {
        const popup = document.getElementById(`peanut-popup-${popupId}`);
        if (!popup) return;

        popup.classList.remove('peanut-popup-visible');

        // Wait for animation
        setTimeout(() => {
            popup.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);

        // Track dismissal
        if (reason === 'dismiss') {
            trackInteraction(popupId, 'dismiss');
        }
    }

    /**
     * Setup close handlers
     */
    function setupCloseHandlers() {
        // Close button
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('peanut-popup-close') ||
                e.target.closest('.peanut-popup-close')) {
                const popup = e.target.closest('.peanut-popup');
                if (popup) {
                    const popupId = popup.dataset.popupId;
                    hidePopup(popupId);
                }
            }
        });

        // Overlay click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('peanut-popup-overlay')) {
                const popup = e.target.closest('.peanut-popup');
                const settings = getPopupSettings(popup.dataset.popupId);

                if (settings.close_on_overlay !== false) {
                    hidePopup(popup.dataset.popupId);
                }
            }
        });

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const visiblePopup = document.querySelector('.peanut-popup-visible');
                if (visiblePopup) {
                    const settings = getPopupSettings(visiblePopup.dataset.popupId);

                    if (settings.close_on_esc !== false) {
                        hidePopup(visiblePopup.dataset.popupId);
                    }
                }
            }
        });
    }

    /**
     * Setup form handlers
     */
    function setupFormHandlers() {
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('peanut-popup-form')) {
                e.preventDefault();
                handleFormSubmit(e.target);
            }
        });
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmit(form) {
        const popupId = form.dataset.popupId;
        const popup = document.getElementById(`peanut-popup-${popupId}`);
        const button = form.querySelector('.peanut-popup-button');
        const successDiv = popup.querySelector('.peanut-popup-success');

        // Collect form data
        const formData = {};
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.name) {
                if (input.type === 'checkbox') {
                    formData[input.name] = input.checked;
                } else {
                    formData[input.name] = input.value;
                }
            }
        });

        // Disable button
        button.disabled = true;
        button.textContent = 'Sending...';

        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'peanut_popup_convert',
                    nonce: nonce,
                    popup_id: popupId,
                    'form_data': JSON.stringify(formData),
                }),
            });

            const result = await response.json();

            if (result.success) {
                // Show success message
                form.style.display = 'none';
                if (successDiv) {
                    successDiv.style.display = '';
                }

                // Close after delay
                setTimeout(() => {
                    hidePopup(popupId, 'convert');
                }, 3000);
            } else {
                throw new Error(result.data || 'Submission failed');
            }
        } catch (error) {
            console.error('Popup form error:', error);
            button.disabled = false;
            button.textContent = 'Try Again';
        }
    }

    /**
     * Track popup interaction
     */
    function trackInteraction(popupId, action) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: `peanut_popup_${action}`,
                nonce: nonce,
                popup_id: popupId,
            }),
        }).catch(console.error);
    }

    /**
     * Get popup settings
     */
    function getPopupSettings(popupId) {
        const popup = popups.find(p => p.id == popupId);
        return popup?.settings || {};
    }

    /**
     * Check if device is mobile
     */
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

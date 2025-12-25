/**
 * Peanut Suite Feature Tour
 *
 * Displays a floating card that highlights new features in v2.4.0
 *
 * @package Peanut_Suite
 * @since 2.4.0
 */

(function($) {
    'use strict';

    class PeanutFeatureTour {
        constructor() {
            this.currentStep = 0;
            this.isVisible = false;
            this.tourCompleted = false;

            // Define the new features to highlight
            this.features = [
                {
                    id: 'keyboard-shortcuts',
                    title: 'Keyboard Shortcuts',
                    description: 'Work faster with Ctrl+S to save, Escape to close modals, and arrow keys to navigate. Press Ctrl+K to open quick search.',
                    selector: null, // Global feature, no specific element
                    position: 'center',
                    icon: '⌨️'
                },
                {
                    id: 'dark-mode',
                    title: 'Dark Mode',
                    description: 'Reduce eye strain with our new dark theme. Click the moon icon in the header to toggle between light and dark modes.',
                    selector: '#peanut-theme-toggle',
                    position: 'bottom',
                    icon: '🌙'
                },
                {
                    id: 'collapsible-sidebar',
                    title: 'Collapsible Sidebar',
                    description: 'Need more space? Click the arrow on the left side of the screen to collapse the WordPress sidebar. Hover to expand it again.',
                    selector: '#peanut-sidebar-toggle',
                    position: 'right',
                    icon: '📐'
                },
                {
                    id: 'empty-states',
                    title: 'Helpful Empty States',
                    description: 'When there\'s no data to show, you\'ll now see helpful messages and quick actions to get started.',
                    selector: null,
                    position: 'center',
                    icon: '📭'
                },
                {
                    id: 'mobile-menu',
                    title: 'Mobile-Friendly Navigation',
                    description: 'Access Peanut Suite on any device. The new responsive menu adapts perfectly to tablets and phones.',
                    selector: null,
                    position: 'center',
                    icon: '📱'
                },
                {
                    id: 'accessibility',
                    title: 'Improved Accessibility',
                    description: 'Skip-to-content links, better focus indicators, and screen reader support make Peanut Suite accessible to everyone.',
                    selector: null,
                    position: 'center',
                    icon: '♿'
                }
            ];

            this.init();
        }

        init() {
            // Check if tour was already completed
            this.tourCompleted = localStorage.getItem('peanut_feature_tour_2_4_0') === 'completed';

            if (!this.tourCompleted) {
                this.createTourElements();
                this.attachEvents();

                // Show tour after a short delay
                setTimeout(() => this.show(), 1500);
            }
        }

        createTourElements() {
            // Create overlay
            this.$overlay = $('<div class="peanut-tour-overlay"></div>');

            // Create spotlight (highlight circle)
            this.$spotlight = $('<div class="peanut-tour-spotlight"></div>');

            // Create the floating card
            this.$card = $(`
                <div class="peanut-tour-card" role="dialog" aria-labelledby="peanut-tour-title" aria-modal="true">
                    <button class="peanut-tour-close" aria-label="Close tour">&times;</button>
                    <div class="peanut-tour-badge">New in v2.4.0</div>
                    <div class="peanut-tour-icon"></div>
                    <h3 class="peanut-tour-title" id="peanut-tour-title"></h3>
                    <p class="peanut-tour-description"></p>
                    <div class="peanut-tour-footer">
                        <div class="peanut-tour-dots"></div>
                        <div class="peanut-tour-buttons">
                            <button class="peanut-tour-skip">Skip Tour</button>
                            <button class="peanut-tour-next">Next</button>
                        </div>
                    </div>
                    <div class="peanut-tour-arrow"></div>
                </div>
            `);

            // Create dots
            this.features.forEach((_, index) => {
                this.$card.find('.peanut-tour-dots').append(
                    `<span class="peanut-tour-dot ${index === 0 ? 'active' : ''}" data-step="${index}"></span>`
                );
            });

            // Append to body
            $('body').append(this.$overlay, this.$spotlight, this.$card);
        }

        attachEvents() {
            const self = this;

            // Next button
            this.$card.on('click', '.peanut-tour-next', () => {
                if (this.currentStep < this.features.length - 1) {
                    this.goToStep(this.currentStep + 1);
                } else {
                    this.complete();
                }
            });

            // Skip button
            this.$card.on('click', '.peanut-tour-skip', () => {
                this.complete();
            });

            // Close button
            this.$card.on('click', '.peanut-tour-close', () => {
                this.complete();
            });

            // Dot navigation
            this.$card.on('click', '.peanut-tour-dot', function() {
                self.goToStep($(this).data('step'));
            });

            // Keyboard navigation
            $(document).on('keydown.peanutTour', (e) => {
                if (!this.isVisible) return;

                if (e.key === 'Escape') {
                    this.complete();
                } else if (e.key === 'ArrowRight' || e.key === 'Enter') {
                    if (this.currentStep < this.features.length - 1) {
                        this.goToStep(this.currentStep + 1);
                    } else {
                        this.complete();
                    }
                } else if (e.key === 'ArrowLeft') {
                    if (this.currentStep > 0) {
                        this.goToStep(this.currentStep - 1);
                    }
                }
            });

            // Handle window resize
            $(window).on('resize.peanutTour', () => {
                if (this.isVisible) {
                    this.positionCard();
                }
            });
        }

        show() {
            this.isVisible = true;
            this.$overlay.addClass('visible');
            this.goToStep(0);
        }

        hide() {
            this.isVisible = false;
            this.$overlay.removeClass('visible');
            this.$spotlight.removeClass('visible');
            this.$card.removeClass('visible');
        }

        goToStep(step) {
            this.currentStep = step;
            const feature = this.features[step];

            // Update card content
            this.$card.find('.peanut-tour-icon').text(feature.icon);
            this.$card.find('.peanut-tour-title').text(feature.title);
            this.$card.find('.peanut-tour-description').text(feature.description);

            // Update dots
            this.$card.find('.peanut-tour-dot').removeClass('active');
            this.$card.find(`.peanut-tour-dot[data-step="${step}"]`).addClass('active');

            // Update button text
            if (step === this.features.length - 1) {
                this.$card.find('.peanut-tour-next').text('Got It!');
            } else {
                this.$card.find('.peanut-tour-next').text('Next');
            }

            // Position the card
            this.positionCard();

            // Show the card
            this.$card.addClass('visible');
        }

        positionCard() {
            const feature = this.features[this.currentStep];
            const $target = feature.selector ? $(feature.selector).first() : null;
            const cardWidth = 340;
            const cardHeight = this.$card.outerHeight() || 280;
            const padding = 20;
            const arrowSize = 12;

            // Remove all position classes
            this.$card.removeClass('position-top position-bottom position-left position-right position-center');
            this.$spotlight.removeClass('visible');

            if ($target && $target.length && $target.is(':visible')) {
                // Position relative to target element
                const targetRect = $target[0].getBoundingClientRect();
                const targetCenterX = targetRect.left + targetRect.width / 2;
                const targetCenterY = targetRect.top + targetRect.height / 2;

                // Show and position spotlight
                this.$spotlight.addClass('visible').css({
                    left: targetRect.left - 10,
                    top: targetRect.top - 10,
                    width: targetRect.width + 20,
                    height: targetRect.height + 20
                });

                // Calculate card position based on preference and available space
                let left, top;
                const position = feature.position || 'bottom';

                switch (position) {
                    case 'top':
                        left = targetCenterX - cardWidth / 2;
                        top = targetRect.top - cardHeight - arrowSize - padding;
                        this.$card.addClass('position-top');
                        break;
                    case 'bottom':
                        left = targetCenterX - cardWidth / 2;
                        top = targetRect.bottom + arrowSize + padding;
                        this.$card.addClass('position-bottom');
                        break;
                    case 'left':
                        left = targetRect.left - cardWidth - arrowSize - padding;
                        top = targetCenterY - cardHeight / 2;
                        this.$card.addClass('position-left');
                        break;
                    case 'right':
                        left = targetRect.right + arrowSize + padding;
                        top = targetCenterY - cardHeight / 2;
                        this.$card.addClass('position-right');
                        break;
                }

                // Keep card within viewport
                left = Math.max(padding, Math.min(left, window.innerWidth - cardWidth - padding));
                top = Math.max(padding, Math.min(top, window.innerHeight - cardHeight - padding));

                this.$card.css({ left, top });

            } else {
                // Center the card if no target or target not visible
                this.$card.addClass('position-center').css({
                    left: (window.innerWidth - cardWidth) / 2,
                    top: (window.innerHeight - cardHeight) / 2
                });
            }
        }

        complete() {
            this.hide();
            this.tourCompleted = true;
            localStorage.setItem('peanut_feature_tour_2_4_0', 'completed');

            // Also save to user meta via AJAX
            $.post(ajaxurl, {
                action: 'peanut_complete_feature_tour',
                version: '2.4.0',
                nonce: peanutTour.nonce || ''
            });

            // Cleanup
            $(document).off('.peanutTour');
            $(window).off('.peanutTour');

            // Remove elements after animation
            setTimeout(() => {
                this.$overlay.remove();
                this.$spotlight.remove();
                this.$card.remove();
            }, 300);
        }

        // Static method to reset tour (for testing)
        static reset() {
            localStorage.removeItem('peanut_feature_tour_2_4_0');
            location.reload();
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Only show on Peanut Suite admin pages
        if ($('body').hasClass('toplevel_page_peanut') ||
            $('body').is('[class*="peanut"]') ||
            $('#peanut-app').length ||
            $('.peanut-admin-wrap').length) {

            window.peanutFeatureTour = new PeanutFeatureTour();
        }
    });

    // Expose reset function for testing
    window.resetPeanutTour = PeanutFeatureTour.reset;

})(jQuery);

/**
 * Peanut Suite Admin JavaScript
 */

(function($) {
    'use strict';

    // Global Peanut Admin object
    window.PeanutAdmin = {
        // Configuration from localized script
        config: window.peanutAdmin || {},

        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initModals();
            this.initCopyButtons();
            this.initConfirmActions();
            this.initTabs();
            this.initFilters();
        },

        /**
         * Bind global events
         */
        bindEvents: function() {
            // Form submission with loading state
            $(document).on('submit', '.peanut-form', this.handleFormSubmit.bind(this));

            // AJAX links
            $(document).on('click', '[data-peanut-action]', this.handleAction.bind(this));

            // Escape key closes modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    PeanutAdmin.closeAllModals();
                }
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Tooltips are handled via CSS [data-tip] attribute
            // This is for any JS-enhanced tooltips if needed
        },

        /**
         * Initialize modal functionality
         */
        initModals: function() {
            // Open modal
            $(document).on('click', '[data-peanut-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('peanut-modal');
                PeanutAdmin.openModal(modalId);
            });

            // Close modal on backdrop click
            $(document).on('click', '.peanut-modal-backdrop', function(e) {
                if ($(e.target).hasClass('peanut-modal-backdrop')) {
                    PeanutAdmin.closeAllModals();
                }
            });

            // Close modal button
            $(document).on('click', '.peanut-modal-close, [data-dismiss="modal"]', function(e) {
                e.preventDefault();
                PeanutAdmin.closeAllModals();
            });
        },

        /**
         * Open a modal
         */
        openModal: function(modalId) {
            // Handle selector with or without # prefix
            var selector = modalId;
            if (modalId.charAt(0) !== '#') {
                selector = '#' + modalId;
            }
            var $modal = $(selector);
            if ($modal.length) {
                $modal.addClass('active');
                $('body').addClass('peanut-modal-open');
            }
        },

        /**
         * Close a specific modal
         */
        closeModal: function(modalId) {
            var selector = modalId;
            if (modalId.charAt(0) !== '#') {
                selector = '#' + modalId;
            }
            $(selector).removeClass('active');
            // Only remove body class if no modals are active
            if (!$('.peanut-modal.active, .peanut-modal-backdrop.active').length) {
                $('body').removeClass('peanut-modal-open');
            }
        },

        /**
         * Close all modals
         */
        closeAllModals: function() {
            $('.peanut-modal, .peanut-modal-backdrop').removeClass('active');
            $('body').removeClass('peanut-modal-open');
        },

        /**
         * Initialize copy to clipboard buttons
         */
        initCopyButtons: function() {
            $(document).on('click', '.peanut-copy-btn, [data-copy]', function(e) {
                e.preventDefault();
                var text = $(this).data('copy') || $(this).siblings('.peanut-copy-target').text();
                PeanutAdmin.copyToClipboard(text, $(this));
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text, $button) {
            navigator.clipboard.writeText(text).then(function() {
                PeanutAdmin.showNotice('success', PeanutAdmin.config.i18n?.copied || 'Copied!');

                // Visual feedback on button
                if ($button) {
                    var originalText = $button.text();
                    $button.text(PeanutAdmin.config.i18n?.copied || 'Copied!');
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 2000);
                }
            }).catch(function() {
                PeanutAdmin.showNotice('error', PeanutAdmin.config.i18n?.copyFailed || 'Failed to copy');
            });
        },

        /**
         * Initialize confirmation dialogs
         */
        initConfirmActions: function() {
            $(document).on('click', '[data-confirm]', function(e) {
                var message = $(this).data('confirm') || PeanutAdmin.config.i18n?.confirm || 'Are you sure?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Initialize tabs
         * Only handles JavaScript-based tabs with data-tab attributes
         * Regular link tabs (like settings) work as normal links
         */
        initTabs: function() {
            $(document).on('click', '.peanut-tab[data-tab]', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var target = $tab.data('tab');

                // Update tab active state
                $tab.closest('.peanut-tabs').find('.peanut-tab').removeClass('active');
                $tab.addClass('active');

                // Show target panel
                var $panels = $tab.closest('.peanut-tabs-container').find('.peanut-tab-panel');
                $panels.removeClass('active');
                $panels.filter('[data-panel="' + target + '"]').addClass('active');

                // Update URL hash
                if (history.pushState) {
                    history.pushState(null, null, '#' + target);
                }
            });

            // Handle initial hash for JS-based tabs only
            if (window.location.hash) {
                var hash = window.location.hash.substring(1);
                var $targetTab = $('.peanut-tab[data-tab="' + hash + '"]');
                if ($targetTab.length) {
                    $targetTab.trigger('click');
                }
            }
        },

        /**
         * Initialize filters
         */
        initFilters: function() {
            // Auto-submit filter forms on change
            $(document).on('change', '.peanut-filters select', function() {
                $(this).closest('form').submit();
            });
        },

        /**
         * Handle form submissions
         */
        handleFormSubmit: function(e) {
            var $form = $(e.target);
            var $submit = $form.find('[type="submit"]');

            // Add loading state
            $submit.prop('disabled', true);
            $submit.data('original-text', $submit.text());
            $submit.text(this.config.i18n?.saving || 'Saving...');
        },

        /**
         * Handle data-peanut-action clicks
         */
        handleAction: function(e) {
            e.preventDefault();
            var $el = $(e.currentTarget);
            var action = $el.data('peanut-action');
            var data = $el.data();

            // Trigger custom event
            $(document).trigger('peanut:action:' + action, [data, $el]);
        },

        /**
         * Make an API request
         */
        api: function(endpoint, method, data) {
            method = method || 'GET';

            return $.ajax({
                url: this.config.restUrl + '/' + endpoint,
                method: method,
                data: data,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', PeanutAdmin.config.nonce);
                }
            });
        },

        /**
         * Show a notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="peanut-notice peanut-notice-' + type + '">' +
                '<span class="dashicons dashicons-' + this.getNoticeIcon(type) + '"></span>' +
                '<span>' + message + '</span>' +
                '<button type="button" class="peanut-notice-dismiss">&times;</button>' +
                '</div>');

            // Remove existing notices of same type
            $('.peanut-notice-' + type).remove();

            // Add to page
            $('.peanut-wrap').prepend($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Get notice icon
         */
        getNoticeIcon: function(type) {
            var icons = {
                success: 'yes-alt',
                error: 'warning',
                warning: 'info',
                info: 'info-outline'
            };
            return icons[type] || 'info';
        },

        /**
         * Format number with commas
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        /**
         * Format date
         */
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString();
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },

        /**
         * Generate a random ID
         */
        generateId: function() {
            return 'peanut_' + Math.random().toString(36).substr(2, 9);
        }
    };

    // UTM Builder specific functionality
    window.PeanutUTM = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Real-time URL preview
            $(document).on('input change', '.peanut-utm-field', PeanutAdmin.debounce(function() {
                PeanutUTM.updatePreview();
            }, 200));

            // Reset form
            $(document).on('click', '#peanut-utm-reset', function(e) {
                e.preventDefault();
                PeanutUTM.resetForm();
            });
        },

        updatePreview: function() {
            var baseUrl = $('#peanut-utm-url').val();
            if (!baseUrl) {
                $('#peanut-url-preview').text('');
                return;
            }

            var params = [];
            var fields = ['source', 'medium', 'campaign', 'term', 'content'];

            fields.forEach(function(field) {
                var value = $('#peanut-utm-' + field).val();
                if (value) {
                    params.push('utm_' + field + '=' + encodeURIComponent(value));
                }
            });

            var fullUrl = baseUrl;
            if (params.length > 0) {
                fullUrl += (baseUrl.indexOf('?') > -1 ? '&' : '?') + params.join('&');
            }

            $('#peanut-url-preview').text(fullUrl);
        },

        resetForm: function() {
            $('.peanut-utm-form')[0].reset();
            $('#peanut-url-preview').text('');
        }
    };

    // Links specific functionality
    window.PeanutLinks = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add link button
            $(document).on('click', '#peanut-add-link', function(e) {
                e.preventDefault();
                PeanutAdmin.openModal('peanut-link-modal');
            });

            // QR code modal
            $(document).on('click', '.peanut-qr-btn', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                PeanutLinks.showQRCode(url);
            });
        },

        showQRCode: function(url) {
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
            $('#peanut-qr-image').attr('src', qrUrl);
            $('#peanut-qr-url').text(url);
            PeanutAdmin.openModal('peanut-qr-modal');
        }
    };

    // Contacts specific functionality
    window.PeanutContacts = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add contact button
            $(document).on('click', '#peanut-add-contact', function(e) {
                e.preventDefault();
                PeanutAdmin.openModal('peanut-contact-modal');
            });

            // Export contacts
            $(document).on('click', '#peanut-export-contacts', function(e) {
                e.preventDefault();
                PeanutContacts.exportContacts();
            });
        },

        exportContacts: function() {
            window.location.href = PeanutAdmin.config.restUrl + '/contacts/export?_wpnonce=' + PeanutAdmin.config.nonce;
        }
    };

    // Webhooks specific functionality
    window.PeanutWebhooks = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // View webhook details
            $(document).on('click', '.peanut-webhook-view', function(e) {
                e.preventDefault();
                var webhookId = $(this).data('id');
                PeanutWebhooks.viewDetails(webhookId);
            });

            // Reprocess webhook
            $(document).on('click', '.peanut-webhook-reprocess', function(e) {
                e.preventDefault();
                var webhookId = $(this).data('id');
                PeanutWebhooks.reprocess(webhookId);
            });
        },

        viewDetails: function(id) {
            PeanutAdmin.api('webhooks/' + id).done(function(response) {
                $('#peanut-webhook-source').text(response.source);
                $('#peanut-webhook-event').text(response.event);
                $('#peanut-webhook-status').text(response.status);
                $('#peanut-webhook-received').text(response.created_at);
                $('#peanut-webhook-payload').text(JSON.stringify(response.payload, null, 2));
                PeanutAdmin.openModal('peanut-webhook-modal');
            });
        },

        reprocess: function(id) {
            PeanutAdmin.showNotice('info', PeanutAdmin.config.i18n?.processingWebhook || 'Processing...');

            PeanutAdmin.api('webhooks/' + id + '/reprocess', 'POST').done(function() {
                PeanutAdmin.showNotice('success', PeanutAdmin.config.i18n?.webhookProcessed || 'Webhook processed!');
                location.reload();
            }).fail(function() {
                PeanutAdmin.showNotice('error', PeanutAdmin.config.i18n?.error || 'An error occurred');
            });
        }
    };

    // Visitors specific functionality
    window.PeanutVisitors = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Get tracking snippet
            $(document).on('click', '#peanut-get-snippet', function(e) {
                e.preventDefault();
                PeanutVisitors.showSnippet();
            });
        },

        showSnippet: function() {
            PeanutAdmin.api('visitors/snippet').done(function(response) {
                $('#peanut-snippet-code').text(response.snippet);
                PeanutAdmin.openModal('peanut-snippet-modal');
            });
        }
    };

    // Integrations functionality
    window.PeanutIntegrations = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Test integration connection
            $(document).on('click', '.peanut-test-integration', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var integration = $btn.data('integration');
                PeanutIntegrations.testConnection(integration, $btn);
            });

            // Test Stripe connection (legacy button ID)
            $(document).on('click', '#test-stripe-connection', function(e) {
                e.preventDefault();
                var $btn = $(this);
                PeanutIntegrations.testConnection('stripe', $btn);
            });
        },

        testConnection: function(integration, $btn) {
            var originalText = $btn.text();
            $btn.prop('disabled', true).text(PeanutAdmin.config.i18n?.testing || 'Testing...');

            $.ajax({
                url: PeanutAdmin.config.restUrl + '/integrations/' + integration + '/test',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', PeanutAdmin.config.nonce);
                },
                success: function(response) {
                    PeanutAdmin.showNotice('success', response.data?.message || 'Connection successful!');
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || 'Connection failed';
                    PeanutAdmin.showNotice('error', msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PeanutAdmin.init();
        PeanutUTM.init();
        PeanutLinks.init();
        PeanutContacts.init();
        PeanutWebhooks.init();
        PeanutVisitors.init();
        PeanutIntegrations.init();

        // Fix empty state table display - hide footer when empty state present
        $('.peanut-empty-state').each(function() {
            var $table = $(this).closest('.wp-list-table');
            if ($table.length) {
                $table.addClass('peanut-has-empty-state');
                $table.find('tfoot').hide();
            }
        });
    });

})(jQuery);

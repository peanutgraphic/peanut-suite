/**
 * API Checker Admin JavaScript
 *
 * @package Peanut_Suite
 * @since 4.1.3
 */

/* global jQuery, ajaxurl, peanutApiChecker */
(function($) {
    'use strict';

    var nonce = peanutApiChecker.nonce;
    var restNonce = peanutApiChecker.restNonce;
    var restUrl = peanutApiChecker.restUrl;
    var savedApis = peanutApiChecker.savedApis || [];
    var i18n = peanutApiChecker.i18n || {};

    // Check connections on load
    checkConnections();

    function checkConnections() {
        $.ajax({
            url: restUrl + 'peanut/v1/api-checker/connections',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', restNonce);
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateConnectionStatus(response.data);
                }
            }
        });

        // Check local REST API
        $.ajax({
            url: restUrl + 'peanut/v1',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', restNonce);
            },
            success: function() {
                var $card = $('[data-connection="rest_api"]');
                $card.find('.status-indicator').removeClass('loading').addClass('connected');
                $card.find('.status-text').text('Connected');
            },
            error: function() {
                var $card = $('[data-connection="rest_api"]');
                $card.find('.status-indicator').removeClass('loading').addClass('error');
                $card.find('.status-text').text('Error');
            }
        });
    }

    function updateConnectionStatus(data) {
        $.each(data, function(key, connection) {
            var $card = $('[data-connection="' + key + '"]');
            if ($card.length === 0) return;

            $card.find('.status-indicator')
                .removeClass('loading')
                .addClass(connection.status);

            var statusText = connection.status.charAt(0).toUpperCase() + connection.status.slice(1).replace('_', ' ');
            $card.find('.status-text').text(statusText);

            if (connection.latency) {
                $card.find('.connection-details').append('<div>Latency: ' + connection.latency + '</div>');
            }

            if (connection.details && connection.details.tier) {
                $card.find('.connection-details').append('<div>Tier: ' + connection.details.tier + '</div>');
            }
        });
    }

    // Test connection button
    $('.test-connection').on('click', function() {
        var $btn = $(this);
        var connection = $btn.data('connection');
        var $card = $btn.closest('.peanut-connection-card');

        $btn.prop('disabled', true).text(i18n.testing || 'Testing...');
        $card.find('.status-indicator').removeClass('connected error not_configured').addClass('loading');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'peanut_test_external_connection',
                nonce: nonce,
                connection: connection
            },
            success: function(response) {
                if (response.success && response.data) {
                    $card.find('.status-indicator')
                        .removeClass('loading')
                        .addClass(response.data.status);
                    $card.find('.status-text').text(response.data.status);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text(i18n.testConnection || 'Test Connection');
            }
        });
    });

    // Test endpoint button
    $('.test-endpoint').on('click', function() {
        var $btn = $(this);
        var route = $btn.data('route');
        var method = $btn.data('method');

        $btn.prop('disabled', true).text(i18n.testing || 'Testing...');

        // Replace route parameters with example values
        var testRoute = route.replace(/\(\?P<id>\\d\+\)/g, '1');

        $.ajax({
            url: restUrl + testRoute.substring(1),
            method: method,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', restNonce);
            },
            success: function(data, textStatus, xhr) {
                showTestResult({
                    request: {
                        method: method,
                        url: restUrl + testRoute.substring(1)
                    },
                    response: {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        data: data
                    }
                });
            },
            error: function(xhr) {
                showTestResult({
                    request: {
                        method: method,
                        url: restUrl + testRoute.substring(1)
                    },
                    response: {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        data: xhr.responseJSON || xhr.responseText
                    }
                });
            },
            complete: function() {
                $btn.prop('disabled', false).text(i18n.test || 'Test');
            }
        });
    });

    function showTestResult(result) {
        var $modal = $('#peanut-api-test-modal');

        $modal.find('.request-details').text(
            result.request.method + ' ' + result.request.url
        );

        var statusClass = result.response.status >= 200 && result.response.status < 300 ? 'success' : 'error';
        $modal.find('.response-status')
            .removeClass('success error')
            .addClass(statusClass)
            .text(result.response.status + ' ' + result.response.statusText);

        $modal.find('.response-body').text(
            JSON.stringify(result.response.data, null, 2)
        );

        $modal.show();
    }

    // Close modal
    $('.peanut-modal-close, .peanut-modal').on('click', function(e) {
        if (e.target === this) {
            $('#peanut-api-test-modal').hide();
        }
    });

    // Toggle endpoint groups
    $('.endpoint-group-header').on('click', function(e) {
        // Don't toggle if clicking export button
        if ($(e.target).closest('.export-section').length) {
            return;
        }

        var $header = $(this);
        var target = $header.data('target');
        var $content = $('#' + target);

        $header.toggleClass('collapsed');
        $content.toggleClass('collapsed');
    });

    // Export section as JSON
    $('.export-section').on('click', function(e) {
        e.stopPropagation();
        var namespace = $(this).data('namespace');
        var $group = $(this).closest('.peanut-endpoint-group');

        // Collect endpoints from the table
        var endpoints = [];
        $group.find('.endpoint-row').each(function() {
            var $row = $(this);
            var route = $row.data('route');
            var methods = [];
            $row.find('.method-badge').each(function() {
                methods.push($(this).text().trim());
            });

            var params = [];
            $row.find('.params-list li').each(function() {
                var $li = $(this);
                params.push({
                    name: $li.find('code').first().text(),
                    type: $li.find('.param-type').text().replace(/[()]/g, ''),
                    required: $li.find('.param-required').length > 0,
                    description: $li.find('.param-desc').text() || ''
                });
            });

            endpoints.push({
                route: route,
                methods: methods,
                parameters: params
            });
        });

        var exportData = {
            namespace: namespace,
            exported_at: new Date().toISOString(),
            endpoint_count: endpoints.length,
            endpoints: endpoints
        };

        // Create download
        var blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = namespace.replace(/\//g, '-') + '-endpoints.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // Auth type toggle
    $('#custom-api-auth-type').on('change', function() {
        var authType = $(this).val();
        $('.auth-fields').hide();
        if (authType !== 'none') {
            $('.auth-' + authType).show();
        }
    });

    // Content type toggle
    $('#custom-api-content-type').on('change', function() {
        var contentType = $(this).val();
        if (contentType === 'form') {
            $('.body-label-json').hide();
            $('.body-label-form').show();
            $('.body-hint-json').hide();
            $('.body-hint-form').show();
            $('#custom-api-body').attr('placeholder', 'pswd=yourpassword&val=submit');
        } else {
            $('.body-label-json').show();
            $('.body-label-form').hide();
            $('.body-hint-json').show();
            $('.body-hint-form').hide();
            $('#custom-api-body').attr('placeholder', '{"key": "value"}');
        }
    });

    // Toggle saved API sections
    $(document).on('click', '.saved-api-header', function(e) {
        if ($(e.target).closest('.header-actions').length) {
            return;
        }

        var $header = $(this);
        var target = $header.data('target');
        var $content = $('#' + target);

        $header.toggleClass('collapsed');
        $content.toggleClass('collapsed');
    });

    // Save API button - show name input
    $('#save-custom-api').on('click', function() {
        var url = $('#custom-api-url').val();
        if (!url) {
            alert(i18n.enterUrlFirst || 'Please enter an API URL first');
            return;
        }
        $('#save-api-name-row').slideDown();
    });

    // Cancel save
    $('#cancel-save-api').on('click', function() {
        $('#save-api-name-row').slideUp();
        $('#custom-api-name').val('');
    });

    // Confirm save API
    $('#confirm-save-api').on('click', function() {
        var name = $('#custom-api-name').val();
        var url = $('#custom-api-url').val();

        if (!name) {
            alert(i18n.enterName || 'Please enter a name for this API');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text(i18n.saving || 'Saving...');

        var authType = $('#custom-api-auth-type').val();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'peanut_save_custom_api',
                nonce: nonce,
                name: name,
                url: url,
                method: $('#custom-api-method').val(),
                auth_type: authType,
                auth_value: authType === 'bearer' ? $('#custom-api-token').val() : '',
                auth_username: authType === 'basic' ? $('#custom-api-username').val() : '',
                auth_password: authType === 'basic' ? $('#custom-api-password').val() : '',
                auth_header_name: authType === 'api_key' ? $('#custom-api-key-name').val() : '',
                body_template: $('#custom-api-body').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert((i18n.errorSaving || 'Error saving API:') + ' ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert(i18n.failedToSave || 'Failed to save API');
            },
            complete: function() {
                $btn.prop('disabled', false).text(i18n.confirmSave || 'Confirm Save');
            }
        });
    });

    // Delete saved API
    $(document).on('click', '.delete-saved-api', function(e) {
        e.stopPropagation();

        if (!confirm(i18n.confirmDelete || 'Are you sure you want to delete this API?')) {
            return;
        }

        var $btn = $(this);
        var apiId = $btn.data('api-id');
        var $group = $btn.closest('.peanut-saved-api-group');

        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'peanut_delete_custom_api',
                nonce: nonce,
                api_id: apiId
            },
            success: function(response) {
                if (response.success) {
                    $group.slideUp(function() {
                        $(this).remove();
                        if ($('#saved-apis-list .peanut-saved-api-group').length === 0) {
                            $('#saved-apis-list').html('<div class="no-saved-apis"><p>' + (i18n.noSavedApis || 'No saved APIs yet. Use the form above to test and save an API configuration.') + '</p></div>');
                        }
                    });
                } else {
                    alert((i18n.errorDeleting || 'Error deleting API:') + ' ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert(i18n.failedToDelete || 'Failed to delete API');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Test saved API
    $(document).on('click', '.test-saved-api', function(e) {
        e.stopPropagation();

        var $btn = $(this);
        var apiId = $btn.data('api-id');
        var $group = $btn.closest('.peanut-saved-api-group');
        var $content = $group.find('.saved-api-content');
        var $result = $group.find('.saved-api-result');

        // Find API data
        var api = null;
        for (var i = 0; i < savedApis.length; i++) {
            if (savedApis[i].id === apiId) {
                api = savedApis[i];
                break;
            }
        }

        if (!api) {
            alert('API not found');
            return;
        }

        // Expand the content
        $group.find('.saved-api-header').removeClass('collapsed');
        $content.removeClass('collapsed');

        $btn.prop('disabled', true);
        $result.show().find('.result-status').removeClass('success error').text(i18n.testing || 'Testing...');

        // Build headers
        var headers = {};
        if (api.auth_type === 'bearer' && api.auth_value) {
            headers['Authorization'] = 'Bearer ' + api.auth_value;
        } else if (api.auth_type === 'basic' && api.auth_username) {
            headers['Authorization'] = 'Basic ' + btoa(api.auth_username + ':' + api.auth_password);
        } else if (api.auth_type === 'api_key' && api.auth_value) {
            var headerName = api.auth_header_name || 'X-API-Key';
            headers[headerName] = api.auth_value;
        }

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'peanut_test_custom_api',
                nonce: nonce,
                api_url: api.url,
                api_method: api.method,
                api_headers: JSON.stringify(headers),
                api_body: api.body_template || ''
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var statusClass = data.status_code >= 200 && data.status_code < 300 ? 'success' : 'error';

                    $result.find('.result-status')
                        .removeClass('success error')
                        .addClass(statusClass)
                        .html('<strong>' + data.status_code + '</strong> <em>(' + data.latency + 'ms)</em>');

                    var bodyContent = data.response_body;
                    if (typeof bodyContent === 'object') {
                        bodyContent = JSON.stringify(bodyContent, null, 2);
                    }
                    $result.find('.body-content').text(bodyContent || 'Empty response');
                } else {
                    $result.find('.result-status')
                        .removeClass('success')
                        .addClass('error')
                        .text('Error: ' + (response.data.message || response.data || 'Unknown error'));
                }
            },
            error: function(xhr) {
                $result.find('.result-status')
                    .removeClass('success')
                    .addClass('error')
                    .text('Request failed: ' + xhr.statusText);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Export saved API
    $(document).on('click', '.export-saved-api', function(e) {
        e.stopPropagation();

        var apiId = $(this).data('api-id');

        // Find API data
        var api = null;
        for (var i = 0; i < savedApis.length; i++) {
            if (savedApis[i].id === apiId) {
                api = savedApis[i];
                break;
            }
        }

        if (!api) {
            alert('API not found');
            return;
        }

        // Create export data (excluding sensitive auth values)
        var exportData = {
            name: api.name,
            url: api.url,
            method: api.method,
            auth_type: api.auth_type,
            body_template: api.body_template,
            description: api.description || '',
            exported_at: new Date().toISOString()
        };

        // Create download
        var blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = api.name.toLowerCase().replace(/\s+/g, '-') + '-api-config.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // Test custom API
    $('#test-custom-api').on('click', function() {
        var $btn = $(this);
        var url = $('#custom-api-url').val();

        if (!url) {
            alert(i18n.enterUrl || 'Please enter an API URL');
            return;
        }

        var method = $('#custom-api-method').val();
        var authType = $('#custom-api-auth-type').val();
        var contentType = $('#custom-api-content-type').val();
        var body = $('#custom-api-body').val();

        // Build headers
        var headers = {};

        if (authType === 'bearer') {
            headers['Authorization'] = 'Bearer ' + $('#custom-api-token').val();
        } else if (authType === 'basic') {
            var credentials = btoa($('#custom-api-username').val() + ':' + $('#custom-api-password').val());
            headers['Authorization'] = 'Basic ' + credentials;
        } else if (authType === 'api_key') {
            var headerName = $('#custom-api-key-name').val() || 'X-API-Key';
            headers[headerName] = $('#custom-api-key-value').val();
        }

        // Set Content-Type based on selection
        if (body && method !== 'GET') {
            if (contentType === 'form') {
                headers['Content-Type'] = 'application/x-www-form-urlencoded';
            } else {
                headers['Content-Type'] = 'application/json';
            }
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + (i18n.testing || 'Testing...'));

        // Use AJAX proxy to avoid CORS issues
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'peanut_test_custom_api',
                nonce: nonce,
                api_url: url,
                api_method: method,
                api_headers: JSON.stringify(headers),
                api_body: body,
                api_content_type: contentType
            },
            success: function(response) {
                var $result = $('#custom-api-result');
                $result.show();

                if (response.success) {
                    var data = response.data;
                    var statusClass = data.status_code >= 200 && data.status_code < 300 ? 'success' : 'error';

                    $result.find('.result-status')
                        .removeClass('success error')
                        .addClass(statusClass)
                        .html('<strong>' + data.status_code + '</strong> <em>(' + (data.latency || '?') + 'ms)</em>');

                    $result.find('.headers-content').text(
                        data.response_headers ? JSON.stringify(data.response_headers, null, 2) : 'No headers'
                    );

                    var bodyContent = data.response_body;
                    if (typeof bodyContent === 'object') {
                        bodyContent = JSON.stringify(bodyContent, null, 2);
                    }

                    $result.find('.body-content').text(bodyContent || 'Empty response');
                } else {
                    // Handle error response - could be string or object
                    var errorMsg = response.data;
                    if (typeof errorMsg === 'object') {
                        errorMsg = errorMsg.message || JSON.stringify(errorMsg);
                    }
                    $result.find('.result-status')
                        .removeClass('success')
                        .addClass('error')
                        .text('Error: ' + (errorMsg || 'Unknown error'));
                    $result.find('.headers-content').text('');
                    $result.find('.body-content').text('');
                }
            },
            error: function(xhr) {
                var $result = $('#custom-api-result');
                $result.show();
                $result.find('.result-status')
                    .removeClass('success')
                    .addClass('error')
                    .text('Request failed: ' + xhr.statusText);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> ' + (i18n.testApi || 'Test API'));
            }
        });
    });

})(jQuery);

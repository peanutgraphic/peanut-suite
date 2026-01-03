<?php
/**
 * API Checker Admin View
 *
 * Displays all API endpoints, connection status, and testing tools.
 *
 * @package Peanut_Suite
 */

defined('ABSPATH') || exit;

// Get endpoints
$endpoints = \PeanutSuite\APIChecker\API_Checker_Module::get_endpoints_for_admin();

// Get saved custom APIs
$saved_apis = \PeanutSuite\APIChecker\API_Checker_Module::get_custom_apis_for_admin();

// Group by namespace
$grouped = [];
foreach ($endpoints as $endpoint) {
    preg_match('#^/([^/]+/v\d+)#', $endpoint['route'], $matches);
    $ns = $matches[1] ?? 'other';
    if (!isset($grouped[$ns])) {
        $grouped[$ns] = [];
    }
    $grouped[$ns][] = $endpoint;
}

$namespace_labels = [
    'peanut/v1' => 'Peanut Suite',
    'peanut-api/v1' => 'License Server API',
    'peanut-license/v1' => 'License Server',
    'peanut-booker/v1' => 'Peanut Booker',
];
?>

<!-- Connection Status Cards -->
    <div class="peanut-api-connections">
        <h2><?php esc_html_e('External Connections', 'peanut-suite'); ?></h2>

        <div class="peanut-connection-grid">
            <!-- License Server -->
            <div class="peanut-connection-card" data-connection="license_server">
                <div class="connection-header">
                    <span class="dashicons dashicons-shield"></span>
                    <h3><?php esc_html_e('License Server', 'peanut-suite'); ?></h3>
                </div>
                <div class="connection-status">
                    <span class="status-indicator loading"></span>
                    <span class="status-text"><?php esc_html_e('Checking...', 'peanut-suite'); ?></span>
                </div>
                <div class="connection-details"></div>
                <button type="button" class="button test-connection" data-connection="license_server">
                    <?php esc_html_e('Test Connection', 'peanut-suite'); ?>
                </button>
            </div>

            <!-- WordPress.org API -->
            <div class="peanut-connection-card" data-connection="wordpress_api">
                <div class="connection-header">
                    <span class="dashicons dashicons-wordpress"></span>
                    <h3><?php esc_html_e('WordPress.org API', 'peanut-suite'); ?></h3>
                </div>
                <div class="connection-status">
                    <span class="status-indicator loading"></span>
                    <span class="status-text"><?php esc_html_e('Checking...', 'peanut-suite'); ?></span>
                </div>
                <div class="connection-details"></div>
                <button type="button" class="button test-connection" data-connection="wordpress_api">
                    <?php esc_html_e('Test Connection', 'peanut-suite'); ?>
                </button>
            </div>

            <!-- REST API -->
            <div class="peanut-connection-card" data-connection="rest_api">
                <div class="connection-header">
                    <span class="dashicons dashicons-rest-api"></span>
                    <h3><?php esc_html_e('Local REST API', 'peanut-suite'); ?></h3>
                </div>
                <div class="connection-status">
                    <span class="status-indicator loading"></span>
                    <span class="status-text"><?php esc_html_e('Checking...', 'peanut-suite'); ?></span>
                </div>
                <div class="connection-details">
                    <code><?php echo esc_url(rest_url('peanut/v1')); ?></code>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Custom API -->
    <div class="peanut-custom-api-test">
        <h2><?php esc_html_e('Test Custom API', 'peanut-suite'); ?></h2>
        <p class="description"><?php esc_html_e('Test any external API endpoint with optional authentication.', 'peanut-suite'); ?></p>

        <div class="custom-api-form">
            <div class="form-row">
                <label for="custom-api-url"><?php esc_html_e('API URL', 'peanut-suite'); ?></label>
                <input type="url" id="custom-api-url" class="large-text" placeholder="https://api.example.com/endpoint">
            </div>
            <div class="form-row form-row-split">
                <div class="form-col">
                    <label for="custom-api-method"><?php esc_html_e('Method', 'peanut-suite'); ?></label>
                    <select id="custom-api-method">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                        <option value="PATCH">PATCH</option>
                    </select>
                </div>
                <div class="form-col">
                    <label for="custom-api-auth-type"><?php esc_html_e('Auth Type', 'peanut-suite'); ?></label>
                    <select id="custom-api-auth-type">
                        <option value="none"><?php esc_html_e('None', 'peanut-suite'); ?></option>
                        <option value="bearer"><?php esc_html_e('Bearer Token', 'peanut-suite'); ?></option>
                        <option value="basic"><?php esc_html_e('Basic Auth', 'peanut-suite'); ?></option>
                        <option value="api_key"><?php esc_html_e('API Key (Header)', 'peanut-suite'); ?></option>
                    </select>
                </div>
            </div>
            <div class="form-row auth-fields auth-bearer" style="display: none;">
                <label for="custom-api-token"><?php esc_html_e('Bearer Token', 'peanut-suite'); ?></label>
                <input type="password" id="custom-api-token" class="large-text" placeholder="your-api-token">
            </div>
            <div class="form-row form-row-split auth-fields auth-basic" style="display: none;">
                <div class="form-col">
                    <label for="custom-api-username"><?php esc_html_e('Username', 'peanut-suite'); ?></label>
                    <input type="text" id="custom-api-username" placeholder="username">
                </div>
                <div class="form-col">
                    <label for="custom-api-password"><?php esc_html_e('Password', 'peanut-suite'); ?></label>
                    <input type="password" id="custom-api-password" placeholder="password">
                </div>
            </div>
            <div class="form-row form-row-split auth-fields auth-api_key" style="display: none;">
                <div class="form-col">
                    <label for="custom-api-key-name"><?php esc_html_e('Header Name', 'peanut-suite'); ?></label>
                    <input type="text" id="custom-api-key-name" placeholder="X-API-Key">
                </div>
                <div class="form-col">
                    <label for="custom-api-key-value"><?php esc_html_e('API Key', 'peanut-suite'); ?></label>
                    <input type="password" id="custom-api-key-value" placeholder="your-api-key">
                </div>
            </div>
            <div class="form-row">
                <label for="custom-api-content-type"><?php esc_html_e('Content Type', 'peanut-suite'); ?></label>
                <select id="custom-api-content-type">
                    <option value="json">JSON (application/json)</option>
                    <option value="form">Form Data (application/x-www-form-urlencoded)</option>
                </select>
            </div>
            <div class="form-row">
                <label for="custom-api-body">
                    <span class="body-label-json"><?php esc_html_e('Request Body (JSON)', 'peanut-suite'); ?></span>
                    <span class="body-label-form" style="display:none;"><?php esc_html_e('Request Body (Form Data)', 'peanut-suite'); ?></span>
                </label>
                <textarea id="custom-api-body" rows="4" class="large-text code" placeholder='{"key": "value"}'></textarea>
                <p class="description body-hint-json"><?php esc_html_e('Enter JSON format: {"key": "value"}', 'peanut-suite'); ?></p>
                <p class="description body-hint-form" style="display:none;"><?php esc_html_e('Enter form data: key=value&another=data (e.g., pswd=yourpassword&val=submit)', 'peanut-suite'); ?></p>
            </div>
            <div class="form-row form-row-actions">
                <button type="button" id="test-custom-api" class="button button-primary">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php esc_html_e('Test API', 'peanut-suite'); ?>
                </button>
                <button type="button" id="save-custom-api" class="button">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save API', 'peanut-suite'); ?>
                </button>
            </div>
            <div id="save-api-name-row" class="form-row" style="display: none;">
                <label for="custom-api-name"><?php esc_html_e('API Name', 'peanut-suite'); ?></label>
                <input type="text" id="custom-api-name" class="regular-text" placeholder="<?php esc_attr_e('e.g., Stripe API, Mailchimp', 'peanut-suite'); ?>">
                <p class="description"><?php esc_html_e('Enter a name for this API configuration to save it.', 'peanut-suite'); ?></p>
                <button type="button" id="confirm-save-api" class="button button-primary" style="margin-top: 10px;">
                    <?php esc_html_e('Confirm Save', 'peanut-suite'); ?>
                </button>
                <button type="button" id="cancel-save-api" class="button" style="margin-top: 10px;">
                    <?php esc_html_e('Cancel', 'peanut-suite'); ?>
                </button>
            </div>
            <div id="custom-api-result" style="display: none;">
                <h4><?php esc_html_e('Result', 'peanut-suite'); ?></h4>
                <div class="result-status"></div>
                <div class="result-headers">
                    <strong><?php esc_html_e('Response Headers:', 'peanut-suite'); ?></strong>
                    <pre class="headers-content"></pre>
                </div>
                <div class="result-body">
                    <strong><?php esc_html_e('Response Body:', 'peanut-suite'); ?></strong>
                    <pre class="body-content"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Saved APIs Section -->
    <div class="peanut-saved-apis">
        <h2><?php esc_html_e('Saved APIs', 'peanut-suite'); ?></h2>
        <p class="description"><?php esc_html_e('Your saved API configurations for quick testing.', 'peanut-suite'); ?></p>

        <div id="saved-apis-list">
            <?php if (empty($saved_apis)): ?>
                <div class="no-saved-apis">
                    <p><?php esc_html_e('No saved APIs yet. Use the form above to test and save an API configuration.', 'peanut-suite'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($saved_apis as $api_id => $api): ?>
                    <div class="peanut-saved-api-group" data-api-id="<?php echo esc_attr($api_id); ?>">
                        <h3 class="saved-api-header" data-target="saved-api-<?php echo esc_attr($api_id); ?>">
                            <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
                            <span class="dashicons dashicons-cloud"></span>
                            <?php echo esc_html($api['name']); ?>
                            <span class="api-method-badge method-<?php echo esc_attr(strtolower($api['method'])); ?>">
                                <?php echo esc_html($api['method']); ?>
                            </span>
                            <?php if (!empty($api['last_test_status'])): ?>
                                <span class="last-test-status status-<?php echo $api['last_test_status'] >= 200 && $api['last_test_status'] < 300 ? 'success' : 'error'; ?>">
                                    <?php echo esc_html($api['last_test_status']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="header-actions">
                                <button type="button" class="button button-small test-saved-api" data-api-id="<?php echo esc_attr($api_id); ?>" title="<?php esc_attr_e('Test API', 'peanut-suite'); ?>">
                                    <span class="dashicons dashicons-controls-play"></span>
                                </button>
                                <button type="button" class="button button-small export-saved-api" data-api-id="<?php echo esc_attr($api_id); ?>" title="<?php esc_attr_e('Export as JSON', 'peanut-suite'); ?>">
                                    <span class="dashicons dashicons-download"></span>
                                </button>
                                <button type="button" class="button button-small delete-saved-api" data-api-id="<?php echo esc_attr($api_id); ?>" title="<?php esc_attr_e('Delete', 'peanut-suite'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </span>
                        </h3>
                        <div class="saved-api-content collapsed" id="saved-api-<?php echo esc_attr($api_id); ?>">
                            <table class="widefat">
                                <tr>
                                    <th><?php esc_html_e('URL', 'peanut-suite'); ?></th>
                                    <td><code><?php echo esc_html($api['url']); ?></code></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Method', 'peanut-suite'); ?></th>
                                    <td><?php echo esc_html($api['method']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Auth Type', 'peanut-suite'); ?></th>
                                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $api['auth_type']))); ?></td>
                                </tr>
                                <?php if (!empty($api['description'])): ?>
                                <tr>
                                    <th><?php esc_html_e('Description', 'peanut-suite'); ?></th>
                                    <td><?php echo esc_html($api['description']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($api['last_tested_at'])): ?>
                                <tr>
                                    <th><?php esc_html_e('Last Tested', 'peanut-suite'); ?></th>
                                    <td>
                                        <?php echo esc_html($api['last_tested_at']); ?>
                                        <?php if (!empty($api['last_test_latency'])): ?>
                                            <em>(<?php echo esc_html($api['last_test_latency']); ?>ms)</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th><?php esc_html_e('Created', 'peanut-suite'); ?></th>
                                    <td><?php echo esc_html($api['created_at']); ?></td>
                                </tr>
                            </table>
                            <div class="saved-api-result" id="saved-api-result-<?php echo esc_attr($api_id); ?>" style="display: none;">
                                <h4><?php esc_html_e('Test Result', 'peanut-suite'); ?></h4>
                                <div class="result-status"></div>
                                <div class="result-body">
                                    <pre class="body-content"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Endpoint Summary -->
    <div class="peanut-api-summary">
        <h2><?php esc_html_e('API Endpoints Summary', 'peanut-suite'); ?></h2>

        <div class="peanut-stats-row">
            <div class="peanut-stat">
                <span class="stat-number"><?php echo count($endpoints); ?></span>
                <span class="stat-label"><?php esc_html_e('Total Endpoints', 'peanut-suite'); ?></span>
            </div>
            <div class="peanut-stat">
                <span class="stat-number"><?php echo count($grouped); ?></span>
                <span class="stat-label"><?php esc_html_e('Namespaces', 'peanut-suite'); ?></span>
            </div>
            <div class="peanut-stat">
                <span class="stat-number">
                    <?php
                    $get_count = 0;
                    foreach ($endpoints as $ep) {
                        if (in_array('GET', $ep['methods'])) $get_count++;
                    }
                    echo $get_count;
                    ?>
                </span>
                <span class="stat-label"><?php esc_html_e('GET Endpoints', 'peanut-suite'); ?></span>
            </div>
            <div class="peanut-stat">
                <span class="stat-number">
                    <?php
                    $post_count = 0;
                    foreach ($endpoints as $ep) {
                        if (in_array('POST', $ep['methods'])) $post_count++;
                    }
                    echo $post_count;
                    ?>
                </span>
                <span class="stat-label"><?php esc_html_e('POST Endpoints', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Endpoint List by Namespace -->
    <div class="peanut-api-endpoints">
        <h2><?php esc_html_e('All Endpoints', 'peanut-suite'); ?></h2>

        <?php foreach ($grouped as $namespace => $ns_endpoints): ?>
            <?php $group_id = sanitize_title($namespace); ?>
            <div class="peanut-endpoint-group" data-namespace="<?php echo esc_attr($namespace); ?>">
                <h3 class="endpoint-group-header" data-target="<?php echo esc_attr($group_id); ?>">
                    <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
                    <span class="dashicons dashicons-category"></span>
                    <?php echo esc_html($namespace_labels[$namespace] ?? $namespace); ?>
                    <span class="endpoint-count">(<?php echo count($ns_endpoints); ?>)</span>
                    <span class="header-actions">
                        <button type="button" class="button button-small export-section" data-namespace="<?php echo esc_attr($namespace); ?>" title="<?php esc_attr_e('Export as JSON', 'peanut-suite'); ?>">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </span>
                </h3>

                <div class="endpoint-group-content" id="<?php echo esc_attr($group_id); ?>">
                <table class="widefat striped peanut-endpoint-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;"><?php esc_html_e('Methods', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Route', 'peanut-suite'); ?></th>
                            <th style="width: 300px;"><?php esc_html_e('Parameters', 'peanut-suite'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Actions', 'peanut-suite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ns_endpoints as $endpoint): ?>
                            <tr class="endpoint-row" data-route="<?php echo esc_attr($endpoint['route']); ?>">
                                <td class="endpoint-methods">
                                    <?php foreach ($endpoint['methods'] as $method): ?>
                                        <span class="method-badge method-<?php echo esc_attr(strtolower($method)); ?>">
                                            <?php echo esc_html($method); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="endpoint-route">
                                    <code><?php echo esc_html($endpoint['route']); ?></code>
                                </td>
                                <td class="endpoint-params">
                                    <?php if (!empty($endpoint['args'])): ?>
                                        <ul class="params-list">
                                            <?php foreach ($endpoint['args'] as $name => $config): ?>
                                                <li>
                                                    <code><?php echo esc_html($name); ?></code>
                                                    <span class="param-type">(<?php echo esc_html($config['type']); ?>)</span>
                                                    <?php if ($config['required']): ?>
                                                        <span class="param-required">*</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($config['description'])): ?>
                                                        <span class="param-desc"><?php echo esc_html($config['description']); ?></span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="no-params"><?php esc_html_e('No parameters', 'peanut-suite'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="endpoint-actions">
                                    <?php if (in_array('GET', $endpoint['methods'])): ?>
                                        <button type="button" class="button button-small test-endpoint"
                                                data-route="<?php echo esc_attr($endpoint['route']); ?>"
                                                data-method="GET">
                                            <?php esc_html_e('Test', 'peanut-suite'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Test Result Modal -->
    <div id="peanut-api-test-modal" class="peanut-modal" style="display: none;">
        <div class="peanut-modal-content">
            <div class="peanut-modal-header">
                <h3><?php esc_html_e('API Test Result', 'peanut-suite'); ?></h3>
                <button type="button" class="peanut-modal-close">&times;</button>
            </div>
            <div class="peanut-modal-body">
                <div class="test-result-info">
                    <div class="test-request">
                        <h4><?php esc_html_e('Request', 'peanut-suite'); ?></h4>
                        <pre class="request-details"></pre>
                    </div>
                    <div class="test-response">
                        <h4><?php esc_html_e('Response', 'peanut-suite'); ?></h4>
                        <div class="response-status"></div>
                        <pre class="response-body"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
// CSS and JavaScript are now loaded via wp_enqueue_style('peanut-api-checker') and wp_enqueue_script('peanut-api-checker')
// See: /core/admin/class-peanut-admin-assets.php

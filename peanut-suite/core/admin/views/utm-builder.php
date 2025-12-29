<?php
/**
 * UTM Builder View
 *
 * Create and manage UTM-tagged URLs for marketing campaigns.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Common source options
$sources = [
    'google' => 'Google',
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'twitter' => 'Twitter / X',
    'linkedin' => 'LinkedIn',
    'email' => 'Email',
    'newsletter' => 'Newsletter',
    'direct' => 'Direct',
];

// Common medium options
$mediums = [
    'cpc' => 'CPC (Cost Per Click)',
    'organic' => 'Organic',
    'social' => 'Social',
    'email' => 'Email',
    'referral' => 'Referral',
    'display' => 'Display',
    'affiliate' => 'Affiliate',
    'video' => 'Video',
];
?>

<div class="peanut-grid peanut-grid-2">
    <!-- UTM Builder Form -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3 class="peanut-card-title"><?php esc_html_e('Build Your URL', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <form id="peanut-utm-form" class="peanut-form peanut-utm-form">
                <?php wp_nonce_field('peanut_utm_create', 'peanut_utm_nonce'); ?>

                <!-- Website URL -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-url">
                        <?php esc_html_e('Website URL', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('The full URL of the page you want to track. Include https://', 'peanut-suite')); ?>
                    </label>
                    <input
                        type="url"
                        id="peanut-utm-url"
                        name="base_url"
                        class="peanut-form-input peanut-utm-field"
                        placeholder="https://yoursite.com/landing-page"
                        required
                    >
                    <?php echo peanut_field_help(__('The destination URL where visitors will land.', 'peanut-suite')); ?>
                </div>

                <!-- Campaign Source -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-source">
                        <?php esc_html_e('Campaign Source', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('Identifies which site sent the traffic. Required for all UTM tracking.', 'peanut-suite')); ?>
                    </label>
                    <select id="peanut-utm-source" name="utm_source" class="peanut-form-select peanut-utm-field" required>
                        <option value=""><?php esc_html_e('Select source...', 'peanut-suite'); ?></option>
                        <?php foreach ($sources as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                        <option value="custom"><?php esc_html_e('Custom...', 'peanut-suite'); ?></option>
                    </select>
                    <input
                        type="text"
                        id="peanut-utm-source-custom"
                        class="peanut-form-input peanut-utm-field"
                        placeholder="<?php esc_attr_e('Enter custom source', 'peanut-suite'); ?>"
                        style="display: none; margin-top: 8px;"
                    >
                    <?php echo peanut_field_examples(['google', 'facebook', 'newsletter', 'partner_name']); ?>
                </div>

                <!-- Campaign Medium -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-medium">
                        <?php esc_html_e('Campaign Medium', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('Identifies what type of link was used. Helps categorize your traffic.', 'peanut-suite')); ?>
                    </label>
                    <select id="peanut-utm-medium" name="utm_medium" class="peanut-form-select peanut-utm-field" required>
                        <option value=""><?php esc_html_e('Select medium...', 'peanut-suite'); ?></option>
                        <?php foreach ($mediums as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                        <option value="custom"><?php esc_html_e('Custom...', 'peanut-suite'); ?></option>
                    </select>
                    <input
                        type="text"
                        id="peanut-utm-medium-custom"
                        class="peanut-form-input peanut-utm-field"
                        placeholder="<?php esc_attr_e('Enter custom medium', 'peanut-suite'); ?>"
                        style="display: none; margin-top: 8px;"
                    >
                    <?php echo peanut_field_examples(['cpc', 'email', 'social', 'organic']); ?>
                </div>

                <!-- Campaign Name -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-campaign">
                        <?php esc_html_e('Campaign Name', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('A unique name for your campaign. Use underscores instead of spaces.', 'peanut-suite')); ?>
                    </label>
                    <input
                        type="text"
                        id="peanut-utm-campaign"
                        name="utm_campaign"
                        class="peanut-form-input peanut-utm-field"
                        placeholder="spring_sale_2024"
                        required
                    >
                    <?php echo peanut_field_help(__('Use lowercase letters and underscores. Be descriptive so you remember what this campaign was about.', 'peanut-suite')); ?>
                    <?php echo peanut_field_examples(['spring_sale', 'product_launch', 'weekly_newsletter']); ?>
                </div>

                <!-- Campaign Term (Optional) -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-term">
                        <?php esc_html_e('Campaign Term', 'peanut-suite'); ?>
                        <span class="optional">(<?php esc_html_e('optional', 'peanut-suite'); ?>)</span>
                        <?php echo peanut_tooltip(__('Used for paid search keywords. Track which keywords brought visitors.', 'peanut-suite')); ?>
                    </label>
                    <input
                        type="text"
                        id="peanut-utm-term"
                        name="utm_term"
                        class="peanut-form-input peanut-utm-field"
                        placeholder="running+shoes"
                    >
                    <?php echo peanut_field_help(__('Primarily used for paid search to identify keywords. Use + for spaces.', 'peanut-suite')); ?>
                </div>

                <!-- Campaign Content (Optional) -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-content">
                        <?php esc_html_e('Campaign Content', 'peanut-suite'); ?>
                        <span class="optional">(<?php esc_html_e('optional', 'peanut-suite'); ?>)</span>
                        <?php echo peanut_tooltip(__('Used to differentiate similar content or links. Useful for A/B testing.', 'peanut-suite')); ?>
                    </label>
                    <input
                        type="text"
                        id="peanut-utm-content"
                        name="utm_content"
                        class="peanut-form-input peanut-utm-field"
                        placeholder="header_cta"
                    >
                    <?php echo peanut_field_help(__('Differentiate ads or links that point to the same URL.', 'peanut-suite')); ?>
                    <?php echo peanut_field_examples(['header_link', 'footer_link', 'blue_button', 'image_ad']); ?>
                </div>

                <!-- Program/Initiative (Custom field) -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-program">
                        <?php esc_html_e('Program / Initiative', 'peanut-suite'); ?>
                        <span class="optional">(<?php esc_html_e('optional', 'peanut-suite'); ?>)</span>
                        <?php echo peanut_tooltip(__('Internal tracking field. Not added to URL, just stored for your reference.', 'peanut-suite')); ?>
                    </label>
                    <input
                        type="text"
                        id="peanut-utm-program"
                        name="program"
                        class="peanut-form-input"
                        placeholder="Q4 Marketing"
                    >
                    <?php echo peanut_field_help(__('This is stored internally but not added to the URL. Use for your own organization.', 'peanut-suite')); ?>
                </div>

                <!-- Notes -->
                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="peanut-utm-notes">
                        <?php esc_html_e('Notes', 'peanut-suite'); ?>
                        <span class="optional">(<?php esc_html_e('optional', 'peanut-suite'); ?>)</span>
                    </label>
                    <textarea
                        id="peanut-utm-notes"
                        name="notes"
                        class="peanut-form-textarea"
                        rows="2"
                        placeholder="<?php esc_attr_e('Add any notes about this campaign...', 'peanut-suite'); ?>"
                    ></textarea>
                </div>
            </form>
        </div>
    </div>

    <!-- URL Preview & Actions -->
    <div>
        <!-- Generated URL Preview -->
        <div class="peanut-card peanut-sticky-card">
            <div class="peanut-card-header">
                <h3 class="peanut-card-title"><?php esc_html_e('Generated URL', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <div class="peanut-url-preview">
                    <div class="peanut-url-preview-label"><?php esc_html_e('Your tracked URL', 'peanut-suite'); ?></div>
                    <div id="peanut-url-preview" class="peanut-url-preview-value">
                        <?php esc_html_e('Enter a URL above to generate your tracked link', 'peanut-suite'); ?>
                    </div>
                    <div class="peanut-url-preview-actions">
                        <button type="button" class="button" id="peanut-copy-url" disabled>
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php esc_html_e('Copy URL', 'peanut-suite'); ?>
                        </button>
                        <button type="button" class="button" id="peanut-test-url" disabled>
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e('Test URL', 'peanut-suite'); ?>
                        </button>
                    </div>
                </div>

                <!-- Parameter Breakdown -->
                <div id="peanut-params-breakdown" class="peanut-params-breakdown" style="display: none;">
                    <h4><?php esc_html_e('Parameter Breakdown', 'peanut-suite'); ?></h4>
                    <table class="peanut-params-table">
                        <tbody>
                            <tr data-param="source">
                                <td class="param-name">utm_source</td>
                                <td class="param-value">-</td>
                            </tr>
                            <tr data-param="medium">
                                <td class="param-name">utm_medium</td>
                                <td class="param-value">-</td>
                            </tr>
                            <tr data-param="campaign">
                                <td class="param-name">utm_campaign</td>
                                <td class="param-value">-</td>
                            </tr>
                            <tr data-param="term" style="display: none;">
                                <td class="param-name">utm_term</td>
                                <td class="param-value">-</td>
                            </tr>
                            <tr data-param="content" style="display: none;">
                                <td class="param-name">utm_content</td>
                                <td class="param-value">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="peanut-form-actions">
                    <button type="submit" form="peanut-utm-form" class="button button-primary button-hero">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e('Save to Library', 'peanut-suite'); ?>
                    </button>
                    <button type="button" id="peanut-utm-reset" class="button">
                        <?php esc_html_e('Reset Form', 'peanut-suite'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="peanut-card peanut-tips-card">
            <div class="peanut-card-header">
                <h3 class="peanut-card-title">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php esc_html_e('Quick Tips', 'peanut-suite'); ?>
                </h3>
            </div>
            <div class="peanut-card-body">
                <ul class="peanut-tips-list">
                    <li>
                        <span class="dashicons dashicons-yes"></span>
                        <strong><?php esc_html_e('Use lowercase', 'peanut-suite'); ?></strong> - <?php esc_html_e('google and Google are tracked separately', 'peanut-suite'); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-yes"></span>
                        <strong><?php esc_html_e('Use underscores', 'peanut-suite'); ?></strong> - <?php esc_html_e('Use spring_sale instead of spring sale', 'peanut-suite'); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-yes"></span>
                        <strong><?php esc_html_e('Be consistent', 'peanut-suite'); ?></strong> - <?php esc_html_e('Stick to naming conventions across campaigns', 'peanut-suite'); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-no"></span>
                        <strong><?php esc_html_e('Don\'t use on internal links', 'peanut-suite'); ?></strong> - <?php esc_html_e('Only use UTMs for external traffic sources', 'peanut-suite'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var $form = $('#peanut-utm-form');
    var $preview = $('#peanut-url-preview');
    var $copyBtn = $('#peanut-copy-url');
    var $testBtn = $('#peanut-test-url');
    var $paramsBreakdown = $('#peanut-params-breakdown');

    // Update preview on input
    function updatePreview() {
        var baseUrl = $('#peanut-utm-url').val();
        if (!baseUrl) {
            $preview.text('<?php echo esc_js(__('Enter a URL above to generate your tracked link', 'peanut-suite')); ?>');
            $copyBtn.prop('disabled', true);
            $testBtn.prop('disabled', true);
            $paramsBreakdown.hide();
            return;
        }

        var params = [];
        var fields = {
            source: $('#peanut-utm-source').val() === 'custom' ? $('#peanut-utm-source-custom').val() : $('#peanut-utm-source').val(),
            medium: $('#peanut-utm-medium').val() === 'custom' ? $('#peanut-utm-medium-custom').val() : $('#peanut-utm-medium').val(),
            campaign: $('#peanut-utm-campaign').val(),
            term: $('#peanut-utm-term').val(),
            content: $('#peanut-utm-content').val()
        };

        // Build params and update breakdown
        $.each(fields, function(key, value) {
            var $row = $paramsBreakdown.find('[data-param="' + key + '"]');
            if (value) {
                params.push('utm_' + key + '=' + encodeURIComponent(value));
                $row.show().find('.param-value').text(value);
            } else {
                if (key === 'term' || key === 'content') {
                    $row.hide();
                } else {
                    $row.find('.param-value').text('-');
                }
            }
        });

        var fullUrl = baseUrl;
        if (params.length > 0) {
            fullUrl += (baseUrl.indexOf('?') > -1 ? '&' : '?') + params.join('&');
        }

        $preview.text(fullUrl);
        $copyBtn.prop('disabled', false);
        $testBtn.prop('disabled', false);
        $paramsBreakdown.show();
    }

    // Bind events
    $('.peanut-utm-field').on('input change', updatePreview);

    // Handle custom source/medium
    $('#peanut-utm-source').on('change', function() {
        $('#peanut-utm-source-custom').toggle($(this).val() === 'custom');
    });

    $('#peanut-utm-medium').on('change', function() {
        $('#peanut-utm-medium-custom').toggle($(this).val() === 'custom');
    });

    // Copy URL
    $copyBtn.on('click', function() {
        var url = $preview.text();
        navigator.clipboard.writeText(url).then(function() {
            PeanutAdmin.showNotice('success', peanutAdmin.i18n.copied);
        });
    });

    // Test URL
    $testBtn.on('click', function() {
        var url = $preview.text();
        window.open(url, '_blank');
    });

    // Reset form
    $('#peanut-utm-reset').on('click', function() {
        $form[0].reset();
        $('#peanut-utm-source-custom, #peanut-utm-medium-custom').hide();
        updatePreview();
    });

    // Form submission
    $form.on('submit', function(e) {
        e.preventDefault();

        var $submitBtn = $form.find('[type="submit"]');
        $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'peanut-suite')); ?>');

        var formData = {
            base_url: $('#peanut-utm-url').val(),
            utm_source: $('#peanut-utm-source').val() === 'custom' ? $('#peanut-utm-source-custom').val() : $('#peanut-utm-source').val(),
            utm_medium: $('#peanut-utm-medium').val() === 'custom' ? $('#peanut-utm-medium-custom').val() : $('#peanut-utm-medium').val(),
            utm_campaign: $('#peanut-utm-campaign').val(),
            utm_term: $('#peanut-utm-term').val(),
            utm_content: $('#peanut-utm-content').val(),
            program: $('#peanut-utm-program').val(),
            notes: $('#peanut-utm-notes').val()
        };

        PeanutAdmin.api('utms', 'POST', formData)
            .done(function(response) {
                PeanutAdmin.showNotice('success', '<?php echo esc_js(__('UTM saved to library!', 'peanut-suite')); ?>');
                // Option to continue or go to library
            })
            .fail(function(xhr) {
                var message = xhr.responseJSON?.message || '<?php echo esc_js(__('Failed to save UTM', 'peanut-suite')); ?>';
                PeanutAdmin.showNotice('error', message);
            })
            .always(function() {
                $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> <?php echo esc_js(__('Save to Library', 'peanut-suite')); ?>');
            });
    });
});
</script>


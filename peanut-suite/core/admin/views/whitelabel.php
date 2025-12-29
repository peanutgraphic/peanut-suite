<?php
/**
 * White-Label View
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('peanut_whitelabel_settings', [
    'company_name' => '',
    'company_logo' => '',
    'primary_color' => '#2271b1',
    'secondary_color' => '#135e96',
    'hide_peanut_branding' => false,
    'custom_css' => '',
    'report_logo' => '',
    'report_footer' => '',
]);
?>

<div class="peanut-content">
    <form id="whitelabel-form">
        <?php wp_nonce_field('peanut_whitelabel', 'peanut_nonce'); ?>

        <!-- Branding -->
        <div class="peanut-card">
            <div class="peanut-card-header">
                <h3><?php esc_html_e('Company Branding', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <div class="peanut-form-row">
                    <label for="company-name"><?php esc_html_e('Company Name', 'peanut-suite'); ?></label>
                    <input type="text" id="company-name" name="company_name"
                           value="<?php echo esc_attr($settings['company_name']); ?>"
                           placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    <p class="description"><?php esc_html_e('Used in reports and client-facing areas.', 'peanut-suite'); ?></p>
                </div>

                <div class="peanut-form-row">
                    <label><?php esc_html_e('Company Logo', 'peanut-suite'); ?></label>
                    <div class="peanut-media-upload">
                        <input type="hidden" id="company-logo" name="company_logo"
                               value="<?php echo esc_attr($settings['company_logo']); ?>">
                        <div class="logo-preview" id="company-logo-preview">
                            <?php if ($settings['company_logo']): ?>
                                <img src="<?php echo esc_url($settings['company_logo']); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-format-image"></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button upload-logo" data-target="company-logo">
                            <?php esc_html_e('Upload Logo', 'peanut-suite'); ?>
                        </button>
                        <button type="button" class="button remove-logo" data-target="company-logo"
                                <?php echo empty($settings['company_logo']) ? 'style="display:none;"' : ''; ?>>
                            <?php esc_html_e('Remove', 'peanut-suite'); ?>
                        </button>
                    </div>
                    <p class="description"><?php esc_html_e('Recommended: 200x50px PNG with transparent background.', 'peanut-suite'); ?></p>
                </div>

                <div class="peanut-form-row">
                    <label>
                        <input type="checkbox" name="hide_peanut_branding" value="1"
                               <?php checked($settings['hide_peanut_branding']); ?>>
                        <?php esc_html_e('Hide "Powered by Peanut Suite" branding', 'peanut-suite'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Colors -->
        <div class="peanut-card">
            <div class="peanut-card-header">
                <h3><?php esc_html_e('Brand Colors', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <div class="peanut-color-grid">
                    <div class="peanut-form-row">
                        <label for="primary-color"><?php esc_html_e('Primary Color', 'peanut-suite'); ?></label>
                        <div class="color-input-group">
                            <input type="color" id="primary-color" name="primary_color"
                                   value="<?php echo esc_attr($settings['primary_color']); ?>">
                            <input type="text" class="color-hex" value="<?php echo esc_attr($settings['primary_color']); ?>">
                        </div>
                        <p class="description"><?php esc_html_e('Used for buttons, links, and accents.', 'peanut-suite'); ?></p>
                    </div>

                    <div class="peanut-form-row">
                        <label for="secondary-color"><?php esc_html_e('Secondary Color', 'peanut-suite'); ?></label>
                        <div class="color-input-group">
                            <input type="color" id="secondary-color" name="secondary_color"
                                   value="<?php echo esc_attr($settings['secondary_color']); ?>">
                            <input type="text" class="color-hex" value="<?php echo esc_attr($settings['secondary_color']); ?>">
                        </div>
                        <p class="description"><?php esc_html_e('Used for hover states and secondary elements.', 'peanut-suite'); ?></p>
                    </div>
                </div>

                <!-- Color Preview -->
                <div class="color-preview-box">
                    <h4><?php esc_html_e('Preview', 'peanut-suite'); ?></h4>
                    <div class="preview-elements">
                        <button type="button" class="preview-button primary"><?php esc_html_e('Primary Button', 'peanut-suite'); ?></button>
                        <button type="button" class="preview-button secondary"><?php esc_html_e('Secondary Button', 'peanut-suite'); ?></button>
                        <a href="#" class="preview-link"><?php esc_html_e('Sample Link', 'peanut-suite'); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports -->
        <div class="peanut-card">
            <div class="peanut-card-header">
                <h3><?php esc_html_e('Report Branding', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <div class="peanut-form-row">
                    <label><?php esc_html_e('Report Logo', 'peanut-suite'); ?></label>
                    <div class="peanut-media-upload">
                        <input type="hidden" id="report-logo" name="report_logo"
                               value="<?php echo esc_attr($settings['report_logo']); ?>">
                        <div class="logo-preview" id="report-logo-preview">
                            <?php if ($settings['report_logo']): ?>
                                <img src="<?php echo esc_url($settings['report_logo']); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-format-image"></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button upload-logo" data-target="report-logo">
                            <?php esc_html_e('Upload Logo', 'peanut-suite'); ?>
                        </button>
                        <button type="button" class="button remove-logo" data-target="report-logo"
                                <?php echo empty($settings['report_logo']) ? 'style="display:none;"' : ''; ?>>
                            <?php esc_html_e('Remove', 'peanut-suite'); ?>
                        </button>
                    </div>
                    <p class="description"><?php esc_html_e('Logo displayed at the top of PDF/email reports.', 'peanut-suite'); ?></p>
                </div>

                <div class="peanut-form-row">
                    <label for="report-footer"><?php esc_html_e('Report Footer Text', 'peanut-suite'); ?></label>
                    <textarea id="report-footer" name="report_footer" rows="3"
                              placeholder="<?php esc_attr_e('e.g., Contact us at support@yourcompany.com', 'peanut-suite'); ?>"><?php echo esc_textarea($settings['report_footer']); ?></textarea>
                    <p class="description"><?php esc_html_e('Custom text displayed at the bottom of reports.', 'peanut-suite'); ?></p>
                </div>
            </div>
        </div>

        <!-- Custom CSS -->
        <div class="peanut-card">
            <div class="peanut-card-header">
                <h3><?php esc_html_e('Custom CSS', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <div class="peanut-form-row">
                    <label for="custom-css"><?php esc_html_e('Additional CSS for Admin Pages', 'peanut-suite'); ?></label>
                    <textarea id="custom-css" name="custom_css" rows="10" class="code"
                              placeholder="/* Your custom CSS here */"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                    <p class="description"><?php esc_html_e('Advanced: Add custom CSS to modify the admin interface appearance.', 'peanut-suite'); ?></p>
                </div>
            </div>
        </div>

        <div class="peanut-form-actions">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Save White-Label Settings', 'peanut-suite'); ?>
            </button>
            <button type="button" class="button" id="reset-whitelabel">
                <?php esc_html_e('Reset to Defaults', 'peanut-suite'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.peanut-media-upload {
    display: flex;
    align-items: center;
    gap: 12px;
}
.logo-preview {
    width: 200px;
    height: 60px;
    border: 2px dashed #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9;
    overflow: hidden;
}
.logo-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.logo-preview .dashicons {
    font-size: 32px;
    color: #999;
}

.peanut-color-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}
.color-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.color-input-group input[type="color"] {
    width: 50px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 2px;
    cursor: pointer;
}
.color-input-group .color-hex {
    width: 100px;
    font-family: monospace;
}

.color-preview-box {
    margin-top: 24px;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}
.color-preview-box h4 {
    margin: 0 0 16px;
}
.preview-elements {
    display: flex;
    align-items: center;
    gap: 16px;
}
.preview-button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}
.preview-button.primary {
    background: var(--primary-color, #2271b1);
    color: #fff;
}
.preview-button.secondary {
    background: #fff;
    color: var(--primary-color, #2271b1);
    border: 1px solid var(--primary-color, #2271b1);
}
.preview-link {
    color: var(--primary-color, #2271b1);
}

#custom-css {
    font-family: monospace;
    font-size: 13px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Media uploader
    let mediaFrame;

    $('.upload-logo').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('target');

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: '<?php esc_html_e('Select Logo', 'peanut-suite'); ?>',
            button: { text: '<?php esc_html_e('Use Logo', 'peanut-suite'); ?>' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.url);
            $('#' + target + '-preview').html('<img src="' + attachment.url + '" alt="">');
            $('[data-target="' + target + '"].remove-logo').show();
        });

        mediaFrame.targetInput = target;
        mediaFrame.open();
    });

    $('.remove-logo').on('click', function() {
        const target = $(this).data('target');
        $('#' + target).val('');
        $('#' + target + '-preview').html('<span class="dashicons dashicons-format-image"></span>');
        $(this).hide();
    });

    // Color sync
    $('input[type="color"]').on('input', function() {
        $(this).siblings('.color-hex').val($(this).val());
        updatePreview();
    });

    $('.color-hex').on('input', function() {
        const hex = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(hex)) {
            $(this).siblings('input[type="color"]').val(hex);
            updatePreview();
        }
    });

    function updatePreview() {
        const primary = $('#primary-color').val();
        const secondary = $('#secondary-color').val();

        $('.color-preview-box').css('--primary-color', primary);
        $('.preview-button.primary').css('background', primary);
        $('.preview-button.secondary').css({
            'color': primary,
            'border-color': primary
        });
        $('.preview-link').css('color', primary);
    }

    updatePreview();

    // Save form
    $('#whitelabel-form').on('submit', function(e) {
        e.preventDefault();

        $.post(ajaxurl, {
            action: 'peanut_save_whitelabel_settings',
            nonce: $('[name="peanut_nonce"]').val(),
            company_name: $('#company-name').val(),
            company_logo: $('#company-logo').val(),
            primary_color: $('#primary-color').val(),
            secondary_color: $('#secondary-color').val(),
            hide_peanut_branding: $('[name="hide_peanut_branding"]').is(':checked') ? 1 : 0,
            report_logo: $('#report-logo').val(),
            report_footer: $('#report-footer').val(),
            custom_css: $('#custom-css').val()
        }, function(response) {
            if (response.success) {
                alert('Settings saved successfully!');
            } else {
                alert(response.data || 'Error saving settings');
            }
        });
    });

    // Reset
    $('#reset-whitelabel').on('click', function() {
        if (confirm('<?php esc_html_e('Reset all white-label settings to defaults?', 'peanut-suite'); ?>')) {
            $.post(ajaxurl, {
                action: 'peanut_reset_whitelabel_settings',
                nonce: '<?php echo wp_create_nonce('peanut_whitelabel'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    });
});
</script>

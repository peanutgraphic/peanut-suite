<?php
/**
 * Accessibility View
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('peanut_accessibility_settings', [
    'widget_enabled' => false,
    'widget_position' => 'bottom-right',
    'widget_color' => '#2271b1',
    'skip_link' => true,
]);

// Get latest scan results
$scan_results = get_option('peanut_accessibility_scan', []);
$last_scan = get_option('peanut_accessibility_last_scan', '');

// Get alt text report
global $wpdb;
$images_without_alt = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
     WHERE p.post_type = 'attachment'
     AND p.post_mime_type LIKE 'image/%'
     AND (pm.meta_value IS NULL OR pm.meta_value = '')"
);
$total_images = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
);
$images_with_alt = $total_images - $images_without_alt;
$alt_percentage = $total_images > 0 ? round(($images_with_alt / $total_images) * 100, 1) : 0;
?>

<div class="peanut-content">
    <div class="peanut-tabs">
        <nav class="peanut-tab-nav">
            <a href="#widget" class="active"><?php esc_html_e('Widget', 'peanut-suite'); ?></a>
            <a href="#scanner"><?php esc_html_e('Scanner', 'peanut-suite'); ?></a>
            <a href="#alt-text"><?php esc_html_e('Alt Text', 'peanut-suite'); ?></a>
            <a href="#contrast"><?php esc_html_e('Contrast Checker', 'peanut-suite'); ?></a>
            <a href="#statement"><?php esc_html_e('Statement', 'peanut-suite'); ?></a>
        </nav>

        <!-- Widget Tab -->
        <div id="widget" class="peanut-tab-content active">
            <form id="widget-settings-form">
                <?php wp_nonce_field('peanut_accessibility', 'peanut_nonce'); ?>

                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3><?php esc_html_e('Accessibility Widget Settings', 'peanut-suite'); ?></h3>
                    </div>
                    <div class="peanut-card-body">
                        <div class="peanut-form-row">
                            <label>
                                <input type="checkbox" name="widget_enabled" value="1"
                                       <?php checked($settings['widget_enabled']); ?>>
                                <?php esc_html_e('Enable accessibility widget on frontend', 'peanut-suite'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Adds a floating button that opens accessibility options for visitors.', 'peanut-suite'); ?></p>
                        </div>

                        <div class="peanut-form-row">
                            <label for="widget-position"><?php esc_html_e('Widget Position', 'peanut-suite'); ?></label>
                            <select id="widget-position" name="widget_position">
                                <option value="bottom-right" <?php selected($settings['widget_position'], 'bottom-right'); ?>>
                                    <?php esc_html_e('Bottom Right', 'peanut-suite'); ?>
                                </option>
                                <option value="bottom-left" <?php selected($settings['widget_position'], 'bottom-left'); ?>>
                                    <?php esc_html_e('Bottom Left', 'peanut-suite'); ?>
                                </option>
                                <option value="top-right" <?php selected($settings['widget_position'], 'top-right'); ?>>
                                    <?php esc_html_e('Top Right', 'peanut-suite'); ?>
                                </option>
                                <option value="top-left" <?php selected($settings['widget_position'], 'top-left'); ?>>
                                    <?php esc_html_e('Top Left', 'peanut-suite'); ?>
                                </option>
                            </select>
                        </div>

                        <div class="peanut-form-row">
                            <label for="widget-color"><?php esc_html_e('Widget Color', 'peanut-suite'); ?></label>
                            <input type="color" id="widget-color" name="widget_color"
                                   value="<?php echo esc_attr($settings['widget_color']); ?>">
                        </div>

                        <div class="peanut-form-row">
                            <label>
                                <input type="checkbox" name="skip_link" value="1"
                                       <?php checked($settings['skip_link'] ?? true); ?>>
                                <?php esc_html_e('Add "Skip to Content" link', 'peanut-suite'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Adds a hidden link that appears on Tab key for keyboard navigation.', 'peanut-suite'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Widget Features -->
                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3><?php esc_html_e('Widget Features', 'peanut-suite'); ?></h3>
                    </div>
                    <div class="peanut-card-body">
                        <div class="peanut-features-grid">
                            <div class="feature-item">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                <strong><?php esc_html_e('Font Size', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Increase/decrease text size', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <strong><?php esc_html_e('Contrast Modes', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('High contrast & invert colors', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-admin-links"></span>
                                <strong><?php esc_html_e('Highlight Links', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Make links more visible', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-text"></span>
                                <strong><?php esc_html_e('Readable Font', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Switch to dyslexia-friendly font', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-visibility"></span>
                                <strong><?php esc_html_e('Focus Mode', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Dim inactive content', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-controls-pause"></span>
                                <strong><?php esc_html_e('Pause Animations', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Stop all motion', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                <strong><?php esc_html_e('Large Cursor', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Bigger mouse pointer', 'peanut-suite'); ?></p>
                            </div>
                            <div class="feature-item">
                                <span class="dashicons dashicons-editor-expand"></span>
                                <strong><?php esc_html_e('Text Spacing', 'peanut-suite'); ?></strong>
                                <p><?php esc_html_e('Increase line/letter spacing', 'peanut-suite'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="peanut-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Widget Settings', 'peanut-suite'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Scanner Tab -->
        <div id="scanner" class="peanut-tab-content">
            <div class="peanut-card">
                <div class="peanut-card-header">
                    <h3><?php esc_html_e('Accessibility Scanner', 'peanut-suite'); ?></h3>
                    <button type="button" class="button button-primary" id="run-scan">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Run Scan', 'peanut-suite'); ?>
                    </button>
                </div>
                <div class="peanut-card-body">
                    <?php if ($last_scan): ?>
                        <p class="scan-date">
                            <?php printf(__('Last scan: %s', 'peanut-suite'), date('F j, Y g:i a', strtotime($last_scan))); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($scan_results['issues'])): ?>
                        <table class="peanut-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Issue', 'peanut-suite'); ?></th>
                                    <th><?php esc_html_e('WCAG', 'peanut-suite'); ?></th>
                                    <th><?php esc_html_e('Impact', 'peanut-suite'); ?></th>
                                    <th><?php esc_html_e('Count', 'peanut-suite'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scan_results['issues'] as $issue): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($issue['title']); ?></strong>
                                            <p class="description"><?php echo esc_html($issue['description']); ?></p>
                                        </td>
                                        <td><code><?php echo esc_html($issue['wcag']); ?></code></td>
                                        <td>
                                            <span class="peanut-badge peanut-badge-<?php echo esc_attr($issue['impact']); ?>">
                                                <?php echo esc_html(ucfirst($issue['impact'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($issue['count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($last_scan): ?>
                        <div class="peanut-success-state">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <h3><?php esc_html_e('No Issues Found!', 'peanut-suite'); ?></h3>
                            <p><?php esc_html_e('Your site passed all accessibility checks.', 'peanut-suite'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="peanut-empty-state">
                            <span class="dashicons dashicons-universal-access-alt"></span>
                            <h3><?php esc_html_e('Run Your First Scan', 'peanut-suite'); ?></h3>
                            <p><?php esc_html_e('Click "Run Scan" to check your site for accessibility issues.', 'peanut-suite'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Alt Text Tab -->
        <div id="alt-text" class="peanut-tab-content">
            <div class="peanut-card">
                <div class="peanut-card-header">
                    <h3><?php esc_html_e('Image Alt Text Report', 'peanut-suite'); ?></h3>
                </div>
                <div class="peanut-card-body">
                    <div class="peanut-alt-stats">
                        <div class="alt-stat">
                            <div class="stat-value"><?php echo esc_html($total_images); ?></div>
                            <div class="stat-label"><?php esc_html_e('Total Images', 'peanut-suite'); ?></div>
                        </div>
                        <div class="alt-stat good">
                            <div class="stat-value"><?php echo esc_html($images_with_alt); ?></div>
                            <div class="stat-label"><?php esc_html_e('With Alt Text', 'peanut-suite'); ?></div>
                        </div>
                        <div class="alt-stat bad">
                            <div class="stat-value"><?php echo esc_html($images_without_alt); ?></div>
                            <div class="stat-label"><?php esc_html_e('Missing Alt Text', 'peanut-suite'); ?></div>
                        </div>
                        <div class="alt-stat">
                            <div class="stat-value"><?php echo esc_html($alt_percentage); ?>%</div>
                            <div class="stat-label"><?php esc_html_e('Compliance', 'peanut-suite'); ?></div>
                        </div>
                    </div>

                    <?php if ($images_without_alt > 0): ?>
                        <a href="<?php echo admin_url('upload.php?mode=list'); ?>" class="button">
                            <?php esc_html_e('View Media Library', 'peanut-suite'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contrast Tab -->
        <div id="contrast" class="peanut-tab-content">
            <div class="peanut-card">
                <div class="peanut-card-header">
                    <h3><?php esc_html_e('Color Contrast Checker', 'peanut-suite'); ?></h3>
                </div>
                <div class="peanut-card-body">
                    <div class="contrast-checker">
                        <div class="peanut-form-row">
                            <label for="foreground-color"><?php esc_html_e('Text Color', 'peanut-suite'); ?></label>
                            <input type="color" id="foreground-color" value="#000000">
                            <input type="text" id="foreground-hex" value="#000000" maxlength="7">
                        </div>
                        <div class="peanut-form-row">
                            <label for="background-color"><?php esc_html_e('Background Color', 'peanut-suite'); ?></label>
                            <input type="color" id="background-color" value="#FFFFFF">
                            <input type="text" id="background-hex" value="#FFFFFF" maxlength="7">
                        </div>
                        <div id="contrast-preview">
                            <p><?php esc_html_e('Sample text preview', 'peanut-suite'); ?></p>
                        </div>
                        <div id="contrast-result">
                            <div class="result-ratio">
                                <strong><?php esc_html_e('Contrast Ratio:', 'peanut-suite'); ?></strong>
                                <span id="ratio-value">21:1</span>
                            </div>
                            <div class="result-checks">
                                <div class="check-item" id="check-aa-normal">
                                    <span class="dashicons"></span>
                                    <?php esc_html_e('AA Normal Text (4.5:1)', 'peanut-suite'); ?>
                                </div>
                                <div class="check-item" id="check-aa-large">
                                    <span class="dashicons"></span>
                                    <?php esc_html_e('AA Large Text (3:1)', 'peanut-suite'); ?>
                                </div>
                                <div class="check-item" id="check-aaa-normal">
                                    <span class="dashicons"></span>
                                    <?php esc_html_e('AAA Normal Text (7:1)', 'peanut-suite'); ?>
                                </div>
                                <div class="check-item" id="check-aaa-large">
                                    <span class="dashicons"></span>
                                    <?php esc_html_e('AAA Large Text (4.5:1)', 'peanut-suite'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statement Tab -->
        <div id="statement" class="peanut-tab-content">
            <div class="peanut-card">
                <div class="peanut-card-header">
                    <h3><?php esc_html_e('Accessibility Statement Generator', 'peanut-suite'); ?></h3>
                </div>
                <div class="peanut-card-body">
                    <form id="statement-form">
                        <div class="peanut-form-row">
                            <label for="org-name"><?php esc_html_e('Organization Name', 'peanut-suite'); ?></label>
                            <input type="text" id="org-name" value="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        </div>
                        <div class="peanut-form-row">
                            <label for="contact-email"><?php esc_html_e('Contact Email', 'peanut-suite'); ?></label>
                            <input type="email" id="contact-email" value="<?php echo esc_attr(get_option('admin_email')); ?>">
                        </div>
                        <div class="peanut-form-row">
                            <label for="conformance-level"><?php esc_html_e('Conformance Level', 'peanut-suite'); ?></label>
                            <select id="conformance-level">
                                <option value="partial"><?php esc_html_e('Partial Conformance', 'peanut-suite'); ?></option>
                                <option value="A"><?php esc_html_e('WCAG 2.1 Level A', 'peanut-suite'); ?></option>
                                <option value="AA"><?php esc_html_e('WCAG 2.1 Level AA', 'peanut-suite'); ?></option>
                                <option value="AAA"><?php esc_html_e('WCAG 2.1 Level AAA', 'peanut-suite'); ?></option>
                            </select>
                        </div>
                        <button type="button" class="button button-primary" id="generate-statement">
                            <?php esc_html_e('Generate Statement', 'peanut-suite'); ?>
                        </button>
                    </form>

                    <div id="statement-output" style="display:none;">
                        <h4><?php esc_html_e('Generated Statement', 'peanut-suite'); ?></h4>
                        <textarea id="statement-text" rows="15" readonly></textarea>
                        <button type="button" class="button" id="copy-statement">
                            <?php esc_html_e('Copy to Clipboard', 'peanut-suite'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.peanut-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.feature-item {
    padding: 16px;
    background: #f9f9f9;
    border-radius: 8px;
    text-align: center;
}
.feature-item .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #2271b1;
    margin-bottom: 8px;
}
.feature-item strong {
    display: block;
    margin-bottom: 4px;
}
.feature-item p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.peanut-alt-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
.alt-stat {
    text-align: center;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}
.alt-stat.good { background: #d4edda; }
.alt-stat.bad { background: #f8d7da; }
.alt-stat .stat-value {
    font-size: 32px;
    font-weight: 700;
}

.contrast-checker {
    max-width: 500px;
}
#contrast-preview {
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
    font-size: 18px;
}
.result-checks {
    margin-top: 16px;
}
.check-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
}
.check-item.pass .dashicons { color: #28a745; }
.check-item.pass .dashicons:before { content: "\f147"; }
.check-item.fail .dashicons { color: #dc3545; }
.check-item.fail .dashicons:before { content: "\f335"; }

#statement-text {
    width: 100%;
    margin: 16px 0;
    font-family: monospace;
}

.peanut-badge-critical { background: #dc3545; color: #fff; }
.peanut-badge-serious { background: #fd7e14; color: #fff; }
.peanut-badge-moderate { background: #ffc107; color: #000; }
.peanut-badge-minor { background: #6c757d; color: #fff; }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.peanut-tab-nav a').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        $('.peanut-tab-nav a').removeClass('active');
        $(this).addClass('active');
        $('.peanut-tab-content').removeClass('active');
        $(target).addClass('active');
    });

    // Save widget settings
    $('#widget-settings-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ajaxurl, {
            action: 'peanut_save_accessibility_settings',
            nonce: $('[name="peanut_nonce"]').val(),
            widget_enabled: $('[name="widget_enabled"]').is(':checked') ? 1 : 0,
            widget_position: $('#widget-position').val(),
            widget_color: $('#widget-color').val(),
            skip_link: $('[name="skip_link"]').is(':checked') ? 1 : 0
        }, function(response) {
            alert(response.success ? 'Settings saved!' : 'Error saving settings');
        });
    });

    // Run scan
    $('#run-scan').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Scanning...');

        $.post(ajaxurl, {
            action: 'peanut_run_accessibility_scan',
            nonce: '<?php echo wp_create_nonce('peanut_accessibility'); ?>'
        }, function(response) {
            location.reload();
        });
    });

    // Contrast checker
    function hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    function luminance(r, g, b) {
        const [rs, gs, bs] = [r, g, b].map(c => {
            c = c / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
    }

    function contrastRatio(l1, l2) {
        const lighter = Math.max(l1, l2);
        const darker = Math.min(l1, l2);
        return (lighter + 0.05) / (darker + 0.05);
    }

    function updateContrast() {
        const fg = hexToRgb($('#foreground-color').val());
        const bg = hexToRgb($('#background-color').val());

        if (!fg || !bg) return;

        const ratio = contrastRatio(luminance(fg.r, fg.g, fg.b), luminance(bg.r, bg.g, bg.b));

        $('#contrast-preview').css({
            color: $('#foreground-color').val(),
            backgroundColor: $('#background-color').val()
        });

        $('#ratio-value').text(ratio.toFixed(2) + ':1');

        $('#check-aa-normal').removeClass('pass fail').addClass(ratio >= 4.5 ? 'pass' : 'fail');
        $('#check-aa-large').removeClass('pass fail').addClass(ratio >= 3 ? 'pass' : 'fail');
        $('#check-aaa-normal').removeClass('pass fail').addClass(ratio >= 7 ? 'pass' : 'fail');
        $('#check-aaa-large').removeClass('pass fail').addClass(ratio >= 4.5 ? 'pass' : 'fail');
    }

    $('#foreground-color, #background-color').on('input', function() {
        const hex = $(this).val();
        $(this).next('input').val(hex);
        updateContrast();
    });

    $('#foreground-hex, #background-hex').on('input', function() {
        const hex = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(hex)) {
            $(this).prev('input').val(hex);
            updateContrast();
        }
    });

    updateContrast();

    // Generate statement
    $('#generate-statement').on('click', function() {
        const org = $('#org-name').val();
        const email = $('#contact-email').val();
        const level = $('#conformance-level').val();
        const date = new Date().toLocaleDateString();

        let levelText = 'partially conforms to';
        if (level === 'A') levelText = 'conforms to WCAG 2.1 Level A';
        if (level === 'AA') levelText = 'conforms to WCAG 2.1 Level AA';
        if (level === 'AAA') levelText = 'conforms to WCAG 2.1 Level AAA';

        const statement = `ACCESSIBILITY STATEMENT

${org} is committed to ensuring digital accessibility for people with disabilities. We are continually improving the user experience for everyone and applying the relevant accessibility standards.

CONFORMANCE STATUS
This website ${levelText} guidelines. We welcome your feedback on the accessibility of this site.

FEEDBACK
We welcome your feedback on the accessibility of our website. Please let us know if you encounter accessibility barriers:
- Email: ${email}

TECHNICAL SPECIFICATIONS
This website relies upon the following technologies for conformance with WCAG 2.1:
- HTML
- CSS
- JavaScript
- WAI-ARIA

ASSESSMENT APPROACH
${org} assessed the accessibility of this website using self-evaluation.

DATE
This statement was last updated on ${date}.`;

        $('#statement-text').val(statement);
        $('#statement-output').show();
    });

    // Copy statement
    $('#copy-statement').on('click', function() {
        $('#statement-text').select();
        document.execCommand('copy');
        $(this).text('Copied!');
        setTimeout(() => $(this).text('Copy to Clipboard'), 2000);
    });
});
</script>

<?php
/**
 * Notifications View
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('peanut_notification_settings', [
    'slack_webhook' => '',
    'discord_webhook' => '',
    'telegram_bot_token' => '',
    'telegram_chat_id' => '',
]);

$events = get_option('peanut_notification_events', [
    'contact_created' => [],
    'form_submitted' => [],
    'order_completed' => [],
    'visitor_converted' => [],
    'utm_click' => [],
]);
?>

<div class="peanut-content">
    <div class="peanut-tabs">
        <nav class="peanut-tab-nav">
            <a href="#channels" class="active"><?php esc_html_e('Channels', 'peanut-suite'); ?></a>
            <a href="#events"><?php esc_html_e('Events', 'peanut-suite'); ?></a>
            <a href="#test"><?php esc_html_e('Test', 'peanut-suite'); ?></a>
        </nav>

        <!-- Channels Tab -->
        <div id="channels" class="peanut-tab-content active">
            <form id="notification-channels-form">
                <?php wp_nonce_field('peanut_notifications', 'peanut_nonce'); ?>

                <!-- Slack -->
                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3>
                            <span class="peanut-channel-icon slack"></span>
                            <?php esc_html_e('Slack', 'peanut-suite'); ?>
                        </h3>
                    </div>
                    <div class="peanut-card-body">
                        <div class="peanut-form-row">
                            <label for="slack-webhook"><?php esc_html_e('Webhook URL', 'peanut-suite'); ?></label>
                            <input type="url" id="slack-webhook" name="slack_webhook"
                                   value="<?php echo esc_attr($settings['slack_webhook']); ?>"
                                   placeholder="https://hooks.slack.com/services/...">
                            <p class="description">
                                <?php printf(
                                    __('Create a webhook in your Slack workspace: %s', 'peanut-suite'),
                                    '<a href="https://api.slack.com/messaging/webhooks" target="_blank">Slack Webhooks</a>'
                                ); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Discord -->
                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3>
                            <span class="peanut-channel-icon discord"></span>
                            <?php esc_html_e('Discord', 'peanut-suite'); ?>
                        </h3>
                    </div>
                    <div class="peanut-card-body">
                        <div class="peanut-form-row">
                            <label for="discord-webhook"><?php esc_html_e('Webhook URL', 'peanut-suite'); ?></label>
                            <input type="url" id="discord-webhook" name="discord_webhook"
                                   value="<?php echo esc_attr($settings['discord_webhook']); ?>"
                                   placeholder="https://discord.com/api/webhooks/...">
                            <p class="description">
                                <?php esc_html_e('Create a webhook in your Discord server: Server Settings > Integrations > Webhooks', 'peanut-suite'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Telegram -->
                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3>
                            <span class="peanut-channel-icon telegram"></span>
                            <?php esc_html_e('Telegram', 'peanut-suite'); ?>
                        </h3>
                    </div>
                    <div class="peanut-card-body">
                        <div class="peanut-form-row">
                            <label for="telegram-bot-token"><?php esc_html_e('Bot Token', 'peanut-suite'); ?></label>
                            <input type="text" id="telegram-bot-token" name="telegram_bot_token"
                                   value="<?php echo esc_attr($settings['telegram_bot_token']); ?>"
                                   placeholder="123456:ABC-DEF...">
                            <p class="description">
                                <?php printf(
                                    __('Create a bot with %s and get the token', 'peanut-suite'),
                                    '<a href="https://t.me/BotFather" target="_blank">@BotFather</a>'
                                ); ?>
                            </p>
                        </div>
                        <div class="peanut-form-row">
                            <label for="telegram-chat-id"><?php esc_html_e('Chat ID', 'peanut-suite'); ?></label>
                            <input type="text" id="telegram-chat-id" name="telegram_chat_id"
                                   value="<?php echo esc_attr($settings['telegram_chat_id']); ?>"
                                   placeholder="-1001234567890">
                            <p class="description">
                                <?php esc_html_e('Your personal or group chat ID (use @userinfobot to find it)', 'peanut-suite'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="peanut-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Channels', 'peanut-suite'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Events Tab -->
        <div id="events" class="peanut-tab-content">
            <form id="notification-events-form">
                <?php wp_nonce_field('peanut_notifications', 'peanut_nonce'); ?>

                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3><?php esc_html_e('Event Notifications', 'peanut-suite'); ?></h3>
                        <p><?php esc_html_e('Choose which channels receive notifications for each event.', 'peanut-suite'); ?></p>
                    </div>
                    <div class="peanut-card-body">
                        <table class="peanut-table peanut-events-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Event', 'peanut-suite'); ?></th>
                                    <th><span class="peanut-channel-icon slack small"></span> Slack</th>
                                    <th><span class="peanut-channel-icon discord small"></span> Discord</th>
                                    <th><span class="peanut-channel-icon telegram small"></span> Telegram</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $event_labels = [
                                    'contact_created' => __('New Contact Created', 'peanut-suite'),
                                    'form_submitted' => __('Form Submitted', 'peanut-suite'),
                                    'order_completed' => __('WooCommerce Order Completed', 'peanut-suite'),
                                    'visitor_converted' => __('Visitor Converted', 'peanut-suite'),
                                    'utm_click' => __('UTM Link Clicked', 'peanut-suite'),
                                ];
                                foreach ($event_labels as $event => $label):
                                    $event_channels = $events[$event] ?? [];
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($label); ?></strong></td>
                                        <td>
                                            <input type="checkbox" name="events[<?php echo esc_attr($event); ?>][]"
                                                   value="slack" <?php checked(in_array('slack', $event_channels)); ?>>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="events[<?php echo esc_attr($event); ?>][]"
                                                   value="discord" <?php checked(in_array('discord', $event_channels)); ?>>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="events[<?php echo esc_attr($event); ?>][]"
                                                   value="telegram" <?php checked(in_array('telegram', $event_channels)); ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="peanut-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Event Settings', 'peanut-suite'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Test Tab -->
        <div id="test" class="peanut-tab-content">
            <div class="peanut-card">
                <div class="peanut-card-header">
                    <h3><?php esc_html_e('Test Notifications', 'peanut-suite'); ?></h3>
                </div>
                <div class="peanut-card-body">
                    <p><?php esc_html_e('Send a test message to verify your channels are configured correctly.', 'peanut-suite'); ?></p>

                    <div class="peanut-test-buttons">
                        <button type="button" class="button test-notification" data-channel="slack"
                                <?php echo empty($settings['slack_webhook']) ? 'disabled' : ''; ?>>
                            <span class="peanut-channel-icon slack small"></span>
                            <?php esc_html_e('Test Slack', 'peanut-suite'); ?>
                        </button>
                        <button type="button" class="button test-notification" data-channel="discord"
                                <?php echo empty($settings['discord_webhook']) ? 'disabled' : ''; ?>>
                            <span class="peanut-channel-icon discord small"></span>
                            <?php esc_html_e('Test Discord', 'peanut-suite'); ?>
                        </button>
                        <button type="button" class="button test-notification" data-channel="telegram"
                                <?php echo empty($settings['telegram_bot_token']) ? 'disabled' : ''; ?>>
                            <span class="peanut-channel-icon telegram small"></span>
                            <?php esc_html_e('Test Telegram', 'peanut-suite'); ?>
                        </button>
                    </div>

                    <div id="test-result" class="peanut-test-result" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.peanut-channel-icon {
    display: inline-block;
    width: 24px;
    height: 24px;
    vertical-align: middle;
    margin-right: 8px;
    border-radius: 4px;
}
.peanut-channel-icon.small {
    width: 18px;
    height: 18px;
}
.peanut-channel-icon.slack {
    background: #4A154B;
}
.peanut-channel-icon.discord {
    background: #5865F2;
}
.peanut-channel-icon.telegram {
    background: #0088cc;
}
.peanut-events-table th:not(:first-child),
.peanut-events-table td:not(:first-child) {
    text-align: center;
    width: 100px;
}
.peanut-test-buttons {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}
.peanut-test-result {
    margin-top: 16px;
    padding: 12px;
    border-radius: 4px;
}
.peanut-test-result.success {
    background: #d4edda;
    color: #155724;
}
.peanut-test-result.error {
    background: #f8d7da;
    color: #721c24;
}
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

    // Save channels
    $('#notification-channels-form').on('submit', function(e) {
        e.preventDefault();
        const data = {
            action: 'peanut_save_notification_channels',
            nonce: $('[name="peanut_nonce"]').val(),
            slack_webhook: $('#slack-webhook').val(),
            discord_webhook: $('#discord-webhook').val(),
            telegram_bot_token: $('#telegram-bot-token').val(),
            telegram_chat_id: $('#telegram-chat-id').val()
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert('Channels saved successfully!');
                location.reload();
            } else {
                alert(response.data || 'Error saving channels');
            }
        });
    });

    // Save events
    $('#notification-events-form').on('submit', function(e) {
        e.preventDefault();
        const data = {
            action: 'peanut_save_notification_events',
            nonce: $('[name="peanut_nonce"]').val(),
            events: {}
        };

        // Collect checked events
        $('[name^="events["]').each(function() {
            const match = $(this).attr('name').match(/events\[([^\]]+)\]/);
            if (match) {
                const event = match[1];
                if (!data.events[event]) data.events[event] = [];
                if ($(this).is(':checked')) {
                    data.events[event].push($(this).val());
                }
            }
        });

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert('Event settings saved!');
            } else {
                alert(response.data || 'Error saving events');
            }
        });
    });

    // Test notification
    $('.test-notification').on('click', function() {
        const channel = $(this).data('channel');
        const $result = $('#test-result');
        const $btn = $(this);

        $btn.prop('disabled', true).text('Sending...');
        $result.hide();

        $.post(ajaxurl, {
            action: 'peanut_test_notification',
            nonce: '<?php echo wp_create_nonce('peanut_notifications'); ?>',
            channel: channel
        }, function(response) {
            $btn.prop('disabled', false);
            $btn.html($btn.data('original-html') || $btn.html());

            $result.show()
                   .removeClass('success error')
                   .addClass(response.success ? 'success' : 'error')
                   .text(response.data || (response.success ? 'Test sent successfully!' : 'Failed to send test'));
        });
    });
});
</script>

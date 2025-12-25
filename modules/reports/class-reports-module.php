<?php
/**
 * Email Digest Reports Module
 *
 * Scheduled email reports with marketing analytics summaries.
 * Features:
 * - Daily, weekly, monthly digest options
 * - PDF export capability
 * - Multiple recipient support
 * - Customizable report sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reports_Module {

    /**
     * Module instance
     */
    private static ?Reports_Module $instance = null;

    /**
     * Settings
     */
    private array $settings = [];

    /**
     * Get singleton instance
     */
    public static function instance(): Reports_Module {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('peanut_reports_settings', $this->get_defaults());
        $this->init_hooks();
    }

    /**
     * Get default settings
     */
    private function get_defaults(): array {
        return [
            'enabled' => false,
            'frequency' => 'weekly', // daily, weekly, monthly
            'day_of_week' => 1, // Monday
            'day_of_month' => 1,
            'time' => '08:00',
            'recipients' => [get_option('admin_email')],
            'include_sections' => [
                'overview' => true,
                'utm_campaigns' => true,
                'links' => true,
                'contacts' => true,
                'visitors' => true,
                'top_performers' => true,
            ],
            'attach_pdf' => true,
            'custom_logo' => '',
            'custom_footer' => '',
        ];
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Cron for sending reports
        add_action('peanut_send_digest_report', [$this, 'send_scheduled_report']);

        // Schedule cron if enabled
        if ($this->settings['enabled']) {
            $this->schedule_reports();
        }

        // REST API
        add_action('rest_api_init', [$this, 'register_routes']);

        // Settings update hook
        add_action('update_option_peanut_reports_settings', [$this, 'on_settings_update'], 10, 2);
    }

    /**
     * Schedule report sending
     */
    private function schedule_reports(): void {
        $hook = 'peanut_send_digest_report';

        // Clear existing schedule
        $timestamp = wp_next_scheduled($hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
        }

        // Calculate next run time
        $next_run = $this->calculate_next_run();

        if ($next_run) {
            wp_schedule_single_event($next_run, $hook);
        }
    }

    /**
     * Calculate next run timestamp
     */
    private function calculate_next_run(): int {
        $frequency = $this->settings['frequency'];
        $time = $this->settings['time'] ?? '08:00';
        list($hour, $minute) = explode(':', $time);

        $now = current_time('timestamp');

        switch ($frequency) {
            case 'daily':
                $next = strtotime("today {$hour}:{$minute}", $now);
                if ($next <= $now) {
                    $next = strtotime("tomorrow {$hour}:{$minute}", $now);
                }
                break;

            case 'weekly':
                $day_of_week = (int) ($this->settings['day_of_week'] ?? 1);
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $day_name = $days[$day_of_week];

                $next = strtotime("next {$day_name} {$hour}:{$minute}", $now);
                break;

            case 'monthly':
                $day_of_month = (int) ($this->settings['day_of_month'] ?? 1);
                $next = strtotime(date('Y-m-', $now) . sprintf('%02d', $day_of_month) . " {$hour}:{$minute}");

                if ($next <= $now) {
                    $next = strtotime(date('Y-m-', strtotime('+1 month', $now)) . sprintf('%02d', $day_of_month) . " {$hour}:{$minute}");
                }
                break;

            default:
                return 0;
        }

        return $next;
    }

    /**
     * Send scheduled report
     */
    public function send_scheduled_report(): void {
        if (!$this->settings['enabled']) {
            return;
        }

        $report_data = $this->generate_report_data();
        $html_content = $this->render_email_html($report_data);

        $recipients = $this->settings['recipients'] ?? [];
        if (empty($recipients)) {
            $recipients = [get_option('admin_email')];
        }

        $subject = $this->get_email_subject($report_data);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        $attachments = [];

        // Generate PDF if enabled
        if ($this->settings['attach_pdf']) {
            $pdf_path = $this->generate_pdf($report_data);
            if ($pdf_path) {
                $attachments[] = $pdf_path;
            }
        }

        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $html_content, $headers, $attachments);
        }

        // Clean up PDF
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                @unlink($attachment);
            }
        }

        // Log report sent
        $this->log_report_sent($report_data);

        // Schedule next report
        $this->schedule_reports();
    }

    /**
     * Generate report data
     */
    public function generate_report_data(): array {
        global $wpdb;

        $frequency = $this->settings['frequency'];
        $period = $this->get_period_dates($frequency);
        $previous_period = $this->get_previous_period_dates($frequency);

        $data = [
            'generated_at' => current_time('mysql'),
            'period' => $period,
            'frequency' => $frequency,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ];

        // Overview stats
        if ($this->settings['include_sections']['overview'] ?? true) {
            $data['overview'] = $this->get_overview_stats($period, $previous_period);
        }

        // UTM Campaigns
        if ($this->settings['include_sections']['utm_campaigns'] ?? true) {
            $data['utm_campaigns'] = $this->get_utm_stats($period);
        }

        // Links
        if ($this->settings['include_sections']['links'] ?? true) {
            $data['links'] = $this->get_links_stats($period);
        }

        // Contacts
        if ($this->settings['include_sections']['contacts'] ?? true) {
            $data['contacts'] = $this->get_contacts_stats($period);
        }

        // Visitors
        if ($this->settings['include_sections']['visitors'] ?? true) {
            $data['visitors'] = $this->get_visitors_stats($period);
        }

        // Top performers
        if ($this->settings['include_sections']['top_performers'] ?? true) {
            $data['top_performers'] = $this->get_top_performers($period);
        }

        return $data;
    }

    /**
     * Get period dates
     */
    private function get_period_dates(string $frequency): array {
        $now = current_time('timestamp');

        switch ($frequency) {
            case 'daily':
                return [
                    'start' => date('Y-m-d 00:00:00', strtotime('-1 day', $now)),
                    'end' => date('Y-m-d 23:59:59', strtotime('-1 day', $now)),
                    'label' => date('F j, Y', strtotime('-1 day', $now)),
                ];

            case 'weekly':
                return [
                    'start' => date('Y-m-d 00:00:00', strtotime('-7 days', $now)),
                    'end' => date('Y-m-d 23:59:59', strtotime('-1 day', $now)),
                    'label' => date('M j', strtotime('-7 days', $now)) . ' - ' . date('M j, Y', strtotime('-1 day', $now)),
                ];

            case 'monthly':
                $first_day = date('Y-m-01 00:00:00', strtotime('-1 month', $now));
                $last_day = date('Y-m-t 23:59:59', strtotime('-1 month', $now));
                return [
                    'start' => $first_day,
                    'end' => $last_day,
                    'label' => date('F Y', strtotime('-1 month', $now)),
                ];

            default:
                return [
                    'start' => date('Y-m-d 00:00:00', strtotime('-7 days', $now)),
                    'end' => date('Y-m-d 23:59:59', strtotime('-1 day', $now)),
                    'label' => 'Last 7 days',
                ];
        }
    }

    /**
     * Get previous period dates for comparison
     */
    private function get_previous_period_dates(string $frequency): array {
        $now = current_time('timestamp');

        switch ($frequency) {
            case 'daily':
                return [
                    'start' => date('Y-m-d 00:00:00', strtotime('-2 days', $now)),
                    'end' => date('Y-m-d 23:59:59', strtotime('-2 days', $now)),
                ];

            case 'weekly':
                return [
                    'start' => date('Y-m-d 00:00:00', strtotime('-14 days', $now)),
                    'end' => date('Y-m-d 23:59:59', strtotime('-8 days', $now)),
                ];

            case 'monthly':
                return [
                    'start' => date('Y-m-01 00:00:00', strtotime('-2 months', $now)),
                    'end' => date('Y-m-t 23:59:59', strtotime('-2 months', $now)),
                ];

            default:
                return [
                    'start' => date('Y-m-d 00:00:00', strtotime('-14 days', $now)),
                    'end' => date('Y-m-d 23:59:59', strtotime('-8 days', $now)),
                ];
        }
    }

    /**
     * Get overview stats
     */
    private function get_overview_stats(array $period, array $previous): array {
        global $wpdb;

        // Current period stats
        $utm_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}peanut_utms
            WHERE created_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        $link_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(clicks) FROM {$wpdb->prefix}peanut_links
            WHERE updated_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        $new_contacts = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}peanut_contacts
            WHERE created_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        // Previous period for comparison
        $prev_utm_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}peanut_utms
            WHERE created_at BETWEEN %s AND %s",
            $previous['start'], $previous['end']
        ));

        $prev_link_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(clicks) FROM {$wpdb->prefix}peanut_links
            WHERE updated_at BETWEEN %s AND %s",
            $previous['start'], $previous['end']
        ));

        $prev_contacts = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}peanut_contacts
            WHERE created_at BETWEEN %s AND %s",
            $previous['start'], $previous['end']
        ));

        return [
            'utm_clicks' => [
                'value' => $utm_clicks,
                'change' => $this->calculate_change($utm_clicks, $prev_utm_clicks),
            ],
            'link_clicks' => [
                'value' => $link_clicks,
                'change' => $this->calculate_change($link_clicks, $prev_link_clicks),
            ],
            'new_contacts' => [
                'value' => $new_contacts,
                'change' => $this->calculate_change($new_contacts, $prev_contacts),
            ],
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculate_change(int $current, int $previous): array {
        if ($previous === 0) {
            return [
                'percent' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'percent' => abs(round($change, 1)),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Get UTM campaign stats
     */
    private function get_utm_stats(array $period): array {
        global $wpdb;

        $campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT
                utm_campaign,
                utm_source,
                utm_medium,
                COUNT(*) as clicks
            FROM {$wpdb->prefix}peanut_utms
            WHERE created_at BETWEEN %s AND %s
            GROUP BY utm_campaign, utm_source, utm_medium
            ORDER BY clicks DESC
            LIMIT 10",
            $period['start'], $period['end']
        ), ARRAY_A);

        return [
            'total_clicks' => array_sum(array_column($campaigns, 'clicks')),
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Get links stats
     */
    private function get_links_stats(array $period): array {
        global $wpdb;

        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT
                title,
                short_url,
                destination_url,
                clicks
            FROM {$wpdb->prefix}peanut_links
            WHERE updated_at BETWEEN %s AND %s
            ORDER BY clicks DESC
            LIMIT 10",
            $period['start'], $period['end']
        ), ARRAY_A);

        $total_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(clicks) FROM {$wpdb->prefix}peanut_links
            WHERE updated_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        return [
            'total_clicks' => $total_clicks,
            'links' => $links,
        ];
    }

    /**
     * Get contacts stats
     */
    private function get_contacts_stats(array $period): array {
        global $wpdb;

        $new_contacts = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}peanut_contacts
            WHERE created_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        $by_source = $wpdb->get_results($wpdb->prepare(
            "SELECT
                COALESCE(source, 'Unknown') as source,
                COUNT(*) as count
            FROM {$wpdb->prefix}peanut_contacts
            WHERE created_at BETWEEN %s AND %s
            GROUP BY source
            ORDER BY count DESC
            LIMIT 5",
            $period['start'], $period['end']
        ), ARRAY_A);

        $by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT
                status,
                COUNT(*) as count
            FROM {$wpdb->prefix}peanut_contacts
            WHERE created_at BETWEEN %s AND %s
            GROUP BY status",
            $period['start'], $period['end']
        ), ARRAY_A);

        return [
            'new_contacts' => $new_contacts,
            'by_source' => $by_source,
            'by_status' => $by_status,
        ];
    }

    /**
     * Get visitors stats
     */
    private function get_visitors_stats(array $period): array {
        global $wpdb;

        // Check if visitors table exists
        $table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$wpdb->prefix}peanut_visitors'"
        );

        if (!$table_exists) {
            return [
                'total_visitors' => 0,
                'new_visitors' => 0,
                'returning_visitors' => 0,
                'top_pages' => [],
            ];
        }

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM {$wpdb->prefix}peanut_pageviews
            WHERE created_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        $new = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}peanut_visitors
            WHERE created_at BETWEEN %s AND %s",
            $period['start'], $period['end']
        ));

        return [
            'total_visitors' => $total,
            'new_visitors' => $new,
            'returning_visitors' => max(0, $total - $new),
        ];
    }

    /**
     * Get top performers
     */
    private function get_top_performers(array $period): array {
        global $wpdb;

        // Top UTM source
        $top_source = $wpdb->get_row($wpdb->prepare(
            "SELECT utm_source, COUNT(*) as clicks
            FROM {$wpdb->prefix}peanut_utms
            WHERE created_at BETWEEN %s AND %s AND utm_source IS NOT NULL
            GROUP BY utm_source
            ORDER BY clicks DESC
            LIMIT 1",
            $period['start'], $period['end']
        ), ARRAY_A);

        // Top campaign
        $top_campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT utm_campaign, COUNT(*) as clicks
            FROM {$wpdb->prefix}peanut_utms
            WHERE created_at BETWEEN %s AND %s AND utm_campaign IS NOT NULL
            GROUP BY utm_campaign
            ORDER BY clicks DESC
            LIMIT 1",
            $period['start'], $period['end']
        ), ARRAY_A);

        // Top link
        $top_link = $wpdb->get_row($wpdb->prepare(
            "SELECT title, short_url, clicks
            FROM {$wpdb->prefix}peanut_links
            WHERE updated_at BETWEEN %s AND %s
            ORDER BY clicks DESC
            LIMIT 1",
            $period['start'], $period['end']
        ), ARRAY_A);

        return [
            'top_source' => $top_source,
            'top_campaign' => $top_campaign,
            'top_link' => $top_link,
        ];
    }

    /**
     * Get email subject
     */
    private function get_email_subject(array $data): string {
        $frequency_labels = [
            'daily' => __('Daily', 'peanut-suite'),
            'weekly' => __('Weekly', 'peanut-suite'),
            'monthly' => __('Monthly', 'peanut-suite'),
        ];

        $frequency = $frequency_labels[$data['frequency']] ?? $frequency_labels['weekly'];

        return sprintf(
            __('[%s] %s Marketing Report - %s', 'peanut-suite'),
            $data['site_name'],
            $frequency,
            $data['period']['label']
        );
    }

    /**
     * Render email HTML
     */
    private function render_email_html(array $data): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 30px 40px; text-align: center;">
                                    <?php if (!empty($this->settings['custom_logo'])): ?>
                                        <img src="<?php echo esc_url($this->settings['custom_logo']); ?>" alt="<?php echo esc_attr($data['site_name']); ?>" style="max-height: 50px; margin-bottom: 16px;">
                                    <?php endif; ?>
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                        <?php echo esc_html(ucfirst($data['frequency'])); ?> Marketing Report
                                    </h1>
                                    <p style="margin: 8px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                        <?php echo esc_html($data['period']['label']); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Overview Stats -->
                            <?php if (!empty($data['overview'])): ?>
                            <tr>
                                <td style="padding: 30px 40px;">
                                    <h2 style="margin: 0 0 20px; font-size: 18px; color: #1f2937;">Overview</h2>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <?php foreach ($data['overview'] as $key => $stat): ?>
                                            <td style="text-align: center; padding: 20px; background-color: #f9fafb; border-radius: 8px; <?php echo $key !== 'new_contacts' ? 'margin-right: 16px;' : ''; ?>">
                                                <div style="font-size: 28px; font-weight: 700; color: #1f2937;">
                                                    <?php echo number_format($stat['value']); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; margin-top: 4px;">
                                                    <?php
                                                    $labels = [
                                                        'utm_clicks' => __('UTM Clicks', 'peanut-suite'),
                                                        'link_clicks' => __('Link Clicks', 'peanut-suite'),
                                                        'new_contacts' => __('New Contacts', 'peanut-suite'),
                                                    ];
                                                    echo esc_html($labels[$key] ?? $key);
                                                    ?>
                                                </div>
                                                <?php if ($stat['change']['percent'] > 0): ?>
                                                <div style="font-size: 12px; margin-top: 8px; color: <?php echo $stat['change']['direction'] === 'up' ? '#10b981' : '#ef4444'; ?>;">
                                                    <?php echo $stat['change']['direction'] === 'up' ? '↑' : '↓'; ?>
                                                    <?php echo $stat['change']['percent']; ?>%
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($key !== 'new_contacts'): ?>
                                            <td style="width: 16px;"></td>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <!-- Top Performers -->
                            <?php if (!empty($data['top_performers'])): ?>
                            <tr>
                                <td style="padding: 0 40px 30px;">
                                    <h2 style="margin: 0 0 16px; font-size: 18px; color: #1f2937;">Top Performers</h2>
                                    <table width="100%" cellpadding="12" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px;">
                                        <?php if (!empty($data['top_performers']['top_source'])): ?>
                                        <tr>
                                            <td style="color: #6b7280; font-size: 14px;">Top Source</td>
                                            <td style="text-align: right; font-weight: 600; color: #1f2937;">
                                                <?php echo esc_html($data['top_performers']['top_source']['utm_source']); ?>
                                                <span style="color: #f97316;">(<?php echo number_format($data['top_performers']['top_source']['clicks']); ?> clicks)</span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($data['top_performers']['top_campaign'])): ?>
                                        <tr>
                                            <td style="color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb;">Top Campaign</td>
                                            <td style="text-align: right; font-weight: 600; color: #1f2937; border-top: 1px solid #e5e7eb;">
                                                <?php echo esc_html($data['top_performers']['top_campaign']['utm_campaign']); ?>
                                                <span style="color: #f97316;">(<?php echo number_format($data['top_performers']['top_campaign']['clicks']); ?> clicks)</span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($data['top_performers']['top_link'])): ?>
                                        <tr>
                                            <td style="color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb;">Top Link</td>
                                            <td style="text-align: right; font-weight: 600; color: #1f2937; border-top: 1px solid #e5e7eb;">
                                                <?php echo esc_html($data['top_performers']['top_link']['title'] ?: $data['top_performers']['top_link']['short_url']); ?>
                                                <span style="color: #f97316;">(<?php echo number_format($data['top_performers']['top_link']['clicks']); ?> clicks)</span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <!-- UTM Campaigns -->
                            <?php if (!empty($data['utm_campaigns']['campaigns'])): ?>
                            <tr>
                                <td style="padding: 0 40px 30px;">
                                    <h2 style="margin: 0 0 16px; font-size: 18px; color: #1f2937;">UTM Campaigns</h2>
                                    <table width="100%" cellpadding="10" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; border-collapse: separate;">
                                        <tr style="background-color: #f9fafb;">
                                            <th style="text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase;">Campaign</th>
                                            <th style="text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase;">Source</th>
                                            <th style="text-align: right; font-size: 12px; color: #6b7280; text-transform: uppercase;">Clicks</th>
                                        </tr>
                                        <?php foreach (array_slice($data['utm_campaigns']['campaigns'], 0, 5) as $campaign): ?>
                                        <tr>
                                            <td style="font-size: 14px; color: #1f2937; border-top: 1px solid #e5e7eb;">
                                                <?php echo esc_html($campaign['utm_campaign'] ?: '(not set)'); ?>
                                            </td>
                                            <td style="font-size: 14px; color: #6b7280; border-top: 1px solid #e5e7eb;">
                                                <?php echo esc_html($campaign['utm_source'] ?: '-'); ?>
                                            </td>
                                            <td style="font-size: 14px; color: #1f2937; font-weight: 600; text-align: right; border-top: 1px solid #e5e7eb;">
                                                <?php echo number_format($campaign['clicks']); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <!-- Contacts -->
                            <?php if (!empty($data['contacts'])): ?>
                            <tr>
                                <td style="padding: 0 40px 30px;">
                                    <h2 style="margin: 0 0 16px; font-size: 18px; color: #1f2937;">
                                        Contacts
                                        <span style="font-weight: normal; color: #6b7280;">
                                            (<?php echo number_format($data['contacts']['new_contacts']); ?> new)
                                        </span>
                                    </h2>
                                    <?php if (!empty($data['contacts']['by_source'])): ?>
                                    <p style="margin: 0 0 8px; font-size: 14px; color: #6b7280;">By Source:</p>
                                    <?php foreach ($data['contacts']['by_source'] as $source): ?>
                                    <div style="display: inline-block; padding: 4px 12px; margin: 4px 4px 4px 0; background-color: #f3f4f6; border-radius: 16px; font-size: 13px; color: #374151;">
                                        <?php echo esc_html($source['source']); ?>: <?php echo number_format($source['count']); ?>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <!-- CTA -->
                            <tr>
                                <td style="padding: 0 40px 30px; text-align: center;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-suite')); ?>" style="display: inline-block; padding: 12px 24px; background-color: #f97316; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                                        View Full Dashboard
                                    </a>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="padding: 20px 40px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                                    <p style="margin: 0; font-size: 13px; color: #6b7280;">
                                        <?php echo esc_html($this->settings['custom_footer'] ?: sprintf(__('Sent from %s via Peanut Suite', 'peanut-suite'), $data['site_name'])); ?>
                                    </p>
                                    <p style="margin: 8px 0 0; font-size: 12px; color: #9ca3af;">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-reports')); ?>" style="color: #6b7280;">Manage report settings</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate PDF report
     */
    private function generate_pdf(array $data): ?string {
        // Simple HTML-to-PDF conversion using DomPDF if available
        // For basic implementation, we'll create an HTML file instead

        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/peanut-reports/';

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $filename = 'report-' . date('Y-m-d-His') . '.html';
        $filepath = $pdf_dir . $filename;

        $html = $this->render_pdf_html($data);
        file_put_contents($filepath, $html);

        return $filepath;
    }

    /**
     * Render PDF HTML (simplified for attachment)
     */
    private function render_pdf_html(array $data): string {
        return $this->render_email_html($data);
    }

    /**
     * Log report sent
     */
    private function log_report_sent(array $data): void {
        $log = get_option('peanut_reports_log', []);

        // Keep only last 50 entries
        if (count($log) >= 50) {
            $log = array_slice($log, -49);
        }

        $log[] = [
            'sent_at' => current_time('mysql'),
            'frequency' => $data['frequency'],
            'period' => $data['period']['label'],
            'recipients' => count($this->settings['recipients'] ?? []),
        ];

        update_option('peanut_reports_log', $log);
    }

    /**
     * Handle settings update
     */
    public function on_settings_update($old_value, $new_value): void {
        $this->settings = $new_value;

        if ($new_value['enabled']) {
            $this->schedule_reports();
        } else {
            $timestamp = wp_next_scheduled('peanut_send_digest_report');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'peanut_send_digest_report');
            }
        }
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        register_rest_route(PEANUT_API_NAMESPACE, '/reports/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/reports/preview', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'preview_report'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/reports/send-now', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'send_now'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Get settings via API
     */
    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        $next_scheduled = wp_next_scheduled('peanut_send_digest_report');

        return new WP_REST_Response([
            'settings' => $this->settings,
            'next_scheduled' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : null,
            'log' => get_option('peanut_reports_log', []),
        ], 200);
    }

    /**
     * Update settings via API
     */
    public function update_settings(WP_REST_Request $request): WP_REST_Response {
        $data = $request->get_json_params();

        $settings = wp_parse_args($data, $this->get_defaults());

        // Sanitize
        $settings['enabled'] = (bool) ($settings['enabled'] ?? false);
        $settings['frequency'] = in_array($settings['frequency'], ['daily', 'weekly', 'monthly'])
            ? $settings['frequency']
            : 'weekly';
        $settings['day_of_week'] = absint($settings['day_of_week']) % 7;
        $settings['day_of_month'] = min(28, max(1, absint($settings['day_of_month'])));
        $settings['attach_pdf'] = (bool) ($settings['attach_pdf'] ?? true);

        // Sanitize recipients
        if (isset($settings['recipients']) && is_array($settings['recipients'])) {
            $settings['recipients'] = array_filter(array_map('sanitize_email', $settings['recipients']));
        }

        update_option('peanut_reports_settings', $settings);

        return new WP_REST_Response(['success' => true, 'settings' => $settings], 200);
    }

    /**
     * Preview report
     */
    public function preview_report(WP_REST_Request $request): WP_REST_Response {
        $data = $this->generate_report_data();
        $html = $this->render_email_html($data);

        return new WP_REST_Response([
            'data' => $data,
            'html' => $html,
        ], 200);
    }

    /**
     * Send report now
     */
    public function send_now(WP_REST_Request $request): WP_REST_Response {
        $this->send_scheduled_report();

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Report sent successfully', 'peanut-suite'),
        ], 200);
    }
}

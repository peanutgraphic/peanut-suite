<?php
/**
 * Content Calendar View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$events_table = $wpdb->prefix . 'peanut_calendar_events';
$ideas_table = $wpdb->prefix . 'peanut_content_ideas';

// Get current month
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$day_of_week = date('N', $first_day);

// Get events for the month
$events = [];
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $events_table)) === $events_table) {
    $month_start = date('Y-m-01', $first_day);
    $month_end = date('Y-m-t', $first_day);

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $events_table WHERE scheduled_date BETWEEN %s AND %s ORDER BY scheduled_date ASC",
        $month_start, $month_end
    ), ARRAY_A) ?: [];

    foreach ($results as $event) {
        $day = date('j', strtotime($event['scheduled_date']));
        if (!isset($events[$day])) $events[$day] = [];
        $events[$day][] = $event;
    }
}

// Get scheduled posts
$scheduled_posts = get_posts([
    'post_status' => 'future',
    'posts_per_page' => -1,
    'date_query' => [
        [
            'year' => $year,
            'month' => $month,
        ],
    ],
]);

foreach ($scheduled_posts as $post) {
    $day = date('j', strtotime($post->post_date));
    if (!isset($events[$day])) $events[$day] = [];
    $events[$day][] = [
        'id' => 'post_' . $post->ID,
        'title' => $post->post_title,
        'type' => 'scheduled_post',
        'post_id' => $post->ID,
        'scheduled_date' => $post->post_date,
    ];
}

// Get content ideas
$ideas = [];
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $ideas_table)) === $ideas_table) {
    $ideas = $wpdb->get_results(
        "SELECT * FROM $ideas_table WHERE status = 'pending' ORDER BY priority DESC, created_at DESC LIMIT 10",
        ARRAY_A
    ) ?: [];
}

// Navigation
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>

<div class="peanut-content">
    <div class="peanut-calendar-wrapper">
        <!-- Calendar Header -->
        <div class="peanut-calendar-header">
            <a href="?page=peanut-calendar&year=<?php echo $prev_year; ?>&month=<?php echo $prev_month; ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>
            <h2><?php echo date('F Y', $first_day); ?></h2>
            <a href="?page=peanut-calendar&year=<?php echo $next_year; ?>&month=<?php echo $next_month; ?>" class="button">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
            <button type="button" class="button button-primary" id="add-event" style="margin-left: auto;">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Add Event', 'peanut-suite'); ?>
            </button>
        </div>

        <div class="peanut-calendar-layout">
            <!-- Calendar Grid -->
            <div class="peanut-calendar">
                <div class="peanut-calendar-weekdays">
                    <div><?php esc_html_e('Mon', 'peanut-suite'); ?></div>
                    <div><?php esc_html_e('Tue', 'peanut-suite'); ?></div>
                    <div><?php esc_html_e('Wed', 'peanut-suite'); ?></div>
                    <div><?php esc_html_e('Thu', 'peanut-suite'); ?></div>
                    <div><?php esc_html_e('Fri', 'peanut-suite'); ?></div>
                    <div><?php esc_html_e('Sat', 'peanut-suite'); ?></div>
                    <div><?php esc_html_e('Sun', 'peanut-suite'); ?></div>
                </div>
                <div class="peanut-calendar-days">
                    <?php
                    // Empty cells before first day
                    for ($i = 1; $i < $day_of_week; $i++) {
                        echo '<div class="peanut-calendar-day empty"></div>';
                    }

                    // Days of month
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                        $day_events = $events[$day] ?? [];
                        ?>
                        <div class="peanut-calendar-day <?php echo $is_today ? 'today' : ''; ?>" data-date="<?php echo sprintf('%04d-%02d-%02d', $year, $month, $day); ?>">
                            <span class="day-number"><?php echo $day; ?></span>
                            <?php if (!empty($day_events)): ?>
                                <div class="day-events">
                                    <?php foreach ($day_events as $event):
                                        $type_class = $event['type'] ?? 'default';
                                    ?>
                                        <div class="calendar-event event-<?php echo esc_attr($type_class); ?>"
                                             title="<?php echo esc_attr($event['title']); ?>"
                                             data-event='<?php echo esc_attr(json_encode($event)); ?>'>
                                            <?php echo esc_html(wp_trim_words($event['title'], 3)); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }

                    // Empty cells after last day
                    $remaining = 7 - (($day_of_week + $days_in_month - 1) % 7);
                    if ($remaining < 7) {
                        for ($i = 0; $i < $remaining; $i++) {
                            echo '<div class="peanut-calendar-day empty"></div>';
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="peanut-calendar-sidebar">
                <!-- Content Ideas -->
                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3><?php esc_html_e('Content Ideas', 'peanut-suite'); ?></h3>
                        <button type="button" class="button button-small" id="add-idea">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </button>
                    </div>
                    <div class="peanut-card-body">
                        <?php if (!empty($ideas)): ?>
                            <ul class="peanut-ideas-list">
                                <?php foreach ($ideas as $idea): ?>
                                    <li class="idea-item" draggable="true" data-id="<?php echo esc_attr($idea['id']); ?>">
                                        <span class="idea-priority priority-<?php echo esc_attr($idea['priority']); ?>"></span>
                                        <span class="idea-title"><?php echo esc_html($idea['title']); ?></span>
                                        <span class="idea-type"><?php echo esc_html($idea['content_type']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted"><?php esc_html_e('No content ideas yet.', 'peanut-suite'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Legend -->
                <div class="peanut-card">
                    <div class="peanut-card-header">
                        <h3><?php esc_html_e('Legend', 'peanut-suite'); ?></h3>
                    </div>
                    <div class="peanut-card-body">
                        <ul class="peanut-legend">
                            <li><span class="legend-color event-scheduled_post"></span> <?php esc_html_e('Scheduled Post', 'peanut-suite'); ?></li>
                            <li><span class="legend-color event-blog"></span> <?php esc_html_e('Blog Post', 'peanut-suite'); ?></li>
                            <li><span class="legend-color event-social"></span> <?php esc_html_e('Social Media', 'peanut-suite'); ?></li>
                            <li><span class="legend-color event-email"></span> <?php esc_html_e('Email Campaign', 'peanut-suite'); ?></li>
                            <li><span class="legend-color event-video"></span> <?php esc_html_e('Video', 'peanut-suite'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div id="event-modal" class="peanut-modal" style="display:none;">
    <div class="peanut-modal-content">
        <div class="peanut-modal-header">
            <h2 id="event-modal-title"><?php esc_html_e('Add Event', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form id="event-form">
                <input type="hidden" name="id" id="event-id">
                <div class="peanut-form-row">
                    <label for="event-title"><?php esc_html_e('Title', 'peanut-suite'); ?></label>
                    <input type="text" id="event-title" name="title" required>
                </div>
                <div class="peanut-form-row">
                    <label for="event-type"><?php esc_html_e('Type', 'peanut-suite'); ?></label>
                    <select id="event-type" name="type">
                        <option value="blog"><?php esc_html_e('Blog Post', 'peanut-suite'); ?></option>
                        <option value="social"><?php esc_html_e('Social Media', 'peanut-suite'); ?></option>
                        <option value="email"><?php esc_html_e('Email Campaign', 'peanut-suite'); ?></option>
                        <option value="video"><?php esc_html_e('Video', 'peanut-suite'); ?></option>
                        <option value="other"><?php esc_html_e('Other', 'peanut-suite'); ?></option>
                    </select>
                </div>
                <div class="peanut-form-row">
                    <label for="event-date"><?php esc_html_e('Date', 'peanut-suite'); ?></label>
                    <input type="date" id="event-date" name="scheduled_date" required>
                </div>
                <div class="peanut-form-row">
                    <label for="event-description"><?php esc_html_e('Description', 'peanut-suite'); ?></label>
                    <textarea id="event-description" name="description" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="button" class="button button-primary" id="save-event"><?php esc_html_e('Save Event', 'peanut-suite'); ?></button>
        </div>
    </div>
</div>

<style>
.peanut-calendar-wrapper {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.peanut-calendar-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.peanut-calendar-header h2 {
    margin: 0;
    min-width: 200px;
    text-align: center;
}
.peanut-calendar-layout {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 24px;
}
.peanut-calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e0e0e0;
    border: 1px solid #e0e0e0;
    border-bottom: none;
}
.peanut-calendar-weekdays > div {
    background: #f5f5f5;
    padding: 10px;
    text-align: center;
    font-weight: 600;
}
.peanut-calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e0e0e0;
    border: 1px solid #e0e0e0;
}
.peanut-calendar-day {
    background: #fff;
    min-height: 100px;
    padding: 8px;
    cursor: pointer;
}
.peanut-calendar-day:hover {
    background: #f9f9f9;
}
.peanut-calendar-day.empty {
    background: #fafafa;
}
.peanut-calendar-day.today {
    background: #fff8e5;
}
.peanut-calendar-day.today .day-number {
    background: #2271b1;
    color: #fff;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.day-number {
    font-weight: 600;
    color: #333;
}
.day-events {
    margin-top: 4px;
}
.calendar-event {
    font-size: 11px;
    padding: 2px 6px;
    margin-bottom: 2px;
    border-radius: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}
.event-scheduled_post { background: #d4edda; color: #155724; }
.event-blog { background: #cce5ff; color: #004085; }
.event-social { background: #fff3cd; color: #856404; }
.event-email { background: #f8d7da; color: #721c24; }
.event-video { background: #e2d5f1; color: #563d7c; }
.event-default, .event-other { background: #e9ecef; color: #495057; }

.peanut-ideas-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.idea-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 8px;
    background: #fff;
    cursor: grab;
}
.idea-priority {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.priority-high { background: #dc3545; }
.priority-medium { background: #ffc107; }
.priority-low { background: #28a745; }
.idea-type {
    margin-left: auto;
    font-size: 11px;
    color: #666;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}

.peanut-legend {
    list-style: none;
    margin: 0;
    padding: 0;
}
.peanut-legend li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}
.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add event
    $('#add-event').on('click', function() {
        $('#event-modal-title').text('<?php esc_html_e('Add Event', 'peanut-suite'); ?>');
        $('#event-form')[0].reset();
        $('#event-id').val('');
        $('#event-date').val(new Date().toISOString().split('T')[0]);
        $('#event-modal').show();
    });

    // Click on day
    $('.peanut-calendar-day:not(.empty)').on('click', function(e) {
        if ($(e.target).hasClass('calendar-event')) return;
        const date = $(this).data('date');
        $('#event-form')[0].reset();
        $('#event-id').val('');
        $('#event-date').val(date);
        $('#event-modal').show();
    });

    // Close modal
    $('.peanut-modal-close, [data-dismiss="modal"]').on('click', function() {
        $(this).closest('.peanut-modal').hide();
    });

    // Save event
    $('#save-event').on('click', function() {
        const data = {
            action: 'peanut_save_calendar_event',
            nonce: '<?php echo wp_create_nonce('peanut_calendar'); ?>',
            id: $('#event-id').val(),
            title: $('#event-title').val(),
            type: $('#event-type').val(),
            scheduled_date: $('#event-date').val(),
            description: $('#event-description').val()
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Error saving event');
            }
        });
    });

    // Click on event
    $('.calendar-event').on('click', function(e) {
        e.stopPropagation();
        const event = $(this).data('event');
        if (event.post_id) {
            window.open('<?php echo admin_url('post.php?action=edit&post='); ?>' + event.post_id, '_blank');
        } else {
            $('#event-modal-title').text('<?php esc_html_e('Edit Event', 'peanut-suite'); ?>');
            $('#event-id').val(event.id);
            $('#event-title').val(event.title);
            $('#event-type').val(event.type);
            $('#event-date').val(event.scheduled_date.split(' ')[0]);
            $('#event-description').val(event.description || '');
            $('#event-modal').show();
        }
    });
});
</script>

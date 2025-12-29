<?php
/**
 * Popups Renderer
 *
 * Generates HTML markup for different popup types.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Popups_Renderer {

    /**
     * Render a popup
     */
    public function render(object $popup): string {
        $type = $popup->type;

        return match ($type) {
            'modal' => $this->render_modal($popup),
            'slide-in' => $this->render_slide_in($popup),
            'bar' => $this->render_bar($popup),
            'fullscreen' => $this->render_fullscreen($popup),
            default => $this->render_modal($popup),
        };
    }

    /**
     * Render modal popup
     */
    private function render_modal(object $popup): string {
        $styles = json_decode($popup->styles, true) ?? [];
        $settings = json_decode($popup->settings, true) ?? [];

        $css_vars = $this->get_css_variables($styles);
        $position_class = 'peanut-popup-position-' . ($popup->position ?: 'center');

        ob_start();
        ?>
        <div id="peanut-popup-<?php echo esc_attr($popup->id); ?>"
             class="peanut-popup peanut-popup-modal <?php echo esc_attr($position_class); ?>"
             data-popup-id="<?php echo esc_attr($popup->id); ?>"
             data-animation="<?php echo esc_attr($settings['animation'] ?? 'fade'); ?>"
             style="<?php echo esc_attr($css_vars); ?> display: none;">

            <?php if ($settings['overlay'] ?? true): ?>
            <div class="peanut-popup-overlay"
                 style="background: <?php echo esc_attr($settings['overlay_color'] ?? 'rgba(0,0,0,0.5)'); ?>">
            </div>
            <?php endif; ?>

            <div class="peanut-popup-container">
                <div class="peanut-popup-content">
                    <?php if ($settings['close_button'] ?? true): ?>
                    <button type="button" class="peanut-popup-close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <?php endif; ?>

                    <?php echo $this->render_popup_body($popup); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render slide-in popup
     */
    private function render_slide_in(object $popup): string {
        $styles = json_decode($popup->styles, true) ?? [];
        $settings = json_decode($popup->settings, true) ?? [];

        $css_vars = $this->get_css_variables($styles);
        $position_class = 'peanut-popup-position-' . ($popup->position ?: 'bottom-right');

        ob_start();
        ?>
        <div id="peanut-popup-<?php echo esc_attr($popup->id); ?>"
             class="peanut-popup peanut-popup-slide-in <?php echo esc_attr($position_class); ?>"
             data-popup-id="<?php echo esc_attr($popup->id); ?>"
             data-animation="slide"
             style="<?php echo esc_attr($css_vars); ?> display: none;">

            <div class="peanut-popup-container">
                <div class="peanut-popup-content">
                    <?php if ($settings['close_button'] ?? true): ?>
                    <button type="button" class="peanut-popup-close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <?php endif; ?>

                    <?php echo $this->render_popup_body($popup); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render bar popup (top or bottom)
     */
    private function render_bar(object $popup): string {
        $styles = json_decode($popup->styles, true) ?? [];
        $settings = json_decode($popup->settings, true) ?? [];

        $css_vars = $this->get_css_variables($styles);
        $position_class = 'peanut-popup-position-' . ($popup->position ?: 'top');

        ob_start();
        ?>
        <div id="peanut-popup-<?php echo esc_attr($popup->id); ?>"
             class="peanut-popup peanut-popup-bar <?php echo esc_attr($position_class); ?>"
             data-popup-id="<?php echo esc_attr($popup->id); ?>"
             data-animation="slide"
             style="<?php echo esc_attr($css_vars); ?> display: none;">

            <div class="peanut-popup-container">
                <div class="peanut-popup-content peanut-popup-bar-content">
                    <?php echo $this->render_bar_body($popup); ?>

                    <?php if ($settings['close_button'] ?? true): ?>
                    <button type="button" class="peanut-popup-close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render fullscreen popup
     */
    private function render_fullscreen(object $popup): string {
        $styles = json_decode($popup->styles, true) ?? [];
        $settings = json_decode($popup->settings, true) ?? [];

        $css_vars = $this->get_css_variables($styles);

        ob_start();
        ?>
        <div id="peanut-popup-<?php echo esc_attr($popup->id); ?>"
             class="peanut-popup peanut-popup-fullscreen"
             data-popup-id="<?php echo esc_attr($popup->id); ?>"
             data-animation="<?php echo esc_attr($settings['animation'] ?? 'fade'); ?>"
             style="<?php echo esc_attr($css_vars); ?> display: none;">

            <div class="peanut-popup-container">
                <div class="peanut-popup-content">
                    <?php if ($settings['close_button'] ?? true): ?>
                    <button type="button" class="peanut-popup-close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <?php endif; ?>

                    <?php echo $this->render_popup_body($popup); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render popup body content
     */
    private function render_popup_body(object $popup): string {
        $form_fields = json_decode($popup->form_fields, true) ?? [];

        ob_start();
        ?>
        <?php if ($popup->image_url): ?>
        <div class="peanut-popup-image">
            <img src="<?php echo esc_url($popup->image_url); ?>" alt="">
        </div>
        <?php endif; ?>

        <div class="peanut-popup-text">
            <?php if ($popup->title): ?>
            <h3 class="peanut-popup-title"><?php echo esc_html($popup->title); ?></h3>
            <?php endif; ?>

            <?php if ($popup->content): ?>
            <div class="peanut-popup-description">
                <?php echo wp_kses_post($popup->content); ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($form_fields)): ?>
        <form class="peanut-popup-form" data-popup-id="<?php echo esc_attr($popup->id); ?>">
            <?php foreach ($form_fields as $field): ?>
            <div class="peanut-popup-field">
                <?php if (($field['show_label'] ?? false) && !empty($field['label'])): ?>
                <label for="peanut-field-<?php echo esc_attr($popup->id); ?>-<?php echo esc_attr($field['name']); ?>">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($field['required'] ?? false): ?><span class="required">*</span><?php endif; ?>
                </label>
                <?php endif; ?>

                <?php echo $this->render_form_field($popup->id, $field); ?>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="peanut-popup-button">
                <?php echo esc_html($popup->button_text ?: 'Subscribe'); ?>
            </button>
        </form>

        <div class="peanut-popup-success" style="display: none;">
            <?php echo esc_html($popup->success_message ?: 'Thank you!'); ?>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Render bar body (simplified horizontal layout)
     */
    private function render_bar_body(object $popup): string {
        $form_fields = json_decode($popup->form_fields, true) ?? [];

        ob_start();
        ?>
        <div class="peanut-popup-bar-text">
            <?php if ($popup->title): ?>
            <span class="peanut-popup-title"><?php echo esc_html($popup->title); ?></span>
            <?php endif; ?>

            <?php if ($popup->content): ?>
            <span class="peanut-popup-description"><?php echo wp_strip_all_tags($popup->content); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($form_fields)): ?>
        <form class="peanut-popup-form peanut-popup-bar-form" data-popup-id="<?php echo esc_attr($popup->id); ?>">
            <?php foreach ($form_fields as $field): ?>
            <?php echo $this->render_form_field($popup->id, $field); ?>
            <?php endforeach; ?>

            <button type="submit" class="peanut-popup-button">
                <?php echo esc_html($popup->button_text ?: 'Subscribe'); ?>
            </button>
        </form>

        <div class="peanut-popup-success" style="display: none;">
            <?php echo esc_html($popup->success_message ?: 'Thank you!'); ?>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Render individual form field
     */
    private function render_form_field(int $popup_id, array $field): string {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $placeholder = $field['placeholder'] ?? $label;
        $required = $field['required'] ?? false;
        $id = "peanut-field-{$popup_id}-{$name}";

        $attrs = [
            'id' => $id,
            'name' => $name,
            'placeholder' => $placeholder,
        ];

        if ($required) {
            $attrs['required'] = 'required';
        }

        $attrs_str = '';
        foreach ($attrs as $key => $value) {
            $attrs_str .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }

        return match ($type) {
            'email' => sprintf('<input type="email" class="peanut-popup-input"%s>', $attrs_str),
            'text' => sprintf('<input type="text" class="peanut-popup-input"%s>', $attrs_str),
            'tel' => sprintf('<input type="tel" class="peanut-popup-input"%s>', $attrs_str),
            'textarea' => sprintf('<textarea class="peanut-popup-input"%s></textarea>', $attrs_str),
            'select' => $this->render_select_field($attrs, $field['options'] ?? []),
            'checkbox' => sprintf(
                '<label class="peanut-popup-checkbox"><input type="checkbox"%s> %s</label>',
                $attrs_str,
                esc_html($label)
            ),
            default => sprintf('<input type="text" class="peanut-popup-input"%s>', $attrs_str),
        };
    }

    /**
     * Render select field
     */
    private function render_select_field(array $attrs, array $options): string {
        $attrs_str = '';
        foreach ($attrs as $key => $value) {
            $attrs_str .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }

        $html = sprintf('<select class="peanut-popup-input"%s>', $attrs_str);
        $html .= '<option value="">Select...</option>';

        foreach ($options as $option) {
            $html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($option['value'] ?? $option),
                esc_html($option['label'] ?? $option)
            );
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Get CSS variables from styles
     */
    private function get_css_variables(array $styles): string {
        $vars = [];

        if (isset($styles['background_color'])) {
            $vars[] = '--peanut-popup-bg: ' . $styles['background_color'];
        }
        if (isset($styles['text_color'])) {
            $vars[] = '--peanut-popup-text: ' . $styles['text_color'];
        }
        if (isset($styles['button_color'])) {
            $vars[] = '--peanut-popup-btn-bg: ' . $styles['button_color'];
        }
        if (isset($styles['button_text_color'])) {
            $vars[] = '--peanut-popup-btn-text: ' . $styles['button_text_color'];
        }
        if (isset($styles['border_radius'])) {
            $vars[] = '--peanut-popup-radius: ' . $styles['border_radius'] . 'px';
        }
        if (isset($styles['max_width'])) {
            $vars[] = '--peanut-popup-max-width: ' . $styles['max_width'] . 'px';
        }

        return implode('; ', $vars);
    }
}

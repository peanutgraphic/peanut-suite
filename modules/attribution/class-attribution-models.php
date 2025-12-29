<?php
/**
 * Attribution Models
 *
 * Implements 5 attribution models for marketing analytics.
 *
 * @package PeanutSuite\Attribution
 */

namespace PeanutSuite\Attribution;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attribution model calculations.
 */
class Attribution_Models {

    /**
     * Available models.
     */
    const MODELS = [
        'first_touch' => 'First Touch',
        'last_touch' => 'Last Touch',
        'linear' => 'Linear',
        'time_decay' => 'Time Decay',
        'position_based' => 'Position Based',
    ];

    /**
     * Time decay half-life in days.
     */
    const TIME_DECAY_HALF_LIFE = 7;

    /**
     * Position-based weights.
     */
    const POSITION_FIRST_WEIGHT = 0.40;
    const POSITION_LAST_WEIGHT = 0.40;
    const POSITION_MIDDLE_WEIGHT = 0.20;

    /**
     * Get available models.
     *
     * @return array
     */
    public static function get_models(): array {
        return self::MODELS;
    }

    /**
     * Calculate attribution for a set of touches.
     *
     * @param string $model      Model name.
     * @param array  $touches    Array of touch points.
     * @param string $conversion_time Conversion timestamp.
     * @return array Array of touch_id => credit mappings.
     */
    public static function calculate(string $model, array $touches, string $conversion_time): array {
        if (empty($touches)) {
            return [];
        }

        switch ($model) {
            case 'first_touch':
                return self::first_touch($touches);

            case 'last_touch':
                return self::last_touch($touches);

            case 'linear':
                return self::linear($touches);

            case 'time_decay':
                return self::time_decay($touches, $conversion_time);

            case 'position_based':
                return self::position_based($touches);

            default:
                return self::last_touch($touches);
        }
    }

    /**
     * Calculate all models for a set of touches.
     *
     * @param array  $touches         Array of touch points.
     * @param string $conversion_time Conversion timestamp.
     * @return array Model => [touch_id => credit] mappings.
     */
    public static function calculate_all(array $touches, string $conversion_time): array {
        $results = [];

        foreach (array_keys(self::MODELS) as $model) {
            $results[$model] = self::calculate($model, $touches, $conversion_time);
        }

        return $results;
    }

    /**
     * First Touch Attribution.
     *
     * 100% credit to the first touchpoint.
     *
     * @param array $touches Touch points.
     * @return array
     */
    public static function first_touch(array $touches): array {
        if (empty($touches)) {
            return [];
        }

        $results = [];

        // Sort by touch_time to ensure correct order
        usort($touches, function ($a, $b) {
            return strtotime($a['touch_time']) - strtotime($b['touch_time']);
        });

        $first = reset($touches);

        foreach ($touches as $touch) {
            $results[$touch['id']] = ($touch['id'] === $first['id']) ? 1.0 : 0.0;
        }

        return $results;
    }

    /**
     * Last Touch Attribution.
     *
     * 100% credit to the last touchpoint.
     *
     * @param array $touches Touch points.
     * @return array
     */
    public static function last_touch(array $touches): array {
        if (empty($touches)) {
            return [];
        }

        $results = [];

        // Sort by touch_time to ensure correct order
        usort($touches, function ($a, $b) {
            return strtotime($a['touch_time']) - strtotime($b['touch_time']);
        });

        $last = end($touches);

        foreach ($touches as $touch) {
            $results[$touch['id']] = ($touch['id'] === $last['id']) ? 1.0 : 0.0;
        }

        return $results;
    }

    /**
     * Linear Attribution.
     *
     * Equal credit to all touchpoints.
     *
     * @param array $touches Touch points.
     * @return array
     */
    public static function linear(array $touches): array {
        if (empty($touches)) {
            return [];
        }

        $credit = 1.0 / count($touches);
        $results = [];

        foreach ($touches as $touch) {
            $results[$touch['id']] = $credit;
        }

        return $results;
    }

    /**
     * Time Decay Attribution.
     *
     * More credit to touches closer to conversion.
     * Uses exponential decay with 7-day half-life.
     *
     * @param array  $touches         Touch points.
     * @param string $conversion_time Conversion timestamp.
     * @return array
     */
    public static function time_decay(array $touches, string $conversion_time): array {
        if (empty($touches)) {
            return [];
        }

        $conversion_timestamp = strtotime($conversion_time);
        $half_life_seconds = self::TIME_DECAY_HALF_LIFE * 24 * 60 * 60;

        $weights = [];
        $total_weight = 0;

        foreach ($touches as $touch) {
            $touch_timestamp = strtotime($touch['touch_time']);
            $days_before = ($conversion_timestamp - $touch_timestamp) / (24 * 60 * 60);

            // Exponential decay: weight = 2^(-days/half_life)
            $weight = pow(2, -$days_before / self::TIME_DECAY_HALF_LIFE);

            $weights[$touch['id']] = $weight;
            $total_weight += $weight;
        }

        // Normalize to sum to 1
        $results = [];
        foreach ($weights as $id => $weight) {
            $results[$id] = $total_weight > 0 ? $weight / $total_weight : 0;
        }

        return $results;
    }

    /**
     * Position-Based Attribution (U-shaped).
     *
     * 40% to first touch, 40% to last touch, 20% distributed among middle.
     *
     * @param array $touches Touch points.
     * @return array
     */
    public static function position_based(array $touches): array {
        if (empty($touches)) {
            return [];
        }

        $count = count($touches);

        // Sort by touch_time
        usort($touches, function ($a, $b) {
            return strtotime($a['touch_time']) - strtotime($b['touch_time']);
        });

        $results = [];

        if ($count === 1) {
            // Single touch gets 100%
            $results[$touches[0]['id']] = 1.0;
        } elseif ($count === 2) {
            // Two touches: 50% each
            $results[$touches[0]['id']] = 0.5;
            $results[$touches[1]['id']] = 0.5;
        } else {
            // First and last get 40% each
            $results[$touches[0]['id']] = self::POSITION_FIRST_WEIGHT;
            $results[$touches[$count - 1]['id']] = self::POSITION_LAST_WEIGHT;

            // Middle touches share the remaining 20%
            $middle_count = $count - 2;
            $middle_credit = self::POSITION_MIDDLE_WEIGHT / $middle_count;

            for ($i = 1; $i < $count - 1; $i++) {
                $results[$touches[$i]['id']] = $middle_credit;
            }
        }

        return $results;
    }

    /**
     * Get model description.
     *
     * @param string $model Model name.
     * @return string
     */
    public static function get_description(string $model): string {
        $descriptions = [
            'first_touch' => 'Assigns 100% credit to the first touchpoint in the customer journey.',
            'last_touch' => 'Assigns 100% credit to the last touchpoint before conversion.',
            'linear' => 'Distributes credit equally among all touchpoints.',
            'time_decay' => 'Assigns more credit to touchpoints closer to conversion (7-day half-life).',
            'position_based' => 'Assigns 40% to first touch, 40% to last touch, and 20% distributed among middle touches.',
        ];

        return $descriptions[$model] ?? '';
    }

    /**
     * Compare models for a set of touches.
     *
     * @param array  $touches         Touch points.
     * @param string $conversion_time Conversion timestamp.
     * @return array
     */
    public static function compare_models(array $touches, string $conversion_time): array {
        $all_results = self::calculate_all($touches, $conversion_time);
        $comparison = [];

        // Group by channel for comparison
        $channels = [];
        foreach ($touches as $touch) {
            $channel = $touch['channel'] ?? 'Unknown';
            if (!isset($channels[$channel])) {
                $channels[$channel] = [];
            }
            $channels[$channel][] = $touch['id'];
        }

        foreach (array_keys(self::MODELS) as $model) {
            $comparison[$model] = [];

            foreach ($channels as $channel => $touch_ids) {
                $channel_credit = 0;
                foreach ($touch_ids as $id) {
                    $channel_credit += $all_results[$model][$id] ?? 0;
                }
                $comparison[$model][$channel] = round($channel_credit * 100, 1);
            }
        }

        return $comparison;
    }
}

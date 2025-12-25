<?php
/**
 * REST Response Trait
 *
 * Consistent REST API response formatting across Peanut controllers.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Peanut_REST_Response {

    /**
     * Return a success response
     */
    protected function success(array $data = [], int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Return a success response with message
     */
    protected function success_message(string $message, array $data = [], int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $message,
        string $code = 'error',
        int $status = 400,
        array $additional = []
    ): WP_Error {
        return new WP_Error($code, $message, array_merge(['status' => $status], $additional));
    }

    /**
     * Return a not found error
     */
    protected function not_found(string $message = 'Resource not found'): WP_Error {
        return $this->error($message, 'not_found', 404);
    }

    /**
     * Return an unauthorized error
     */
    protected function unauthorized(string $message = 'Unauthorized'): WP_Error {
        return $this->error($message, 'unauthorized', 401);
    }

    /**
     * Return a forbidden error
     */
    protected function forbidden(string $message = 'Forbidden'): WP_Error {
        return $this->error($message, 'forbidden', 403);
    }

    /**
     * Return a validation error
     */
    protected function validation_error(array $errors, string $message = 'Validation failed'): WP_Error {
        return $this->error($message, 'validation_error', 422, ['errors' => $errors]);
    }

    /**
     * Return a rate limit error
     */
    protected function rate_limited(int $retry_after = 60): WP_Error {
        return $this->error(
            'Too many requests. Please try again later.',
            'rate_limited',
            429,
            ['retry_after' => $retry_after]
        );
    }

    /**
     * Return a server error
     */
    protected function server_error(string $message = 'Internal server error'): WP_Error {
        return $this->error($message, 'server_error', 500);
    }

    /**
     * Return a paginated response
     */
    protected function paginated(array $items, int $total, int $page, int $per_page): WP_REST_Response {
        $total_pages = (int) ceil($total / $per_page);

        return new WP_REST_Response([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
                'has_more' => $page < $total_pages,
            ],
        ], 200);
    }

    /**
     * Return a created response (201)
     */
    protected function created(array $data, ?string $location = null): WP_REST_Response {
        $response = new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], 201);

        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Return a no content response (204)
     */
    protected function no_content(): WP_REST_Response {
        return new WP_REST_Response(null, 204);
    }

    /**
     * Add rate limit headers to response
     */
    protected function with_rate_limit_headers(
        WP_REST_Response $response,
        int $limit,
        int $remaining,
        int $reset
    ): WP_REST_Response {
        $response->header('X-RateLimit-Limit', (string) $limit);
        $response->header('X-RateLimit-Remaining', (string) $remaining);
        $response->header('X-RateLimit-Reset', (string) $reset);

        return $response;
    }

    /**
     * Standard permission callback for admin users
     */
    protected function admin_permission_callback(WP_REST_Request $request): bool|WP_Error {
        if (!current_user_can('manage_options')) {
            return $this->forbidden('Administrator access required.');
        }
        return true;
    }

    /**
     * Standard permission callback for logged-in users
     */
    protected function logged_in_permission_callback(WP_REST_Request $request): bool|WP_Error {
        if (!is_user_logged_in()) {
            return $this->unauthorized('You must be logged in to access this resource.');
        }
        return true;
    }

    /**
     * Verify nonce from request header or parameter
     */
    protected function verify_request_nonce(WP_REST_Request $request, string $action): bool {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce) {
            $nonce = $request->get_param('_wpnonce');
        }
        if (!$nonce) {
            return false;
        }
        return wp_verify_nonce($nonce, $action) !== false;
    }
}

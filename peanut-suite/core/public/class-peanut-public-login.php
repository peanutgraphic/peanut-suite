<?php
/**
 * Peanut Public Login
 *
 * Handles the white-label team login page shortcode and authentication.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Public_Login {

    /**
     * Instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register shortcode
        add_shortcode('peanut_team_login', [$this, 'render_login_form']);

        // AJAX handlers
        add_action('wp_ajax_nopriv_peanut_team_login', [$this, 'handle_ajax_login']);
        add_action('wp_ajax_peanut_team_login', [$this, 'handle_ajax_login']);

        // Enqueue login assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_login_assets']);

        // Redirect team members after WP login
        add_filter('login_redirect', [$this, 'redirect_team_members'], 10, 3);

        // Redirect logged-in users away from login page
        add_action('template_redirect', [$this, 'redirect_logged_in_users']);
    }

    /**
     * Render the login form shortcode
     */
    public function render_login_form(array $atts = []): string {
        $atts = shortcode_atts([
            'redirect' => admin_url('admin.php?page=peanut-app'),
            'logo' => '',
            'title' => __('Team Login', 'peanut-suite'),
        ], $atts, 'peanut_team_login');

        // If user is already logged in, show redirect message
        if (is_user_logged_in()) {
            $redirect_url = esc_url($atts['redirect']);
            return sprintf(
                '<div class="peanut-login-container">
                    <div class="peanut-login-box">
                        <p>%s</p>
                        <a href="%s" class="peanut-login-btn">%s</a>
                    </div>
                </div>',
                esc_html__('You are already logged in.', 'peanut-suite'),
                $redirect_url,
                esc_html__('Go to Dashboard', 'peanut-suite')
            );
        }

        // Generate nonce for security
        $nonce = wp_create_nonce('peanut_team_login_nonce');

        ob_start();
        ?>
        <div class="peanut-login-container">
            <div class="peanut-login-box">
                <?php if ($atts['logo']): ?>
                    <img src="<?php echo esc_url($atts['logo']); ?>" alt="Logo" class="peanut-login-logo">
                <?php else: ?>
                    <div class="peanut-login-brand">
                        <svg class="peanut-login-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="currentColor"/>
                        </svg>
                        <span>Peanut Suite</span>
                    </div>
                <?php endif; ?>

                <h2 class="peanut-login-title"><?php echo esc_html($atts['title']); ?></h2>

                <form id="peanut-login-form" class="peanut-login-form">
                    <input type="hidden" name="action" value="peanut_team_login">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($atts['redirect']); ?>">

                    <div class="peanut-login-field">
                        <label for="peanut-username"><?php esc_html_e('Email or Username', 'peanut-suite'); ?></label>
                        <input type="text" id="peanut-username" name="username" required autocomplete="username">
                    </div>

                    <div class="peanut-login-field">
                        <label for="peanut-password"><?php esc_html_e('Password', 'peanut-suite'); ?></label>
                        <div class="peanut-password-wrapper">
                            <input type="password" id="peanut-password" name="password" required autocomplete="current-password">
                            <button type="button" class="peanut-password-toggle" aria-label="<?php esc_attr_e('Toggle password visibility', 'peanut-suite'); ?>">
                                <svg class="peanut-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="peanut-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="peanut-login-options">
                        <label class="peanut-remember">
                            <input type="checkbox" name="remember" value="1">
                            <span><?php esc_html_e('Remember me', 'peanut-suite'); ?></span>
                        </label>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="peanut-forgot-link">
                            <?php esc_html_e('Forgot password?', 'peanut-suite'); ?>
                        </a>
                    </div>

                    <div id="peanut-login-error" class="peanut-login-error" style="display: none;"></div>

                    <button type="submit" class="peanut-login-btn">
                        <span class="peanut-btn-text"><?php esc_html_e('Sign In', 'peanut-suite'); ?></span>
                        <span class="peanut-btn-loading" style="display: none;">
                            <svg class="peanut-spinner" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="31.4 31.4" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX login request
     */
    public function handle_ajax_login(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'peanut_team_login_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh and try again.', 'peanut-suite'),
            ]);
        }

        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : admin_url('admin.php?page=peanut-app');

        if (empty($username) || empty($password)) {
            wp_send_json_error([
                'message' => __('Please enter your username and password.', 'peanut-suite'),
            ]);
        }

        // Try to find user by email or username
        $user = null;
        if (is_email($username)) {
            $user = get_user_by('email', $username);
        }
        if (!$user) {
            $user = get_user_by('login', $username);
        }

        if (!$user) {
            wp_send_json_error([
                'message' => __('Invalid username or password.', 'peanut-suite'),
            ]);
        }

        // Attempt sign in
        $creds = [
            'user_login' => $user->user_login,
            'user_password' => $password,
            'remember' => $remember,
        ];

        $signed_in = wp_signon($creds, is_ssl());

        if (is_wp_error($signed_in)) {
            wp_send_json_error([
                'message' => __('Invalid username or password.', 'peanut-suite'),
            ]);
        }

        // Set the auth cookie
        wp_set_current_user($signed_in->ID);

        // Log the login if account service is available
        if (class_exists('Peanut_Account_Service') && class_exists('Peanut_Audit_Log_Service')) {
            $account = Peanut_Account_Service::get_user_account($signed_in->ID);
            if ($account) {
                Peanut_Audit_Log_Service::log(
                    $account['id'],
                    'user_login',
                    'auth',
                    null,
                    ['ip' => $this->get_client_ip(), 'method' => 'team_login'],
                    $signed_in->ID
                );
            }
        }

        wp_send_json_success([
            'message' => __('Login successful. Redirecting...', 'peanut-suite'),
            'redirect_url' => $redirect_to,
        ]);
    }

    /**
     * Enqueue login page assets
     */
    public function enqueue_login_assets(): void {
        global $post;

        // Only enqueue on pages with our shortcode
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'peanut_team_login')) {
            return;
        }

        // Inline CSS for login form
        $css = $this->get_login_styles();
        wp_register_style('peanut-login', false);
        wp_enqueue_style('peanut-login');
        wp_add_inline_style('peanut-login', $css);

        // Inline JS for form handling
        $js = $this->get_login_scripts();
        wp_register_script('peanut-login', false, [], '', true);
        wp_enqueue_script('peanut-login');
        wp_add_inline_script('peanut-login', $js);

        // Pass AJAX URL
        wp_localize_script('peanut-login', 'peanutLogin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * Get login form CSS
     */
    private function get_login_styles(): string {
        return '
        .peanut-login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .peanut-login-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .peanut-login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
            color: #0ea5e9;
            font-size: 24px;
            font-weight: 700;
        }
        .peanut-login-icon {
            width: 40px;
            height: 40px;
        }
        .peanut-login-logo {
            display: block;
            max-width: 200px;
            margin: 0 auto 24px;
        }
        .peanut-login-title {
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 24px;
        }
        .peanut-login-field {
            margin-bottom: 16px;
        }
        .peanut-login-field label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }
        .peanut-login-field input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .peanut-login-field input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
        }
        .peanut-password-wrapper {
            position: relative;
        }
        .peanut-password-wrapper input {
            padding-right: 48px;
        }
        .peanut-password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .peanut-password-toggle:hover {
            color: #64748b;
        }
        .peanut-password-toggle svg {
            width: 20px;
            height: 20px;
        }
        .peanut-login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .peanut-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            cursor: pointer;
        }
        .peanut-remember input {
            width: 16px;
            height: 16px;
            accent-color: #0ea5e9;
        }
        .peanut-forgot-link {
            color: #0ea5e9;
            text-decoration: none;
        }
        .peanut-forgot-link:hover {
            text-decoration: underline;
        }
        .peanut-login-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .peanut-login-btn {
            width: 100%;
            padding: 14px 24px;
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            box-sizing: border-box;
        }
        .peanut-login-btn:hover {
            background: #0284c7;
        }
        .peanut-login-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        .peanut-spinner {
            width: 20px;
            height: 20px;
            animation: peanut-spin 1s linear infinite;
        }
        @keyframes peanut-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        ';
    }

    /**
     * Get login form JavaScript
     */
    private function get_login_scripts(): string {
        return '
        (function() {
            var form = document.getElementById("peanut-login-form");
            if (!form) return;

            var errorDiv = document.getElementById("peanut-login-error");
            var submitBtn = form.querySelector(".peanut-login-btn");
            var btnText = submitBtn.querySelector(".peanut-btn-text");
            var btnLoading = submitBtn.querySelector(".peanut-btn-loading");

            // Password visibility toggle
            var passwordToggle = form.querySelector(".peanut-password-toggle");
            var passwordInput = document.getElementById("peanut-password");
            var eyeOpen = passwordToggle.querySelector(".peanut-eye-open");
            var eyeClosed = passwordToggle.querySelector(".peanut-eye-closed");

            passwordToggle.addEventListener("click", function() {
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    eyeOpen.style.display = "none";
                    eyeClosed.style.display = "block";
                } else {
                    passwordInput.type = "password";
                    eyeOpen.style.display = "block";
                    eyeClosed.style.display = "none";
                }
            });

            form.addEventListener("submit", function(e) {
                e.preventDefault();

                // Reset error
                errorDiv.style.display = "none";
                errorDiv.textContent = "";

                // Show loading
                btnText.style.display = "none";
                btnLoading.style.display = "inline-flex";
                submitBtn.disabled = true;

                // Get form data
                var formData = new FormData(form);

                // Send AJAX request
                fetch(peanutLogin.ajaxUrl, {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        // Redirect on success
                        window.location.href = data.data.redirect_url;
                    } else {
                        // Show error
                        errorDiv.textContent = data.data.message;
                        errorDiv.style.display = "block";
                        btnText.style.display = "inline";
                        btnLoading.style.display = "none";
                        submitBtn.disabled = false;
                    }
                })
                .catch(function(error) {
                    errorDiv.textContent = "An error occurred. Please try again.";
                    errorDiv.style.display = "block";
                    btnText.style.display = "inline";
                    btnLoading.style.display = "none";
                    submitBtn.disabled = false;
                });
            });
        })();
        ';
    }

    /**
     * Redirect team members after WP login
     */
    public function redirect_team_members(string $redirect_to, string $requested_redirect_to, $user): string {
        if (is_wp_error($user) || !$user) {
            return $redirect_to;
        }

        // Check if user has a Peanut account
        if (class_exists('Peanut_Account_Service')) {
            $account = Peanut_Account_Service::get_user_account($user->ID);
            if ($account) {
                return admin_url('admin.php?page=peanut-app');
            }
        }

        return $redirect_to;
    }

    /**
     * Redirect logged-in users away from login page
     */
    public function redirect_logged_in_users(): void {
        if (!is_user_logged_in()) {
            return;
        }

        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'peanut_team_login')) {
            return;
        }

        // User is logged in and viewing the login page, redirect to dashboard
        wp_safe_redirect(admin_url('admin.php?page=peanut-app'));
        exit;
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        return $ip;
    }
}

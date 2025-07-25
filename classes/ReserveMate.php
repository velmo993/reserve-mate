<?php
namespace ReserveMate;
defined('ABSPATH') or die('No direct access!');
/**
 * Main plugin class
 */
use Exception;
use ReserveMate\Admin\Controllers\PageController;
use ReserveMate\Admin\Controllers\BookingController;
use ReserveMate\Admin\Controllers\SettingController;
use ReserveMate\Admin\Controllers\DashboardController;
use ReserveMate\Admin\Helpers\Tables;
use ReserveMate\Frontend\Controllers\BookingController as FrontendBookingController;
use ReserveMate\Frontend\Controllers\PaymentController;
use ReserveMate\Frontend\Handlers\AjaxHandler;

if (!class_exists('ReserveMate')) :
    
    class ReserveMate {
        
        private static $instance = null;
        
        /**
         * Get singleton instance
         */
        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * Constructor
         */
        private function __construct() {
            $this->define_hooks();
            $this->include_files();
            $this->init_components();
        }
        
        /**
         * Define WordPress hooks
         */
        private function define_hooks() {
            add_action('init', [$this, 'init']);
            add_action('admin_init', [$this, 'admin_init']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
            add_action('plugins_loaded', [$this, 'load_textdomain']);
            
            // Register page builder supports
            add_action('init', [$this, 'register_gutenberg_block']);
            add_action('init', [$this, 'register_beaver_builder_module']);
            add_action('elementor/widgets/widgets_registered', [$this, 'register_elementor_widget']);
            add_action('et_builder_ready', [$this, 'register_divi_module']);
            add_action('vc_before_init', [$this, 'register_wpbakery_element']);
            
            // Add activation redirect hook
            add_action('admin_init', [$this, 'handle_activation_redirect']);
            
            // Add AJAX handler for welcome notice dismissal
            add_action('wp_ajax_rm_dismiss_welcome', [$this, 'dismiss_welcome_notice']);
        }
        
        /**
         * Include required files
         */
        private function include_files() {
            // Include required files for mail
            if (!function_exists('wp_mail')) {
                require_once ABSPATH . WPINC . '/pluggable.php';
            }
            
            // Include files
            $include_files = [
                'includes/helpers.php'
            ];

            // Load all files
            foreach ($include_files as $file) {
                if (file_exists(RM_PLUGIN_PATH . $file)) {
                    require_once RM_PLUGIN_PATH . $file;
                }
            }
        }
        
        /**
         * Initialize plugin components
         */
        private function init_components() {
            AssetManager::get_instance();
            ScriptManager::get_instance();
            FrontendBookingController::init_shortcode();
            PaymentController::init();
            AjaxHandler::init();
            

            if (is_admin()) {
                // Initialize other controllers
                new \ReserveMate\Admin\Controllers\PageController();
                new \ReserveMate\Admin\Controllers\BookingController();
                \ReserveMate\Admin\Controllers\SettingController::init();
            }
        }
        
        /**
         * Plugin activation
         */
        public static function activate() {
            $tables = new Tables();
            $results = $tables->create_all_tables();
            
            foreach ($results as $table => $success) {
                if (!$success) {
                    error_log("Failed to create table: $table");
                }
            }
            
            self::setup_default_form_fields();
            
            // Set activation redirect flag
            add_option('rm_activation_redirect', true);
            
            flush_rewrite_rules();
        }
        
         /**
         * Plugin deactivation
         */
        public static function deactivate() {
            // Clean up redirect flag
            delete_option('rm_activation_redirect');
            
            // Flush rewrite rules
            flush_rewrite_rules();
        }
        
        /**
         * Handle activation redirect
         */
        public function handle_activation_redirect() {
            if (get_option('rm_activation_redirect', false)) {
                delete_option('rm_activation_redirect');
                
                // Only redirect if not doing bulk activation and user can manage options
                if (!isset($_GET['activate-multi']) && current_user_can('manage_options')) {
                    // Redirect to dashboard
                    wp_safe_redirect(admin_url('admin.php?page=reserve-mate&rm_welcome=1'));
                    exit;
                }
            }
        }
        
        /**
         * AJAX handler for dismissing welcome notice
         */
        public function dismiss_welcome_notice() {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'rm_dismiss_welcome')) {
                wp_die(__('Security check failed', 'reserve-mate'));
            }
            
            // Check user capability
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions', 'reserve-mate'));
            }
            
            // Update option
            update_option('rm_welcome_dismissed', true);
            
            wp_die(); // AJAX response
        }
        
        /**
         * Initialize plugin
         */
        public function init() {
            $this->create_booking_post_type();
        }
        
        /**
         * Admin initialization
         */
        public function admin_init() {
            // Admin specific initialization
        }
        
        /**
         * Register custom post types
         */
        public function create_booking_post_type() {
            register_post_type('booking', [
                'labels' => [
                    'name' => __('Bookings', 'reserve-mate'),
                    'singular_name' => __('Booking', 'reserve-mate')
                ],
                'public' => true,
                'has_archive' => true,
                'supports' => ['title', 'editor', 'custom-fields'],
                'show_in_rest' => true,
            ]);
        }
        
        /**
         * Enqueue frontend scripts
         */
        public function enqueue_frontend_scripts() {
            ScriptManager::get_instance()->enqueue_frontend_scripts();
        }
        
        /**
         * Enqueue admin scripts
         */
        public function enqueue_admin_scripts() {
            if (!current_user_can('manage_options')) {
                return;
            }
            ScriptManager::get_instance()->enqueue_admin_scripts();
            if (is_admin()) {
                wp_enqueue_script(
                    'reserve-mate-gutenberg-block',
                    RM_PLUGIN_URL . 'assets/js/gutenberg-block.js',
                    ['wp-blocks', 'wp-element', 'wp-editor'],
                    time()
                );
            }
        }
        
        /**
         * Load plugin text domain for internationalization
         */
        public function load_textdomain() {
            
            $mo_file = RM_PLUGIN_PATH . 'assets/languages/reserve-mate-' . get_locale() . '.mo';
            if (file_exists($mo_file)) {
                $loaded = load_textdomain('reserve-mate', $mo_file);
            }
            
            // Debug logging (remove in production)
            // error_log('Reserve Mate: Loading textdomain from: ' . $mo_file);
            // error_log('Reserve Mate: Textdomain loaded: ' . ($loaded ? 'Yes' : 'No'));
            // error_log('Reserve Mate: Current locale: ' . get_locale());
            
            
        }
        
        /**
         * Setup default form fields
         */
        private static function setup_default_form_fields() {
            $default_fields = [
                [
                    'id' => 'name',
                    'label' => 'Full Name',
                    'type' => 'text',
                    'placeholder' => 'Full name',
                    'required' => true,
                    'order' => 1
                ],
                [
                    'id' => 'email',
                    'label' => 'Email Address',
                    'type' => 'email',
                    'placeholder' => 'Email address',
                    'required' => true,
                    'order' => 2
                ],
                [
                    'id' => 'phone',
                    'label' => 'Phone Number',
                    'type' => 'tel',
                    'placeholder' => 'Phone number',
                    'required' => false,
                    'order' => 3
                ]
            ];
        
            $options = get_option('rm_form_options');
        
            if (!$options || !isset($options['form_fields']) || empty($options['form_fields'])) {
                $options = is_array($options) ? $options : [];
                $options['form_fields'] = $default_fields;
                update_option('rm_form_options', $options);
            }
        }
        
        /**
         * Register gutenberg block
         */
        public function register_gutenberg_block() {
            if (!function_exists('register_block_type')) {
                return;
            }
            
            register_block_type('reserve-mate/booking-form', [
                'render_callback' => ['ReserveMate\Frontend\Controllers\BookingController', 'display_booking_form'],
                'attributes' => []
            ]);
        }
        
        /**
         * Register elementor widget
         */
        public function register_elementor_widget() {
            if (!did_action('elementor/loaded')) {
                return;
            }
            
            require_once RM_PLUGIN_PATH . 'includes/elementor/booking-widget.php';
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \ReserveMate\Elementor\BookingWidget());
        }
        
        /**
         * Register divi module
         */
        public function register_divi_module() {
            if (class_exists('ET_Builder_Module')) {
                require_once RM_PLUGIN_PATH . 'includes/divi/booking-module.php';
            }
        }
        
        /**
         * Register beaver builder module
         */
        public function register_beaver_builder_module() {
            if (!class_exists('FLBuilder')) {
                return;
            }
    
            $module_file = RM_PLUGIN_PATH . 'includes/beaver-builder/booking-module.php';
            if (file_exists($module_file)) {
                require_once $module_file;
            }
        }
        
        /**
         * Register wpbakery element
         */
        public function register_wpbakery_element() {
            if (function_exists('vc_map')) {
                vc_map([
                    'name' => __('Reserve Mate Booking Form', 'reserve-mate'),
                    'base' => 'reserve_mate_booking_form',
                    'category' => __('Content', 'reserve-mate'),
                    'description' => __('Add booking form', 'reserve-mate'),
                    'icon' => 'icon-wpb-ui-accordion',
                    'params' => []
                ]);
            }
        }

    }
endif;
<?php
namespace ReserveMate\Admin\Views;

use ReserveMate\Admin\Controllers\DashboardController;
use DateTime;

defined('ABSPATH') or die('No direct access!');

class DashboardViews {
    
    public static function render_dashboard() {
        $stats = DashboardController::get_dashboard_stats();
        $data = DashboardController::get_recent_bookings_data();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e('ReserveMate Dashboard', 'reserve-mate'); ?>
            </h1>
            
            <?php self::render_welcome_notice(); ?>
            
            <!-- Statistics Cards -->
            <div class="rm-stats-grid">
                <?php self::render_stat_card(
                    __('Total Bookings', 'reserve-mate'),
                    $stats['total_bookings'],
                    'dashicons-calendar-alt',
                    '#0073aa'
                ); ?>
                
                <?php self::render_stat_card(
                    __('Pending Bookings', 'reserve-mate'),
                    $stats['pending_bookings'],
                    'dashicons-clock',
                    '#d63638'
                ); ?>
                
                <?php self::render_stat_card(
                    __('Today\'s Bookings', 'reserve-mate'),
                    $stats['todays_bookings'],
                    'dashicons-calendar',
                    '#00a32a'
                ); ?>
                
                <?php self::render_stat_card(
                    __('This Month', 'reserve-mate'),
                    $stats['month_bookings'],
                    'dashicons-chart-line',
                    '#8b2fb3'
                ); ?>
            </div>
            
            <!-- Navigation Cards -->
            <div class="rm-nav-section">
                <h2><?php _e('Quick Actions', 'reserve-mate'); ?></h2>
                <div class="rm-nav-grid">
                    <?php self::render_nav_card(
                        __('Manage Bookings', 'reserve-mate'),
                        __('View, edit, and manage all your bookings', 'reserve-mate'),
                        'dashicons-calendar-alt',
                        admin_url('admin.php?page=reserve-mate-bookings'),
                        '#8b2fb3'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Services', 'reserve-mate'),
                        __('Configure your services and pricing', 'reserve-mate'),
                        'dashicons-products',
                        admin_url('admin.php?page=reserve-mate-services'),
                        '#00a32a'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Settings', 'reserve-mate'),
                        __('Configure plugin settings and preferences', 'reserve-mate'),
                        'dashicons-admin-settings',
                        admin_url('admin.php?page=reserve-mate-settings'),
                        '#d63638'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Staff Members', 'reserve-mate'),
                        __('View and manage staff', 'reserve-mate'),
                        'dashicons-businessperson',
                        admin_url('admin.php?page=reserve-mate-staff'),
                        '#0073aa'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Payment Settings', 'reserve-mate'),
                        __('View and manage staff', 'reserve-mate'),
                        'dashicons-businessperson',
                        admin_url('admin.php?page=reserve-mate-payments'),
                        '#8b2fb3'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Taxes', 'reserve-mate'),
                        __('View and manage staff', 'reserve-mate'),
                        'dashicons-businessperson',
                        admin_url('admin.php?page=reserve-mate-tax'),
                        '#00a32a'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Notifications', 'reserve-mate'),
                        __('View and manage staff', 'reserve-mate'),
                        'dashicons-businessperson',
                        admin_url('admin.php?page=reserve-mate-notifications'),
                        '#d63638'
                    ); ?>
                    
                    <?php self::render_nav_card(
                        __('Documentation', 'reserve-mate'),
                        __('Learn how to use ReserveMate effectively', 'reserve-mate'),
                        'dashicons-book',
                        '#',
                        '#0073aa',
                        true
                    ); ?>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <?php if (!empty($data['recent_bookings'])): ?>
            <div class="rm-recent-section">
                <h2><?php _e('Recent Bookings', 'reserve-mate'); ?></h2>
                <div class="rm-recent-bookings">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Customer', 'reserve-mate'); ?></th>
                                <th><?php _e('Date & Time', 'reserve-mate'); ?></th>
                                <th><?php _e('Service', 'reserve-mate'); ?></th>
                                <?php if($data['approval_enabled']) : ?>
                                <th><?php _e('Status', 'reserve-mate'); ?></th>
                                <?php endif; ?>
                                <?php if(count($data['staff_members']) > 0) : ?>
                                <th><?php _e('Staff', 'reserve-mate'); ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recent_bookings'] as $booking): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($booking->name ?? __('N/A', 'reserve-mate')); ?></strong>
                                    <?php if (!empty($booking->email)): ?>
                                        <br><span><?php echo esc_html($booking->email); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $start = new DateTime($booking->start_datetime);
                                    $end = new DateTime($booking->end_datetime);
                                    if (!empty($booking->start_datetime) && !empty($booking->end_datetime)) {
                                        echo '<br><span>' . esc_html($start->format('Y-m-d H:i') . ' - ' . $end->format('H:i')) . '</span>';
                                    } else if(!empty($booking->start_datetime)) {
                                        echo '<br><span>' . esc_html($start->format('Y-m-d H:i')) . '</span>';
                                    } else {
                                        echo __('N/A', 'reserve-mate');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($booking->services)) : ?>
                                        <ul>
                                            <?php foreach ($booking->services as $service) : ?>
                                                <li>
                                                    <?php echo esc_html($service->service_name . ' x' . $service->quantity); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else : ?>
                                        <?php _e('N/A', 'reserve-mate'); ?>
                                    <?php endif; ?>
                                </td>
                                <?php if($data['approval_enabled']) : ?>
                                <td>
                                    <?php self::render_status_badge($booking->status ?? 'pending'); ?>
                                </td>
                                <?php endif; ?>
                                <?php if (!empty($booking->staff_name)) : ?>
                                <td>
                                    <?php echo esc_html($booking->staff_name) ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="rm-view-all">
                        <a href="<?php echo admin_url('admin.php?page=reserve-mate-bookings'); ?>" class="button button-primary">
                            <?php _e('View All Bookings', 'reserve-mate'); ?>
                        </a>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php self::render_dashboard_styles(); ?>
        <?php
    }
    
    private static function render_welcome_notice() {
        $dismissed = get_option('rm_welcome_dismissed', false);
        if ($dismissed) return;
        ?>
        <div class="notice notice-info is-dismissible rm-welcome-notice">
            <h3><?php _e('Welcome to ReserveMate!', 'reserve-mate'); ?></h3>
            <p><?php _e('Thank you for installing ReserveMate. Get started by configuring your settings and creating your first service.', 'reserve-mate'); ?></p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=reserve-mate-settings'); ?>" class="button button-primary">
                    <?php _e('Configure Settings', 'reserve-mate'); ?>
                </a>
                <a href="#" class="button rm-dismiss-welcome">
                    <?php _e('Dismiss', 'reserve-mate'); ?>
                </a>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.rm-dismiss-welcome').on('click', function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'rm_dismiss_welcome',
                    nonce: '<?php echo wp_create_nonce('rm_dismiss_welcome'); ?>'
                }, function() {
                    $('.rm-welcome-notice').fadeOut();
                });
            });
        });
        </script>
        <?php
    }
    
    private static function render_stat_card($title, $value, $icon, $color) {
        ?>
        <div class="rm-stat-card">
            <div class="rm-stat-icon" style="color: <?php echo esc_attr($color); ?>">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
            </div>
            <div class="rm-stat-content">
                <div class="rm-stat-number"><?php echo esc_html($value); ?></div>
                <div class="rm-stat-title"><?php echo esc_html($title); ?></div>
            </div>
        </div>
        <?php
    }
    
    private static function render_nav_card($title, $description, $icon, $link, $color, $external = false) {
        $target = $external ? ' target="_blank"' : '';
        ?>
        <div class="rm-nav-card">
            <a href="<?php echo esc_url($link); ?>"<?php echo $target; ?>>
                <div class="rm-nav-icon" style="color: <?php echo esc_attr($color); ?>">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                </div>
                <div class="rm-nav-content">
                    <h3><?php echo esc_html($title); ?></h3>
                    <p><?php echo esc_html($description); ?></p>
                </div>
                <div class="rm-nav-arrow">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>
            </a>
        </div>
        <?php
    }
    
    private static function render_status_badge($status) {
        $status_classes = [
            'pending' => 'rm-status-pending',
            'confirmed' => 'rm-status-confirmed',
            'cancelled' => 'rm-status-cancelled',
            'completed' => 'rm-status-completed'
        ];
        
        $status_labels = [
            'pending' => __('Pending', 'reserve-mate'),
            'confirmed' => __('Confirmed', 'reserve-mate'),
            'cancelled' => __('Cancelled', 'reserve-mate'),
            'completed' => __('Completed', 'reserve-mate')
        ];
        
        $class = $status_classes[$status] ?? 'rm-status-pending';
        $label = $status_labels[$status] ?? ucfirst($status);
        
        echo '<span class="rm-status-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    }
    
    private static function render_dashboard_styles() {
        ?>
        <style>
        .rm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .rm-stat-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        
        .rm-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .rm-stat-icon {
            font-size: 40px;
            margin-right: 15px;
        }
        
        .rm-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #23282d;
        }
        
        .rm-stat-title {
            color: #666;
            font-size: 14px;
        }
        
        .rm-nav-section {
            margin: 30px 0;
        }
        
        .rm-nav-section h2 {
            margin-bottom: 15px;
            color: #23282d;
        }
        
        .rm-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .rm-nav-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        
        .rm-nav-card:hover {
            border-color: #0073aa;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .rm-nav-card a {
            display: flex;
            align-items: center;
            padding: 20px;
            text-decoration: none;
            color: inherit;
        }
        
        .rm-nav-icon {
            font-size: 32px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .rm-nav-content {
            flex-grow: 1;
        }
        
        .rm-nav-content h3 {
            margin: 0 0 5px 0;
            color: #23282d;
            font-size: 16px;
        }
        
        .rm-nav-content p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .rm-nav-arrow {
            color: #999;
            margin-left: 10px;
        }
        
        .rm-nav-card:hover .rm-nav-arrow {
            color: #0073aa;
        }
        
        .rm-recent-section {
            margin: 30px 0;
        }
        
        .rm-recent-section h2 {
            margin-bottom: 15px;
            color: #23282d;
        }
        
        .rm-recent-bookings {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .rm-view-all {
            padding: 15px 20px;
            margin: 0;
            background: #f9f9f9;
            border-top: 1px solid #eee;
        }
        
        .rm-status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .rm-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .rm-status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .rm-status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .rm-status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .rm-welcome-notice {
            border-left-color: #0073aa;
        }
        
        .rm-welcome-notice h3 {
            margin-top: 0;
        }
        
        @media (max-width: 768px) {
            .rm-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .rm-nav-grid {
                grid-template-columns: 1fr;
            }
            
            .rm-nav-card a {
                padding: 15px;
            }
            
            .rm-nav-icon {
                font-size: 24px;
            }
        }
        </style>
        <?php
    }
}
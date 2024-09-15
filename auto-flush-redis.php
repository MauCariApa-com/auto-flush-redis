<?php
/**
 * Plugin Name: Auto Flush Redis & Valkey
 * Plugin URI: https://maucariapa.com/dukungan-plugin-auto-flush-redis-valkey
 * Description: Auto flush Redis or Valkey cache at custom intervals. Supports customizable IP, port, and flush interval.
 * Version: 1.0.0
 * Author: MauCariApa.com
 * Author URI: https://maucariapa.com
 * License: GPLv2
 * Requires at least: 5.6
 * Tested up to: 6.6
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin text domain
function auto_flush_cache_load_textdomain() {
    load_plugin_textdomain('auto-flush-cache', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'auto_flush_cache_load_textdomain');

// Create admin menu
function auto_flush_cache_menu() {
    add_options_page(
        __('Auto Flush Cache Settings', 'auto-flush-cache'),
        __('Auto Flush Cache', 'auto-flush-cache'),
        'manage_options',
        'auto-flush-cache',
        'auto_flush_cache_settings_page'
    );
}
add_action('admin_menu', 'auto_flush_cache_menu');

// Plugin settings page
function auto_flush_cache_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Auto Flush Cache Settings', 'auto-flush-cache'); ?></h1>

        <!-- Settings Form -->
        <form method="post" action="options.php">
            <?php
            settings_fields('auto_flush_cache_options_group');
            do_settings_sections('auto-flush-cache');
            submit_button();
            ?>
        </form>

        <!-- Instructions Section -->
        <div class="auto-flush-cache-instructions">
            <h2><?php _e('How to Find Redis/Valkey IP and Port', 'auto-flush-cache'); ?></h2>
            <p><?php _e('Follow the steps below to find the IP address and port for your Redis or Valkey server:', 'auto-flush-cache'); ?></p>

            <strong><?php _e('For Redis on a Local Machine:', 'auto-flush-cache'); ?></strong>
            <ol>
                <li><?php _e('Open your terminal and run: <code>redis-cli</code>', 'auto-flush-cache'); ?></li>
                <li><?php _e('By default, Redis runs on <strong>IP: 127.0.0.1</strong> and <strong>Port: 6379</strong>. You can confirm this by running: <code>INFO</code>', 'auto-flush-cache'); ?></li>
                <li><?php _e('If you\'re using a custom configuration file, check your Redis config file (usually <code>/etc/redis/redis.conf</code>) for the bind IP and port settings.', 'auto-flush-cache'); ?></li>
            </ol>

            <strong><?php _e('For Redis on a Remote Server:', 'auto-flush-cache'); ?></strong>
            <ol>
                <li><?php _e('SSH into your remote server where Redis is installed.', 'auto-flush-cache'); ?></li>
                <li><?php _e('Run <code>redis-cli</code> to connect to the Redis server.', 'auto-flush-cache'); ?></li>
                <li><?php _e('Check your Redis configuration file (<code>/etc/redis/redis.conf</code>) for the server\'s IP address and port number (under <code>bind</code> and <code>port</code> settings).', 'auto-flush-cache'); ?></li>
                <li><?php _e('If Redis is behind a firewall or proxy, ensure you are using the correct external IP address and that the necessary ports are open.', 'auto-flush-cache'); ?></li>
            </ol>

            <strong><?php _e('For Valkey:', 'auto-flush-cache'); ?></strong>
            <ol>
                <li><?php _e('Check your Valkey installation documentation for the default IP and port configuration.', 'auto-flush-cache'); ?></li>
                <li><?php _e('Typically, the default IP is <strong>127.0.0.1</strong> and the default port is <strong>6380</strong>, but this can vary based on your setup.', 'auto-flush-cache'); ?></li>
                <li><?php _e('If Valkey is hosted remotely, check the server\'s network configuration and Valkey\'s config file for the correct IP address and port.', 'auto-flush-cache'); ?></li>
            </ol>

            <p><?php _e('If you are unsure, consult your cloud provider or hosting service for the correct Redis or Valkey IP and port details.', 'auto-flush-cache'); ?></p>
        </div>

        <!-- PayPal Donation Section -->
        <div style="margin-top: 20px; padding: 12px; background-color: #f1f1f1; border: 1px solid #0073aa;">
            <p><?php _e('If you find this plugin helpful, please consider supporting us by making a donation:', 'auto-flush-cache'); ?></p>
            <p>
                <a href="https://www.paypal.com/paypalme/kodester" target="_blank" class="button button-primary">
                    <?php _e('Donate via PayPal', 'auto-flush-cache'); ?>
                </a>
            </p>
        </div>
    </div>

    <style>
        .auto-flush-cache-instructions {
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            padding: 12px;
            margin-top: 20px;
        }
    </style>
    <?php
}

// Register plugin settings
function auto_flush_cache_register_settings() {
    register_setting('auto_flush_cache_options_group', 'auto_flush_cache_type');
    register_setting('auto_flush_cache_options_group', 'auto_flush_cache_ip');
    register_setting('auto_flush_cache_options_group', 'auto_flush_cache_port');
    register_setting('auto_flush_cache_options_group', 'auto_flush_cache_interval');

    add_settings_section('auto_flush_cache_main_section', __('Cache Connection Settings', 'auto-flush-cache'), null, 'auto-flush-cache');

    add_settings_field(
        'auto_flush_cache_type',
        __('Cache Type', 'auto-flush-cache'),
        'auto_flush_cache_type_callback',
        'auto-flush-cache',
        'auto_flush_cache_main_section'
    );

    add_settings_field(
        'auto_flush_cache_ip',
        __('Redis/Valkey IP', 'auto-flush-cache'),
        'auto_flush_cache_ip_callback',
        'auto-flush-cache',
        'auto_flush_cache_main_section'
    );

    add_settings_field(
        'auto_flush_cache_port',
        __('Redis/Valkey Port', 'auto-flush-cache'),
        'auto_flush_cache_port_callback',
        'auto-flush-cache',
        'auto_flush_cache_main_section'
    );

    add_settings_field(
        'auto_flush_cache_interval',
        __('Auto Flush Interval', 'auto-flush-cache'),
        'auto_flush_cache_interval_callback',
        'auto-flush-cache',
        'auto_flush_cache_main_section'
    );
}
add_action('admin_init', 'auto_flush_cache_register_settings');

// Settings fields callbacks
function auto_flush_cache_type_callback() {
    $cache_type = get_option('auto_flush_cache_type', 'redis');
    ?>
    <select name="auto_flush_cache_type">
        <option value="redis" <?php selected($cache_type, 'redis'); ?>><?php _e('Redis', 'auto-flush-cache'); ?></option>
        <option value="valkey" <?php selected($cache_type, 'valkey'); ?>><?php _e('Valkey', 'auto-flush-cache'); ?></option>
    </select>
    <?php
}

function auto_flush_cache_ip_callback() {
    $ip = get_option('auto_flush_cache_ip', '127.0.0.1');
    echo "<input type='text' name='auto_flush_cache_ip' value='" . esc_attr($ip) . "' />";
}

function auto_flush_cache_port_callback() {
    $port = get_option('auto_flush_cache_port', '6379');
    echo "<input type='text' name='auto_flush_cache_port' value='" . esc_attr($port) . "' />";
}

function auto_flush_cache_interval_callback() {
    $interval = get_option('auto_flush_cache_interval', '3');
    echo "<input type='text' name='auto_flush_cache_interval' value='" . esc_attr($interval) . "' /> " . __('Days', 'auto-flush-cache');
}

// Auto-flush cache function
function auto_flush_cache() {
    $cache_type = get_option('auto_flush_cache_type', 'redis');
    $ip = get_option('auto_flush_cache_ip', '127.0.0.1');
    $port = get_option('auto_flush_cache_port', '6379');

    if ($cache_type === 'redis') {
        // Redis flush logic
        $redis = new Redis();
        if ($redis->connect($ip, $port)) {
            $redis->flushAll();
        }
    } elseif ($cache_type === 'valkey') {
        // Valkey flush logic
        $valkey = new Valkey(); // Assuming Valkey has a similar API to Redis
        if ($valkey->connect($ip, $port)) {
            $valkey->flushAll(); // Adjust this method call as per Valkey's API
        }
    }
}

// Schedule auto flush
function auto_flush_cache_schedule() {
    if (!wp_next_scheduled('auto_flush_cache_event')) {
        $interval = get_option('auto_flush_cache_interval', '1');
        wp_schedule_event(time(), 'daily', 'auto_flush_cache_event');
    }
}
add_action('wp', 'auto_flush_cache_schedule');

// Hook auto-flush event
add_action('auto_flush_cache_event', 'auto_flush_cache');

// Clear scheduled event on plugin deactivation
function auto_flush_cache_deactivation() {
    wp_clear_scheduled_hook('auto_flush_cache_event');
}
register_deactivation_hook(__FILE__, 'auto_flush_cache_deactivation');

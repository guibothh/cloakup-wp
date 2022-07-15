<?php

/**
 * Plugin Name: Cloakup
 * Plugin URI: https://cloakup.me
 * Description: Traffic filtering for your website.
 * Version: 1.0.0
 * Author: Cloakup
 * Author URI: https://github.com/upsurgedev
 * License: GPL2
 */

function setup_cloakup()
{
    // create db table if it doesn't exist
    global $wpdb;

    $table_name = $wpdb->prefix . 'cloakup';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        campaign_id varchar(255) NOT NULL,
        campaign_name varchar(255) NOT NULL,
        campaign_slug varchar(255) NOT NULL,
        api_key varchar(255) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

function find_cloakup_post_id($post_id)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'cloakup';

    $sql = "SELECT * FROM $table_name WHERE post_id = $post_id LIMIT 1";

    $results = $wpdb->get_results($sql);

    return $results ? $results[0] : false;
}

function cloakup_check_request($campaign)
{
    $data = array(
        'token' => $campaign->api_key,
        'slug' => $campaign->campaign_slug,
        'ip' => get_user_ip(),
        'domain' => @$_SERVER['HTTP_HOST'],
        'referer' => @$_SERVER['HTTP_REFERER'],
        'user_agent' => @$_SERVER['HTTP_USER_AGENT'],
        'query' => $_GET,
    );

    $url = 'https://na-beta.cloakup.me?' . http_build_query($data);

    return json_decode(@file_get_contents($url), true);
}

function get_user_ip()
{
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }
    return $ip;
}

function check_page()
{
    $post_id = get_the_ID();

    $campaign = find_cloakup_post_id($post_id);

    if ($campaign) {
        $result = cloakup_check_request($campaign);

        if ($result) {
            $cloak_method = $result['next']['type'];
            $next_page = $result['next']['content'];
            $page = find_post_by_path($next_page);

            if (filter_var($next_page, FILTER_VALIDATE_URL)) {
                $url = $next_page;
            } else {
                $url = get_permalink($page->ID);
            }

            if ($page) {
                // redirect to internal page
                if ($cloak_method == 'redirect') {
                    // redirect to page
                    redirect_to_page(prepare_url($url));
                }
                //show page content
                if ($cloak_method == 'content') {
                    // show page content
                    show_content($url);
                }
            } elseif ($cloak_method == 'redirect' && filter_var($next_page, FILTER_VALIDATE_URL)) {
                // redirect to external page
                redirect_to_page(prepare_url($url));
            } else {
                // if page doesn't exist, show error message
                echo '<h2>Cloakup - Page not found</h2>';
                echo '<div class="error"><p>The page you are trying to access is not available.</p></div>';
                exit;
            }
        } else {
            // if request failed, show setup error message
            echo '<h2>Cloakup - Setup error</h2>';
            echo '<div class="error"><p>Check your account status.</p></div>';
            exit;
        }
    }
}

function prepare_url($url)
{
    if (!empty($_GET)) {
        if (strrpos($url, '?') === false) {
            $url .= '?' . http_build_query($_GET);
        } else {
            $url .= '&' . http_build_query($_GET);
        }
    }

    return $url;
}

function find_post_by_path($path)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'posts';

    $sql = "SELECT * FROM $table_name WHERE post_type = 'page' AND post_name = '$path' LIMIT 1";

    $results = $wpdb->get_results($sql);

    return $results ? $results[0] : false;
}

function redirect_to_page($url)
{
    wp_redirect(prepare_url($url));
    exit;
}

function show_content($url)
{
    $content = file_get_contents($url);
    if ($content) {
        echo $content;
    } else {
        echo '<h2>Cloakup - Page not found</h2>';
        echo '<div class="error"><p>The page you are trying to access is not available.</p></div>';
    }
    exit;
}

if (is_admin()) {
    include dirname(__FILE__) . '/includes/admin.php';

    new Cloakup_Admin();
}

// add action to check page
add_action('template_redirect', 'check_page');

register_activation_hook(__FILE__, 'setup_cloakup');
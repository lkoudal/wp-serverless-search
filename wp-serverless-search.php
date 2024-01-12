<?php

/**
 * Plugin Name: WP Serverless Search
 * Plugin URI: https://github.com/emaildano/wp-serverless-search
 * Description: A static search plugin for WordPress.
 * Version: 0.3
 * Author: DigitalCube - Daniel Olson, @Erudition
 * Author URI: https://digitalcube.jp
 * License: GPL2
 * Text Domain: wp-serverless-search
 */


/**
 * On Plugin Activation
 */

function wp_sls_search_install()
{
  // trigger our function that registers the custom post type
  create_wp_sls_dir();
  create_search_feed_modern();
}

add_action('init', 'create_wp_sls_dir');
register_activation_hook(__FILE__, 'wp_sls_search_install');

/**
 * Create WP SLS Dir
 */

function create_wp_sls_dir()
{

  $upload_dir = wp_get_upload_dir();
  $save_path = $upload_dir['basedir'] . '/wp-sls/.';
  $dirname = dirname($save_path);

  if (!is_dir($dirname)) {
    mkdir($dirname, 0755, true);
  }
}

/**
 * Create Search Feed
 */
//add_action('wp_loaded', 'create_search_feed');
 add_action('publish_post', 'create_search_feed_modern');






/**
 * create_search_feed_modern.
 *
 * @author	Lars Koudal
 * @since	v0.0.1
 * @version	v1.0.0	Friday, January 5th, 2024.
 * @global
 * @return	void
 */
function create_search_feed_modern()
{
  if (defined('WP_DEBUG') && WP_DEBUG) {
    $start_time = microtime(true);
  }

  global $wpdb;

  $post_types = ['post', 'page', 'location'];
  $post_types = "'" . implode("','", $post_types) . "'";

  $query = "
    SELECT p.ID, p.post_title, p.post_excerpt, p.post_content, m.meta_value as formatted_address
    FROM $wpdb->posts p
    LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id AND m.meta_key = 'formatted_address'
    WHERE p.post_status = 'publish' AND p.post_type IN ($post_types)
  ";

  $results = $wpdb->get_results($query);

  $upload_dir = wp_get_upload_dir();
  $raw_path = $upload_dir['basedir'] . '/wp-sls/export.json';

  $posts = [];

  foreach ($results as $post) {
    $post_data = [];
    if (!empty($post->post_title)) {
      $post_data['title'] = $post->post_title;
    }
    if (!empty($post->post_excerpt) || !empty($post->formatted_address)) {
      $description = $post->post_excerpt;
      if (!empty($post->formatted_address)) {
        $description .= ' ' . $post->formatted_address;
      }
      $post_data['description'] = $description;
    }
    if (!empty(strip_tags($post->post_content))) {
      $post_data['content'] = strip_tags($post->post_content);
    }
    if (!empty($post->ID)) {
      $post_data['link'] = str_replace('dirtyfl.local', 'dirtyfl.com', get_permalink($post->ID));

    }
    if (!empty($post_data)) {
      $posts[] = $post_data;
    }
  }

  file_put_contents($raw_path, json_encode($posts));

  if (defined('WP_DEBUG') && WP_DEBUG) {
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    $execution_time_minutes = floor($execution_time / 60);
    $execution_time_seconds = $execution_time % 60;
    error_log("Execution time of create_search_feed_modern: $execution_time_minutes minute(s) $execution_time_seconds second(s)");
  }
}






/**
 * Set Plugin Defaults
 *
 * @author	Lars Koudal
 * @since	v0.0.1
 * @version	v1.0.0	Friday, January 5th, 2024.
 * @global
 * @return	void
 */
function wp_sls_search_default_options()
{
  $options = array(
    'wp_sls_search_form' => '[role=search]',
    'wp_sls_search_form_input' => 'input[type=search]',
    'wp_sls_search_post_type' => 'post'
  );

  foreach ($options as $key => $value) {
    update_option($key, $value);
  }
}

if (!get_option('wp_sls_search_form')) {
  register_activation_hook(__FILE__, 'wp_sls_search_default_options');
}

/**
 * Admin Settings Menu
 */

add_action('admin_menu', 'wp_sls_search');
function wp_sls_search()
{
  add_options_page(
    'WP Serverless Search',
    'WP Serverless Search',
    'manage_options',
    'wp-sls-search',
    'wp_sls_search_options'
  );
}

require_once('lib/includes.php');

/*
* Scripts
*/

add_action('wp_enqueue_scripts', 'wp_sls_search_assets');
add_action('admin_enqueue_scripts', 'wp_sls_search_assets');

function wp_sls_search_assets()
{

  $shifter_js = plugins_url('main/main.js', __FILE__);

  $search_params = array(
    'searchForm' => get_option('wp_sls_search_form'),
    'searchFormInput' => get_option('wp_sls_search_form_input'),
    'uploadDir' => wp_get_upload_dir()['baseurl']
  );

  wp_register_script('wp-sls-search-js', $shifter_js, array('jquery', 'fusejs'), null, true);
  wp_localize_script('wp-sls-search-js', 'searchParams', $search_params);
  wp_enqueue_script('wp-sls-search-js');

  wp_register_script('fusejs', 'https://cdn.jsdelivr.net/npm/fuse.js@7.0.0/dist/fuse.js', null, null, true);
  wp_enqueue_script('fusejs');

  wp_register_style("wp-sls-search-css", plugins_url('/main/main.css', __FILE__));
  wp_enqueue_style("wp-sls-search-css");
}

// create a function that replaces the default WordPress search box output
function wp_sls_search_form($form)
{
  $form = '<form role="search" method="get" class="search-form">
  <label for="wp-sls-earch-field">
    <span class="screen-reader-text">Search for:</span>
  </label>
  <input id="wp-sls-earch-field" class="wp-sls-search-field" type="search" autocomplete="off" class="search-field" placeholder="Search …" value="" name="s">
</form>
<div role="document"></div>
<div class="wp-sls-search-results"></div>';
return $form;
}
// add the action that replaces the content of the default WordPress search form with our custom search wp_sls_search_form
add_action('get_search_form', 'wp_sls_search_form',9999,1);



<?php
/**
 * Plugin Name:       Image Crop Field for ACF
 * Plugin URI:        https://github.com/chrisku/ACF-Image-Crop
 * Description:       An Advanced Custom Fields image field that lets editors crop the selected image to a registered image size or custom dimensions. Based on ACF Image Crop by Anders Thorborg.
 * Version:           2.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Christian Kuffner
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       image-crop-field-for-acf
 * Domain Path:       /lang
 * Update URI:        https://github.com/chrisku/ACF-Image-Crop
 *
 * Copyright (C) Anders Thorborg (original ACF Image Crop add-on, 2014-2017)
 * Copyright (C) 2026 Christian Kuffner
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

defined( 'ABSPATH' ) || exit;

define( 'ICFA_VERSION', '2.0.0' );
define( 'ICFA_FILE', __FILE__ );


// Load bundled translations before ACF (init priority 5) registers the field type.
function icfa_load_textdomain() {
    load_plugin_textdomain( 'image-crop-field-for-acf', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}
add_action( 'init', 'icfa_load_textdomain', 0 );


// Wait until every plugin is loaded so the original add-on and ACF can be detected
// regardless of load order.
function icfa_bootstrap() {

    // The original "ACF Image Crop" add-on registers the same "image_crop" field type.
    // Running both would register it twice, so stay inactive until it is deactivated.
    if ( function_exists( 'include_field_types_image_crop' ) ) {
        add_action( 'admin_notices', 'icfa_notice_original_active' );
        return;
    }

    if ( ! class_exists( 'ACF' ) ) {
        add_action( 'admin_notices', 'icfa_notice_acf_missing' );
        return;
    }

    add_action( 'acf/include_field_types', 'icfa_include_field_types' );
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'icfa_action_links' );
}
add_action( 'plugins_loaded', 'icfa_bootstrap' );


function icfa_include_field_types() {
    require_once __DIR__ . '/class-icfa-field-image-crop.php';
    acf_register_field_type( 'ICFA_Field_Image_Crop' );
}


function icfa_action_links( $links ) {
    $links[] = '<a href="' . esc_url( admin_url( 'options-media.php' ) ) . '">' . esc_html__( 'Settings', 'image-crop-field-for-acf' ) . '</a>';
    return $links;
}


function icfa_notice_original_active() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Image Crop Field for ACF is inactive because the original "Advanced Custom Fields: Image Crop Add-on" plugin is still active. Deactivate the original plugin; existing image crop fields and their values will keep working.', 'image-crop-field-for-acf' ) . '</p></div>';
}


function icfa_notice_acf_missing() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Image Crop Field for ACF requires Advanced Custom Fields (version 5.7 or later) to be active.', 'image-crop-field-for-acf' ) . '</p></div>';
}

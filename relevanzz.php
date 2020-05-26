<?php
/*
   Plugin Name: Relevanzz for Woocommerce
   Plugin URI: https://github.com/Relevanzz/relevanzz-woocommerce
   description: Easily integrate Relevanzz with your WooCommerce  
   Version: 0.0.1
   Author: Relevanzz
   Author URI: https://relevanzz.com
   License: GPL2
   */

defined('ABSPATH') || die('Executing outside of the WordPress context.');

require_once __DIR__ . '/src/class-relevanzz-woocommerce-api.php';

$api = new WC_RLZ_API();

<?php

/**
 * Relevanzz Utils
 *
 * @package Relevanzz_Woocommerce
 */

defined('ABSPATH') || die('Executing outside of the WordPress context.');

/**
 * Woocommerce Utils
 */
class WC_RELEVANZZ_UTILS
{

    const PLUGIN_DIR = WP_PLUGIN_DIR . '/relevanzz-woocommerce';

    /**
     * Returns boolean that determines if Woocoommerce plugin is activated
     */
    public static function isWoocommerceActivated()
    {
        $result = false;
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Prints to rlz.log
     */
    public static function rlzLog($var)
    {
        $fp = fopen(WC_RELEVANZZ_UTILS::PLUGIN_DIR . '/dev_tools/devrlz.log', 'a');
        fwrite($fp, print_r($var, true) . "\n");
        fclose($fp);
    }
}

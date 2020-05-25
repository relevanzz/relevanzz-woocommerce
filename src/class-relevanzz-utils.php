<?php

/**
 * Relevanzz Woocommerce Utils
 *
 * @package Relevanzz_Woocommerce
 */

defined('ABSPATH') || die('Executing outside of the WordPress context.');

/**
 * Woocommerce Utils
 */
class Woocommerce_Utils
{
    const NAME = 'woocommerce-utils-relevanzz';

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
}

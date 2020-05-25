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
class Relevanzz_Utils
{
    const NAME = 'woocommerce-utils-relevanzz';

    /**
     * Prints to rlz.log
     */
    public static function rlzLog($var)
    {
        $fp = fopen('/var/www/html/wp-content/plugins/relevanzz/rlz.log', 'a');
        fwrite($fp, print_r($var, true) . "\n");
        fclose($fp);
    }
}

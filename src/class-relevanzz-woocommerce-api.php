<?php
/**
 * Relevanzz Api Endpoint
 *
 * @package Relevanzz_Woocommerce
 */

defined('ABSPATH') || die('Executing outside of the WordPress context.');
require_once __DIR__ . '/class-relevanzz-utils.php';

class WC_RLZ_API
{
    const VERSION = '1.0.0';

    const RELEVANZZ_BASE_URL = 'relevanzz/v1/';
    const ORDERS_ENDPOINT = 'orders';
    const EXTENSION_VERSION_ENDPOINT = 'version';
    const PRODUCTS_ENDPOINT = 'products';
    const HEALTHCHECK_ENDPOINT = 'healthcheck';

    // API RESPONSES
    const API_RESPONSE_VERSION = 'version';
    const API_RESPONSE_CODE = 'status_code';
    const API_RESPONSE_ERROR = 'error';
    const API_RESPONSE_REASON = 'reason';
    const API_RESPONSE_SUCCESS = 'success';

    // HTTP CODES
    const STATUS_CODE_AUTHORIZATION_ERROR = 403;
    const STATUS_CODE_HTTP_OK = 200;
    const STATUS_CODE_UNPROCESSABLE_ENTITY = 422;
    const STATUS_CODE_PRECONDITION_REQUIRED = 428;

    const DEFAULT_RECORDS_PER_PAGE = '50';
    const DATE_MODIFIED = 'post_modified_gmt';
    const POST_STATUS_ANY = 'any';

    const ERROR_KEYS_NOT_PASSED = 'consumerKeyOrConsumerSecretNotPassed';
    const ERROR_CONSUMER_KEY_NOT_FOUND = 'consumerKeyNotFound';
    const ERROR_WOOCOMMERCE_NOT_INSTALLED = 'woocommerceNotInstalled';
}

function rlz_count_loop(WP_Query $loop)
{
    $loop_ids = array();
    while ($loop->have_posts()) {
        $loop->the_post();
        $loop_id = get_the_ID();
        array_push($loop_ids, $loop_id);
    }
    return $loop_ids;
}

function rlz_validate_request($request)
{
    $is_woocommerce_activated = Woocommerce_Utils::isWoocommerceActivated();
    if(!$is_woocommerce_activated) {
        return rlz_validation_response(
            true,
            WC_RLZ_API::STATUS_CODE_PRECONDITION_REQUIRED,
            WC_RLZ_API::ERROR_WOOCOMMERCE_NOT_INSTALLED,
            false,
            WC_RLZ_API::VERSION
        );
    }   
    $consumer_key = $request->get_param('consumer_key');
    $consumer_secret = $request->get_param('consumer_secret');
    if (empty($consumer_key) || empty($consumer_secret)) {
        return rlz_validation_response(
            true,
            WC_RLZ_API::STATUS_CODE_UNPROCESSABLE_ENTITY,
            WC_RLZ_API::ERROR_KEYS_NOT_PASSED,
            false,
            WC_RLZ_API::VERSION
        );
    }

    global $wpdb;
    // this is stored as a hash so we need to query on the hash
    $key = hash_hmac('sha256', $consumer_key, 'wc-api');
    $user = $wpdb->get_row(
        $wpdb->prepare(
            "
    SELECT consumer_key, consumer_secret
    FROM {$wpdb->prefix}woocommerce_api_keys
    WHERE consumer_key = %s
     ",
            $key
        )
    );

    if ($user->consumer_secret == $consumer_secret) {
        return rlz_validation_response(
            false,
            WC_RLZ_API::STATUS_CODE_HTTP_OK,
            null,
            true,
            WC_RLZ_API::VERSION
        );
    }
    return rlz_validation_response(
        true,
        WC_RLZ_API::STATUS_CODE_AUTHORIZATION_ERROR,
        WC_RLZ_API::ERROR_CONSUMER_KEY_NOT_FOUND,
        false,
        WC_RLZ_API::VERSION
    );
}

function rlz_validation_response($error, $code, $reason, $success, $version)
{
    return array(
        WC_RLZ_API::API_RESPONSE_ERROR => $error,
        WC_RLZ_API::API_RESPONSE_CODE => $code,
        WC_RLZ_API::API_RESPONSE_REASON => $reason,
        WC_RLZ_API::API_RESPONSE_SUCCESS => $success,
        WC_RLZ_API::API_RESPONSE_VERSION => $version
    );
}

function rlz_process_resource_args($request, $post_type)
{
    $page_limit = $request->get_param('page_limit');
    if (empty($page_limit)) {
        $page_limit = WC_RLZ_API::DEFAULT_RECORDS_PER_PAGE;
    }
    $date_modified_after = $request->get_param('date_modified_after');
    $date_modified_before = $request->get_param('date_modified_before');
    $page = $request->get_param('page');

    $args = array(
        'post_type' => $post_type,
        'posts_per_page' => $page_limit,
        'post_status' => WC_RLZ_API::POST_STATUS_ANY,
        'paged' => $page,
        'date_query' => array(
            array(
                'column' => WC_RLZ_API::DATE_MODIFIED,
                'after' => $date_modified_after,
                'before' => $date_modified_before
            )
        ),
    );
    return $args;
}

function rlz_get_orders_count(WP_REST_Request $request)
{
    $validated_request = rlz_validate_request($request);
    if ($validated_request['error'] === true) {
        return $validated_request;
    }

    $args = rlz_process_resource_args($request, 'shop_order');

    $loop = new WP_Query($args);
    $data = rlz_count_loop($loop);
    return array('order_count' => $loop->found_posts);
}

function rlz_get_products_count(WP_REST_Request $request)
{
    $validated_request = rlz_validate_request($request);
    if ($validated_request['error'] === true) {
        return $validated_request;
    }

    $args = rlz_process_resource_args($request, 'product');
    $loop = new WP_Query($args);
    $data = rlz_count_loop($loop);
    return array('product_count' => $loop->found_posts);
}

function rlz_get_products(WP_REST_Request $request)
{
    $validated_request = rlz_validate_request($request);
    if ($validated_request['error'] === true) {
        return $validated_request;
    }

    $args = rlz_process_resource_args($request, 'product');

    $loop = new WP_Query($args);
    $data = rlz_count_loop($loop);
    return array('product_ids' => $data);
}

function rlz_get_orders(WP_REST_Request $request)
{
    $validated_request = rlz_validate_request($request);
    if ($validated_request['error'] === true) {
        return $validated_request;
    }

    $args = rlz_process_resource_args($request, 'shop_order');

    $loop = new WP_Query($args);
    $data = rlz_count_loop($loop);
    return array('order_ids' => $data);
}

function rlz_get_extension_version($data)
{
    return array('version' => WC_RLZ_API::VERSION);
}

function rlz_handleHealthcheck(WP_REST_Request $request)
{
    $validated_request = rlz_validate_request($request);
    if ($validated_request['error'] === true) {
        return $validated_request;
    }
    $is_woocommerce_activated = Woocommerce_Utils::isWoocommerceActivated();
    $response = new HealthcheckResponse(WC_RLZ_API::VERSION, $is_woocommerce_activated);
    return $response->jsonSerialize();
}

add_action('rest_api_init', function () {
    register_rest_route(WC_RLZ_API::RELEVANZZ_BASE_URL, WC_RLZ_API::EXTENSION_VERSION_ENDPOINT, array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'rlz_get_extension_version',
        )
    );
});

add_action('rest_api_init', function ()
{
    register_rest_route(WC_RLZ_API::RELEVANZZ_BASE_URL, 'orders/count', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'rlz_get_orders_count',
        )
    );
});

add_action('rest_api_init', function ()
{
    register_rest_route(WC_RLZ_API::RELEVANZZ_BASE_URL, 'products/count', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'rlz_get_products_count',
        )
    );
});

add_action('rest_api_init', function ()
{
    register_rest_route(WC_RLZ_API::RELEVANZZ_BASE_URL, WC_RLZ_API::ORDERS_ENDPOINT, array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'rlz_get_orders',
        'args' => array(
            'id' => array(
                'validate_callback' => 'is_numeric'
            ),
        ),
        'permission_callback' => function () {
            return true;
        })
    );
});

add_action('rest_api_init', function()
{
    register_rest_route(WC_RLZ_API::RELEVANZZ_BASE_URL, WC_RLZ_API::PRODUCTS_ENDPOINT, array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'rlz_get_products',
        'args' => array(
            'id' => array(
                'validate_callback' => 'is_numeric'
            ),
        ),
        'permission_callback' => function () {
            return true;
        })
    );
});

add_action('rest_api_init', function()
{
    register_rest_route(WC_RLZ_API::RELEVANZZ_BASE_URL, WC_RLZ_API::HEALTHCHECK_ENDPOINT, array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'rlz_handleHealthcheck',
        'args' => array(
            'id' => array(
                'validate_callback' => 'is_numeric'
            ),
        ),
        'permission_callback' => function () {
            return true;
        })
    );
});

class HealthcheckResponse implements JsonSerializable
{
    private $version;
    private $is_woocommerce_activated;
    private $wp_version;
    private $wp_db_version;
    private $required_php_version;
    private $required_mysql_version;
    private $locale;
    private $woocommerce;

    public function __construct($version, $is_woocommerce_activated)
    {
        $this->version = $version;
        $this->is_woocommerce_activated = $is_woocommerce_activated;
        $this->wp_version = $GLOBALS['wp_version'];
        $this->wp_db_version = $GLOBALS['wp_db_version'];
        $this->required_php_version = $GLOBALS['required_php_version'];
        $this->required_mysql_version = $GLOBALS['required_mysql_version'];
        $this->locale = $GLOBALS['locale'];
        //$this->a = array_keys($GLOBALS);
        if ($is_woocommerce_activated) {
            $this->woocommerce = $GLOBALS['woocommerce'];
        }
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

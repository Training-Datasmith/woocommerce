<?php

declare (strict_types=1);
/**
 * Reports Data Store Interface
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports;

if (!defined('ABSPATH')) {
    exit;
}
/**
 * WooCommerce Reports data store interface.
 *
 * @since 3.5.0
 */
interface Data_Store_Interface
{
    /**
     * Get the data based on args.
     *
     * @param array $args Query parameters.
     * @return stdClass|WP_Error
     */
    public function get_data($args);
}
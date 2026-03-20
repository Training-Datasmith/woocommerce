<?php

declare (strict_types=1);
/**
 * WooCommerce Admin Input Parameter Exception Class
 *
 * Exception class thrown when user provides incorrect parameters.
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports;

defined('ABSPATH') || exit;
/**
 * API\Reports\ParameterException class.
 */
class Parameter_Exception extends \WC_Data_Exception
{
}
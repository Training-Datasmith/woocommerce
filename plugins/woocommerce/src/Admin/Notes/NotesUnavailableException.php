<?php

declare (strict_types=1);
/**
 * WooCommerce Admin Notes Unavailable Exception Class
 *
 * Exception class thrown when an attempt to use notes is made but notes are unavailable.
 */
namespace Automattic\Woo_Commerce\Admin\Notes;

defined('ABSPATH') || exit;
/**
 * Notes\NotesUnavailableException class.
 */
class Notes_Unavailable_Exception extends \WC_Data_Exception
{
}
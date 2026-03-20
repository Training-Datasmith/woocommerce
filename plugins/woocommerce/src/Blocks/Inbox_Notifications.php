<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks;

use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Notes;
/**
 * A class used to display inbox messages to merchants in the WooCommerce Admin dashboard.
 *
 * @package Automattic\WooCommerce\Blocks
 * @since x.x.x
 */
class Inbox_Notifications
{
    public const SURFACE_CART_CHECKOUT_NOTE_NAME = 'surface_cart_checkout';
    /**
     * Deletes the note.
     */
    public static function delete_surface_cart_checkout_blocks_notification(): void
    {
        Notes::delete_notes_with_name(self::SURFACE_CART_CHECKOUT_NOTE_NAME);
    }
}
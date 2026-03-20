<?php

declare (strict_types=1);
/**
 * WooCommerce Admin: How to customize your product catalog note provider
 *
 * Adds a note with a link to the customizer a day after adding the first product
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
use Automattic\Woo_Commerce\Enums\Product_Status;
/**
 * Class CustomizingProductCatalog
 *
 * @package Automattic\WooCommerce\Admin\Notes
 */
class Customizing_Product_Catalog
{
    /**
     * Note traits.
     */
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-customizing-product-catalog';
    /**
     * Get the note.
     *
     * @return Note
     */
    public static function get_note()
    {
        $query = new \WC_Product_Query(['limit' => 1, 'paginate' => true, 'status' => [Product_Status::PUBLISH], 'orderby' => 'post_date', 'order' => 'DESC']);
        $products = $query->get_products();
        // we need at least 1 product.
        if (0 === $products->total) {
            return;
        }
        $product = $products->products[0];
        $created_timestamp = $product->get_date_created()->get_timestamp();
        $is_a_day_old = time() - $created_timestamp >= DAY_IN_SECONDS;
        // the product must be at least 1 day old.
        if (!$is_a_day_old) {
            return;
        }
        // store must not been active more than 14 days.
        if (self::wc_admin_active_for(DAY_IN_SECONDS * 14)) {
            return;
        }
        $note = new Note();
        $note->set_title(__('How to customize your product catalog', 'woocommerce'));
        $note->set_content(__('You want your product catalog and images to look great and align with your brand. This guide will give you all the tips you need to get your products looking great in your store.', 'woocommerce'));
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_content_data((object) []);
        $note->set_source('woocommerce-admin');
        $note->add_action('day-after-first-product', __('Learn more', 'woocommerce'), 'https://woocommerce.com/document/woocommerce-customizer/?utm_source=inbox&utm_medium=product');
        return $note;
    }
}
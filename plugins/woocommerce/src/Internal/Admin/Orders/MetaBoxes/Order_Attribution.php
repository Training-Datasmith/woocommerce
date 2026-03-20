<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Orders\Meta_Boxes;

use Automattic\Woo_Commerce\Internal\Traits\Order_Attribution_Meta;
use WC_Order;
/**
 * Class OrderAttribution
 *
 * @since 8.5.0
 */
class Order_Attribution
{
    use Order_Attribution_Meta;
    /**
     * OrderAttribution constructor.
     */
    public function __construct()
    {
        $this->set_fields_and_prefix();
    }
    /**
     * Format the meta data for display.
     *
     * @since 8.5.0
     *
     * @param array $meta The array of meta data to format.
     */
    public function format_meta_data(array &$meta): void
    {
        if (array_key_exists('device_type', $meta)) {
            $meta['device_type'] = match ($meta['device_type']) {
                'Mobile' => __('Mobile', 'woocommerce'),
                'Tablet' => __('Tablet', 'woocommerce'),
                'Desktop' => __('Desktop', 'woocommerce'),
                default => __('Unknown', 'woocommerce'),
            };
        }
    }
    /**
     * Output the attribution data metabox for the order.
     *
     * @since 8.5.0
     *
     * @param WC_Order $order The order object.
     */
    public function output(WC_Order $order): void
    {
        $meta = $this->filter_meta_data($order->get_meta_data());
        $this->format_meta_data($meta);
        // No more details if there is only the origin value - this is for unknown source types.
        $has_more_details = ['origin'] !== array_keys($meta);
        // For direct, web admin, mobile app or pos orders, also don't show more details.
        $simple_sources = ['typein', 'admin', 'mobile_app', 'pos'];
        if (isset($meta['source_type']) && in_array($meta['source_type'], $simple_sources, true)) {
            $has_more_details = false;
        }
        $template_data = ['meta' => $meta, 'has_more_details' => $has_more_details];
        wc_get_template('order/attribution-details.php', $template_data);
    }
}
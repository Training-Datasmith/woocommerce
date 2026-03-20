<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Accordion;

use Automattic\Woo_Commerce\Blocks\Block_Types\Abstract_Block;
use Automattic\Woo_Commerce\Blocks\Block_Types\Enable_Block_Json_Assets_Trait;
/**
 * AccordionItem class.
 */
class Accordion_Item extends Abstract_Block
{
    use Enable_Block_Json_Assets_Trait;
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'accordion-item';
    /**
     * Include and render the block.
     *
     * @param array    $attributes Block attributes. Default empty array.
     * @param string   $content    Block content. Default empty string.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render($attributes, $content, $block)
    {
        if (!$content) {
            return $content;
        }
        $p = new \WP_HTML_Tag_Processor($content);
        $unique_id = wp_unique_id('woocommerce-accordion-item-');
        // Initialize the state of the item on the server using a closure,
        // since we need to get derived state based on the current context.
        wp_interactivity_state('woocommerce/accordion', ['isOpen' => function () {
            $context = wp_interactivity_get_context();
            return $context['openByDefault'];
        }]);
        if ($p->next_tag()) {
            $interactivity_context = ['id' => $unique_id, 'openByDefault' => $attributes['openByDefault']];
            $p->set_attribute('data-wp-context', wp_json_encode($interactivity_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
            $p->set_attribute('data-wp-class--is-open', 'state.isOpen');
            $p->set_attribute('data-wp-init', 'callbacks.initIsOpen');
            if ($p->next_tag()) {
                $p->set_attribute('data-wp-on--click', 'actions.toggle');
                $p->set_attribute('id', $unique_id);
                $p->set_attribute('aria-controls', $unique_id . '-panel');
                $p->set_attribute('data-wp-bind--aria-expanded', 'state.isOpen');
                if ($p->next_tag()) {
                    $p->set_attribute('id', $unique_id . '-panel');
                    $p->set_attribute('aria-labelledby', $unique_id);
                    $p->set_attribute('role', 'region');
                    $p->set_attribute('data-wp-bind--inert', '!state.isOpen');
                    // Only modify content if all directives have been set.
                    $content = $p->get_updated_html();
                }
            }
        }
        return $content;
    }
}
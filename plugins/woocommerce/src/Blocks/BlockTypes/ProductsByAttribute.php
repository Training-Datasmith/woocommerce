<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductsByAttribute class.
 */
class Products_By_Attribute extends Abstract_Product_Grid
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'products-by-attribute';
    /**
     * Set args specific to this block
     *
     * @param array $query_args Query args.
     */
    protected function set_block_query_args(&$query_args)
    {
        if (!empty($this->attributes['attributes'])) {
            $taxonomy = sanitize_title($this->attributes['attributes'][0]['attr_slug']);
            $terms = wp_list_pluck($this->attributes['attributes'], 'id');
            $query_args['tax_query'][] = ['taxonomy' => $taxonomy, 'terms' => array_map(absint(...), $terms), 'field' => 'term_id', 'operator' => 'all' === $this->attributes['attrOperator'] ? 'AND' : 'IN'];
        }
    }
    /**
     * Get block attributes.
     */
    protected function get_block_type_attributes(): array
    {
        return ['align' => $this->get_schema_align(), 'alignButtons' => $this->get_schema_boolean(false), 'attributes' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['id' => ['type' => 'number'], 'attr_slug' => ['type' => 'string']]], 'default' => []], 'attrOperator' => ['type' => 'string', 'default' => 'any'], 'className' => $this->get_schema_string(), 'columns' => $this->get_schema_number(wc_get_theme_support('product_blocks::default_columns', 3)), 'contentVisibility' => $this->get_schema_content_visibility(), 'orderby' => $this->get_schema_orderby(), 'rows' => $this->get_schema_number(wc_get_theme_support('product_blocks::default_rows', 3)), 'isPreview' => $this->get_schema_boolean(false), 'stockStatus' => ['type' => 'array', 'default' => array_keys(wc_get_product_stock_status_options())]];
    }
}
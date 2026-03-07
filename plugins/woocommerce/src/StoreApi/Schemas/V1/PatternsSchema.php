<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * OrderSchema class.
 */
class PatternsSchema extends AbstractSchema
{
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'patterns';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'patterns';

    /**
     * Patterns schema properties.
     */
    public function get_properties(): array
    {
        return [];
    }

    /**
     * Get the Patterns response.
     *
     * @param array $item Item to get response for.
     */
    public function get_item_response($item): array
    {
        return [
            'success' => true,
        ];
    }
}

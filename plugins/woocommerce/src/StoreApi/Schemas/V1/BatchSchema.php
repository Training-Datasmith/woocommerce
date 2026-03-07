<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * BatchSchema class.
 */
class BatchSchema extends AbstractSchema
{
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'batch';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'batch';

    /**
     * Batch schema properties.
     */
    public function get_properties(): array
    {
        return [];
    }
}

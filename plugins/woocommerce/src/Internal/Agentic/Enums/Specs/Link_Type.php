<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Link types as defined in the Agentic Commerce Protocol.
 */
class Link_Type
{
    /**
     * Terms of use/service.
     */
    public const TERMS_OF_USE = 'terms_of_use';
    /**
     * Privacy policy.
     */
    public const PRIVACY_POLICY = 'privacy_policy';
    /**
     * Seller shop policies.
     */
    public const SELLER_SHOP_POLICIES = 'seller_shop_policies';
}
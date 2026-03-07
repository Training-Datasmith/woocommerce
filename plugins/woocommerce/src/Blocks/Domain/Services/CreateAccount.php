<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

/**
 * Service class implementing new create account emails used for order processing via the Block Based Checkout.
 *
 * @deprecated This class can't be removed due to https://github.com/woocommerce/woocommerce/issues/52311.
 */
class CreateAccount
{
    /**
     * Init - register handlers for WooCommerce core email hooks.
     */
    public function init(): void
    {
        // This method is intentionally left blank.
    }

    /**
     * Trigger new account email.
     *
     * @param int   $customer_id       The ID of the new customer account.
     * @param array $new_customer_data Assoc array of data for the new account.
     */
    public function customer_new_account($customer_id = 0, array $new_customer_data = []): void
    {
        // This method is intentionally left blank.
    }
}

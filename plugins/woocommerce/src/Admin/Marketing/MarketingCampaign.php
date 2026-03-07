<?php

declare(strict_types=1);
/**
 * Represents a marketing/ads campaign for marketing channels.
 *
 * Marketing channels (implementing MarketingChannelInterface) can use this class to map their campaign data and present it to WooCommerce core.
 */

namespace Automattic\WooCommerce\Admin\Marketing;

/**
 * MarketingCampaign class
 *
 * @since x.x.x
 */
class MarketingCampaign
{
    /**
     * MarketingCampaign constructor.
     *
     * @param string                $id         The marketing campaign's unique identifier.
     * @param MarketingCampaignType $type       The marketing campaign type.
     * @param string                $title      The title of the marketing campaign.
     * @param string                $manage_url The URL to the channel's campaign management page.
     * @param Price|null            $cost       The cost of the marketing campaign with the currency.
     * @param Price|null            $sales      The sales of the marketing campaign with the currency.
     */
    public function __construct(protected string $id, protected \Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType $type, protected string $title, protected string $manage_url, protected ?\Automattic\WooCommerce\Admin\Marketing\Price $cost = null, protected ?\Automattic\WooCommerce\Admin\Marketing\Price $sales = null)
    {
    }

    /**
     * Returns the marketing campaign's unique identifier.
     */
    public function get_id(): string
    {
        return $this->id;
    }

    /**
     * Returns the marketing campaign type.
     */
    public function get_type(): MarketingCampaignType
    {
        return $this->type;
    }

    /**
     * Returns the title of the marketing campaign.
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * Returns the URL to manage the marketing campaign.
     */
    public function get_manage_url(): string
    {
        return $this->manage_url;
    }

    /**
     * Returns the cost of the marketing campaign with the currency.
     */
    public function get_cost(): ?Price
    {
        return $this->cost;
    }

    /**
     * Returns the sales of the marketing campaign with the currency.
     */
    public function get_sales(): ?Price
    {
        return $this->sales;
    }
}

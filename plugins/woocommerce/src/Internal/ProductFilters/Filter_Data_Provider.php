<?php

/**
 * Provider class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;

defined('ABSPATH') || exit;

/**
 * Provider class.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class FilterDataProvider
{
    /**
     * Hold initialized providers.
     *
     * @var array Product filter data providers.
     */
    private array $providers = [];

    /**
     * Instance of TaxonomyHierarchyData.
     */
    private ?\Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData $taxonomy_hierarchy_data = null;

    /**
     * Initialize dependencies.
     *
     * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
     *
     * @param TaxonomyHierarchyData $taxonomy_hierarchy_data Instance of TaxonomyHierarchyData.
     */
    final public function init(TaxonomyHierarchyData $taxonomy_hierarchy_data): void
    {
        $this->taxonomy_hierarchy_data = $taxonomy_hierarchy_data;
    }

    /**
     * Get the data provider with desired query clauses generator.
     *
     * @param QueryClausesGenerator $query_clauses_generator The query clauses generator instance.
     */
    public function with(QueryClausesGenerator $query_clauses_generator)
    {
        $class_name = $query_clauses_generator::class;

        if (! isset($this->providers[ $class_name ])) {
            $this->providers[ $class_name ] = new FilterData($query_clauses_generator, $this->taxonomy_hierarchy_data);
        }

        return $this->providers[ $class_name ];
    }
}

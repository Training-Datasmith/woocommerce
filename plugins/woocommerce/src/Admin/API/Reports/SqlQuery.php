<?php

declare(strict_types=1);
/**
 * Admin\API\Reports\SqlQuery class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin\API\Reports\SqlQuery: Common parent for manipulating SQL query clauses.
 */
class SqlQuery
{
    /**
     * List of SQL clauses.
     */
    private array $sql_clauses = [
        'select'     => [],
        'from'       => [],
        'left_join'  => [],
        'join'       => [],
        'right_join' => [],
        'where'      => [],
        'where_time' => [],
        'group_by'   => [],
        'having'     => [],
        'limit'      => [],
        'order_by'   => [],
        'union'      => [],
    ];
    /**
     * SQL clause merge filters.
     */
    private array $sql_filters = [
        'where' => [
            'where',
            'where_time',
        ],
        'join'  => [
            'right_join',
            'join',
            'left_join',
        ],
    ];

    /**
     * Constructor.
     *
     * @param string $context Optional context passed to filters. Default empty string.
     */
    public function __construct(
        /**
         * Data store context used to pass to filters.
         */
        protected $context = ''
    ) {
    }

    /**
     * Add a SQL clause to be included when get_data is called.
     *
     * @param string $type   Clause type.
     * @param string $clause SQL clause.
     */
    public function add_sql_clause($type, $clause): void
    {
        if (isset($this->sql_clauses[ $type ]) && ! empty($clause)) {
            $this->sql_clauses[ $type ][] = $clause;
        }
    }

    /**
     * Get SQL clause by type.
     *
     * @param string $type     Clause type.
     * @param string $handling Whether to filter the return value (filtered|unfiltered). Default unfiltered.
     *
     * @return string SQL clause.
     */
    protected function get_sql_clause($type, $handling = 'unfiltered'): string
    {
        if (! isset($this->sql_clauses[ $type ])) {
            return '';
        }

        /**
         * Default to bypassing filters for clause retrieval internal to data stores.
         * The filters are applied when the full SQL statement is retrieved.
         */
        if ('unfiltered' === $handling) {
            return implode(' ', $this->sql_clauses[ $type ]);
        }

        if (isset($this->sql_filters[ $type ])) {
            $clauses = [];
            foreach ($this->sql_filters[ $type ] as $subset) {
                $clauses = array_merge($clauses, $this->sql_clauses[ $subset ]);
            }
        } else {
            $clauses = $this->sql_clauses[ $type ];
        }

        /**
         * Filter SQL clauses by type and context.
         *
         * @param array  $clauses The original arguments for the request.
         * @param string $context The data store context.
         */
        $clauses = apply_filters("woocommerce_analytics_clauses_{$type}", $clauses, $this->context);
        /**
         * Filter SQL clauses by type and context.
         *
         * @param array  $clauses The original arguments for the request.
         */
        $clauses = apply_filters("woocommerce_analytics_clauses_{$type}_{$this->context}", $clauses);
        return implode(' ', $clauses);
    }

    /**
     * Clear SQL clauses by type.
     *
     * @param string|array $types Clause type.
     */
    protected function clear_sql_clause($types)
    {
        foreach ((array) $types as $type) {
            if (isset($this->sql_clauses[ $type ])) {
                $this->sql_clauses[ $type ] = [];
            }
        }
    }

    /**
     * Replace strings within SQL clauses by type.
     *
     * @param string $type    Clause type.
     * @param string $search  String to search for.
     * @param string $replace Replacement string.
     */
    protected function str_replace_clause($type, $search, $replace)
    {
        if (isset($this->sql_clauses[ $type ])) {
            foreach ($this->sql_clauses[ $type ] as $key => $sql) {
                $this->sql_clauses[ $type ][ $key ] = str_replace($search, $replace, $sql);
            }
        }
    }

    /**
     * Get the full SQL statement.
     */
    public function get_query_statement(): string
    {
        $join     = $this->get_sql_clause('join', 'filtered');
        $where    = $this->get_sql_clause('where', 'filtered');
        $group_by = $this->get_sql_clause('group_by', 'filtered');
        $having   = $this->get_sql_clause('having', 'filtered');
        $order_by = $this->get_sql_clause('order_by', 'filtered');
        $union    = $this->get_sql_clause('union', 'filtered');

        $statement = '';

        $statement .= "
			SELECT
				{$this->get_sql_clause('select', 'filtered')}
			FROM
				{$this->get_sql_clause('from', 'filtered')}
				{$join}
			WHERE
				1=1
				{$where}
		";

        if (! empty($group_by)) {
            $statement .= "
				GROUP BY
					{$group_by}
			";
            if (! empty($having)) {
                $statement .= "
					HAVING
						1=1
						{$having}
				";
            }
        }

        if (! empty($union)) {
            $statement .= "
				UNION
					{$union}
			";
        }

        if (! empty($order_by)) {
            $statement .= "
				ORDER BY
					{$order_by}
			";
        }

        return $statement . $this->get_sql_clause('limit', 'filtered');
    }

    /**
     * Reinitialize the clause array.
     */
    public function clear_all_clauses(): void
    {
        $this->sql_clauses = [
            'select'     => [],
            'from'       => [],
            'left_join'  => [],
            'join'       => [],
            'right_join' => [],
            'where'      => [],
            'where_time' => [],
            'group_by'   => [],
            'having'     => [],
            'limit'      => [],
            'order_by'   => [],
            'union'      => [],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\ResultFormatters;

/**
 * Class JsonResultFormatter
 */
class JsonResultFormatter
{
    /**
     * JsonResultFormatter constructor.
     *
     * @param array $results The results to format.
     */
    public function __construct(
        /**
         * The results to format.
         */
        private readonly array $results
    ) {
    }

    /**
     * Format the results.
     *
     * @param string $message_type The message type to format.
     */
    public function format(string $message_type = 'all'): array
    {
        $data = [
            'is_success' => $this->is_success(),
            'messages'   => [],
        ];

        foreach ($this->results as $result) {
            $step_name = $result->get_step_name();
            foreach ($result->get_messages($message_type) as $message) {
                if (! isset($data['messages'][ $message['type'] ])) {
                    $data['messages'][ $message['type'] ] = [];
                }
                $data['messages'][ $message['type'] ][] = [
                    'step'    => $step_name,
                    'type'    => $message['type'],
                    'message' => $message['message'],
                ];
            }
        }

        return $data;
    }

    /**
     * Check if all results are successful.
     *
     * @return bool True if all results are successful, false otherwise.
     */
    public function is_success(): bool
    {
        foreach ($this->results as $result) {
            $is_success = $result->is_success();
            if (! $is_success) {
                return false;
            }
        }
        return true;
    }
}

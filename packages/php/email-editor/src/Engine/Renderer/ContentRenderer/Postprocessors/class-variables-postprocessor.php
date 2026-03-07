<?php

/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * In some case the blocks HTML contains CSS variables.
 * For example when spacing is set from a preset the inline styles contain var(--wp--preset--spacing--10), var(--wp--preset--spacing--20) etc.
 * This postprocessor uses variables from theme.json and replaces the CSS variables with their values in final email HTML.
 */
class Variables_Postprocessor implements Postprocessor
{
    /**
     * Constructor.
     *
     * @param Theme_Controller $theme_controller Theme controller.
     */
    public function __construct(
        /**
         * Instance of Theme_Controller.
         */
        private readonly Theme_Controller $theme_controller
    ) {
    }

    /**
     * Postprocess the HTML.
     *
     * @param string $html HTML to postprocess.
     */
    public function postprocess(string $html): string
    {
        $variables    = $this->theme_controller->get_variables_values_map();
        $replacements = [];

        foreach ($variables as $name => $value) {
            $var_pattern                  = '/' . preg_quote('var(' . $name . ')', '/') . '/i';
            $replacements[ $var_pattern ] = $value;
        }

        // We want to replace the CSS variables only in the style attributes to avoid replacing the actual content.
        $processor = new \WP_HTML_Tag_Processor($html);

        while ($processor->next_tag()) {
            $style = $processor->get_attribute('style');

            if (null !== $style && true !== $style) {
                // Replace CSS variables with their values.
                $processed_style = preg_replace(array_keys($replacements), array_values($replacements), $style);

                if (null !== $processed_style) {
                    $processor->set_attribute('style', $processed_style);
                }
            }
        }

        return $processor->get_updated_html();
    }
}

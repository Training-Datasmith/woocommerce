<?php

/**
 * WooCommerce Admin Sanitization Helper
 *
 * @package WooCommerce\Admin\Helper
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WC_Helper_Sanitization Class
 *
 * Provides sanitization functions for admin content.
 */
class WC_Helper_Sanitization
{
    /**
     * Sanitize CSS markup from API responses for safe rendering in admin pages.
     *
     * @param string $css The raw CSS to sanitize.
     *
     * @return string Sanitized CSS safe for inclusion in style blocks.
     */
    public static function sanitize_css($css): string
    {
        // Handle non-string inputs (return empty string).
        if (! is_string($css)) {
            return '';
        }

        // Remove potentially harmful constructs.
        $css = preg_replace('/@import\s+[^;]+;?/', '', $css);

        // Block all data URIs.
        $css = preg_replace('/url\s*\(\s*([\'"]?)data:/i', 'url($1invalid:', (string) $css);

        // Only allow URLs from specific trusted domains and their subdomains.
        $css = preg_replace_callback(
            '/url\s*\(\s*([\'"]?)(https?:\/\/[^)]+)\1\s*\)/i',
            function ($matches): string {
                $url   = $matches[2];
                $quote = $matches[1];

                // Check if URL belongs to allowed domains.
                if (preg_match(
                    '/^https?:\/\/(([\w-]+\.)*woocommerce\.com|' .
                    '([\w-]+\.)*woocommerce\.test|' .
                    '([\w-]+\.)*WordPress\.com|' .
                    '([\w-]+\.)*wp\.com)/ix',
                    $url
                )) {
                    // URL is from a trusted domain, keep it.
                    return "url({$quote}{$url}{$quote})";
                }
                // URL is not from a trusted domain, make it ineffective.
                return "url({$quote}#blocked-url{$quote})";
            },
            (string) $css
        );

        // Preserve all asterisks by temporarily replacing them.
        $css = str_replace('*', '__PRESERVED_ASTERISK__', $css);

        // Remove HTML tags and PHP.
        $css = wp_strip_all_tags($css);

        // Remove any JavaScript events.
        $css = preg_replace('/\s*expression\s*\(.*?\)/', '', $css);
        $css = preg_replace('/\s*javascript\s*:/', '', $css);

        // Block other potentially dangerous protocols.
        $css = preg_replace('/(behavior|eval|calc|mocha)(\s*:|\s*\()/i', 'blocked', $css);

        // Restore all asterisks.
        $css = str_replace('__PRESERVED_ASTERISK__', '*', $css);

        // We assume relative and root-relative URLs are safe because they point to resources on the same domain.

        // Limit size of CSS to prevent DoS.
        $css = substr($css, 0, 100000);

        return $css;
    }

    /**
     * Sanitize HTML content allowing a subset of SVG elements.
     *
     * @param string $html The HTML to sanitize.
     *
     * @return string Sanitized HTML with SVG support.
     */
    public static function sanitize_html($html)
    {
        $allowed_html = wp_kses_allowed_html('post');

        // Selected SVG tags and attributes.
        $svg_tags     = self::wc_kses_safe_svg_tags();
        $allowed_html = array_merge($allowed_html, $svg_tags);

        return wp_kses(self::wc_pre_sanitize_svg($html), $allowed_html);
    }

    /**
     * Sanitize SVG content before processing with wp_kses.
     *
     * @param string $content The SVG content to sanitize.
     * @return string Sanitized SVG content.
     */
    public static function wc_pre_sanitize_svg($content): string|array|null
    {
        // Remove any xlink:href attributes containing javascript.
        $content = preg_replace('/xlink:href\s*=\s*(["\'])\s*javascript:.*?\1/i', '', $content);

        // Remove foreignObject elements (can contain arbitrary HTML).
        $content = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', (string) $content);

        return $content;
    }

    /**
     * Add limited SVG support to wp_kses_post with XSS protection.
     *
     * @return array Array of allowed SVG tags and their attributes.
     */
    public static function wc_kses_safe_svg_tags(): array
    {
        // SVG elements and attributes - security focused.
        return [
            'svg'            => [
                'class'               => true,
                'aria-hidden'         => true,
                'aria-labelledby'     => true,
                'role'                => true,
                'xmlns'               => true,
                'width'               => true,
                'height'              => true,
                'viewbox'             => true,
                'viewBox'             => true,
                'preserveAspectRatio' => true,
                'fill'                => true,
                'stroke'              => true,
                'stroke-width'        => true,
                'stroke-linecap'      => true,
                'stroke-linejoin'     => true,
                // Explicitly exclude dangerous attributes.
                'onload'              => false,
                'onclick'             => false,
            ],
            'g'              => [
                'fill'      => true,
                'transform' => true,
                'stroke'    => true,
            ],
            'title'          => [
                'title' => true,
            ],
            'path'           => [
                'd'               => true,
                'fill'            => true,
                'transform'       => true,
                'stroke'          => true,
                'stroke-width'    => true,
                'stroke-linecap'  => true,
                'stroke-linejoin' => true,
            ],
            'polyline'       => [
                'points'       => true,
                'fill'         => true,
                'stroke'       => true,
                'stroke-width' => true,
            ],
            'polygon'        => [
                'points'       => true,
                'fill'         => true,
                'stroke'       => true,
                'stroke-width' => true,
            ],
            'circle'         => [
                'cx'           => true,
                'cy'           => true,
                'r'            => true,
                'fill'         => true,
                'stroke'       => true,
                'stroke-width' => true,
            ],
            'rect'           => [
                'x'            => true,
                'y'            => true,
                'width'        => true,
                'height'       => true,
                'fill'         => true,
                'stroke'       => true,
                'stroke-width' => true,
                'rx'           => true,
                'ry'           => true,
            ],
            'line'           => [
                'x1'           => true,
                'y1'           => true,
                'x2'           => true,
                'y2'           => true,
                'stroke'       => true,
                'stroke-width' => true,
            ],
            'defs'           => [],
            'linearGradient' => [
                'id'            => true,
                'x1'            => true,
                'y1'            => true,
                'x2'            => true,
                'y2'            => true,
                'gradientUnits' => true,
            ],
            'radialGradient' => [
                'id'            => true,
                'cx'            => true,
                'cy'            => true,
                'r'             => true,
                'gradientUnits' => true,
            ],
            'stop'           => [
                'offset'       => true,
                'stop-color'   => true,
                'stop-opacity' => true,
                // Remove style which can contain JavaScript.
                'style'        => false,
            ],
            // Removed potentially risky elements.
            // 'use' - can reference external content.
            // 'mask' - not commonly needed and adds complexity.
        ];
    }
}

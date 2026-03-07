<?php

declare(strict_types=1);
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/wrapper-end.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.3.0
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$template = wc_get_theme_slug_for_templates();

switch ($template) {
    case 'twentyten':
    case 'twentytwelve':
    case 'twentythirteen':
    case 'twentyfifteen':
        echo '</div></div>';
        break;
    case 'twentyeleven':
        echo '</div>';
        get_sidebar('shop');
        echo '</div>';
        break;
    case 'twentyfourteen':
        echo '</div></div></div>';
        get_sidebar('content');
        break;
    case 'twentysixteen':
    default:
        echo '</main></div>';
        break;
}

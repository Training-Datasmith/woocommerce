<?php

/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Process_Manager;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use WP_Style_Engine;

/**
 * Class Renderer
 */
class Renderer
{
    /**
     * Map of placeholders to full HTML comment tags for restoration.
     */
    private array $personalization_tag_placeholders = [];

    public const TEMPLATE_FILE        = 'template-canvas.php';
    public const TEMPLATE_STYLES_FILE = 'template-canvas.css';

    /**
     * Renderer constructor.
     *
     * @param Content_Renderer              $content_renderer Content renderer.
     * @param Templates                     $templates Templates.
     * @param Css_Inliner                   $css_inliner CSS Inliner.
     * @param Theme_Controller              $theme_controller Theme controller.
     * @param Personalization_Tags_Registry $personalization_tags_registry Personalization tags registry.
     * @param Process_Manager               $process_manager Process manager.
     */
    public function __construct(
        /**
         * Content renderer
         */
        private readonly Content_Renderer $content_renderer,
        /**
         * Templates
         */
        private readonly Templates $templates,
        /**
         * Css inliner
         */
        private readonly Css_Inliner $css_inliner,
        /**
         * Theme controller
         */
        private readonly Theme_Controller $theme_controller,
        /**
         * Personalization tags registry
         */
        private readonly Personalization_Tags_Registry $personalization_tags_registry,
        /**
         * Process manager
         */
        private readonly Process_Manager $process_manager
    ) {
    }

    /**
     * Renders the email template
     *
     * @param \WP_Post $post Post object.
     * @param string   $subject Email subject.
     * @param string   $pre_header An email preheader or preview text is the short snippet of text that follows the subject line in an inbox. See https://kb.mailpoet.com/article/418-preview-text.
     * @param string   $language Email language.
     * @param string   $meta_robots Optional string. Can be left empty for sending, but you can provide a value (e.g. noindex, nofollow) when you want to display email html in a browser.
     * @param string   $template_slug Optional block template slug used for cases when email doesn't have associated template.
     */
    public function render(\WP_Post $post, string $subject, string $pre_header, string $language, string $meta_robots = '', string $template_slug = ''): array // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    {if (! $template_slug) {
        $template_slug = get_page_template_slug($post) ?: 'email-general';
    }
        /** @var \WP_Block_Template $template */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
        $template = $this->templates->get_block_template($template_slug);

        $email_styles   = $this->theme_controller->get_styles();
        $content_result = $this->content_renderer->render_without_css_inline($post, $template);
        $template_html  = $content_result['html'];
        $content_styles = $content_result['styles'];
        $layout         = $this->theme_controller->get_layout_settings();

        ob_start();
        include self::TEMPLATE_FILE;
        $rendered_template = (string) ob_get_clean();

        $template_styles  =
        WP_Style_Engine::compile_css(
            [
                'background-color' => $email_styles['color']['background'] ?? 'inherit',
                'color'            => $email_styles['color']['text'] ?? 'inherit',
                'padding-top'      => $email_styles['spacing']['padding']['top'] ?? '0px',
                'padding-bottom'   => $email_styles['spacing']['padding']['bottom'] ?? '0px',
                'padding-left'     => $email_styles['spacing']['padding']['left'] ?? '0px',
                'padding-right'    => $email_styles['spacing']['padding']['right'] ?? '0px',
                'font-family'      => $email_styles['typography']['fontFamily'] ?? 'inherit',
                'line-height'      => $email_styles['typography']['lineHeight'] ?? '1.5',
                'font-size'        => $email_styles['typography']['fontSize'] ?? 'inherit',
            ],
            'body, .email_layout_wrapper'
        );
        $template_styles .= '.email_layout_wrapper { box-sizing: border-box;}';
        $template_styles .= file_get_contents(__DIR__ . '/' . self::TEMPLATE_STYLES_FILE);
        $template_styles  = wp_strip_all_tags((string) apply_filters('woocommerce_email_renderer_styles', $template_styles, $post));

        // Single CSS inlining pass: combine content and template styles, then inline all at once.
        $all_styles        = '<style>' . $template_styles . $content_styles . '</style>';
        $rendered_template = $this->inline_css_styles($all_styles . $rendered_template);

        // Postprocess after CSS inlining (border normalization, CSS variable replacement, etc.).
        $rendered_template = $this->process_manager->postprocess($rendered_template);

        // This is a workaround to support link :hover in some clients. Ideally we would remove the ability to set :hover
        // however this is not possible using the color panel from Gutenberg.
        if (isset($email_styles['elements']['link'][':hover']['color']['text'])) {
            $rendered_template = str_replace('<!-- Forced Styles -->', '<style>a:hover { color: ' . esc_attr($email_styles['elements']['link'][':hover']['color']['text']) . ' !important; }</style>', $rendered_template);
        }

        return [
            'html' => $rendered_template,
            'text' => $this->render_text_version($rendered_template),
        ];
    }

    /**
     * Inlines CSS styles into the HTML
     *
     * @param string $template HTML template.
     */
    private function inline_css_styles(string $template): string
    {
        return $this->css_inliner->from_html($template)->inline_css()->render();
    }

    /**
     * Renders the text version of the email template.
     *
     * @param string $template HTML template.
     */
    private function render_text_version(string $template): string
    {
        $template = (mb_detect_encoding($template, 'UTF-8', true)) ? $template : mb_convert_encoding($template, 'UTF-8', mb_list_encodings());

        // Ensure template is a string before processing.
        if (! is_string($template)) {
            return '';
        }

        // Preserve personalization tags by temporarily replacing them with unique placeholders.
        $template = $this->preserve_personalization_tags($template);

        $result = Html2Text::convert($template, [ 'ignore_errors' => true ]);
        if (! $result) {
            return '';
        }

        // Restore personalization tags from placeholders.
        $result = $this->restore_personalization_tags($result);

        return $result;
    }

    /**
     * Preserves personalization tags by replacing them with unique placeholders (not inside comments).
     *
     * @param string $template HTML template.
     */
    private function preserve_personalization_tags(string $template): string
    {
        $all_registered_tags                    = $this->personalization_tags_registry->get_all();
        $this->personalization_tag_placeholders = [];
        $counter                                = 0;

        $base_tokens    = []; // All the tokens used in the email, e.g. [woocommerce/customer-username].
        $token_prefixes = []; // All the used prefixes, e.g. woocommerce, mailpoet, etc.
        foreach ($all_registered_tags as $tag) {
            $token                 = $tag->get_token(); // E.g. [woocommerce/customer-username].
            $base_tokens[ $token ] = true;
            // Remove brackets for regex matching, escape for regex.
            $token_prefixes[] = preg_quote(substr((string) $token, 1, -1), '/');
        }

        if (empty($token_prefixes)) {
            return $template;
        }

        // Match all of the code comments that look like a personalization tags.
        $pattern = '/<!--\[(' . implode('|', $token_prefixes) . ')(?:\s+[^\]]*)?\]-->/';

        $template = preg_replace_callback(
            $pattern,
            function ($matches) use (&$counter, $base_tokens): string {
                // $matches[1] is the token without brackets, add brackets for lookup.
                $base_token = '[' . $matches[1] . ']';
                if (isset($base_tokens[ $base_token ])) {
                    $placeholder = 'PERSONALIZATION_TAG_PLACEHOLDER_' . $counter;
                    $this->personalization_tag_placeholders[ $placeholder ] = $matches[0];
                    ++$counter;
                    return $placeholder;
                }
                return $matches[0];
            },
            $template
        );

        return $template ?? '';
    }

    /**
     * Restores personalization tags from placeholders
     *
     * @param string $text Text content.
     */
    private function restore_personalization_tags(string $text): string
    {
        if (empty($this->personalization_tag_placeholders)) {
            return $text;
        }
        foreach ($this->personalization_tag_placeholders as $placeholder => $html_comment) {
            $text = str_replace($placeholder, $html_comment, $text);
        }
        return $text;
    }
}

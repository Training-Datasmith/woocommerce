<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

/**
 * Class ClassExtractor
 *
 * Provides functionality to manipulate PHP class files by replacing variables,
 * adding prefixes, and removing strict types declarations.
 *
 * This class is used to generate 'code' part for runPHP step from a template file.
 */
class ClassExtractor
{
    /**
     * Path to the PHP file being processed.
     */
    private readonly string $file_path;

    /**
     * Whether the file contains a strict types declaration.
     */
    private bool $has_strict_types_declaration = false;

    /**
     * PHP code to prefix to the final output.
     */
    private string $prefix = '';

    /**
     * Replacements for class variables.
     */
    private array $class_variable_replacements = [];

    /**
     * Replacements for method variables.
     */
    private array $method_variable_replacements = [];

    /**
     * Constructor.
     *
     * @param string $file_path Path to the PHP file to process.
     *
     * @throws \InvalidArgumentException If the file does not exist.
     */
    public function __construct(string $file_path)
    {
        if (! file_exists($file_path)) {
            throw new \InvalidArgumentException("File not found: $file_path");
        }
        $this->file_path = $file_path;
    }

    /**
     * Adds a prefix to include the WordPress wp-load.php file.
     *
     * @return $this
     */
    public function with_wp_load(): static
    {
        $this->prefix .= "<?php require_once 'wordpress/wp-load.php'; ";
        return $this;
    }

    /**
     * Replaces a class variable with a new value.
     *
     * @param string $variable_name Name of the class variable.
     * @param mixed  $new_value The new value to assign to the variable.
     *
     * @return $this
     */
    public function replace_class_variable($variable_name, $new_value): static
    {
        $this->class_variable_replacements[ $variable_name ] = $new_value;
        return $this;
    }

    /**
     * Replaces a variable inside a method with a new value.
     *
     * @param string $method_name Name of the method.
     * @param string $variable_name Name of the variable to replace.
     * @param mixed  $new_value The new value to assign to the variable.
     *
     * @return $this
     */
    public function replace_method_variable($method_name, $variable_name, $new_value): static
    {
        $this->method_variable_replacements[] = [
            'method'   => $method_name,
            'variable' => $variable_name,
            'value'    => $new_value,
        ];
        return $this;
    }

    /**
     * Generates the processed PHP code with applied replacements and prefixes.
     *
     * @return string The modified PHP code.
     */
    public function get_code(): string
    {
        // Security check: Check if we can replace this with a more secure function.
        $file_content = file_get_contents($this->file_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        $file_content = preg_replace('/<\?php\s*/', '', $file_content);

        if (preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', (string) $file_content)) {
            $this->has_strict_types_declaration = true;
            $file_content                       = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', '', (string) $file_content);
        }

        $file_content = preg_replace('/\/\*.*?\*\/|\/\/.*?(?=\r?\n)/s', '', (string) $file_content);

        foreach ($this->class_variable_replacements as $variable => $value) {
            $file_content = $this->apply_class_variable_replacement($file_content, $variable, $value);
        }

        foreach ($this->method_variable_replacements as $replacement) {
            $file_content = $this->apply_variable_replacement(
                $file_content,
                $replacement['method'],
                $replacement['variable'],
                $replacement['value']
            );
        }

        return $this->prefix . trim((string) $file_content);
    }

    /**
     * Applies a replacement to a class variable in the file content.
     *
     * @param string $file_content The content of the PHP file.
     * @param string $variable_name The name of the variable to replace.
     * @param mixed  $new_value The new value for the variable.
     *
     * @return string The updated file content.
     */
    private function apply_class_variable_replacement($file_content, int|string $variable_name, $new_value): ?string
    {
        // Security check: Check if it's necessary to use var_export.
        $replacement_value = var_export($new_value, true); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

        $pattern = '/(protected|private|public)\s+\$' . preg_quote((string) $variable_name, '/') . '\s*=\s*.*?;|'
            . '(protected|private|public)\s+\$' . preg_quote((string) $variable_name, '/') . '\s*;?/';

        $replacement = "$1 \$$variable_name = $replacement_value;";
        return preg_replace($pattern, $replacement, $file_content, 1);
    }

    /**
     * Applies a replacement to a variable in a specific method.
     *
     * @param string $file_content The content of the PHP file.
     * @param string $method_name The name of the method containing the variable.
     * @param string $variable_name The name of the variable to replace.
     * @param mixed  $new_value The new value for the variable.
     *
     * @return string The updated file content.
     */
    private function apply_variable_replacement($file_content, $method_name, string $variable_name, $new_value)
    {
        $pattern = '/function\s+' . preg_quote($method_name, '/') . '\s*\([^)]*\)\s*\{\s*(.*?)\s*\}/s';
        if (preg_match($pattern, $file_content, $matches)) {
            $method_body = $matches[1];

            // Security check: Check if it's necessary to use var_export.
            $new_value_exported = var_export($new_value, true); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
            $variable_pattern   = '/\$' . preg_quote($variable_name, '/') . '\s*=\s*[^;]+;/';
            $replacement        = '$' . $variable_name . ' = ' . $new_value_exported . ';';

            $updated_method_body = preg_replace($variable_pattern, $replacement, $method_body, 1);

            if (null !== $updated_method_body) {
                $file_content = str_replace($method_body, $updated_method_body, $file_content);
            }
        }

        return $file_content;
    }

    /**
     * Checks if the file has a strict types declaration.
     *
     * @return bool True if the file has a strict types declaration, false otherwise.
     */
    public function has_strict_type_declaration(): bool
    {
        return $this->has_strict_types_declaration;
    }
}

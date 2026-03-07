<?php

declare(strict_types=1);
/**
 * Formatter class
 *
 * @package  WooCommerce
 */

namespace Automattic\WooCommerce\MonorepoTools\Changelogger;

/**
 * Base Jetpack Changelogger Formatter for WooCommerce
 */

use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\KeepAChangelogParser;
use Automattic\Jetpack\Changelogger\PluginTrait;

/**
 * Base Jetpack Changelogger Formatter for WooCommerce
 *
 * Note: Since the "filename" loading relies on a single class implementing the plugin interface,
 * we have to implement it in the child class, even though the base class satisfies it.
 *
 * Class Formatter
 */
class Formatter extends KeepAChangelogParser
{
    use PluginTrait;

    /**
     * Bullet for changes.
     *
     * @var string
     */
    public $bullet = '-   ';

    /**
     * Prologue text.
     *
     * @var string
     */
    public $prologue = "# Changelog \n\n";

    /**
     * Epilogue text.
     *
     * @var string
     */
    public $epilogue = '';

    /**
     * Entry pattern regex.
     *
     * @var string
     */
    public $entry_pattern = '/^##\s+([^\n=]+)\s+((?:(?!^##).)+)/ms';

    /**
     * Heading pattern regex.
     *
     * @var string
     */
    public $heading_pattern = '/^## \[+(\[?[^] ]+\]?)\]\(.+\) - (.+?)\n/s';

    /**
     * Subheading pattern regex.
     *
     * @var string
     */
    public $subentry_pattern = '/^###(.+)\n/m';

    /**
     * Return the epiologue.
     */
    public function getEpilogue()
    {
        return $this->epilogue;
    }

    /**
     * Get Release link given a version number.
     *
     * @throws \InvalidArgumentException When directory parsing fails.
     * @param string $version Release version.
     *
     * @return string Link to the version's release.
     */
    public function getReleaseLink(string $version): string
    {
        return 'https://github.com/woocommerce/woocommerce/releases/tag/' . $version;
    }

    /**
     * Modified version of parse() from KeepAChangelogParser.
     *
     * @param string $changelog Changelog contents.
     * @return Changelog
     * @throws \InvalidArgumentException If the changelog data cannot be parsed.
     */
    public function parse($changelog)
    {
        $ret = new Changelog();

        // Fix newlines and expand tabs.
        $changelog = strtr($changelog, [ "\r\n" => "\n" ]);
        $changelog = strtr($changelog, [ "\r" => "\n" ]);
        while (str_contains((string) $changelog, "\t")) {
            $changelog = preg_replace_callback(
                '/^([^\t\n]*)\t/m',
                fn ($m) => $m[1] . str_repeat(' ', 4 - (mb_strlen((string) $m[1]) % 4)),
                (string) $changelog
            );
        }

        // Entries make up the rest of the document.
        $entries       = [];
        $entry_pattern = $this->entry_pattern;
        preg_match_all($entry_pattern, (string) $changelog, $matches);

        foreach ($matches[0] as $section) {
            // Remove the epilogue, if it exists.
            $section = str_replace($this->epilogue, '', $section);

            $heading_pattern  = $this->heading_pattern;
            $subentry_pattern = $this->subentry_pattern;

            // Parse the heading and create a ChangelogEntry for it.
            preg_match($heading_pattern, $section, $heading);

            // Check if the heading may be a sub-heading.
            preg_match($subentry_pattern, $section, $subheading);
            $is_subentry = count($subheading) > 0;

            if (! count($heading) && ! count($subheading)) {
                throw new \InvalidArgumentException('Invalid heading');
            }

            $version         = '';
            $timestamp       = new \DateTime('now', new \DateTimeZone('UTC'));
            $entry_timestamp = new \DateTime('now', new \DateTimeZone('UTC'));

            if (count($heading)) {
                $version   = $heading[1];
                $timestamp = $heading[2];

                try {
                    $timestamp = new \DateTime($timestamp, new \DateTimeZone('UTC'));
                } catch (\Exception $ex) {
                    throw new \InvalidArgumentException("Heading has an invalid timestamp: $heading", 0, $ex);
                }

                if (strtotime($heading[2], 0) !== strtotime($heading[2], 1000000000)) {
                    throw new \InvalidArgumentException("Heading has a relative timestamp: $heading");
                }
                $entry_timestamp = $timestamp;

                $content = trim((string) preg_replace($heading_pattern, '', $section));
            } elseif ($is_subentry) {
                // It must be a subheading.
                $version = $subheading[0]; // For now.
                $content = trim((string) preg_replace($subentry_pattern, '', $section));
            }

            $entry = $this->newChangelogEntry(
                $version,
                [
                    'timestamp' => $timestamp,
                ]
            );

            $entries[] = $entry;

            if ('' === $content) {
                // Huh, no changes.
                continue;
            }

            // Now parse all the subheadings and changes.
            while ('' !== $content) {
                $changes = [];
                $rows    = explode("\n", $content);
                foreach ($rows as $row) {
                    $row          = trim($row);
                    $row          = preg_replace('/' . $this->bullet . '/', '', $row, 1);
                    $row_segments = explode(' - ', (string) $row);
                    $significance = trim(strtolower($row_segments[0]));

                    array_push(
                        $changes,
                        [
                            'subheading'   => $is_subentry ? '' : trim($row_segments[0]),
                            'content'      => $is_subentry ? trim((string) $row) : trim($row_segments[1] ?? ''),
                            'significance' => in_array($significance, [ 'patch', 'minor', 'major' ], true) ? $significance : null,
                        ]
                    );
                }

                foreach ($changes as $change) {
                    $entry->appendChange(
                        $this->newChangeEntry(
                            [
                                'subheading'   => $change['subheading'],
                                'content'      => $change['content'],
                                'significance' => $change['significance'],
                                'timestamp'    => $entry_timestamp,
                            ]
                        )
                    );
                }
                $content = '';
            }
        }

        $ret->setEntries($entries);
        $ret->setPrologue($this->prologue);
        $ret->setEpilogue($this->getEpilogue());
        return $ret;
    }

    /**
     * Write a Changelog object to a string.
     *
     * @param Changelog $changelog Changelog object.
     * @return string
     */
    public function format(Changelog $changelog)
    {
        $ret         = '';
        $date_format = 'Y-m-d';
        $bullet      = $this->bullet;
        $indent      = str_repeat(' ', strlen($bullet));

        $prologue = trim($changelog->getPrologue());
        if ('' !== $prologue) {
            $ret .= "$prologue\n\n";
        }

        foreach ($changelog->getEntries() as $entry) {
            $version      = $entry->getVersion();
            $is_subentry  = preg_match($this->subentry_pattern, (string) $version, $subentry);
            $timestamp    = $entry->getTimestamp();
            $release_link = $this->getReleaseLink($version);

            if ($is_subentry) {
                $ret .= '###' . $subentry[1] . " \n\n";
            } else {
                $ret .= '## [' . $version . '](' . $release_link . ') - ' . $timestamp->format($date_format) . " \n\n";
            }

            $prologue = trim((string) $entry->getPrologue());

            if ('' !== $prologue) {
                $ret .= "$prologue\n\n";
            }

            foreach ($entry->getChangesBySubheading() as $changes) {
                foreach ($changes as $change) {
                    $significance    = $change->getSignificance();
                    $breaking_change = 'major' === $significance ? ' [ **BREAKING CHANGE** ]' : '';
                    $text            = trim((string) $change->getContent());
                    if ('' !== $text) {
                        $preamble = $is_subentry ? '' : $bullet . ucfirst((string) $significance) . $breaking_change . ' - ';
                        $ret     .= $preamble . str_replace("\n", "\n$indent", $text) . "\n";
                    }
                }
            }
            $ret = trim($ret) . "\n\n";
        }

        $epilogue = trim($changelog->getEpilogue());
        if ('' !== $epilogue) {
            $ret .= "$epilogue\n";
        }

        return trim($ret) . "\n";
    }
}

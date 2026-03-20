<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\URL;
use WC_Unit_Test_Case;

/**
 * A collection of tests for the filepath utility class.
 */
class URLTest extends WC_Unit_Test_Case
{
    /**
     * @testdox Test if it can be determined whether a URL is absolute or relative.
     */
    public function test_if_absolute_or_relative()
    {
        $this->assertTrue(
            (new URL('/etc/foo/bar'))->is_absolute(),
            'Correctly determines if a Unix-style path is absolute.'
        );

        $this->assertTrue(
            (new URL('c:\\Windows\Programs\Item'))->is_absolute(),
            'Correctly determines if a Windows-style path is absolute.'
        );

        $this->assertTrue(
            (new URL('wp-content/uploads/thing.pdf'))->is_relative(),
            'Correctly determines if a filepath is relative.'
        );
    }

    /**
     * @dataProvider path_expectations
     *
     * @param string $source_path Source path to test.
     * @param string $expected_resolution Expected result of the test.
     */
    public function test_path_resolution($source_path, $expected_resolution)
    {
        $this->assertEquals($expected_resolution, (new URL($source_path))->get_path());
    }

    /**
     * Expectations when requesting the path of a URL.
     *
     * @return string[][]
     */
    public function path_expectations(): array
    {
        return [
            [ '/var/foo/bar/baz/../../foobar', '/var/foo/foobar' ],
            [ '/var/foo/../../../../bazbar', '/bazbar' ],
            [ '././././.', './' ],
            [ 'empty/segments//are/stripped', 'empty/segments/are/stripped' ],
            [ '///nonempty/  /whitespace/   /is//kept', '/nonempty/  /whitespace/   /is/kept' ],
            [ 'relative/../../should/remain/relative', '../should/remain/relative' ],
            [ 'relative/../../../should/remain/relative', '../../should/remain/relative' ],
            [ 'c:\\Windows\Server\HTTP\dump.xml', 'c:/Windows/Server/HTTP/dump.xml' ],
        ];
    }

    /**
     * @dataProvider url_expectations
     *
     * @param string $source_url URL to test.
     * @param string $expected_resolution Expected result of the test.
     */
    public function test_url_resolution($source_url, $expected_resolution)
    {
        $this->assertEquals($expected_resolution, (new URL($source_url))->get_url());
    }

    /**
     * Expectations when resolving URLs.
     *
     * @return string[][]
     */
    public function url_expectations(): array
    {
        return [
            [ '/../foo/bar/baz/bazooka/../../baz', 'file:///foo/bar/baz' ],
            [ './a/b/c/./../././../b/c', 'file://a/b/c' ],
            [ 'relative/path', 'file://relative/path' ],
            [ '/absolute/path', 'file:///absolute/path' ],
            [ '/var/www/network/%2econfig', 'file:///var/www/network/%2econfig' ],
            [ '///foo', 'file:///foo' ],
            [ '~/foo.txt', 'file://~/foo.txt' ],
            [ 'baz///foo', 'file://baz/foo' ],
            [ 'file:///etc/foo/bar', 'file:///etc/foo/bar' ],
            [ 'foo://bar', 'foo://bar/' ],
            [ 'foo://bar/baz-file', 'foo://bar/baz-file' ],
            [ 'foo://bar/baz-dir/', 'foo://bar/baz-dir/' ],
            [ 'https://foo.bar/parent/.%2e/asset.txt', 'https://foo.bar/asset.txt' ],
            [ 'https://foo.bar/parent/%2E./asset.txt', 'https://foo.bar/asset.txt' ],
            [ 'https://foo.bar/parent/%2E%2e/asset.txt', 'https://foo.bar/asset.txt' ],
            [ 'https://foo.bar/parent/%2E.%2fasset.txt', 'https://foo.bar/parent/%2E.%2fasset.txt' ],
            [ 'http://localhost?../../bar', 'http://localhost/?../../bar' ],
            [ '//http.or.https/', '//http.or.https/' ],
            [ '//schemaless/with-path', '//schemaless/with-path' ],
        ];
    }

    /**
     * @dataProvider parent_url_expectations
     *
     * @param string       $source_path Path to test.
     * @param int          $parent_level Parent level to use for the test.
     * @param string|false $expectation Expected result of the test.
     */
    public function test_can_obtain_parent_url(string $source_path, int $parent_level, $expectation)
    {
        $this->assertEquals($expectation, (new URL($source_path))->get_parent_url($parent_level));
    }

    /**
     * Expectations when resolving (grand-)parent URLs.
     *
     * @return array[]
     */
    public function parent_url_expectations(): array
    {
        return [
            [ '/', 1, false ],
            [ '/', 2, false ],
            [ './', 1, 'file://../' ],
            [ '../', 1, 'file://../../' ],
            [ 'relative-file.png', 1, 'file://./' ],
            [ 'relative-file.png', 2, 'file://../' ],
            [ '/var/dev/', 1, 'file:///var/' ],
            [ '/var/../dev/./../foo/bar', 1, 'file:///foo/' ],
            [ 'https://example.com', 1, false ],
            [ 'https://example.com/foo', 1, 'https://example.com/' ],
            [ 'https://example.com/foo/bar/baz/../cat/', 2, 'https://example.com/foo/' ],
            [ 'https://example.com/foo/bar/baz/%2E%2E/dog/', 2, 'https://example.com/foo/' ],
            [ 'file://./', 1, 'file://../' ],
            [ 'file://./', 2, 'file://../../' ],
            [ 'file://../../foo', 1, 'file://../../' ],
            [ 'file://../../foo', 2, 'file://../../../' ],
            [ 'file://../../', 1, 'file://../../../' ],
            [ 'file://./../', 2, 'file://../../../' ],
        ];
    }

    /**
     * @dataProvider all_parent_url_expectations
     *
     * @param string $source_path Path to test.
     * @param array  $expectation Expected result of the test.
     */
    public function test_can_obtain_all_parent_urls(string $source_path, array $expectation)
    {
        $this->assertEquals($expectation, (new URL($source_path))->get_all_parent_urls());
    }

    /**
     * Expectations when obtaining all possible parent URLs of a given URL/path.
     *
     * @return array[]
     */
    public function all_parent_urL_expectations(): array
    {
        return [
            [
                'https://local.web/wp-content/uploads/woocommerce_uploads/pdf_bucket/secret-sauce.pdf',
                [
                    'https://local.web/wp-content/uploads/woocommerce_uploads/pdf_bucket/',
                    'https://local.web/wp-content/uploads/woocommerce_uploads/',
                    'https://local.web/wp-content/uploads/',
                    'https://local.web/wp-content/',
                    'https://local.web/',
                ],
            ],
            [
                '/srv/websites/my.wp.site/public/test-file.doc',
                [
                    'file:///srv/websites/my.wp.site/public/',
                    'file:///srv/websites/my.wp.site/',
                    'file:///srv/websites/',
                    'file:///srv/',
                    'file:///',
                ],
            ],
            [
                'C:\\Documents\\Web\\TestSite\\BackgroundTrack.mp3',
                [
                    'file://C:/Documents/Web/TestSite/',
                    'file://C:/Documents/Web/',
                    'file://C:/Documents/',
                    'file://C:/',
                ],
            ],
            [
                'file:///',
                [],
            ],
            [
                'relative/to/abspath',
                [
                    'file://relative/to/',
                    'file://relative/',
                    'file://./',
                ],
            ],
            [
                '../../some.file',
                [
                    'file://../../',
                ],
            ],
        ];
    }
}

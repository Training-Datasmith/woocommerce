<?php

/**
 * Class Aliases for graceful Backwards compatibility.
 *
 * This file is autoloaded via composer.json and maps the old namespaces to deprecation handlers.
 */
declare (strict_types=1);
use Automattic\Woo_Commerce\Admin\Features\Navigation\Removed_Deprecated;
class_alias(Removed_Deprecated::class, \Automattic\Woo_Commerce\Admin\Features\Navigation\Screen::class);
class_alias(Removed_Deprecated::class, \Automattic\Woo_Commerce\Admin\Features\Navigation\Menu::class);
class_alias(Removed_Deprecated::class, \Automattic\Woo_Commerce\Admin\Features\Navigation\Core_Menu::class);
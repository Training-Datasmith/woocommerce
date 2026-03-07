<?php

declare(strict_types=1);

/**
 * Dump and die.
 *
 * @param mixed $x The variable to dump.
 *
 * @return void
 */
function dd($x)
{
    print_r($x);
    exit;
}

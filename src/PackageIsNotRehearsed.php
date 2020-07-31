<?php

declare(strict_types=1);

namespace noximo\Rehearsal;

use Exception;

final class PackageIsNotRehearsed extends Exception
{
    public static function incorrectOperation(): PackageIsNotRehearsed
    {
        return new self('Rehearsal cannot act on this event');
    }

    public static function incorrectPackage(): PackageIsNotRehearsed
    {
        return new self("This package is not rehearsed by Rehearsal");
    }
}

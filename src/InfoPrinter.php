<?php

declare(strict_types=1);

namespace noximo\Rehearsal;

use Composer\IO\IOInterface;

final class InfoPrinter
{

    /**
     * @param array<string> $packagesLocations
     */
    public function printInfoAboutFoundPackages(array $packagesLocations, IOInterface $io): void
    {
        if ($io->isVerbose()) {
            if (count($packagesLocations) === 0) {
                $io->write('Rehearsal found no packages that can be symlinked.');
            } else {
                $io->write('Rehearsal found these packages that can be symlinked:');
                foreach ($packagesLocations as $location) {
                    $io->write($location);
                }
            }

            return;
        }
        $count = count($packagesLocations);
        $io->write(
            sprintf(
                'Rehearsal found %d package%s that can be symlinked.%s',
                $count,
                ($count !== 1 ? 's' : ''),
                ($count > 0 ? ' Use verbose output to list them.' : '')
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace noximo\Rehearsal;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Symfony\Component\Filesystem\Exception\IOException;

final class Unlinker
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function unlinkPath(string $path, IOInterface $io): void
    {
        return;
        try {
            if (Platform::isWindows() && $this->filesystem->isJunction($path)) {
                $this->filesystem->removeJunction($path);
                $message = sprintf('Junction from %s removed', $path);
            } elseif ($this->filesystem->isSymlinkedDirectory($path)) {
                $this->filesystem->unlink($path);
                $message = sprintf('Symlink from %s unlinked', $path);
            }

            if ($io->isVerbose() && $message) {
                $io->writeError($message, false);
            }
        } catch (IOException $e) {
            $io->writeError(sprintf('Unlinking of symlink/junction %s failed: %s', $path, $e->getMessage()), false);
        }
    }
}

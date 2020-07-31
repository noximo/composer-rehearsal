<?php

namespace noximo\Rehearsal;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\PathRepository;
use Composer\Repository\RepositoryManager;
use Composer\Script\ScriptEvents;
use Seld\JsonLint\ParsingException;

final class RehearsalPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer|null
     */
    private $composer;
    /**
     * @var IOInterface|null
     */
    private $io;
    /**
     * @var PackageLoader
     */
    private $packageLoader;
    private $packages;
    /**
     * @var InfoPrinter
     */
    private $infoPrinter;
    /**
     * @var Unlinker
     */
    private $unlinker;

    /**
     * RehearsalPlugin constructor.
     */
    public function __construct()
    {
        $this->packageLoader = new PackageLoader();
        $this->infoPrinter = new InfoPrinter();
        $this->unlinker = new Unlinker();
    }

    /**
     * @throws ParsingException
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->packages = $this->packageLoader->findPackages(getcwd());

        $this->infoPrinter->printInfoAboutFoundPackages($this->packages, $io);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => 'registerPaths',
            ScriptEvents::PRE_UPDATE_CMD => 'registerPaths',
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'packageUninstall',
            PackageEvents::PRE_PACKAGE_INSTALL => 'packageInstall',
            PackageEvents::PRE_PACKAGE_UPDATE => 'packageInstall',
        ];
    }

    public function registerPaths(): void
    {
        $repositoryManager = $this->composer->getRepositoryManager();
        $composerConfig = $this->composer->getConfig();
        foreach ($this->packages as $path) {
            $this->unlinker->unlinkPath($path, $this->io);
            $this->registerPathRepository($path, $repositoryManager, $composerConfig);
        }
    }

    public function packageInstall(PackageEvent $event): void
    {
        $package = $this->getCorrectTargetPackage($event);

        if (!$this->isRehearsedPackage($package)) {
            return;
        }

        $this->unlinkPackage($package);
        $this->io->writeError($package->getName());
        // $package->replaceVersion('dev-master', 'dev-master');
    }

    public function packageUninstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        assert($operation instanceof UninstallOperation);
        $package = $operation->getPackage();

        if (!$this->isRehearsedPackage($package)) {
            return;
        }

        $this->unlinkPackage($package);
        $this->io->writeError($package->getName());
    }

    private function registerPathRepository(string $path, RepositoryManager $repositoryManager, Config $composerConfig): void
    {
        $repositoryManager->prependRepository(
            new PathRepository(
                ['url' => $path, 'symlink' => true],
                $this->io,
                $composerConfig
            )
        );
    }

    private function getPackagePath(PackageInterface $package): string
    {
        return getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $package->getName();
    }

    private function isRehearsedPackage(PackageInterface $package): bool
    {
        $path = $this->getPackagePath($package);

        return array_key_exists($path, $this->packages);
    }

    private function unlinkPackage(PackageInterface $package): void
    {
        $path = $this->getPackagePath($package);

        $this->unlinker->unlinkPath($path, $this->io);
    }

    /**
     * @param PackageEvent $event
     * @return PackageInterface
     */
    private function getCorrectTargetPackage(PackageEvent $event): PackageInterface
    {
        $operation = $event->getOperation();
        if ($operation instanceof UpdateOperation) {
            return $operation->getTargetPackage();
        }

        return $operation->getPackage();
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }
}

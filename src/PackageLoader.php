<?php

declare(strict_types=1);

namespace noximo\Rehearsal;

use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Semver\VersionParser;
use Seld\JsonLint\ParsingException;
use UnexpectedValueException;

final class PackageLoader
{
    /**
     * @var ArrayLoader
     */
    private $arrayLoader;

    /**
     * PackageLoader constructor.
     */
    public function __construct()
    {
        $this->arrayLoader = new ArrayLoader(new VersionParser());
    }

    /**
     * @param string $targetDir
     * @return array
     * @throws ParsingException
     */
    public function findPackages(string $targetDir): array
    {
        $rehearsalJson = realpath($targetDir) . DIRECTORY_SEPARATOR . 'rehearsal.json';

        if (!file_exists($rehearsalJson)) {
            return [];
        }

        $json = file_get_contents($rehearsalJson);
        $paths = JsonFile::parseJson($json, $rehearsalJson);

        return $this->findPackagesByComposerJson($paths);
    }

    /**
     * @param array<string> $paths
     * @return array
     */
    private function findPackagesByComposerJson(array $paths): array
    {
        $packagesLocations = [];
        foreach ($paths as $path) {
            $path = realpath($path) . DIRECTORY_SEPARATOR;
            if (file_exists($path . 'composer.json')) {
                $packagesLocations[] = $path;
                continue;
            }

            $pattern = $path . '*';
            foreach (glob($pattern, GLOB_MARK | GLOB_ONLYDIR) as $subdirectory) {
                $composerJson = $subdirectory . 'composer.json';
                if (file_exists($composerJson)) {
                    try {
                        $json = file_get_contents($composerJson);
                        $config = JsonFile::parseJson($json, $composerJson);
                        $this->arrayLoader->load($config);

                        $packagesLocations[] = realpath($subdirectory) . DIRECTORY_SEPARATOR;
                    } catch (UnexpectedValueException|ParsingException $e) {
                        if (strpos($e->getMessage(), 'has no version defined') !== false) {
                            $packagesLocations[] = realpath($subdirectory) . DIRECTORY_SEPARATOR;
                        }
                    }
                }
            }
        }

        return $packagesLocations;
    }
}

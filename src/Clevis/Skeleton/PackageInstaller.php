<?php

namespace Clevis\Skeleton;

use DirectoryIterator;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use InvalidArgumentException;


/**
 * Instalátor pro balíčky Skeletonu21
 */
class PackageInstaller extends LibraryInstaller
{

	/** @var string instalační adresář balíčku */
	public $baseDir;

	/** @var string kořenový adresář aplikace */
	public $rootDir;


	/**
	 * Kontrola podpory
	 */
	public function supports($packageType)
	{
		return $packageType === 'clevis-skeleton-package';
	}

	/**
	 * Instaluje balíček
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::install($repo, $package);

		$this->initDirs($package);

		$this->installTemplates();
		$this->installAssets();
		$this->installMigrations();
		$this->installTests();
	}

	/**
	 * Aktualizuje balíček
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
		$this->initDirs($initial);

		$this->uninstallTemplates();
		$this->uninstallAssets();
		$this->uninstallTests();

		parent::update($repo, $initial, $target);

		$this->initDirs($target);

		$this->installTemplates();
		$this->installAssets();
		$this->installMigrations();
		$this->installTests();
	}

	/**
	 * Odinstaluje balíček
	 */
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		$this->initDirs($package);

		$this->uninstallTemplates();
		$this->uninstallAssets();
		$this->uninstallTests();

		parent::uninstall($repo, $package);
	}

	/**
	 * Instaluje šablony
	 */
	private function installTemplates()
	{
		if (is_readable($this->baseDir . '/templates'))
		{
			$this->copyFiles($this->baseDir . '/templates', array($this, 'mapTemplatePath'));
		}
	}

	/**
	 * Odinstaluje šablony
	 */
	private function uninstallTemplates()
	{
		if (is_readable($this->baseDir . '/templates'))
		{
			$this->deleteFiles($this->baseDir . '/templates', array($this, 'mapTemplatePath'));
		}
	}

	/**
	 * Instaluje assety
	 */
	private function installAssets()
	{
		if (is_readable($this->baseDir . '/www'))
		{
			$this->copyFiles($this->baseDir . '/www', array(
				'#^(.*)/www/(.*)$#', $this->rootDir . '/www/$2'
			));
		}
	}

	/**
	 * Odinstaluje assety
	 */
	private function uninstallAssets()
	{
		if (is_readable($this->baseDir . '/www'))
		{
			$this->deleteFiles($this->baseDir . '/www', array(
				'#^(.*)/www/(.*)$#', $this->rootDir . '/www/$2'
			));
		}
	}

	/**
	 * Instaluje migrace
	 */
	private function installMigrations()
	{
		if (is_readable($this->baseDir . '/migrations/struct'))
		{
			$this->copyFiles($this->baseDir . '/migrations/struct', $this->rootDir . '/migrations/struct', TRUE);
		}
		if (is_readable($this->baseDir . '/migrations/data'))
		{
			$this->copyFiles($this->baseDir . '/migrations/data', $this->rootDir . '/migrations/struct', TRUE);
		}
	}

	/**
	 * Instaluje testy
	 */
	private function installTests()
	{
		if (is_readable($this->baseDir . '/tests'))
		{
			$this->copyFiles($this->baseDir . '/tests', array($this, 'mapTemplatePath'));
		}
	}

	/**
	 * Odinstaluje testy
	 */
	private	function uninstallTests()
	{
		if (is_readable($this->baseDir . '/templates'))
		{
			$this->deleteFiles($this->baseDir . '/templates', array($this, 'mapTemplatePath'));
		}
	}

	/**
	 * Inicializace instalačních adresářů balíčku
	 */
	private function initDirs(PackageInterface $package)
	{
		$this->baseDir = $this->getPackageBasePath($package);
		// todo: may be a problem if vendor dir is configured to be a subdirectory (eg. `libs/composer`)
		$this->rootDir = substr($this->vendorDir, strpos($this->vendorDir, '/') + 1);
	}

	/**
	 * Kopíruje soubory z adresáře pomocí mapování adres
	 *
	 * @param string
	 * @param callable|string|string[]
	 * @param bool
	 */
	private function copyFiles($sourceDir, $mapping, $onceOnly = FALSE)
	{
		$files = new DirectoryIterator($sourceDir);
		/** @var DirectoryIterator $file */
		foreach ($files as $file)
		{
			if ($file->isDot())
			{
				continue;
			}
			elseif ($file->isDir())
			{
				$this->copyFiles($sourceDir . '/' . $file->getFilename(), $mapping, $onceOnly);
			}
			else
			{
				$targetPath = $this->getMappedPath($file->getPathname(), $mapping);
				if ($onceOnly && file_exists($targetPath))
				{
					continue;
				}
				$this->filesystem->ensureDirectoryExists(dirname($targetPath));
				copy($file->getPathname(), $targetPath);
			}
		}
	}

	/**
	 * Maže soubory pomocí mapování adres
	 *
	 * @param string
	 * @param callable|string|string[]
	 * @return string|NULL
	 */
	private function deleteFiles($sourceDir, $mapping)
	{
		$files = new DirectoryIterator($sourceDir);
		/** @var DirectoryIterator $file */
		foreach ($files as $file)
		{
			if ($file->isDot())
			{
				continue;
			}
			elseif ($file->isDir())
			{
				$path = $this->deleteFiles($sourceDir . '/' . $file->getFilename(), $mapping);
				if ($path)
				{
					// odebírá adresář, pokud je prázdný
					@rmdir(dirname($path));
				}
			}
			else
			{
				$targetPath = $this->getMappedPath($file->getPathname(), $mapping);
				unlink($targetPath);
			}
		}

		return isset($targetPath) ? $targetPath : NULL;
	}

	/**
	 * Mapuje adresu souboru na jinou
	 *
	 * @param string
	 * @param callable|string|string[]
	 * @return string
	 */
	private function getMappedPath($path, $mapping)
	{
		if (is_string($mapping))
		{
			$parts = explode('/', $path);
			return $mapping . end($parts);
		}
		elseif (is_array($mapping))
		{
			return preg_replace($mapping[0], $mapping[0], $path);
		}
		elseif (is_callable($mapping))
		{
			return $mapping($path);
		}
		else
		{
			throw new InvalidArgumentException("Mapping can be only callable, string or array.");
		}
	}

	/**
	 * Překládá cestu k souborům šablon
	 *
	 * @param string
	 * @return string
	 */
	public function mapTemplatePath($path) {
		// vendor/{Vendor}/{Package}/src/{XyzModule}/templates/{Presenter}/default.latte
		if (preg_match('#((?:/[^/]+Module)*)/templates/([^/]+)/(.*)#', $path, $m))
		{
			return empty($m[1])
				? $this->rootDir . '/app/templates/' . $m[2] . '/package/' . $m[3]
				// app/{XyzModule}/templates/{Presenter}/package/default.latte
				: $this->rootDir . '/app' . $m[1] . '/templates/' . $m[2] . '/package/' . $m[3];
		}
		else
		{
			throw new InvalidArgumentException("Cannot map template file path '$path'.");
		}
	}

	/**
	 * Překládá cestu k souborům testů
	 *
	 * @param string
	 * @return string
	 */
	public function mapTestsPath($path) {
		// vendor/{Vendor}/{Package}/tests/cases/Unit/{Sub/Dir}/Test.php
		if (preg_match('#tests/cases/([^/]+)((?:/[^/]+)*)/(.*)#', $path, $m))
		{
			return empty($m[2])
				? $this->rootDir . '/tests/cases/' . $m[1] . '/' . $m[3]
				// tests/cases/Unit/{Package}/{Sub/Dir}/Test.php
				: $this->rootDir . '/tests/cases/' . $m[1] . $m[2] . '/' . $m[3];
		}
		else
		{
			throw new InvalidArgumentException("Cannot map template file path '$path'.");
		}
	}

}

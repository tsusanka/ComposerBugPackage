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

	/** @var string[] seznam nainstalovaných souborů */
	private $files;


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

		$this->files = array();
		$this->initDirs($package);

		$this->installTemplates();
		$this->installAssets();
		$this->installMigrations();
		$this->installTests();

		$this->logFiles($package);
	}

	/**
	 * Aktualizuje balíček
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
		$this->initDirs($initial);

		$this->removeFiles($initial);
		$this->uninstallTemplates();
		$this->uninstallAssets();
		$this->uninstallTests();

		parent::update($repo, $initial, $target);

		$this->files = array();
		$this->initDirs($target);

		$this->installTemplates();
		$this->installAssets();
		$this->installMigrations();
		$this->installTests();

		$this->logFiles($target);
	}

	/**
	 * Odinstaluje balíček
	 */
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		$this->initDirs($package);

		$this->removeFiles($package);
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
		if (is_readable($this->baseDir . '/app'))
		{
			$this->copyFiles($this->baseDir . '/app', array($this, 'mapTemplatePath'));
		}
	}

	/**
	 * Odinstaluje šablony
	 */
	private function uninstallTemplates()
	{
		if (is_readable($this->baseDir . '/app'))
		{
			$this->deleteFiles($this->baseDir . '/app', array($this, 'mapTemplatePath'));
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
		if (is_readable($this->baseDir . '/migrations'))
		{
			$this->copyFiles($this->baseDir . '/migrations', $this->rootDir . '/migrations/', TRUE);
		}
	}

	/**
	 * Instaluje testy
	 */
	private function installTests()
	{
		if (is_readable($this->baseDir . '/tests/cases'))
		{
			$this->copyFiles($this->baseDir . '/tests/cases', array($this, 'mapTestsPath'));
		}
	}

	/**
	 * Odinstaluje testy
	 */
	private	function uninstallTests()
	{
		if (is_readable($this->baseDir . '/tests/cases'))
		{
			$this->deleteFiles($this->baseDir . '/tests/cases', array($this, 'mapTestsPath'));
		}
	}

	/**
	 * Inicializace instalačních adresářů balíčku
	 */
	private function initDirs(PackageInterface $package)
	{
		$this->baseDir = $this->getPackageBasePath($package);
		$this->rootDir = dirname($this->vendorDir);
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
		//echo "\nCOPY: " . substr($sourceDir, 40) . ":\n";
		$files = new DirectoryIterator($sourceDir);
		/** @var DirectoryIterator $file */
		foreach ($files as $file)
		{
			$name = $file->getFilename();
			if ($name[0] === '.')
			{
				continue;
			}
			elseif ($file->isDir())
			{
				$this->copyFiles($sourceDir . '/' . $name, $mapping, $onceOnly);
			}
			else
			{
				$targetPath = $this->getMappedPath($file->getPathname(), $mapping);
				if (!$targetPath || $onceOnly && file_exists($targetPath))
				{
					//echo "- skip: " . substr($file->getPathname(), 40) . "\n";
					continue;
				}
				$this->filesystem->ensureDirectoryExists(dirname($targetPath));
				//echo "- copy: " . substr($file->getPathname(), 40) . ' --> ' . substr($targetPath, 40) . "\n";
				copy($file->getPathname(), $targetPath);
				if (!$onceOnly)
				{
					$this->files[] = substr($targetPath, strlen($this->rootDir) + 1);
				}
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
		//echo "\nDELETE: " . substr($sourceDir, 40) . ":\n";
		$files = new DirectoryIterator($sourceDir);
		/** @var DirectoryIterator $file */
		foreach ($files as $file)
		{
			$name = $file->getFilename();
			if ($name[0] === '.')
			{
				continue;
			}
			elseif ($file->isDir())
			{
				$path = $this->deleteFiles($sourceDir . '/' . $name, $mapping);
				if ($path)
				{
					// odebírá adresář, pokud je prázdný
					@rmdir(dirname($path));
				}
			}
			else
			{
				$targetPath = $this->getMappedPath($file->getPathname(), $mapping);
				if (!$targetPath)
				{
					//echo "- skip: " . substr($file->getPathname(), 40) . "\n";
					continue;
				}
				//echo "- delete: " . substr($targetPath, 40) . "\n";
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
		elseif (is_array($mapping) && !is_object($mapping[0]))
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
		$path = str_replace('\\', '/', $path);

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
			return FALSE;
		}
	}

	/**
	 * Překládá cestu k souborům testů
	 *
	 * @param string
	 * @return string
	 */
	public function mapTestsPath($path) {
		$path = str_replace('\\', '/', $path);

		// vendor/{Vendor}/{Package}/tests/cases/Unit/{Sub/Dir}/Test.php
		if (preg_match('#tests/cases/(.*)#', $path, $m))
		{
			// tests/cases/Unit/{Sub/Dir}/Test.php
			return $this->rootDir . '/tests/cases/' . $m[1];
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Ukládá seznam rozkopírovaných souborů (pro odinstalaci)
	 */
	private function logFiles(PackageInterface $package)
	{
		$dir = dirname(dirname($this->baseDir)) . '/composer/' . $package->getName();
		$this->filesystem->ensureDirectoryExists($dir);

		file_put_contents($dir . '/installed.json', json_encode($this->files));
	}

	/**
	 * Odinstaluje soubory rozkopírované do aplikace (podle logu z instalace)
	 */
	private function removeFiles(PackageInterface $package)
	{
		$dir = dirname(dirname($this->baseDir)) . '/composer/' . $package->getName();

		$files = json_decode(file_get_contents($dir . '/installed.json'));
		if (!$files) return;

		foreach ($files as $file)
		{
			$path = $this->rootDir . '/' . $file;
			if (!@unlink($path))
			{
				if (file_exists($path))
				{
					trigger_error('Cannot remove file ' . $path, E_USER_NOTICE);
				}
			}
		}

		$this->filesystem->removeDirectory($dir);
		@rmdir(dirname($dir));
	}

}

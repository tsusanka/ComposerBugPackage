<?php

require '../vendor/autoload.php';
require '../vendor/nette/tester/Tester/bootstrap.php';

use Tester\Assert;
use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Clevis\Skeleton\PackageInstaller;

$composer = new Composer;
$composer->setConfig(new Config);

$pi = new PackageInstaller(new NullIO, $composer);
$pi->baseDir = $base = '/httpd/app/vendor/Vendor/Package';
$pi->rootDir = $root = '/httpd';

// templates
Assert::same($root . '/app/templates/Presenter/package/default.latte',
	$pi->mapTemplatePath($base . '/src/templates/Presenter/default.latte'));

Assert::same($root . '/app/XModule/templates/Presenter/package/default.latte',
	$pi->mapTemplatePath($base . '/src/XModule/templates/Presenter/default.latte'));

Assert::same($root . '/app/XModule/YModule/templates/Presenter/package/default.latte',
	$pi->mapTemplatePath($base . '/src/XModule/YModule/templates/Presenter/default.latte'));

// tests
Assert::same($root . '/tests/cases/Unit/Test.php',
	$pi->mapTestsPath($base . '/tests/cases/Unit/Test.php'));

Assert::same($root . '/tests/cases/Unit/Dir/Test.php',
	$pi->mapTestsPath($base . '/tests/cases/Unit/Dir/Test.php'));

Assert::same($root . '/tests/cases/Unit/Sub/Dir/Test.php',
	$pi->mapTestsPath($base . '/tests/cases/Unit/Sub/Dir/Test.php'));


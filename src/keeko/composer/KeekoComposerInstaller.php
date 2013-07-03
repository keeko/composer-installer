<?php

namespace keeko\composer;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\IO\IOInterface;
use Composer\Composer;

class KeekoComposerInstaller extends \Composer\Installer\LibraryInstaller {
	
	private $types = array (
		'keeko-core',
		'keeko-app',
		'keeko-module' 
	);
	
	public function __construct(IOInterface $io, Composer $composer, $type = 'library') {
		parent::__construct($io, $composer, $type);
	}
	
	/**
	 *
	 * @param PackageInterface $package        	
	 *
	 * @return string a path relative to the root of the composer.json that is being installed.
	 */
	public function getInstallPath(PackageInterface $package) {
		
		$type = $package->getType();
		
		if ($type === 'keeko-core') {
			return 'core';
		}
		
		$folderMappings = array(
			'keeko-app' => 'apps',
			'keeko-modules' => 'modules'
		);
		
		return $folderMappings[$type].'/'.$package->getName();
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
		$local = $this->getLocalRepositoryPath();
		$installed = false;
		
		if ($local !== null) {
			$path = $local . DIRECTORY_SEPARATOR . $package->getName();
			if (!$this->filesystem->isAbsolutePath($path)) {
				$path = $this->filesystem->normalizePath(getcwd() . '/' . $path);
			}
			
			if (file_exists($path)) {
				try {
					$this->symlink($path, $this->getInstallPath($package));
					$installed = true;
				} catch(IOException $e) {
					$installed = false;
				}
			}
		}
		
		if (!$installed) {
			parent::install($repo, $package);
		}
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target) {
		if (!$repo->hasPackage($initial)) {
			throw new \InvalidArgumentException('Package is not installed: '.$initial);
		}
		
		$path = $this->getInstallPath($initial);
		
		if (!is_link($path)) {
			parent::update($repo, $initial, $target);
		}
	}
	
	private function getLocalRepositoryPath() {
		$root = $this->composer->getPackage();
		$extra = $root->getExtra();
		
		if (array_key_exists('local', $extra)) {
			return $extra['local'];
		}
		
		return null;
	}
	
// 	/**
// 	 * Returns the root installation path for templates.
// 	 *
// 	 * @return string a path relative to the root of the composer.json that is being installed where the templates
// 	 *         are stored.
// 	 */
// 	protected function getTemplateRootPath() {
// 		return (file_exists ( $this->vendorDir . '/phpdocumentor/phpdocumentor/composer.json' )) ? $this->vendorDir . '/phpdocumentor/phpdocumentor/data/templates' : 'data/templates';
// 	}
	
	/**
	 * {@inheritDoc}
	 */
	public function supports($packageType) {
		return (bool) in_array($packageType, $this->types);
	}
	
	

	/**
	 * A lightweight method of the symlink method in Symfony\Filesystem
	 * 
	 * Creates a symbolic link or copy a directory.
	 *
	 * @param string $originDir The origin directory path
	 * @param string $targetDir The symbolic link name
	 * @param Boolean $copyOnWindows Whether to copy files if on Windows
	 *
	 * @throws \Exception When symlink fails
	 */
	private function symlink($originDir, $targetDir) {
		@mkdir(dirname($targetDir), 0777, true);
	
		$ok = false;
		if (is_link($targetDir)) {
			if (readlink($targetDir) != $originDir) {
				$this->filesystem->remove($targetDir);
			} else {
				$ok = true;
			}
		}
	
		if (!$ok) {
			if (true !== @symlink($originDir, $targetDir)) {
				$report = error_get_last();
				if (is_array($report)) {
					if (defined('PHP_WINDOWS_VERSION_MAJOR') && false !== strpos($report['message'], 'error code(1314)')) {
						throw new \Exception('Unable to create symlink due to error code 1314: \'A required privilege is not held by the client\'. Do you have the required Administrator-rights?');
					}
				}
				throw new \Exception(sprintf('Failed to create symbolic link from %s to %s', $originDir, $targetDir));
			}
		}
	}
}
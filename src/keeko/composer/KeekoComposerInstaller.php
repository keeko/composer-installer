<?php

namespace keeko\composer;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class KeekoComposerInstaller extends \Composer\Installer\LibraryInstaller {
	
	private $types = array (
		'keeko-core',
		'keeko-app',
		'keeko-module' 
	);
	
	protected $symFilesystem;
	
	public function __construct(IOInterface $io, Composer $composer, $type = 'library') {
		parent::__construct($io, $composer, $type);
		
		$this->symFilesystem = new Filesystem();
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
			
			if (file_exists($path)) {
				try {
					$this->symFilesystem->symlink($path, $this->getInstallPath($package));
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
}
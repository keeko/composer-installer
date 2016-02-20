<?php
namespace keeko\composer;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;

class KeekoComposerInstaller extends LibraryInstaller {
	
	/** @var RootPackageInterface */
	private $root;
	
	/** @var array */
	private $types = ['keeko-framework', 'keeko-app', 'keeko-module', 'keeko-design'];
	
	public function __construct(IOInterface $io, Composer $composer, $type = 'library') {
		parent::__construct($io, $composer, $type);
		$this->root = $composer->getPackage();
	}
	
	/**
	 *
	 * @param PackageInterface $package        	
	 *
	 * @return string a path relative to the root of the composer.json that is being installed.
	 */
	public function getInstallPath(PackageInterface $package) {
		// custom install path only when it is the keeko/keeko package
		if ($this->root->getName() == 'keeko/keeko') {
			return 'packages/'.$package->getName();
		}
		
		// ... anyway return the default
		return parent::getInstallPath($package);
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports($packageType) {
		return (bool) in_array($packageType, $this->types);
	}	

}

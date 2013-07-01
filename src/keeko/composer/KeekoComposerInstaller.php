<?php

namespace keeko\composer;

use Composer\Package\PackageInterface;

class KeekoComposerInstaller extends \Composer\Installer\LibraryInstaller {
	
	private $types = array (
		'keeko-core',
		'keeko-app',
		'keeko-module' 
	);
	
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
		
		return $folderMappings[$type];
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
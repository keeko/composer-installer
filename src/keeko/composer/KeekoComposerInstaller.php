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
		'keeko-module',
		'keeko-design'
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

		return $this->getPackageDir($type) .'/'.$package->getName();
	}
	
	private function getPackageDir($type) {
		return str_replace('keeko-', '', $type) . 's';
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function installCode(PackageInterface $package) {
		$installPath = $this->getInstallPath($package);
		$publicPath = $this->getPublicPath();
		$local = $this->getLocalRepositoryPath();
		$installed = false;
		
		if ($local !== null) {
			$path = $local . DIRECTORY_SEPARATOR . $package->getName();
			if (!$this->filesystem->isAbsolutePath($path)) {
				$path = $this->filesystem->normalizePath(getcwd() . '/' . $path);
			}
			
			if (file_exists($path)) {
				try {
					$this->symlink($path, $installPath);
					$installed = true;
				} catch(IOException $e) {
					$installed = false;
				}
			}
		}
		
		if (!$installed) {
			parent::installCode($package);
		}
		
		// symlink package public folder to keeko's public folder
		$type = $package->getType(); 
		if ($type !== 'keeko-core') {
			$packagePublicPath = $this->filesystem->normalizePath($installPath .'/public');
			if (!$this->filesystem->isAbsolutePath($packagePublicPath)) {
				$packagePublicPath = $this->filesystem->normalizePath(getcwd() . '/' . $packagePublicPath);
			}
			
			if (file_exists($packagePublicPath) && file_exists($publicPath)) {
				$target = $this->filesystem->normalizePath($publicPath . '/' .  $this->getPackageDir($type) . '/' . $package->getName());
				$this->filesystem->ensureDirectoryExists(dirname($target));
				$this->symlink($packagePublicPath, $target);
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function removeCode(PackageInterface $package) {
		$publicPath = $this->getPublicPath();
		$installPath = $this->getInstallPath($package);
        
		if (is_link($installPath)) {
			unlink($installPath);
		} else {
			parent::removeCode($package);
		}
		
		// remove symlink package public folder to keeko's public folder
		$type = $package->getType();
		if ($type !== 'keeko-core') {
			$target = $this->filesystem->normalizePath($publicPath . '/' .  $this->getPackageDir($type) . '/' . $package->getName());
			
			if (is_link($target)) {
				unlink($target);
			}
			
			// remove parent if empty
			$parent = dirname($target);
			if (count(scandir($parent)) == 2) {
				$this->filesystem->removeDirectoryPhp($parent);
			}
		}
	}
	
	private function getPublicPath() {
		return $this->filesystem->normalizePath(getcwd() . '/public/_keeko');
	}

	
	/**
	 * {@inheritDoc}
	 */
	protected function updateCode(PackageInterface $initial, PackageInterface $target) {
		$path = $this->getInstallPath($initial);
		
		if (!is_link($path)) {
			parent::updateCode($initial, $target);
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
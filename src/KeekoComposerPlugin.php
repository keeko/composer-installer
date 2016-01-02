<?php
namespace keeko\composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class KeekoComposerPlugin implements PluginInterface {
	
	public function activate(Composer $composer, IOInterface $io) {
		$installer = new KeekoComposerInstaller($io, $composer);
		$composer->getInstallationManager()->addInstaller($installer);
	}
	
}
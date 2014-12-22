<?php
namespace keeko\composer;

use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\Composer;

class KeekoComposerPlugin implements PluginInterface {
	
	public function activate(Composer $composer, IOInterface $io) {
		$installer = new KeekoComposerInstaller($io, $composer);
		$composer->getInstallationManager()->addInstaller($installer);
	}
	
}
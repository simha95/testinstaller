<?php
/*
 * AssetsInstallerPlugin.php
 *
 * @(#) $Id: AssetsInstallerPlugin.php,v 1.2 2014/01/06 09:12:53 mlemos Exp $
 *
 */

namespace mlemos\Composer;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreFileDownloadEvent;

class AssetsInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
	protected $composer;
	protected $io;
	private $authentication;

	public function activate(Composer $composer, IOInterface $io)
	{
		$this->composer = $composer;
		$this->io = $io;
		$installer = new AssetsInstaller($io, $composer);
		$package = $composer->getPackage();
		$extra = $package->getExtra()
		if(IsSet($extra['assets']))
			$installer->setAssets($extra['assets']);
		$composer->getInstallationManager()->addInstaller($installer);
	}

	public static function getSubscribedEvents()
	{
		return array(
			PluginEvents::PRE_FILE_DOWNLOAD => array(
				array('onPreFileDownload', 0)
			),
		);
	}

	public function onPreFileDownload(PreFileDownloadEvent $event)
	{
		
	}
};

?>
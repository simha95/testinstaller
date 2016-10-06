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
		$extra = $package->getExtra();
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
		if(!IsSet($this->authentication))
		{
			$this->authentication = array();
			$fileName = 'auth.json';
			if(file_exists($fileName))
			{
				if($this->io->isDebug())
					$this->io->write('Reading autentication configuration from '.$fileName);
				$json = file_get_contents($fileName);
				$auth = json_decode($json);
				if(GetType($auth) === 'object'
				&& IsSet($auth->config)
				&& GetType($auth->config) === 'object'
				&& IsSet($auth->config->{'basic-auth'})
				&& GetType($auth->config->{'basic-auth'}) === 'object')
				{
					foreach($auth->config->{'basic-auth'} as $url => $credentials)
					{
						$host = parse_url($url, PHP_URL_HOST);
						if(!IsSet($host))
							$host = $url;
						if(!IsSet($credentials->username))
						{
							if($this->io->isDebug())
								$this->io->write('The autentication credentials for '.$host.' are missing the username');
						}
						elseif(!IsSet($credentials->password))
						{
							if($this->io->isDebug())
								$this->io->write('The autentication credentials for '.$host.' are missing the password');
						}
						else
						{
							$this->authentication[$host] = array(
								'username'=>$credentials->username,
								'password'=>$credentials->password
							);
						}
					}
				}
				elseif($this->io->isDebug())
					$this->io->write('The autentication definitions in the configuration file '.$fileName.' are not valid');
			}
		}
		$host = parse_url($event->getProcessedUrl(), PHP_URL_HOST);
		if(IsSet($this->authentication[$host]))
			$this->io->setAuthentication($host, $this->authentication[$host]['username'], $this->authentication[$host]['password']);
	}
};

?>
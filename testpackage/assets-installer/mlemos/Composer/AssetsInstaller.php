<?php
/*
 * AssetsInstaller.php
 *
 * @(#) $Id: AssetsInstaller.php,v 1.3 2014/01/07 10:14:45 mlemos Exp $
 *
 */
 
namespace mlemos\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class AssetsInstaller extends \Composer\Installer\LibraryInstaller
{
	private $assets = array();

	public function __construct(IOInterface $io, Composer $composer)
	{
		parent::__construct($io, $composer, 'assets');
	}

	private function makeTargetDirectory($target)
	{
		if(!file_exists($target))
		{
			$parent = dirname($target);
			if($parent !== '.'
			&& $parent !== '')
				$this->makeTargetDirectory($parent);
			if($this->io->isDebug())
				$this->io->write('Creating target directory '.$target);
			mkdir($target);
		}
	}

	public function installAction(PackageInterface $package, $action)
	{
		switch($action['type'])
		{
			case 'copy':
				if(!IsSet($action['target']))
					throw new \InvalidArgumentException('it was not specified the target of the copy action for the package '.$package->getName());
				$target = $action['target'];
				if(!IsSet($action['pattern']))
					throw new \InvalidArgumentException('it was not specified the pattern of the files of the copy action for the package '.$package->getName());
				$pattern = $action['pattern'];
				$this->makeTargetDirectory($target);
				if(substr($target, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR)
					$target .= DIRECTORY_SEPARATOR;
				$files = 0;
				foreach(new \RecursiveDirectoryIterator($this->getInstallPath($package), \FilesystemIterator::SKIP_DOTS) as $entry)
				{
					$name = $entry->getBasename();
					if($entry->getType() === 'file'
					&& preg_match('/'.$pattern.'/', $name))
					{
						$targetFile = $target.$name;
						if($this->io->isDebug())
							$this->io->write('Copying asset file to '.$targetFile);
						copy($entry->getRealPath(), $targetFile);
						++$files;
					}
				}
				if($files === 0)
					throw new \InvalidArgumentException('no files were copied with the pattern '.$pattern.' for the package '.$package->getName());
				return true;
		}
		return false;
	}

	private function installActions(PackageInterface $package, $actions)
	{
		foreach($actions as $action)
		{
			if(IsSet($action['js-target']))
			{
				if(!IsSet($action['type']))
					$action['type'] = 'copy';
				$action['target'] = $action['js-target'];
				if(!IsSet($action['pattern']))
					$action['pattern'] = '\\.js$';
			}
			elseif(IsSet($action['css-target']))
			{
				if(!IsSet($action['type']))
					$action['type'] = 'copy';
				$action['target'] = $action['css-target'];
				if(!IsSet($action['pattern']))
					$action['pattern'] = '\\.css$';
			}
			elseif(IsSet($action['image-target']))
			{
				if(!IsSet($action['type']))
					$action['type'] = 'copy';
				$action['target'] = $action['image-target'];
				if(!IsSet($action['pattern']))
					$action['pattern'] = '((jpg)|(png)|(gif))$';
			}
			if(!IsSet($action['type']))
				throw new \InvalidArgumentException('it was not specified the type of installation action for the package '.$package->getName());
			if(!$this->installAction($package, $action))
				throw new \InvalidArgumentException($action['type'].' is not a supported type of installation action for the package '.$package->getName());
		}
	}

	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::install($repo, $package);
		$name = $package->getName();
		if(IsSet($this->assets['packages'][$name]))
		{
			$assetActions = $this->assets['packages'][$name];
			if($this->io->isDebug())
				$this->io->write('Executing asset install actions for package '.$name);
			if(IsSet($assetActions['actions']))
				$this->installActions($package, $assetActions['actions']);
			if(IsSet($this->assets['actions']))
				$this->installActions($package, $this->assets['actions']);
		}
	}

	public function supports($packageType)
	{
		return true;
	}

	public function setAssets($assets)
	{
		$this->assets = $assets;
	}
};
?>
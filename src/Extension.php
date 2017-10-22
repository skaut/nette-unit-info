<?php
namespace Skautis\NetteUnitInfo;

/**
 *Sets up the extension
 *
 * @author basnik
 */
class Extension extends \Nette\DI\CompilerExtension{
	
	/**
	 * Add our services
	 */
	public function loadConfiguration() {
		
		$builder = $this->getContainerBuilder();
		
		// register our services
		$builder->addDefinition($this->prefix('skautisUnitDetailsControl'))->setImplement('Skautis\NetteUnitInfo\IUnitDetailControlFactory');
		$builder->addDefinition($this->prefix('skautisSubunitsMapControl'))->setImplement('Skautis\NetteUnitInfo\ISubunitsMapControlFactory');
	}
}

<?php
namespace Skautis\NetteUnitInfo;

/**
 *Sets up the extension
 *
 * @author basnik
 */
class Extension extends \Nette\DI\CompilerExtension{

    /** @var array */
    private $defaults = array(
        'cacheTime' => 1440 // = 60*24 = 1 day
    );


    /**
     * Add our services
     */
    public function loadConfiguration() {

        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults);

        // register our services
        $builder->addDefinition($this->prefix('skautisUnitDetailsControl'))
            ->setImplement('Skautis\NetteUnitInfo\IUnitDetailControlFactory')
            ->setArguments([$config['cacheTime']]);
        $builder->addDefinition($this->prefix('skautisSubunitsMapControl'))
            ->setImplement('Skautis\NetteUnitInfo\ISubunitsMapControlFactory')
            ->setArguments([$config['cacheTime']]);
    }
}

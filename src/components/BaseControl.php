<?php
namespace Basnik\SkautisUnitContacts;

use Nette\Caching\Cache;

/**
 * Base class for definning common methods and properties
 *
 * @author basnik
 */
abstract class BaseControl extends \Nette\Application\UI\Control{
	
	const CACHE_EXPIRY_MINUTES = 1440; // = 60*24 = 1 day
	
	/** @var \Skautis\Skautis */
	protected $skautis;
	
	/** @var Cache */
	protected $cache;
	
	/** @var string */
	protected $templateFile;
	
	protected $headingCallback;
	protected $descCallback;
	
	public function __construct(\Skautis\Skautis $skautis, \Nette\Caching\IStorage $storage, \Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		
		$this->skautis = $skautis;
		$this->cache = new Cache($storage);
		$this->headingCallback = function($realty){ return $realty->Realty_RealtyType." ".$realty->Realty_Street; };
		$this->descCallback = function($realty){ return $realty->Realty_Description; };
	}
	
	public function setTemplateFile($filePath){
		$this->templateFile = $filePath;
	}
	
	public function setDescCallback($descCallback){
		$this->descCallback = $descCallback;
	}
	
	public function setHeadingCallback($headingCallback){
		$this->headingCallback = $headingCallback;
	}
	
}

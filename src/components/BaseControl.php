<?php
namespace Skautis\NetteUnitInfo;

use Nette\Caching\Cache;


/**
 * Base class for definning common methods and properties
 *
 * @author basnik
 */
abstract class BaseControl extends \Nette\Application\UI\Control{

    /** @var \Skautis\Skautis */
    protected $skautis;

    /** @var Cache */
    protected $cache;

    /** @var string */
    protected $templateFile;

    /** @var  string */
    protected $cacheTime;

    protected $headingCallback;
    protected $descCallback;

    public function __construct($cacheTime = null, \Skautis\Skautis $skautis, \Nette\Caching\IStorage $storage, \Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
        $this->skautis = $skautis;
        $this->cache = new Cache($storage);
        $this->cacheTime = $cacheTime;
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

    protected function loadUnitMapData($unitId){
        $marks = [];

        $organizationUnit = $this->skautis->OrganizationUnit;
        $skautisData = $organizationUnit->advertisingSummary(["ID_Unit" => $unitId, "IncludeChildUnits" => TRUE]);
        foreach($skautisData as $realty){
            if(isset($realty->Realty_ID) && !array_key_exists($realty->Realty_ID, $marks)){

                $unitData = $organizationUnit->unitAll(["ID_UnitChild" => $realty->ID_Unit])[0];
                $realty->ID_UnitParent = $unitData->ID;
                $realty->ID_UnitType_parent = $unitData->ID_UnitType;

                $marks[$realty->Realty_ID] = [
                    "lat" => $realty->Realty_GpsLatitude,
                    "lng" => $realty->Realty_GpsLongitude,
                    "title" => call_user_func($this->headingCallback, $realty),
                    "desc" => call_user_func($this->descCallback, $realty)
                ];
            }
        }

        return $marks;
    }
}

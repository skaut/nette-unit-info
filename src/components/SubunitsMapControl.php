<?php
namespace Basnik\SkautisUnitContacts;

use Nette\Caching\Cache;

/**
 * Displays map with subunits of given unit.
 * --
 * Zobrazi mapu podjednotek dane jednotky podle toho, kde maji klubovnu.
 *
 * @author basnik
 */
class SubunitsMapControl extends BaseControl{
	

	public function render($unitId, $cacheMinutes=NULL){
		
		$marks = [];
		$cacheTime = $cacheMinutes !== NULL ? intval($cacheMinutes) : self::CACHE_EXPIRY_MINUTES;
		
		// first look for cache
		$cached = $this->cache->load(get_class($this));
		if($cached !== NULL && array_key_exists($unitId, $cached)){
			$marks = $cached[$unitId];
		}else{
			if($cached === NULL){
				$cached = [];
			}
			$marks = $this->loadUnitData($unitId);
			
			$cached[$unitId] = $marks;
			$this->cache->save(get_class($this), $cached, [
				Cache::EXPIRATION => $cacheTime.' minutes'
			]);
		}
	
		$this->template->mapMarks = json_encode($marks);
		if($this->templateFile !== NULL){
			$this->template->setFile($this->templateFile);
		}else{
			$this->template->setFile(__DIR__.'/../templates/components/subunitsMap.latte');
		}
		$this->template->render();
	}
	
	protected function loadUnitData($unitId){
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

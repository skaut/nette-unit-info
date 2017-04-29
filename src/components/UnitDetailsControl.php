<?php
namespace Basnik\SkautisUnitContacts;

use Nette\Caching\Cache;

/**
 * Outputs contact information for given unit from Skautis (if you have sufficient access rights for it).
 * --
 * Vypise kontaktni informace o zvolene jednotce ze Skautisu (za predpokladu, ze k ni mate prava)
 *
 * @author basnik
 */
class UnitDetailsControl extends BaseControl{
	
	
	public function render($unitId, $cacheMinutes=NULL){
		
		$cacheTime = $cacheMinutes !== NULL ? intval($cacheMinutes) : self::CACHE_EXPIRY_MINUTES;
		$unitData = [];
		
		// first look for cache
		$cached = $this->cache->load(get_class($this));
		if($cached !== NULL && array_key_exists($unitId, $cached)){
			$unitData = $cached[$unitId];
			$unitData["logo"] = isset($unitData["logoRaw"]) ? \Nette\Utils\Image::fromString($unitData["logoRaw"]) : NULL;
			
		}else{
			if($cached === NULL){
				$cached = [];
			}
			$unitData = $this->loadData($unitId);
			
			$cached[$unitId] = $unitData;
			unset($cached[$unitId]["logo"]); // do not cache nette image
			$this->cache->save(get_class($this), $cached, [
				Cache::EXPIRATION => $cacheTime.' minutes'
			]);
		}
		
		$this->template->unitName = ucfirst($unitData["details"]->DisplayName);
		$this->template->fullName = $unitData["details"]->FullDisplayName;
		$this->template->unitIdent = $unitData["details"]->RegistrationNumber;
		
		$this->template->unitIC = $unitData["details"]->IC;
		
		$this->template->qStreet = $unitData["details"]->Street;
		$this->template->qCity = $unitData["details"]->City;
		$this->template->qPostcode = $unitData["details"]->Postcode;
		
		
		$this->template->unitText = $unitData["note"];
		
		
		$this->template->unitContacts = $unitData["contacts"];
		
		
		$this->template->statutoryName = $unitData["statutory"];
		$this->template->assistantName = $unitData["assistant"];
		
		
		$this->template->logoContent = $unitData["logo"];
		
		$this->template->mapMarks = json_encode($unitData["marks"]);
		
		
		if($this->templateFile !== NULL){
			$this->template->setFile($this->templateFile);
		}else{
			$this->template->setFile(__DIR__.'/../templates/components/unitDetails.latte');
		}
		$this->template->render();
	}
	
	protected function loadData($unitId){
		
		$organizationUnit = $this->skautis->OrganizationUnit;
		$unitData = [];
		
		$unitData["details"] = $organizationUnit->UnitDetail(["ID" => $unitId]);
		if(!isset($unitData["details"]->DisplayName)){
			throw new \Nette\InvalidArgumentException(sprintf("Unknown unit: %s", $unitId));
		}
		
		$adData = $organizationUnit->advertisingDetail(["ID_Unit" => $unitId]);
		$unitData["note"] = isset($adData->Note) ? $adData->Note : "";
		
		$unitData["contacts"] = $organizationUnit->unitContactAll(["ID_Unit" => $unitId]);
		
		$statutoryDataArray = $organizationUnit->functionAllRegistry(["ID_Unit" => $unitId, "ReturnStatutory" => TRUE]);
		$statutoryData = reset($statutoryDataArray);
		$unitData["statutory"] = $statutoryData->Person;
		
		$assistantDataArray = $organizationUnit->functionAllRegistry(["ID_Unit" => $unitId, "ReturnAssistant" => TRUE]);
		$assistantData = reset($assistantDataArray);
		$unitData["assistant"] = $assistantData->Person;
		
		$logoData = $organizationUnit->unitLogo(["ID" => $unitId]);
		$unitData["logo"] = NULL;
		if(isset($logoData->LogoContent)){
			$unitData["logoRaw"] = $logoData->LogoContent;
			$unitData["logo"] = \Nette\Utils\Image::fromString($logoData->LogoContent);
		}
		
		$adSummary = $organizationUnit->advertisingSummary(["ID_Unit" => $unitId, "IncludeChildUnits" => TRUE]);
		$unitData["marks"] = [];
		foreach($adSummary as $realty){
			if(isset($realty->Realty_ID) && !array_key_exists($realty->Realty_ID, $unitData["marks"])){
	
				$subunitData = $organizationUnit->unitAll(["ID_UnitChild" => $realty->ID_Unit])[0];
				$realty->ID_UnitParent = $subunitData->ID;
				$realty->ID_UnitType_parent = $subunitData->ID_UnitType;
				
				$unitData["marks"][$realty->Realty_ID] = [
					"lat" => $realty->Realty_GpsLatitude,
					"lng" => $realty->Realty_GpsLongitude,
					"title" => call_user_func($this->headingCallback, $realty),
					"desc" => call_user_func($this->descCallback, $realty)
				];
			}
		}
		
		return $unitData;
	}

}

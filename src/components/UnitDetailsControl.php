<?php
namespace Skautis\NetteUnitInfo;

use Nette\Caching\Cache;

/**
 * Outputs contact information for given unit from Skautis (if you have sufficient access rights for it).
 * --
 * Vypise kontaktni informace o zvolene jednotce ze Skautisu (za predpokladu, ze k ni mate prava)
 *
 * @author basnik
 */
class UnitDetailsControl extends BaseControl{


    public function render($unitId){

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

            try{
                $unitData = $this->loadData($unitId);

                $cached[$unitId] = $unitData;
                unset($cached[$unitId]["logo"]); // do not cache nette image
                $this->cache->save(get_class($this), $cached, [
                    Cache::EXPIRATION => $this->cacheTime.' minutes'
                ]);

            }catch(\Skautis\Exception $e){
                $this->template->skautisError = TRUE;

                \Tracy\Debugger::log($e);
            }
        }

        if(!empty($unitData)){

            $this->template->unitName = ucfirst($unitData["details"]->DisplayName);
            $this->template->fullName = isset($unitData["details"]->FullDisplayName) ? $unitData["details"]->FullDisplayName : "";
            $this->template->unitIdent = $unitData["details"]->RegistrationNumber;

            $this->template->unitIC = isset($unitData["details"]->IC) ? $unitData["details"]->IC : "";

            $this->template->qStreet = $unitData["details"]->Street;
            $this->template->qCity = $unitData["details"]->City;
            $this->template->qPostcode = $unitData["details"]->Postcode;


            $this->template->unitText = $unitData["note"];


            $this->template->unitContacts = $unitData["contacts"];


            $this->template->statutoryName = $unitData["statutory"];
            $this->template->assistantName = $unitData["assistant"];


            $this->template->logoContent = $unitData["logo"];

            $this->template->mapMarks = !empty($unitData["marks"]) ? json_encode($unitData["marks"]) : "";
            $this->template->places = $unitData["places"];
        }

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

        // contacts are returned as an array, if no contacts are available, empty object is returned, so is_array is one of the best ways to check
        $contacts = $organizationUnit->unitContactAll(["ID_Unit" => $unitId]);
        $unitData["contacts"] = is_array($contacts) ? $contacts : [];

        $statutoryDataArray = $organizationUnit->functionAllRegistry(["ID_Unit" => $unitId, "ReturnStatutory" => TRUE]);
        $statutoryData = reset($statutoryDataArray);
        $unitData["statutory"] = isset($statutoryData->Person) ? $statutoryData->Person : "";

        $assistantDataArray = $organizationUnit->functionAllRegistry(["ID_Unit" => $unitId, "ReturnAssistant" => TRUE]);
        $assistantData = reset($assistantDataArray);
        $unitData["assistant"] = isset($assistantData->Person) ? $assistantData->Person : "";

        $logoData = $organizationUnit->unitLogo(["ID" => $unitId]);
        $unitData["logo"] = NULL;
        if(isset($logoData->LogoContent)){
            $unitData["logoRaw"] = $logoData->LogoContent;
            $unitData["logo"] = \Nette\Utils\Image::fromString($logoData->LogoContent);
        }

        $realtyData = $this->loadUnitMapData($unitId);
        $unitData["marks"] = $realtyData["marks"];
        $unitData["places"] = $realtyData["places"];

        return $unitData;
    }

}

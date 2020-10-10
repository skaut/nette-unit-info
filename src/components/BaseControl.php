<?php
namespace Skautis\NetteUnitInfo;

use Nette\Caching\Cache;
use Nette\Utils\DateTime;
use Nette\Utils\Html;


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
        $this->headingCallback = function($place){ return $place["name"]; };
        $this->descCallback = function($place){
        	$desc = "";
        	foreach($place["units"] as $unitName) {
        		$desc .= $unitName.Html::el("br");
			}
        	if (!empty($place["desc"])) {
        		$desc .= $place["desc"].Html::el("br");
			}

        	if (!empty($place["meetings"])) {
        		$desc .= Html::el("br")."Schůzky:".Html::el("br");
        		foreach($place["meetings"] as $meeting) {
					$desc .= $meeting["day"]." ".$meeting["start"] ." - ". $meeting["end"].Html::el("br");
				}
			}

        	return $desc;
        };
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
        $places = [];

        $organizationUnit = $this->skautis->OrganizationUnit;
        $skautisData = $organizationUnit->advertisingSummary(["ID_Unit" => $unitId, "IncludeChildUnits" => TRUE]);
        foreach($skautisData as $realty){
            if(isset($realty->Realty_ID)) {
				$unitData = $organizationUnit->unitAll(["ID_UnitChild" => $realty->ID_Unit])[0];

				if (!array_key_exists($realty->Realty_ID, $places)) {
					$place = [
						"unitId" => $realty->ID_Unit,
						"parentUnitId" => $unitData->ID,
						"parentUnitType" => $unitData->ID_UnitType,
						"parentUnitName" => $unitData->DisplayName,
						"name" => $realty->Realty_RealtyType." ".$realty->Realty_Street,
						"type" => $realty->Realty_RealtyType,
						"street" => $realty->Realty_Street,
						"city" => $realty->Realty_City,
						"desc" => $realty->Realty_Description,
						"lat" => $realty->Realty_GpsLatitude,
						"lng" => $realty->Realty_GpsLongitude,
						"units" => [
							$unitData->ID => $unitData->DisplayName,
							$realty->ID_Unit => $realty->Unit
						],
						"meetings" => [
							$realty->MeetingDate_ID => $this->getMeetingData($realty)
						]
					];
					$places[$realty->Realty_ID] = $place;
				} else {
					if (!array_key_exists($realty->ID_Unit, $places[$realty->Realty_ID]["units"])) {
						$places[$realty->Realty_ID]["units"][$realty->ID_Unit] = $realty->Unit;
					}
					if (!array_key_exists($realty->MeetingDate_ID, $places[$realty->Realty_ID]["meetings"])) {
						$places[$realty->Realty_ID]["meetings"][$realty->MeetingDate_ID] = $this->getMeetingData($realty);
					}
					if (!array_key_exists($realty->ID_AdvertisingCategory, $places[$realty->Realty_ID]["meetings"][$realty->MeetingDate_ID]["categories"])) {
						$places[$realty->Realty_ID]["meetings"][$realty->MeetingDate_ID]["categories"][$realty->ID_AdvertisingCategory] = $this->getCategoryData($realty);
					}
				}
            }
        }

		foreach($places as $placeId => $place) {

			// remove empty categories from meetings
			foreach($place["meetings"] as $meetingId => $meeting) {
				$filtered = array_filter($meeting["categories"]);
				$places[$placeId]["meetings"][$meetingId]["categories"] = $filtered;
			}

			// sort meetings by weekday - we need to sort on the source array, so using the key
			usort($places[$placeId]["meetings"], [$this, "compareMeetings"]);

			$marks[] = [
				"lat" => $place["lat"],
				"lng" => $place["lng"],
				"title" => call_user_func($this->headingCallback, $places[$placeId]),
				"desc" => call_user_func($this->descCallback, $places[$placeId])
			];
		}

		return [
			"places" => $places,
			"marks" => $marks
		];
    }

    private function getMeetingData($realty) {
    	return [
    		"dayNum" => $this->parseWeekDay($realty->MeetingDate_ID_WeekDay),
    		"day" => $realty->MeetingDate_WeekDay,
			"start" => $this->parseTime($realty->MeetingDate_TimeFrom),
			"end" => $this->parseTime($realty->MeetingDate_TimeTo),
			"periodicity" => $realty->MeetingDate_Periodicity,
			"unit" => $realty->Unit,
			"categories" => [
				$realty->ID_AdvertisingCategory => $this->getCategoryData($realty)
			]
		];
	}

	private function getCategoryData($realty) {
    	return $realty->ID_AdvertisingCategory == NULL ? [] : [
    		"ageFrom" => $realty->AdvertisingCategory_AgeFrom,
			"ageTo" => $realty->AdvertisingCategory_AgeTo,
			"sex" => $realty->AdvertisingCategory_ID_Sex,
			"note" => $realty->AdvertisingCategory_Note
		];
	}

	private function parseTime($time) {
    	$interval =  new \DateInterval($time);
    	$output = $interval->format("%h:%I");
    	return $output;
	}

	private function parseWeekDay($short) {
    	switch ($short) {
			case "Po":
				return 1;
			case "Ut":
				return 2;
			case "St":
				return 3;
			case "Ct":
				return 4;
			case "Pa":
				return 5;
			case "So":
				return 6;
			case "Ne":
				return 7;
		}
	}

	private function compareMeetings($meetingA, $meetingB) {
    	if ($meetingA["dayNum"] < $meetingB["dayNum"]) {
    		return -1;
		} else if ($meetingA["dayNum"] > $meetingB["dayNum"]) {
    		return 1;
		} else {
    		// days same, compare time
			$hourA = explode(":", $meetingA["start"])[0];
			$hourB = explode(":", $meetingB["start"])[0];
			return ($hourA < $hourB) ? -1 : 1;
		}
	}
}

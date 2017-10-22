<?php
namespace Skautis\NetteUnitInfo;

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

            try{
                $marks = $this->loadUnitMapData($unitId);

                $cached[$unitId] = $marks;
                $this->cache->save(get_class($this), $cached, [
                    Cache::EXPIRATION => $cacheTime.' minutes'
                ]);
            }catch(\Skautis\Exception $e){
                $this->template->skautisError = TRUE;

                \Tracy\Debugger::log($e);
            }
        }

        if(!empty($marks)){
            $this->template->mapMarks = json_encode($marks);
        }

        if($this->templateFile !== NULL){
            $this->template->setFile($this->templateFile);
        }else{
            $this->template->setFile(__DIR__.'/../templates/components/subunitsMap.latte');
        }
        $this->template->render();
    }

}

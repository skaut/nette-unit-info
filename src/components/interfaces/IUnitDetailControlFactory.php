<?php

namespace Skautis\NetteUnitInfo;

/**
 * Used for DI.
 * 
 * @internal
 * @author basnik
 */
interface IUnitDetailControlFactory {
	
	/**
	 * @return UnitDetailsControl 
	 */
	public function create();
}

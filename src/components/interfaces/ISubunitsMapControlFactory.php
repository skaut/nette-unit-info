<?php

namespace Skautis\NetteUnitInfo;

/**
 * Used for DI.
 * 
 * @internal
 * @author basnik
 */
interface ISubunitsMapControlFactory {
	
	/**
	 * @return SubunitsMapControl 
	 */
	public function create();
}

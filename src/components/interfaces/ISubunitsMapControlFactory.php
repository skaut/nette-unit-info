<?php

namespace Basnik\SkautisUnitContacts;

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

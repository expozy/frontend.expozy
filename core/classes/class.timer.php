<?php
if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }

/** =========================================================
 * Class Timer
 * ========================================================== */
class Timer{
	public $start;
	
	public function __construct() {
		$this->start = microtime(true);
	}
	
	public function end(){
		
		return microtime(true) - $this->start;
	}
	
}
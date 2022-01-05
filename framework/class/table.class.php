<?php
/**
 */
 
defined('IN_IA') or exit('Access Denied');


abstract class We7Table {
	protected $query;
	
	public function __construct() {
				$this->query = load()->object('Query');
		$this->query->from('');
	}
	
	
	public function searchWithPage($pageindex, $pagesize) {
		if (!empty($pageindex) && !empty($pagesize)) {
			$this->query->page($pageindex, $pagesize);
		}
		
		return $this;
	}
	
	
	public function getLastQueryTotal() {
		return $this->query->getLastQueryTotal();
	}
}
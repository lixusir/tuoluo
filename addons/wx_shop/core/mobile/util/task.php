<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Task_WxShopPage extends MobilePage
{
	public function main() 
	{
		$this->runTasks();
	}
}
?>
<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class lightly_ime_WxShopPage extends MobileLoginPage
{
	public function main() 
	{
		global $_W;
		
		global $_GPC;
		

		

		include $this->template();
	}
}
?>
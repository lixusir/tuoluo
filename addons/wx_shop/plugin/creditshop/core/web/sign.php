<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Sign_WxShopPage extends PluginWebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		include $this->template();
	}
	public function tpl() 
	{
		include $this->template();
	}
}
?>
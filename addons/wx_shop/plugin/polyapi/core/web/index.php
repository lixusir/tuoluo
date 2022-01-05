<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}

class Index_WxShopPage extends PluginWebPage
{
	public function main()
	{
		header('location: ' . webUrl('polyapi/set'));
	}
}


?>
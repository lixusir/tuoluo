<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}


require_once IA_ROOT . '/addons/wx_shop/version.php';
require_once IA_ROOT . '/addons/wx_shop/defines.php';
require_once WX_SHOP_INC . 'functions.php';
require_once WX_SHOP_INC . 'processor.php';
require_once WX_SHOP_INC . 'plugin_model.php';
require_once WX_SHOP_INC . 'com_model.php';
class Wx_shopModuleProcessor extends Processor
{
	public function respond()
	{
		return parent::respond();
	}
}


?>
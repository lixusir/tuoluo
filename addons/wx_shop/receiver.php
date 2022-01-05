<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}


require_once IA_ROOT . '/addons/wx_shop/version.php';
require_once IA_ROOT . '/addons/wx_shop/defines.php';
require_once WX_SHOP_INC . 'functions.php';
require_once WX_SHOP_INC . 'receiver.php';
class Wx_shopModuleReceiver extends Receiver
{
	public function receive()
	{
		parent::receive();
	}
}


?>
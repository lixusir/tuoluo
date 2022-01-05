<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}


require_once IA_ROOT . '/addons/wx_shop/version.php';
require_once IA_ROOT . '/addons/wx_shop/defines.php';
require_once WX_SHOP_INC . 'functions.php';
class Wx_shopModule extends WeModule
{
	public function welcomeDisplay()
	{
		header('location: ' . webUrl());
		exit();
	}
}


?>
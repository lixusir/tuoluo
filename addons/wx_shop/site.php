<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}

if (!(function_exists('getIsSecureConnection'))) {
function getIsSecureConnection()
{
	if (isset($_SERVER['HTTPS']) && (('1' == $_SERVER['HTTPS']) || ('on' == strtolower($_SERVER['HTTPS'])))) {
		return true;
	}


	if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
		return true;
	}


	return false;
}
}


if (function_exists('getIsSecureConnection')) {
	$secure = getIsSecureConnection();
	$http = (($secure ? 'https' : 'http'));
	$_W['siteroot'] = ((strexists($_W['siteroot'], 'https://') ? $_W['siteroot'] : str_replace('http', $http, $_W['siteroot'])));
}
if(empty($_W['setting']['site']['key'])) $_W['setting']['site']['key']= $_W['config']['setting']['site']['key'];
require_once IA_ROOT . '/addons/wx_shop/version.php';
require_once IA_ROOT . '/addons/wx_shop/defines.php';
require_once WX_SHOP_INC . 'functions.php';
class Wx_shopModuleSite extends WeModuleSite
{

	public function getMenus()
	{
		global $_W;
		return array(
	array('title' => '管理后台', 'icon' => 'fa fa-shopping-cart', 'url' => webUrl())
	);
	}

	public function doWebWeb()
	{
 		m('route')->run();
	}

	public function doMobileMobile()
	{
		m('route')->run(false);
	}

	public function payResult($params)
	{
		return m('order')->payResult($params);
	}
}


?>
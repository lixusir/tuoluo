<?php
/**
海生科技网 www.47cu.com
*/
define('IN_SYS', true);
require '../framework/bootstrap.inc.php';
load()->web('common');
load()->web('template');
header('Content-Type: text/html; charset=UTF-8');
$uniacid = intval($_GPC['i']);
if (empty($uniacid)) {
	die('Access Denied.');
}
$site = WeUtility::createModuleSite('wx_shop');
$_GPC['c'] = 'site';
$_GPC['a'] = 'entry';
$_GPC['m'] = 'wx_shop';
$_GPC['do'] = 'web';
if (!isset($_GPC['r'])) {
	$_GPC['r'] = 'cashier.manage.index';
} else {
	$_GPC['r'] = 'cashier.manage.' . $_GPC['r'];
}
$_W['uniacid'] = (int) $_GPC['i'];
$_W['acid'] = (int) $_GPC['i'];
if (!is_error($site)) {
	$method = 'doWebWeb';
	$site->uniacid = $uniacid;
	$site->inMobile = false;
	if (method_exists($site, $method)) {
		$site->{$method}();
		die;
	}
}
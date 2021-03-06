<?php
/**
 */
defined('IN_IA') or exit('Access Denied');

load()->model('setting');
load()->model('user');

$dos = array('display', 'save_oauth');
$do = in_array($_GPC['do'], $dos)? $do : 'display';
$_W['page']['title'] = '站点管理 - oauth全局设置 - oauth全局设置';

$oauth = setting_load('global_oauth');
$oauth = !empty($oauth['global_oauth']) ? $oauth['global_oauth'] : array();

if ($do == 'display') {
	$user_have_accounts = user_borrow_oauth_account_list();
	$oauth_accounts = $user_have_accounts['oauth_accounts'];
}

if ($do == 'save_oauth') {
	if (!$_W['isajax'] || !$_W['ispost']) {
		iajax(-1, '添加失败');
	}

	$oauth['oauth']['account'] = intval($_GPC['account']);
	$oauth['oauth']['host'] = rtrim($_GPC['host'],'/');
	if (!empty($oauth['host']) && !preg_match('/^http(s)?:\/\//', $oauth['host'])) {
		iajax(-1, '域名不能为空或域名格式不对');
	}
	$result = setting_save($oauth, 'global_oauth');
	if (is_error($result)) {
		iajax(-1, '添加失败');
	}
	iajax(0, '添加成功', url('system/oauth'));

}
template('system/oauth');
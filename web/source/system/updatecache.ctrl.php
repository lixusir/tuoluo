<?php
/**
 * [WECHAT 2017]
 * [WECHAT  a free software]
 */
defined('IN_IA') or exit('Access Denied');

load()->model('cache');
load()->model('setting');

$_W['page']['title'] = '更新缓存 - 设置 - 系统管理';

if (checksubmit('submit', true)) {
	$account_ticket_cache = cache_read('account:ticket');
	pdo_delete('core_cache');
	cache_clean();
	cache_write('account:ticket', $account_ticket_cache);
	unset($account_ticket_cache);
	cache_build_template();
	cache_build_users_struct();
	// cache_build_module_status(); //导致返回错信息 所以注释掉 xiaojie 2018/07/07
	// cache_build_cloud_upgrade_module(); //导致返回错信息 所以注释掉 xiaojie 2018/07/07
	cache_build_setting();
	cache_build_frame_menu();
	cache_build_module_subscribe_type();
	// cache_build_cloud_ad(); //导致返回错信息 所以注释掉 xiaojie 2018/07/07
	iajax(0, '更新缓存成功！', '');
}

template('system/updatecache');
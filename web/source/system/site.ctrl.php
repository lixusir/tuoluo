<?php 
/**
 * [WECHAT 2017]
 * [WECHAT  a free software]
 */
defined('IN_IA') or exit('Access Denied');

load()->model('system');

$dos = array('copyright');
$do = in_array($do, $dos) ? $do : 'copyright';
$_W['page']['title'] = '站点设置 - 工具  - 系统管理';

$settings = $_W['setting']['copyright'];
if(empty($settings) || !is_array($settings)) {
	$settings = array();
} else {
	$settings['slides'] = iunserializer($settings['slides']);
}

$path = IA_ROOT . '/web/themes/';
if(is_dir($path)) {
	if ($handle = opendir($path)) {
		while (false !== ($templatepath = readdir($handle))) {
			if ($templatepath != '.' && $templatepath != '..') {
				if(is_dir($path.$templatepath)){
					$template[] = $templatepath;
				}
			}
		}
	}
}

if ($do == 'copyright') {
	$template_ch_name = system_template_ch_name();
	if (checksubmit('submit')) {
		$data = array(
			'status' => intval($_GPC['status']),
			'demo' => $_GPC['demo'],
			'is_dns' => $_GPC['is_dns'],
			'is_check' => $_GPC['is_check'],
			'verifycode' => intval($_GPC['verifycode']),
			'reason' => trim($_GPC['reason']),
			'smname' => $_GPC['smname'],
			'sitename' => trim($_GPC['sitename']),
			'url' => (strexists($_GPC['url'], 'http://') || strexists($_GPC['url'], 'https://')) ? $_GPC['url'] : "http://{$_GPC['url']}",
			'sitehost' => $_GPC['sitehost'],
			'payhost' => $_GPC['payhost'],
			'statcode' => htmlspecialchars_decode($_GPC['statcode']),
			'footerleft' => htmlspecialchars_decode($_GPC['footerleft']),
			'footerright' => htmlspecialchars_decode($_GPC['footerright']),
			'icon' => trim($_GPC['icon']),
			'ewm' => $_GPC['ewm'],
			'flogo' => trim($_GPC['flogo']),
			'background_img' => trim($_GPC['background_img']),
			'slides' => iserializer($_GPC['slides']),
			'notice' => trim($_GPC['notice']),
			'blogo' => trim($_GPC['blogo']),
			'baidumap' => $_GPC['baidumap'],
			'company' => trim($_GPC['company']),
			'companyprofile' => htmlspecialchars_decode($_GPC['companyprofile']),
			'address' => trim($_GPC['address']),
			'person' => trim($_GPC['person']),
			'phone' => trim($_GPC['phone']),
			'qq' => trim($_GPC['qq']),
			'email' => trim($_GPC['email']),
			'keywords' => trim($_GPC['keywords']),
			'description' => trim($_GPC['description']),
			'showhomepage' => intval($_GPC['showhomepage']),
			'leftmenufixed' => (!empty($_GPC['leftmenu_fixed'])) ? 1 : 0,
				'mobile_status' => $_GPC['mobile_status'],
				'login_type' => $_GPC['login_type'],
		);
		$test = setting_save($data, 'copyright');
		$template = trim($_GPC['template']);
		setting_save(array('template' => $template), 'basic');
		itoast('更新设置成功！', url('system/site'), 'success');
	}
}

template('system/site');
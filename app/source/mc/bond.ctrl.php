<?php
/**
 * [WECHAT 2017]
 * [WECHAT  a free software]
 */
defined('IN_IA') or exit('Access Denied');

load()->model('app');
load()->func('tpl');
load()->model('user');

$dos = array('display', 'credits', 'address', 'card', 'mycard', 'record', 
			'mobile', 'email', 'card_qrcode', 
			'addressadd', 'settings', 'password', 'aboutus', 'binding_account');
$do = in_array($do, $dos) ? $do : 'display';
$profile = mc_fetch($_W['member']['uid']);

if ($do == 'credits') {
	$where = '';
	$params = array(':uid' => $_W['member']['uid']);
	$pindex = max(1, intval($_GPC['page']));
	$psize = 15;
	
	$period = intval($_GPC['period']);
	if ($period == '1') {
		$starttime = date('Ym01',strtotime(0));
		$endtime = date('Ymd His', time());
	} elseif($period == '0') {
		$starttime = date('Ym01', strtotime(1*$period . "month"));
		$endtime = date('Ymd', strtotime("$starttime + 1 month - 1 day"));
	} else {
		$starttime = date('Ym01', strtotime(1*$period . "month"));
		$endtime = date('Ymd', strtotime("$starttime + 1 month - 1 day"));
	}
	$where = ' AND `createtime` >= :starttime AND `createtime` < :endtime';
	$params[':starttime'] = strtotime($starttime);
	$params[':endtime'] = strtotime($endtime);
	
	$sql = 'SELECT `realname`, `avatar` FROM ' . tablename('mc_members') . " WHERE `uid` = :uid";
	$user = pdo_fetch($sql, array(':uid' => $_W['member']['uid']));
	if ($_GPC['credittype']) {
		
		if ($_GPC['type'] == 'order') {
			$sql = 'SELECT * FROM ' . tablename('mc_credits_recharge') . " WHERE `uid` = :uid $where LIMIT " . ($pindex - 1) * $psize. ',' . $psize;
			$orders = pdo_fetchall($sql, $params);
			foreach ($orders as &$value) {
				$value['createtime'] = date('Y-m-d', $value['createtime']);
				$value['fee'] = number_format($value['fee'], 2);
				if ($value['status'] == 1) {
					$orderspay += $value['fee'];
				}
				unset($value);
			}
			
			$ordersql = 'SELECT COUNT(*) FROM ' .tablename('mc_credits_recharge') . "WHERE `uid` = :uid {$where}";
			$total = pdo_fetchcolumn($ordersql, $params);
			$orderpager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
			template('mc/bond');
			exit();
		}
		$where .= " AND `credittype` = '{$_GPC['credittype']}'";
	}
	
	
	$sql = 'SELECT `num` FROM ' . tablename('mc_credits_record') . " WHERE `uid` = :uid $where";
	$nums = pdo_fetchall($sql, $params);
	$pay = $income = 0;
	foreach ($nums as $value) {
		if ($value['num'] > 0) {
			$income += $value['num'];
		} else {
			$pay += abs($value['num']);
		}
	}
	if ($_GPC['credittype'] == 'credit2') {
		$pay = number_format($pay, 2);
		$income = number_format($income, 2);
	}
	
	$sql = 'SELECT * FROM ' . tablename('mc_credits_record') . " WHERE `uid` = :uid {$where} ORDER BY `createtime` DESC LIMIT " . ($pindex - 1) * $psize.','. $psize;
	$data = pdo_fetchall($sql, $params);
	foreach ($data as $key=>$value) {
		$data[$key]['credittype'] = $creditnames[$data[$key]['credittype']]['title'];
		$data[$key]['createtime'] = date('Y-m-d H:i', $data[$key]['createtime']);
		$data[$key]['num'] = number_format($value['num'], 2);
		if ($data[$key]['num'] < 0) {
			$data[$key]['color'] = "#000";
		} else {
			$data[$key]['color'] = "#04be02";
			$data[$key]['num'] = "+" . $data[$key]['num'];
		}
		$data[$key]['remark'] = str_replace('credit1', '??????', $data[$key]['remark']);
		$data[$key]['remark'] = str_replace('credit2', '??????', $data[$key]['remark']);
		$data[$key]['remark'] = empty($data[$key]['remark']) ? '?????????' : $data[$key]['remark'];
	}
	
	$pagesql = 'SELECT COUNT(*) FROM ' .tablename('mc_credits_record') . "WHERE `uid` = :uid {$where}";
	$total = pdo_fetchcolumn($pagesql, $params);
	$pager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
	$pagenums = ceil($total / $psize);
	if($_W['isajax'] && $_W['ispost']) {
		if (!empty($data)){
			exit(json_encode($data));
		} else {
			exit(json_encode(array('state'=>'error'))); 
		}
	}
	$type = trim($_GPC['type']);
	if ($type == 'recorddetail') {
		$id = intval($_GPC['id']);
		$credittype = $_GPC['credittype'];
		$data = pdo_fetch("SELECT r.*, u.username FROM " . tablename('mc_credits_record') . ' AS r LEFT JOIN ' .tablename('users') . ' AS u ON r.operator = u.uid ' . ' WHERE r.id = :id AND r.uniacid = :uniacid AND r.credittype = :credittype ORDER BY id DESC LIMIT ' . ($pindex - 1) * $psize .',' . $psize, array(':uniacid' => $_W['uniacid'], ':id' => $id, ':credittype' => $credittype));
		if ($data['credittype'] == 'credit2') {
			$data['credittype'] = '??????';
		} elseif ($data['credittype'] == 'credit1') {
			$data['credittype'] = '??????';
		}
		$data['remark'] = str_replace('credit1', '??????', $data['remark']);
		$data['remark'] = str_replace('credit2', '??????', $data['remark']);
		$data['remark'] = empty($data['remark']) ? '????????????' : $data['remark'];
	}
}

if($do == 'record') {
	$setting = pdo_get('mc_card', array('uniacid' => $_W['uniacid']), array('nums_text', 'times_text'));
	$card = pdo_get('mc_card_members', array('uniacid' => $_W['uniacid'], 'uid' => $_W['member']['uid']));
	$type = trim($_GPC['type']);
	$where = ' WHERE uniacid = :uniacid AND uid = :uid AND type = :type';
	$params = array(
		':uniacid' => $_W['uniacid'],
		':uid' => $_W['member']['uid'],
		':type' => $type,
	);
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('mc_card_record') . $where, $params);
	$limit = ' ORDER BY id DESC LIMIT ' . ($pindex - 1) * $psize . ', ' . $psize;
	$data = pdo_fetchall('SELECT * FROM ' . tablename('mc_card_record') . $where . $limit, $params);
	$pager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
}

if($do == 'mobile') {
	$reregister = false;
	if ($_W['member']['email'] == md5($_W['openid']).'@HaiShengcms.com') {
		$reregister = true;
		message('????????????????????????', url('mc/bond/binding_account', array('type' => '1')), 'error');
	}
	$op = trim($_GPC['op']) ? trim($_GPC['op']) : 'index';
	$mobile_exist = empty($profile['mobile']) ? 0 : 1;
	if($_W['ispost'] && $_W['isajax']) {
		$code = trim($_GPC['code']);
		$mobile = trim($_GPC['mobile']);
		$password = trim($_GPC['password']);
		$repassword = trim($_GPC['repassword']);
		load()->model('utility');
		if (!preg_match(REGULAR_MOBILE, $mobile)) {
			message(error(-1, '????????????'), '', 'ajax');
		}
		if (!code_verify($_W['uniacid'], $mobile, $code)) {
			pdo_delete('uni_verifycode', array('receiver' => $username));
			message(error(-1, '???????????????'), '', 'ajax');
		} else {

		}
		if (empty($mobile)) {
			message(error(-1, '??????????????????'), '', 'ajax');
		}
		if (!empty($reregister)) {
			if (empty($password) || empty($repassword)) {
				message(error(-1, '???????????????'), '', 'ajax');
			}
			if ($password !== $repassword) {
				message(error(-1, '???????????????'), '', 'ajax');
			}
		}
		$is_exist = pdo_fetch('SELECT uid FROM ' . tablename('mc_members') . ' WHERE uniacid = :uniacid AND mobile = :mobile AND uid != :uid', array(':uniacid' => $_W['uniacid'], ':mobile' => $mobile, ':uid' => $_W['member']['uid']));
		if(!empty($is_exist)) {
			message(error(-1, '?????????????????????'), '', 'ajax');
		} else {
			$salt = random(8);
			$password = md5($password . $salt . $_W['config']['setting']['authkey']);
			if (!empty($reregister)) {
				mc_update($_W['member']['uid'], array('mobile' => $mobile, 'email' => '', 'salt' => $salt, 'password' => $password));
			} else {
				mc_update($_W['member']['uid'], array('mobile' => $mobile));
			}
			message(error(0, '????????????'), url('mc/bond/mobile'), 'ajax');
		}
	}
}

if ($do == 'password') {
	$reregister = false;
	if ($_W['member']['email'] == md5($_W['openid']).'@HaiShengcms.com') {
		$reregister = true;
		message('????????????????????????', url('mc/bond/binding_account', array('type' => '1')), 'error');
	}
	if ($_W['isajax'] && $_W['ispost']) {
		if (empty($reregister) && !empty($profile['password'])) {	
			$oldpassword = trim($_GPC['oldpassword']);
			$oldpassword = md5($oldpassword . $profile['salt'] . $_W['config']['setting']['authkey']);
			$correct = pdo_get('mc_members', array('uid' => $_W['member']['uid'], 'password' => $oldpassword), array('uid'));
			if (empty($correct)) {
				message('??????????????????', '', 'error');
			}
		}
		$password = trim($_GPC['password']);
		if(empty($password) || strlen($password) < 6) {
			message('??????????????????6???', '', 'error');
		}
		$repassword = trim($_GPC['repassword']);
		if($password != $repassword) {
			message('???????????????????????????', '', 'error');
		}
		$salt = random(8);
		$password = md5($password . $salt . $_W['config']['setting']['authkey']);
		mc_update($_W['member']['uid'], array('salt' => $salt, 'password' => $password));
		message('??????????????????', url('mc/bond/settings'), 'success');
	}
}

if ($do == 'email') {
	$reregister = false;
	if ($_W['member']['email'] == md5($_W['openid']).'@HaiShengcms.com') {
		$reregister = true;
		message('????????????????????????', url('mc/bond/binding_account', array('type' => '1')), 'error');
	}
	if ($_W['isajax'] && $_W['ispost']) {
		$data = array();
		if (empty($_GPC['email'])) {
			message('?????????????????????', '', 'error');
		}
		$data['email'] = trim($_GPC['email']);
		$emailexists = pdo_get('mc_members', array('email' => $data['email'], 'uniacid' => $_W['uniacid'], 'uid <>' => $_W['member']['uid']), array('uid'));
		if (!empty($emailexists['uid'])) {
			message('????????????E-Mail????????????????????????????????????', '', 'error');
		}
		mc_update($profile['uid'], $data);
		message('??????????????????', url('mc/home'), 'success');
	}
}
if ($do == 'settings') {
	$reregister = false;
	if ($_W['member']['email'] == md5($_W['openid']).'@HaiShengcms.com') {
		$reregister = true;
	}
	$profile_hide = mc_card_settings_hide();
	$item = empty($setting['passport']['item']) ? 'random' : $setting['passport']['item'];
	$ltype = empty($setting['passport']['type']) ? 'hybird' : $setting['passport']['type'];
}
if ($do == 'binding_account') {
	$type = intval($_GPC['type']);
	$reregister = false;
	if ($_W['member']['email'] == md5($_W['openid']).'@HaiShengcms.com') {
		$reregister = true;
	}
	$item = empty($setting['passport']['item']) ? 'random' : $setting['passport']['item'];
	if ($_W['isajax'] && $_W['ispost']) {
		$username = trim($_GPC['username']);
		$password = trim($_GPC['password']);
		$data = array();
		if (empty($_GPC['username'])) {
			message('?????????????????????', '', 'error');
		}
		if (empty($_GPC['password'])) {
			message('?????????????????????', '', 'error');
		}
				if($item == 'email') {
			if (preg_match(REGULAR_EMAIL, $username)) {
				$data['email'] = $username;
			} else {
				message('?????????????????????', referer(), 'error');
			}
		} elseif($item == 'mobile') {
			if (preg_match(REGULAR_MOBILE, $username)) {
				$data['mobile'] = $username;
			} else {
				message('????????????????????????', referer(), 'error');
			}
		} else {
			if (preg_match(REGULAR_MOBILE, $username)) {
				$data['mobile'] = $username;
			} elseif (preg_match(REGULAR_EMAIL, $username)) {
				$data['email'] = $username;
			} else {
				message('?????????????????????', referer(), 'error');
			}
		}
		if ($type == '1') {
			if (!empty($data['email'])) {
				$userexists = pdo_get('mc_members', array('email' => $data['email'], 'uniacid' => $_W['uniacid'], 'uid <>' => $_W['member']['uid']), array('uid'));
			} elseif (!empty($data['mobile'])) {
				$userexists = pdo_get('mc_members', array('mobile' => $data['mobile'], 'uniacid' => $_W['uniacid'], 'uid <>' => $_W['member']['uid']), array('uid'));
				$data['email'] = '';
			}
			
			if (!empty($userexists['uid'])) {
				message('????????????????????????????????????????????????', '', 'error');
			}
			$hash = md5($password . $profile['salt'] . $_W['config']['setting']['authkey']);
			$data['salt'] = $salt;
			$data['password'] = $hash;
			mc_update($profile['uid'], $data);
			message('??????????????????', url('mc/home'), 'success');
		} else {
			if (!preg_match(REGULAR_EMAIL, $data['email'])) {
				message('?????????????????????', referer(), 'error');
			}
			if (!empty($reregister)) {
				$member = pdo_get('mc_members', array('uniacid' => $_W['uniacid'], 'email' => $data['email']), array('uid', 'salt', 'password'));
				if (empty($member)) {
					message('????????????????????????', '', 'error');
				}
				$hash = md5($_GPC['password'] . $member['salt'] . $_W['config']['setting']['authkey']);
				if ($member['password'] != $hash) {
					message('????????????????????????', '', 'error');
				}
				pdo_update('mc_mapping_fans', array('uid' => $member['uid']), array(
					'acid' => $_W['acid'],
					'openid' => $_W['openid'],
				));

								$member_old = mc_fetch($_W['member']['uid']);
				$member_new = mc_fetch($member['uid']);
				if (!empty($member_old) && !empty($member_new)) {
					$ignore = array('email', 'password', 'uid', 'uniacid', 'salt', 'credit1', 'credit2', 'credit3','credit4','credit5');
					$profile_update = array();
					foreach ($member_old as $key => $value) {
						if (!in_array($key, $ignore)) {
							if (empty($member_new[$key]) && !empty($member_old[$key])) {
								$profile_update[$key] = $member_old[$key];
							}
						}
					}
					$profile_update['credit1'] = $member_old['credit1'] + $member_new['credit1'];
					$profile_update['credit2'] = $member_old['credit2'] + $member_new['credit2'];
					$profile_update['credit3'] = $member_old['credit3'] + $member_new['credit3'];
					$profile_update['credit4'] = $member_old['credit4'] + $member_new['credit4'];
					$profile_update['credit5'] = $member_old['credit5'] + $member_new['credit5'];
					pdo_update('mc_members', $profile_update, array('uid' => $member['uid'], 'uniacid' => $_W['uniacid']));
					cache_build_memberinfo($member['uid']);
					pdo_delete('mc_members', array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
										pdo_update('coupon_record', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
					pdo_update('activity_exchange_trades', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
					pdo_update('activity_exchange_trades_shipping', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
										pdo_update('mc_credits_record', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
					pdo_update('mc_card_members', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
				}
				message('????????????????????????', url('mc/home'), 'success');
			}
		}
	}
}

template('mc/bond');
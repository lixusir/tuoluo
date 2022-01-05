<?php
class Login_WxShopPage extends Page
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$_W['uniacid'] = $_SESSION['__merch_uniacid'];
		$merch=p('merch');
		$set = $merch?$merch->getPluginsetByMerch('merch'):exit('Access denied');
		if ($_W['ispost']) 
		{
			$username = trim($_GPC['username']);
			$pwd = trim($_GPC['pwd']);
			if (empty($username)) 
			{
				show_json(0, '请输入用户名!');
			}
			if (empty($pwd)) 
			{
				show_json(0, '请输入密码!');
			}
			$account = pdo_fetch('select * from ' . tablename('wx_shop_merch_account') . ' where uniacid=:uniacid and username=:username limit 1', array(':uniacid' => $_W['uniacid'], ':username' => $username));
			if (empty($account)) 
			{
				show_json(0, '用户未找到!');
			}
//			$pwd = m('util')->pwd_encrypt($pwd, 'E'); //2018-11-07修改：商户密码可由用户申请时设定
            $pwd = md5($pwd . $account['salt']);
			if ($account['pwd'] != $pwd) 
			{
				show_json(0, '用户密码错误!');
			}
			$user = pdo_fetch('select status from ' . tablename('wx_shop_merch_user') . ' where uniacid=:uniacid and accountid=:accountid limit 1', array(':uniacid' => $_W['uniacid'], ':accountid' => $account['id']));
			if (!(empty($user))) 
			{
				if ($user['status'] == 2) 
				{
					show_json(0, '帐号暂停中,请联系管理员!');
				}
			}
			$account['hash'] = md5($account['pwd'] . $account['salt']);
			$session = base64_encode(json_encode($account));
			$session_key = '__merch_' . $account['uniacid'] . '_session';
			isetcookie($session_key, $session, 0, true);
			$status = array();
			$status['lastvisit'] = TIMESTAMP;
			$status['lastip'] = CLIENT_IP;
			pdo_update('wx_shop_merch_account', $status, array('id' => $account['id']));
			$url = $_W['siteroot'] . 'web/merchant.php?c=site&a=entry&i=' . $account['uniacid'] . '&m=wx_shop&do=web&r=shop';
			show_json(1, array('url' => $url));
		}
		$submitUrl = $_W['siteroot'] . 'web/merchant.php?c=site&a=entry&i=' . $_SESSION['__merch_uniacid'] . '&m=wx_shop&do=web&r=login';
		include $this->template('merch/manage/login');
	}
}
?>
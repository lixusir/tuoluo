<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'app/core/page_mobile.php';
// class Pay_WxShopPage extends PluginMobileLoginPage
class Pay_WxShopPage extends AppMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_GPC['openid'];
		// var_dump($openid);die;
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}

		$member = m('member')->getMember($openid, true);
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['orderid']);
		$teamid = intval($_GPC['teamid']);
		$order = pdo_fetch('select o.*,g.title,g.status as gstatus,g.deleted as gdeleted,g.stock from ' . tablename('wx_shop_groups_order') . " as o\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_goods') . " as g on g.id = o.goodid\r\n\t\t\t\twhere o.id = :id and o.uniacid = :uniacid order by o.createtime desc", array(':id' => $orderid, ':uniacid' => $uniacid));

		if (empty($order)) {
			// $this->message('订单未找到！', mobileUrl('groups/index'), 'error');
			show_json(0, '订单未找到！');
		}

		if (!empty($isteam) && ($order['success'] == -1)) {
			// $this->message('该活动已失效，请浏览其他商品或联系商家！', mobileUrl('groups/index'), 'error');
			show_json(0, '该活动已失效，请浏览其他商品或联系商家！');
		}

		if (empty($order['gstatus']) || !empty($order['gdeleted'])) {
			// $this->message($order['title'] . '<br/> 已下架!', mobileUrl('groups/index'), 'error');
			show_json(0, $order['title'] . '<br/> 已下架!');
		}

		if ($order['stock'] <= 0) {
			// $this->message($order['title'] . '<br/>库存不足!', mobileUrl('groups/index'), 'error');
			show_json(0, $order['title'] . '<br/>库存不足!');
		}

		if (!empty($teamid)) {
			$team_orders = pdo_fetchall('select * from ' . tablename('wx_shop_groups_order') . "\r\n\t\t\t\t\twhere teamid = :teamid and uniacid = :uniacid ", array(':teamid' => $teamid, ':uniacid' => $uniacid));

			foreach ($team_orders as $key => $value) {
				if ($team_orders && ($value['success'] == -1)) {
					// $this->message('该活动已过期，请浏览其他商品或联系商家！', mobileUrl('groups/index'), 'error');
					show_json(0, '该活动已过期，请浏览其他商品或联系商家！');
				}

				if ($team_orders && ($value['success'] == 1)) {
					// $this->message('该活动已结束，请浏览其他商品或联系商家！', mobileUrl('groups/index'), 'error');
					show_json(0, '该活动已结束，请浏览其他商品或联系商家！');
				}
			}

			$num = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_order') . ' as o where teamid = :teamid and status > :status and uniacid = :uniacid ', array(':teamid' => $teamid, ':status' => 0, ':uniacid' => $uniacid));

			if ($order['groupnum'] <= $num) {
				// $this->message('该活动已成功组团，请浏览其他商品或联系商家！', mobileUrl('groups/index'), 'error');
				show_json(0, '该活动已成功组团，请浏览其他商品或联系商家！');
			}
		}

		if (empty($order)) {
			// header('location: ' . mobileUrl('groups'));
			// exit();
			show_json(0, '参数错误');
		}

		if ($order['status'] == -1) {
			// header('location: ' . mobileUrl('groups/goods', array('id' => $order['goodid'])));
			// exit();
			show_json(0, '参数错误');
		}
		else {
			if (1 <= $order['status']) {
				// header('location: ' . mobileUrl('groups/goods', array('id' => $order['goodid'])));
				// exit();
				show_json(0, '参数错误');
			}
		}

		$log = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_groups_paylog') . "\r\n\t\t WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array(':uniacid' => $uniacid, ':module' => 'groups', ':tid' => $order['orderno']));
		if (!empty($log) && ($log['status'] != '0')) {
			// header('location: ' . mobileUrl('groups/goods', array('id' => $order['id'])));
			// exit();
			show_json(0, '参数错误');
		}

		if (empty($log)) {
			$log = array('uniacid' => $uniacid, 'openid' => $openid, 'module' => 'groups', 'tid' => $order['orderno'], 'credit' => $order['credit'], 'creditmoney' => $order['creditmoney'], 'fee' => ($order['price'] - $order['creditmoney']) + $order['freight'], 'status' => 0);
			pdo_insert('wx_shop_groups_paylog', $log);
			$plid = pdo_insertid();
		}

		$set = m('common')->getSysset(array('shop', 'pay'));
		$set['pay']['weixin'] = !empty($set['pay']['weixin_sub']) ? 1 : $set['pay']['weixin'];
		$set['pay']['weixin_jie'] = !empty($set['pay']['weixin_jie_sub']) ? 1 : $set['pay']['weixin_jie'];
		$sec = m('common')->getSec();
		$sec = iunserializer($sec['sec']);
		$param_title = $set['shop']['name'] . '订单';
		$credit = array('success' => false);
		if (isset($set['pay']) && ($set['pay']['credit'] == 1)) {
			if ($order['deductcredit2'] <= 0) {
				$credit = array('success' => true, 'current' => $member['credit2']);
			}
		}

		$wechat = array('success' => false);

		if (is_weixin()) {
			$params = array();
			$params['tid'] = $log['tid'];
			$params['user'] = $openid;
			$params['fee'] = $log['fee'];
			$params['title'] = $param_title;
			if (isset($set['pay']) && ($set['pay']['weixin'] == 1)) {
				load()->model('payment');
				$setting = uni_setting($_W['uniacid'], array('payment'));
				$options = array();
				if ( empty($_W['account']['key']) || empty($_W['account']['secret']) ) {
					$pay = pdo_fetch('select * from ' . tablename('wx_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
					$_W['account']['key'] = iunserializer($pay['sets'])['app']['appid'];
					$_W['account']['secret'] = iunserializer($pay['sets'])['app']['secret'];
				}
				
				if (is_array($setting['payment'])) {
					$options = $setting['payment']['wechat'];
					$options['appid'] = $_W['account']['key'];
					$options['secret'] = $_W['account']['secret'];
				}
				
				$payinfo = array('openid' => $openid, 'title' => $set['shop']['name'] . '订单', 'tid' => $params['tid'], 'fee' => $order['price']);
				$res = $this->model->wxpay($payinfo, 5);
				// var_dump($res);die;
				// array(4) { ["openid"]=> string(35) "sns_wa_o91fH5a4M0TTlJqEp0xC9D3kMoos" ["title"]=> string(20) "GZ一天科技订单" ["tid"]=> string(22) "SH20180730112449247125" ["fee"]=> string(6) "660.00" }
				// array(5) { ["nonceStr"]=> string(32) "h5Sau5YPdbHBSr5v9bYr6a2Bd6vWa95a" ["package"]=> string(46) "prepay_id=wx3011324456079289ef12135c3491172494" ["signType"]=> string(3) "MD5" ["timeStamp"]=> string(10) "1532921564" ["paySign"]=> string(32) "FB38CE52ADA139E1DDEB740A4D68467F" }
				// $wechat = m('common')->wechat_build($params, $options, 5);
				if (!(is_error($res))) {
					$wechat = array('success' => true, 'payinfo' => $res);

					if (!(empty($res['package'])) && strexists($res['package'], 'prepay_id=')) {
						$prepay_id = str_replace('prepay_id=', '', $res['package']);
						pdo_update('wx_shop_order', array('wxapp_prepay_id' => $prepay_id), array('id' => $orderid, 'uniacid' => $_W['uniacid']));
					}

				}
				 else {
					$wechat['payinfo'] = $res;
				}

				// if (!is_error($wechat)) {
				// 	$wechat['success'] = true;

				// 	if (!empty($wechat['code_url'])) {
				// 		$wechat['weixin_jie'] = true;
				// 	}
				// 	else {
				// 		$wechat['weixin'] = true;
				// 	}
				// }
			}

			if (isset($set['pay']) && ($set['pay']['weixin_jie'] == 1) && !$wechat['success']) {
				$params['tid'] = $params['tid'] . '_borrow';
				$options = array();
				$options['appid'] = $sec['appid'];
				$options['mchid'] = $sec['mchid'];
				$options['apikey'] = $sec['apikey'];
				if (!empty($set['pay']['weixin_jie_sub']) && !empty($sec['sub_secret_jie_sub'])) {
					$wxuser = m('member')->wxuser($sec['sub_appid_jie_sub'], $sec['sub_secret_jie_sub']);
					$params['openid'] = $wxuser['openid'];
				}
				else {
					if (!empty($sec['secret'])) {
						$wxuser = m('member')->wxuser($sec['appid'], $sec['secret']);
						$params['openid'] = $wxuser['openid'];
					}
				}
				
				$wechat = m('common')->wechat_native_build($params, $options, 5);

				if (!is_error($wechat)) {
					$wechat['success'] = true;

					if (!empty($params['openid'])) {
						$wechat['weixin'] = true;
					}
					else {
						$wechat['weixin_jie'] = true;
					}
				}
			}
			$cash = array('success' => ($order['cash'] == 1) && isset($set['pay']) && ($set['pay']['cash'] == 1) && ($order['isverify'] == 0) && ($order['isvirtual'] == 0));
			$alipay = array('success' => false);

			if (!(empty($set['pay']['nativeapp_alipay'])) && (0 < $order['price']) && !($this->iswxapp)) {
				$params = array('out_trade_no' => $log['tid'], 'total_amount' => $order['price'], 'subject' => $set['shop']['name'] . '订单', 'body' => $_W['uniacid'] . ':0:NATIVEAPP');
				$sec = m('common')->getSec();
				$sec = iunserializer($sec['sec']);
				$alipay_config = $sec['nativeapp']['alipay'];

				if (!(empty($alipay_config))) {
					$res = $this->model->alipay_build($params, $alipay_config);
					$alipay = array('success' => true, 'payinfo' => $res);
				}
			}

		}

		$payinfo = array(
			'order'  => array('id' => $order['id'], 'orderno' => $order['orderno'], 'price' => $order['price'], 'title' => $set['shop']['name'] . '订单'),
			'teamid' => $teamid,
			'credit' => $credit,
			'wechat' => $wechat,
			'money' => $log['fee'],
			'alipay' => $alipay,
			'cash'   => $cash
		);

		if (is_h5app()) {
			$payinfo = array('wechat' => !empty($sec['app_wechat']['merchname']) && !empty($set['pay']['app_wechat']) && !empty($sec['app_wechat']['appid']) && !empty($sec['app_wechat']['appsecret']) && !empty($sec['app_wechat']['merchid']) && !empty($sec['app_wechat']['apikey']) && (0 < $order['price']) ? true : false, 'alipay' => false, 'mcname' => $sec['app_wechat']['merchname'], 'ordersn' => $order['orderno'], 'money' => $log['fee'], 'attach' => $_W['uniacid'] . ':5', 'type' => 5, 'orderid' => $orderid, 'credit' => $credit, 'teamid' => $teamid);
		}
		show_json(1, $payinfo);
		// include $this->template();
	}

	public function complete()
	{
		global $_W;
		global $_GPC;
		$orderid = intval($_GPC['orderid']);
		$teamid = intval($_GPC['teamid']);
		$isteam = intval($_GPC['isteam']);
		$uniacid = $_W['uniacid'];
		$openid = $_GPC['openid'];

		if (is_h5app() && empty($orderid)) {
			$ordersn = $_GPC['ordersn'];
			$orderid = pdo_fetchcolumn('select id from ' . tablename('wx_shop_groups_order') . ' where orderno=:orderno and uniacid=:uniacid and openid=:openid limit 1', array(':orderno' => $ordersn, ':uniacid' => $uniacid, ':openid' => $openid));
		}

		if (empty($orderid)) {
			if ($_W['ispost']) {
				show_json(0, '参数错误!');
			}
			else {
				// $this->message('参数错误!', mobileUrl('groups/orders'));
				show_json(0, '参数错误!');
			}
		}

		$order = pdo_fetch('select * from ' . tablename('wx_shop_groups_order') . ' where id = :orderid and uniacid=:uniacid and openid=:openid', array(':orderid' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));

		if (empty($order)) {
			if ($_W['ispost']) {
				show_json(0, '订单不存在!');
			}
			else {
				// $this->message('参数错误!', mobileUrl('groups/orders'));
				show_json(0, '参数错误!');
			}
		}

		$order_goods = pdo_fetch('select * from  ' . tablename('wx_shop_groups_goods') . "\r\n\t\t\t\t\twhere id = :id and uniacid=:uniacid ", array(':uniacid' => $_W['uniacid'], ':id' => $order['goodid']));

		if (empty($order_goods)) {
			if ($_W['ispost']) {
				show_json(0, '商品不存在!');
			}
			else {
				// $this->message('商品不存在!', mobileUrl('groups/orders'));
				show_json(0, '商品不存在!');
			}
		}

		$type = $_GPC['type'];

		if (!in_array($type, array('wechat', 'alipay', 'credit', 'cash'))) {
			if ($_W['ispost']) {
				show_json(0, '未找到支付方式!');
			}
			else {
				// $this->message('未找到支付方式!', mobileUrl('groups/orders'));
				show_json(0, '未找到支付方式!');
			}
		}

		$log = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_groups_paylog') . "\r\n\t\t WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array(':uniacid' => $uniacid, ':module' => 'groups', ':tid' => $order['orderno']));

		if (empty($log)) {
			if ($_W['ispost']) {
				show_json(0, '支付出错,请重试(0)!');
			}
			else {
				// $this->message('支付出错,请重试!', mobileUrl('groups/orders'));
				show_json(0, '支付出错,请重试(0)!');
			}
		}

		if ($type == 'credit') {
			$orderno = $order['orderno'];
			$credits = m('member')->getCredit($openid, 'credit2');
			if (($credits < $log['fee']) || ($credits < 0)) {
				show_json($credits, '余额不足,请充值');
			}

			$fee = floatval($log['fee']);
			$result = m('member')->setCredit($openid, 'credit2', 0 - $fee, array($_W['member']['uid'], $_W['shopset']['shop']['name'] . '消费' . $fee));

			if (is_error($result)) {
				if ($_W['ispost']) {
					show_json(0, $result['message']);
				}
				else {
					// $this->message($result['message'], mobileUrl('groups/orders'));
					show_json(0, $result['message']);
				}
			}
			// var_dump();die;
			
			// load()->model('demo');
			// $this->model->payResult($log['tid'], $type);
			p('groups')->payResult($log['tid'], $type);
			pdo_update('wx_shop_groups_order', array('pay_type' => 'credit', 'status' => 1, 'paytime' => time(), 'starttime' => time()), array('id' => $orderid));

			if ($_W['ispost']) {
				show_json(1);
			}
			else {
				// header('location: ' . mobileUrl('groups/team/detail', array('orderid' => $orderid, 'teamid' => $orderid)));
				// exit();
				show_json(1, array('message'=>'groups/team/detail', 'orderid' => $orderid, 'teamid' => $orderid));
			}
		}
		else {
			if ($type == 'wechat') {
				$orderno = $order['orderno'];

				if (!empty($order['ordersn2'])) {
					$orderno .= 'GJ' . sprintf('%02d', $order['ordersn2']);
				}

				$payquery = m('finance')->isWeixinPay($orderno, $log['fee'], is_h5app() ? true : false);
				$payqueryBorrow = m('finance')->isWeixinPayBorrow($orderno, $log['fee']);
				if (!is_error($payquery) || !is_error($payqueryBorrow)) {
					// $this->model->payResult($log['tid'], $type, is_h5app() ? true : false);
					p('groups')->payResult($log['tid'], $type, is_h5app() ? true : false);
					pdo_update('wx_shop_groups_order', array('pay_type' => 'wechat', 'status' => 1, 'paytime' => time(), 'starttime' => time(), 'apppay' => is_h5app() ? 1 : 0), array('id' => $orderid));

					if ($_W['ispost']) {
						show_json(1);
					}
					else {
						// header('location: ' . mobileUrl('groups/team/detail', array('orderid' => $orderid, 'teamid' => $orderid)));
						// exit();
						show_json(1, array('message'=>'groups/team/detail', 'orderid' => $orderid, 'teamid' => $orderid));
					}
				}
				else if ($_W['ispost']) {
					show_json(0, '支付出错,请重试(1)!');
				}
				else {
					// $this->message('支付出错,请重试!', mobileUrl('groups/orders'));
					show_json(0, '支付出错,请重试(1)!');
				}
			}
		}
	}

	public function orderstatus()
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['id']);
		$order = pdo_fetch('select status from ' . tablename('wx_shop_groups_order') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
		show_json(1, array('order'=>$order));
	}
}

?>

<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Orders_WxShopPage //extends PluginMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}

		// $this->model->groupsShare();
		// include $this->template();
		show_json(1);
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['orderid']);
		$teamid = intval($_GPC['teamid']);
		$condition = ' and openid=:openid  and uniacid=:uniacid and id = :orderid and teamid = :teamid ';
		$order = pdo_fetch('select * from ' . tablename('wx_shop_groups_order') . "\r\n\t\t\t\twhere openid=:openid  and uniacid=:uniacid and id = :orderid and teamid = :teamid order by createtime desc ", array(':uniacid' => $uniacid, ':openid' => $openid, ':orderid' => $orderid, ':teamid' => $teamid));
		// var_dump($order);die;
		$good = pdo_fetch('select * from ' . tablename('wx_shop_groups_goods') . "\r\n\t\t\t\t\twhere id = :id and status = :status and uniacid = :uniacid and deleted = 0 order by displayorder desc", array(':id' => $order['goodid'], ':uniacid' => $uniacid, ':status' => 1));

		if (!empty($order['isverify'])) {
			$storeids = array();
			$merchid = 0;

			if (!empty($good['storeids'])) {
				$merchid = $good['merchid'];
				$storeids = array_merge(explode(',', $good['storeids']), $storeids);
			}

			if (empty($storeids)) {
				if (0 < $merchid) {
					$stores = pdo_fetchall('select * from ' . tablename('wx_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
				}
				else {
					$stores = pdo_fetchall('select * from ' . tablename('wx_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
				}
			}
			else if (0 < $merchid) {
				$stores = pdo_fetchall('select * from ' . tablename('wx_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
			}
			else {
				$stores = pdo_fetchall('select * from ' . tablename('wx_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
			}

			$verifytotal = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_verify') . ' where orderid = :orderid and openid = :openid and uniacid = :uniacid and verifycode = :verifycode ', array(':orderid' => $order['id'], ':openid' => $order['openid'], ':uniacid' => $order['uniacid'], ':verifycode' => $order['verifycode']));

			if ($order['verifytype'] == 0) {
				$verify = pdo_fetch('select isverify from ' . tablename('wx_shop_groups_verify') . ' where orderid = :orderid and openid = :openid and uniacid = :uniacid and verifycode = :verifycode ', array(':orderid' => $order['id'], ':openid' => $order['openid'], ':uniacid' => $order['uniacid'], ':verifycode' => $order['verifycode']));
			}

			$verifynum = $order['verifynum'] - $verifytotal;

			if ($verifynum < 0) {
				$verifynum = 0;
			}
		}
		else {
			$address = false;

			if (!empty($order['addressid'])) {
				$address = iunserializer($order['address']);

				if (!is_array($address)) {
					$address = pdo_fetch('select * from  ' . tablename('wx_shop_member_address') . ' where id=:id limit 1', array(':id' => $order['addressid']));
				}
			}
		}

		$carrier = @iunserializer($order['carrier']);
		if (!is_array($carrier) || empty($carrier)) {
			$carrier = false;
		}
		// $this->model->groupsShare();
		// include $this->template();
		$order_amount_including_freight = ($order['price'] - $order['creditmoney'] + $order['freight']);
		$good['thumb'] = tomedia($good['thumb']);

		$merchandise_subtotal = number_format($order['price']+$order['discount'],2);
		$head_of_the_preferential = number_format($order['discount'],2);
		$real_payment_including_freight = number_format(($order['price'] - $order['creditmoney'] + $order['freight']),2);
		$order['createtime'] = date('Y-m-d H:i:s', $order['createtime']);
		$order['paytime'] = date('Y-m-d H:i:s', $order['paytime']);
		$order['sendtime'] = date('Y-m-d H:i:s', $order['sendtime']);
		$order['finishtime'] = date('Y-m-d H:i:s', $order['finishtime']);

		show_json(1, array('order' => $order, 'good'=>$good, 'address'=>$address, 'stores' => $stores, 'verifynum' => $verifynum, 'verify'=>$verify, 'carrier'=>$carrier, 'order_amount_including_freight'=>$order_amount_including_freight, 'merchandise_subtotal'=>$merchandise_subtotal ,'head_of_the_preferential'=>$head_of_the_preferential, 'real_payment_including_freight'=>$real_payment_including_freight));
	}

	public function express()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['id']);

		if (empty($orderid)) {
			// header('location: ' . mobileUrl('groups/orders'));
			// exit();
			show_json(0, 'orderid为空');
		}

		$order = pdo_fetch('select * from ' . tablename('wx_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));

		if (empty($order)) {
			// header('location: ' . mobileUrl('groups/order'));
			show_json(0, '订单未找到');
		}

		if (empty($order['addressid'])) {
			// $this->message('订单非快递单，无法查看物流信息!');
			show_json(0, '订单非快递单，无法查看物流信息!');
		}

		if ($order['status'] < 2) {
			// $this->message('订单未发货，无法查看物流信息!');
			show_json(0, '订单未发货，无法查看物流信息!');
		}

		$goods = pdo_fetch('select *  from ' . tablename('wx_shop_groups_goods') . '  where id=:id and uniacid=:uniacid ', array(':uniacid' => $uniacid, ':id' => $order['goodid']));
		$expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);
		// include $this->template();
		$goods['thumb'] =  tomedia($goods['thumb']);
		$ifstrexists =  strexists($expresslist[0]['step'],'已签收');
		$count_expresslist =  count($expresslist);
		show_json(1, array('goods'=>$goods, 'expresslist'=>$expresslist, 'ifstrexists'=>$ifstrexists, 'count_expresslist'=>$count_expresslist));
	}

	/**
	 * 取消订单
	 * @global type $_W
	 * @global type $_GPC
	 */
	public function cancel()
	{
		global $_W;
		global $_GPC;

		try {
			$_W['openid'] = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
			$orderid = intval($_GPC['id']);
			$order = pdo_fetch('select id,orderno,openid,status,credit,teamid,groupnum,creditmoney,price,freight,pay_type,discount,success from ' . tablename('wx_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));

			$total = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_order') . '  where teamid = :teamid  ', array(':teamid' => $order['teamid']));

			if (empty($order)) {
				show_json(0, '订单未找到');
			}

			if ($order['status'] != 0) {
				show_json(0, '订单不能取消');
			}

			pdo_update('wx_shop_groups_order', array('status' => -1, 'canceltime' => time()), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
			p('groups')->sendTeamMessage($orderid);
			show_json(1);
		}
		catch (Exception $e) {
			// throw new $e->getMessage();
			show_json(0, array('error'=>$e->getMessage()));
		}
	}

	/**
	 * 删除订单
	 * @global type $_W
	 * @global type $_GPC
	 */
	public function delete()
	{
		global $_W;
		global $_GPC;
		$orderid = intval($_GPC['id']);
		$_W['openid'] = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
		$order = pdo_fetch('select id,status from ' . tablename('wx_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));

		if (empty($order)) {
			show_json(0, '订单未找到!');
		}

		if (($order['status'] != 3) && ($order['status'] != -1)) {
			show_json(0, '无法删除');
		}

		pdo_update('wx_shop_groups_order', array('deleted' => 1), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
		show_json(1);
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$list = array();
		$openid = $_W['openid'] ? $_W['openid'] :$_GPC['openid'];

		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}

		$uniacid = $_W['uniacid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 5;
		$status = $_GPC['status'];

		if ($status == 0) {
			$tab_all = true;
			$condition = ' and o.openid=:openid  and o.uniacid=:uniacid and o.deleted = :deleted ';
			$params = array(':uniacid' => $uniacid, ':openid' => $openid, ':deleted' => 0);
		}
		else {
			$condition = ' and o.openid=:openid  and o.uniacid=:uniacid and o.status = :status and o.deleted = :deleted  ';
			$params = array(':uniacid' => $uniacid, ':openid' => $openid, ':deleted' => 0);

			if ($status == 1) {
				$tab0 = true;
				$params[':status'] = 0;
			}
			else if ($status == 2) {
				$tab1 = true;
				$condition = ' and o.openid=:openid  and o.uniacid=:uniacid and o.deleted = :deleted and o.status = :status and (o.is_team = 0 or o.success = 1) ';
				$params[':status'] = 1;
			}
			else if ($status == 3) {
				$tab2 = true;
				$params[':status'] = 2;
			}
			else {
				if ($status == 4) {
					$tab3 = true;
					$params[':status'] = 3;
				}
			}
		}

		$orders = pdo_fetchall("select o.id,o.orderno,o.createtime,o.price,o.freight,o.creditmoney,o.goodid,o.teamid,o.status,o.is_team,o.success,o.teamid,o.openid,\r\n\t\t\t\tg.title,g.thumb,g.units,g.goodsnum,g.groupsprice,g.singleprice,o.verifynum,o.verifytype,o.isverify,o.uniacid,o.verifycode\r\n\t\t\t\tfrom " . tablename('wx_shop_groups_order') . " as o\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_goods') . " as g on g.id = o.goodid\r\n\t\t\t\twhere 1 " . $condition . ' order by o.createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);

		$total = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_order') . ' as o where 1 ' . $condition, $params);

		foreach ($orders as $key => $value) {
			$verifytotal = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_verify') . ' where orderid = :orderid and openid = :openid and uniacid = :uniacid and verifycode = :verifycode ', array(':orderid' => $value['id'], ':openid' => $value['openid'], ':uniacid' => $value['uniacid'], ':verifycode' => $value['verifycode']));

			if (!$verifytotal) {
				$verifytotal = 0;
			}

			$orders[$key]['vnum'] = $value['verifynum'] - intval($verifytotal);
			$orders[$key]['amount'] = ($value['price'] + $value['freight']) - $value['creditmoney'];
			$statuscss = 'text-cancel';

			switch ($value['status']) {
			case '-1':
				$status = '已取消';
				break;

			case '0':
				$status = '待付款';
				$statuscss = 'text-cancel';
				break;

			case '1':
				if (($value['is_team'] == 0) || ($value['success'] == 1)) {
					$status = '待发货';
					$statuscss = 'text-warning';
				}
				else {
					$status = '已付款';
					$statuscss = 'text-success';
				}

				break;

			case '2':
				$status = '待收货';
				$statuscss = 'text-danger';
				break;

			case '3':
				$status = '已完成';
				$statuscss = 'text-success';
				break;
			}

			$orders[$key]['statusstr'] = $status;
			$orders[$key]['statuscss'] = $statuscss;
		}

		$orders = set_medias($orders, 'thumb');
		show_json(1, array('list' => $orders, 'pagesize' => $psize, 'total' => $total));
	}

	public function confirm()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];	

		$uniacid = $_W['uniacid'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}

		$isverify = false;
		$goodid = intval($_GPC['id']);
		$type = $_GPC['type'];
		$heads = intval($_GPC['heads']);
		$teamid = intval($_GPC['teamid']);
		$member = m('member')->getMember($openid, true);
		$credit = array();
		$goods = pdo_fetch('select * from ' . tablename('wx_shop_groups_goods') . "\r\n\t\t\t\twhere id = :id and uniacid = :uniacid and deleted = 0 order by displayorder desc", array(':id' => $goodid, ':uniacid' => $uniacid));

		if ($goods['stock'] <= 0) {
			// $this->message('您选择的商品已经下架，请浏览其他商品或联系商家！');
			show_json(0, '您选择的商品已经下架，请浏览其他商品或联系商家！');
		}

		$follow = m('user')->followed($openid);
		if (!empty($goods['followneed']) && !$follow && is_weixin()) {
			$followtext = (empty($goods['followtext']) ? '如果您想要购买此商品，需要您关注我们的公众号，点击【确定】关注后再来购买吧~' : $goods['followtext']);
			$followurl = (empty($goods['followurl']) ? $_W['shopset']['share']['followurl'] : $goods['followurl']);
			// $this->message($followtext, $followurl, 'error');
			show_json(0, $followtext);
		}

		$ordernum = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_order') . " as o\r\n\t\t\twhere openid = :openid and status >= :status and goodid = :goodid and uniacid = :uniacid ", array(':openid' => $openid, ':status' => 0, ':goodid' => $goodid, ':uniacid' => $uniacid));
		if (!empty($goods['purchaselimit']) && ($goods['purchaselimit'] <= $ordernum)) {
			// $this->message('您已到达此商品购买上限，请浏览其他商品或联系商家！');
			show_json(0, '您已到达此商品购买上限，请浏览其他商品或联系商家！');
		}

		$order = pdo_fetch('select * from ' . tablename('wx_shop_groups_order') . "\r\n\t\t\t\t\twhere goodid = :goodid and status >= 0 and is_team = 1 and openid = :openid and uniacid = :uniacid and success = 0 and deleted = 0 ", array(':goodid' => $goodid, ':openid' => $openid, ':uniacid' => $uniacid));
		if ($order && ($order['status'] == 0)) {
			// $this->message('您的订单已存在，请尽快完成支付！');
			show_json(0, '您的订单已存在，请尽快完成支付！');
		}

		if ($order && ($order['status'] == 1)) {
			// $this->message('您已经参与了该团，请等待拼团结束后再进行购买！');
			show_json(0, '您已经参与了该团，请等待拼团结束后再进行购买！');
		}

		if ($order && ($order['groupnum'] <= $ordernum)) {
			// $this->message('该团人数已达上限，请浏览其他商品或联系商家！');
			show_json(0, '该团人数已达上限，请浏览其他商品或联系商家！');
		}

		if (!empty($teamid)) {
			$orders = pdo_fetchall('select * from ' . tablename('wx_shop_groups_order') . "\r\n\t\t\t\t\twhere teamid = :teamid and uniacid = :uniacid ", array(':teamid' => $teamid, ':uniacid' => $uniacid));

			foreach ($orders as $key => $value) {
				if ($orders && ($value['success'] == -1)) {
					// $this->message('该活动已过期，请浏览其他商品或联系商家！');
					show_json(0, '该活动已过期，请浏览其他商品或联系商家！');
				}

				if ($orders && ($value['success'] == 1)) {
					// $this->message('该活动已结束，请浏览其他商品或联系商家！');
					show_json(0, '该活动已结束，请浏览其他商品或联系商家！');
				}
			}

			$num = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_order') . ' as o where teamid = :teamid and status > :status and goodid = :goodid and uniacid = :uniacid ', array(':teamid' => $teamid, ':status' => 0, ':goodid' => $goods['id'], ':uniacid' => $uniacid));

			if ($num == $goods['groupnum']) {
				// $this->message('该活动已成功组团，请浏览其他商品或联系商家！');
				show_json(0, '该活动已成功组团，请浏览其他商品或联系商家！');
			}
		}

		if ($type == 'groups') {
			$goodsprice = $goods['groupsprice'];
			$price = $goods['groupsprice'];
			$groupnum = intval($goods['groupnum']);
			$is_team = 1;
		}
		else {
			if ($type == 'single') {
				$goodsprice = $goods['singleprice'];
				$price = $goods['singleprice'];
				$groupnum = 1;
				$is_team = 0;
				$teamid = 0;
			}
		}

		$set = pdo_fetch('select discount,headstype,headsmoney,headsdiscount from ' . tablename('wx_shop_groups_set') . "\r\n\t\t\t\t\twhere uniacid = :uniacid ", array(':uniacid' => $uniacid));
		if (!empty($set['discount']) && ($heads == 1)) {
			if (!empty($goods['discount'])) {
				if (empty($goods['headstype'])) {
				}
				else {
					if (0 < $goods['headsdiscount']) {
						$goods['headsmoney'] = $goods['groupsprice'] - price_format(($goods['groupsprice'] * $goods['headsdiscount']) / 100, 2);
					}
				}
			}
			else {
				if (empty($set['headstype'])) {
					$goods['headsmoney'] = $set['headsmoney'];
				}
				else {
					if (0 < $set['headsdiscount']) {
						$goods['headsmoney'] = $goods['groupsprice'] - price_format(($goods['groupsprice'] * $set['headsdiscount']) / 100, 2);
					}
				}

				$goods['headstype'] = $set['headstype'];
				$goods['headsdiscount'] = $set['headsdiscount'];
			}

			if ($goods['groupsprice'] < $goods['headsmoney']) {
				$goods['headsmoney'] = $goods['groupsprice'];
			}

			$price = $price - $goods['headsmoney'];

			if ($price < 0) {
				$price = 0;
			}
		}
		else {
			$goods['headsmoney'] = 0;
		}

		if (!empty($goods['isverify'])) {
			$isverify = true;
			$goods['freight'] = 0;
			$storeids = array();
			$merchid = 0;

			if (!empty($goods['storeids'])) {
				$merchid = $goods['merchid'];
				$storeids = array_merge(explode(',', $goods['storeids']), $storeids);
			}

			if (empty($storeids)) {
				if (0 < $merchid) {
					$stores = pdo_fetchall('select * from ' . tablename('wx_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
				}
				else {
					$stores = pdo_fetchall('select * from ' . tablename('wx_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
				}
			}
			else if (0 < $merchid) {
				$stores = pdo_fetchall('select * from ' . tablename('wx_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
			}
			else {
				$stores = pdo_fetchall('select * from ' . tablename('wx_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
			}

			$verifycode = 'PT' . random(8, true);

			while (1) {
				$count = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_groups_order') . ' where verifycode=:verifycode and uniacid=:uniacid limit 1', array(':verifycode' => $verifycode, ':uniacid' => $_W['uniacid']));

				if ($count <= 0) {
					break;
				}

				$verifycode = 'PT' . random(8, true);
			}

			$verifynum = (!empty($goods['verifytype']) ? $verifynum = $goods['verifynum'] : 1);
		}
		else {
			$address = pdo_fetch('select * from ' . tablename('wx_shop_member_address') . "\r\n\t\t\t\twhere openid=:openid and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1", array(':uniacid' => $uniacid, ':openid' => $openid));
		}

		$creditdeduct = pdo_fetch('SELECT creditdeduct,groupsdeduct,credit,groupsmoney FROM' . tablename('wx_shop_groups_set') . 'WHERE uniacid = :uniacid ', array(':uniacid' => $uniacid));

		if (intval($creditdeduct['creditdeduct'])) {
			if (intval($creditdeduct['groupsdeduct'])) {
				if (0 < $goods['deduct']) {
					$credit['deductprice'] = round(intval($member['credit1']) * $creditdeduct['groupsmoney'], 2);

					if ($price <= $credit['deductprice']) {
						$credit['deductprice'] = $price;
					}

					if ($goods['deduct'] <= $credit['deductprice']) {
						$credit['deductprice'] = $goods['deduct'];
					}

					$credit['credit'] = floor($credit['deductprice'] / $creditdeduct['groupsmoney']);

					if ($credit['credit'] < 1) {
						$credit['credit'] = 0;
						$credit['deductprice'] = 0;
					}

					$credit['deductprice'] = $credit['credit'] * $creditdeduct['groupsmoney'];
				}
				else {
					$credit['deductprice'] = 0;
				}
			}
			else {
				$sys_data = m('common')->getPluginset('sale');

				if (0 < $goods['deduct']) {
					$credit['deductprice'] = round(intval($member['credit1']) * $sys_data['money'], 2);

					if ($price <= $credit['deductprice']) {
						$credit['deductprice'] = $price;
					}

					if ($goods['deduct'] <= $credit['deductprice']) {
						$credit['deductprice'] = $goods['deduct'];
					}

					$credit['credit'] = floor($credit['deductprice'] / $sys_data['money']);

					if ($credit['credit'] < 1) {
						$credit['credit'] = 0;
						$credit['deductprice'] = 0;
					}

					$credit['deductprice'] = $credit['credit'] * $sys_data['money'];
				}
				else {
					$credit['deductprice'] = 0;
				}
			}
		}

		$ordersn = m('common')->createNO('groups_order', 'orderno', 'PT');
		if ($_W['ispost']) {
			if (empty($_GPC['aid']) && !$isverify) {
				// header('location: ' . mobileUrl('groups/address/post'));
				// exit();
				show_json(0, '地址不能为空！');
			}

			if ($isverify) {
				if (empty($_GPC['realname']) || empty($_GPC['mobile'])) {
					// $this->message('联系人或联系电话不能为空！');
					show_json(0, '联系人或联系电话不能为空！');
				}
			}
			// show_json(1, array('1'=>$goods ));	
			// var_dump(tablename('wx_shop_member_address'));die;
			if ((0 < intval($_GPC['aid'])) && !$isverify) {
				$order_address = pdo_fetch('select * from ' . tablename('wx_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid limit 1', array(':id' => intval($_GPC['aid']), ':openid' => $openid, ':uniacid' => $uniacid));
				if (empty($order_address)) {
					// $this->message('未找到地址');
					// header('location: ' . mobileUrl('groups/address/post'));
					// exit();
					show_json(0, '未找到地址');
				}
				else {
					if (empty($order_address['province']) || empty($order_address['city'])) {
						// $this->message('地址请选择省市信息');
						// header('location: ' . mobileUrl('groups/address/post'));
						// exit();
						show_json(0, '地址请选择省市信息');
					}
				}
			}

			$data = array('uniacid' => $_W['uniacid'], 'groupnum' => $groupnum, 'openid' => $openid, 'paytime' => '', 'orderno' => $ordersn, 'credit' => intval($_GPC['isdeduct']) ? $_GPC['credit'] : 0, 'creditmoney' => intval($_GPC['isdeduct']) ? $_GPC['creditmoney'] : 0, 'price' => $price, 'freight' => $goods['freight'], 'status' => 0, 'goodid' => $goodid, 'teamid' => $teamid, 'is_team' => $is_team, 'heads' => $heads, 'discount' => !empty($heads) ? $goods['headsmoney'] : 0, 'addressid' => intval($_GPC['aid']), 'address' => iserializer($order_address), 'message' => trim($_GPC['message']), 'realname' => $isverify ? trim($_GPC['realname']) : '', 'mobile' => $isverify ? trim($_GPC['mobile']) : '', 'endtime' => $goods['endtime'], 'isverify' => intval($goods['isverify']), 'verifytype' => intval($goods['verifytype']), 'verifycode' => !empty($verifycode) ? $verifycode : 0, 'verifynum' => !empty($verifynum) ? $verifynum : 1, 'createtime' => TIMESTAMP);
			$order_insert = pdo_insert('wx_shop_groups_order', $data);

			if (!$order_insert) {
				// $this->message('生成订单失败！');
				show_json(0, '生成订单失败！');
			}

			$orderid = pdo_insertid();
			if (empty($teamid) && ($type == 'groups')) {
				pdo_update('wx_shop_groups_order', array('teamid' => $orderid), array('id' => $orderid));
			}

			$order = pdo_fetch('select * from ' . tablename('wx_shop_groups_order') . "\r\n\t\t\t\t\t\twhere id = :id and uniacid = :uniacid ", array(':id' => $orderid, ':uniacid' => $uniacid));
			// header('location: ' . MobileUrl('groups/pay', array('teamid' => empty($teamid) ? $order['teamid'] : $teamid, 'orderid' => $orderid)));
			show_json(1,  array('teamid' => empty($teamid) ? $order['teamid'] : $teamid, 'orderid' => $orderid));
		}

		$number_format_arr = '';
		$number_format_arr['freight'] = number_format($goods['freight'],2);
		$number_format_arr['preferential'] =  number_format($price+$goods['freight'],2);
		$number_format_arr['Promotional_offers'] = number_format($isdiscountprice,2);
		$number_format_arr['headsmoney'] = number_format($goods['headsmoney'],2);
		$number_format_arr['headsdiscount'] = number_format($goods['headsdiscount'] / 10,1);
		$number_format_arr['deductprice'] = number_format($credit['deductprice'],2);
		$goods['thumb'] = tomedia($goods['thumb']);
		// $this->model->groupsShare();
		// include $this->template();
		show_json(1, array('goods'=>$goods, 'is_team'=>$is_team, 'heads'=>$heads, 'set'=>$set, 'price'=>$price, 'number_format_arr'=>$number_format_arr, 'isverify' => $isverify, 'address'=>$address, 'teamid'=>$teamid, 'stores' => $stores, 'credit'=>$credit, 'creditdeduct'=>$creditdeduct, 'credit1' => $member['credit1'], 'realname' => $member['realname'], 'mobile' => $member['mobile']));
	}

	/**
	 * 确认收货
	 * @global type $_W
	 * @global type $_GPC
	 */
	public function finish()
	{
		global $_W;
		global $_GPC;
		$_W['openid'] = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
		$orderid = intval($_GPC['id']);
		$order = pdo_fetch('select * from ' . tablename('wx_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));

		if (empty($order)) {
			show_json(0, '订单未找到');
		}

		if ($order['status'] != 2) {
			show_json(0, '订单不能确认收货');
		}

		if ((0 < $order['refundstate']) && !empty($order['refundid'])) {
			$change_refund = array();
			$change_refund['refundstatus'] = -2;
			$change_refund['refundtime'] = time();
			pdo_update('wx_shop_groups_order_refund', $change_refund, array('id' => $order['refundid'], 'uniacid' => $_W['uniacid']));
		}

		pdo_update('wx_shop_groups_order', array('status' => 3, 'finishtime' => time(), 'refundstate' => 0), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
		p('groups')->sendTeamMessage($orderid);
		show_json(1);
	}
}

?>

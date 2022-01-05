<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Team_WxShopPage //extends PluginMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_GPC['openid'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}

		// $this->model->groupsShare();
		// include $this->template();
		show_json(1);
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$openid = $_GPC['openid'];
		$uniacid = $_W['uniacid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 5;
		$success = intval($_GPC['success']);
		$condition = ' and o.openid=:openid and o.uniacid=:uniacid and o.is_team = 1 and o.paytime > 0 and o.deleted = 0 ';
		$params = array(':uniacid' => $uniacid, ':openid' => $openid);

		if ($success == 0) {
			$tab0 = true;
			$condition .= ' and o.success = :success ';
			$params[':success'] = $success;
		}
		else if ($success == 1) {
			$tab1 = true;
			$condition .= ' and o.success = :success ';
			$params[':success'] = $success;
		}
		else {
			if ($success == -1) {
				$tab2 = true;
				$condition .= ' and o.success = :success ';
				$params[':success'] = $success;
			}
		}

		$orders = pdo_fetchall('select o.*,g.title,g.price as gprice,g.groupsprice,g.thumb,g.units,g.goodsnum from ' . tablename('wx_shop_groups_order') . " as o\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_goods') . " as g on g.id = o.goodid\r\n\t\t\t\twhere 1 " . $condition . ' order by o.createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_groups_order') . ' as o where 1 ' . $condition, $params);

		foreach ($orders as $key => $order) {
			$orders[$key]['amount'] = ($order['price'] + $order['freight']) - $order['creditmoney'];
			$goods = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_groups_goods') . 'WHERE id = ' . $order['goodid']);
			$sql2 = 'SELECT * FROM' . tablename('wx_shop_groups_order') . 'where teamid = :teamid and success = 1';
			$params2 = array(':teamid' => $order['teamid']);
			$alltuan = pdo_fetchall($sql2, $params2);
			$item = array();

			foreach ($alltuan as $num => $all) {
				$item[$num] = $all['id'];
			}

			$orders[$key]['itemnum'] = count($item);
			$sql3 = 'SELECT * FROM ' . tablename('wx_shop_groups_order') . ' WHERE teamid = :teamid and paytime > 0 and heads = :heads';
			$params3 = array(':teamid' => $order['teamid'], ':heads' => 1);
			$tuan_first_order = pdo_fetch($sql3, $params3);
			$hours = $tuan_first_order['endtime'];
			$time = time();
			$date = date('Y-m-d H:i:s', $tuan_first_order['starttime']);
			$endtime = date('Y-m-d H:i:s', strtotime(' ' . $date . ' + ' . $hours . ' hour'));
			$date1 = date('Y-m-d H:i:s', $time);
			$orders[$key]['lasttime'] = strtotime($endtime) - strtotime($date1);
			$orders[$key]['starttime'] = date('Y-m-d H:i:s', $orders[$key]['starttime']);
		}

		$orders = set_medias($orders, 'thumb');
		show_json(1, array('list' => $orders, 'pagesize' => $psize, 'total' => $total));
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$openid = $_GPC['openid'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}

		$uniacid = $_W['uniacid'];
		$teamid = intval($_GPC['teamid']);
		$condition = '';

		if (empty($teamid)) {
			// $this->message('该团不存在!', mobileUrl('groups/index'), 'error');
			show_json(0, '该团不存在!');
		}

		$myorder = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_groups_order') . ' WHERE uniacid = ' . $uniacid . ' and openid = \'' . $openid . '\' and teamid = ' . $teamid . ' and paytime>0');
		$params = array(':teamid' => $teamid);
		$orders = pdo_fetchall('select * from ' . tablename('wx_shop_groups_order') . ' where uniacid = ' . $uniacid . ' and teamid = :teamid and paytime>0 order by id asc ', $params);
		// var_dump($orders[0]);die;
		if(empty($orders[0]['goodid'])){
			show_json(0, '该团不存在!');
		}
		$profileall = array();

		foreach ($orders as $key => $value) {
			if ($value['groupnum'] == 1) {
				$single = 1;
			}

			$order['goodid'] = $value['goodid'];
			$order['groupnum'] = $value['groupnum'];
			$order['success'] = $value['success'];
			$avatar = pdo_fetch('SELECT openid,avatar,nickname FROM ' . tablename('wx_shop_member') . ' WHERE uniacid =\'' . $_W['uniacid'] . '\' and openid = \'' . $value['openid'] . '\'');
			$orders[$key]['openid'] = $avatar['openid'];
			$orders[$key]['nickname'] = $avatar['nickname'];
			$orders[$key]['avatar'] = $avatar['avatar'];
			$orders[$key]['paytime'] = date('Y-m-d H:i:s', $value['paytime']);

			if ($orders[$key]['avatar'] == '') {
				$orders[$key]['avatar'] = '../addons/wx_shop/plugin/groups/template/mobile/default/images/user/' . mt_rand(1, 20) . '.jpg';
			}
		}

		$groupsset = pdo_fetch('select description,groups_description,discount,headstype,headsmoney,headsdiscount from ' . tablename('wx_shop_groups_set') . "\r\n\t\t\t\t\twhere uniacid = :uniacid ", array(':uniacid' => $uniacid));
		$groupsset['groups_description'] = m('ui')->lazy($groupsset['groups_description']);
		$goods = pdo_fetch('SELECT * FROM' . tablename('wx_shop_groups_goods') . 'WHERE  uniacid = ' . $uniacid . ' and id = ' . $order['goodid']);
		// $goods['content'] = m('ui')->lazy($goods['content']);

		if (!empty($goods['thumb_url'])) {
			$goods['thumb_url'] = array_merge(iunserializer($goods['thumb_url']));
		}
		foreach ($goods['thumb_url'] as $k => $v) {
			$goods['thumb_url'][$k] = tomedia($v);
		}
		
		
		$sql = 'SELECT * FROM' . tablename('wx_shop_groups_order') . ' where uniacid = :uniacid and teamid=:teamid and status > 0 ';
		$params = array(':uniacid' => $_W['uniacid'], ':teamid' => $teamid);
		$alltuan = pdo_fetchall($sql, $params);
		$item = array();

		foreach ($alltuan as $num => $all) {
			$item[$num] = $all['id'];
		}

		$n = intval($order['groupnum']) - count($alltuan);

		if ($n <= 0) {
			pdo_update('wx_shop_groups_order', array('success' => 1), array('teamid' => $teamid));
		}

		$nn = intval($order['groupnum']) - 1;
		$arr = array();
		$i = 0;

		while ($i < $n) {
			$arr[$i] = 0;
			++$i;
		}

		$tuan_first_order = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_groups_order') . ' WHERE teamid = ' . $teamid . ' and heads = 1');
		// var_dump($teamid);die;
		$hours = $tuan_first_order['endtime'];
		$time = time();

		$date = date('Y-m-d H:i:s', $tuan_first_order['starttime']);
		$endtime = date('Y-m-d H:i:s', strtotime(' ' . $date . ' + ' . $hours . ' hour'));
		$date1 = date('Y-m-d H:i:s', $time);

		$lasttime2 = strtotime($endtime) - strtotime($date1);
		$tuan_first_order['endtime'] = strtotime(' ' . $date . ' + ' . $hours . ' hour');
		$set = $_W['shopset'];
		$_W['shopshare'] = array('title' => '还差' . $n . '人，我参加了“' . $goods['title'] . '”拼团，快来吧。盼你如南方人盼暖气~', 'imgUrl' => !empty($goods['share_icon']) ? tomedia($goods['share_icon']) : tomedia($goods['thumb']), 'desc' => !empty($goods['share_title']) ? $goods['share_title'] : $goods['title'], 'link' => mobileUrl('groups/team/detail', array('teamid' => $teamid), true));
		// include $this->template();
		$goods['thumb'] = tomedia($goods['thumb']);
		$tuan_first_order['starttime'] = date('Y-m-d H:i:s', $tuan_first_order['starttime']);
		$tuan_first_order['endtime'] = date('Y-m-d H:i:s', $tuan_first_order['endtime']);
		$order['paytime'] = date('Y-m-d H:i:s', $order['paytime']);
		$groupsset['groups_description'] = htmlspecialchars_decode($groupsset['groups_description']);
		$goods['content'] = htmlspecialchars_decode($goods['content']);

		show_json(1, array(
			'thumb' => $thumb,
			'goods' => $goods,
			'order' => $order,
			'orders' => $orders,
			'lasttime2' => $lasttime2,
			'tuan_first_order' => $tuan_first_order,
			'arr' => $arr,
			'n' => $n,
			'groupsset' => $groupsset,
			'myorder' => $myorder,
			'shopshare' => array('title' => '还差' . $n . '人，我参加了“' . $goods['title'] . '”拼团，快来吧。盼你如南方人盼暖气~', 'imgUrl' => !empty($goods['share_icon']) ? tomedia($goods['share_icon']) : tomedia($goods['thumb']), 'desc' => !empty($goods['share_title']) ? $goods['share_title'] : $goods['title'], 'link' => mobileUrl('groups/team/detail', array('teamid' => $teamid), true)),
		));
	}

	public function rules()
	{
		global $_W;
		global $_GPC;
		$set = pdo_fetch('SELECT rules FROM ' . tablename('wx_shop_groups_set') . ' WHERE uniacid =\'' . $_W['uniacid'] . '\'');
		// include $this->template();
		show_json(1, array('set'=>$set));
	}
}

?>
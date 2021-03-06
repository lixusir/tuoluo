<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Verify_WxShopPage extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$verify = $_GPC['verify'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and o.uniacid=:uniacid and o.isverify = 1 ';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($verify == 'normal') {
			$condition .= ' and o.status = 1 ';
		}
		else if ($verify == 'over') {
			$condition .= ' and o.status = 3 ';
		}
		else {
			if ($verify == 'cancel') {
				$condition .= ' and o.status <= 0 ';
			}
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		$searchtime = trim($_GPC['searchtime']);

		if (!empty($searchtime)) {
			$condition .= ' and o.' . $searchtime . 'time > ' . strtotime($_GPC['time']['start']) . ' and o.' . $searchtime . 'time < ' . strtotime($_GPC['time']['end']) . ' ';
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
		}

		if (!empty($_GPC['paytype'])) {
			$_GPC['paytype'] = trim($_GPC['paytype']);
			$condition .= ' and o.pay_type = :paytype';
			$params[':paytype'] = $_GPC['paytype'];
		}

		if (!empty($_GPC['searchfield']) && !empty($_GPC['keyword'])) {
			$searchfield = trim(strtolower($_GPC['searchfield']));
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$params[':keyword'] = $_GPC['keyword'];
			$sqlcondition = '';
			$keycondition = '';

			if ($searchfield == 'orderno') {
				$condition .= ' AND locate(:keyword,o.orderno)>0 ';
			}
			else if ($searchfield == 'member') {
				$condition .= ' AND (locate(:keyword,m.realname)>0 or locate(:keyword,m.mobile)>0 or locate(:keyword,m.nickname)>0)';
			}
			else if ($searchfield == 'goodstitle') {
				$condition .= ' and locate(:keyword,g.title)>0 ';
			}
			else if ($searchfield == 'goodssn') {
				$condition .= ' and locate(:keyword,g.goodssn)>0 ';
			}
			else if ($searchfield == 'saler') {
				$keycondition = ' ,sm.id as salerid,sm.nickname as salernickname,s.salername ';
				$condition .= ' AND (locate(:keyword,sm.realname)>0 or locate(:keyword,sm.mobile)>0 or locate(:keyword,sm.nickname)>0 or locate(:keyword,s.salername)>0 )';
				$sqlcondition = ' left join ' . tablename('wx_shop_saler') . " as s on s.openid = v.verifier and s.uniacid=v.uniacid\r\n\t\t\t\tleft join " . tablename('wx_shop_member') . ' sm on sm.openid = s.openid and sm.uniacid=s.uniacid ';
			}
			else {
				if ($searchfield == 'store') {
					$condition .= ' AND (locate(:keyword,store.storename)>0)';
					$sqlcondition = ' left join ' . tablename('wx_shop_store') . ' store on store.id = v.storeid and store.uniacid=o.uniacid ';
				}
			}
		}

		if (empty($_GPC['export'])) {
			$page = 'LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}

		$list = pdo_fetchall("SELECT o.id,o.orderno,o.status,o.expresssn,o.addressid,o.express,o.remark,o.is_team,o.pay_type,o.isverify,o.refundtime,o.price,o.creditmoney,o.realname,o.mobile,\r\n\t\t\t\to.freight,o.discount,o.creditmoney,o.createtime,o.success,o.deleted,o.paytime,o.finishtime,\r\n\t\t\t\tv.verifier,v.storeid as vstoreid,g.title,g.category,g.thumb,g.groupsprice,g.singleprice,g.price as gprice,g.goodssn,m.nickname,m.id as mid,m.realname as mrealname,m.mobile as mmobile\r\n\t\t\t\t" . $keycondition . "\r\n\t\t\t\tFROM " . tablename('wx_shop_groups_order') . " as o\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_goods') . " as g on g.id = o.goodid\r\n\t\t\t\tleft join " . tablename('wx_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid\t\t\t\t\r\n\t\t\t\t" . $sqlcondition . "\r\n\t\t\t\tWHERE 1 " . $condition . ' group by o.id  ORDER BY o.createtime DESC ' . $page, $params);

		foreach ($list as $key => $value) {
		}

		$num = pdo_fetchall('SELECT count(1) FROM ' . tablename('wx_shop_groups_order') . " as o\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_goods') . " as g on g.id = o.goodid\r\n\t\t\t\tleft join " . tablename('wx_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid\r\n\t\t\t\t" . $sqlcondition . "\r\n\t\t\t\tWHERE 1 " . $condition . ' group by o.id  ', $params);
		$total = count($num);
		unset($num);
		$pager = pagination2($total, $pindex, $psize);
		$paytype = array('credit' => '????????????', 'wechat' => '????????????', 'other' => '????????????');
		$paystatus = array(0 => '?????????', 1 => '?????????', 2 => '?????????', 3 => '?????????', -1 => '?????????', 4 => '?????????');

		if ($_GPC['export'] == 1) {
			plog('groups.order.export', '????????????');
			$columns = array(
				array('title' => '????????????', 'field' => 'orderno', 'width' => 24),
				array('title' => '????????????', 'field' => 'nickname', 'width' => 12),
				array('title' => '????????????', 'field' => 'mrealname', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 30),
				array('title' => '?????????????????????', 'field' => 'mmobile', 'width' => 15),
				array('title' => '????????????(????????????)', 'field' => 'arealname', 'width' => 15),
				array('title' => '????????????', 'field' => 'amobile', 'width' => 12),
				array('title' => '????????????', 'field' => 'title', 'width' => 30),
				array('title' => '????????????', 'field' => 'goodssn', 'width' => 15),
				array('title' => '?????????', 'field' => 'groupsprice', 'width' => 12),
				array('title' => '?????????', 'field' => 'singleprice', 'width' => 12),
				array('title' => '??????', 'field' => 'price', 'width' => 12),
				array('title' => '????????????', 'field' => 'goods_total', 'width' => 15),
				array('title' => '????????????', 'field' => 'goodsprice', 'width' => 12),
				array('title' => '????????????', 'field' => 'credit', 'width' => 12),
				array('title' => '??????????????????', 'field' => 'creditmoney', 'width' => 12),
				array('title' => '??????', 'field' => 'freight', 'width' => 12),
				array('title' => '?????????', 'field' => 'amount', 'width' => 12),
				array('title' => '????????????', 'field' => 'pay_type', 'width' => 12),
				array('title' => '??????', 'field' => 'status', 'width' => 12),
				array('title' => '????????????', 'field' => 'createtime', 'width' => 24),
				array('title' => '????????????', 'field' => 'paytime', 'width' => 24),
				array('title' => '????????????', 'field' => 'finishtime', 'width' => 24),
				array('title' => '?????????', 'field' => 'salerinfo', 'width' => 24),
				array('title' => '????????????', 'field' => 'storeinfo', 'width' => 36),
				array('title' => '????????????', 'field' => 'remark', 'width' => 36)
				);
			$exportlist = array();

			foreach ($list as $key => $value) {
				$r['salerinfo'] = '';
				$r['storeinfo'] = '';
				$verify = pdo_fetchall('select sm.id as salerid,sm.nickname as salernickname,s.salername,store.storename from ' . tablename('wx_shop_groups_verify') . " as v\r\n\t\t\t\t\tleft join " . tablename('wx_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid\r\n\t\t\t\t\tleft join " . tablename('wx_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid\r\n\t\t\t\t\tleft join " . tablename('wx_shop_store') . " store on store.id = v.storeid and store.uniacid=v.uniacid\r\n\t\t\t\t\twhere v.orderid = :orderid and v.uniacid = :uniacid ", array(':orderid' => $value['id'], ':uniacid' => $_W['uniacid']));
				$vcount = count($verify) - 1;

				foreach ($verify as $k => $val) {
					$r['salerinfo'] .= '[' . $val['salerid'] . ']' . $val['salername'] . '(' . $val['salernickname'] . ')';
					$r['storeinfo'] .= $val['storename'];

					if ($k != $vcount) {
						$r['salerinfo'] .= "\r\n";
						$r['storeinfo'] .= "\r\n";
					}
					else {
						$r['salerinfo'] .= '';
						$r['storeinfo'] .= '';
					}
				}

				$r['orderno'] = $value['orderno'];
				$r['nickname'] = str_replace('=', '', $value['nickname']);
				$r['mrealname'] = $value['mrealname'];
				$r['openid'] = $value['openid'];
				$r['mmobile'] = $value['mmobile'];
				$r['arealname'] = $value['realname'];
				$r['amobile'] = $value['mobile'];
				$r['pay_type'] = $paytype['' . $value['pay_type'] . ''];
				$r['freight'] = $value['freight'];
				$r['groupsprice'] = $value['groupsprice'];
				$r['singleprice'] = $value['singleprice'];
				$r['price'] = $value['price'];
				$r['credit'] = !empty($value['credit']) ? '-' . $value['credit'] : 0;
				$r['creditmoney'] = !empty($value['creditmoney']) ? '-' . $value['creditmoney'] : 0;
				$r['goodsprice'] = $value['groupsprice'] * 1;
				$r['status'] = ($value['status'] == 1) && ($value['status'] == 1) ? $paystatus[4] : $paystatus['' . $value['status'] . ''];
				$r['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
				$r['paytime'] = !empty($value['paytime']) ? date('Y-m-d H:i:s', $value['paytime']) : '';
				$r['finishtime'] = !empty($value['finishtime']) ? date('Y-m-d H:i:s', $value['finishtime']) : '';
				$r['expresscom'] = $value['expresscom'];
				$r['expresssn'] = $value['expresssn'];
				$r['amount'] = (($value['groupsprice'] * 1) - $value['creditmoney']) + $value['freight'];
				$r['remark'] = $value['remark'];
				$r['title'] = $value['title'];
				$r['goodssn'] = $value['goodssn'];
				$r['goods_total'] = 1;
				$exportlist[] = $r;
			}

			unset($r);
			m('excel')->export($exportlist, array('title' => '????????????-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}

		include $this->template();
	}

	public function fetch()
	{
		global $_W;
		global $_GPC;
		$opdata = $this->opData();
		extract($opdata);

		if ($item['status'] != 1) {
			message('???????????????????????????????????????');
		}

		$time = time();
		$d = array('status' => 3, 'sendtime' => $time, 'finishtime' => $time);

		if ($item['isverify'] == 1) {
			$d['verified'] = 1;
			$d['verifytime'] = $time;
			$d['verifyopenid'] = '';
		}

		pdo_update('wx_shop_order', $d, array('id' => $item['id'], 'uniacid' => $_W['uniacid']));

		if (!empty($item['refundid'])) {
			$refund = pdo_fetch('select * from ' . tablename('wx_shop_order_refund') . ' where id=:id limit 1', array(':id' => $item['refundid']));

			if (!empty($refund)) {
				pdo_update('wx_shop_order_refund', array('status' => -1), array('id' => $item['refundid']));
				pdo_update('wx_shop_order', array('refundstate' => 0), array('id' => $item['id']));
			}
		}

		plog('groups.verify.fetch', '?????????????????? ID: ' . $item['id'] . ' ?????????: ' . $item['orderno']);
		show_json(1);
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$status = $_GPC['status'];
		$orderid = intval($_GPC['orderid']);
		$params = array(':orderid' => $orderid);
		$order = pdo_fetch('SELECT o.*,g.title,g.category,g.groupsprice,g.singleprice,g.thumb,g.id as gid FROM ' . tablename('wx_shop_groups_order') . " as o\r\n\t\t\t\tleft join " . tablename('wx_shop_groups_goods') . " as g on g.id = o.goodid\r\n\t\t\t\tWHERE o.id = :orderid ", $params);
		$order = set_medias($order, 'thumb');
		$member = m('member')->getMember($order['openid'], true);
		$verifyinfo = pdo_fetchall('select v.*,sm.id as salerid,sm.nickname as salernickname,s.salername,store.storename from ' . tablename('wx_shop_groups_verify') . " as v\r\n\t\t\t\t\tleft join " . tablename('wx_shop_saler') . " s on s.openid = v.verifier and s.uniacid = v.uniacid\r\n\t\t\t\t\tleft join " . tablename('wx_shop_member') . " sm on sm.openid = s.openid and sm.uniacid = s.uniacid\r\n\t\t\t\t\tleft join " . tablename('wx_shop_store') . " store on store.id = v.storeid and store.uniacid = v.uniacid\r\n\t\t\t\t\twhere v.uniacid = :uniacid and v.orderid = :orderid ", array(':orderid' => $orderid, ':uniacid' => $order['uniacid']));

		if ($order['verifytype'] == 0) {
			$verify = pdo_fetch('select * from ' . tablename('wx_shop_groups_verify') . ' where orderid = ' . $order['id'] . ' ');

			if (!empty($verify['verifier'])) {
				$saler = m('member')->getMember($verify['verifier']);
				$saler['salername'] = pdo_fetchcolumn('select salername from ' . tablename('wx_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1 ', array(':uniacid' => $_W['uniacid'], ':openid' => $verify['verifier']));
			}

			if (!empty($verify['storeid'])) {
				$store = pdo_fetch('select * from ' . tablename('wx_shop_store') . ' where id=:storeid limit 1 ', array(':storeid' => $verify['storeid']));
			}
		}

		include $this->template();
	}

	protected function opData()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$item = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_groups_order') . ' WHERE id = :id and uniacid=:uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($item)) {
			if ($_W['isajax']) {
				show_json(0, '???????????????!');
			}

			$this->message('???????????????!', '', 'error');
		}

		return array('id' => $id, 'item' => $item);
	}
}

?>

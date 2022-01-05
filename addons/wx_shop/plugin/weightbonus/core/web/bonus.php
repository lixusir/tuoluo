<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Bonus_WxShopPage extends PluginWebPage
{
	public function status0()
	{
		$this->get_list(0);
	}

	public function status1()
	{
		$this->get_list(1);
	}

	public function status2()
	{
		$this->get_list(2);
	}

	protected function get_list($status = 0)
	{
		global $_W;
		global $_GPC;
		$set = $this->getSet();
		$years = array();
		$current_year = date('Y');
		$i = $current_year - 10;

		while ($i <= $current_year) {
			$years[] = $i;
			++$i;
		}

		$months = array();
		$i = 1;

		while ($i <= 12) {
			$months[] = strlen($i) == 1 ? '0' . $i : $i;
			++$i;
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid and status=:status';
		$params = array(':uniacid' => $_W['uniacid'], ':status' => $status);

		if ($_GPC['year'] != '') {
			$condition .= ' and `year`=' . intval($_GPC['year']);
		}

		if ($_GPC['month'] != '') {
			$condition .= ' and `month`=' . intval($_GPC['month']);
		}

		if ($_GPC['week'] != '') {
			$condition .= ' and `week`=' . intval($_GPC['week']);
		}

		$keyword = trim($_GPC['keyword']);

		if (!empty($keyword)) {
			$condition .= ' and billno like :keyword ';
			$params[':keyword'] = '%' . $keyword . '%';
		}

		$sql = 'select *  from ' . tablename('wx_shop_weightbonus_bill') . '  where 1 ' . $condition . ' ORDER BY createtime desc ';

		if (empty($_GPC['export'])) {
			$sql .= '  limit ' . (($pindex - 1) * $psize) . ',' . $psize;
		}

		$list = pdo_fetchall($sql, $params);

		if ($_GPC['export'] == 1) {
			ca('weightbonus.bonus.export');
			plog('weightbonus.bonus.export', '导出结算单');

			foreach ($list as &$row) {
				$row['createtime'] = empty($row['createtime']) ? '' : date('Y-m-d H:i', $row['createtime']);
				$row['days'] = $row['year'] . '年' . $row['month'] . '月';

				if ($row['paytype'] == 2) {
					$row['days'] .= '第' . $row['week'] . '周';
				}

				if (empty($row['status'])) {
					$row['statusstr'] = '待确认';
				}
				else if ($row['status'] == 1) {
					$row['statusstr'] = '待结算';
				}
				else {
					if ($row['status'] == 2) {
						$row['statusstr'] = '已结算';
					}
				}

				$row['paytype'] = $row['paytype'] == 2 ? '按周' : '按月';
			}

			unset($row);
			m('excel')->export($list, array(
	'title'   => '结算单-' . time(),
	'columns' => array(
		array('title' => 'ID', 'field' => 'id', 'width' => 12),
		array('title' => '结算类型', 'field' => 'paytype', 'width' => 12),
		array('title' => '单号', 'field' => 'billno', 'width' => 24),
		array('title' => '日期', 'field' => 'days', 'width' => 12),
		array('title' => '订单数', 'field' => 'ordercount', 'width' => 12),
		array('title' => '订单金额', 'field' => 'ordermoney', 'width' => 12),
		array('title' => '代理数', 'field' => 'weightcount', 'width' => 12),
		array('title' => '预计分红', 'field' => 'bonusmoney', 'width' => 12),
		array('title' => '最终分红', 'field' => 'bonusmoney_send', 'width' => 12),
		array('title' => '状态', 'field' => 'statusstr', 'width' => 12)
		)
	));
		}

		$total = pdo_fetchcolumn('select count(*) from' . tablename('wx_shop_weightbonus_bill') . '  where 1 ' . $condition, $params);
		$totalmoney = pdo_fetchcolumn('select sum(bonusmoney_send) from' . tablename('wx_shop_weightbonus_bill') . '  where 1 ' . $condition, $params);
		$pager = pagination2($total, $pindex, $psize);
		include $this->template('weightbonus/bonus/index');
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$data = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($data)) {
			$this->message('结算单未找到!');
		}

		$data['weightcount1'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and status=1 and uniacid=:uniacid', array(':uniacid' => $_W['uniacid'], ':billid' => $id));
		$condition = ' and b.billid=:billid and b.uniacid =:uniacid';
		$params = array(':billid' => $id, ':uniacid' => $_W['uniacid']);
		$keyword = trim($_GPC['keyword']);

		if (!empty($keyword)) {
			$condition .= ' and (m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword)';
			$params[':keyword'] = '%' . $keyword . '%';
		}

		if ($_GPC['status'] != '') {
			if ($_GPC['status'] == 1) {
				$condition .= ' and b.status=1';
			}
			else {
				$condition .= ' and b.status=0 or b.status=-1';
			}
		}

        if ($_GPC['level'] != '')
        {
            $condition .= ' and m.weightlevel=' . intval($_GPC['level']);
        }

		$sql = 'select b.*, m.nickname,m.avatar,m.realname,m.weixin,m.mobile,l.levelname,b.bonus,m.weightlevel,m.id as mid from ' . tablename('wx_shop_weightbonus_billp') . ' b ' . ' left join ' . tablename('wx_shop_member') . ' m on m.openid = b.openid and m.uniacid = b.uniacid' . ' left join ' . tablename('wx_shop_weightbonus_level') . ' l on l.id = b.weightlevel' . ' where 1 ' . $condition . ' ORDER BY status asc ';

//	  print_r($condition);
//      print_r($params);
//      print_r($sql);
//      exit();

		$list = pdo_fetchall($sql, $params);

		if ($_GPC['export'] == 1) {
			ca('weightbonus.bonus.detail.export');
			plog('weightbonus.bonus.detail.export', '导出结算单代理数据 ID: ' . $data['id'] . ' 单号: ' . $data['billno']);

			foreach ($list as &$row) {
				$row['paytime'] = empty($row['paytime']) ? '' : date('Y-m-d H:i', $row['paytime']);
				$row['createtime'] = empty($row['createtime']) ? '' : date('Y-m-d H:i', $row['createtime']);
				$row['levelname'] = !empty($row['levelname']) ? $row['levelname'] : (empty($set['levelname']) ? '默认等级' : $set['levelname']);
			}

			unset($row);
			m('excel')->export($list, array(
	'title'   => '结算单代理数据-' . $data['billno'],
	'columns' => array(
		array('title' => 'ID', 'field' => 'id', 'width' => 12),
		array('title' => '单号', 'field' => 'payno', 'width' => 12),
		array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
		array('title' => '姓名', 'field' => 'realname', 'width' => 12),
		array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
		array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
		array('title' => 'openid', 'field' => 'openid', 'width' => 24),
		array('title' => '等级', 'field' => 'levelname', 'width' => 12),
		array('title' => '计算分红', 'field' => 'money', 'width' => 12),
		array('title' => '实际分红', 'field' => 'realmoney', 'width' => 12),
		array('title' => '最终分红', 'field' => 'paymoney', 'width' => 12),
		array('title' => '打款时间', 'field' => 'paytime', 'width' => 12)
		)
	));
		}

		$set = $this->getSet();
		$levels = $this->model->getLevels();
		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$data = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($data)) {
			show_json(0, '结算单未找到!');
		}

		if (!empty($data['status'])) {
			show_json(0, '结算单已经结算，不能删除!');
		}

		pdo_query('delete from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		pdo_query('delete from ' . tablename('wx_shop_weightbonus_billo') . ' where billid=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		pdo_query('delete from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		plog('weightbonus.bonus.delete', '删除结算单 ID:' . $data . ' 单号: ' . $data['billno'] . ' 分红金额: ' . $data['bonusmoney_pay'] . ' 代理人数:  ' . $data['weightcount']);
		show_json(1);
	}

	public function totals()
	{
		global $_W;
		$totals = $this->model->getTotals();
		show_json(1, $totals);
	}

	//生成结算单
	public function build()
	{
		global $_W;
		global $_GPC;
		$set = $this->getSet();

		if ($_W['ispost']) {
			$year = intval($_GPC['year']);
			$month = intval($_GPC['month']);
			$week = intval($_GPC['week']);
			$data = $this->model->getBonusData($year, $month, $week);

			if ($data['old']) {
				show_json(1, array('old' => true));
			}

			$set = $this->getSet();

			//分红结算总单
			$bill = array('uniacid' => $_W['uniacid'], 'billno' => m('common')->createNO('weightbonus_bill', 'billno', 'GB'), 'paytype' => $set['paytype'], 'year' => $year, 'month' => $month, 'week' => $week, 'ordercount' => $data['ordercount'], 'ordermoney' => $data['ordermoney'], 'bonusmoney' => $data['bonusmoney'], 'bonusordermoney' => $data['bonusordermoney'], 'bonusmoney_send' => $data['bonusmoney'], 'bonusmoney_pay' => $data['bonusmoney'], 'weightcount' => $data['weightcount'], 'starttime' => $data['starttime'], 'endtime' => $data['endtime'], 'createtime' => time());
			pdo_insert('wx_shop_weightbonus_bill', $bill);
			$billid = pdo_insertid();

			//分红结算订单部分
			foreach ($data['orders'] as $order) {
				$bo = array('uniacid' => $_W['uniacid'], 'billid' => $billid, 'orderid' => $order['id'], 'ordermoney' => $order['price'],'weightbonusprice'=>$order['weightbonusprice']);
				pdo_insert('wx_shop_weightbonus_billo', $bo);
			}

			//分红订单用户部分(这里应该添加订单号，确定每个订单)
			foreach ($data['weights'] as $weight) {
				$bp = array('uniacid' => $_W['uniacid'], 'billid' => $billid, 'payno' => m('common')->createNO('weightbonus_billp', 'payno', 'GP'), 'openid' => $weight['openid'], 'orderid'=>$weight['orderid'],'weightlevel'=>$weight['weightlevel'],'money' => $weight['bonusmoney'], 'realmoney' => $weight['bonusmoney'], 'paymoney' => $weight['bonusmoney'], 'bonus' => $weight['bonus'], 'charge' => $weight['charge'], 'chargemoney' => $weight['chargemoney'], 'status' => 0);
				pdo_insert('wx_shop_weightbonus_billp', $bp);
			}

			plog('weightbonus.bonus.build', '创建结算单 ID:' . $billid . ' 单号: ' . $bill['billno'] . ' 分红金额: ' . $bill['bonusmoney_pay'] . ' 代理人数:  ' . $bill['weightcount']);
			show_json(1, array('old' => false));
		}

		$years = array();
		$current_year = date('Y');
		$i = $current_year - 10;

		while ($i <= $current_year) {
			$years[] = $i;
			++$i;
		}

		$months = array();
		$i = 1;

		while ($i <= 12) {
			$months[] = strlen($i) == 1 ? '0' . $i : $i;
			++$i;
		}

		$days = get_last_day(date('Y'), date('m'));
		$week = intval($days / date('d')) - 1;

		if (empty($week)) {
			$week = 1;
		}

		$bill = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where uniacid=:uniacid order by id desc limit 1 ', array(':uniacid' => $_W['uniacid']));

		include $this->template();
	}

	//创建结算单时候的分红统计显示
	public function loaddetail()
	{
		global $_W;
		global $_GPC;
		$year = intval($_GPC['year']);
		$month = intval($_GPC['month']);
		$week = intval($_GPC['week']);
		$data = $this->model->getBonusData($year, $month, $week);
		include $this->template('weightbonus/bonus/loaddetail');
	}

	//确认结算单
	public function confirm()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$data = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($data)) {
			show_json(0, '结算单未找到!');
		}

		if (!empty($data['status'])) {
			show_json(0, '结算单已经确认或已经结算!');
		}

		$time = time();
		pdo_query('update ' . tablename('wx_shop_weightbonus_bill') . ' set status=1,confirmtime=' . $time . ' where id=:id and uniacid=:uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		plog('weightbonus.bonus.confirm', '确认结算单 ID:' . $data['id'] . ' 单号: ' . $data['billno']);
		show_json(1);
	}

	//结算订单分红
	public function pay($a = array(), $b = array())
	{
		global $_W;
		global $_GPC;

		$id = intval($_GPC['id']);
		$data = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($data)) {
			show_json(0, '结算单未找到!');
		}

		if ($data['status'] == 2) {
			show_json(0, '结算单已经全部结算!');
		}

		if (empty($data['status'])) {
			$orders = pdo_fetchall('select orderid from ' . tablename('wx_shop_weightbonus_billo') . ' where billid=:billid and uniacid=:uniacid', array(':uniacid' => $_W['uniacid'], ':billid' => $id), 'orderid');

			if (empty($orders)) {
				show_json(0, '未找到任何结算订单!');
			}

			pdo_query('update ' . tablename('wx_shop_order') . ' set isweightbonus=1 where id in ( ' . implode(',', array_keys($orders)) . ' ) and uniacid=' . $_W['uniacid']);
		}

		$time = time();
		pdo_query('update ' . tablename('wx_shop_weightbonus_bill') . ' set paytime=' . $time . ' where id=:id and uniacid=:uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		plog('weightbonus.bonus.pay', '进行结算单结算 ID:' . $data['id'] . ' 单号: ' . $data['billno']);
		$weights = pdo_fetchall('select id from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and status<>1 and uniacid=:uniacid', array(':uniacid' => $_W['uniacid'], ':billid' => $id), 'id');
		show_json(1, array('weights' => array_keys($weights)));
	}

	public function payp()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$bpid = intval($_GPC['bpid']);
		$set = $this->getSet();
		$data = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($data)) {
			show_json(0, '结算单未找到!');
		}

		if ($data['status'] == 2) {
			show_json(0, '结算单已经全部结算!');
		}

		if (empty($bpid)) {
			show_json(0, '参数错误!');
		}

		$weight = pdo_fetch('select *  from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':billid' => $id, ':id' => $bpid));

		if (empty($weight)) {
			show_json(0, '未找到代理!');
		}

		if ($weight['status'] == 1) {
			show_json(0, '此代理已经结算!');
		}

		if (empty($weight['openid']) || ($weight['paymoney'] <= 0)) {
			show_json(0, '结算数据错误!');
		}

		$pay = $weight['paymoney'];
		$moneytype = intval($set['moneytype']);
		($moneytype <= 0) && ($moneytype = 0);

		if ($pay < 1) {
			$moneytype = 0;
		}

		if (1 < $moneytype) {
			show_json(0, '结算方式错误!');
		}

		if ($moneytype == 1) {
			$pay *= 100;
		}

		$member = m('member')->getMember($weight['openid']);

		if (!empty($member)) {
			//发放分红
			$result = m('finance')->pay($weight['openid'], $moneytype, $pay, $weight['payno'], '代理分红', false);

			if (is_error($result)) {
				pdo_update('wx_shop_weightbonus_billp', array('reason' => $result['message'], 'status' => -1), array('billid' => $id, 'id' => $bpid));
			}
			else {
				pdo_update('wx_shop_weightbonus_billp', array('reason' => '', 'status' => 1, 'paytime' => time()), array('billid' => $id, 'id' => $bpid));
				$this->model->upgradeLevelByBonus($weight['openid']);   //结算分红后，调用是否升级
				$this->model->sendMessage($weight['openid'], array('money' => $weight['paymoney'], 'nickname' => $member['nickname'], 'type' => $moneytype ? '微信钱包' : '余额'), TM_GLOBONUS_PAY);
			}
		}
		else {
			pdo_update('wx_shop_weightbonus_billp', array('reason' => '未找到会员', 'status' => -1), array('billid' => $id, 'id' => $bpid));
		}

		$weightcount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and status=1 and uniacid=:uniacid', array(':uniacid' => $_W['uniacid'], ':billid' => $id));
		$allweightcount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and uniacid=:uniacid', array(':uniacid' => $_W['uniacid'], ':billid' => $id));
		$full = $weightcount == $allweightcount;

		if ($full) {
			pdo_query('update ' . tablename('wx_shop_weightbonus_bill') . ' set status=2 where id=:id and uniacid=:uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		}

		if (is_error($result)) {
			show_json(0, array('message' => $result['message'], 'weightcount' => $weightcount, 'full' => $full));
		}
		else {
			show_json(1, array('weightcount' => $weightcount, 'full' => $full));
		}
	}


	//此函数还未看用作什么？
	public function paymoney()
	{
		global $_W;
		global $_GPC;
		$type = trim($_GPC['type']);
		if (($type != 'level') && ($type != 'weight')) {
			show_json(0, '参数错误!');
		}

		$value = floatval($_GPC['value']);

		if ($value <= 0) {
			show_json(0, '参数错误!');
		}

		$id = intval($_GPC['id']);
		$data = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($data)) {
			show_json(0, '结算单未找到!');
		}

		if (!empty($data['status'])) {
			show_json(0, '结算单已经确认或结算!');
		}

		if ($type == 'level') {
			$level = intval($_GPC['level']);
			$sql = 'update ' . tablename('wx_shop_weightbonus_billp') . ' b ,' . tablename('wx_shop_member') . ' m set b.paymoney = ' . $value . ' where b.openid = m.openid and b.uniacid = m.uniacid and m.weightlevel=' . $level . ' and b.billid=' . $id . ' and b.uniacid=' . $_W['uniacid'];
			pdo_query($sql);
		}
		else {
			if ($type == 'weight') {
				$bpid = intval($_GPC['bpid']);
				$weight = pdo_fetch('select *  from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':billid' => $id, ':id' => $bpid));

				if (empty($weight)) {
					show_json(0, '未找到代理!');
				}

				pdo_update('wx_shop_weightbonus_billp', array('paymoney' => $value), array('id' => $bpid, 'billid' => $id, 'uniacid' => $_W['uniacid']));
			}
		}

		$totalmoney = pdo_fetchcolumn('select sum(paymoney)  from ' . tablename('wx_shop_weightbonus_billp') . ' where billid=:billid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':billid' => $id));
		pdo_update('wx_shop_weightbonus_bill', array('bonusmoney_pay' => $totalmoney), array('id' => $id, 'uniacid' => $_W['uniacid']));
		show_json(1);
	}
}

?>

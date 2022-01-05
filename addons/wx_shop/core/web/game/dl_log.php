<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Dl_log_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and log.uniacid=:uniacid and log.money<>0';
		$condition1 = '';
		$_GPC['type'] = 1;
		$params = array(':uniacid' => $_W['uniacid']);
		

		$cz_id = pdo_getcolumn('wx_shop_perm_user', array('uid' => $_W['user']['uid']), 'cz_id');



			
		if(!empty($_GPC['paytype'])) {

			$condition .= ' and  log.type=:paytype';

			$params[':paytype'] = intval($_GPC['paytype']);


		}

		if(!empty($_GPC['keyword'])) {

			$condition .= ' and ( m.realname like :realname or m.nickname like :realname or m.mobile like :realname or log.uid like :realname)';

			$params[':realname'] = trim($_GPC['keyword']);


		}

		if (empty($starttime) || empty($endtime)) 
		{
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		if (!(empty($_GPC['time']['start'])) && !(empty($_GPC['time']['end']))) 
		{
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND log.time >= :starttime AND log.time <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if ($_GPC['status'] != '') 
		{
			$condition .= ' and log.status=' . intval($_GPC['status']);
		}


		if(!empty($cz_id)) {


			$abonus_member = m('game')->getAbonus($cz_id);

			$condition .= ' and log.uid in ('.$abonus_member['member_str'].')';

		}

		$sql = 'select log.id,log.uid,log.fid,log.type,log.status,log.bili,log.k_id,log.money,log.time,m.nickname,m.id as mid,m.avatar,m.level,m.groupid,m.nickname,m.mobile,mm.id as mmid,mm.avatar as mavatar,mm.nickname as mnickname,mm.mobile as mmobile from ' . tablename('wx_shop_game_abonus') . ' log ' . ' left join ' . tablename('wx_shop_member') . ' m on m.id = log.uid left join '. tablename('wx_shop_member') .' mm on mm.id=log.fid ' . ' where 1 ' . $condition . ' ' . $condition1 . ' GROUP BY log.id ORDER BY log.time DESC ';

		



		if (empty($_GPC['export'])) 
		{
			$sql .= 'LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}
		$list = pdo_fetchall($sql, $params);


		$zong = 0;

		if (!(empty($list))) 
		{	

			foreach ($list as $key => $value ) 
			{

				$zong += $value['money'];
				
			}
		}

		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		if ($_GPC['export'] == 1) 
		{
			if ($_GPC['type'] == 1) 
			{
				plog('finance.log.withdraw.export', '导出提现记录');
			}
			else 
			{
				plog('finance.log.recharge.export', '导出充值记录');
			}
			foreach ($list as &$row ) 
			{
				$row['time'] = date('Y-m-d H:i', $row['time']);
				$row['groupname'] = ((empty($row['groupname']) ? '无分组' : $row['groupname']));
				$row['levelname'] = ((empty($row['levelname']) ? '普通会员' : $row['levelname']));
				$row['typestr'] = $apply_type[$row['applytype']];
				if ($row['status'] == 0) 
				{
					if ($row['type'] == 0) 
					{
						$row['status'] = '未充值';
					}
					else 
					{
						$row['status'] = '申请中';
					}
				}
				else if ($row['status'] == 1) 
				{
					if ($row['type'] == 0) 
					{
						$row['status'] = '充值成功';
					}
					else 
					{
						$row['status'] = '完成';
					}
				}
				else if ($row['status'] == -1) 
				{
					if ($row['type'] == 0) 
					{
						$row['status'] = '';
					}
					else 
					{
						$row['status'] = '失败';
					}
				}
				if ($row['rechargetype'] == 'system') 
				{
					$row['rechargetype'] = '后台';
				}
				else if ($row['rechargetype'] == 'wechat') 
				{
					$row['rechargetype'] = '微信';
				}
				else if ($row['rechargetype'] == 'alipay') 
				{
					$row['rechargetype'] = '支付宝';
				}
			}
			unset($row);
			$columns = array();
			$columns[] = array('title' => '昵称', 'field' => 'nickname', 'width' => 12);
			$columns[] = array('title' => '姓名', 'field' => 'realname', 'width' => 12);
			$columns[] = array('title' => '手机号', 'field' => 'mobile', 'width' => 12);
			$columns[] = array('title' => '会员等级', 'field' => 'levelname', 'width' => 12);
			$columns[] = array('title' => '会员分组', 'field' => 'groupname', 'width' => 12);
			$columns[] = array('title' => (empty($type) ? '充值金额' : '提现金额'), 'field' => 'money', 'width' => 12);
			if (!(empty($type))) 
			{
				$columns[] = array('title' => '到账金额', 'field' => 'realmoney', 'width' => 12);
				$columns[] = array('title' => '手续费金额', 'field' => 'deductionmoney', 'width' => 12);
				$columns[] = array('title' => '提现方式', 'field' => 'typestr', 'width' => 12);
				$columns[] = array('title' => '提现姓名', 'field' => 'applyrealname', 'width' => 24);
				$columns[] = array('title' => '支付宝', 'field' => 'alipay', 'width' => 24);
				$columns[] = array('title' => '银行', 'field' => 'bankname', 'width' => 24);
				$columns[] = array('title' => '银行卡号', 'field' => 'bankcard', 'width' => 24);
				$columns[] = array('title' => '申请时间', 'field' => 'applytime', 'width' => 24);
			}
			$columns[] = array('title' => (empty($type) ? '充值时间' : '提现申请时间'), 'field' => 'time', 'width' => 12);
			if (empty($type)) 
			{
				$columns[] = array('title' => '充值方式', 'field' => 'rechargetype', 'width' => 12);
			}
			$columns[] = array('title' => '备注', 'field' => 'remark', 'width' => 24);
			m('excel')->export($list, array('title' => ((empty($type) ? '会员充值数据-' : '会员提现记录')) . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_game_abonus') . ' log ' . ' left join ' . tablename('wx_shop_member') . ' m on m.id = log.uid left join '. tablename('wx_shop_member') .' mm on mm.id=log.fid ' . ' where 1 ' . $condition . ' ' . $condition1, $params);

		// echo "<pre>";
		// 	print_r($total);
		// echo "</pre>";
		// exit;
		$pager = pagination2($total, $pindex, $psize);
		include $this->template();
	}



	// public function recharge() 
	// {
	// 	$this->main(0);
	// }
	// public function withdraw() 
	// {
	// 	$this->main(1);
	// }
}
?>
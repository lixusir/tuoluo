<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Credit_red_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and log.uniacid=:uniacid and log.type!=:type and log.money<>0';
		$condition1 = '';
		$_GPC['type'] = 1;
		$params = array(':uniacid' => $_W['uniacid'], ':type' => $_GPC['type']);


		$paytype['广告等级红包'] = 10;
		$paytype['视频分销红包'] = 11;
		$paytype['视频分红红包'] = 12;
		$paytype['视频神鸟红包'] = 24;


		$paytype['升级红包'] = 13;
		$paytype['大转盘中奖'] = 4;
		$paytype['敲蛋中奖']=9;
		
		// echo '<pre>';
		//     print_r($paytype);
		// echo '</pre>';
		// exit;
			
		if(!empty($_GPC['paytype'])) {

			$condition .= ' and  log.type=:paytype';

			$params[':paytype'] = intval($_GPC['paytype']);


		}

		if(!empty($_GPC['keyword'])) {

			$condition .= ' and ( m.realname like :realname or m.nickname like :realname or m.mobile like :realname or log.uid like :realname)';

			$params[':realname'] = trim($_GPC['keyword']);


		}

		// echo "<pre>";
		// 	print_r($condition);
		// echo "</pre>";
		// echo "<pre>";
		// 	print_r($params);
		// echo "</pre>";
		// exit;
		if (empty($starttime) || empty($endtime)) 
		{
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		if (!(empty($_GPC['time']['start'])) && !(empty($_GPC['time']['end']))) 
		{
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND log.createtime >= :starttime AND log.createtime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}
		if (!(empty($_GPC['rechargetype']))) 
		{
			$_GPC['rechargetype'] = trim($_GPC['rechargetype']);
			if ($_GPC['rechargetype'] == 'system1') 
			{
				$condition .= ' AND log.rechargetype=\'system\' and log.money<0';
			}
			else 
			{
				$condition .= ' AND log.rechargetype=:rechargetype';
				$params[':rechargetype'] = $_GPC['rechargetype'];
			}
		}
		if ($_GPC['status'] != '') 
		{
			$condition .= ' and log.status=' . intval($_GPC['status']);
		}
		$sql = 'select log.id,log.uid,log.logno,log.type,log.status,log.rechargetype,log.sendmoney,log.money,log.createtime,log.realmoney,log.title,log.charge,log.remark,log.alipay,log.bankname,log.bankcard,log.realname as applyrealname,log.applytype,m.nickname,m.id as mid,m.avatar,m.level,m.groupid,m.realname,m.mobile,g.groupname,l.levelname from ' . tablename('wx_shop_game_member_log') . ' log ' . ' left join ' . tablename('wx_shop_member') . ' m on m.id = log.uid ' . ' left join ' . tablename('wx_shop_member_group') . ' g on g.id = m.groupid ' . ' left join ' . tablename('wx_shop_member_level') . ' l on l.id = m.level ' . ' where 1 ' . $condition . ' ' . $condition1 . ' GROUP BY log.id ORDER BY log.createtime DESC ';
		if (empty($_GPC['export'])) 
		{
			$sql .= 'LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}

		$money = 0;
		$list = pdo_fetchall($sql, $params);
		if (!(empty($list))) 
		{
			foreach ($list as $key => $value ) 
			{
				if ($value['deductionmoney'] == 0) 
				{
					$list[$key]['realmoney'] = $value['money'];
				}

				if($value['status'] == 3) {
					$money  += $value['money'];
				}
				foreach($paytype as $kk=>$vv){

					// unset($vv)

					if($vv==$value['type']){

				      $list[$key]['types']=$kk;

					  break;

					}

				}
				
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
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
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
			$columns[] = array('title' => (empty($type) ? '充值时间' : '提现申请时间'), 'field' => 'createtime', 'width' => 12);
			if (empty($type)) 
			{
				$columns[] = array('title' => '充值方式', 'field' => 'rechargetype', 'width' => 12);
			}
			$columns[] = array('title' => '备注', 'field' => 'remark', 'width' => 24);
			m('excel')->export($list, array('title' => ((empty($type) ? '会员充值数据-' : '会员提现记录')) . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_game_member_log') . ' log ' . ' left join ' . tablename('wx_shop_member') . ' m on m.id = log.uid ' . ' left join ' . tablename('wx_shop_member_group') . ' g on g.id = m.groupid ' . ' left join ' . tablename('wx_shop_member_level') . ' l on l.id = m.level ' . ' where 1 ' . $condition . ' ' . $condition1, $params);
		$pager = pagination2($total, $pindex, $psize);
		$groups = m('member')->getGroups();
		$levels = m('member')->getLevels();
		include $this->template();
	}

	public function wechat() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$log = pdo_fetch('select * from ' . tablename('wx_shop_game_member_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if (empty($log)) 
		{
			show_json(0, '未找到记录!');
		}
		if ($log['deductionmoney'] == 0) 
		{
			$realmoney = $log['money'];
		}
		else 
		{
			$realmoney = $log['realmoney'];
		}
		$set = $_W['shopset']['shop'];
		$data = m('common')->getSysset('pay');
		if (!(empty($data['paytype']['withdraw']))) 
		{
			$result = m('finance')->payRedPack($log['openid'], $realmoney * 100, $log['logno'], $log, $set['name'] . '余额提现', $data['paytype']);
			pdo_update('wx_shop_game_member_log', array('sendmoney' => $result['sendmoney'], 'senddata' => json_encode($result['senddata'])), array('id' => $log['id']));
			if ($result['sendmoney'] == $realmoney) 
			{
				$result = true;
			}
			else 
			{
				$result = $result['error'];
			}
		}
		else 
		{
			$result = m('finance')->pay($log['openid'], 1, $realmoney * 100, $log['logno'], $set['name'] . '余额提现');
		}
		if (is_error($result)) 
		{
			show_json(0, array('message' => $result['message']));
		}
		pdo_update('wx_shop_game_member_log', array('status' => 1), array('id' => $id, 'uniacid' => $_W['uniacid']));
		m('notice')->sendMemberLogMessage($log['id']);
		$member = m('member')->getMember($log['openid']);
		plog('finance.log.wechat', '余额提现 ID: ' . $log['id'] . ' 方式: 微信 提现金额: ' . $log['money'] . ' ,到账金额: ' . $realmoney . ' ,手续费金额 : ' . $log['deductionmoney'] . '<br/>会员信息:  ID: ' . $member['id'] . ' / ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
		show_json(1);
	}
	public function alipay() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$log = pdo_fetch('select * from ' . tablename('wx_shop_game_member_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if (empty($log)) 
		{
			show_json(0, '未找到记录!');
		}
		if ($log['deductionmoney'] == 0) 
		{
			$realmoeny = $log['money'];
		}
		else 
		{
			$realmoeny = $log['realmoney'];
		}
		$set = $_W['shopset']['shop'];
		$sec = m('common')->getSec();
		$sec = iunserializer($sec['sec']);
		if (!(empty($sec['alipay_pay']['open']))) 
		{
			$batch_no_money = $realmoeny * 100;
			$batch_no = 'D' . date('Ymd') . 'RW' . $log['id'] . 'MONEY' . $batch_no_money;
			$res = m('finance')->AliPay(array('account' => $log['alipay'], 'name' => $log['realname'], 'money' => $realmoeny), $batch_no, $sec['alipay_pay'], $log['title']);
			if (is_error($res)) 
			{
				show_json(0, $res['message']);
			}
			show_json(1, array('url' => $res));
		}
		show_json(0, '未开启,支付宝打款!');
	}
	public function manual() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$log = pdo_fetch('select * from ' . tablename('wx_shop_game_member_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if (empty($log)) 
		{
			show_json(0, '未找到记录!');
		}
		$member = m('member')->getMember($log['openid']);
		pdo_update('wx_shop_game_member_log', array('status' => 1), array('id' => $id, 'uniacid' => $_W['uniacid']));
		m('notice')->sendMemberLogMessage($log['id']);
		plog('finance.log.manual', '余额提现 方式: 手动 ID: ' . $log['id'] . ' <br/>会员信息: ID: ' . $member['id'] . ' / ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
		show_json(1);
	}
	public function refuse() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$log = pdo_fetch('select * from ' . tablename('wx_shop_game_member_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if (empty($log)) 
		{
			show_json(0, '未找到记录!');
		}
		pdo_update('wx_shop_game_member_log', array('status' => -1), array('id' => $id, 'uniacid' => $_W['uniacid']));
		if (0 < $log['money']) 
		{
			m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $set['name'] . '余额提现退回'));
		}
		m('notice')->sendMemberLogMessage($log['id']);
		plog('finance.log.refuse', '拒绝余额度提现 ID: ' . $log['id'] . ' 金额: ' . $log['money'] . ' <br/>会员信息:  ID: ' . $member['id'] . ' / ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
		show_json(1);
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
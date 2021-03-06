<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Withdraw_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and log.uniacid=:uniacid and log.type=:type and log.money<>0';
		$condition1 = '';
		$_GPC['type'] = 1;
		$params = array(':uniacid' => $_W['uniacid'], ':type' => $_GPC['type']);
		if (!(empty($_GPC['keyword']))) 
		{
			$_GPC['keyword'] = trim($_GPC['keyword']);
			if ($_GPC['searchfield'] == 'logno') 
			{
				$condition .= ' and log.logno like :keyword';
			}
			else if ($_GPC['searchfield'] == 'member') 
			{
				$condition1 .= ' and ( m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword )';
			}
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
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
			$condition .= ' AND log.createtime >= :starttime AND log.createtime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}
		if (!(empty($_GPC['level']))) 
		{
			$condition1 .= ' and m.level=' . intval($_GPC['level']);
		}
		if (!(empty($_GPC['groupid']))) 
		{
			$condition1 .= ' and m.groupid=' . intval($_GPC['groupid']);
		}
		$member_sql = '';
		if ($condition1 != '') 
		{
			// $member_sql = ' and openid IN (SELECT m.openid FROM ims_wx_shop_member WHERE m m.uniacid = :uniacid ' . $condition1 . ') OR openid IN (SELECT CONCAT(\'sns_wa_\',openid_wa) FROM ims_wx_shop_member WHERE m m.uniacid = :uniacid ' . $condition1 . ')';
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
		$sql = 'select log.id,log.uid,log.logno,log.type,log.status,log.rechargetype,log.sendmoney,log.money,log.createtime,log.realmoney,log.deductionmoney,log.charge,log.remark,log.alipay,log.bankname,log.bankcard,log.applytype,m.nickname,m.id as mid,m.avatar,m.level,m.groupid,m.realname,m.mobile,g.groupname,l.levelname from ' . tablename('wx_shop_game_member_log') . ' log ' . ' left join ' . tablename('wx_shop_member') . ' m on m.id = log.uid ' . ' left join ' . tablename('wx_shop_member_group') . ' g on g.id = m.groupid ' . ' left join ' . tablename('wx_shop_member_level') . ' l on l.id = m.level ' . ' where 1 ' . $condition . ' ' . $condition1 . ' GROUP BY log.id ORDER BY log.createtime DESC ';
		if (empty($_GPC['export'])) 
		{
			$sql .= 'LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}
		$list = pdo_fetchall($sql, $params);
		$apply_type = array(0 => '????????????');
		if (!(empty($list))) 
		{
			foreach ($list as $key => $value ) 
			{
				$list[$key]['typestr'] = $apply_type[$value['applytype']];
				if ($value['deductionmoney'] == 0) 
				{
					$list[$key]['realmoney'] = $value['money'];
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
				plog('game.withdraw.withdraw.export', '??????????????????');
			}
			else 
			{
				plog('game.withdraw.recharge.export', '??????????????????');
			}
			foreach ($list as &$row ) 
			{
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['groupname'] = ((empty($row['groupname']) ? '?????????' : $row['groupname']));
				$row['levelname'] = ((empty($row['levelname']) ? '????????????' : $row['levelname']));
				$row['typestr'] = $apply_type[$row['applytype']];
				if ($row['status'] == 0) 
				{
					if ($row['type'] == 0) 
					{
						$row['status'] = '?????????';
					}
					else 
					{
						$row['status'] = '?????????';
					}
				}
				else if ($row['status'] == 1) 
				{
					if ($row['type'] == 0) 
					{
						$row['status'] = '????????????';
					}
					else 
					{
						$row['status'] = '??????';
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
						$row['status'] = '??????';
					}
				}
				if ($row['rechargetype'] == 'system') 
				{
					$row['rechargetype'] = '??????';
				}
				else if ($row['rechargetype'] == 'wechat') 
				{
					$row['rechargetype'] = '??????';
				}
				else if ($row['rechargetype'] == 'alipay') 
				{
					$row['rechargetype'] = '?????????';
				}
			}
			unset($row);
			$columns = array();
			$columns[] = array('title' => '??????', 'field' => 'nickname', 'width' => 12);
			$columns[] = array('title' => '??????', 'field' => 'realname', 'width' => 12);
			$columns[] = array('title' => '?????????', 'field' => 'mobile', 'width' => 12);
			$columns[] = array('title' => '????????????', 'field' => 'levelname', 'width' => 12);
			$columns[] = array('title' => '????????????', 'field' => 'groupname', 'width' => 12);
			$columns[] = array('title' => (empty($type) ? '????????????' : '????????????'), 'field' => 'money', 'width' => 12);
			if (!(empty($type))) 
			{
				$columns[] = array('title' => '????????????', 'field' => 'realmoney', 'width' => 12);
				$columns[] = array('title' => '???????????????', 'field' => 'deductionmoney', 'width' => 12);
				$columns[] = array('title' => '????????????', 'field' => 'typestr', 'width' => 12);
				$columns[] = array('title' => '????????????', 'field' => 'applyrealname', 'width' => 24);
				$columns[] = array('title' => '?????????', 'field' => 'alipay', 'width' => 24);
				$columns[] = array('title' => '??????', 'field' => 'bankname', 'width' => 24);
				$columns[] = array('title' => '????????????', 'field' => 'bankcard', 'width' => 24);
				$columns[] = array('title' => '????????????', 'field' => 'applytime', 'width' => 24);
			}
			$columns[] = array('title' => (empty($type) ? '????????????' : '??????????????????'), 'field' => 'createtime', 'width' => 12);
			if (empty($type)) 
			{
				$columns[] = array('title' => '????????????', 'field' => 'rechargetype', 'width' => 12);
			}
			$columns[] = array('title' => '??????', 'field' => 'remark', 'width' => 24);
			m('excel')->export($list, array('title' => ((empty($type) ? '??????????????????-' : '??????????????????')) . date('Y-m-d-H-i', time()), 'columns' => $columns));
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
			show_json(0, '???????????????!');
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
			$result = m('finance')->payRedPack($log['openid'], $realmoney * 100, $log['logno'], $log, $set['name'] . '????????????', $data['paytype']);
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
			$result = m('finance')->pay($log['openid'], 1, $realmoney * 100, $log['logno'], $set['name'] . '????????????');
		}
		if (is_error($result)) 
		{
			show_json(0, array('message' => $result['message']));
		}
		pdo_update('wx_shop_game_member_log', array('status' => 1), array('id' => $id, 'uniacid' => $_W['uniacid']));
		m('notice')->sendMemberLogMessage($log['id']);
		$member = m('member')->getMember($log['openid']);
		plog('game.withdraw.wechat', '???????????? ID: ' . $log['id'] . ' ??????: ?????? ????????????: ' . $log['money'] . ' ,????????????: ' . $realmoney . ' ,??????????????? : ' . $log['deductionmoney'] . '<br/>????????????:  ID: ' . $member['id'] . ' / ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
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
			show_json(0, '???????????????!');
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
		show_json(0, '?????????,???????????????!');
	}
	public function manual() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$log = pdo_fetch('select * from ' . tablename('wx_shop_game_member_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if (empty($log)) 
		{
			show_json(0, '???????????????!');
		}
		$member = m('member')->getMember($log['uid']);
		pdo_update('wx_shop_game_member_log', array('status' => 3), array('id' => $id, 'uniacid' => $_W['uniacid']));
		m('notice')->sendMemberLogMessage($log['id']);
		plog('game.withdraw.manual', '???????????? ??????: ?????? ID: ' . $log['id'] . ' <br/>????????????: ID: ' . $member['id'] . ' / ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
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
			show_json(0, '???????????????!');
		}
		pdo_update('wx_shop_game_member_log', array('status' => -1), array('id' => $id, 'uniacid' => $_W['uniacid']));
		if (0 < $log['money']) 
		{	

			$credit_red = pdo_fetchcolumn('select credit_red from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$log['uid']));
			pdo_update('wx_shop_member',array('credit_red'=>$credit_red+$log['money']),array('id'=>$log['uid']));
			// m('member')->setCredit($log['uid'], 'credit_red', $log['money'], array(0, $set['name'] . '??????????????????'));
		}
		m('notice')->sendMemberLogMessage($log['id']);
		plog('game.withdraw.refuse', '????????????????????? ID: ' . $log['id'] . ' ??????: ' . $log['money'] . ' <br/>????????????:  ID: ' . $member['id'] . ' / ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
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
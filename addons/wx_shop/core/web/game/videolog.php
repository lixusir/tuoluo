<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}

class Videolog_WxShopPage extends WebPage
{
	public function main($type = 0) 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' v.uniacid=:uniacid ';
		$condition1 = '';
		$params = array(':uniacid' => $_W['uniacid']);
		
		// echo '<pre>';
		//     print_r(substr(11, -1));
		// echo '</pre>';
		// exit;
		
		// $paytype = m('game')->paytype();

		// echo '<pre>';
		//     print_r($paytype);
		// echo '</pre>';
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
			$condition .= ' and v.time >= :starttime AND v.time <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		// if(!empty($_GPC['paytype'])) {

		// 	$condition .= ' and  type=:paytype';

		// 	$params[':paytype'] = intval($_GPC['paytype']);


		// }

		if(!empty($_GPC['keyword'])) {

			$condition .= ' and ( m.realname like :realname or m.nickname like :realname or m.mobile like :realname or v.uid like :realname)';

			$params[':realname'] = intval($_GPC['keyword']);


		}

		// echo '<pre>';
		//     print_r($condition);
		// echo '</pre>';
		// exit;
		$sql = 'select v.*,m.nickname,m.avatar from '. tablename('wx_shop_game_video') .' v left join '.tablename('wx_shop_member').' m on m.id=v.uid where 1 and '.$condition.' order by v.id desc';

		// if(!empty($_GPC['paytype'])) {

		// echo '<pre>';
		//     print_r($sql);
		// echo '</pre>';
		// exit;
		// }
		// echo '<pre>';
		//     print_r($sql);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($params);
		// echo '</pre>';
		// $sql = 'select log.id,log.openid,log.logno,log.type,log.status,log.rechargetype,log.sendmoney,log.money,log.createtime,log.realmoney,log.deductionmoney,log.charge,log.remark,log.alipay,log.bankname,log.bankcard,log.realname as applyrealname,log.applytype,m.nickname,m.id as mid,m.avatar,m.level,m.groupid,m.realname,m.mobile,g.groupname,l.levelname from ' . tablename('wx_shop_member_log') . ' log ' . ' left join ' . tablename('wx_shop_member') . ' m on m.openid = log.openid ' . ' left join ' . tablename('wx_shop_member_group') . ' g on g.id = m.groupid ' . ' left join ' . tablename('wx_shop_member_level') . ' l on l.id = m.level ' . ' where 1 ' . $condition . ' ' . $condition1 . ' GROUP BY log.id ORDER BY log.createtime DESC ';
		if (empty($_GPC['export'])) 
		{
			$sql .= ' LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}
		$list = pdo_fetchall($sql, $params);

		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		// echo '<pre>';
		//     print_r($paytype);
		// echo '</pre>';
		if (!(empty($list))) 
		{
			foreach ($list as $key => $value ) 
			{
				$list[$key]['time']=date("Y-m-d H:i:s",$value['time']);

				// $info=pdo_fetch("select nickname,avatar from ".tablename("wx_shop_member")." where id=:id",array(":id"=>$value['uid']));

	            $list[$key]['nickname']=$value['nickname'];

				$list[$key]['avatar']=$value['avatar'];

				// foreach($paytype as $kk=>$vv){

				// 	if($vv==$value['type']){

				//       $list[$key]['types']=$kk;

				// 	  break;

				// 	}

				// }
			}
		}

		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		// if ($_GPC['export'] == 1) 
		// {
		// 	if ($_GPC['type'] == 1) 
		// 	{
		// 		plog('finance.log.withdraw.export', '??????????????????');
		// 	}
		// 	else 
		// 	{
		// 		plog('finance.log.recharge.export', '??????????????????');
		// 	}
		// 	foreach ($list as &$row ) 
		// 	{
		// 		$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
		// 		$row['groupname'] = ((empty($row['groupname']) ? '?????????' : $row['groupname']));
		// 		$row['levelname'] = ((empty($row['levelname']) ? '????????????' : $row['levelname']));
		// 		$row['typestr'] = $apply_type[$row['applytype']];
		// 		if ($row['status'] == 0) 
		// 		{
		// 			if ($row['type'] == 0) 
		// 			{
		// 				$row['status'] = '?????????';
		// 			}
		// 			else 
		// 			{
		// 				$row['status'] = '?????????';
		// 			}
		// 		}
		// 		else if ($row['status'] == 1) 
		// 		{
		// 			if ($row['type'] == 0) 
		// 			{
		// 				$row['status'] = '????????????';
		// 			}
		// 			else 
		// 			{
		// 				$row['status'] = '??????';
		// 			}
		// 		}
		// 		else if ($row['status'] == -1) 
		// 		{
		// 			if ($row['type'] == 0) 
		// 			{
		// 				$row['status'] = '';
		// 			}
		// 			else 
		// 			{
		// 				$row['status'] = '??????';
		// 			}
		// 		}
		// 		if ($row['rechargetype'] == 'system') 
		// 		{
		// 			$row['rechargetype'] = '??????';
		// 		}
		// 		else if ($row['rechargetype'] == 'wechat') 
		// 		{
		// 			$row['rechargetype'] = '??????';
		// 		}
		// 		else if ($row['rechargetype'] == 'alipay') 
		// 		{
		// 			$row['rechargetype'] = '?????????';
		// 		}
		// 	}
		// 	unset($row);
		// 	$columns = array();
		// 	$columns[] = array('title' => '??????', 'field' => 'nickname', 'width' => 12);
		// 	$columns[] = array('title' => '??????', 'field' => 'realname', 'width' => 12);
		// 	$columns[] = array('title' => '?????????', 'field' => 'mobile', 'width' => 12);
		// 	$columns[] = array('title' => '????????????', 'field' => 'levelname', 'width' => 12);
		// 	$columns[] = array('title' => '????????????', 'field' => 'groupname', 'width' => 12);
		// 	$columns[] = array('title' => (empty($type) ? '????????????' : '????????????'), 'field' => 'money', 'width' => 12);
		// 	if (!(empty($type))) 
		// 	{
		// 		$columns[] = array('title' => '????????????', 'field' => 'realmoney', 'width' => 12);
		// 		$columns[] = array('title' => '???????????????', 'field' => 'deductionmoney', 'width' => 12);
		// 		$columns[] = array('title' => '????????????', 'field' => 'typestr', 'width' => 12);
		// 		$columns[] = array('title' => '????????????', 'field' => 'applyrealname', 'width' => 24);
		// 		$columns[] = array('title' => '?????????', 'field' => 'alipay', 'width' => 24);
		// 		$columns[] = array('title' => '??????', 'field' => 'bankname', 'width' => 24);
		// 		$columns[] = array('title' => '????????????', 'field' => 'bankcard', 'width' => 24);
		// 		$columns[] = array('title' => '????????????', 'field' => 'applytime', 'width' => 24);
		// 	}
		// 	$columns[] = array('title' => (empty($type) ? '????????????' : '??????????????????'), 'field' => 'createtime', 'width' => 12);
		// 	if (empty($type)) 
		// 	{
		// 		$columns[] = array('title' => '????????????', 'field' => 'rechargetype', 'width' => 12);
		// 	}
		// 	$columns[] = array('title' => '??????', 'field' => 'remark', 'width' => 24);
		// 	m('excel')->export($list, array('title' => ((empty($type) ? '??????????????????-' : '??????????????????')) . date('Y-m-d-H-i', time()), 'columns' => $columns));
		// }
		$total = pdo_fetchcolumn('select count(*) from '. tablename('wx_shop_game_video') .' v left join '.tablename('wx_shop_member').' m on m.id=v.uid where 1 and '.$condition,$params);
		$pager = pagination2($total, $pindex, $psize);
		// $groups = m('member')->getGroups();
		// $levels = m('member')->getLevels();
		include $this->template();
	}

	public function recharge() 
	{
		$this->main(0);
	}
	public function withdraw() 
	{
		$this->main(1);
	}


	public function jl(){

		global $_W;
		global $_GPC;


		$videoid = $_GPC['videoid'];
		
		$uid = $_GPC['uid'];

		$res = pdo_fetchall('select r.*,m.nickname,m.avatar from ' . tablename('wx_shop_game_redlog'.substr($videoid, -1)) . ' r left join '.tablename('wx_shop_member').' m on m.id=r.uid where r.uniacid=:uniacid and r.video_uid=:video_uid and r.video_id=:video_id',array(':uniacid'=>$_W['uniacid'],':video_uid'=>$uid,':video_id'=>$videoid));

		foreach ($res as $key => $value) {

			// if($value['status'] == 1 && $value['type'] == 3 || $value['type'] == 4 || $value['type'] == 5) {
			// 	$res[$key]['status_s'] = '?????????';

			// } else if($value['status'] == 1 && $value['type'] == 1 || $value['type'] == 2) {
				
			// 	$res[$key]['status_s'] = '?????????';
			
			// } else {

			// 	$res[$key]['status_s'] = '?????????';


			// }

			if($value['type'] == 3 || $value['type'] == 4 || $value['type'] == 5) {

				if($value['status'] == 1){
					
					$res[$key]['status_s'] = '?????????';

				} else {

					$res[$key]['status_s'] = '?????????';

				}

			}

			if($value['type'] == 1 || $value['type'] == 2) {
				$res[$key]['status_s'] = '?????????';
			}


			if($value['type'] == 1) {
				$res[$key]['type_s'] = '????????????';
			} else if($value['type'] == 2) {
				$res[$key]['type_s'] = '????????????';
			} else if($value['type'] == 3) {
				$res[$key]['type_s'] = '????????????';
			} else if($value['type'] == 4) {
				$res[$key]['type_s'] = '????????????';
			} else if($value['type'] == 5) {
				$res[$key]['type_s'] = '????????????';
			}

			// $res[$key]['type_s'] = m('game')->type_money($value['type']);
		}

		echo json_encode($res,true);exit;


		// echo '<pre>';
		//     print_r($res);
		// echo '</pre>';



	}

}

function alltable($condition){

	// for ($i=0; $i < 10; $i++) { 
	// 	if($i == 9) {
	// 		$sql .= 'select * from ' . tablename('wx_shop_game_video'.$i) . ' where '.$condition;

	// 	} else {

	// 		$sql .= 'select * from ' . tablename('wx_shop_game_video'.$i) . ' where '.$condition.' union all ';

	// 	}
	// }


	// return $sql;
	// echo '<pre>';
	//     print_r($sql);
	// echo '</pre>';
	// exit;

}
?>
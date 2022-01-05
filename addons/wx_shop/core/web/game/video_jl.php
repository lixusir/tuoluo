<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}

class Video_jl_WxShopPage extends WebPage
{
	public function main($type = 0) 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' uniacid=:uniacid ';
		$condition1 = '';
		$params = array(':uniacid' => $_W['uniacid']);
		
		
		
		$paytype = m('game')->paytype();
		// echo '<pre>';
		//     print_r($paytype);
		// echo '</pre>';
		// exit;
		unset($paytype['广告等级红包']);
		unset($paytype['视频分销红包']);
		unset($paytype['视频分红红包']);
		unset($paytype['升级红包']);

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
			$condition .= ' and time >= :starttime AND time <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if(!empty($_GPC['paytype'])) {

			$condition .= ' and  type=:paytype';

			$params[':paytype'] = intval($_GPC['paytype']);


		}

		if(!empty($_GPC['keyword'])) {

			$condition .= ' and  uid=:keyword';

			$params[':keyword'] = intval($_GPC['keyword']);


		}

		// echo '<pre>';
		//     print_r($condition);
		// echo '</pre>';
		// exit;
		$sql = ' select * from (' .alltable($condition).') log where 1 order by log.time desc , log.id desc';

		if(!empty($_GPC['paytype'])) {

		// echo '<pre>';
		//     print_r($sql);
		// echo '</pre>';
		// exit;
		}

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

				$info=pdo_fetch("select nickname,avatar from ".tablename("wx_shop_member")." where id=:id",array(":id"=>$value['uid']));

	            $list[$key]['nickname']=$info['nickname'];

				$list[$key]['avatar']=$info['avatar'];

				foreach($paytype as $kk=>$vv){

					// unset($vv)

					if($vv==$value['type']){

				      $list[$key]['types']=$kk;

					  break;

					}

				}
			}
		}

		$total = pdo_fetchcolumn('select count(*) from (' . alltable($condition) . ') log  where 1 ', $params);
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
}

function alltable($condition){

	for ($i=0; $i < 10; $i++) { 
		if($i == 9) {
			$sql .= 'select * from ' . tablename('wx_shop_game_log'.$i) . ' where '.$condition;

		} else {

			$sql .= 'select * from ' . tablename('wx_shop_game_log'.$i) . ' where '.$condition.' union all ';

		}
	}


	return $sql;
	// echo '<pre>';
	//     print_r($sql);
	// echo '</pre>';
	// exit;

}
?>
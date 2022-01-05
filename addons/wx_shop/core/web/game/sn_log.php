<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}

class Sn_log_WxShopPage extends WebPage
{
	public function main($type = 0) 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' uniacid=:uniacid  and type=:type ';
		$condition1 = '';
		$params = array(':uniacid' => $_W['uniacid'],':type'=>5);
		
		


		// echo "<pre>";
		// 	print_r();
		// echo "</pre>";
		// exit;
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
			$condition .= ' and time >= :starttime AND time <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		$sql = ' select log.uid,sum(log.sn_money) as moneys,log.time,log.time_jr,group_concat(log.video_id) as video_ids,log.bs,log.status,log.bili from (' .alltable($condition).') as log where 1 group by log.time_jr,log.uid order by log.time_jr desc';

		if (empty($_GPC['export'])) 
		{
			$sql .= ' LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}
		
		$list = pdo_fetchall($sql, $params);

		// echo "<pre>";
		// 	print_r($list);
		// echo "</pre>";
		// exit;
		$item=array();
		foreach($list as $k=>$v){
		    if(!isset($item[$v['time_jr']])){
		    	// echo 1;
		        $item[$v['time_jr']]=$v;
		    }else{
		    	// echo 2;
		        $item[$v['time_jr']]['moneys']+=$v['moneys'];
		        $item[$v['time_jr']]['uid'].= ','.$v['uid'];
		    }
		}
		// echo "<pre>";
		// 	print_r($item);
		// echo "</pre>";
		// exit;
		foreach ($item as $key => $value) {
			if(empty($key)){
				unset($item[$key]);
			} else {
				//获取今天的广告
				$stime =$key;
				// $stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
				$etime = $key+86400;
				// $etime = mktime(23,59,59,date("m"),date("d"),date("Y"));
				$money = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_video') . ' where uniacid=:uniacid and time>:stime and time<=:etime ',array(':uniacid'=>$_W['uniacid'],':stime'=>$stime,':etime'=>$etime));

				$item[$key]['qw'] = empty($money)?0:$money;
				
				// if()

				// $item[$key]['time_jr'] = empty($money)?0:$money;

			}


		}
		// $setime = mktime(0,0,0,date("m"),date("d"),date("Y"));

		

		// //昨天23点
		// $zsetime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		// for ($i=0; $i < 10; $i++) { 
					
		// 		// echo '<pre>';
		// 		//     print_r($i);
		// 		// echo '</pre>';

		// 		$reslog = pdo_fetchall('select uid,sum(sn_money) as sn_moneys,group_concat(video_id) as video_ids  from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status and time>=:stime and time<=:etime group by uid',array(':uniacid'=>$_W['uniacid'],':status'=>0,':stime'=>$setime,':etime'=>$zsetime));

		// 		echo '<pre>';
		// 		    print_r($reslog);
		// 		echo '</pre>';
							
		// 		if(!empty($reslog)) {


		// 			foreach ($reslog as $key => $value) {


		// 				//神鸟
		// 				if(empty($sn_member[$value['uid']])){

		// 					$sn_member[$value['uid']] = array(
		// 						'sn_money'=>$value['sn_moneys']
		// 					);

		// 				} else {

		// 					$sn_member[$value['uid']] = array(
		// 						'sn_money'=>$value['sn_moneys']+$sn_member[$value['uid']]['sn_money']
		// 					);

		// 				}

		// 			}


		// 		}
		// 	}

		// 	echo "<pre>";
		// 		print_r($sn_member);
		// 	echo "</pre>";
		// 	exit;

		// pdo_fetchall('select uid,sum(money) as moneys from ' . tablename('wx'))

		// echo "<pre>";
		// 	print_r($item);
		// echo "</pre>";
		// exit;
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


		// echo "<pre>";
		// 	print_r($item);
		// echo "</pre>";
		// exit;

		$video_ids = $_GPC['video_ids'];

		// $str = '30,31,28,32,21,18,22,29,23,20,19,24,25,27';

		$member = explode(",", $video_ids);

    	$tes = array();
    	foreach ($member as $key => $value) {
    		if(substr($value, -1) == 0) {
    			$tes['cont0'] .= $value.',';
    		} else if(substr($value, -1) == 1) {
    			$tes['cont1'] .= $value.',';
    		} else if(substr($value, -1) == 2) {
    			$tes['cont2'] .= $value.',';
    		} else if(substr($value, -1) == 3) {
    			$tes['cont3'] .= $value.',';
    		} else if(substr($value, -1) == 4) {
    			$tes['cont4'] .= $value.',';
    		} else if(substr($value, -1) == 5) {
    			$tes['cont5'] .= $value.',';
    		} else if(substr($value, -1) == 6) {
    			$tes['cont6'] .= $value.',';
    		} else if(substr($value, -1) == 7) {
    			$tes['cont7'] .= $value.',';
    		} else if(substr($value, -1) == 8) {
    			$tes['cont8'] .= $value.',';
    		} else if(substr($value, -1) == 9) {
    			$tes['cont9'] .= $value.',';
    		}
    	}


    	for ($i=0; $i < 10; $i++) {

    		if(!empty($tes['cont'.$i])) {
				$table .= ' select * from ' . tablename('wx_shop_game_redlog'.$i) . ' where video_id in ('.rtrim($tes['cont'.$i],',').') and type=5 union all ';
    		}
    	}


    	$table = rtrim($table,'union all');



    	$sql = 'select log.uid,sum(log.sn_money) as moneys,log.bs,log.status,log.time from ('.$table.' ) as log where 1 and log.uniacid=:uniacid group by log.uid';
    	
    	
		$res = pdo_fetchall($sql,array(':uniacid'=>$_W['uniacid']));

		foreach ($res as $key => $value) {

			$member = pdo_fetch('select nickname,mobile,avatar from' .tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:uid',array(':uniacid'=>$_W['uniacid'],':uid'=>$value['uid']));


			if($value['status'] == 1) {
				$res[$key]['status_s'] = '已发放';
				$res[$key]['time'] = date("Y-m-d H:i:s",$value['time']);

			} else if($value['status'] == 0) {
				$res[$key]['status_s'] = '未发放';
				$res[$key]['time'] = '';

			}
			$res[$key]['nickname'] = $member['nickname'];
			$res[$key]['mobile'] = $member['mobile'];
			$res[$key]['avatar'] = $member['avatar'];
			

		}



		echo json_encode($res,true);exit;


		// echo '<pre>';
		//     print_r($res);
		// echo '</pre>';



	}

}


function alltable($condition){

	for ($i=0; $i < 10; $i++) { 
		if($i == 9) {
			$sql .= 'select * from ' . tablename('wx_shop_game_redlog'.$i) . ' where '.$condition;

		} else {

			$sql .= 'select * from ' . tablename('wx_shop_game_redlog'.$i) . ' where '.$condition.' union all ';

		}
	}


	return $sql;
	// echo '<pre>';
	//     print_r($sql);
	// echo '</pre>';
	// exit;

}
?>
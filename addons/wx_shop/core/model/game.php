<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Game_WxShopModel
{

	public function __construct() 
	{

	}

	public function getuid($token)

    {

	  if($token!=""){

		  $uinfo=pdo_fetch("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		  if(!$uinfo){

             show_json_w(-2,null,"token失效，请重新登录");

		  }

		  return $uinfo['id'];

		  

	  }else{

		  show_json_w(-2,null,"token不能为空");

	  }

    }


	public function paytype($title="")

	{



		$array['后台充值']=100;

		$array['购买商品']=1;

		$array['商品回收']=2;
		
		$array['5秒收益']=3;

		$array['大转盘中奖']=4;
		
		$array['大转盘抽奖']=5;

		$array['离线收益']=6;
		
		// $array['离线收益翻倍']=7;

		$array['敲蛋抽奖消费']=8;
		
		$array['敲蛋中奖']=9;

		

		$array['广告等级红包']=10;
		

		$array['视频分销红包']=11;

		$array['视频分红红包']=12;
		
		$array['视频神鸟红包']=24;
		

		$array['升级红包']=13;


		$array['答题彩蛋']=14;
		

		$array['签到奖励']=15;
		
		$array['微信绑定']=16;
		

		$array['徒弟认证奖励']=17;
		
		$array['徒孙认证奖励']=18;

		$array['认证奖励']=19;
		
		$array['在线奖励']=20;

		$array['答题奖励']=21;


		$array['视频金币奖励'] = 22;

		$array['视频翻倍金币奖励'] = 23;



		

		if($title==""){

           return $array;

		}else{

		  return $array[$title];

		}



	}

	public function type_money($title="")

	{



		// $array['后台充值']=100;

		$array['徒弟返佣']=1;

		$array['徒孙返佣']=2;
		
		$array['徒弟分红']=3;

		$array['徒孙分红']=4;
		
		$array['神鸟奖励']=5;
		


		// $array['等级红包']=5;
		
		// $array['大转盘抽奖']=5;

		// $array['离线收益']=6;
		
		// $array['离线收益翻倍']=7;

		// $array['敲蛋抽奖消费']=8;
		
		// $array['敲蛋中奖']=9;

		



		if($title==""){

           return $array;

		}else{

		  return $array[$title];

		}



	}


	//开通赠送
	public function getSp($uid) 
	{
		global $_W;

		if(empty($uid)) return;
	
		
		// pre
		$member = pdo_fetch('select is_one,lj_zxtime from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));
		
		if(empty($member)) return;

		if(empty($member['lj_zxtime'])) {
			pdo_update('wx_shop_member',array('lj_zxtime'=>time()),array('id'=>$uid));
		}


		$game_goods = pdo_fetch('select * from ' . tablename('wx_shop_game_goods') . '  where uniacid=:uniacid and level=1',array(':uniacid'=>$_W['uniacid']));

		if(empty($game_goods)) return;
 
		$games = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

		if(!empty($games)) return;


		if($member['is_one'] == 1) return;

		// $sql = 'insert into ' . tablename('wx_shop_game'.substr($uid, -1)) . ' (`uniacid`,`uid`,`level`,`goodsid`,`goodsType`,`goodslevel`,`income`,`status`,`lasttime`) values';

		// $sql1 = '';

		// for ($i=0; $i < 12; $i++) { 

		// 	$l = $i+1;
		// 	if($l == 1) {
		// 		$sql1 .= "(".$_W['uniacid']."," .$uid. ",".$l.",".$game_goods['id'].",'".$game_goods['goodsType']."',1,".$game_goods['income'].",1,'".time()."')";
		// 	}
		// 	 else {
		// 		$sql1 .= "(".$_W['uniacid']."," .$uid. ",".$l.",0,0,0,0,0,0),";
		// 	}

		// }

		// $sql1 = rtrim($sql1, ',');

		// echo '<pre>';
		//     print_r($sql1);
		// echo '</pre>';
		// exit;
		// $sql .= $sql1;


		

		pdo_update('wx_shop_member',array('game_level'=>1,'is_one'=>1),array('id'=>$uid));

		pdo_insert('wx_shop_game'.substr($uid, -1),array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$uid,
			'level'=>1,
			'goodsid'=>$game_goods['id'],
			'goodsType'=>$game_goods['goodsType'],
			'goodslevel'=>$game_goods['level'],
			'income'=>$game_goods['income'],
			'status'=>1,
			'lasttime'=>time()
		));

		// echo '<pre>';
		//     print_r($sql);
		// echo '</pre>';
		// pdo_query($sql);
	
	}


	//一秒执行一次
	//五秒推送 方案一
	public function ontimes($member)
	{

		// global $_W;

		// echo '<pre>';
		//     print_r(1112222);
		// echo '</pre>';
		//在线人


		$_W['uniacid'] = 96;

		$fp = fopen(dirname(__FILE__).'/game/log_mess.txt', "w+");

		file_put_contents(dirname(__FILE__).'/curl',json_encode( $member)); 

		// echo '<pre>';
		//     print_r(flock($fp,LOCK_EX));
		// echo '</pre>';
        if(flock($fp,LOCK_EX)){

        	$redis = $this->getRedis();


        	if(empty($member))return;
        	// $list 	=	array();
        	
        	// foreach ($member as $key => $val) {
        		

        	// }
        	foreach ($member as $key => $value) {

				$game = pdo_fetchall('select id,uid,income,goodslevel,lasttime from ' . tablename('wx_shop_game'.substr($value, -1)) . ' where uid=:uid and status=1 limit 12',array(':uid'=>$value));

				// echo '<pre>';
				//     print_r($game);
				// echo '</pre>';

				// echo '<pre>';
				//     print_r($value);
				// echo '</pre>';
				// if()
				foreach ($game as $keys => $val) {
					
					if($val['lasttime'] <= time() - 5) { //时间超过五秒发放奖励
						
						$time = time();
						
						pdo_update("wx_shop_game".substr($val['uid'], -1),array('lasttime'=>$time),array('id'=>$val['id']));//更新时间

						// pdo_insert('')

						$this->setMoney($val['uid'],'jinbi',$val['income'],'5秒收益','5秒收益等级为'.$val['goodslevel']);

						$arr = array(
							'type' => 1, //类型 
							'uid'=>$val['uid'],//会员信息 
							'id'=>$val['id'],//gameid
							'income'=>$val['income'], //收益金额
							'time'=>$time
						);


	   					$res = $redis->rPush('messinfo12019', serialize($arr));//往尾部插入一条消息

	   					// echo '<pre>';
	   					//     print_r($res);
	   					// echo '</pre>';

					}


				}
        	}


		}

		flock($fp,LOCK_UN);

		fclose($fp);


	}

	//一秒执行一次
	//五秒推送 方案二
	public function ontimes_2($member)
	{

		// global $_W;

		// echo '<pre>';
		//     print_r(1112222);
		// echo '</pre>';
		//在线人


		$_W['uniacid'] = 96;

		$fp = fopen(dirname(__FILE__).'/game/log_mess.txt', "w+");

		file_put_contents(dirname(__FILE__).'/curl',json_encode( $member)); 

		// echo '<pre>';
		//     print_r(flock($fp,LOCK_EX));
		// echo '</pre>';
        if(flock($fp,LOCK_EX)){

        	$redis = $this->getRedis();


        	if(empty($member))return;


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

        	// echo "<pre>";
        	// 	print_r($tes);
        	// echo "</pre>";


        



        	for ($i=0; $i < 10; $i++) {

        		if(!empty($tes['cont'.$i])) {
					$table .= ' select * from ' . tablename('wx_shop_game'.$i) . ' where uid in ('.rtrim($tes['cont'.$i],',').') union all';
        		}
        	}


        	$table = rtrim($table,'union all');

        	// echo "<pre>";
        	// 	print_r($table);
        	// echo "</pre>";


        	$sql = 'select id,uid,income,goodslevel,lasttime,goodsid from ('.$table.') log where 1';
        	
        	
			$game = pdo_fetchall($sql);

			foreach ($game as $keys => $val) {
				
				if($val['lasttime'] <= time() - 5) { //时间超过五秒发放奖励
					
					$time = time();
					
					pdo_update("wx_shop_game".substr($val['uid'], -1),array('lasttime'=>$time),array('id'=>$val['id']));//更新时间

					// pdo_insert('')

					//去掉实时更新钱
					// $this->setMoney($val['uid'],'jinbi',$val['income'],'5秒收益','5秒收益等级为'.$val['goodslevel']);

					//更改队列
					$ars = array($val['uid'],'jinbi',$val['income'],'5秒收益','5秒收益等级为'.$val['goodslevel'],$val['goodsid'],$val['id'],time());
   					
   					$resa = $redis->rPush('messjinbi2019', serialize($ars));//往尾部插入一条消息


					$arr = array(
						'type' => 1, //类型 
						'uid'=>$val['uid'],//会员信息 
						'id'=>$val['id'],//gameid
						'income'=>$val['income'], //收益金额
						'time'=>$time
					);


   					$res = $redis->rPush('messinfo12019', serialize($arr));//往尾部插入一条消息

   					// echo '<pre>';
   					//     print_r($res);
   					// echo '</pre>';

				}


			}


		}

		flock($fp,LOCK_UN);

		fclose($fp);


	}


	public function getRedis()
	{

		$redis = new Redis();

		$redis->pconnect('127.0.0.1', 6379);

		return $redis;

		// $arrs = array('1'=>1);

		// $rs = $redis->rPush("mess".$_W['uniacid'],serialize($arrs));

		// // echo '<pre>';
		// //     print_r($rs);
		// // echo '</pre>';

		// $res = $redis->lrange("mess".$_W['uniacid'],0,20);

		// foreach ($res as $key => $value) {
			
		// 	$mass = unserialize($value);

		// 	if($key == 5) {
		// 		echo '<pre>';
		// 		    print_r($value);
		// 		echo '</pre>';
		// 		$redis->lrem("mess".$_W['uniacid'],$key,0);
				
		// 	}
		// 	echo '<pre>';
		// 	    print_r($mass);
		// 	echo '</pre>';




		// }
		// echo '<pre>';
		//     print_r($res);
		// echo '</pre>';
		// exit;

	}


	//变动某人账号余额信息

	public  function setMoney($uid,$type,$money,$title="",$details="",$level=0){

		global $_W;

		if(empty($uid)) return;

		$fp = fopen(dirname(__FILE__).'/game/log'.$uid.'.txt', "w+");

		// echo '<pre>';
		//     print_r($fp);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r(flock($fp,LOCK_EX));
		// echo '</pre>';
        if(flock($fp,LOCK_EX)){

        	$nowmoney=pdo_fetchcolumn("select ".$type." from ".tablename("wx_shop_member")." where id=:id",array(":id"=>$uid));

		  	$nowmoney=$nowmoney+$money;

		  	// echo '<pre>';
		  	//     print_r($nowmoney);
		  	// echo '</pre>';

		  	if($nowmoney >= 0) {

		  		if($type == 'credit_red') {

			  		$data = array();


		  			if($title == '升级红包') {
				  		//防止
				  		$data['status'] = 2;

				  		$data['level'] = $level;

				  	} else {
				  		
				  		pdo_update("wx_shop_member",array($type=>$nowmoney),array("id"=>$uid));
				  		
			  			$data['status'] = 3;

				  	}


		  			//赠送红包换另外一个表

			  		$data['uniacid'] = $_W['uniacid'];
			  		$data['uid'] = $uid;
			  		$data['type'] = $this->paytype($title);
			  		$data['logno'] = 'RC'.time();
			  		$data['title'] = $details;
			  		$data['money'] = $money;
			  		$data['realmoney'] = $money;
			  		$data['createtime'] = time();

			  		pdo_insert('wx_shop_game_member_log',$data);

					$ids = pdo_insertid();


		  		} else {
			  		//等于彩蛋币累加 //后台充值不计算
					if($type == 'credit_b' && $data['type'] != 100 && $money > 0) {

	        			$lj_c=pdo_fetchcolumn("select lj_c from ".tablename("wx_shop_member")." where id=:id",array(":id"=>$uid));

	        			$lj_c_1 = $lj_c+$money;
	        			// echo '<pre>';
	        			//     print_r($lj_c);
	        			// echo '</pre>';
				  		pdo_update("wx_shop_member",array('lj_c'=>$lj_c_1),array("id"=>$uid));


					}

				  	pdo_update("wx_shop_member",array($type=>$nowmoney),array("id"=>$uid));

				  	$status = 1;

				  	//有观看视频双倍收益 
				  	if($title == '视频金币奖励' || $title == '离线收益') {
				  		//防止
				  		$status = 0;

				  	}
				  	// echo "<pre>";
				  	// 	print_r($status);
				  	// echo "</pre>";

			  		$data = array();

			  		$data['uid'] = $uid;

			  		$data['uniacid'] = $_W['uniacid'];

			  		$data['money'] = $money;
			  		
			  		$data['level'] = $level;
	 
			  		$data['type'] = $this->paytype($title);

			  		

			  		$data['content'] = $details;


			  		$data['settype'] = $type;

			  		$data['time'] = time();

			  		$data['status'] = $status;
			  		

			  		$tablename="wx_shop_game_log".substr($uid,-1);

			  		// $tablename="wx_shop_game_log0";

					pdo_insert($tablename,$data);

					$ids = pdo_insertid();

		  		}


				


		  	}
        	


		}

		flock($fp,LOCK_UN);

		fclose($fp);

		return $ids;

	}


	public function getWeizhi($uid) {
		global $_W;

		if(empty($uid)) return;



		$games = pdo_fetchcolumn('select count(id) from ' . tablename('wx_shop_game'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and  status=1 ',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

		if($games + 1 >= 13) {

			return 0;

		} else {

			return 1;
		}


	}



	//获取小数点随机数
	public function randFloat($max = 1,$min = 0) 
	{

	   	$num = $min + mt_rand() / mt_getrandmax() * ($max - $min);

	   	return sprintf("%.2f",$num);
	
	}


	//获取会员最高购买等级价格
	public function getZg($uid){

		global $_W;

		if(empty($uid)) return;

		
		// pre
		$member = pdo_fetch('select id,game_level from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));
		
		if(empty($member)) return;



		



		if($member['game_level'] <= 5) {
			//只能购买第一个
			$game_level = 1;

		} else {

			$game_level = $member['game_level'] - 4;

		}

		$game_goods = pdo_fetch('select money,money_z,money_max from ' . tablename('wx_shop_game_goods') . '  where uniacid=:uniacid and level=:game_level',array(':uniacid'=>$_W['uniacid'],':game_level'=>$game_level));

		
		$buy_logs = pdo_fetch('select * from ' . tablename('wx_shop_game_goods_log'.substr($member['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and level=:level and type=:type order by id desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':level'=>$game_level,':type'=>0));

		// echo '<pre>';
		//     print_r($member['game_level']);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($game_level);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($buy_logs);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($game_goods);
		// echo '</pre>';

		if(empty($buy_logs)) {

			$money = $game_goods['money'];

		} else {

			//获取上次增长金币数量
			$money_z = $buy_logs['money_z'];

			// echo '<pre>';
			//     print_r($money_z);
			// echo '</pre>';

			$money = $money_z + ($money_z * ($game_goods['money_z'] / 100));


		}

		// echo '<pre>';
		//     print_r($money);
		// echo '</pre>';


		//是否超过封顶值
		if($money >= $game_goods['money_max']) {

			$money = $game_goods['money_max'];

		}

		return round($money, 2);

		// echo '<pre>';
		//     print_r($money);
		// echo '</pre>';



	}

	//获取会员可购买商品
	public function getSp1($uid)
	{

		global $_W;

		if(empty($uid)) return;




		
		// pre
		$member = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));
		
		if(empty($member)) return;

		// $redis = $this->getRedis();

        $game_level = pdo_fetchcolumn("select game_level from ".tablename("wx_shop_member")." where id=:id and uniacid=:uniacid",array(":id"=>$uid,':uniacid'=>$_W['uniacid']));
		

        // echo '<pre>';
        //     print_r($uid);
        // echo '</pre>';


        //最高商品等级37
		$game_goods = pdo_fetchall('select * from ' . tablename('wx_shop_game_goods') . '  where uniacid=:uniacid and level <= 37',array(':uniacid'=>$_W['uniacid']));

		// echo '<pre>';
		//     print_r($game_goods);
		// echo '</pre>';


		foreach ($game_goods as $key => $value) {

			//五级以下只能购买一个
			$status = 0;
			$status_1 = 0;
			if($game_level <= 5) {
				//只能购买第一个
				if($key == 0) {
					$game_goods[$key]['status'] = 1;
					$status = 1;
				} else {
					$game_goods[$key]['status'] = 0;

				}

			} else if($game_level <= 7){

				$adr = $game_level - 4;

				if($key < $adr) {
					
					$game_goods[$key]['status'] = 1;
					$status = 1;

				
				} else {
					
					$game_goods[$key]['status'] = 0;
				
				}


			} else {

				//金币可购买!
				$adq = $game_level - 4;

				//其他币可购买
				$adw = $game_level - 2;


				if($key < $adq) {
					
					$game_goods[$key]['status'] = 1;
					$status = 1;

				
				} else if($key < $adw){

					// $game_goods[$key]['status_1'] = 0;
					$game_goods[$key]['status_1'] = 1;
					// $status_1 = 0;
					$status_1 = 1;



				} else {
					
					$game_goods[$key]['status'] = 0;


				}



			}






			if($status == 1) {
					
				$buy_logs = pdo_fetch('select id,money_z from ' . tablename('wx_shop_game_goods_log'.substr($member['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and level=:level and type=:type order by id desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':level'=>$value['level'],':type'=>0));


				// echo '<pre>';
				//     print_r($buy_logs);
				// echo '</pre>';

				if(empty($buy_logs)) {


					$money = $value['money'];

				} else {

					//获取上次增长金币数量
					$money_z = $buy_logs['money_z'];

					$money = $money_z + ($money_z * ($value['money_z'] / 100));


				}

				//是否超过封顶值
				if($money >= $value['money_max']) {

					$money = $value['money_max'];

				}

				// echo '<pre>';
				//     print_r($money);
				// echo '</pre>';

				$game_goods[$key]['zx_money'] = round($money, 2);



			}

			if($status_1 == 1) {

				$buy_logs = pdo_fetch('select id,money_z from ' . tablename('wx_shop_game_goods_log'.substr($member['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and level=:level and type=:type order by id desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':level'=>$value['level'],':type'=>1));

					


				if(empty($buy_logs)) {

					$money = $value['b_money'];

				} else {

					//获取上次增长彩蛋数量
					$b_money_z = $buy_logs['money_z'];

					// +($game_goods['b_money_z'] / 100)

					$money = $b_money_z + ($b_money_z * ($value['b_money_z'] / 100));


				}	




				//是否超过封顶值
				if($money >= $value['b_money_max']) {

					$money = $value['b_money_max'];

				}

				$game_goods[$key]['zx_money'] = round($money, 2);


			}
			


		}


		$arrs['game_goods_'.$uid] = $game_goods;


		// $rs = $redis->rPush("game_goods".$_W['uniacid'],serialize($arrs));

		// echo '<pre>';
		//     print_r($rs);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($game_goods);
		// echo '</pre>';
		// exit;

		return $game_goods;
	}

	public function logout($uid)
	{

		global $_W;

		if(empty($uid)) return;

		pdo_update('wx_shop_member',array('logouttime'=>time()),array('id'=>$uid));


	}


	public function login($uid)
	{

		global $_W;

		if(empty($uid)) return;


		//获取用户信息
		$member = pdo_fetch('select logintime,logouttime from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));

		if($member['logouttime'] == 0) {
			return;
		}

		$time = time();

		//离线时间
		$lx_time = $time - $member['logouttime'];


		//修改鸟的发放时间
		pdo_update('wx_shop_game'.substr($uid, -1),array('lasttime'=>time()),array('uid'=>$uid));
		// file_put_contents(dirname(__FILE__).'/lixian1',json_encode( $lx_time)); 
		
		// file_put_contents(dirname(__FILE__).'/time',json_encode( $time)); 


		//低于十分钟不算收益
		$shi_time = 60 * 10;


		//高于两个小时 算2小时
		$sx_time = 60 * 60 * 2;

		//登录已经加上
		// pdo_update('wx_shop_member',array('logintime'=>$time),array('id'=>$uid));
		
		if($lx_time < $shi_time) {

			return 0;

		}

		if($lx_time >= $sx_time) {

			$js_times =  $sx_time;

		} else {
			
			$js_times = $lx_time;
		
		}

		// file_put_contents(dirname(__FILE__).'/js_times',json_encode( $js_times)); 


		//获取计算的次数
		$suan_num = $js_times / 5;

		$suan_num = floor($suan_num);

		// file_put_contents(dirname(__FILE__).'/suan_num',json_encode( $suan_num)); 


		// $game = pdo_fetchall('select * from ' . tablename('wx_shop_game'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and status=1',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

		$game = pdo_fetchall('select g.id,gg.lx_income from ' . tablename('wx_shop_game'.substr($uid, -1)) . ' g left join '.tablename('wx_shop_game_goods').' gg on gg.id=g.goodsid where g.uniacid=:uniacid and g.uid=:uid and g.status=1',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));
		

		$num = 0;
		
		$zong = 0;
		
		foreach ($game as $key => $value) {
				
			$num = $value['lx_income'] * $suan_num;

			$zong += $num;

		}
		// file_put_contents(dirname(__FILE__).'/zong',json_encode( $zong)); 
		// file_put_contents(dirname(__FILE__).'/num',json_encode( $num)); 

		$ids = m('game')->setMoney($uid,'jinbi',$zong,'离线收益','用户离线收益'.$zong.',离线秒数'.$js_times);


		return array('sy_id'=>$ids,'jinbi'=>$zong);


	}


	public function getJiang($arrs)
	{

	    global $_W;

	    // echo '<pre>';

	    //     print_r($arrs);

	    // echo '</pre>';

	    //获取总比例

	    $zong = 0;

	    foreach ($arrs as $key => $value) {

	        $zong += $value['gl'];

	    }



	    foreach ($arrs as $key => $value) {

	        $num = mt_rand(1,$zong);

	        if($num <= $value['gl']) {

	            //中奖

	            $list = $arrs[$key];

	            break;

	        } else {

	            $zong -= $value['gl'];

	        }

	    }


	    return $list;

	}


	public function qd_jiang($zhong) 
	{

	    global $_W;

	    // echo '<pre>';
	    //     print_r($_W);
	    // echo '</pre>';

		//获取今天时间
		$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));


		$jinri = pdo_fetch('select id,data from ' . tablename('wx_shop_game_qdlist_day') . ' where uniacid=:uniacid and time>=:stime and time<=:etime',array(':uniacid'=>$_W['uniacid'],':stime'=>$stime,':etime'=>$etime));
		// echo '<pre>';
		//     print_r($jinri);
		// echo '</pre>';
		if(empty($zhong)) return 0;

		if(empty($jinri)) {

			return 0;
			

		} else {


					
			$jiang = unserialize($jinri['data']);

			// echo '<pre>';
			//     print_r($jiang);
			// echo '</pre>';

			// echo '<pre>';
			//     print_r($zhong);
			// echo '</pre>';
			if(empty($jiang)) {

				return 0;

			} else {

				$ax = 0;

				foreach ($jiang as $key => $value) {

					if($value['status'] == $zhong['status'] && $value['id'] == $zhong['id']) {
						//抽的奖存在
						$ax = 1;

						// echo '<pre>';
						//     print_r($key);
						// echo '</pre>';
						//去掉奖品
						unset($jiang[$key]);

						break;
						//终止循环
					} else {

						//奖品抽完
						$ax = 0;

					}

				}

				if($ax == 1) {
					//中奖
					// echo '<pre>';
					//     print_r($jiang);
					// echo '</pre>';
					pdo_update('wx_shop_game_qdlist_day',array('data'=>serialize($jiang)),array('id'=>$jinri['id']));

					return 1;
				
				} else {

					//奖品已被抽完
					return 0;
				}

			}



		}

	}


	public function gaozi(){

	    global $_W;


		$gaozi = pdo_fetchcolumn('select gaozi from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));


		$gaozi_arr = unserialize($gaozi);
		// echo '<pre>';
		//     print_r($gaozi_arr);
		// echo '</pre>';
		$num = mt_rand(0,count($gaozi_arr)-1);
		// echo '<pre>';
		//     print_r($num);
		// echo '</pre>';
		return $gaozi_arr[$num];


	}


    // //更新等级
    // public function setLevel22($uid) {
    	
    // 	global $_W;

    	

    // 	$member = m('member')->getMember($uid);

    // 	if(empty($member)) {
    //         // echo s;
    // 		return;
    // 	}
    // 	$jy = pdo_fetchcolumn('select ifnull(sum(fx_money),0) from ' . tablename('wx_shop_game_redlog'.substr($uid,-1)) . ' where uniacid=:uniacid and uid=:uid and type in (1,2)',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

    // 	// $jy = 21;
        

    // 	// echo '<pre>';
    // 	//     print_r($jy);
    // 	// echo '</pre>';
    // 	// exit;
    // 	//获取全部等级
    // 	$level = pdo_fetchall('select * from' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid  order by level asc',array(':uniacid'=>$_W['uniacid']));
    // 	// echo '<pre>';
    // 	//     print_r($level);
    // 	// echo '</pre>';
    // 	foreach ($level as $key => $value) {
    // 		if($jy >= $value['jy']) {
    // 			$yg = $level[$key];
    // 		} else {
    // 			break;
    // 		}
    // 	}
    // 	// echo '<pre>';
    // 	//     print_r($yg);
    // 	// echo '</pre>';

    // 	// if(0>0) {
    // 	// 	echo '大于';
    // 	// }

    // 	if(!empty($yg)) {


    // 		if($yg['id'] >= $member['gg_level'] &&  $jy >= $level[0]['jl']) {
    // 			//查看是否跳级
    // 			for ($i=$member['gg_level']; $i <= $yg['id']; $i++) { 

    // 				// echo '<pre>';
    // 				//     print_r($i);
    // 				// echo '</pre>';

    // 				$levels = pdo_fetch('select id from ' . tablename('wx_shop_game_log'.substr($uid,-1)) . ' where uniacid=:uniacid and uid=:uid and type=:type and  level=:level',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],'type'=>10,':level'=>$i));

    // 				if(empty($levels)) {


    // 					$money = $level[$i-1]['jl'];
    // 					$this->setMoney($member['id'],'credit_red',$money,'广告等级红包','('.$level[$i-1]['levelname'].')奖励'.$money,$i);

    // 				}


    // 			}
    			
    // 			//升级
    // 			if($yg['id'] > $member['gg_level'] ) {
    // 				// echo '修改的等级';
    // 				pdo_update('wx_shop_member',array('gg_level'=>$yg['id']),array('id'=>$member['id']));
 
    // 			}


    // 		}

    // 	}



    // }


    //获取等级
    public function getLevel($uid){
        global $_W;

        if(empty($uid)) {
            return;
        }

        $member = m('member')->getMember($uid);

        if(empty($member)) {
            return;
        }
        // echo '<pre>';
        //     print_r($member);
        // echo '</pre>';
        $level = pdo_fetch('select * from' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid and level=:level order by level asc',array(':uniacid'=>$_W['uniacid'],':level'=>$member['gg_levels']['level']+1));
        // echo '<pre>';
        //     print_r($level);
        // echo '</pre>';
        // exit;
        $arr = array();
        
        if(empty($level)) {
        	// echo '<pre>';
        	//     print_r($jy);
        	// echo '</pre>';
        	// exit;
            $arr['jy'] = 100;
        }
        // exit;



    	// $zong = pdo_fetchcolumn('select ifnull(sum(fx_money),0) from ' . tablename('wx_shop_game_redlog'.substr($uid,-1)) . ' where uniacid=:uniacid and uid=:uid and type in (1,2)',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));
		// $zong = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_log'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and type=11',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id']));

        $fx_money = 0;

		for ($i=0; $i < 10; $i++) { 
				
			// echo '<pre>';
			//     print_r($i);
			// echo '</pre>';

			$reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status and uid=:uid ',array(':uniacid'=>$_W['uniacid'],':status'=>1,':uid'=>$uid));

			// echo '<pre>';
			//     print_r($reslog);
			// echo '</pre>';

			if(!empty($reslog)) {
				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
				foreach ($reslog as $key => $value) {
					$fx_money += $value['fx_money'];
					// $fh_money += $value['fh_money'];

				}

			}
		}


		// $zong = $fx_money;

		$arr['zong'] = empty($fx_money)?0:$fx_money;


        $level_dq = pdo_fetch('select * from' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid and level=:level order by level asc',array(':uniacid'=>$_W['uniacid'],':level'=>$member['gg_levels']['level']));
        // echo "<pre>";
        // 	print_r($arr['zong']-$level_dq['jy']);
        // echo "</pre>";

        // echo "<pre>";
        // 	print_r($level['jy']-$level_dq['jy']);
        // echo "</pre>";

        $arr['jy'] = ($arr['zong']-$level_dq['jy'])/($level['jy']);
		

		$arr['zong'] = $arr['zong']-$level_dq['jy'];

		// echo "<pre>";
		//  	print_r($arr['jy']);
		//  echo "</pre>"; 

        $arr['jy'] = $arr['jy'] * 100;

        return $arr;
    }



    // //更新等级
    public function setLevel($uid) {
    	
    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	$member = m('member')->getMember($uid);

    	if(empty($member)) {
            // echo s;
    		return;
    	}
    	// $jy = pdo_fetchcolumn('select ifnull(sum(fx_money),0) from ' . tablename('wx_shop_game_redlog'.substr($uid,-1)) . ' where uniacid=:uniacid and uid=:uid and type in (1,2)',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

    	//总分销
    	$fx_money = 0;

		for ($i=0; $i < 10; $i++) { 
				
			// echo '<pre>';
			//     print_r($i);
			// echo '</pre>';

			$reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status and uid=:uid ',array(':uniacid'=>$_W['uniacid'],':status'=>1,':uid'=>$uid));

			// echo '<pre>';
			//     print_r($reslog);
			// echo '</pre>';

			if(!empty($reslog)) {
				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
				foreach ($reslog as $key => $value) {
					$fx_money += $value['fx_money'];
					// $fh_money += $value['fh_money'];

				}

			}
		}

		$jy = empty($fx_money)?0:$fx_money;

		// $jy = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_log'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and type=11',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id']));

    	// $jy = 300;
        // echo "<pre>";
        // 	print_r($jy);
        // echo "</pre>";
        // exit;
        $level_dq = pdo_fetch('select * from' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid and level=:level order by level asc',array(':uniacid'=>$_W['uniacid'],':level'=>$member['gg_levels']['level']));


        // $jy = $level_dq

        // $arr['zong']-$level_dq['jy']
        //减去当前拥有的
        $jy= $jy-$level_dq['jy'];
        // echo "<pre>";
        // 	print_r($jy);
        // echo "</pre>";
    	// echo '<pre>';
    	//     print_r($level_dq);
    	// echo '</pre>';
    	// exit;
    	//获取全部等级
    	$level = pdo_fetchall('select * from' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid and level>'.$level_dq['level'].'  order by level asc',array(':uniacid'=>$_W['uniacid']));
    	// echo '<pre>';
    	//     print_r($level);
    	// echo '</pre>';
    	foreach ($level as $key => $value) {
    		if($jy >= $value['jy']) {
    			$yg = $level[$key];
    		} else {
    			break;
    		}
    	}
    	// echo '<pre>';
    	//     print_r($yg);
    	// echo '</pre>';
    	// exit;

    	// if(0>0) {
    	// 	echo '大于';
    	// }

    	if(!empty($yg)) {


    		if($yg['id'] > $member['gg_level']) {
    			//查看是否跳级

    			for ($i=$member['gg_level']; $i < $yg['id']; $i++) { 

    				// echo '<pre>';
    				//     print_r($i);
    				// echo '</pre>';

    				$levels = pdo_fetch('select id from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and uid=:uid and type=:type and  level=:level',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],'type'=>10,':level'=>$i));

    				if(empty($levels)) {


    					$money = $level[$i-1]['jl'];
    					

    					// echo '<pre>';
    					//     print_r($money);
    					// echo '</pre>';
    					$this->setMoney($member['id'],'credit_red',$money,'广告等级红包','('.$level[$i-1]['levelname'].')奖励'.$money,$i);

    				}


    			}
    			
    			//升级
    			if($yg['id'] > $member['gg_level'] ) {
    				// echo '修改的等级';
    				pdo_update('wx_shop_member',array('gg_level'=>$yg['id']),array('id'=>$member['id']));
    
    			}


    		} else {
    			//
    			// echo '小于';
    		}

    	}
    	// exit;



    }


    //更新称号等级
    public function setBlevel($uid) {
    	
    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	$member = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));

    	if(empty($member)) {
            // echo s;
    		return;
    	}
    	// $jy = pdo_fetchcolumn('select ifnull(sum(fx_money),0) from ' . tablename('wx_shop_game_redlog'.substr($uid,-1)) . ' where uniacid=:uniacid and uid=:uid and type in (1,2)',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

    	$jy = pdo_fetchcolumn('select lj_c from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:uid ',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

    	// $jy = 300;
        

    	// echo '<pre>';
    	//     print_r($jy);
    	// echo '</pre>';
    	// exit;
    	//获取全部等级
    	$level = pdo_fetchall('select * from' . tablename('wx_shop_game_blevel') . ' where uniacid=:uniacid  order by level asc',array(':uniacid'=>$_W['uniacid']));
    	// echo '<pre>';
    	//     print_r($level);
    	// echo '</pre>';
    	foreach ($level as $key => $value) {
    		if($jy >= $value['jy']) {
    			$yg = $level[$key];
    		} else {
    			break;
    		}
    	}
    	// echo '<pre>';
    	//     print_r($yg);
    	// echo '</pre>';

    	// if(0>0) {
    	// 	echo '大于';
    	// }

    	if(!empty($yg)) {


    		if($yg['id'] > $member['c_level']) {
    			//查看是否跳级

    			// for ($i=$member['c_level']; $i < $yg['id']; $i++) { 

    			// 	// echo '<pre>';
    			// 	//     print_r($i);
    			// 	// echo '</pre>';

    			// 	$levels = pdo_fetch('select id from ' . tablename('wx_shop_game_log'.substr($uid,-1)) . ' where uniacid=:uniacid and uid=:uid and type=:type and  level=:level',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],'type'=>10,':level'=>$i));

    			// 	if(empty($levels)) {


    			// 		$money = $level[$i-1]['jl'];
    					

    			// 		// echo '<pre>';
    			// 		//     print_r($money);
    			// 		// echo '</pre>';
    			// 		$this->setMoney($member['id'],'credit_red',$money,'广告等级红包','('.$level[$i-1]['levelname'].')奖励'.$money,$i);

    			// 	}


    			// }
    			
    			//升级
    			if($yg['id'] > $member['c_level'] ) {
    				// echo '修改的等级';
    				pdo_update('wx_shop_member',array('c_level'=>$yg['id']),array('id'=>$member['id']));
    
    			}


    		} else {
    			//
    			// echo '小于';
    		}

    	}
    	// exit;



    }


    //获取超过人数
    public function getBlevel_cg($uid) {
    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	$member = pdo_fetch('select lj_c from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));

    	if(empty($member)) {
            // echo s;
    		return;
    	}

    	//总平台人数
		$zong_total = pdo_fetchcolumn('select count(*) from '.tablename('wx_shop_member').'where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		// echo '<pre>';
		//     print_r($zong_total);
		// echo '</pre>';

		//查询小于等于我累计彩蛋币的人数

		$x_total = pdo_fetchcolumn('select count(*) from '.tablename('wx_shop_member').'where uniacid=:uniacid and lj_c<=:lj_c',array(':uniacid'=>$_W['uniacid'],':lj_c'=>$member['lj_c']));
		// echo '<pre>';
		//     print_r($x_total);
		// echo '</pre>';

		$res = ($x_total / $zong_total);

		return $res;


    }


    public function getMoney($uid){
		
    	global $_W;

		return pdo_fetch('SELECT credit_b,credit_red,dzp,qd_num,yqq,jinbi FROM ' . tablename('wx_shop_member') . ' WHERE `id` = :uid', array(':uid' => $uid));

    }



    //今日预计收益
    public function jgetSy($uid)
    {
    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	$member = m('member')->getMember($uid);

    	if(empty($member)) {
            // echo s;
    		return;
    	}

    	//今天0.00
		$setime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		

		//今天23.59
		$zsetime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		// $zsetime = mktime(12,0,0,date("m"),date("d")+1,date("Y"));
		

		// //昨天十二点
		// $zsetime = mktime(12,0,0,date("m"),date("d")-1,date("Y"));


		//总分销
    	$jin_fx_money = 0;

    	//总分红
    	$jin_fh_money = 0;


    	//徒弟分销
    	$tu_fx_money = 0;

    	//徒孙分销
    	$sun_fx_money = 0;



    	//徒弟分红
    	$tu_fh_money = 0;


    	//徒孙分红
    	$sun_fh_money = 0;

    	$bili = 0;

		for ($i=0; $i < 10; $i++) { 
				
			// echo '<pre>';
			//     print_r($i);
			// echo '</pre>';

			$reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type,bili,bs from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and uid=:uid and time>=:setime and time<=:zsetime and type!=5',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':setime'=>$setime,':zsetime'=>$zsetime));

			// echo '<pre>';
			//     print_r($reslog);
			// echo '</pre>';

			if(!empty($reslog)) {
				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
				foreach ($reslog as $key => $value) {
					$jin_fx_money += $value['fx_money'];
					$jin_fh_money += $value['fh_money'];
					$bili = $value['bs'];
					// echo '<pre>';
					//     print_r($value['bili']);
					// echo '</pre>';
					if($value['type'] == 1) {

						$tu_fx_money += $value['fx_money'];

					} else if($value['type'] == 2) {
						$sun_fx_money += $value['fx_money'];

					} else if($value['type'] == 3) {
						$tu_fh_money += $value['fh_money'];

					} else if($value['type'] == 4) {
						$sun_fh_money += $value['fh_money'];

					}

				}

			}
		}

		$arrs = array(
			'jin_fx_money'=>$jin_fx_money,
			'jin_fh_money'=>$jin_fh_money,
			'jin_bili'=>$bili,
			'tu_fx_money'=>$tu_fx_money,
			'sun_fx_money'=>$sun_fx_money,
			'tu_fh_money'=>$tu_fh_money,
			'sun_fh_money'=>$sun_fh_money,
		);

		return $arrs;
		// echo '<pre>';
		//     print_r($tu_fx_money);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($sun_fx_money);
		// echo '</pre>';

    }


    //昨日预计收益
    public function zgetSy($uid)
    {
    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	$member = m('member')->getMember($uid);

    	if(empty($member)) {
            // echo s;
    		return;
    	}

    	//昨天0.00
		$setime = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
		

		//昨天23.59
		$zsetime = mktime(23,59,59,date("m"),date("d")-1,date("Y"));
		// $zsetime = mktime(12,0,0,date("m"),date("d")+1,date("Y"));
		

		// //昨天十二点
		// $zsetime = mktime(12,0,0,date("m"),date("d")-1,date("Y"));


		//总分销
    	$zuo_fx_money = 0;

    	//总分红
    	$zuo_fh_money = 0;


    	//徒弟分销
    	$tu_fx_money = 0;

    	//徒孙分销
    	$sun_fx_money = 0;



    	//徒弟分红
    	$tu_fh_money = 0;


    	//徒孙分红
    	$sun_fh_money = 0;

    	$bili = 0;


		for ($i=0; $i < 10; $i++) { 
				
			// echo '<pre>';
			//     print_r($i);
			// echo '</pre>';

			$reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type,bili,bs from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status and uid=:uid and time>=:setime and time<=:zsetime and type!=5',array(':uniacid'=>$_W['uniacid'],':status'=>1,':uid'=>$uid,':setime'=>$setime,':zsetime'=>$zsetime));

			// echo '<pre>';
			//     print_r($reslog);
			// echo '</pre>';

			if(!empty($reslog)) {
				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
				foreach ($reslog as $key => $value) {
					$zuo_fx_money += $value['fx_money'];
					$zuo_fh_money += $value['fh_money'];
					$bili = $value['bs'];

					if($value['type'] == 1) {

						$tu_fx_money += $value['fx_money'];

					} else if($value['type'] == 2) {
						$sun_fx_money += $value['fx_money'];

					} else if($value['type'] == 3) {
						$tu_fh_money += $value['fh_money'];

					} else if($value['type'] == 4) {
						$sun_fh_money += $value['fh_money'];

					}

				}

			}
		}

		$arrs = array(
			'zuo_fx_money'=>$zuo_fx_money,
			'zuo_fh_money'=>$zuo_fh_money,
			'zuo_bili'=>$bili,
			'tu_fx_money'=>$tu_fx_money,
			'sun_fx_money'=>$sun_fx_money,
			'tu_fh_money'=>$tu_fh_money,
			'sun_fh_money'=>$sun_fh_money,
		);

		return $arrs;
		// echo '<pre>';
		//     print_r($tu_fx_money);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($sun_fx_money);
		// echo '</pre>';

    }


    //累计总收益
    public function ljgetSy($uid)
    {
    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	$member = m('member')->getMember($uid);

    	if(empty($member)) {
            // echo s;
    		return;
    	}

    	//今天0.00
		// $setime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		

		//今天23.59
		// $zsetime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		// $zsetime = mktime(12,0,0,date("m"),date("d")+1,date("Y"));
		

		// //昨天十二点
		// $zsetime = mktime(12,0,0,date("m"),date("d")-1,date("Y"));


		//总分销
    	$fx_money = 0;

    	//总分红
    	$fh_money = 0;


		for ($i=0; $i < 10; $i++) { 
				
			// echo '<pre>';
			//     print_r($i);
			// echo '</pre>';

			$reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and uid=:uid ',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));
			// $reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status and uid=:uid ',array(':uniacid'=>$_W['uniacid'],':status'=>1,':uid'=>$uid));

			// echo '<pre>';
			//     print_r($reslog);
			// echo '</pre>';

			if(!empty($reslog)) {
				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
				foreach ($reslog as $key => $value) {
					$fx_money += $value['fx_money'];
					$fh_money += $value['fh_money'];

				}

			}
		}

		$arrs = array(
			'fx_money'=>$fx_money,
			'fh_money'=>$fh_money,
		);

		return $arrs;
		// echo '<pre>';
		//     print_r($tu_fx_money);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($sun_fx_money);
		// echo '</pre>';

    }


    // public function


    //发送短信

    public  function sendcode($mobile,$isres="true"){

		global $_W,$_GPC;

		$redis=$this->getRedis();

		$codetime=$redis->get($mobile."codetime");

		if($codetime+60>time()){

			show_json_w(-1,null,"1分钟之内发送一次短信");

		}

		if(empty($mobile)){

			show_json_w(-1,null, '请填入手机号');

		}



        if($isres=="true"){

			$info = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where mobile=:mobile and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'],':mobile' => $mobile));

			if(!empty($info))

			{

				show_json_w(-1,null, '该手机号已被注册！不能获取验证码。');

			} 

		}

		$code = rand(10000, 99999);



		$redis->setex($mobile.'codetime', 300, time());

		$redis->setex($mobile."code", 300, $code);



		// $sms = pdo_fetch("select sets from hs_sz_yi_sysset where uniacid = {$_W['uniacid']}");

		// $sms_1 = unserialize($sms['sets']);

        $sms = pdo_fetch("select * from " . tablename('wx_shop_sms_set_17int') . " where uniacid = {$_W['uniacid']}");


		$message = '【'.$_W['shopset']['shop']['name'].'】您的验证码是:'.$code.'验证码5分钟后过期，请您及时验证！';

		$post_data = array(

		   'account' => $sms['account'],

		   'password' => md5($sms['password']),

		   'mobile' => $mobile,

		   'content' => $message,

		   'requestId' => '1111',

		   'extno' => ''

		);

		file_put_contents(dirname(__FILE__).'/post_data',json_encode( $post_data)); 
		file_put_contents(dirname(__FILE__).'/sms',json_encode( $sms)); 

		$url = 'http://www.17int.cn/xxsmsweb/smsapi/send.json';

		$post_data = json_encode($post_data,true);

		$list = $this->curl_request($url,$post_data);

		file_put_contents(dirname(__FILE__).'/list',json_encode( $list)); 

		if($list['errorCode'] == 'ALLSuccess'){

			show_json_w(1,null,"发送成功");

		}else{

			show_json_w(-1,null,"发送失败");

		}

    }


    //验证短信

	public  function checkmobile($mobile,$code)

	{

		$redis=$this->getRedis();

		$codetime=$redis->get($mobile.'codetime');

		$checkcode=$redis->get($mobile."code");

		if(($codetime + 60 * 5) < time()){

			 show_json_w(-1,null, '验证码已过期,请重新获取');

		}

		if($checkcode != $code){

			 show_json_w(-1,null, '验证码错误,请重新获取');

		}



	}

	//获取平台神鸟用户
	public function get_sn($is=false){

		global $_W,$_GPC;


		$sn_num = 0;

		//神鸟用户

		$res =  array();

		for ($i=0; $i < 10; $i++) { 
			
			$sn_numa = pdo_fetchall('select id,uid from ' . tablename('wx_shop_game'.$i) . ' where uniacid=:uniacid and goodsid=46 group by uid',array(':uniacid'=>$_W['uniacid']));

			// echo "<pre>";
			// 	print_r($sn_numa);
			// echo "</pre>";

			$sn_num += count($sn_numa);


			foreach ($sn_numa as $key => $value) {
				if($value['uid']) {

					$res[] = $value['uid'];

				}
			}


		}

		if($is) {

			// echo "<pre>";
			// 	print_r(serialize($res));
			// echo "</pre>";
			// exit;

			$arr = array('sn_member'=>serialize($res),'num'=>$sn_num);

			return $arr;

		} else {

			return $sn_num;


		}


	}

	public  function curl_request($url,$postStr = ""){



		$header = array(

			'Content-Type: application/json',

		);

		$curl = curl_init($url);



		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);



		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");



		curl_setopt($curl, CURLOPT_POSTFIELDS, $postStr);



		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);



		curl_setopt($curl, CURLOPT_FAILONERROR, false);



		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);



		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);



		$response = curl_exec($curl) or die("error：".curl_errno($curl));



		curl_close($curl);



		$result = (array)json_decode($response);



		return $result;

    }


    //校检身份证
    public function isCreditNo($vStr){
	    $vCity = array(
	      '11','12','13','14','15','21','22',
	      '23','31','32','33','34','35','36',
	      '37','41','42','43','44','45','46',
	      '50','51','52','53','54','61','62',
	      '63','64','65','71','81','82','91'
	    );
	    if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
	    if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
	    $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
	    $vLength = strlen($vStr);
	    if ($vLength == 18) {
	    	$vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
	    } else {
	    	$vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
	    }
	    if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
	    if ($vLength == 18) {
	    	$vSum = 0;
		    for ($i = 17 ; $i >= 0 ; $i--) {
		    	$vSubStr = substr($vStr, 17 - $i, 1);
		    	$vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
		    }
		    if($vSum % 11 != 1) return false;
	    }
	     return true;
    }

    //代理分红

    public function abonus($uid,$money,$k_id){

    	global $_W;

    	// echo '<pre>';
    	//     print_r(111);
    	// echo '</pre>';

    	// $member = m('member')->getMember($uid);

		$member = pdo_fetch('select id,province,city,area from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));


    	if(empty($member)) {
    		return;
    	}
    	// echo "<pre>";
    	// 	print_r($member);
    	// echo "</pre>";

    	//查询市区代理
    	$aagentcitys = iserializer(array($member['province'].$member['city']));
 		
 		// echo "<pre>";
 		// 	print_r($aagentcitys);
 		// echo "</pre>";

    	$shi = pdo_fetch('select id,dl_money from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id<>:uid and aagentcitys=:aagentcitys and aagenttype=:aagenttype',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':aagentcitys'=>$aagentcitys,':aagenttype'=>2));

    	$set = p('abonus')->getSet();
    	// echo "<pre>";
    	// 	print_r($shi);
    	// echo "</pre>";
    	// echo "<pre>";
    	// 	print_r($uid);
    	// echo "</pre>";
    	// echo "<pre>";
    	// 	print_r($set);
    	// echo "</pre>";
    	// exit;

    	if(!empty($shi)) {
    		$bili_2 = $set['bonus2']/100;
    		$city_money = round($bili_2 * $money,4);
    		pdo_update('wx_shop_member',array('dl_money'=>$shi['dl_money']+$city_money),array('id'=>$shi['id']));

    		pdo_insert('wx_shop_game_abonus',array(
    			'uniacid'=>$_W['uniacid'],
    			'uid'=>$shi['id'],
    			'fid'=>$uid,
    			'type'=>2,
    			'money'=>$city_money,
    			'bili'=>$bili_2,
    			'time'=>time(),
    			'k_id'=>$k_id,
    			'status'=>1
    		));
    	}

    	// echo "<pre>";
    	// 	print_r($shi);
    	// echo "</pre>";
    	// echo "<pre>";
    	// 	print_r($aagentcitys);
    	// echo "</pre>";

    	$aagentareas = iserializer(array($member['province'].$member['city'].$member['area']));

    	//查询区代理

    	$qu = pdo_fetch('select id,dl_money from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id<>:uid and aagentareas=:aagentareas and aagenttype=:aagenttype',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':aagentareas'=>$aagentareas,':aagenttype'=>3));

    	if(!empty($qu)) {
    		$bili_3 = $set['bonus3']/100;
    		$area_money = round($bili_3 * $money,4);
    		pdo_update('wx_shop_member',array('dl_money'=>$qu['dl_money']+$area_money),array('id'=>$qu['id']));

    		pdo_insert('wx_shop_game_abonus',array(
    			'uniacid'=>$_W['uniacid'],
    			'uid'=>$qu['id'],
    			'fid'=>$uid,
    			'type'=>3,
    			'money'=>$area_money,
    			'bili'=>$bili_3,
    			'time'=>time(),
    			'k_id'=>$k_id,
    			'status'=>1
    		));
    	}






    }



    //获取代理下级
    public function getAbonus($uid,$is=0){

        global $_W;
        
        $member = pdo_fetch('SELECT id,aagenttype,aagentcitys,aagentareas,address FROM ' . tablename('wx_shop_member') . ' WHERE `id` = :uid', array(':uid' => $uid));

        
	    $str = $member['id'].',';

	    $member_ids  = array();




        if($member['aagenttype'] == 2) {

	        //地区
	        $member['aagentcitys_s'] = unserialize($member['aagentcitys']);
	        // echo "<pre>";
	        // 	print_r($member);
	        // echo "</pre>";
	        //获取所有会员区域会员
	        $member_list = pdo_fetchall('SELECT id,aagenttype,aagentcitys,aagentareas,address FROM ' . tablename('wx_shop_member') . ' WHERE `aagenttype` = :aagenttype', array(':aagenttype' => 3));



	        $str_s = '';

	        foreach ($member_list as $key => $value) {
		        	
		        $address = unserialize($value['address']);


		        if (in_array($address['province'] . $address['city'], $member['aagentcitys_s'])) 
		        {
		        		
		        	$member_ids[] = $value['id'];

		        	$str .= $value['id'] . ',';
		        	
		        	$str_s .= $value['id'] . ',';
		        }


	        }



	    }
	    
	    $str = rtrim($str,',');
	    
	    $str_s = rtrim($str_s,',');

	    return array('member_ids'=>$member_ids,'member_str'=>$str,'member_w'=>$str_s);



        // echo "<pre>";
        // 	print_r($member_ids);
        // echo "</pre>";

        // echo "<pre>";
        // 	print_r($str);
        // echo "</pre>";

        // echo "<pre>";
        // 	print_r($member_list);
        // echo "</pre>";


    }

    //获取下级金额
    public function getAbonus_money($uid){
    	global $_W;
    	
    	$member = pdo_fetch('SELECT id,aagenttype,aagentcitys,aagentareas,address FROM ' . tablename('wx_shop_member') . ' WHERE `id` = :uid', array(':uid' => $uid));

    	$abonus = $this->getAbonus($uid,1);


    	// echo "<pre>";
    	// 	print_r($abonus);
    	// echo "</pre>";
    	// exit;


    	if(!empty($abonus['member_w']) && $member['aagenttype'] ==2) {

    		$qd = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_abonus') . ' where uniacid=:uniacid and uid in('.$abonus['member_w'].') and type=3',array(':uniacid'=>$_W['uniacid']));

    		// echo "<pre>";
    		// 	print_r($qd);
    		// echo "</pre>";

    	} else if($member['aagenttype'] ==3) {


    		$qd = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_abonus') . ' where uniacid=:uniacid and uid in('.$member['id'].') and type=3',array(':uniacid'=>$_W['uniacid']));

    		// echo "<pre>";
    		// 	print_r($qd);
    		// echo "</pre>";

    	}


    	$sd = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_abonus') . ' where uniacid=:uniacid and uid=:uid and type=2',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));

    	$sd = $sd ? $sd: 0;
    	
    	$qd = $qd ? $qd: 0;

    	return array('ok'=>$sd+$qd,'ok2'=>$sd,'ok3'=>$qd);

    	// echo "<pre>";
    	// 	print_r($sd);
    	// echo "</pre>";




    }


    public function createMyQrcode($incode,$xz_lj){

        global $_W, $_GPC;

        $dephp_2 = IA_ROOT . '/addons/sz_yi/data/qrcode/' . $_W['uniacid'] . '/';

        if (!is_dir($dephp_2)){

            load() -> func('file');

            mkdirs($dephp_2);

        }



        $dephp_3 = $xz_lj.'&yqm=' . $incode;



        $dephp_4 = 'shop_qrcode_'.$incode.'.png';

        $dephp_5 = $dephp_2 . $dephp_4;

        if (!is_file($dephp_5)){

            require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';

            QRcode :: png($dephp_3, $dephp_5, QR_ECLEVEL_L, 4);

        }

        return $_W['siteroot'] . 'addons/sz_yi/data/qrcode/' . $_W['uniacid'] . '/' . $dephp_4;

    }



    public function  getSn_id()
    {

        $sn_id=mt_rand(10000000,99999999);

        $isfind=pdo_fetch("select sn_id from ".tablename("wx_shop_member")." where sn_id=:sn_id",array(":sn_id"=>$sn_id));

        if($isfind){

           $this->getSn_id();

        }

        return $sn_id;

    }


}
?>
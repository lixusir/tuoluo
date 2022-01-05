<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}


class Ontimes_WxShopPage extends MobilePage
{



	public function main() 
	{
		global $_GPC;

		// echo '<pre>';
		//     print_r(111);
		// echo '</pre>';
		$post_data = file_get_contents("php://input"); 
		
		// file_put_contents(dirname(__FILE__).'/post',$post_data); 

		if(empty($post_data)) {
		
			// file_put_contents(dirname(__FILE__).'/post2',time()); 


		} else {

			$members = json_decode($post_data,true);
		
			// file_put_contents(dirname(__FILE__).'/123',$members['curl_member_list']); 

			// if(is_array($members)){
				file_put_contents(dirname(__FILE__).'/arr',$members); 

			// }

			$mema = explode(",", $members['curl_member_list']);

			if(!empty($mema)) {
				m('game')->ontimes_2($mema);
			}
			
			// file_put_contents(dirname(__FILE__).'/_GPC122',$mema[0]); 


		}


	}


	public function logouttime(){

		global $_GPC;


		$uid = intval($_GPC['uid']);

		if(!empty($uid)) {

			m('game')->logout($uid);

		}

		show_json(1,$_GPC['uid']);



	}


	public function logintime(){

		global $_GPC;


		$uid = intval($_GPC['uid']);

		if(!empty($uid)) {

			$res = m('game')->login($uid);
			
			show_json(1,$res);

		}




	}


	public function aabb() 
	{
		global $_GPC,$_W;

		// echo '<pre>';
		//     print_r($_GPC);
		// echo '</pre>';

		// $mema = array('3990','3991','3995','3996','3997','3998','3999');
		// echo '<pre>';
		//     print_r($mema);
		// echo '</pre>';
		// if(!empty($mema)) {
		// 	m('game')->ontimes_2($mema);
		// }
		


		// echo '<pre>';
		//     print_r($_W['siteroot']);
		// echo '</pre>';

		$redis = m('game')->getRedis();


		// $redis->rPush('mess',serialize(1232));


		$mess = $redis->lrange('messinfo12019',0,2);
		

		$aa = $redis->lrange('messgame',0,100);

		echo '<pre>';
		    print_r($aa);
		echo '</pre>';
		foreach ($aa as $key => $value) {
			
			$mss = unserialize($value);

			// $sucess=$redis->lrem('mess',$value,0);//消息发送成功删除



			echo '<pre>';
			    print_r($mss);
			echo '</pre>';
		}
		// echo '<pre>';
		//     print_r($mess);
		// echo '</pre>';


		// $prize_arr = array(   
  //         '0' => array('id'=>1,'prize'=>'平板电脑','gl'=>0.1),   
  //         '1' => array('id'=>2,'prize'=>'数码相机','gl'=>1),   
  //         '2' => array('id'=>3,'prize'=>'音箱设备','gl'=>2),   
  //         '3' => array('id'=>4,'prize'=>'4G优盘','gl'=>30),   
  //         '4' => array('id'=>5,'prize'=>'10Q币','gl'=>40),   
  //         '5' => array('id'=>6,'prize'=>'空奖','gl'=>60),   
  //       );


		// $res = m('game')->getJiang($prize_arr);

		// echo '<pre>';
		//     print_r($res);
		// echo '</pre>';


		// m('game')->setMoney(3995,'dzp',-1,'大转盘抽奖','大转盘抽奖消费');



	}


	public function video()
	{

		global $_W;

		//昨天0点
		// $setime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$setime = mktime(0,0,0,date("m"),date("d")-1,date("Y"));

		

		//昨天23点
		// $zsetime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		$zsetime = mktime(23,59,59,date("m"),date("d")-1,date("Y"));


		// echo '<pre>';
		//     print_r(date("Y-m-d H:i:s",$setime));
		// echo '</pre>';

		// echo '<pre>';
		//     print_r(date("Y-m-d H:i:s",$zsetime));
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($setime);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($zsetime);
		// echo '</pre>';
		 file_put_contents(dirname(__FILE__).'/aaa',json_encode(123)); 

		// if($setime == time()) {

			$game = m('game');


			$fx_member = array();
			$fh_member = array();
			$sn_member = array();

			for ($i=0; $i < 10; $i++) { 
					
				// echo '<pre>';
				//     print_r($i);
				// echo '</pre>';

				$reslog = pdo_fetchall('select uid,sum(fx_money) as fx_moneys,sum(fh_money) as fh_moneys,sum(sn_money) as sn_moneys  from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status and time>=:stime and time<=:etime group by uid',array(':uniacid'=>$_W['uniacid'],':status'=>0,':stime'=>$setime,':etime'=>$zsetime));

				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
							
				if(!empty($reslog)) {

					// $jin = pdo_fetch('select id from ' . tablename('wx_shop_game_log'.$i) . ' where uniacid=:uniacid and type in(11,12) and time>=:stime and time=<=:etime ',array(':uniacid'=>$_W['uniacid'],':status'=>0,':stime'=>$zsetime,':etime'=>$setime));


					// if(!empty($jin)) {
						foreach ($reslog as $key => $value) {

							//分销
							// if(empty($fx_member[$value['uid']])){

							// 	$fx_member[$value['uid']] = array(
							// 		'fx_money'=>$value['fx_moneys']
							// 	);

							// } else {

							// 	$fx_member[$value['uid']] = array(
							// 		'fx_money'=>$value['fx_moneys']+$fx_member[$value['uid']]['fx_money']
							// 	);

							// }

							//分红
							if(empty($fh_member[$value['uid']])){

								$fh_member[$value['uid']] = array(
									'fh_money'=>$value['fh_moneys']
								);

							} else {

								$fh_member[$value['uid']] = array(
									'fh_money'=>$value['fh_moneys']+$fh_member[$value['uid']]['fh_money']
								);

							}

							//神鸟
							if(empty($sn_member[$value['uid']])){

								$sn_member[$value['uid']] = array(
									'sn_money'=>$value['sn_moneys']
								);

							} else {

								$sn_member[$value['uid']] = array(
									'sn_money'=>$value['sn_moneys']+$sn_member[$value['uid']]['sn_money']
								);

							}


							// if($value['fx_money'] > 0) {
							// 	$game->setMoney($value['uid'],'credit_red',$value['fx_money'],'视频分销红包','视频分销红包');
							// }
							// if($value['fh_money'] > 0) {
							// 	$game->setMoney($value['uid'],'credit_red',$value['fh_money'],'视频分红红包','视频分红红包');
							// }
						}
						//需要打开
						pdo_query("UPDATE ".tablename('wx_shop_game_redlog'.$i)." SET status = :status WHERE time>=:stime AND time<=:etime ", array(':status' => 1, ':stime'=>$setime,':etime'=>$zsetime));
					// }

				}
			}

			//统一发放奖励
			// if(!empty($fx_member)) {
			// 	foreach ($fx_member as $key => $value) {
			// 		// echo "<pre>";
			// 		// 	print_r($key);
			// 		// echo "</pre>";

			// 		if($value['fx_money'] > 0) {
			// 			$game->setMoney($key,'credit_red',$value['fx_money'],'视频分销红包','视频分销红包');
			// 		}
			// 		// echo "<pre>";
			// 		// 	print_r($value['fx_money']);
			// 		// echo "</pre>";
			// 	}
			// }

			// echo "<pre>";
			// 	print_r($fh_member);
			// echo "</pre>";

			// echo "<pre>";
			// 	print_r($fh_member);
			// echo "</pre>";
			// exit;
			// exit;

			if(!empty($sn_member)) {
				foreach ($fh_member as $key => $value) {
					// echo "<pre>";
					// 	print_r($key);
					// echo "</pre>";

					if($value['fh_money'] > 0) {
						$game->setMoney($key,'credit_red',$value['fh_money'],'视频分红红包','视频分红红包');
					}
					// echo "<pre>";
					// 	print_r($value['fh_money']);
					// echo "</pre>";
				}
			}


			if(!empty($sn_member)) {
				foreach ($sn_member as $key => $value) {
					// echo "<pre>";
					// 	print_r($key);
					// echo "</pre>";

					if($value['sn_money'] > 0) {
						$game->setMoney($key,'credit_red',$value['sn_money'],'视频神鸟红包','视频神鸟红包');
					}
					// echo "<pre>";
					// 	print_r($value['sn_money']);
					// echo "</pre>";
				}
			}

			// $video = pdo_fetchall('select * from' . tablename('wx_shop_game_video') . '  where uniacid=:uniacid  and time>=:stime and time<=:etime',array(':uniacid'=>$_W['uniacid'],':stime'=>$setime,':etime'=>$zsetime));


			// $zrtime = mktime(23,59,59,date("m"),date("d")-2,date("Y"));

			// //前天神鸟数量
			// $sn_goods = pdo_fetch('select * from ' . tablename('wx_shop_game_zrsn') . ' where uniacid=:uniacid and times=:times',array(':uniacid'=>$_W['uniacid'],':times'=>$zrtime));

			// $sn_goods['num'] = empty($sn_goods) ? 0: $sn_goods['num'];

			// // echo "<pre>";
			// // 	print_r($sn_goods);
			// // echo "</pre>";


			// $sn_members = unserialize($sn_goods['sn_member']);

			// // echo "<pre>";
			// // 	print_r($sn_members);
			// // echo "</pre>";

			// $set = pdo_fetch('select gg_money,fx_one,fx_two,fh_one,fh_two,sn_bili,zr_sn from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));


			// // echo "<pre>";
			// // 	print_r($video);
			// // echo "</pre>";
			// // exit;
			// foreach ($video as $key => $val) {

			// 	if(!empty($sn_members)){

			// 		$member = array();
					
			// 		$one = array();

			// 		$two = array();

			// 		foreach ($sn_members as $key => $value) {

			// 			$member=pdo_fetch("select id,agentid from ".tablename("wx_shop_member")." where id=:id limit 1",array(":id"=>$value));

			// 			echo "<pre>";
			// 				print_r($member);
			// 			echo "</pre>";
			// 			// exit;
			// 			$one = pdo_fetch('select m.id,m.agentid,gl.bili from ' . tablename('wx_shop_member') . ' m left join ' . tablename('wx_shop_game_level') . ' gl on gl.id=m.gg_level where m.uniacid=:uniacid and m.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$member['agentid']));

			// 			echo "<pre>";
			// 				print_r($one);
			// 			echo "</pre>";
			// 			// exit;

			// 			if(!empty($member)) {
			// 				//可以分红

			// 				//自己的神鸟奖励
			// 				// $fh_one = $set['gg_money'] * ($set['fh_one']/100);
			// 				// $fh_one = round($fh_one,2);

			// 				//获取昨日全网神鸟总数
			// 				$sn_num = $sn_goods['num'];

			// 				// echo "<pre>";
			// 				// 	print_r($sn_num);
			// 				// echo "</pre>";
			// 				// exit;

			// 				$sn_money = $set['gg_money'] * ($set['sn_bili']/100) / $sn_num;
			// 				// echo "<pre>";
			// 				// 	print_r($sn_money);
			// 				// echo "</pre>";
			// 				// exit;
			// 				//获取得到的钱
			// 				$sn_money = round($sn_money,2);

			// 				if($sn_money <= 0) {
			// 					continue;
			// 				}

			// 				$type = $game->type_money('神鸟奖励');

			// 				$arr_one_sn = array(
			// 					'uniacid'=>$_W['uniacid'],
			// 					'uid'=>$member['id'],
			// 					'f_uid'=>$val['uid'],
			// 					'time'=>time(),
			// 					'sn_money'=>$sn_money,
			// 					'status'=>0,
			// 					'type'=>$type,
			// 					'video_id'=>$val['id'],
			// 					'bili'=>$set['sn_bili'],
			// 					'bs'=>$sn_num,
			// 					'video_uid'=>$val['uid']
			// 				);


			// 				pdo_insert('wx_shop_game_redlog'.substr($val['id'],-1),$arr_one_sn);
			// 				//


			// 				if(!empty($one)) {

			// 					if($member['agentid'] == 0) {
			// 						continue;
			// 					}

			// 					if($one['agentid'] == 0) {
			// 						continue;
			// 					}

			// 					$fh_one = $sn_money * ($set['fh_one']/100);
			// 					$fh_one = round($fh_one,2);


			// 					$type = $game->type_money('徒弟分红');

			// 					$arr_one_fh = array(
			// 						'uniacid'=>$_W['uniacid'],
			// 						'uid'=>$one['id'],
			// 						'f_uid'=>$member['id'],
			// 						'time'=>time(),
			// 						'fh_money'=>$fh_one,
			// 						'status'=>0,
			// 						'type'=>$type,
			// 						'video_id'=>$val['id'],
			// 						'bili'=>$set['fh_one'],
			// 						'bs'=>$one['bili'],
			// 						'video_uid'=>$val['uid']
			// 					);
			// 					pdo_insert('wx_shop_game_redlog'.substr($val['id'],-1),$arr_one_fh);


			// 					// echo '<pre>';
			// 					//     print_r($arr_one_fh);
			// 					// echo '</pre>';
			// 					// $san = pdo_fetch('select id,agentid from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$two['agentid']));

			// 					$two = pdo_fetch('select m.id,m.agentid,gl.bili from ' . tablename('wx_shop_member') . ' m left join ' . tablename('wx_shop_game_level') . ' gl on gl.id=m.gg_level where m.uniacid=:uniacid and m.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$one['agentid']));

			// 				}


			// 				if(!empty($two)) {

			// 					if($one['agentid'] == 0) {
			// 						continue;
			// 					}

			// 					if($two['agentid'] == 0) {
			// 						continue;
			// 					}

			// 					$fh_two = $sn_money * ($set['fh_two']/100);
			// 					$fh_two = round($fh_two,2);

			// 					$type = $game->type_money('徒孙分红');

			// 					$arr_two_fh = array(
			// 						'uniacid'=>$_W['uniacid'],
			// 						'uid'=>$two['id'],
			// 						'f_uid'=>$one['id'],
			// 						'time'=>time(),
			// 						'fh_money'=>$fh_two,
			// 						'status'=>0,
			// 						'type'=>$type,
			// 						'video_id'=>$val['id'],
			// 						'bili'=>$set['fh_two'],
			// 						'bs'=>$two['bili'],
			// 						'video_uid'=>$val['uid']
			// 					);
			// 					pdo_insert('wx_shop_game_redlog'.substr($val['id'],-1),$arr_two_fh);

			// 				}


			// 			}
			// 		}


			// 	}



			// }



		// }

	}

	public function times(){
		global $_W;

		// echo '<pre>';
		//     print_r(date("H"));
		// echo '</pre>';



		$shi = date("H");

		$redis = m('game')->getRedis();
			
		// $redis->delete('phb_list');
		// exit;

		// $mess = $redis->lrange('phb_list',0,1);
		$mess = $redis->get('phb_list');

		$mes = unserialize($mess);

		// echo '<pre>';
		//     print_r($mes);
		// echo '</pre>';
		// exit;
		if($mes['h'] != $shi) {

			$member = pdo_fetchall('select id,nickname,avatar,game_level,jinbi,token from ' . tablename('wx_shop_member') . ' order by jinbi desc limit 9999',array(':uniacid'=>$_W['uniacid']));

				
			foreach ($member as $key => $value) {
				
				if($value['game_level'] == 38) {
					
					$zg_level = pdo_fetchcolumn('select goodsid from ' . tablename('wx_shop_game'.substr($value['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and status=1 and  goodslevel=38 order by goodsid desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$value['id']));

					// echo "<pre>";
					// 	print_r($zg_level);
					// echo "</pre>";
					// exit;
					if(empty($zg_level)) {

						$zg_level = 37;


					}


					$member[$key]['goodsname'] = pdo_fetchcolumn('select g.goodsname from ' . tablename('wx_shop_game_goods') . ' g left join ' . tablename('wx_shop_game'.substr($value['id'], -1)) . ' gg on gg.goodsid=g.id where g.uniacid=:uniacid and g.id=:goodsid order by g.id desc',array(':uniacid'=>$_W['uniacid'],':goodsid'=>$zg_level));

				} else {

					$member[$key]['goodsname'] = pdo_fetchcolumn('select goodsname from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level=:level',array(':uniacid'=>$_W['uniacid'],':level'=>$value['game_level']));



				}
			}

			// echo "<pre>";
			// 	print_r($member);
			// echo "</pre>";
			// exit;

			$member['h'] = $shi;
			// echo '<pre>';
			//     print_r($shi);
			// echo '</pre>';

			$redis->delete('phb_list');

			$redis->set('phb_list',serialize($member));

		} else {

			echo '不需要';

		}


	}

	public function yw(){



		
		//视频表
		pdo_tableexists('wx_shop_game_video') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_video'));
		
		pdo_tableexists('wx_shop_game_redlog0') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog0'));
		pdo_tableexists('wx_shop_game_redlog1') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog1'));
		pdo_tableexists('wx_shop_game_redlog2') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog2'));
		pdo_tableexists('wx_shop_game_redlog3') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog3'));
		pdo_tableexists('wx_shop_game_redlog4') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog4'));
		pdo_tableexists('wx_shop_game_redlog5') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog5'));
		pdo_tableexists('wx_shop_game_redlog6') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog6'));
		pdo_tableexists('wx_shop_game_redlog7') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog7'));
		pdo_tableexists('wx_shop_game_redlog8') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog8'));
		pdo_tableexists('wx_shop_game_redlog9') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog9'));



		// 游戏表
		pdo_tableexists('wx_shop_game0') && pdo_query('TRUNCATE ' . tablename('wx_shop_game0'));
		pdo_tableexists('wx_shop_game1') && pdo_query('TRUNCATE ' . tablename('wx_shop_game1'));
		pdo_tableexists('wx_shop_game2') && pdo_query('TRUNCATE ' . tablename('wx_shop_game2'));
		pdo_tableexists('wx_shop_game3') && pdo_query('TRUNCATE ' . tablename('wx_shop_game3'));
		pdo_tableexists('wx_shop_game4') && pdo_query('TRUNCATE ' . tablename('wx_shop_game4'));
		pdo_tableexists('wx_shop_game5') && pdo_query('TRUNCATE ' . tablename('wx_shop_game5'));
		pdo_tableexists('wx_shop_game6') && pdo_query('TRUNCATE ' . tablename('wx_shop_game6'));
		pdo_tableexists('wx_shop_game7') && pdo_query('TRUNCATE ' . tablename('wx_shop_game7'));
		pdo_tableexists('wx_shop_game8') && pdo_query('TRUNCATE ' . tablename('wx_shop_game8'));
		pdo_tableexists('wx_shop_game9') && pdo_query('TRUNCATE ' . tablename('wx_shop_game9'));


		//分红
		pdo_tableexists('wx_shop_game_abonu') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_abonu'));


		//答题
		pdo_tableexists('wx_shop_game_dtlist') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_dtlist'));


		//价格增长表
		pdo_tableexists('wx_shop_game_goods_log0') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log0'));
		pdo_tableexists('wx_shop_game_goods_log1') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log1'));
		pdo_tableexists('wx_shop_game_goods_log2') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log2'));
		pdo_tableexists('wx_shop_game_goods_log3') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log3'));
		pdo_tableexists('wx_shop_game_goods_log4') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log4'));
		pdo_tableexists('wx_shop_game_goods_log5') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log5'));
		pdo_tableexists('wx_shop_game_goods_log6') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log6'));
		pdo_tableexists('wx_shop_game_goods_log7') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log7'));
		pdo_tableexists('wx_shop_game_goods_log8') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log8'));
		pdo_tableexists('wx_shop_game_goods_log9') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_goods_log9'));


		//记录表
		pdo_tableexists('wx_shop_game_log0') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log0'));
		pdo_tableexists('wx_shop_game_log1') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log1'));
		pdo_tableexists('wx_shop_game_log2') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log2'));
		pdo_tableexists('wx_shop_game_log3') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log3'));
		pdo_tableexists('wx_shop_game_log4') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log4'));
		pdo_tableexists('wx_shop_game_log5') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log5'));
		pdo_tableexists('wx_shop_game_log6') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log6'));
		pdo_tableexists('wx_shop_game_log7') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log7'));
		pdo_tableexists('wx_shop_game_log8') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log8'));
		pdo_tableexists('wx_shop_game_log9') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_log9'));

		//红包记录表
		pdo_tableexists('wx_shop_game_member_log') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_member_log'));


		//今日敲蛋记录
		pdo_tableexists('wx_shop_game_qdlist_day') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_qdlist_day'));
		
		//分销分红自己奖励
		pdo_tableexists('wx_shop_game_redlog0') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog0'));
		pdo_tableexists('wx_shop_game_redlog1') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog1'));
		pdo_tableexists('wx_shop_game_redlog2') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog2'));
		pdo_tableexists('wx_shop_game_redlog3') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog3'));
		pdo_tableexists('wx_shop_game_redlog4') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog4'));
		pdo_tableexists('wx_shop_game_redlog5') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog5'));
		pdo_tableexists('wx_shop_game_redlog6') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog6'));
		pdo_tableexists('wx_shop_game_redlog7') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog7'));
		pdo_tableexists('wx_shop_game_redlog8') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog8'));
		pdo_tableexists('wx_shop_game_redlog9') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_redlog9'));
		
		//神鸟记录表
		pdo_tableexists('wx_shop_game_sn') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_sn'));
		

		//神鸟会员信息
		pdo_tableexists('wx_shop_game_zrsn') && pdo_query('TRUNCATE ' . tablename('wx_shop_game_zrsn'));



		// http://6339.chac.xyz/app/index.php?i=96&c=entry&m=wx_shop&do=mobile&r=game.ontimes.yw
		
		// for ($i=0; $i < 9; $i++) { 
				
			// $game = pdo_fetchall('select id,uid,income,goodslevel from ' . tablename('wx_shop_game5') . ' where  uid=:uid and status=1 limit 12',array(':uid'=>3995));

		// }
	}


	public function dtime(){

		global $_W;

		$arr = array();

		$arr['qd_num'] = 9;
		$arr['zx_time'] = 0;
		$arr['lj_zxtime'] = 0;
		$arr['k_video'] = 15;

		pdo_update('wx_shop_member',$arr);


		$sn_num = m('game')->get_sn(1);

		// exit;

		$zrtime = mktime(23,59,59,date("m"),date("d")-1,date("Y"));
			
		$res = pdo_fetch('select id from ' . tablename('wx_shop_game_zrsn') . ' where uniacid=:uniacid and times=:times',array(':uniacid'=>$_W['uniacid'],':times'=>$zrtime));

		if(empty($res)) {
			pdo_update('wx_shop_game_set',array('zr_sn'=>$sn_num['num']),array(':uniacid'=>$_W['uniacid']));
			
			pdo_insert('wx_shop_game_zrsn',array(
				'uniacid'=>$_W['uniacid'],
				'num'=>$sn_num['num'],
				'times'=>$zrtime,
				'sn_member'=>$sn_num['sn_member']
			));
		}
		
		// http://6339.chac.xyz/app/index.php?i=96&c=entry&m=wx_shop&do=mobile&r=game.ontimes.dtime

	}


	//定时器 跑加金币
	public function setwutime(){


        $redis = m('game')->getRedis();

		$mess = $redis->lrange('messjinbi2019',0,10000);
					
		file_put_contents('./mess.txt', $mess);

		if(!empty($mess)) {

			foreach ($mess as $key => $val) {
				
				//发送推送
				$mes = unserialize($val);

				// echo "<pre>";
				// 	print_r($mes);
				// echo "</pre>";
        			
				m('game')->setMoney($mes[0],$mes[1],$mes[2],$mes[3],$mes[4]);

				
				$redis->lrem('messjinbi2019',$val,0);//消息发送成功删除


			}

		}

	}
}
?>
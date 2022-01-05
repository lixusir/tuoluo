<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Game_WxShopPage extends MobilePage
{
	public function main() 
	{
		global $_W,$_GPC;


		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		$getsb = pdo_fetchall('select * from ' . tablename('wx_shop_game_goods') . 'where uniacid=:uniacid and level=38 ',array(':uniacid'=>$_W['uniacid']));


		// foreach ($getsb as $key => $value) {
		// 	pdo_insert('wx_shop_game'.substr($id,-1),
		// 		array(
		// 			'uniacid'=>$_W['uniacid'],
		// 			'uid'=>$id,
		// 			'level'=>$value['level'],
		// 			'goodsid'=>$value['id'],
		// 			'goodsType'=>$value['goodsType'],
		// 			'goodslevel'=>$value['level'],
		// 			'status'=>1,
		// 		)
		// 	);
		// }

		m('game')->getSp($id);


		$member = m('member')->getMember($id, true);
		
		if($member['game_level'] == 38) {
			$zg_level = pdo_fetchcolumn('select goodsid from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and status=1 and  goodslevel=38 order by goodsid desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$id));

			if(empty($zg_level)) {

				$zg_level = 37;


			}


			$member['game_level_goods'] = pdo_fetch('select g.goodsname,g.goodsType,g.income,g.lx_income from ' . tablename('wx_shop_game_goods') . ' g left join ' . tablename('wx_shop_game'.substr($member['id'], -1)) . ' gg on gg.goodsid=g.id where g.uniacid=:uniacid and g.id=:goodsid order by g.id desc',array(':uniacid'=>$_W['uniacid'],':goodsid'=>$zg_level));

		} else {

			$member['game_level_goods'] = pdo_fetch('select goodsname,goodsType,income,lx_income from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level=:level',array(':uniacid'=>$_W['uniacid'],':level'=>$member['game_level']));



		}




		// echo '<pre>';
		//     print_r($ars);
		// echo '</pre>';

		$set = pdo_fetch('select kefu,fx_lj,fx_sm from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));

		// echo '<pre>';
		//     print_r($set);
		// echo '</pre>';
		$set['kefu'] = tomedia($set['kefu']);
		// echo '<pre>';
		//     print_r($kefua);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($id);
		// echo '</pre>';
		if(empty($member)) {
			show_json(-1,'当前用户不存在!');
		}

		$game = pdo_fetchall('select g.id,g.uid,g.goodsid,g.goodslevel,g.income,g.goodsType,gg.receive from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' g left join '.tablename('wx_shop_game_goods').' gg on gg.id=g.goodsid where g.uniacid=:uniacid and g.uid=:uid and g.status=1 ',array(':uniacid'=>$_W['uniacid'],':uid'=>$id));
		



		$zx_miao = time() - $member['lj_zxtime'];



		//获取今天的广告
		$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		$money = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_video') . ' where uniacid=:uniacid and time>:stime and time<=:etime ',array(':uniacid'=>$_W['uniacid'],':stime'=>$stime,':etime'=>$etime));

		show_json(1,array('member'=>$member,'game'=>$game,'money'=>$money,'set'=>$set,'zx_miao'=>$zx_miao));

	}


	public function zx_lq(){

		global $_W,$_GPC;


		$token = $_GPC['token'];


		// file_put_contents(dirname(__FILE__).'/dasdsadsa',json_encode( $_GPC)); 


		// show_json(1,array('token'=>$token));

		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		// if($)
		$member = m('member')->getMember($id, true);


		$ytime = 60*60;

		if(empty($member)) {
			show_json(-1,'当前用户不存在!');
		}

		$zx_miao = time() - $member['lj_zxtime'];

		if($zx_miao < $ytime) {

			show_json(-1,'在线时间不足,无法领取');

		}

		$money = m('game')->getZg($id);

		// echo '<pre>';
		//     print_r($money);
		// echo '</pre>';
		// exit;
		$ids = m('game')->setMoney($member['id'],'jinbi',$money,'视频金币奖励','观看视频得金币奖励'.$money);

		pdo_update('wx_shop_member',array('lj_zxtime'=>time()),array('id'=>$member['id']));

		show_json(1,array('money'=>$money,'zx_miao'=>0,'id'=>$ids));


	}

	public function baox(){

		global $_W,$_GPC;


		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));


		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		// if($)
		$member = m('member')->getMember($id, true);

		$money = m('game')->getZg($id);

		show_json(1,array('money'=>$money));



	}


	public function phb_list() {

		global $_W,$_GPC;

		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}



		$member = pdo_fetch('select id,nickname,avatar,game_level,jinbi,token from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$id));





		if(empty($member)) {
			show_json(-1,'当前用户不存在!');
		}


		if($member['game_level'] == 38) {
			
			$zg_level = pdo_fetchcolumn('select goodsid from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and status=1 and  goodslevel=38 order by goodsid desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$id));

			if(empty($zg_level)) {

				$zg_level = 37;


			}


			$member['goodsname'] = pdo_fetchcolumn('select g.goodsname from ' . tablename('wx_shop_game_goods') . ' g left join ' . tablename('wx_shop_game'.substr($member['id'], -1)) . ' gg on gg.goodsid=g.id where g.uniacid=:uniacid and g.id=:goodsid order by g.id desc',array(':uniacid'=>$_W['uniacid'],':goodsid'=>$zg_level));

		} else {

			$member['goodsname'] = pdo_fetchcolumn('select goodsname from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level=:level',array(':uniacid'=>$_W['uniacid'],':level'=>$member['game_level']));



		}

		
		$redis = m('game')->getRedis();
		
		$mess = $redis->get('phb_list');

		$mes = unserialize($mess);

		unset($mes['h']);

		$mes = array_values($mes);
		// $mes = array($mes);

		// var_dump(expression)

		// var_dump($mes);

		$pm = 999;

		if(!empty($mes)) {
			foreach ($mes as $key => $value) {
				
				if($value['id'] == $member['id']){
					$pm = $key+1;
					break;
				}
			}
			
		} else {
			$mes = 0;
		}
		$member['pm'] = $pm;

		show_json(1,array('phb_list'=>$mes,'member'=>$member));


	}

	//购买
	public function buy()
	{
		
		global $_W,$_GPC;


		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		$member = m('member')->getMember($id, true);


		$weizhi = m('game')->getWeizhi($member['id']);

		if(empty($weizhi)) {
			show_json(4,'位置已满!');
		}

		//当前购买等级
		$game_level = intval($_GPC['game_level']);

		if($game_level > 38) {
			show_json(-1,'此等级不允许购买!');
		}

		$game_goods = pdo_fetch('select * from ' . tablename('wx_shop_game_goods') . '  where uniacid=:uniacid and level=:game_level',array(':uniacid'=>$_W['uniacid'],':game_level'=>$game_level));

		if(empty($game_goods)) {
			show_json(-1,'当前商品不存在,无法购买!');
		}


		$type = intval($_GPC['type']);


		if($member['game_level'] <= 5) {

			$buy_level = 1;

			if($game_level > $buy_level){
				show_json(-1,'当前等级不足,无法购买!');
			}


			if($type == 1) {
				show_json(-1,'当前等级不允许购买');
			}

		} else  {

			//使用正常金币购买
			if($type == 0) {
				
				//获取最高购买等级;
				$buy_level = $member['game_level'] - 4;
				
				if($game_level > $buy_level){
					show_json(-1,'当前等级不足,无法购买!');
				}

			} elseif($type == 1) {	

				if($member['game_level'] >=8 ) {
					
					$buy_level  = $member['game_level'] - 2;

					// echo '<pre>';
					//     print_r($buy_level);
					// echo '</pre>';

					if($game_level != $buy_level && $game_level != $buy_level-1) {

						show_json(-1,'此商品不支持彩蛋币购买!');


					}

					// echo '<pre>';
					//     print_r($buy_level);
					// echo '</pre>';
					// if($game_level > $buy_level){
						
					// 	show_json(-1,'当前等级不足,无法购买!');
					
					// }
				
				} else {
					
					show_json(-1,'当前等级不允许购买');
				
				}
			
			
			}




		}


		//获取购买价格


		$buy_logs = pdo_fetch('select * from ' . tablename('wx_shop_game_goods_log'.substr($member['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and level=:level and type=:type order by id desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':level'=>$game_level,':type'=>$type));

		// echo '<pre>';
		//     print_r($buy_logs);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($member['id']);
		// echo '</pre>';

		// echo '<pre>';
		//     print_r($game_level);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($type);
		// echo '</pre>';

		if($type == 0) {

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

			if($member['jinbi'] < $money) {
				
				show_json(-1,'金币不足!');
			
			}
			
		} elseif($type == 1) {
			//彩蛋币
			// show_json(-1,'当前商品不允许彩蛋币购买!');
			if(empty($buy_logs)) {

				$money = $game_goods['b_money'];

			} else {

				//获取上次增长彩蛋数量
				$b_money_z = $buy_logs['money_z'];

				// +($game_goods['b_money_z'] / 100)

				$money = $b_money_z + ($b_money_z * ($game_goods['b_money_z'] / 100));


			}	




			//是否超过封顶值
			if($money >= $game_goods['b_money_max']) {

				$money = $game_goods['b_money_max'];

			}

			if($member['credit_b'] < $money) {
				
				show_json(-1,'彩蛋币不足!');
			
			}


		}


		//转两位
		$money = round($money, 2);


		//购买商品增长价格
		$loga = array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$member['id'],
			'level'=>$game_goods['level'],
			'money'=>$game_goods['money'],
			'money_z'=>$money,
			'bili'=>$game_goods['money_z'],
			'time'=>time(),
			'type'=>$type,
		);

		$tablename_loga = 'wx_shop_game_goods_log'.substr($member['id'], -1);
		// $tablename_loga = 'ims_wx_game_goods_log' . substr($member['id'], -1);

		pdo_insert($tablename_loga,$loga);

		//新记录
		$gamea = array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$member['id'],
			'goodsid'=>$game_goods['id'],
			'goodsType'=>$game_goods['goodsType'],
			'goodslevel'=>$game_goods['level'],
			'income'=>$game_goods['income'],
			'status'=>1,
			'lasttime'=>time(),
		);

		$tablename_game = 'wx_shop_game'.substr($member['id'],-1);
		// $tablename_game = 'wx_shop_game' . substr($member['id'], -1);


		pdo_insert($tablename_game,$gamea);

		$inse_id = pdo_insertid();
		// pdo_update($tablename_game,$gamea,array('id'=>$weizhi['id']));




		//获取最新价格
		if($type == 0) {

			$aas = 'jinbi';

			$zx_moeny = $money + ($money * ($game_goods['money_z'] / 100));

			if($zx_moeny >= $game_goods['money_max']) {

				$zx_moeny = $game_goods['money_max'];

			}



		} elseif($type == 1) {
			
			$aas = 'credit_b';

			$zx_moeny = $money + ($money * ($game_goods['b_money_z'] / 100));

			if($zx_moeny >= $game_goods['b_money_max']) {

				$zx_moeny = $game_goods['b_money_max'];

			}

		}

		$zx_moeny = round($zx_moeny, 2);

		m('game')->setMoney($member['id'],$aas,-$money,'购买商品','购买商品');

		$gamea['id']=$inse_id;
		$gamea['receive'] = $game_goods['receive'];
		show_json(1,array('list'=>$gamea,'zx_moeny'=>$zx_moeny));


	}


	public function getTj()
	{
		
		global $_W,$_GPC;

		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		$list = pdo_fetchall('select id,goodsname,level,js,jn,goodsType from' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level=38 order by id desc ' ,array(':uniacid'=>$_W['uniacid']));

		foreach ($list as $key => $value) {
			$arr = explode(";", $value['js']);
			$list[$key]['js'] = $arr;

			$is = pdo_fetchcolumn('select id from ' . tablename('wx_shop_game' .substr($id,-1)) . ' where uniacid=:uniacid and goodsid=:goodsid and uid=:uid',array(':uniacid'=>$_W['uniacid'],':goodsid'=>$value['id'],':uid'=>$id));

			if(empty($is)) {

				$list[$key]['is'] = 0;
 
			} else {
				
				$list[$key]['is'] = 1;

			}
		}

		show_json(1,array('list'=>$list));


	}


	//回收
	public function receive()
	{
		global $_W,$_GPC;
		

		$game_id = intval($_GPC['game_id']);

		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		$member = m('member')->getMember($id, true);


		$game = pdo_fetch('select g.*,goods.receive,goods.credit_b,goods.credit_red,goods.dzp,goods.goodsname from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' g left join ' .tablename('wx_shop_game_goods'). ' goods on goods.id=g.goodsid where g.uniacid=:uniacid and g.uid=:uid and g.id=:id ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':id'=>$game_id));
		
		// echo '<pre>';
		//     print_r($game);
		// echo '</pre>';
		// exit;

		if(empty($game)) show_json(-1,'当前错误不存在!');

		if($game['status'] == 0) show_json(-1,'当前商品未开启');


		//删除
		pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game['id']));
		// pdo_update('wx_shop_game'.substr($member['id'],-1),array('goodsid'=>0,'goodsType'=>0,'goodslevel'=>0,'income'=>0,'status'=>0,'lasttime'=>0),array('id'=>$game['id']));

		
		$money = $game['receive'];

		if($money > 0 ) {
			m('game')->setMoney($member['id'],'jinbi',$money,'商品回收',$game['goodsname'].'回收赠送金币'.$money);
		}


		$credit_b = $game['credit_b'];

		if($credit_b > 0 && $game['goodslevel'] > 37) {
			//37级生效赠送彩蛋币
			m('game')->setMoney($member['id'],'credit_b',$credit_b,'商品回收',$game['goodsname'].'回收赠送彩蛋币'.$credit_b);


		}

		$credit_red = $game['credit_red'];

		if($credit_red > 0 && $game['goodslevel'] > 37) {
			//37级生效赠送红包
			m('game')->setMoney($member['id'],'credit_red',$credit_red,'商品回收',$game['goodsname'].'回收赠送红包'.$credit_red);


		}

		$dzp = $game['dzp'];

		if($dzp > 0 && $game['goodslevel'] > 37) {
			//37级生效赠送红包
			m('game')->setMoney($member['id'],'dzp',$dzp,'商品回收',$game['goodsname'].'回收赠送转盘券'.$dzp);


		}

		show_json(1,array('jinbi'=>$money,'credit_b'=>$credit_b,'credit_red'=>$credit_red,'dzp'=>$dzp));

	}


	//合成
	public function hecheng()
	{
		global $_W,$_GPC;

		$game_id1 = intval($_GPC['game_id1']);
		
		$game_id2 = intval($_GPC['game_id2']);

		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		$member = m('member')->getMember($id, true);

		$game1 = pdo_fetch('select g.*,goods.red_max,goods.red_min from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' g left join ' .tablename('wx_shop_game_goods'). ' goods on goods.id=g.goodsid where g.uniacid=:uniacid and g.uid=:uid and g.id=:id ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':id'=>$game_id1));
		
		
		$game2 = pdo_fetch('select * from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and id=:id ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':id'=>$game_id2));

		if(empty($game1) || empty($game2)) show_json(-1,'不存在当前商品');
		

		if($game1['goodslevel'] != $game2['goodslevel']) {
			show_json(-1,'商品等级不相同,无法合成!');
		}


		// if($game1['goodslevel'] >= 38 || $game2['goodslevel'] >= 38) {

		// 	show_json(-1,'最高等级,无法合成!');

		// }

		// if($game1['goodslevel'] == 38) {




		// }


		$red_level = $game1['goodslevel'] + 1;



		$redis = m('game')->getRedis();


		$time = time();

		//最高等级
		if($red_level == 38) {


			if($member['is_nb'] == 1) {

				//
				$shang = pdo_fetch('select id,goodsType,level,income,gl,goodsname,receive,lx_income,red_max,red_min from ' . tablename('wx_shop_game_goods') . 'where uniacid=:uniacid and level=38 order by id desc ',array(':uniacid'=>$_W['uniacid']));

				$arr = array(
					'type' => 2, //类型 2//合成别的最稀有
					'mes'=>'恭喜<'.$member['nickname'].'>'.date('H:i').'合成获取'.$shang['goodsname'],
					// 'goodsname'=>$shang['goodsname'],
					// 'nickname'=>$member['nickname'],
					'time'=>$time
				);


				$res = $redis->rPush('messgame', serialize($arr));//往尾部插入一条消息

				pdo_update('wx_shop_member',array('is_nb'=>0),array('id'=>$member['id']));


				//最终神鸟记录
				pdo_insert('wx_shop_game_sn',array('uniacid'=>$_W['uniacid'],'uid'=>$member['id'],'goodsid'=>$shang['id'],'time'=>time()));


			} else {
				
				//合成稀有等级
				//查询最高等级
				$game_zg = pdo_fetchall('select id,goodsType,level,income,gl,goodsname,receive,lx_income,red_max,red_min from ' . tablename('wx_shop_game_goods') . 'where uniacid=:uniacid and level=38 and goodsType!=:goodsType and goodsType!=:goodsType1',array(':uniacid'=>$_W['uniacid'],':goodsType'=>'qinglv',':goodsType1'=>'top'));


				$shang = m('game')->getJiang($game_zg);




				$arr = array(
					'type' => 2, //类型 2//合成别的稀有
					'mes'=>'恭喜<'.$member['nickname'].'>合成获取'.$shang['goodsname'],
					// 'goodsname'=>$shang['goodsname'],
					// 'nickname'=>$member['nickname'],
					'time'=>$time
				);


				$res = $redis->rPush('messgame', serialize($arr));//往尾部插入一条消息
			}




		} elseif($red_level == 39){
			//情侣合成

			$game1 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'nan'));
			$game2 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'nv'));



			if(!empty($game1) && !empty($game2)) {


				//合出情侣 nan + nv
				$shang = pdo_fetch('select id,goodsType,level,income,gl,goodsname,receive,lx_income,red_max,red_min from ' . tablename('wx_shop_game_goods') . 'where uniacid=:uniacid and level=38 order by id desc limit 1,1 ',array(':uniacid'=>$_W['uniacid']));

				// echo '<pre>';
				//     print_r($shang);
				// echo '</pre>';
				// exit;

				if(empty($shang)) {
					show_json(-1,'商品为空,无法合成！');
				}

				$arr = array(
					'type' => 2, //类型 2//合成别的稀有
					'mes'=>'恭喜<'.$member['nickname'].'>'.date('H:i').'合成获取'.$shang['goodsname'],
					// 'goodsname'=>$shang['goodsname'],
					// 'nickname'=>$member['nickname'],
					'time'=>$time
				);


				$res = $redis->rPush('messgame', serialize($arr));//往尾部插入一条消息



			} else {

				show_json(-1,'所需不足无法合成!');

			}


		} else {

			//获取上一级商品

			$shang = pdo_fetch('select * from ' . tablename('wx_shop_game_goods') . '  where uniacid=:uniacid and level=:red_level',array(':uniacid'=>$_W['uniacid'],':red_level'=>$red_level));
			
		}



		//六级发放红包

		//等于0 没有红包奖励
		$red = array('status'=>0);


		if($shang['level'] > $member['game_level'] || $shang['level'] >= 38) {


			//获取今天有没有领取38级龙

			$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
			$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));
			
			$jinri = pdo_fetch('select id from' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and uid=:uid and type=13 and level>=38 and createtime>=:stime and createtime<=:etime',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':stime'=>$stime,':etime'=>$etime));


			if(empty($jinri)) {

				//设置了就发放红包奖励

				//查询此等级有没有发放过
				$s_red = pdo_fetch('select id from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and uid=:uid and type=:type and level=:level',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':type'=>13,':level'=>$shang['level']));

				if($red_level >= 6 && $shang['red_max'] != 0 && $shang['red_min'] != 0) {
					
					
					$red['status'] = 1;
					
					$red['red_max'] = $shang['red_max'];
					
					$red['red_min'] =$shang['red_min'];

					$red['red_zong'] = m('game')->randFloat($red['red_max'],$red['red_min']);

	    			$red['logid'] = m('game')->setMoney($member['id'],'credit_red',$red['red_zong'],'升级红包','升级红包(达到'.$shang['level'].'级)',$shang['level']);



				
				}
			} else {
				
				$red = array('status'=>0);

			}



		}
		



		//第一个清空
		// pdo_update('wx_shop_game'.substr($member['id'],-1),array('goodsid'=>0,'goodsType'=>0,'goodslevel'=>0,'income'=>0,'status'=>0,'lasttime'=>0),array('id'=>$game1['id']));

		$games = array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$member['id'],
			'goodsid'=>$shang['id'],
			'goodsType'=>$shang['goodsType'],
			'goodslevel'=>$shang['level'],
			'income'=>$shang['income'],
			'status'=>1,
			'lasttime'=>time(),
		);
		// pdo_update('wx_shop_game'.substr($member['id'],-1),$games,array('id'=>$game2['id']));
		pdo_insert('wx_shop_game'.substr($member['id'],-1),$games);

		$g_ids = pdo_insertid();

		//删除旧两条
		pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game1['id']));
		pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game2['id']));


		if($shang['level'] > $member['game_level']) {

			//修改会员游戏等级
			$wd['game_level'] = $shang['level'];

		}

		//发放奖励
		$set = pdo_fetch('select * from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));


		if($shang['level'] >= $set['rz_level'] && $member['is_rz'] == 1){


			$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
			$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));


			$agent = pdo_fetch('select id,agentid from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$member['agentid']));


			//查询自己有没有领取过奖励
			// $lq = pdo_fetch('select id from '  .tablename('wx_shop_game_log'.substr($id, -1)) . ' where uniacid=:uniacid and uid=:uid and type=19 limit 1',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id']));



			if(!empty($agent) && empty($member['is_rzlq']) && empty($member['is_rzlq_1'])) {
				
				//上级获取领取认证奖励记录
				$rz = pdo_fetchcolumn('select count(id) from '  .tablename('wx_shop_game_log'.substr($agent['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=17',array(':uniacid'=>$_W['uniacid'],':uid'=>$agent['id'],':stime'=>$stime,':etime'=>$etime));

				//发放上级奖励
				if( ($rz / 2)   < $set['rz_max']) {

					m('game')->setMoney($agent['id'],'credit_b',$set['rz_1'],'徒弟认证奖励','徒弟认证奖励彩蛋币'.$set['rz_1']);
					m('game')->setMoney($agent['id'],'yqq',$set['rz_2'],'徒弟认证奖励','徒弟认证奖励邀请券'.$set['rz_2']);

					$wd['is_rzlq'] = $set['rz_1'].','.$set['rz_2'];

				}  else {
					//超过上限
					$wd['is_rzlq'] = '超过每日上限';

				}

				$two = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$agent['agentid']));

				if(!empty($two)) {

					//上上级获取领取认证奖励记录
					$rz1 = pdo_fetchcolumn('select count(id) from '  .tablename('wx_shop_game_log'.substr($two['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=18',array(':uniacid'=>$_W['uniacid'],':uid'=>$two['id'],':stime'=>$stime,':etime'=>$etime));

					//发放上上级奖励
					if($rz1   < $set['rzs_max']) {

						m('game')->setMoney($two['id'],'credit_b',$set['rzs_1'],'徒孙认证奖励','徒孙认证奖励彩蛋币'.$set['rzs_1']);

						$wd['is_rzlq_1'] = $set['rzs_1'];

					} else {
						
						$wd['is_rzlq_1'] = '超过每日上限';

					}

				}
			
			}


		}

		if(!empty($wd)) {
			pdo_update('wx_shop_member',$wd,array('id'=>$member['id']));
		}



		$games['id'] = $g_ids;
		$games['goodsname'] = $shang['goodsname'];
		$games['receive'] = $shang['receive'];
		$games['lx_income'] = $shang['lx_income'];
		show_json(1,array('games'=>$games,'red'=>$red));



	}

	//合成nb
	public function wuheyi(){

		global $_W,$_GPC;

		$redis = m('game')->getRedis();


		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}


		$member = m('member')->getMember($id, true);

		
		$game1 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'jin'));
		$game2 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'mu'));
		$game3 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'shui'));
		$game4 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'huo'));
		$game5 = pdo_fetch('select id from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and goodsType=:goodsType ',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':goodsType'=>'tu'));

		if(!empty($game1) && !empty($game2) && !empty($game3) && !empty($game4) && !empty($game5) ) {

				$shang = pdo_fetch('select id,goodsType,level,income,gl,goodsname,lx_income,receive,red_max,red_min from ' . tablename('wx_shop_game_goods') . 'where uniacid=:uniacid and level=38 order by id desc ',array(':uniacid'=>$_W['uniacid']));

				$arr = array(
					'type' => 2, //类型 2//合成别的最稀有
					// 'goodsname'=>$shang['goodsname'],
					'mes'=>'恭喜<'.$member['nickname'].'>'.date('H:i').'合成获取'.$shang['goodsname'],
					'time'=>$time
				);


				$res = $redis->rPush('messgame', serialize($arr));//往尾部插入一条消息


				$games = array(
					'uniacid'=>$_W['uniacid'],
					'uid'=>$member['id'],
					'goodsid'=>$shang['id'],
					'goodsType'=>$shang['goodsType'],
					'goodslevel'=>$shang['level'],
					'income'=>$shang['income'],
					'status'=>1,
					'lasttime'=>time(),
				);
				// pdo_update('wx_shop_game'.substr($member['id'],-1),$games,array('id'=>$game2['id']));
				pdo_insert('wx_shop_game'.substr($member['id'],-1),$games);

				$as = pdo_insertid();

				//删除旧两条

				// $shan[0] = $game1['']

				$shan = array();
				$shan[0] = $game1['id'];
				$shan[1] = $game2['id'];
				$shan[2] = $game3['id'];
				$shan[3] = $game4['id'];
				$shan[4] = $game5['id'];



				pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game1['id']));
				pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game2['id']));
				pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game3['id']));
				pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game4['id']));
				pdo_delete('wx_shop_game'.substr($member['id'],-1),array('id'=>$game5['id']));

				$games['id']=$as;

				$games['receive'] = $shang['receive'];
				$games['lx_income'] = $shang['lx_income'];

				//最终神鸟记录
				pdo_insert('wx_shop_game_sn',array('uniacid'=>$_W['uniacid'],'uid'=>$member['id'],'goodsid'=>$shang['id'],'time'=>time()));
				
				$red = array();

				$red['status'] = 1;
				
				$red['red_max'] = $shang['red_max'];
				
				$red['red_min'] =$shang['red_min'];

				$red['red_zong'] = m('game')->randFloat($red['red_max'],$red['red_min']);

    			$red['logid'] = m('game')->setMoney($member['id'],'credit_red',$red['red_zong'],'升级红包','升级红包(达到'.$shang['level'].'级)',$shang['level']);


				show_json(1,array('games'=>$games,'shan'=>$shan,'red'=>$red));


		} else {
			//正常情况下 不会走
			show_json(-1,'缺少,无法合成');
		}
		


	}


	public function weizhi() 
	{

		global $_W,$_GPC;

		$token = $_GPC['token'];


		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}
		
		$type = intval($_GPC['type']);
			

		$weizhi = $_GPC['weizhi'];
		
		$member = pdo_fetch('select weizhi from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$id));

		if(empty($member)) show_json(-1,'用户不存在!');

		if($type == 1) {

			pdo_update('wx_shop_member',array('weizhi'=>$weizhi),array('id'=>$id));

			show_json(1,'成功!');

		} else {

			// echo 1;
			show_json(1,array('weizhi'=>$member['weizhi']));
		}

	}

	//商品列表
	public function splist()
	{

		// show_json(1, 233);

		global $_W,$_GPC;
		
		$token = $_GPC['token'];

		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}
		
		$list = m('game')->getSp1($id);

		//获取免费商品
		m('game')->getSp($id);
		// $a = 10;

		// $b = -1;

		// echo '<pre>';
		//     print_r($a+$b);
		// echo '</pre>';

		show_json(1,array('list'=>$list));


	}


	public function dzp(){
		
		global $_W,$_GPC;


		$dzplist = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dzplist') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));

		show_json(1,array('dzplist'=>$dzplist));


	}

	//大转盘抽奖
	public function dzp_jiang()
	{

		global $_W,$_GPC;

		$token = $_GPC['token'];

		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}

		$member = m('member')->getMember($id, true);

		if(empty($member)) return;

		$cj_type = 'dzp';
		$cj_sta = '转盘券';
		if($member['dzp'] < 1) {

			if($member['yqq'] < 1) {
				show_json(-1,'邀请券转盘券不足!');
			} else {

				$cj_type = 'yqq';
				$cj_sta = '邀请券';

			}

			// show_json(-1,'转盘券不足!');
		}

		$dzplist = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dzplist') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));


		$zhong = m('game')->getJiang($dzplist);



		if($zhong['status'] == 0) {
			
			$status = 'jinbi';
			$status1 = '金币';
		
		} else if($zhong['status'] == 1) {

			$status = 'credit_b';
			$status1 = '彩蛋币';


		} else if($zhong['status'] == 2) {
				
			$status = 'credit_red';
			$status1 = '现金红包';


		} else if($zhong['status'] == 3) {
			
			$status = 'dzp';
			$status1 = '转盘券';

		}


		m('game')->setMoney($member['id'],$cj_type,-1,'大转盘抽奖','大转盘抽奖消费'.$cj_sta);

		m('game')->setMoney($member['id'],$status,$zhong['money'],'大转盘中奖','大转盘中奖'.$status1);
		

		// pdo_update('wx_shop_member',array('dzp'=>$member['dzp']-1),array('id'=>$member['id']));


		show_json(1,array('dzplist'=>$zhong));


	}


	//敲蛋
	public function qd_jiang()
	{

		global $_W,$_GPC;

		$token = $_GPC['token'];

		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($id)) {
			show_json(-2,'token错误!');
		}

		$member = m('member')->getMember($id, true);

		

		if($member['qd_num'] <= 0) {
			
			show_json(-1,'敲蛋数量不足!');
		
		}


		$qdlist = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_qdlist') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));


		$zhong = m('game')->getJiang($qdlist);

		// echo '<pre>';
		//     print_r($zhong);
		// echo '</pre>';
		// exit;

		if($zhong['status'] == 0) {
			
			$status = 'jinbi';
			$status1 = '金币';
		
		} else if($zhong['status'] == 1) {

			$status = 'credit_b';
			$status1 = '彩蛋币';


		} else if($zhong['status'] == 2) {
				
			$status = 'credit_red';
			$status1 = '现金红包';


		}

		// pdo_update('wx_shop_member',array('k_video'=>$member['k_video']-1),array('id'=>$id));

		if($zhong['num'] == 0) {
			//未中奖!
			$img = m('game')->gaozi();

			$zhong['img'] = tomedia($img);

			m('game')->setMoney($member['id'],'qd_num',-1,'敲蛋抽奖消费','敲蛋抽奖消费1次机会');



			// m('game')->setMoney($member['id'],,$zhong['money'],'敲蛋中奖','敲蛋抽奖中奖'.$status1);

			show_json(1,array('qdlist'=>$zhong));



		} else {

			$ias = m('game')->qd_jiang($zhong);

			if($ias == 1) {
				//中奖且奖品存在

				m('game')->setMoney($member['id'],'qd_num',-1,'敲蛋抽奖消费','敲蛋抽奖消费1次机会');

				m('game')->setMoney($member['id'],$status,$zhong['money'],'敲蛋中奖','敲蛋抽奖中奖'.$status1);

				show_json(1,array('qdlist'=>$zhong));

			
			} else {


				$zhong = pdo_fetch('select * from ' . tablename('wx_shop_game_qdlist') . ' where uniacid=:uniacid and num=0',array(':uniacid'=>$_W['uniacid']));

				$img = m('game')->gaozi();

				$zhong['img'] = tomedia($img);

				m('game')->setMoney($member['id'],'qd_num',-1,'敲蛋抽奖消费','敲蛋抽奖消费1次机会');


				show_json(1,array('qdlist'=>$zhong));

				//中奖奖品不存在,中无线的奖品

			}

			

		}


	}



	//观看视频
	public function k_video()
	{

		global $_W,$_GPC;

		$token = $_GPC['token'];

		$id=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		// echo '<pre>';
		//     print_r($id);
		// echo '</pre>';

		if(empty($id)) {
			show_json(-2,'token错误!');
		}

		$member = m('member')->getMember($id, true);

		if(empty($member)) return;


		if($member['k_video'] <= 0 ){

			show_json(-1,'视频观看次数不足!');

		}

		//上一级
		$one = pdo_fetch('select m.id,m.agentid,gl.bili from ' . tablename('wx_shop_member') . ' m left join ' . tablename('wx_shop_game_level') . ' gl on gl.id=m.gg_level where m.uniacid=:uniacid and m.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$member['agentid']));
		// echo '<pre>';
		//     print_r($one);
		// echo '</pre>';
		// exit;

		$set = pdo_fetch('select gg_money,fx_one,fx_two,fh_one,fh_two,sn_bili,zr_sn from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));

		$game = m('game');

		// echo '<pre>';
		//     print_r(substr($id, -1));
		// echo '</pre>';
		// exit;

		pdo_insert('wx_shop_game_video',array(
			'uniacid'=>$_W['uniacid'],
			'uid'=>$member['id'],
			'money'=>$set['gg_money'],
			'time'=>time(),
			'content'=>$_GPC['content']
		));

		$video_id = pdo_insertid();

		if(!empty($one)) {

			//自身广告等级
			$bili_one = ($set['fx_one']/100) * $one['bili'];	
			// echo '<pre>';
			//     print_r($bili_one);
			// echo '</pre>';
			// exit;

			$one_money = $set['gg_money'] *  $bili_one;
			
			$one_money = round($one_money,2);


			$type = $game->type_money('徒弟返佣');

			$arr_one = array(
				'uniacid'=>$_W['uniacid'],
				'uid'=>$one['id'],
				'f_uid'=>$member['id'],
				'time'=>time(),
				'fx_money'=>$one_money,
				'status'=>1,
				'type'=>$type,
				'video_id'=>$video_id,
				'bili'=>$set['fx_one'],
				'bs'=>$one['bili'],
				'video_uid'=>$member['id']
			);

			pdo_insert('wx_shop_game_redlog'.substr($video_id,-1),$arr_one);

			// echo '<pre>';
			//     print_r($arr_one);
			// echo '</pre>';

			// $two = pdo_fetch('select id,agentid from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$one['agentid']));

			$two = pdo_fetch('select m.id,m.agentid,gl.bili from ' . tablename('wx_shop_member') . ' m left join ' . tablename('wx_shop_game_level') . ' gl on gl.id=m.gg_level where m.uniacid=:uniacid and m.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$one['agentid']));

			// echo '<pre>';
			//     print_r($two);
			// echo '</pre>';
			if(!empty($two)) {

				//自身广告等级
				$bili_two =  ($set['fx_two']/100) * $two['bili'];	

				$two_money = $set['gg_money'] *  $bili_two;

				$two_money = round($two_money,2);
				$type = $game->type_money('徒孙返佣');

				$arr_two = array(
					'uniacid'=>$_W['uniacid'],
					'uid'=>$two['id'],
					'f_uid'=>$member['id'],
					'time'=>time(),
					'fx_money'=>$two_money,
					'status'=>1,
					'type'=>$type,
					'video_id'=>$video_id,
					'bili'=>$set['fx_two'],
					'bs'=>$two['bili'],
					'video_uid'=>$member['id']


				);
				pdo_insert('wx_shop_game_redlog'.substr($video_id,-1),$arr_two);


			}


			// }

		}


		//发放所有神鸟奖励
		$zrtime = mktime(23,59,59,date("m"),date("d")-2,date("Y"));

		//前天神鸟数量
		$sn_goods = pdo_fetch('select * from ' . tablename('wx_shop_game_zrsn') . ' where uniacid=:uniacid and times=:times',array(':uniacid'=>$_W['uniacid'],':times'=>$zrtime));

		$sn_goods['num'] = empty($sn_goods) ? 0: $sn_goods['num'];


		$sn_members = unserialize($sn_goods['sn_member']);

		// echo "<pre>";
		// 	print_r($sn_goods);
		// echo "</pre>";
		// exit;

		if(!empty($sn_members)){

			

			foreach ($sn_members as $key => $value) {

				$members = array();
				
				$one = array();

				$two = array();

				$members=pdo_fetch("select id,agentid from ".tablename("wx_shop_member")." where id=:id limit 1",array(":id"=>$value));

				// echo "<pre>";
				// 	print_r($value);
				// echo "</pre>";
				// exit;
				$one = pdo_fetch('select m.id,m.agentid,gl.bili from ' . tablename('wx_shop_member') . ' m left join ' . tablename('wx_shop_game_level') . ' gl on gl.id=m.gg_level where m.uniacid=:uniacid and m.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$members['agentid']));

				// echo "<pre>";
				// 	print_r($one);
				// echo "</pre>";
				// exit;

				if(!empty($members)) {
					//可以分红

					//自己的神鸟奖励
					// $fh_one = $set['gg_money'] * ($set['fh_one']/100);
					// $fh_one = round($fh_one,2);

					//获取昨日全网神鸟总数
					$sn_num = $sn_goods['num'];

					// echo "<pre>";
					// 	print_r($sn_num);
					// echo "</pre>";
					// exit;

					$sn_money = $set['gg_money'] * ($set['sn_bili']/100) / $sn_num;
					// echo "<pre>";
					// 	print_r($sn_money);
					// echo "</pre>";
					// exit;
					//获取得到的钱
					$sn_money = round($sn_money,2);

					if($sn_money <= 0) {
						continue;
					}

					$type = $game->type_money('神鸟奖励');

					$arr_one_sn = array(
						'uniacid'=>$_W['uniacid'],
						'uid'=>$members['id'],
						'f_uid'=>$members['id'],
						'time'=>time(),
						'sn_money'=>$sn_money,
						'status'=>0,
						'type'=>$type,
						'video_id'=>$video_id,
						'bili'=>$set['sn_bili'],
						'bs'=>$sn_num,
						'video_uid'=>$member['id'],
						'time_jr'=>mktime(0,0,0,date("m"),date("d"),date("Y"))
					);
					// echo "<pre>";
					// 	print_r($arr_one_sn);
					// echo "</pre>";

					pdo_insert('wx_shop_game_redlog'.substr($video_id,-1),$arr_one_sn);
					//

					

					if(!empty($one)) {

						if($members['agentid'] == 0) {
							continue;
						}

						if($one['agentid'] == 0) {
							continue;
						}

						$fh_one = $sn_money * ($set['fh_one']/100);
						$fh_one = round($fh_one,2);


						$type = $game->type_money('徒弟分红');

						$arr_one_fh = array(
							'uniacid'=>$_W['uniacid'],
							'uid'=>$one['id'],
							'f_uid'=>$members['id'],
							'time'=>time(),
							'fh_money'=>$fh_one,
							'status'=>0,
							'type'=>$type,
							'video_id'=>$video_id,
							'bili'=>$set['fh_one'],
							'bs'=>$one['bili'],
							'video_uid'=>$member['id']
						);
						pdo_insert('wx_shop_game_redlog'.substr($video_id,-1),$arr_one_fh);


						// echo '<pre>';
						//     print_r($arr_one_fh);
						// echo '</pre>';
						// $san = pdo_fetch('select id,agentid from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$two['agentid']));

						$two = pdo_fetch('select m.id,m.agentid,gl.bili from ' . tablename('wx_shop_member') . ' m left join ' . tablename('wx_shop_game_level') . ' gl on gl.id=m.gg_level where m.uniacid=:uniacid and m.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$one['agentid']));

					}

					// echo "<pre>";
					// 	print_r($two);
					// echo "</pre>";

					if(!empty($two)) {

						if($one['agentid'] == 0) {
							continue;
						}

						if($two['agentid'] == 0) {
							continue;
						}

						$fh_two = $sn_money * ($set['fh_two']/100);
						$fh_two = round($fh_two,2);

						$type = $game->type_money('徒孙分红');

						$arr_two_fh = array(
							'uniacid'=>$_W['uniacid'],
							'uid'=>$two['id'],
							'f_uid'=>$one['id'],
							'time'=>time(),
							'fh_money'=>$fh_two,
							'status'=>0,
							'type'=>$type,
							'video_id'=>$video_id,
							'bili'=>$set['fh_two'],
							'bs'=>$two['bili'],
							'video_uid'=>$member['id']
						);

						// echo "<pre>";
						// 	print_r($arr_two_fh);
						// echo "</pre>";
						pdo_insert('wx_shop_game_redlog'.substr($video_id,-1),$arr_two_fh);

					}


				}
			}


		}
		// exit;


		$logid = intval($_GPC['logid']);


		pdo_update('wx_shop_member',array('k_video'=>$member['k_video']-1),array('id'=>$id));


		//区域分红
		m('game')->abonus($id,$set['gg_money'],$video_id);

		
		if($logid) {

			//视频翻倍
			$log = pdo_fetch('select uid,money,type,status from ' . tablename('wx_shop_game_log'.substr($id, -1)) .' where uniacid=:uniacid and id=:id and settype=:settype and uid=:uid ' ,array(':uniacid'=>$_W['uniacid'],':id'=>$logid,':settype'=>'jinbi',':uid'=>$id));


			if($log['status'] == 1) {
				show_json(-1,'封号吧！');
			}


			if(empty($log)) {
				show_json(-1,'视频翻倍奖励错误！');
			
			} else {

				m('game')->setMoney($member['id'],'jinbi',$log['money'],'视频翻倍金币奖励','观看视频翻倍金币奖励'.$log['money']);

				pdo_update('wx_shop_game_log'.substr($id, -1),array('status'=>1),array('id'=>$logid));

				show_json(1,array('money'=>$log['money']));				

			}



		} else {

			//普通观看视屏
			$money = m('game')->getZg($id);

			m('game')->setMoney($member['id'],'jinbi',$money,'视频金币奖励','观看视频得金币奖励'.$money);

			show_json(1,array('money'=>$money));

		}


	}

	public function articlelist(){
		global $_W,$_GPC;

		$token = $_GPC['token'];
		$cid = $_GPC['article_category'];

		$uid=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($uid)) {
			show_json(-2,'token错误!');
		}

		if(empty($cid)){
			show_json(-1,'分类错误');
		}

		//活动文章表ims_wx_shop_article
		// image_url is_read article_detail
		// 活动  图片一张  23
		// 系统消息 标题article_title 文章 时间 是否已读 25
		// 游戏玩法  视屏 题目 标题 还有图像  24 
		$sql = "SELECT id,article_category,article_content,article_date_v,is_read,article_detail,image_url FROM".tablename('wx_shop_article')."  WHERE article_category=:article_category AND uniacid=:uniacid";
		
		//返回数据
		$params = array(':uniacid' => $_W['uniacid'], ':article_category' => $cid);  		
		$list = pdo_fetchall($sql, $params);

		//分类名字
		$sql = "SELECT id,category_name FROM".tablename('wx_shop_article_category')."  WHERE id=:id AND uniacid=:uniacid";
		$param = array(':uniacid' => $_W['uniacid'], ':id' => $cid);  		
		$name = pdo_fetchall($sql, $param);

		//系统消息  头像
		if($cid == 25){
			foreach ($list as $key => $value) {
				$list[$key]['status'] = 0;
				if(!empty($value['is_read'])){
					$uids = explode(',',$value['is_read']);
					$is_uid = in_array($uid , $uids);
					if($is_uid){
						$list[$key]['status'] = 1;
					}
				}
			}
		}
		$result['list'] = $list?$list:array();
		$result['name'] = $name[0]['category_name'];
		show_json(1 , $result);

	}

		//系统公告已读
	public function articleread(){
		global $_W,$_GPC;
		$token = $_GPC['token'];
		$cid = $_GPC['id'];

		$uid=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));
		if(empty($uid)) {
		show_json(-2,'token错误!');
		}
		if(empty($cid)){
		show_json(-1,'传入参数有误');
		}
		$sql = "SELECT id,is_read,article_content,article_title FROM".tablename('wx_shop_article')."  WHERE id=:id AND uniacid=:uniacid";
		$params = array(':uniacid' => $_W['uniacid'], ':id' => $cid);  		
		$list = pdo_fetch($sql, $params);
		if(!$list){
			show_json(-1,'传入参数有误');
		}
		if(!empty($list['is_read'])){
				$uids = explode(',',$list['is_read']);
				$is_uid = in_array($uid , $uids);
				if(!$is_uid){
					$is_read = $list['is_read'].','.$uid;	
				}
		}else{
			$is_read = $uid;
		}
		pdo_update('wx_shop_article',array('is_read'=>$is_read),array('id'=>$cid));
		unset($list['is_read']);
		show_json(1 ,$list, '阅读成功');
	}


	public function diz(){

		global $_W,$_GPC;

		$token = $_GPC['token'];

		$uid=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));
		
		// echo "<pre>";
		// 	print_r($uid);
		// echo "</pre>";
		if(empty($uid)) {
			show_json(-2,'token错误!');
		}

		$member = pdo_fetch('select id,province,city,area from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));

		if(empty($member)) return;

		if(!empty($member['province'])) {
			show_json(-1,'地区已经绑定成功,无需重复绑定！');
		}

		$data['province'] = trim($_GPC['province']);

		$data['city'] = trim($_GPC['city']);

		$data['area'] = trim($_GPC['area']);

		if(empty($data['province']) || empty($data['city']) || empty($data['area'])) {
			show_json(-1,'请认真填写');
		}

		$data['address'] = iserializer(array(
			'province'=>$data['province'],
			'city'=>$data['city'],
			'area'=>$data['area'],
		));

		pdo_update('wx_shop_member',$data,array('id'=>$uid));


		show_json(1,'成功!');

	}	



	public function article(){

		global $_W,$_GPC;

		$token = $_GPC['token'];

		$uid=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));
		
		// echo "<pre>";
		// 	print_r($uid);
		// echo "</pre>";
		// if(empty($uid)) {
		// 	show_json(-2,'token错误!');
		// }

		// $member = pdo_fetch('select id,province,city,area from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));

		// if(empty($member)) return;


		$cid = intval($_GPC['id']);

		$sql = "SELECT id,article_content,article_title FROM".tablename('wx_shop_article')."  WHERE id=:id AND uniacid=:uniacid";

		$params = array(':uniacid' => $_W['uniacid'], ':id' => $cid);  		
		
		$list = pdo_fetch($sql, $params);

		if(empty($list)) {
			show_json(-1,'当前文章不存在,请联系管理员!');
		}


		show_json(1 ,$list,'成功');


	}
}
?>
<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Qdlist_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;



		$condition = ' and uniacid=:uniacid and deleted=0';
		$params = array(':uniacid' => $_W['uniacid']);
		// if ($_GPC['enabled'] != '') 
		// {
		// 	$condition .= ' and enabled=' . intval($_GPC['enabled']);
		// }
		// if (!(empty($_GPC['keyword']))) 
		// {
		// 	$_GPC['keyword'] = trim($_GPC['keyword']);
		// 	$condition .= ' and ( levelname like :levelname)';
		// 	$params[':levelname'] = '%' . $_GPC['keyword'] . '%';
		// }
		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_qdlist') . ' WHERE 1 ' . $condition, $params);
		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		// if($_GPC['day'] == 1) {
		// 	show_json(1);
		// }
		include $this->template('game/qdlist/index');
	}
	public function add() 
	{
		$this->post();
	}
	public function edit() 
	{
		$this->post();
	}
	protected function post() 
	{
		global $_W;
		global $_GPC;
		$id = trim($_GPC['id']);

		$goods = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_game_qdlist') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => intval($id)));

		if ($_W['ispost']) 
		{

			$data = array(
				'goodsname'=>$_GPC['goodsname'],
				'img'=>$_GPC['img'],
				'money'=>$_GPC['money'],
				'gl'=>$_GPC['gl'],
				// 'goodsType'=>$_GPC['goodsType'],
				'status'=>$_GPC['status'],
				'num'=>$_GPC['num'],
			);
			// echo '<pre>';
			//     print_r($id);
			// echo '</pre>';
			// exit;
			if (empty($goods)) 
			{

				$goods_num = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('wx_shop_game_qdlist') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));


				if($goods_num + 1 >= 10) {
					show_json(0, '敲蛋商品最多不能超过10个!');
				}


				$data['uniacid']=$_W['uniacid'];

				pdo_insert('wx_shop_game_qdlist', $data);
				$id = pdo_insertid();
				plog('game.qdlist.add', '添加敲蛋商品 ID: ' . $id);
			}
			else 
			{
				pdo_update('wx_shop_game_qdlist', $data, array('id' => $id));
				plog('game.qdlist.edit', '编辑敲蛋商品 ID:' . $id);
			}

	
			show_json(1, array('url' => webUrl('game/qdlist')));
		}
		
		include $this->template();
	}


	public function day(){

		global $_W;
		global $_GPC;
		//今天数据

		// echo '<pre>';
		//     print_r($_GPC);
		// echo '</pre>';
		// exit;
		// show_json(1);


		//获取今天时间
		$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));


		$jinri = pdo_fetch('select id from ' . tablename('wx_shop_game_qdlist_day') . ' where uniacid=:uniacid and time>=:stime and time<=:etime',array(':uniacid'=>$_W['uniacid'],':stime'=>$stime,':etime'=>$etime));

		// echo '<pre>';
		//     print_r($jinri);
		// echo '</pre>';
		if(empty($jinri)) {

			$goods = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_qdlist') . ' WHERE  uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));


			$arr = array();

			foreach ($goods as $key => $value) {

				if($value['num'] > 0) {

					for ($i=0; $i < $value['num']; $i++) { 
							
						$arr[] = $value;

					}

				}

			}

			pdo_insert('wx_shop_game_qdlist_day',array(
				'uniacid'=>$_W['uniacid'],
				'data'=>serialize($arr),
				'data_s'=>serialize($arr),
				'time'=>time(),
				'time_s'=>date("Y-m-d H:i:s",time()),
			));

			// echo '<pre>';
			//     print_r($arr);
			// echo '</pre>';
			// exit;
			show_json(1,'成功');
			

		} else {

			show_json(2,'今日已经修改!');

		}



	}
}
?>
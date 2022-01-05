<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Qdjinri_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;



		$condition = ' and uniacid=:uniacid';
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
		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_qdlist_day') . ' WHERE 1 ' . $condition . ' order by time desc ', $params);


		foreach ($list as $key => $value) {
				
			$num = unserialize($value['data']);

			$list[$key]['data_sy'] = $num;

			$list[$key]['data_sy_num'] = count($num);

			$num_s = unserialize($value['data_s']);


			$list[$key]['lj'] = count($num_s);

			$list[$key]['time'] = date("Y-m-d H:i:s",$value['time']);

		}

		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;

		include $this->template('game/qdlist/jinri');
	}

	public function all(){

		global $_W,$_GPC;

		$type = intval($_GPC['type']);

		$getdetail = intval($_GPC['getdetail']);

		$list = pdo_fetch('select * from '. tablename('wx_shop_game_qdlist_day') . 'where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$getdetail));

		//今日未领取
		$num = unserialize($list['data']);
		// echo '<pre>';
		//     print_r($num);
		// echo '</pre>';
		$data_s = unserialize($list['data_s']);


		


		if($type == 1) {
			
			foreach ($num as $key => $value) {
				
				if($value['status'] == 0) {
					$num[$key]['status_s'] = '金币';
				} else if($value['status'] == 1) {
					$num[$key]['status_s'] = '彩蛋币';

				} else if($value['status'] == 2) {
					$num[$key]['status_s'] = '现金';

				} else if($value['status'] == 3) {
					$num[$key]['status_s'] = '卡片稿子';

				}
			}

			$num =array_values($num);

			echo json_encode($num,true);exit;



		} else {
			
			foreach ($data_s as $key => $value) {
				
				if($value['status'] == 0) {
					$data_s[$key]['status_s'] = '金币';
				} else if($value['status'] == 1) {
					$data_s[$key]['status_s'] = '彩蛋币';

				} else if($value['status'] == 2) {
					$data_s[$key]['status_s'] = '现金';

				} else if($value['status'] == 3) {
					$data_s[$key]['status_s'] = '卡片稿子';

				}
			}
			echo json_encode($data_s,true);exit;

		}


	}

}
?>
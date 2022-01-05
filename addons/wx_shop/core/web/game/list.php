<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class List_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;

		$set = array();


		// echo 1;exit;

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
		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_goods') . ' WHERE 1 ' . $condition . ' ORDER BY level asc', $params);
		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;


		// m('game')->setLevel(3997);
		// m('game')->getLevel(3995);
		// exit;
		include $this->template('game/list/index');
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

		$goods = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_game_goods') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => intval($id)));

		if ($_W['ispost']) 
		{

			$data = array(
				'goodsname'=>$_GPC['goodsname'],
				'img'=>$_GPC['img'],
				'img_w'=>$_GPC['img_w'],
				'money'=>$_GPC['money'],
				'b_money'=>$_GPC['b_money'],
				'money_z'=>$_GPC['money_z'],
				'b_money_z'=>$_GPC['b_money_z'],
				'money_max'=>$_GPC['money_max'],
				'b_money_max'=>$_GPC['b_money_max'],
				'income'=>$_GPC['income'],
				'lx_income'=>$_GPC['lx_income'],
				'red_min'=>$_GPC['red_min'],
				'red_max'=>$_GPC['red_max'],
				'receive'=>$_GPC['receive'],
				// 'dzp'=>$_GPC['dzp'],
				// 'goodsType'=>$_GPC['goodsType'],
				// 'goodsType1'=>$_GPC['goodsType1'],
			);
			// echo '<pre>';
			//     print_r($id);
			// echo '</pre>';
			// exit;
			if (empty($id)) 
			{

				$data['uniacid']=$_W['uniacid'];

				$game = pdo_fetch('select level from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid order by level desc ',array(':uniacid'=>$_W['uniacid']));

				if(empty($game)) {
					$data['level'] = 1;
				} else {

					if($game['level'] <= 37) {
						$data['level'] = $game['level']+1;
					} else {
						$data['level'] = 38;

					}


					if($data['level'] > 37) {
						$data['credit_b'] = intval($_GPC['credit_b']);
						$data['credit_red'] = intval($_GPC['credit_red']);
						$data['gl'] = floatval($_GPC['gl']);
						$data['dzp'] = floatval($_GPC['dzp']);
						
						$data['jn'] = $_GPC['jn'];
						$data['js'] = $_GPC['js'];
					}

				}
				pdo_insert('wx_shop_game_goods', $data);
				$id = pdo_insertid();
				plog('game.goods.add', '添加游戏商品 ID: ' . $id);
			}
			else 
			{

				if($goods['level'] > 37) {
					$data['credit_b'] = intval($_GPC['credit_b']);
					$data['credit_red'] = intval($_GPC['credit_red']);
					$data['gl'] = floatval($_GPC['gl']);
					
					$data['jn'] = $_GPC['jn'];
					$data['js'] = $_GPC['js'];
				}

				pdo_update('wx_shop_game_goods', $data, array('id' => $id));
				plog('game.goods.edit', '编辑游戏商品 ID:' . $id);
			}

	
			show_json(1, array('url' => webUrl('game/list')));
		}
		
		include $this->template();
	}
}
?>
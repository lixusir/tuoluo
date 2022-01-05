<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Dzplist_WxShopPage extends WebPage
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
		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dzplist') . ' WHERE 1 ' . $condition, $params);
		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		include $this->template('game/dzplist/index');
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

		$goods = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_game_dzplist') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => intval($id)));

		if ($_W['ispost']) 
		{

			$data = array(
				'goodsname'=>$_GPC['goodsname'],
				'img'=>$_GPC['img'],
				'money'=>$_GPC['money'],
				'gl'=>$_GPC['gl'],
				// 'goodsType'=>$_GPC['goodsType'],
				'status'=>$_GPC['status'],
			);
			// echo '<pre>';
			//     print_r($id);
			// echo '</pre>';
			// exit;
			if (empty($goods)) 
			{

				$goods_num = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('wx_shop_game_dzplist') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));


				if($goods_num + 1 >= 10) {
					show_json(0, '大转盘商品最多不能超过10个!');
				}


				$data['uniacid']=$_W['uniacid'];

				pdo_insert('wx_shop_game_dzplist', $data);
				$id = pdo_insertid();
				plog('game.dzplist.add', '添加大转盘商品 ID: ' . $id);
			}
			else 
			{
				pdo_update('wx_shop_game_dzplist', $data, array('id' => $id));
				plog('game.dzplist.edit', '编辑大转盘商品 ID:' . $id);
			}

	
			show_json(1, array('url' => webUrl('game/dzplist')));
		}
		
		include $this->template();
	}
}
?>
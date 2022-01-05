<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Dtlist_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;



		$condition = ' and uniacid=:uniacid';
		$params = array(':uniacid' => $_W['uniacid']);
		if ($_GPC['type'] != '') 
		{
			$condition .= ' and type=:type';

			$params[':type'] = intval($_GPC['type']);

		}
		// if (!(empty($_GPC['keyword']))) 
		// {
		// 	$_GPC['keyword'] = trim($_GPC['keyword']);
		// 	$condition .= ' and ( levelname like :levelname)';
		// 	$params[':levelname'] = '%' . $_GPC['keyword'] . '%';
		// }
		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dtlist') . ' WHERE 1  ' . $condition . ' order by type asc', $params);
		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		include $this->template('game/dtlist/index');
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

		$goods = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_game_dtlist') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => intval($id)));

		if ($_W['ispost']) 
		{

			$data = array(
				'tm'=>$_GPC['tm'],
				'type'=>$_GPC['type'],
				'abcd'=>$_GPC['abcd'],
				'a_w'=>$_GPC['a_w'],
				'b_w'=>$_GPC['b_w'],
				'c_w'=>$_GPC['c_w'],
				'd_w'=>$_GPC['d_w'],
				'tmjx'=>$_GPC['tmjx'],

			);
			// echo '<pre>';
			//     print_r($id);
			// echo '</pre>';
			// exit;
			if (empty($goods)) 
			{

				$goods_num = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('wx_shop_game_dtlist') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));


				$data['uniacid']=$_W['uniacid'];

				pdo_insert('wx_shop_game_dtlist', $data);
				$id = pdo_insertid();
				plog('game.dtlist.add', '添加答题类型 ID: ' . $id);
			}
			else 
			{
				pdo_update('wx_shop_game_dtlist', $data, array('id' => $id));
				plog('game.dtlist.edit', '编辑答题类型 ID:' . $id);
			}

	
			show_json(1, array('url' => webUrl('game/dtlist')));
		}
		
		include $this->template();
	}
}
?>
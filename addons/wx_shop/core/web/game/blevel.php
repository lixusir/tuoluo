<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Blevel_WxShopPage extends WebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;

    	$list = pdo_fetchall("SELECT * FROM " . tablename('wx_shop_game_blevel') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY level asc");

		include $this->template();
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

		$level = pdo_fetch("SELECT * FROM " . tablename('wx_shop_game_blevel') . " WHERE uniacid = '{$_W['uniacid']}' and id=:id",array(':id'=>$id));

		if ($_W['ispost']) 
		{


		    if (empty($_GPC['levelname'])) {

		    	show_json(-1,'名称不能为空!');
		    }
		    // echo '<pre>';
		    //     print_r($_GPC);
		    // echo '</pre>';
		    // exit;
		    $data = array(

		        'uniacid' => $_W['uniacid'],

		        'level' => intval($_GPC['level']),

		        'levelname' => trim($_GPC['levelname']),

		        'jy' => intval($_GPC['jy']),
		        
		        'jl' => intval($_GPC['jl']),
		        
		        'bili' => $_GPC['bili'],
		       
		        'img' => $_GPC['img']

		    );

		    if (!empty($id)) {

		        pdo_update('wx_shop_game_blevel', $data, array(

		            'id' => $id,

		            'uniacid' => $_W['uniacid']

		        ));

		        // plog('game.level.edit', "修改会员等级 ID: {$id}");

		    } else {

		        pdo_insert('wx_shop_game_blevel', $data);

		        $id = pdo_insertid();

		        // plog('game.level.add', "添加会员等级 ID: {$id}");
		    }

	
			show_json(1, array('url' => webUrl('game/blevel')));
		}
		
		include $this->template();
	}
}

?>
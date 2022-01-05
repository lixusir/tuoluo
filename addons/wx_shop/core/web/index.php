<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Index_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		if (empty($_W['shopversion'])) 
		{
			header('location:' . webUrl('shop'));
			exit();
		}
		$shop_data = m('common')->getSysset('shop');
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		if ($merch_plugin && $merch_data['is_openmerch']) 
		{
			$is_openmerch = 1;
		}
		else 
		{
			$is_openmerch = 0;
		}
		$hascommission = false;
		if (p('commission')) 
		{
			$hascommission = 0 < intval($_W['shopset']['commission']['level']);
		}
		$ordercol = 6;
		if (cv('goods') && cv('order')) 
		{
			$ordercol = 6;
		}
		else 
		{
			if (cv('goods') && !(cv('order'))) 
			{
				$ordercol = 12;
			}
			else 
			{
				if (cv('order') && !(cv('goods'))) 
				{
					$ordercol = 12;
				}
				else 
				{
					$ordercol = 0;
				}
			}
		}
		$pluginnum = m('plugin')->getCount();
		$no_left = true;
		
		$set = pdo_fetch('select dzp_bili,dzp_zong,yx_bili,yx_zong from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));

		//市级代理
		$shi_dai = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and isaagent=1 and aagenttype=2',array(':uniacid'=>$_W['uniacid'])); 

		$qu_dai = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and isaagent=1 and aagenttype=3',array(':uniacid'=>$_W['uniacid'])); 


		$mem_total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid'])); 


		$sn_num = m('game')->get_sn();

		// //神鸟用户
		// for ($i=0; $i < 10; $i++) { 
			
		// 	$sn_numa = pdo_fetchall('select id from ' . tablename('wx_shop_game'.$i) . ' where uniacid=:uniacid and goodsid=46 group by uid',array(':uniacid'=>$_W['uniacid']));

		// 	$sn_num += count($sn_numa);

		// }


		//广告收益
		$gg_sy = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_video') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));


		// echo "<pre>";
		// 	print_r($gg_sy);
		// echo "</pre>";
		// exit;

		$sy_sql = ' uniacid=:uniacid and status=1';

		$sy = pdo_fetchall('select * from ( ' . alltable($sy_sql,'wx_shop_game_redlog') . ' ) log',array(':uniacid'=>$_W['uniacid']));

		//活跃奖励
		$fx_money = 0;

		//有鸟
		$fh_money = 0;
		foreach ($sy as $key => $value) {
			$fx_money += $value['fx_money'];
			$fh_money += $value['fh_money'];
		}

		//大转盘发放奖金数量
		$dzp_ff = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid  and type=4 and status=3',array(':uniacid'=>$_W['uniacid']));

		//大转盘
		$dzp_zong = $gg_sy * ($set['dzp_bili'] /100) + $set['dzp_zong'] - $dzp_ff;

		//合成发放红包
		$yx_ff = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid  and type=13 and status=3',array(':uniacid'=>$_W['uniacid']));
		
		// echo "<pre>";
		// 	print_r($yx_ff);
		// echo "</pre>";

		$yx_zong = $gg_sy * ($set['yx_bili'] /100) + $set['yx_zong'] - $yx_ff;

		$cz_id = pdo_getcolumn('wx_shop_perm_user', array('uid' => $_W['user']['uid']), 'cz_id');

		//昨天
		$z_stime = mktime(0,0,0,date("m"),date("d")-1,date("Y"));

		//昨天23点
		$z_etime = mktime(23,59,59,date("m"),date("d")-1,date("Y"));

		$zr_member = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and createtime>=:z_stime and createtime<=:z_etime',array(':uniacid'=>$_W['uniacid'],':z_stime'=>$z_stime,':z_etime'=>$z_etime));
		// echo "<pre>";
		// 	print_r($zr_member);
		// echo "</pre>";
		// exit;
		//今天
		$j_stime = mktime(0,0,0,date("m"),date("d"),date("Y"));

		//今天23点
		$j_etime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		$jr_member = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and createtime>=:j_stime and createtime<=:j_etime',array(':uniacid'=>$_W['uniacid'],':j_stime'=>$j_stime,':j_etime'=>$j_etime));


		//代理记录
		$params = array(':uniacid' => $_W['uniacid']);
		
		if(!empty($cz_id)) {

			$abonus_member = m('game')->getAbonus($cz_id);

			$sql1 = 'select sum(money) from ' . tablename('wx_shop_game_abonus') . ' where uniacid=:uniacid and  uid in ('.$abonus_member['member_str'].')';


			$zong = pdo_fetchcolumn($sql1,array(':uniacid'=>$_W['uniacid']));
			// echo "<pre>";
			// 	print_r($zong);
			// echo "</pre>";

			//昨日

			$sql2 = 'select sum(money) from ' . tablename('wx_shop_game_abonus') . ' where uniacid=:uniacid and  uid in ('.$abonus_member['member_str'].') and time>=:z_stime and time<=:z_etime';

			$z_zong = pdo_fetchcolumn($sql2,array(':uniacid'=>$_W['uniacid'],':z_stime'=>$z_stime,':z_etime'=>$z_etime));

			$z_zong = empty($z_zong)?0:$z_zong;
			// echo "<pre>";
			// 	print_r($z_zong);
			// echo "</pre>";

			$sql3 = 'select sum(money) from ' . tablename('wx_shop_game_abonus') . ' where uniacid=:uniacid and  uid in ('.$abonus_member['member_str'].') and time>=:j_stime and time<=:j_etime';

			$j_zong = pdo_fetchcolumn($sql3,array(':uniacid'=>$_W['uniacid'],':j_stime'=>$j_stime,':j_etime'=>$j_etime));

			$j_zong = empty($j_zong)?0:$j_zong;


			// echo "<pre>";
			// 	print_r($j_zong);
			// echo "</pre>";
		}
		// exit;
		// $cz_id = pdo_getcolumn('wx_shop_perm_user', array('uid' => $_W['user']['uid']), 'cz_id');

		include $this->template();
	}
	public function searchlist() 
	{
		global $_W;
		global $_GPC;
		$return_arr = array();
		$menu = m('system')->getSubMenus(true, true);
		$keyword = trim($_GPC['keyword']);
		if (empty($keyword) || empty($menu)) 
		{
			show_json(1, array('menu' => $return_arr));
		}
		foreach ($menu as $index => $item ) 
		{
			if (strexists($item['title'], $keyword) || strexists($item['desc'], $keyword) || strexists($item['keywords'], $keyword) || strexists($item['topsubtitle'], $keyword)) 
			{
				if (cv($item['route'])) 
				{
					$return_arr[] = $item;
				}
			}
		}
		show_json(1, array('menu' => $return_arr));
	}
	public function search() 
	{
		global $_W;
		global $_GPC;
		$keyword = trim($_GPC['keyword']);
		$list = array();
		$history = $_GPC['history_search'];
		if (empty($history)) 
		{
			$history = array();
		}
		else 
		{
			$history = htmlspecialchars_decode($history);
			$history = json_decode($history, true);
		}
		if (!(empty($keyword))) 
		{
			$submenu = m('system')->getSubMenus(true, true);
			if (!(empty($submenu))) 
			{
				foreach ($submenu as $index => $submenu_item ) 
				{
					$top = $submenu_item['top'];
					if (strexists($submenu_item['title'], $keyword) || strexists($submenu_item['desc'], $keyword) || strexists($submenu_item['keywords'], $keyword) || strexists($submenu_item['topsubtitle'], $keyword)) 
					{
						if (cv($submenu_item['route'])) 
						{
							if (!(is_array($list[$top]))) 
							{
								$title = ((!(empty($submenu_item['topsubtitle'])) ? $submenu_item['topsubtitle'] : $submenu_item['title']));
								if (strexists($title, $keyword)) 
								{
									$title = str_replace($keyword, '<b>' . $keyword . '</b>', $title);
								}
								$list[$top] = array( 'title' => $title, 'items' => array() );
							}
							if (strexists($submenu_item['title'], $keyword)) 
							{
								$submenu_item['title'] = str_replace($keyword, '<b>' . $keyword . '</b>', $submenu_item['title']);
							}
							if (strexists($submenu_item['desc'], $keyword)) 
							{
								$submenu_item['desc'] = str_replace($keyword, '<b>' . $keyword . '</b>', $submenu_item['desc']);
							}
							$list[$top]['items'][] = $submenu_item;
						}
					}
				}
			}
			if (empty($history)) 
			{
				$history_new = array($keyword);
			}
			else 
			{
				$history_new = $history;
				foreach ($history_new as $index => $key ) 
				{
					if ($key == $keyword) 
					{
						unset($history_new[$index]);
					}
				}
				$history_new = array_merge(array($keyword), $history_new);
				$history_new = array_slice($history_new, 0, 20);
			}
			isetcookie('history_search', json_encode($history_new), 7 * 86400);
			$history = $history_new;
		}
		include $this->template();
	}
	public function clearhistory() 
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) 
		{
			$type = intval($_GPC['type']);
			if (empty($type)) 
			{
				isetcookie('history_url', '', -7 * 86400);
			}
			else 
			{
				isetcookie('history_search', '', -7 * 86400);
			}
		}
		show_json(1);
	}
	public function switchversion() 
	{
		global $_W;
		global $_GPC;
		$route = trim($_GPC['route']);
		$id = intval($_GPC['id']);
		$set = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_version') . ' WHERE uid=:uid AND `type`=0', array(':uid' => $_W['uid']));
		// xiaojie 修改 2018/06/29
		// $data = array('version' => (!(empty($_W['shopversion'])) ? 0 : 1));
		 $data = array('version' => 1);
		if (empty($set)) 
		{
			$data['uid'] = $_W['uid'];
			pdo_insert('wx_shop_version', $data);
		}
		else 
		{
			pdo_update('wx_shop_version', $data, array('id' => $set['id']));
		}
		$params = array();
		if (!(empty($id))) 
		{
			$params['id'] = $id;
		}
		load()->model('cache');
		cache_clean();
		cache_build_template();
		header('location: ' . webUrl($route, $params));
		exit();
	}
}


function alltable($condition,$tablename){

	for ($i=0; $i < 10; $i++) { 
		if($i == 9) {
			$sql .= 'select * from ' . tablename($tablename.$i) . ' where '.$condition;

		} else {

			$sql .= 'select * from ' . tablename($tablename.$i) . ' where '.$condition.' union all ';

		}
	}


	return $sql;
	// echo '<pre>';
	//     print_r($sql);
	// echo '</pre>';
	// exit;

}
?>
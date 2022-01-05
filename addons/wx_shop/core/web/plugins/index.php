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
		$category = m('plugin')->getList(1);
		//兑换中心
		// 快速购买
		// 游戏营销
		// 任务中心 整合
		$new_biz = array();
		$new_sale = array();
		foreach ($category['biz']['plugins'] as $k => $v) {
			//if($k<7 || $k==9 || $k==10){ 这有啥用啊
				if ($k <> 7) {
					$new_biz[] = $v;
				}
			//}
		}
		$new_biz[7]['name'] = '营销中心';
		$category['biz']['plugins'] = $new_biz;
		foreach ($category['sale']['plugins'] as $k => $v) {
			//if($k<>1){ 这有啥用啊
				$new_sale[] = $v;
			//}
		}
		$category['sale']['plugins'] = $new_sale;
		
		$apps = false;
		if (($_W['role'] == 'founder') || empty($_W['role'])) 
		{
			$apps = true;
		}
		$filename = '../addons/wx_shop/core/model/grant.php';
		if (file_exists($filename)) 
		{
			$setting = pdo_fetch('select * from ' . tablename('wx_shop_system_grant_setting') . ' where id = 1 limit 1 ');
			$permPlugin = false;
			if ($setting['condition_type'] == 0) 
			{
				$permPlugin = true;
			}
			else if ($setting['condition_type'] == 1) 
			{
				$total = m('goods')->getTotals();
				if ($setting['total'] <= $total['sale']) 
				{
					$permPlugin = true;
				}
			}
			else if ($setting['condition_type'] == 2) 
			{
				$price = pdo_fetch('select sum(price) as price from ' . tablename('wx_shop_order') . ' where uniacid = ' . $_W['uniacid'] . ' and status = 3 ');
				if ($setting['price'] <= $price['price']) 
				{
					$permPlugin = true;
				}
			}
			else if ($setting['condition_type'] == 3) 
			{
				$time = floor((time() - $_W['user']['joindate']) / 86400);
				if ($setting['day'] <= $time) 
				{
					$permPlugin = true;
				}
			}
		}
		if (p('grant')) 
		{
			$pluginsetting = pdo_fetch('select adv from ' . tablename('wx_shop_system_plugingrant_setting') . ' where 1 = 1 limit 1 ');
		}
		include $this->template();
	}

	public function check_plugin()
	{
		global $_W;
		global $_GPC;
		$plugin = trim($_GPC['plugin']);

		$acid = pdo_fetch('SELECT acid,uniacid FROM ' . tablename('account_wechats') . ' WHERE uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
		$item = pdo_fetch('select plugins from ' . tablename('wx_shop_perm_plugin') . ' where acid=:acid limit 1', array(':acid' => $acid['acid']));
		$status = false;
		if ($_W['role'] == 'founder' or empty($_W['role'])) {
			$status = true;
		}
		if (strstr($item['plugins'],$plugin) || $status) {
			$url = webUrl($plugin);
			show_json(1,$url);
		} else {
			show_json(0);
		}
	}
}
?>
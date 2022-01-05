<?php
class Map_WxShopPage extends PluginMobilePage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$merchid = intval($_GPC['merchid']);
		$store = pdo_fetch('select * from ' . tablename('wx_shop_merch_user') . ' where id=:merchid and uniacid=:uniacid Limit 1', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
		$store['logo'] = tomedia($store['logo']);
		// include $this->template();
		show_json(1, array( 'store'=>$store));
	}
}
?>
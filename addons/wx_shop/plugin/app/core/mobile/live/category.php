<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Category_WxShopPage //extends PluginMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$categorys = pdo_fetchall('select * from ' . tablename('wx_shop_live_category') . ' where uniacid = ' . $uniacid . ' and enabled = 1 ');
		$shop = m('common')->getSysset('shop');
		$setting = pdo_fetch('select * from ' . tablename('wx_shop_live_setting') . ' where uniacid = :uniacid  ', array(':uniacid' => $uniacid));
		$_W['shopshare'] = array('title' => !empty($setting['share_title']) ? $setting['share_title'] : $shop['name'], 'imgUrl' => !empty($setting['share_icon']) ? tomedia($setting['share_icon']) : tomedia($shop['logo']), 'link' => !empty($setting['share_url']) ? $setting['share_url'] : mobileUrl('live', array(), true), 'desc' => !empty($setting['share_desc']) ? $setting['share_desc'] : $shop['description']);
		
		foreach($categorys as &$category){
			$category['thumb']=tomedia($category['thumb']);
		}
       show_json(1,array('categorys'=>$categorys));
		// include $this->template();
	}
}

?>
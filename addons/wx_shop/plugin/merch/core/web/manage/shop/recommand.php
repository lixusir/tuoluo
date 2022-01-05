<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require WX_SHOP_PLUGIN . 'merch/core/inc/page_merch.php';
class Recommand_WxShopPage extends MerchWebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$shop = $this->getSet('shop');
		//$shop = $_W['shopset']['shop'];

		//var_dump($shop);die;
		if ($_W['ispost']) 
		{

			$shop['indexrecommands'] = $_GPC['goodsid'];

			//m('common')->updateSysset(array('shop' => $shop));
			$this->updateSet(array('shop' => $shop));
			mplog('shop.recommand', '修改首页推荐商品设置');
			show_json(1);
		}
		$goodsids = ((isset($shop['indexrecommands']) ? implode(',', $shop['indexrecommands']) : ''));
		$goods = false;
		if (!(empty($goodsids))) 
		{
			$goods = pdo_fetchall('select id,title,thumb from ' . tablename('wx_shop_goods') . ' where id in (' . $goodsids . ') and status=1 and deleted=0 and uniacid=' . $_W['uniacid'] . ' and merchid=' . $_W['merchid'] . ' order by instr(\'' . $goodsids . '\',id)');

		}
		$goodsstyle = $shop['goodsstyle'];
		
		include $this->template();
	}
	public function setstyle() 
	{
		global $_W;
		global $_GPC;

		$shop = $this->getSet('shop');

		$shop['goodsstyle'] = intval($_GPC['goodsstyle']);
		$this->updateSet(array('shop' => $shop));
		mplog('shop.recommand', '修改手机端商品组样式');

		show_json(1, $shop);
	}
}
?>
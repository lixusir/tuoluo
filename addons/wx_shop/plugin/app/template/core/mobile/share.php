<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'app/core/page_mobile.php';
class Share_WxShopPage extends AppMobilePage
{
	public function main()
	{
		global $_GPC;
		echo '以下是分享内容: ';
		print_r($_GET);
	}
}

?>

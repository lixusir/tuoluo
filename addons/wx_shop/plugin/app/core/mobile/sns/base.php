<?php

require WX_SHOP_PLUGIN . 'app/core/page_mobile.php';
class Base_WxShopPage //extends PluginMobilePage//AppMobilePage
{	
	public $islogin = 0;
	public $model;
	public $set;

	public function __construct()
	{	
		global $_W;
		global $_GPC;
		$this->model = m('plugin')->loadModel('sns');
		// $this->set = $this->model->getSet();
		$this->islogin = empty($_GPC['openid']) ? 0 : 1;
	}

	public function getSet()
	{
		if (empty($GLOBALS['_S'][$this->pluginname])) {
			return m('common')->getPluginset($this->pluginname);
		}

		return $GLOBALS['_S'][$this->pluginname];
	}
		
}
?>
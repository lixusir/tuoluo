<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'app/core/page_mobile.php';
class Base_WxShopPage extends AppMobilePage
{
    public function __construct()
    {
        parent::__construct();
//        if(empty( m('cache')->get($_GPC['authkey']) ) ) {
////            app_error(AppError::$CommissionReg,'操作非法');
//        }
        $this->model = p('creditshop');
//        $this->set = $this->model->getSet();
    }
}

?>
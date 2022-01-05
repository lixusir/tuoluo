<?php
//set_error_handler("ExceptionHandel");
//function ExceptionHandel($errno,$message,$error_file,$error_line){
//    file_put_contents(IA_ROOT.'/data/commissionTestError.log',"\n[".date('H:i:s').']    '.$errno.':'.$message.". at :[".$error_file."]->".$error_line,FILE_APPEND);
//}
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require WX_SHOP_PLUGIN . 'app/core/page_mobile.php';
class Upgradeasyn_WxShopPage extends AppMobilePage
{
    public function main()
    {
        global $_GPC;
        if (empty($_GPC['openid'])) {
            echo 2;
            return false;
        }
        $set = $this->getSet();
        $m = m('member')->getMember($_GPC['openid']);
        if (empty($m)) {
            return false;
        }
        
        p('commission')->upgradeLevelAsyn($_GPC['openid']);
    }

    //回调用户区域代理等级计算
    public  function  abonus()
    {
        global $_GPC;
        if (empty($_GPC['openid'])) {
            return false;
        }
        $m = m('member')->getMember($_GPC['openid']);
        if (empty($m)) {
            return false;
        }
        p('abonus')->upgradeLevelAsyn($_GPC['openid']);
    }

    //回调用户股东分红等级计算
    public  function  globonus()
    {
        global $_GPC;
        if (empty($_GPC['openid'])) {
            return false;
        }
        $m = m('member')->getMember($_GPC['openid']);
        if (empty($m)) {
            return false;
        }
        p('globonus')->upgradeLevelAsyn($_GPC['openid']);
    }

    //代理分红修改begin
    //回调用户代理分红等级计算
    public  function  weightbonus()
    {
        global $_GPC;
        if (empty($_GPC['openid'])) {
            return false;
        }
        $m = m('member')->getMember($_GPC['openid']);
        if (empty($m)) {
            return false;
        }
        p('weightbonus')->upgradeLevelAsyn($_GPC['openid']);
    }
    //代理分红修改end

    //代理分红修改begin
    //订单回调用户代理分红创建
    public  function  orderWeightbonus()
    {
        global $_GPC;
        if (empty($_GPC['openid']) || empty($_GPC['orderid'])) {
            return false;
        }
        $m = m('member')->getMember($_GPC['openid']);
        if (empty($m)) {
            return false;
        }
        p('weightbonus')->createOrderWeightbonus($_GPC['openid'],$_GPC['orderid']);
    }
    //代理分红修改end

}
?>
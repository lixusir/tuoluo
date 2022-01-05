<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require IA_ROOT . '/addons/wx_shop/defines.php';
require WX_SHOP_INC . '/plugin_processor.php';
class MerchProcessor extends PluginProcessor
{
	public function __construct() 
	{
		parent::__construct('creditshop');
		$this->sessionkey = WX_SHOP_PREFIX . 'order_wechat_verify_info';
		$this->codekey = WX_SHOP_PREFIX . 'order_wechat_verify_code';
	}

	public function respond($obj = NULL) 
	{
		global $_W;
		$message = $obj->message;
		//$openid = $obj->message['from'];
		$content = $obj->message['content'];
		//$msgtype = strtolower($message['msgtype']);
		//$event = strtolower($message['event']);
		@session_start();

		//分割$content
        $content_array = explode('_',$content);
        if(empty($content_array[1]) && empty($content_array[2]) && empty($content_array[3])){
            //$this->responseEmpty();
            return $obj->respText('欢迎光临');
        }

        //欢迎语，描述改成可编辑
        $merch = pdo_get('wx_shop_merch_account', array('id' => $content_array[2]), array('username'));
        $platform = pdo_get('wx_shop_platform', array('id' => $content_array[3]), array('name'));
        //http://ytxcx1.iiio.top/app/index.php?i='.$content_array[1].'&c=entry&m=wx_shop&do=mobile&r=goods&merchid='.$content_array[2]
        $news = array(
            'title'=>'欢迎光临',
            'description'=>'您已进入'.$merch['username'].'，台位：'.$platform['name'].'，请点击点餐！',
            'picurl'=>'https://up.enterdesk.com/edpic_source/a5/83/4b/a5834b56ad7ba8559b9d125e2f9476bb.jpg',
            'url'=>$_W['siteroot'].'/app/index.php?i='.$content_array[1].'&c=entry&m=wx_shop&do=mobile&r=goods&merchid='.$content_array[2]
         );

        return $obj->respNews($news);
	}

	private function responseEmpty() 
	{
		ob_clean();
		ob_start();
		echo '';
		ob_flush();
		ob_end_flush();
		exit(0);
	}
}
?>
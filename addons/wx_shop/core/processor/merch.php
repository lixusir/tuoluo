<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require IA_ROOT . '/addons/wx_shop/defines.php';
require WX_SHOP_INC . 'com_processor.php';
class MerchProcessor extends ComProcessor
{
	public function __construct() 
	{
		parent::__construct('merch');
	}
	public function respond($obj = NULL) 
	{
		global $_W;
		$message = $obj->message;
		$content = $obj->message['content'];
		$msgtype = strtolower($message['msgtype']);
		$event = strtolower($message['event']);
		if (($msgtype == 'text') || ($event == 'click')) 
		{
			//return $this->respondText($obj);
		}
		//return $this->responseEmpty();
       // return $this->respText(json_encode($obj->message));
        return $obj->respText($content);
	}
	private function responseEmpty() 
	{
		ob_clean();
		ob_start();
		echo 'ssss';
		ob_flush();
		ob_end_flush();
		exit(0);
	}

	//逻辑写在下方
	public function respondText($obj)
	{
		global $_W;
		@session_start();
		$content = $obj->message['content'];
		$openid = $obj->message['from'];
		$member = m('member')->getMember($openid);
		$couponkey = $content;
		if (isset($_SESSION['wx_shop_coupon_key'])) 
		{
			$couponkey = $_SESSION['wx_shop_coupon_key'];
		}
		else 
		{
			$_SESSION['wx_shop_coupon_key'] = $content;
		}
		$coupon = pdo_fetch('select id,couponname,pwdkey2,pwdask,pwdsuc,pwdfail,pwdfull,pwdtimes,pwdurl,pwdwords,pwdown,pwdexit,pwdexitstr from ' . tablename('wx_shop_coupon') . ' where pwdkey2=:pwdkey2 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':pwdkey2' => $couponkey));
		$words = explode(',', $coupon['pwdwords']);
		if (empty($coupon)) 
		{
			$obj->endContext();
			unset($_SESSION['wx_shop_coupon_key']);
			return $this->responseEmpty();
		}
		if (!($obj->inContext)) 
		{
			$guessok = pdo_fetch('select id,times from ' . tablename('wx_shop_coupon_guess') . ' where couponid=:couponid and openid=:openid and pwdkey=:pwdkey and ok=1 and uniacid=:uniacid limit 1 ', array(':couponid' => $coupon['id'], ':openid' => $openid, ':pwdkey' => $coupon['pwdkey2'], ':uniacid' => $_W['uniacid']));
			if (!(empty($guessok))) 
			{
				$guess = $this->getGuess($coupon, $openid);
				$coupon = $this->replaceCoupon($coupon, $member, $guess['times'], $guess['lasttimes']);
				$obj->endContext();
				unset($_SESSION['wx_shop_coupon_key']);
				return $obj->respText($coupon['pwdown']);
			}
			$guess = $this->getGuess($coupon, $openid);
			$coupon = $this->replaceCoupon($coupon, $member, $guess['times'], $guess['lasttimes']);
			if ($guess['lasttimes'] <= 0) 
			{
				$obj->endContext();
				unset($_SESSION['wx_shop_coupon_key']);
				return $obj->respText($coupon['pwdfull']);
			}
			$obj->beginContext();
			return $obj->respText($coupon['pwdask']);
		}
		if (($content == $coupon['pwdexit']) || ($content == '0')) 
		{
			unset($_SESSION['wx_shop_coupon_key']);
			$obj->endContext();
			$guess = $this->getGuess($coupon, $openid);
			$coupon = $this->replaceCoupon($coupon, $member, $guess['times'], $guess['lasttimes']);
			return $obj->respText($coupon['pwdexitstr']);
		}
		$guess = pdo_fetch('select id,times from ' . tablename('wx_shop_coupon_guess') . ' where couponid=:couponid and openid=:openid and pwdkey=:pwdkey and uniacid=:uniacid limit 1 ', array(':couponid' => $coupon['id'], ':openid' => $openid, ':pwdkey' => $coupon['pwdkey2'], ':uniacid' => $_W['uniacid']));
		$ok = in_array($content, $words);
		if (empty($guess)) 
		{
			$guess = array('uniacid' => $_W['uniacid'], 'couponid' => $coupon['id'], 'openid' => $openid, 'times' => 1, 'pwdkey' => $coupon['pwdkey2'], 'ok' => ($ok ? 1 : 0));
			pdo_insert('wx_shop_coupon_guess', $guess);
		}
		else 
		{
			pdo_update('wx_shop_coupon_guess', array('times' => $guess['times'] + 1, 'ok' => ($ok ? 1 : 0)), array('id' => $guess['id']));
		}
		$time = time();
		if ($ok) 
		{
			$log = array('uniacid' => $_W['uniacid'], 'openid' => $openid, 'logno' => m('common')->createNO('coupon_log', 'logno', 'CC'), 'couponid' => $coupon['id'], 'status' => 1, 'paystatus' => -1, 'creditstatus' => -1, 'createtime' => $time, 'getfrom' => 13);
			pdo_insert('wx_shop_coupon_log', $log);
			$data = array('uniacid' => $_W['uniacid'], 'openid' => $openid, 'couponid' => $coupon['id'], 'gettype' => 13, 'gettime' => $time);
			pdo_insert('wx_shop_coupon_data', $data);
			unset($_SESSION['wx_shop_coupon_key']);
			$obj->endContext();
			$set = m('common')->getPluginset('coupon');
			$c = $this->model->getCoupon($coupon['id']);
			$this->model->sendMessage($c, 1, $member);
			$guess = $this->getGuess($coupon, $openid);
			$coupon = $this->replaceCoupon($coupon, $member, $guess['times'], $guess['lasttimes']);
			return $obj->respText($coupon['pwdsuc']);
		}
		$guess = $this->getGuess($coupon, $openid);
		$coupon = $this->replaceCoupon($coupon, $member, $guess['times'], $guess['lasttimes']);
		if ($guess['lasttimes'] <= 0) 
		{
			$obj->endContext();
			unset($_SESSION['wx_shop_coupon_key']);
			return $obj->respText($coupon['pwdfull']);
		}
		return $obj->respText($coupon['pwdfail']);
	}
}
?>
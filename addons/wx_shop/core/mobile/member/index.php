<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}

class Index_WxShopPage extends MobileLoginPage
{
	public $pluginstr;
	
	public function __construct()
	{

		global $_W;
		parent::__construct();

		$merch_data = m('common')->getPluginset('merch');
		$nickname = pdo_fetch('select nickname from '.tablename('wx_shop_member').' where openid=:openid', array(':openid' => $_W['openid']));
		if (empty($nickname) && is_weixin()) {
			$acc = WeiXinAccount::create();
			$userinfo = $acc->fansQueryInfo($_W['openid']);
			$a = pdo_update('wx_shop_member', array('nickname' => $nickname), array('openid' => $_W['openid']));
		}
		$this->pluginstr = pdo_fetchcolumn('select  plugins from ' . tablename('wx_shop_perm_plugin') . ' where acid=:acid limit 1', array(':acid' => $_W['uniacid']));
	}

	public function main() 
	{
		global $_W;
		global $_GPC;
		
		 
		$this->diypage('member');
		$member = m('member')->getMember($_W['openid'], true);

		$redis = m('game')->getRedis();


		// exit;

		// echo '<pre>';
		//     print_r(IA_ROOT);
		// echo '</pre>';

		// $shang = pdo_fetch('select id,goodsType,level,income,gl,goodsname from ' . tablename('wx_shop_game_goods') . 'where uniacid=:uniacid  order by level desc limit 1,1',array(':uniacid'=>$_W['uniacid']));

		// echo '<pre>';
		//     print_r($shang);
		// echo '</pre>';
		// exit;

		// m('game')->setMoney($member['id'],200,'购买商品','购买游戏商品消费');
		// echo '<pre>';
		//     print_r($member);
		// echo '</pre>';

		// $res = m('game')->randFloat(10,0.5);
		$res = m('game')->getSp1($member['id']);
		// echo '<pre>';
		//     print_r($res);
		// echo '</pre>';

		// $res1 = $redis->lrange("mess".$_W['uniacid'],0,20);

		// echo '<pre>';
		//     print_r($res1);
		// echo '</pre>';
		// $redis->lrem("mess".$_W['uniacid'],$key,0);

		


		// $resa = $redis->lrange("game_goods".$_W['uniacid'],0,100);

		// foreach ($resa as $key => $value) {
		// 	# code...
		// 	$redis->lrem("game_goods".$_W['uniacid'],$value);
		// 	$as = unserialize($value);
		// 	foreach ($as as $key => $val) {
		// 		# code...
		// 		echo '<pre>';
		// 		    print_r($val);
		// 		echo '</pre>';
		// 	}

		// }




		// echo '<pre>';
		//     print_r($resa);
		// echo '</pre>';



		// exit;
		// exit;

		if(empty($member['yqm'])) {
			while (1) {
				$yqm = m('member')->getYqm(6);
				$count = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and yqm=:yqm',array(':uniacid'=>$_W['uniacid'],'yqm'=>$yqm));
				if($count <= 0) {
					break;
				}
			}
			pdo_update('wx_shop_member',array('yqm'=>$yqm),array('id'=>$member['id']));

		}
		$level = m('member')->getLevel($_W['openid']);
		//积分商城
		$open_creditshop = false;
		$open_creditshop1 = p('creditshop') && $_W['shopset']['creditshop']['centeropen'];
		if ($open_creditshop1 && strstr($this->pluginstr, 'creditshop')) {
			$open_creditshop = true;
		}
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
		$merch_plugin = p('merch');
		$showMerch = false;
		$merch_data = m('common')->getPluginset('merch');
		// var_dump($merch_data['is_openmerch']);die;
		if ($merch_plugin && $merch_data['is_openmerch']) 
		{
			$statics = array('order_0' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and status=0 and (isparent=1 or (isparent=0 and parentid=0)) and paytype<>3 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'order_1' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and (status=1 or (status=0 and paytype=3)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'order_2' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and (status=2 or (status=1 and sendtype>0)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'order_4' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and refundstate=1 and isparent=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'cart' => pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('wx_shop_member_cart') . ' where uniacid=:uniacid and openid=:openid and deleted=0', $params), 'favorite' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member_favorite') . ' where uniacid=:uniacid and openid=:openid and deleted=0', $params));
			if (strstr($this->pluginstr, 'merch')) {
				$showMerch = true;
			}
		}
		else 
		{
			$statics = array('order_0' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and status=0 and isparent=0 and paytype<>3 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'order_1' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and (status=1 or (status=0 and paytype=3)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'order_2' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and (status=2 or (status=1 and sendtype>0)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'order_4' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and refundstate=1 and isparent=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params), 'cart' => pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('wx_shop_member_cart') . ' where uniacid=:uniacid and openid=:openid and deleted=0 and selected = 1', $params), 'favorite' => ($merch_plugin && $merch_data['is_openmerch'] ? pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member_favorite') . ' where uniacid=:uniacid and openid=:openid and deleted=0 and `type`=0', $params) : pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member_favorite') . ' where uniacid=:uniacid and openid=:openid and deleted=0', $params)));
		}
		$newstore_plugin = p('newstore');
		if ($newstore_plugin) 
		{
			$statics['norder_0'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and status=0 and isparent=0 and istrade=1 and uniacid=:uniacid', $params);
			$statics['norder_1'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and status=1 and isparent=0 and istrade=1 and refundid=0 and uniacid=:uniacid', $params);
			$statics['norder_3'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and status=3 and isparent=0 and istrade=1 and uniacid=:uniacid', $params);
			$statics['norder_4'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where openid=:openid and ismr=0 and refundstate=1 and isparent=0 and istrade=1 and uniacid=:uniacid', $params);
		}
		$hascoupon = false;
		$hascouponcenter = false;
		$plugin_coupon = com('coupon');
		if ($plugin_coupon) 
		{
			$time = time();
			$sql = 'select count(*) from ' . tablename('wx_shop_coupon_data') . ' d';
			$sql .= ' left join ' . tablename('wx_shop_coupon') . ' c on d.couponid = c.id';
			$sql .= ' where d.openid=:openid and d.uniacid=:uniacid and  d.used=0 ';
			$sql .= ' and (   (c.timelimit = 0 and ( c.timedays=0 or c.timedays*86400 + d.gettime >=unix_timestamp() ) )  or  (c.timelimit =1 and c.timestart<=' . $time . ' && c.timeend>=' . $time . ')) order by d.gettime desc';
			$statics['coupon'] = pdo_fetchcolumn($sql, array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
			$pcset = $_W['shopset']['coupon'];
			if (empty($pcset['closemember'])) 
			{
				$hascoupon = true;
			}
			if (empty($pcset['closecenter'])) 
			{
				$hascouponcenter = true;
			}
			if ($hascoupon) 
			{
				$couponnum = com('coupon')->getCanGetCouponNum($_W['merchid']);
			}
		}


		//后台控制小程序创客开关begin
		$hascommissionbonus = false;
		$plugin_commission = p('commission');
		if ($plugin_commission) 
		{	
			$hascommissionbonus1 = m('common')->getPluginset('commission');
			if ($hascommissionbonus1['isgzh'] == 1) {

				$hascommissionbonus = true;
			}
		}
		//后台控制小程序创客开关end


		$hasglobonus = false;
		$plugin_globonus = p('globonus');
		if ($plugin_globonus) 
		{	
			$plugin_globonus_set = $plugin_globonus->getSet();
			$hasglobonus1 = !(empty($plugin_globonus_set['open'])) && !(empty($plugin_globonus_set['openmembercenter']));
			if ($hasglobonus1 && strstr($this->pluginstr,'globonus')) {
				$hasglobonus = true;
			}
		}

        //判断是否有代理分红
        /*代理分红修改begin*/
        $hasweightbonus = false;
        $plugin_weightbonus = p('weightbonus');
        if ($plugin_weightbonus)
        {
            $plugin_weightbonus_set = $plugin_weightbonus->getSet();
            $hasweightbonus1 = !(empty($plugin_weightbonus_set['open'])) && !(empty($plugin_weightbonus_set['openmembercenter']));
            if ($hasweightbonus1 && strstr($this->pluginstr,'weightbonus')) {
				$hasweightbonus = true;
			}
        }
        /*代理分红修改end*/

		/*互动直播显示*/
		$haslive = false;
		// $haslive = p('live');
		if (p('live')) 
		{
			$live_set = p('live')->getSet();
			if ($live_set && strstr($this->pluginstr, 'live'))
			{
				// $live_set = $haslive->getSet();
				$haslive = $live_set['ismember'];
			}
		}
		
		//全返管理
		$showfullback = false;
		if (strstr($this->pluginstr, 'sale.fullback')) {
			$showfullback = true;
		}

		$showtask = false;
		//任务中心
		if(p('lottery'))
		{
			$lottery = p('lottery')->getSet();
			if ($lottery['on_show_wxapp'] && strstr($this->pluginstr,'lottery')) {
				$showtask = true;
			}
		}
		$hasThreen = false;
		$hasThreen = p('threen');
		if ($hasThreen) 
		{
			$plugin_threen_set = $hasThreen->getSet();
			$hasThreen = !(empty($plugin_threen_set['open'])) && !(empty($plugin_threen_set['threencenter']));
		}
		$hasauthor = false;
		$plugin_author = p('author');
		if ($plugin_author) 
		{
			$plugin_author_set = $plugin_author->getSet();
			$hasauthor = !(empty($plugin_author_set['open'])) && !(empty($plugin_author_set['openmembercenter']));
		}
		//区域代理中心
		$hasabonus = false;
		$plugin_abonus = p('abonus');
		if ($plugin_abonus) 
		{
			$plugin_abonus_set = $plugin_abonus->getSet();
			$hasabonus1 = !(empty($plugin_abonus_set['open'])) && !(empty($plugin_abonus_set['openmembercenter']));
			if ($hasabonus1 && strstr($this->pluginstr,'abonus')) {
				$hasabonus = true;
			}
		}
		$card = m('common')->getSysset('membercard');
		$actionset = m('common')->getSysset('memberCardActivation');
		$haveverifygoods = m('verifygoods')->checkhaveverifygoods($_W['openid']);
		if (!(empty($haveverifygoods))) 
		{
			$verifygoods = m('verifygoods')->getCanUseVerifygoods($_W['openid']);
		}
		$showcard = 0;
		if (!(empty($card))) 
		{
			$membercardid = $member['membercardid'];
			if (!(empty($membercardid)) && ($card['card_id'] == $membercardid)) 
			{
				$cardtag = '查看微信会员卡信息';
				$showcard = 1;
			}
			else if (!(empty($actionset['centerget']))) 
			{
				$showcard = 1;
				$cardtag = '领取微信会员卡';
			}
		}
		$hasqa = false;
		$plugin_qa = p('qa');
		if ($plugin_qa) 
		{
			$plugin_qa_set = $plugin_qa->getSet();
			if (!(empty($plugin_qa_set['showmember']))) 
			{
				$hasqa = true;
			}
		}
		$hassign = false;
		$com_sign = p('sign');
		if ($com_sign) 
		{
			$com_sign_set = $com_sign->getSet();
			$iscenter = 0;
			if (!(empty($com_sign_set['iscenter'])) && !(empty($com_sign_set['isopen']))) 
			{
				$hassign = ((empty($_W['shopset']['trade']['credittext']) ? '积分' : $_W['shopset']['trade']['credittext']));
				$hassign .= ((empty($com_sign_set['textsign']) ? '签到' : $com_sign_set['textsign']));
				$iscenter = $com_sign_set['iscenter'];
			}
		}
		$hasLineUp = false;
		$lineUp = p('lineup');
		if ($lineUp) 
		{
			$lineUpSet = $lineUp->getSet();
			if (!(empty($lineUpSet['isopen'])) && !(empty($lineUpSet['mobile_show']))) 
			{
				$hasLineUp = true;
			}
		}
		$wapset = m('common')->getSysset('wap');
		$appset = m('common')->getSysset('app');
		$needbind = false;
		if (empty($member['mobileverify']) || empty($member['mobile'])) 
		{
			if ((empty($_W['shopset']['app']['isclose']) && !(empty($_W['shopset']['app']['openbind']))) || !(empty($_W['shopset']['wap']['open'])) || $hasThreen) 
			{
				$needbind = true;
			}
		}
		if (p('mmanage')) 
		{
			$roleuser = pdo_fetch('SELECT id, uid, username, status FROM' . tablename('wx_shop_perm_user') . 'WHERE openid=:openid AND uniacid=:uniacid AND status=1 LIMIT 1', array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
		}
		// $_W['shopshare'] = array('title' => '燕巢唐代理商：', 'imgUrl' => $_W['siteroot'].'attachment/headimg_84.jpg', 'desc' => '燕巢唐：专营源自马来西亚的纯天然传统手工燕窝!', 'link' => mobileUrl('apply/pay/peerpayshare', array('id' => $orderList['orderid']), 1));
		include $this->template();
	}
}
?>
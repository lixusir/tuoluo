<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'globonus/core/page_login_mobile.php';
class Register_WxShopPage //extends GlobonusMobileLoginPage
{
	public $set;
	public $model;

	public function __construct(){
		$this->model=m('plugin')->loadModel('globonus');
		$this->set=$this->model->getset('globonus');
		// var_dump($GLOBALS['_W']['plugin']);
	}
    
	public function loadModel($pluginname = '') 
	{
		static $_model;
		if (!($_model)) 
		{
			$modelfile = IA_ROOT . '/addons/wx_shop/plugin/' . $pluginname . '/core/model.php';
			if (is_file($modelfile)) 
			{
				$classname = ucfirst($pluginname) . 'Model';
				require_once WX_SHOP_CORE . 'inc/plugin_model.php';
				require_once $modelfile;
				$_model = new $classname($pluginname);
			}
		}
		return $_model;
	}

	public function getSet($pluginname)
		{
			// $set = parent::getSet($uniacid);
			if (empty($GLOBALS['_S'][$pluginname])) {
			    $set = m('common')->getPluginset($pluginname);
		    }else{
			    $set = $GLOBALS['_S'][$pluginname];
		    }

			$set['texts'] = array('partner' => empty($set['texts']['partner']) ? '股东' : $set['texts']['partner'], 'center' => empty($set['texts']['center']) ? '股东中心' : $set['texts']['center'], 'become' => empty($set['texts']['become']) ? '成为股东' : $set['texts']['become'], 'bonus' => empty($set['texts']['bonus']) ? '分红' : $set['texts']['bonus'], 'bonus_total' => empty($set['texts']['bonus_total']) ? '累计分红' : $set['texts']['bonus_total'], 'bonus_lock' => empty($set['texts']['bonus_lock']) ? '待结算分红' : $set['texts']['bonus_lock'], 'bonus_pay' => empty($set['texts']['bonus_lock']) ? '已结算分红' : $set['texts']['bonus_pay'], 'bonus_wait' => empty($set['texts']['bonus_wait']) ? '预计分红' : $set['texts']['bonus_wait'], 'bonus_detail' => empty($set['texts']['bonus_detail']) ? '分红明细' : $set['texts']['bonus_detail'], 'bonus_charge' => empty($set['texts']['bonus_charge']) ? '扣除提现手续费' : $set['texts']['bonus_charge']);
			return $set;
		}
        /**
     * 获取会员信息
     */
		public function getMember($openid = '')
	{
		global $_W;
		$uid = (int) $openid;
        
		if ($uid == 0) {
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

			if (empty($info)) {
				if (strexists($openid, 'sns_qq_')) {
					$openid = str_replace('sns_qq_', '', $openid);
					$condition = ' openid_qq=:openid ';
					$bindsns = 'qq';
				}
				else if (strexists($openid, 'sns_wx_')) {
					$openid = str_replace('sns_wx_', '', $openid);
					$condition = ' openid_wx=:openid ';
					$bindsns = 'wx';
				}
				else {
					if (strexists($openid, 'sns_wa_')) {
						$openid = str_replace('sns_wa_', '', $openid);
						$condition = ' openid_wa=:openid ';
						$bindsns = 'wa';
					}
				}

				if (!empty($condition)) {
					$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where ' . $condition . '  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

					if (!empty($info)) {
						$info['bindsns'] = $bindsns;
					}
				}
			}
		}
		else {
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));
		}

		if (!empty($info)) {
			if (!strexists($info['avatar'], 'http://') && !strexists($info['avatar'], 'https://')) {
				$info['avatar'] = tomedia($info['avatar']);
			}

			if ($_W['ishttps']) {
				$info['avatar'] = str_replace('http://', 'https://', $info['avatar']);
			}

			$info = $this->updateCredits($info);
		}

		return $info;
	}


	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'] = $_GPC['openid'];

		$set = set_medias($this->set, 'regbg');
		 // print_r($set);
		$member = m('member')->getMember($openid);
		 // var_dump($member);
		if (($member['ispartner'] == 1) && ($member['partnerstatus'] == 1)) {
			header('location: ' . mobileUrl('globonus'));
			exit();
		}

		if ($member['agentblack'] || $member['partnerstatus']) {
			include $this->template();
			exit();
		}

		$apply_set = array();
		$apply_set['open_protocol'] = $set['open_protocol'];

		if (empty($set['applytitle'])) {
			$apply_set['applytitle'] = '股东申请协议';
		}
		else {
			$apply_set['applytitle'] = $set['applytitle'];
		}

		$template_flag = 0;
		$diyform_plugin = p('diyform');
        // var_dump($diyform_plugin);//object
		if ($diyform_plugin) {
			$set_config = $diyform_plugin->getSet();
		    // print_r($set_config);//null
			$globonus_diyform_open = $set_config['globonus_diyform_open'];
            // var_dump($globonus_diyform_open);//exit;
			if ($globonus_diyform_open == 1) {
				$template_flag = 1;
				$diyform_id = $set_config['globonus_diyform'];

				if (!empty($diyform_id)) {
					$formInfo = $diyform_plugin->getDiyformInfo($diyform_id);
					$fields = $formInfo['fields'];
					$diyform_data = iunserializer($member['diyglobonusdata']);
					$f_data = $diyform_plugin->getDiyformData($diyform_data, $fields, $member);
				}
			}
		}
        // var_dump($_W['ispost']);//exit;
		if ($_W['ispost']) {
			if ($set['become'] != '1') {
				show_json(0, '未开启' . $set['texts']['partner'] . '注册!');
			}

			$become_check = intval($set['become_check']);
			$ret['status'] = $become_check;

			if ($template_flag == 1) {
				$memberdata = $_GPC['memberdata'];
				$insert_data = $diyform_plugin->getInsertData($fields, $memberdata);
				$data = $insert_data['data'];
				$m_data = $insert_data['m_data'];
				$mc_data = $insert_data['mc_data'];
				$m_data['diyglobonusid'] = $diyform_id;
				$m_data['diyglobonusfields'] = iserializer($fields);
				$m_data['diyglobonusdata'] = $data;
				$m_data['ispartner'] = 1;
				$m_data['partnerstatus'] = $become_check;
				$m_data['partnertime'] = $become_check == 1 ? time() : 0;
				unset($m_data['credit1']);
				unset($m_data['credit2']);
				pdo_update('wx_shop_member', $m_data, array('id' => $member['id']));

				if ($become_check == 1) {
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'partnertime' => $m_data['partnertime']), TM_GLOBONUS_BECOME);
				}

				if (!empty($member['uid'])) {
					if (!empty($mc_data)) {
						unset($mc_data['credit1']);
						unset($mc_data['credit2']);
						m('member')->mc_update($member['uid'], $mc_data);
					}
				}
			}
			else {
				$data = array('ispartner' => 1, 'partnerstatus' => $become_check, 'realname' => trim($_GPC['realname']), 'mobile' => trim($_GPC['mobile']), 'weixin' => trim($_GPC['weixin']), 'partnertime' => $become_check == 1 ? time() : 0);
				pdo_update('wx_shop_member', $data, array('id' => $member['id']));

				if (!empty($member['uid'])) {
					m('member')->mc_update($member['uid'], array('realname' => $data['realname'], 'mobile' => $data['mobile']));
				}
			}

			 show_json(1, array('check' => $become_check));
		}

		$order_status = (intval($set['become_order']) == 0 ? 1 : 3);
		$become_check = intval($set['become_check']);
		$to_check_partner = false;

		if ($set['become'] == '2') {
			$status = 1;
			$ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where uniacid=:uniacid and openid=:openid and status>=' . $order_status . ' limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

			if ($ordercount < intval($set['become_ordercount'])) {
				$status = 0;
				$order_count = number_format($ordercount, 0);
				$order_totalcount = number_format($set['become_ordercount'], 0);
			}
			else {
				$to_check_partner = true;
			}
			 show_json(1,array('set'=>$set,'order_totalcount'=>$order_totalcount,'order_count'=>$order_count));
		}
		else if ($set['become'] == '3') {
			$status = 1;
			$moneycount = pdo_fetchcolumn('select sum(goodsprice) from ' . tablename('wx_shop_order') . ' where uniacid=:uniacid and openid=:openid and status>=' . $order_status . ' limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

			if ($moneycount < floatval($set['become_moneycount'])) {
				$status = 0;
				$money_count = number_format($moneycount, 2);
				$money_totalcount = number_format($set['become_moneycount'], 2);
			}
			else {
				$to_check_partner = true;
			}
			 show_json(1,array('money_totalcount'=>$money_totalcount,'moneycount'=>$moneycount));
		}
		else {
			if ($set['become'] == 4) {
				$goods = pdo_fetch('select id,title,thumb,marketprice from' . tablename('wx_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $set['become_goodsid'], ':uniacid' => $_W['uniacid']));
				$goodscount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order_goods') . ' og ' . '  left join ' . tablename('wx_shop_order') . ' o on o.id = og.orderid' . ' where og.goodsid=:goodsid and o.openid=:openid and o.status>=' . $order_status . '  limit 1', array(':goodsid' => $set['become_goodsid'], ':openid' => $openid));

				if ($goodscount <= 0) {
					$status = 0;
					$buy_goods = $goods;
				}
				else {
					$to_check_partner = true;
					$status = 1;
				}
				show_json(1,array('buy_goods'=>$buy_goods));
			}
			 
		}
        // var_dump($to_check_partner);//exit;
		if ($to_check_partner) {
			if (empty($member['isparnter'])) {
				$data = array('ispartner' => 1, 'partnerstatus' => $become_check, 'partnertime' => time());
				$member['ispartner'] = 1;
				$member['partnerstatus'] = $become_check;
				pdo_update('wx_shop_member', $data, array('id' => $member['id']));

				if ($become_check == 1) {
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'partner' => $data['partnertime']), TM_GLOBONUS_BECOME);
				}
			}
		}
        show_json(1,array('set'=>$set,'shopname' => $_W['shopset']['shop']['name'], 'apply_set'=>$apply_set,'member'=>$member,'template_flag'=>$template_flag));
		// include $this->template();
	}

}

?>

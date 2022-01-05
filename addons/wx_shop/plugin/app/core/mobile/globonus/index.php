<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'globonus/core/page_login_mobile.php';
class Index_WxShopPage //extends GlobonusMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
		// var_dump($_W['openid']);
		 // var_dump($openid);
		// $set = $this->getSet();
		// $set = $this->getSet('globonus');
		// print_r($set);
		$member = m('member')->getMember($openid);
		// var_dump($member);
		// var_dump('1111');
		// $result = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where  uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));
		// var_dump($result);die;
		// $bonus = $this->model->getBonus($_W['openid'], array('ok', 'lock', 'total'));
		$model = $this->getModel();
		$set = $this->getSet($openid);
		// var_dump($model);
        $bonus = $model->getBonus($openid, array('ok', 'lock', 'total'));
		$levelname = (empty($set['levelname']) ? '默认等级' : $set['levelname']);
		// $level = $this->model->getLevel($_W['openid']);
		 $level = $model->getLevel($_W['openid']);
		

		if (!empty($level)) {
			$levelname = $level['levelname'];
		}

		$bonus_wait = 0;
		$year = date('Y');
		$month = intval(date('m'));
		$week = 0;

		if ($set['paytype'] == 2) {
			$ds = explode('-', date('Y-m-d'));
			$day = intval($ds[2]);
			$week = ceil($day / 7);
		}

		// $bonusall = $this->model->getBonusData($year, $month, $week, $_W['openid']);
		$bonusall = $model->getBonusData($year, $month, $week, $_W['openid']);
		 // $bonus_wait = $bonusall['partners'][0]['bonusmoney_send'];
		// $bonus_wait = $bonusall['bonusmoney_send'];
		 $bonus_wait = !empty($bonusall['partners'][0]['bonusmoney_send'])?$bonusall['partners'][0]['bonusmoney_send']:$bonusall['bonusmoney_send'];
		// var_dump($member);
		
		show_json(1,array('member'=>$member,'set'=>$set,'bonus'=>$bonus,'levelname'=>$levelname,'bonusall'=>$bonusall,'bonus_wait'=>$bonus_wait));
		// include $this->template();
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
			// return $set;
		return $set;
	}

	public function getModel(){
         $model = m('plugin')->loadModel('globonus');
		return $model;
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
				// 导入文件
				require_once WX_SHOP_CORE . 'inc/plugin_model.php';
				require_once $modelfile;
				// 创建对象
				$_model = new $classname($pluginname);
			}
		}
		return $_model;
	}
	// 获取分红
	public function getBonus($openid = '', $params = array())
		{
			global $_W;
			$ret = array();

			if (in_array('ok', $params)) {
				$ret['ok'] = pdo_fetchcolumn('select ifnull(sum(paymoney),0) from ' . tablename('wx_shop_globonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}

			if (in_array('lock', $params)) {
				$billdData = pdo_fetchall('select id from ' . tablename('wx_shop_globonus_bill') . ' where 1 and uniacid = ' . intval($_W['uniacid']));
				$id = '';

				if (!empty($billdData)) {
					$ids = array();

					foreach ($billdData as $v) {
						$ids[] = $v['id'];
					}

					$id = implode(',', $ids);
					$ret['lock'] = pdo_fetchcolumn('select ifnull(sum(paymoney),0) from ' . tablename('wx_shop_globonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid  and billid in(' . $id . ') limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
				}
			}

			$ret['total'] = $ret['ok'] + $ret['lock'];
			return $ret;
		}

		public function getBonusData($year = 0, $month = 0, $week = 0, $openid = '')
		{
			global $_W;
			$set = $this->getSet();
			if (empty($set['bonusrate']) || ($set['bonusrate'] <= 0)) {
				$set['bonusrate'] = 100;
			}

			$days = get_last_day($year, $month);
			$starttime = strtotime($year . '-' . $month . '-1');
			$endtime = strtotime($year . '-' . $month . '-' . $days);
			$settletimes = intval($set['settledays']) * 86400;
			if ((1 <= $week) && ($week <= 4)) {
				$weekdays = array();
				$i = $starttime;

				while ($i <= $endtime) {
					$ds = explode('-', date('Y-m-d', $i));
					$day = intval($ds[2]);
					$w = ceil($day / 7);

					if (4 < $w) {
						$w = 4;
					}

					if ($week == $w) {
						$weekdays[] = $i;
					}

					$i += 86400;
				}

				$starttime = $weekdays[0];
				$endtime = strtotime(date('Y-m-d', $weekdays[count($weekdays) - 1]) . ' 23:59:59');
			}
			else {
				$endtime = strtotime($year . '-' . $month . '-' . $days . ' 23:59:59');
			}

			$bill = pdo_fetch('select * from ' . tablename('wx_shop_globonus_bill') . ' where uniacid=:uniacid and `year`=:year and `month`=:month and `week`=:week limit 1', array(':uniacid' => $_W['uniacid'], ':year' => $year, ':month' => $month, ':week' => $week));
			if (!empty($bill) && empty($openid)) {
				return array('ordermoney' => round($bill['ordermoney'], 2), 'ordercount' => $bill['ordercount'], 'bonusmoney' => round($bill['bonusmoney'], 2), 'bonusordermoney' => round($bill['bonusordermoney'], 2), 'bonusrate' => round($bill['bonusrate'], 2), 'bonusmoney_send' => round($bill['bonusmoney_send'], 2), 'partnercount' => $bill['partnercount'], 'starttime' => $starttime, 'endtime' => $endtime, 'billid' => $bill['id'], 'old' => true);
			}

			$ordermoney = 0;
			$bonusordermoney = 0;
			$bonusmoney = 0;
			$pcondition = '';

			if (!empty($openid)) {
				$member = m('member')->getMember($openid);
				$pcondition = 'AND finishtime>' . $member['partnertime'];
			}

			$orders = pdo_fetchall('select id,openid,price from ' . tablename('wx_shop_order') . ' where uniacid=' . $_W['uniacid'] . ' and status=3 and isglobonus=0 and finishtime + ' . $settletimes . '>= ' . $starttime . ' and  finishtime + ' . $settletimes . '<=' . $endtime . ' ' . $pcondition, array(), 'id');
			$pcondition = '';

			if (!empty($openid)) {
				$pcondition = ' and m.openid=\'' . $openid . '\'';
			}

			$partners = pdo_fetchall('select m.id,m.openid,m.partnerlevel,l.bonus from ' . tablename('wx_shop_member') . ' m ' . '  left join ' . tablename('wx_shop_globonus_level') . ' l on l.id = m.partnerlevel ' . '  where m.uniacid=:uniacid and  m.ispartner=1 and m.partnerstatus=1 ' . $pcondition, array(':uniacid' => $_W['uniacid']));

			foreach ($partners as &$p) {
				if (empty($p['partnerlevel']) || ($p['bonus'] == NULL)) {
					$p['bonus'] = floatval($set['bonus']);
				}
			}

			unset($p);

			foreach ($orders as $o) {
				$ordermoney += $o['price'];
				$bonusordermoney += ($o['price'] * $set['bonusrate']) / 100;

				foreach ($partners as &$p) {
					if (empty($set['selfbuy'])) {
						if ($p['openid'] == $o['openid']) {
							continue;
						}
					}

					$price = ($o['price'] * $set['bonusrate']) / 100;
					!isset($p['bonusmoney']) && $p['bonusmoney'] = 0;
					$p['bonusmoney'] += floatval(($price * $p['bonus']) / 100);
				}

				unset($p);
			}

			foreach ($partners as &$p) {
				$bonusmoney_send = 0;
				$p['charge'] = 0;
				$p['chargemoney'] = 0;
				if ((floatval($set['paycharge']) <= 0) || ((floatval($set['paybegin']) <= $p['bonusmoney']) && ($p['bonusmoney'] <= floatval($set['payend'])))) {
					$bonusmoney_send += round($p['bonusmoney'], 2);
				}
				else {
					$bonusmoney_send += round($p['bonusmoney'] - (($p['bonusmoney'] * floatval($set['paycharge'])) / 100), 2);
					$p['charge'] = floatval($set['paycharge']);
					$p['chargemoney'] = round(($p['bonusmoney'] * floatval($set['paycharge'])) / 100, 2);
				}

				$p['bonusmoney_send'] = $bonusmoney_send;
				$bonusmoney += $bonusmoney_send;
			}

			unset($p);

			if ($bonusordermoney < $bonusmoney) {
				$rat = $bonusordermoney / $bonusmoney;
				$bonusmoney = 0;

				foreach ($partners as &$p) {
					$p['chargemoney'] = round($p['chargemoney'] * $rat, 2);
					$p['bonusmoney_send'] = round($p['bonusmoney_send'] * $rat, 2);
					$bonusmoney += $p['bonusmoney_send'];
				}

				unset($p);
			}

			return array('orders' => $orders, 'partners' => $partners, 'ordermoney' => round($ordermoney, 2), 'bonusordermoney' => round($bonusordermoney, 2), 'bonusrate' => round($set['bonusrate'], 2), 'ordercount' => count($orders), 'bonusmoney' => round($bonusmoney, 2), 'partnercount' => count($partners), 'starttime' => $starttime, 'endtime' => $endtime, 'old' => false);
		}
// 获取会员信息
		public function getMember($openid = '') 
	{
		global $_W;
		$uid = (int) $openid;
		if ($uid == 0) 
		{
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if (empty($info)) 
			{
				if (strexists($openid, 'sns_qq_')) 
				{
					$openid = str_replace('sns_qq_', '', $openid);
					$condition = ' openid_qq=:openid ';
					$bindsns = 'qq';
				}
				else if (strexists($openid, 'sns_wx_')) 
				{
					$openid = str_replace('sns_wx_', '', $openid);
					$condition = ' openid_wx=:openid ';
					$bindsns = 'wx';
				}
				else if (strexists($openid, 'sns_wa_')) 
				{
					$openid = str_replace('sns_wa_', '', $openid);
					$condition = ' openid_wa=:openid ';
					$bindsns = 'wa';
				}
				if (!(empty($condition))) 
				{
					$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where ' . $condition . '  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
					if (!(empty($info))) 
					{
						$info['bindsns'] = $bindsns;
					}
				}
			}
		}
		else 
		{
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));
		}
		if (!(empty($info))) 
		{
			if (!(strexists($info['avatar'], 'http://')) && !(strexists($info['avatar'], 'https://'))) 
			{
				$info['avatar'] = tomedia($info['avatar']);
			}
			if ($_W['ishttps']) 
			{
				$info['avatar'] = str_replace('http://', 'https://', $info['avatar']);
			}
				if(strpos($info['avatar'],'132132')){
				$upgrade2=array();
				$upgrade2['avatar'] = str_replace('132132', '132', $info['avatar']);
				pdo_update('wx_shop_member', $upgrade2, array('id' => $info['id']));
			}
			
			$info = $this->updateCredits($info);
		}
		return $info;
	}

   
	

	
}

?>

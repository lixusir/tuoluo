<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'abonus/core/page_login_mobile.php';
class Index_WxShopPage //extends AbonusMobileLoginPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;

		$this->model = m('plugin')->loadModel($GLOBALS['_W']['Abonus']);
		if(empty($_GPC['openid'])){
			$_GPC['openid'] = $_GPC['openid'];
		}
		// $this->set = p('abonus')->getSet();
		// var_dump($_GPC['openid']);die;
		$set = p('abonus')->getSet();
		$member = m('member')->getMember($_GPC['openid']);
		$bonus = p('abonus')->getBonus($_GPC['openid'], array('ok', 'lock', 'ok1', 'ok2', 'ok3', 'lock1', 'lock2', 'lock3', 'total', 'total1', 'total2', 'total3'));
		$levelname = ((empty($set['levelname']) ? '默认等级' : $set['levelname']));
		$level = p('abonus')->getLevel($_GPC['openid']);
		if (!(empty($level))) 
		{
			$levelname = $level['levelname'];
		}
		if ($member['aagenttype'] == 1) 
		{
			$cols = 4;
		}
		else if ($member['aagenttype'] == 2) 
		{
			$cols = 3;
		}
		else if ($member['aagenttype'] == 3) 
		{
			$cols = 2;
		}
		else 
		{
			$cols = 4;
		}
		$bonus_wait = 0;
		$year = date('Y');
		$month = intval(date('m'));
		$week = 0;
		if ($set['paytype'] == 2) 
		{
			$ds = explode('-', date('Y-m-d'));
			$day = intval($ds[2]);
			$week = ceil($day / 7);
		}
		$bonusall = p('abonus')->getBonusData($year, $month, $week, $_GPC['openid']);
		$bonus_wait1 = $bonusall['aagents'][0]['bonusmoney_send1'];
		$bonus_wait2 = $bonusall['aagents'][0]['bonusmoney_send2'];
		$bonus_wait3 = $bonusall['aagents'][0]['bonusmoney_send3'];
		$bonus_wait = $bonusall['aagents'][0]['bonusmoney_send1'] + $bonusall['aagents'][0]['bonusmoney_send2'] + $bonusall['aagents'][0]['bonusmoney_send3'];
		// include $this->template();
		// var_dump($this->set);die;
		$bonus['total'] = number_format($bonus['total'],2);
		$bonus['total1'] = number_format($bonus['total1'],2);
		$bonus['total2'] = number_format($bonus['total2'],2);
		$bonus['total3'] = number_format($bonus['total3'],2);
		$bonus['lock1'] = number_format($bonus['lock1'],2);
		$bonus['lock2'] = number_format($bonus['lock2'],2);
		$bonus['lock3'] = number_format($bonus['lock3'],2);
		$bonus['ok'] = number_format($bonus['ok'],2);
		$bonus['ok1'] = number_format($bonus['ok1'],2);
		$bonus['ok2'] = number_format($bonus['ok2'],2);
		$bonus['ok3'] = number_format($bonus['ok3'],2);
		show_json(1, 
			array(
				'member'=>$member,
				'set'=>$set, 
				'member'=>$member,
				'bonus_wait'=>$bonus_wait,
				'bonus_wait1'=>number_format($bonus_wait1,2),
				'bonus_wait2'=>number_format($bonus_wait2,2),
				'bonus_wait3'=>number_format($bonus_wait3,2),
				'bonus'=>$bonus,
				'levelname'=>$levelname,
				'cols'=>$cols,
				'thisSet'=>p('abonus')->getSet(),
			)
		);
	}
}
?>
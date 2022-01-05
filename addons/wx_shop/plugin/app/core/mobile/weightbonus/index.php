<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_WxShopPage
{
	public function main()
	{
        global $_W;
        global $_GPC;
        $openid = $_W['openid'] ? $_W['openid'] : $_GPC['openid'];
        $set = p('weightbonus')->getSet($openid);
        $member = m('member')->getMember($openid);
        $bonus = p('weightbonus')->getBonus($openid, array('ok', 'lock', 'total'));
        $levelname = (empty($set['levelname']) ? '默认等级' : $set['levelname']);
        $level = p('weightbonus')->getLevel($openid);

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

        //取出预计分红
        $bonusall = p('weightbonus')->getBonusData($year, $month, $week, $openid);
        $bonus_wait = empty($bonusall['weights'][0]['bonusmoney_send'])?0:$bonusall['weights'][0]['bonusmoney_send'];
        show_json(1,array('member'=>$member,'set'=>$set,'bonus'=>$bonus,'levelname'=>$levelname,'bonusall'=>$bonusall,'bonus_wait'=>$bonus_wait));
	}
	
}

?>

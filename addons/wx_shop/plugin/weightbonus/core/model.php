<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

define('TM_WEIGHTBONUS_PAY', 'TM_WEIGHTBONUS_PAY');
define('TM_WEIGHTBONUS_UPGRADE', 'TM_WEIGHTBONUS_UPGRADE');
define('TM_WEIGHTBONUS_BECOME', 'TM_WEIGHTBONUS_BECOME');

if (!class_exists('WeightbonusModel')) {
	class WeightbonusModel extends PluginModel
	{
		public function getSet($uniacid = 0)
		{
			$set = parent::getSet($uniacid);
			$set['texts'] = array('weight' => empty($set['texts']['weight']) ? '代理' : $set['texts']['weight'], 'center' => empty($set['texts']['center']) ? '代理中心' : $set['texts']['center'], 'become' => empty($set['texts']['become']) ? '成为代理' : $set['texts']['become'], 'bonus' => empty($set['texts']['bonus']) ? '分红' : $set['texts']['bonus'], 'bonus_total' => empty($set['texts']['bonus_total']) ? '累计分红' : $set['texts']['bonus_total'], 'bonus_lock' => empty($set['texts']['bonus_lock']) ? '待结算分红' : $set['texts']['bonus_lock'], 'bonus_pay' => empty($set['texts']['bonus_lock']) ? '已结算分红' : $set['texts']['bonus_pay'], 'bonus_wait' => empty($set['texts']['bonus_wait']) ? '预计分红' : $set['texts']['bonus_wait'], 'bonus_detail' => empty($set['texts']['bonus_detail']) ? '分红明细' : $set['texts']['bonus_detail'], 'bonus_charge' => empty($set['texts']['bonus_charge']) ? '扣除提现手续费' : $set['texts']['bonus_charge']);

			//2018-10-09修改
            $set['levelname'] = empty($set['levelname']) ? '默认等级' : $set['levelname'];
            $set['weight'] = empty($set['weight']) ? 1 : $set['weight'];
            $set['bonus'] =  empty($set['bonus']) ? 0 : $set['bonus'];

			return $set;
		}

        public function getInfo($openid, $options = NULL)
        {
            return p('commission')->getInfo($openid, $options);
        }

		/*
		 * 获取用户(代理)的推荐链
		 * @string $memberid
		 * $bool   $isweight 是否需要是代理
		 * @array  $agentids 存储用户推荐链资料
		 * @retuen $agentids
		 * */
		public function getAgentids($memberid='',$isweight = false,$agentids = array())
		{
            global $_W;
            if (empty($memberid)) {
                return null;
            }
            $set = $this->getSet();

            $member_info = m('member')->getMember($memberid);
            if(empty($member_info)){
                return $agentids;
            }

            if($isweight){
                if($member_info['isweight']==1){
                    //将代理等级等信息填入info中
                    $weightlevel = $this->getLevel($member_info['openid']);
                    //这里有个情况就是代理等级为默认
                    if(empty($weightlevel)){
                        $member_info['bonus'] = $set['bonus'];
                        $member_info['weight'] = $set['weight'];
                        $member_info['levelname'] = $set['levelname'];
                    }else{
                        $member_info['bonus'] = $weightlevel['bonus'];
                        $member_info['weight'] = $weightlevel['weight'];
                        $member_info['levelname'] = $weightlevel['levelname'];
                    }
                    $last_member_info = end($agentids);//获取上一个用户信息（用户比较代理等级权重）

                    //判断是否开启极差
                    if($set['range']==1 && $last_member_info){
                        if($last_member_info['weight'] <= $member_info['weight']){
                            array_push($agentids,$member_info);
                        }
                    }else{
                        array_push($agentids,$member_info);
                    }
                }
            }else{
                array_push($agentids,$member_info);
            }

            if($member_info['agentid']!=0){
                //递归
                return $this->getAgentids($member_info['agentid'],$isweight,$agentids);
            }else{
                return $agentids;
            }

		}

        // 代理升级(根据分红数)
        public function upgradeLevelByBonus($openid)
        {
            global  $_W;
            if (empty($openid)) {
                return false;
            }
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;
            /*异步升级*/
            return $this->upgradeLevelByMultiple($openid);
        }

        //代理升级(根据订单数)
        public function upgradeLevelByOrder($openid)
        {
            global  $_W;
            if (empty($openid)) {
                return false;
            }
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;

            /*异步升级*/
            $this->upgradeLevelByMultiple($openid);
        }

        //代理升级(根据下级数)
        public function upgradeLevelByAgent($openid)
        {
            global $_W;
            if (empty($openid)) {
                return false;
            }
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;
            /*异步升级*/
            $this->upgradeLevelByMultiple($openid);
        }


        //分销商升级(根据佣金提现数)
        public function upgradeLevelByCommissionOK($openid)
        {
            global  $_W;
            if (empty($openid)) {
                return false;
            }
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;
            /*异步升级*/
            return $this->upgradeLevelByMultiple($openid);
        }

        /**
         * @var int 递归升级上级层级
         */
        public function upgradeLevelAsyn($openid,$isselfbuy=1)
        {
            global $_W;
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            $m = m('member')->getMember($openid);

            foreach ($levels as $level) {
                if ($level['levelcondition']==2) {
                    $result = $this->upgradeLevelByAnd($m['openid'], $level,$isselfbuy);
                    if ($result == true) break;
                } else{
                    $result = $this->upgradeLevelByOr($m['openid'], $level,$isselfbuy);
                    if ($result == true) break;
                }
            }

            /*递归升级上级分销商*/
            if (!empty($m['agentid'])) {
                $this->upgradeLevelAsyn($m['agentid'],0);
            }
        }

        //满足其中一个条件即可
        public function upgradeLevelByOr($openid, $level,$isselfbuy)
        {
            global $_W;
            $set = $this->getSet();
            $leveltypes_json_toarray = iunserializer($level['leveltypes_json']);

            $m = m('member')->getMember($openid);
            if (!empty($m['weightnotupgrade'])) {
                return NULL;
            }
            $oldlevel = $newlevel = $this->getLevel($m['openid']);
            if (empty($oldlevel['id'])) {
                $oldlevel = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'bonus' => $set['bonus']);
            }

            //已发放分红总金额  11
            if (in_array('11',$leveltypes_json_toarray)) {
                $bonusmoney = $this->getBonus($openid, array('ok'));
                $newlevel11 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $bonusmoney['ok'] . ' >= bonusmoney and bonusmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel11['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel11;
                }
            }

            // 已提现佣金总金额 10
            if (in_array('10',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $commissionmoney = $info['commission_pay'];
                $newlevel10 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $commissionmoney . ' >= commissionmoney and commissionmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel10['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel10;
                }
            }

            // 自购订单金额 4
            if (in_array('4',$leveltypes_json_toarray)) {
                $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                $ordermoney = $orders['ordermoney'];
                $newlevel4 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordermoney . ' >= ordermoney2 and ordermoney2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel4['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel4;
                }
            }

            //自购订单数量 5
            if (in_array('5',$leveltypes_json_toarray)) {
                if (!isset($order)) {
                    $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                }
                $ordercount = $orders['ordercount'];
                $newlevel5 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount2 and ordercount2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel5['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel5;
                }
            }


            if (in_array('6',$leveltypes_json_toarray) || in_array('8',$leveltypes_json_toarray) || in_array('0',$leveltypes_json_toarray) || in_array('1',$leveltypes_json_toarray) || in_array('2',$leveltypes_json_toarray) || in_array('3',$leveltypes_json_toarray)) {

                $downcount = 0;
                $info = $this->getInfo($m['id'], array('ordercount3','ordercount3','ordercount3'));

                if (!empty($info['weightnotupgrade'])) {
                    return null;
                }

                //判断是否开启了自购分红，判断是否为自购id
                if (empty($set['selfbuy']) && $isselfbuy==1) {
                    return null;
                }

                //分销订单金额 0
                if (in_array('0',$leveltypes_json_toarray)) {
                    $ordermoney = $info['ordermoney3'];
                    $newlevel0 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney and ordermoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));//这里有bug
                    if (!empty($newlevel0) && $newlevel0['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel0;
                    }
                }

                //一级分销订单金额 1
                if (in_array('1',$leveltypes_json_toarray)) {
                    $ordermoney = $info['order13money'];
                    $newlevel1 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney1 and ordermoney1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel1) && $newlevel1['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel1;
                    }
                }

                //分销订单数量 2
                if (in_array('2',$leveltypes_json_toarray)) {
                    $ordercount = $info['ordercount3'];
                    $newlevel2 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount and ordercount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel2) && $newlevel2['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel2;
                    }
                }

                //一级分销订单数量 3
                if (in_array('3',$leveltypes_json_toarray)) {
                    $ordercount = $info['order13'];
                    $newlevel3 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount1 and ordercount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel3) && $newlevel3['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel3;
                    }
                }

                //下级总人数 6
                if (in_array('6',$leveltypes_json_toarray)) {
                    $downs1 = pdo_fetchall('select id from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']), 'id');
                    $downcount += count($downs1);

                    if (!empty($downs1)) {
                        $downs2 = pdo_fetchall('select id from ' . tablename('wx_shop_member') . ' where agentid in( ' . implode(',', array_keys($downs1)) . ') and uniacid=:uniacid', array(':uniacid' => $_W['uniacid']), 'id');
                        $downcount += count($downs2);

                        if (!empty($downs2)) {
                            $downs3 = pdo_fetchall('select id from ' . tablename('wx_shop_member') . ' where agentid in( ' . implode(',', array_keys($downs2)) . ') and uniacid=:uniacid', array(':uniacid' => $_W['uniacid']), 'id');
                            $downcount += count($downs3);
                        }
                    }

                    $newlevel6 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel6) && $newlevel6['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel6;
                    }
                }

                //团队总人数 8
                if (in_array('8',$leveltypes_json_toarray)) {
                    $downcount8 = $info['level1'] + $info['level2'] + $info['level3'];

                    $newlevel8 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount8 . ' >= teamcount and teamcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel8) && $newlevel8['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel8;
                    }
                }

            }

            //一级下级人数 7
            if (in_array('7',$leveltypes_json_toarray)) {
                $downcount7 = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']));
                $newlevel7 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount7 . ' >= downcount1 and downcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (!empty($newlevel7) && $newlevel7['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel7;
                }
            }

            //一级团队人数 9
            if (in_array('9',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array());

                $downcount9 = $info['level1'];
                $newlevel9 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount9 . ' >= teamcount1 and teamcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (!empty($newlevel9) && $newlevel9['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel9;
                }
            }

            if ($newlevel > $oldlevel) {
                pdo_update('wx_shop_member', array('weightlevel' => $newlevel['id']), array('id' => $m['id']));
                $this->sendMessage($m['openid'], array('nickname' => $m['nickname'], 'oldlevel' => $oldlevel, 'newlevel' => $newlevel), TM_WEIGHTBONUS_UPGRADE);
                return true;
            }
        }

        //同时满足所有条件
        public function upgradeLevelByAnd($openid, $level,$isselfbuy)
        {
            global $_W;
            $set = $this->getSet();
            $leveltypes_json_toarray = iunserializer($level['leveltypes_json']);

            $m = m('member')->getMember($openid);
            if (!empty($m['weightnotupgrade'])) {
                return false;
            }

            $oldlevel = $newlevel = $this->getLevel($m['openid']);
            if (empty($oldlevel['id'])) {
                $oldlevel = array('id' => 'default', 'levelnames' => empty($set['levelnames']) ? '默认等级' : $set['levelnames'], 'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'bonus' => $set['bonus']);
            }

            //已发放分红总金额 11
            if (in_array('11',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $bonusmoney = $this->getBonus($openid, array('ok'));
                $newlevel11 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $bonusmoney['ok'] . ' >= bonusmoney and bonusmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel11) || $oldlevel['weight'] == $newlevel11['weight']) {
                    return false;
                }
                if ($newlevel11['weight'] > $newlevel['weight']) $newlevel = $newlevel11;
            }


            //已提现佣金总金额 10
            if (in_array('10',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $commissionmoney = $info['commission_pay'];
                $newlevel10 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $commissionmoney . ' >= commissionmoney and commissionmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel10) || $oldlevel['weight'] == $newlevel10['weight']) {
                    return false;
                }
                if ($newlevel10['weight'] > $newlevel['weight']) $newlevel = $newlevel10;
            }

            //自购订单金额  4
            if (in_array('4',$leveltypes_json_toarray)) {
                $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                $ordermoney = $orders['ordermoney'];
                $newlevel4 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordermoney . ' >= ordermoney2 and ordermoney2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if (empty($newlevel4) || $oldlevel['weight'] == $newlevel4['weight']) {
                    return false;
                }
                if ($newlevel4['weight'] > $newlevel['weight']) $newlevel = $newlevel4;
            }

            //自购订单数量 5
            if (in_array('5',$leveltypes_json_toarray)) {
                if (!isset($order)) {
                    $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                }
                $ordercount = $orders['ordercount'];
                $newlevel5 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount2 and ordercount2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if (empty($newlevel5) || $oldlevel['weight'] == $newlevel5['weight']) {
                    return false;
                }
                if ($newlevel5['weight'] > $newlevel['weight']) $newlevel = $newlevel5;
            }


            if (in_array('6',$leveltypes_json_toarray) || in_array('8',$leveltypes_json_toarray) || in_array('0',$leveltypes_json_toarray) || in_array('1',$leveltypes_json_toarray) || in_array('2',$leveltypes_json_toarray) || in_array('3',$leveltypes_json_toarray)) {

                $downcount = 0;

                $info = $this->getInfo($m['id'], array('ordercount3','ordercount3','ordercount3'));

                if (!empty($info['weightnotupgrade'])) {
                    return null;
                }

                if (empty($set['selfbuy']) && $isselfbuy == 1) {
                    return null;
                }

                //分销订单金额 0
                if (in_array('0',$leveltypes_json_toarray)) {
                    $ordermoney = $info['ordermoney3'];
                    $newlevel0 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney and ordermoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel0) || $oldlevel['weight'] == $newlevel0['weight']) {
                        return null;
                    }
                    if ($newlevel0['weight'] > $newlevel['weight']) $newlevel = $newlevel0;
                }

                //一级分销订单金额  1
                if (in_array('1',$leveltypes_json_toarray)) {
                    $ordermoney = $info['order13money'];
                    $newlevel1 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney1 and ordermoney1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                    if (empty($newlevel1) || $oldlevel['weight'] == $newlevel1['weight']) {
                        return null;
                    }
                    if ($newlevel1['weight'] > $newlevel['weight']) $newlevel = $newlevel1;
                }

                //分销订单数量  2
                if (in_array('2',$leveltypes_json_toarray)) {
                    $ordercount = $info['ordercount3'];
                    $newlevel2 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount and ordercount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel2) || $oldlevel['weight'] == $newlevel2['weight']) {
                        return null;
                    }
                    if ($newlevel2['weight'] > $newlevel['weight']) $newlevel = $newlevel2;
                }

                //一级分销订单数量  3
                if (in_array('3',$leveltypes_json_toarray)) {
                    $ordercount = $info['order13'];
                    $newlevel3 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount1 and ordercount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel3) || $oldlevel['weight'] == $newlevel3['weight']) {
                        return null;
                    }
                    if ($newlevel3['weight'] > $newlevel['weight']) $newlevel = $newlevel3;
                }

                //下级总人数  6
                if (in_array('6',$leveltypes_json_toarray)) {
                    $downs1 = pdo_fetchall('select id from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']), 'id');
                    $downcount += count($downs1);

                    if (!empty($downs1)) {
                        $downs2 = pdo_fetchall('select id from ' . tablename('wx_shop_member') . ' where agentid in( ' . implode(',', array_keys($downs1)) . ') and uniacid=:uniacid', array(':uniacid' => $_W['uniacid']), 'id');
                        $downcount += count($downs2);

                        if (!empty($downs2)) {
                            $downs3 = pdo_fetchall('select id from ' . tablename('wx_shop_member') . ' where agentid in( ' . implode(',', array_keys($downs2)) . ') and uniacid=:uniacid', array(':uniacid' => $_W['uniacid']), 'id');
                            $downcount += count($downs3);
                        }
                    }

                    $newlevel6 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel6) || $oldlevel['weight'] == $newlevel6['weight']) {
                        return null;
                    }
                    if ($newlevel6['weight'] > $newlevel['weight']) $newlevel = $newlevel6;
                }

                //团队总人数  8
                if (in_array('8',$leveltypes_json_toarray)) {
                    $downcount8 = $info['level1'] + $info['level2'] + $info['level3'];
                    $newlevel8 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount8 . ' >= teamcount and teamcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel8) || $oldlevel['weight'] == $newlevel8['weight']) {
                        return null;
                    }
                    if ($newlevel8['weight'] > $newlevel['weight']) $newlevel = $newlevel8;
                }

            }

            //一级下级人数  7
            if (in_array('7',$leveltypes_json_toarray)) {
                $downcount7 = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']));
                $newlevel7 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount7 . ' >= downcount1 and downcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel7) || $oldlevel['weight'] == $newlevel7['weight']) {
                    return false;
                }
                if ($newlevel7['weight'] > $newlevel['weight']) $newlevel = $newlevel7;
            }

            //一级团队人数  9
            if (in_array('9',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array());

                $downcount9 = $info['level1'];
                $newlevel9 = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid  and ' . $downcount9 . ' >= teamcount1 and teamcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel9) || $oldlevel['weight'] == $newlevel9['weight']) {
                    return false;
                }
                if ($newlevel9['weight'] > $newlevel['weight']) $newlevel = $newlevel9;
            }

            if ($newlevel['weight'] > $oldlevel['weight']) {
                pdo_update('wx_shop_member', array('weightlevel' => $newlevel['id']), array('id' => $m['id']));
                $this->sendMessage($m['openid'], array('nickname' => $m['nickname'], 'oldlevel' => $oldlevel, 'newlevel' => $newlevel), TM_WEIGHTBONUS_UPGRADE);
                return true;
            }
        }

        /**
         * 异步升级
         * @param $openid
         */
        private function upgradeLevelByMultiple($openid)
        {
            global $_W;
            load()->func('communication');
            ihttp_request($_W['siteroot']. 'app/wx_shop_api.php?r=upgradeasyn.weightbonus&i=' . $_W['uniacid'] . '&openid=' . $openid, [], [], 0);
        }
        /*------------------------------------------*/



		/**
         * 获取所有代理等级
         * @global type $_W
         * @return type
         */
		public function getLevels($all = true, $default = false)
		{
			global $_W;

			if ($all) {
				$levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid order by weight asc', array(':uniacid' => $_W['uniacid']));
			}
			else {
				$levels = pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid and (ordermoney0 > 0 or ordermoney1  > 0 or ordermoney4 > 0 or ordercount2ordercount3 > 0 or ordercount5 > 0 or downcount6 > 0 or downcount7 > 0 or downcount8 > 0 or downcount9 > 0 or commissionmoney10 > 0 or bonusmoney11) order by weight asc', array(':uniacid' => $_W['uniacid']));
			}

			if ($default) {
				$default = array('id' => '0', 'levelname' => empty($_S['Weightbonus']['levelname']) ? '默认等级' : $_W['shopset']['Weightbonus']['levelname'],'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'bonus' => $_W['shopset']['Weightbonus']['bonus']);
				$levels = array_merge(array($default), $levels);
			}

			return $levels;
		}

		//获取分红
		public function getBonus($openid = '', $params = array())
		{
			global $_W;
			$ret = array();

			if (in_array('ok', $params)) {
				$ret['ok'] = pdo_fetchcolumn('select ifnull(sum(paymoney),0) from ' . tablename('wx_shop_weightbonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}

			if (in_array('lock', $params)) {
				$billdData = pdo_fetchall('select id from ' . tablename('wx_shop_weightbonus_bill') . ' where 1 and uniacid = ' . intval($_W['uniacid']));
				$id = '';

				if (!empty($billdData)) {
					$ids = array();

					foreach ($billdData as $v) {
						$ids[] = $v['id'];
					}

					$id = implode(',', $ids);
					$ret['lock'] = pdo_fetchcolumn('select ifnull(sum(paymoney),0) from ' . tablename('wx_shop_weightbonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid  and billid in(' . $id . ') limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

				}
                $ret['lock'] = empty($ret['lock'])?0:$ret['lock'];
			}

			$ret['total'] = $ret['ok'] + $ret['lock'];
			return $ret;
		}


        //创建订单时候生成当前代理分红订单
        public function createOrderWeightbonus($opendid='',$orderid='')
        {
            if(empty($opendid) || empty($orderid)){
                return null;
            }
            global $_W;

            $set = $this->getSet();
            if(empty($set['open']) || empty($set)){
                return null;
            }

            $orders = pdo_fetch('select * from '  . tablename('wx_shop_order') . 'O  where  O.uniacid=' . $_W['uniacid']. ' and O.isweightbonus=1 and O.id = '.$orderid, array(), 'id');

            if(empty($orders)){
                return null;
            }

            $weights_array = $this->getAgentids($orders['openid'],true); //根据订单中的openid取出上级推荐链

            if(is_array($weights_array) && !empty($weights_array)){
                foreach ($weights_array as $key => $weight){
                    $weights[] = array_elements(array('id', 'uniacid','openid','agentid','nickname','bonus','weight','weightlevel','levelname'),$weight,0);
                }
            }

            //循环用户链接
            for($weights_count=0;$weights_count<count($weights);++$weights_count)
            {
                //判断是否算上自购
                if (empty($set['selfbuy'])) {
                    if ($weights[$weights_count]['openid'] == $orders['openid']) {
                        continue;
                    }
                }

                //计算用户分红：判断是否开启了极差，第一个用户始终都为乘与自身等级的分红比例
                if($set['range']==1 && $weights_count>0){
                    //减去上一个等级的分红比例再相乘
                    !isset($weights[$weights_count]['bonusmoney']) && $weights[$weights_count]['bonusmoney'] = 0;
                    $weights[$weights_count]['bonusmoney'] += floatval(($orders['weightbonusprice'] * ($weights[$weights_count]['bonus']-$weights[$weights_count-1]['bonus'])) / 100);
                }else{
                    !isset($weights[$weights_count]['bonusmoney']) && $weights[$weights_count]['bonusmoney'] = 0;
                    $weights[$weights_count]['bonusmoney'] += floatval(($orders['weightbonusprice'] * $weights[$weights_count]['bonus']) / 100);
                }

                $weightlist[]=$weights[$weights_count];
            }

            //将下单时的自购分红参数与极差存库
            $data = array('uniacid'=>$_W['uniacid'],'orderid'=>$orderid,'weightbonus_json'=>iserializer($weightlist),'weightbonus_range'=>$set['range'],'weightbonus_selfbuy'=>$set['selfbuy']);

            pdo_insert('wx_shop_weightbonus_order', $data);
        }


		/**
         * 消息通知
         * @param type $message_type
         * @param type $openid
         * @return type
         */
		public function sendMessage($openid = '', $data = array(), $message_type = '')
		{
			global $_W;
			global $_GPC;
			$set = $this->getSet();
			$tm = $set['tm'];
			$templateid = $tm['templateid'];
			$member = m('member')->getMember($openid);
			$usernotice = unserialize($member['noticeset']);

			if (!is_array($usernotice)) {
				$usernotice = array();
			}

			if (($message_type == TM_WEIGHTBONUS_PAY) && empty($usernotice['Weightbonus_pay'])) {
				$message = $tm['pay'];

				if (empty($message)) {
					return false;
				}

				$message = str_replace('[昵称]', $member['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', time()), $message);
				$message = str_replace('[金额]', $data['money'], $message);
				$message = str_replace('[打款方式]', $data['type'], $message);
				$msg = array(
					'keyword1' => array('value' => !empty($tm['paytitle']) ? $tm['paytitle'] : '分红发放通知', 'color' => '#73a68d'),
					'keyword2' => array('value' => $message, 'color' => '#73a68d')
					);
				return $this->sendNotice($openid, $tm, 'pay_advanced', $data, $member, $msg);
			}

			if (($message_type == TM_WEIGHTBONUS_UPGRADE) && empty($usernotice['Weightbonus_upgrade'])) {
				$message = $tm['upgrade'];

				if (empty($message)) {
					return false;
				}

				$message = str_replace('[昵称]', $member['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', time()), $message);
				$message = str_replace('[旧等级]', $data['oldlevel']['levelname'], $message);
				$message = str_replace('[旧分红比例]', $data['oldlevel']['bonus'] . '%', $message);
				$message = str_replace('[新等级]', $data['newlevel']['levelname'], $message);
				$message = str_replace('[新分红比例]', $data['newlevel']['bonus'] . '%', $message);
				$msg = array(
					'keyword1' => array('value' => !empty($tm['upgradetitle']) ? $tm['upgradetitle'] : '代理等级升级通知', 'color' => '#73a68d'),
					'keyword2' => array('value' => $message, 'color' => '#73a68d')
					);
				return $this->sendNotice($openid, $tm, 'upgrade_advanced', $data, $member, $msg);
			}

			if (($message_type == TM_WEIGHTBONUS_BECOME) && empty($usernotice['Weightbonus_become'])) {
				$message = $tm['become'];

				if (empty($message)) {
					return false;
				}

				$message = str_replace('[昵称]', $data['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', $data['weighttime']), $message);
				$msg = array(
					'keyword1' => array('value' => !empty($tm['becometitle']) ? $tm['becometitle'] : '成为代理通知', 'color' => '#73a68d'),
					'keyword2' => array('value' => $message, 'color' => '#73a68d')
					);
				return $this->sendNotice($openid, $tm, 'become_advanced', $data, $member, $msg);
			}
		}

		protected function sendNotice($touser, $tm, $tag, $datas, $member, $msg)
		{
			global $_W;
			if (!empty($tm['is_advanced']) && !empty($tm[$tag])) {
				$advanced_template = pdo_fetch('select * from ' . tablename('wx_shop_member_message_template') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $tm[$tag], ':uniacid' => $_W['uniacid']));

				if (!empty($advanced_template)) {
					$url = (!empty($advanced_template['url']) ? $this->replaceTemplate($advanced_template['url'], $tag, $datas, $member) : '');
					$advanced_message = array(
						'first'  => array('value' => $this->replaceTemplate($advanced_template['first'], $tag, $datas, $member), 'color' => $advanced_template['firstcolor']),
						'remark' => array('value' => $this->replaceTemplate($advanced_template['remark'], $tag, $datas, $member), 'color' => $advanced_template['remarkcolor'])
						);
					$data = iunserializer($advanced_template['data']);

					foreach ($data as $d) {
						$advanced_message[$d['keywords']] = array('value' => $this->replaceTemplate($d['value'], $tag, $datas, $member), 'color' => $d['color']);
					}

					if (!empty($advanced_template['template_id'])) {
						m('message')->sendTplNotice($touser, $advanced_template['template_id'], $advanced_message);
					}
					else {
						m('message')->sendCustomNotice($touser, $advanced_message);
					}
				}
			}
			else if (!empty($tm['templateid'])) {
				m('message')->sendTplNotice($touser, $tm['templateid'], $msg);
			}
			else {
				m('message')->sendCustomNotice($touser, $msg);
			}

			return true;
		}

		protected function replaceTemplate($str, $tag, $data, $member)
		{
			$arr = array('[昵称]' => $member['nickname'], '[时间]' => date('Y-m-d H:i:s', time()), '[金额]' => !empty($data['bonus']) ? $data['bonus'] : '', '[提现方式]' => !empty($data['type']) ? $data['type'] : '', '[旧等级]' => !empty($data['oldlevel']['levelname']) ? $data['oldlevel']['levelname'] : '', '[旧等级分红比例]' => !empty($data['oldlevel']['bonus']) ? $data['oldlevel']['bonus'] . '%' : '', '[新等级]' => !empty($data['newlevel']['levelname']) ? $data['newlevel']['levelname'] : '', '[新等级分红比例]' => !empty($data['newlevel']['bonus']) ? $data['newlevel']['bonus'] . '%' : '');

			switch ($tag) {
			case 'become_advanced':
				$arr['[时间]'] = date('Y-m-d H:i:s', $data['weighttime']);
				$arr['[昵称]'] = $data['nickname'];
			case 'pay_advanced':
				$arr['[时间]'] = date('Y-m-d H:i:s', $data['paytime']);
				$arr['[昵称]'] = $data['nickname'];
				break;
			}

			foreach ($arr as $key => $value) {
				$str = str_replace($key, $value, $str);
			}

			return $str;
		}

		//根据用户openid获取用户等级
		public function getLevel($openid)
		{
			global $_W;

			if (empty($openid)) {
				return false;
			}

			$member = m('member')->getMember($openid);

			if (empty($member['weightlevel'])) {
				return false;
			}

			$level = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_level') . ' where uniacid=:uniacid and id=:id limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $member['weightlevel']));
			return $level;
		}


		//获取分红数据
		public function getBonusData($year = 0, $month = 0, $week = 0, $openid = '')
		{
			global $_W;
			$set = $this->getSet();

			$days = get_last_day($year, $month);
			$starttime = strtotime($year . '-' . $month . '-1');
			$endtime = strtotime($year . '-' . $month . '-' . $days);
			$settletimes = intval($set['settledays']) * 86400;   //设置订单几天后才可进行分红结算
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

			$bill = pdo_fetch('select * from ' . tablename('wx_shop_weightbonus_bill') . ' where uniacid=:uniacid and `year`=:year and `month`=:month and `week`=:week limit 1', array(':uniacid' => $_W['uniacid'], ':year' => $year, ':month' => $month, ':week' => $week));

			if (!empty($bill) && empty($openid)) {
				return array('ordermoney' => round($bill['ordermoney'], 2), 'ordercount' => $bill['ordercount'], 'bonusmoney' => round($bill['bonusmoney'], 2), 'bonusordermoney' => round($bill['bonusordermoney'], 2), 'bonusmoney_send' => round($bill['bonusmoney_send'], 2), 'weightcount' => $bill['weightcount'], 'starttime' => $starttime, 'endtime' => $endtime, 'billid' => $bill['id'], 'old' => true);
			}

			$ordermoney = 0;
			$bonusordermoney = 0;
			$bonusmoney = 0;
            $weightlist = array();   //将订单中的用户链接全部塞到这里（可能出现同用户出现多次）
			$pcondition = '';

			if (!empty($openid)) {
				$member = m('member')->getMember($openid);
				$pcondition = 'AND finishtime>' . $member['weighttime'];
			}

			//只查找参与分红的订单 isweightbonus = 1  把订单价格price换成分红价格weightbonusprice
//			$orders = pdo_fetchall('select id,openid,price,weightbonusprice from ' . tablename('wx_shop_order') . ' where uniacid=' . $_W['uniacid'] . ' and status=3 and isweightbonus=1 and finishtime + ' . $settletimes . '>= ' . $starttime . ' and  finishtime + ' . $settletimes . '<=' . $endtime . ' ' . $pcondition, array(), 'id');

            $orders=pdo_fetchall('select * from ' . tablename('wx_shop_weightbonus_order') . '  W inner join ' . tablename('wx_shop_order') . '  O on W.orderid = O.id  where  W.uniacid=' . $_W['uniacid']. ' and O.status=3 and O.isweightbonus=1 and O.finishtime + ' . $settletimes . '>= ' . $starttime . ' and  O.finishtime + ' . $settletimes . '<=' . $endtime . ' ' . $pcondition, array(), 'id');

            if(empty($orders)){
                return null;
            }

			foreach ($orders as $o) {
                //$weights = $this->getAgentids($o['openid'],true); //根据订单中的openid取出上级推荐链
                $weights = iunserializer($o['weightbonus_json']);//取出数据库中的用户链（下单时候存储的）

                $ordermoney += $o['price'];  //订单金额
				$bonusordermoney += $o['weightbonusprice'];  //订单分红金额

                for($weights_count=0;$weights_count<count($weights);++$weights_count)
                {
                    //判断是否算上自购
                    if (empty($o['weightbonus_selfbuy'])) {
                        if ($weights[$weights_count]['openid'] == $o['openid']) {
                            continue;
                        }
                    }
                    $weights[$weights_count]['orderid'] = $o['id'];
                    $weights[$weights_count]['bonusmoney'] = $weights[$weights_count]['bonusmoney'];
                    if($weights[$weights_count]['bonusmoney']==0){
                        continue;
                    }

                    //计算用户分红：判断是否开启了极差，第一个用户始终都为乘与自身等级的分红比例
//                    if($o['weightbonus_range']==1 && $weights_count>0){
//                        //减去上一个等级的分红比例再相乘
//                        !isset($weights[$weights_count]['bonusmoney']) && $weights[$weights_count]['bonusmoney'] = 0;
//                        $weights[$weights_count]['bonusmoney'] += floatval(($o['weightbonusprice'] * ($weights[$weights_count]['bonus']-$weights[$weights_count-1]['bonus'])) / 100);
//                    }else{
//                        !isset($weights[$weights_count]['bonusmoney']) && $weights[$weights_count]['bonusmoney'] = 0;
//                        $weights[$weights_count]['bonusmoney'] += floatval(($o['weightbonusprice'] * $weights[$weights_count]['bonus']) / 100);
//                    }

                    $weightlist[]=$weights[$weights_count];
                }

				unset($p);
			}


			//计算最终的分红，扣除手续费与判断免手续费区间
			foreach ($weightlist as &$p) {
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

				foreach ($weightlist as &$p) {
					$p['chargemoney'] = round($p['chargemoney'] * $rat, 2);
					$p['bonusmoney_send'] = round($p['bonusmoney_send'] * $rat, 2);
					$bonusmoney += $p['bonusmoney_send'];
				}

				unset($p);
			}


			//表示有用户提交了openid时候的情况（暂时在手机端显示预计分红时使用）
            if(!empty($openid)){
                $weightmember = array();
                $charge = 0;
                $chargemoney = 0;
                $bonusmoney_send = 0;
			    foreach ($weightlist as $w){
			        if($w['openid']==$openid){
			            $weightmember[0] = $w;
			            $charge+= $w['charge'];
                        $chargemoney+= $w['chargemoney'];
                        $bonusmoney_send+= $w['bonusmoney_send'];
                    }
                }
               $weightmember[0]['charge'] = $charge;
               $weightmember[0]['chargemoney'] = $chargemoney;
               $weightmember[0]['bonusmoney_send'] = $bonusmoney_send;
               $weightlist = $weightmember;
               unset($w);
            }

            //统计代理人数，取出openid列组成新数组->去重->统计
            $weightlist_count = count(array_unique(array_column($weightlist, 'openid')));


			return array('orders' => $orders, 'weights' => $weightlist, 'ordermoney' => round($ordermoney, 2), 'bonusordermoney' => round($bonusordermoney, 2),  'ordercount' => count($orders), 'bonusmoney' => round($bonusmoney, 2), 'weightcount' => $weightlist_count, 'starttime' => $starttime, 'endtime' => $endtime, 'old' => false);
		}

		public function getTotals()
		{
			global $_W;
			return array('total0' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_bill') . ' where uniacid=:uniacid and status=0 limit 1', array(':uniacid' => $_W['uniacid'])), 'total1' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_bill') . ' where uniacid=:uniacid and status=1 limit 1', array(':uniacid' => $_W['uniacid'])), 'total2' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_bill') . ' where uniacid=:uniacid and status=2  limit 1', array(':uniacid' => $_W['uniacid'])));
		}
	}
}

?>

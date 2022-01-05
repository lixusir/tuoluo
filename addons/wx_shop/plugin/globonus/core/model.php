<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

define('TM_GLOBONUS_PAY', 'TM_GLOBONUS_PAY');
define('TM_GLOBONUS_UPGRADE', 'TM_GLOBONUS_UPGRADE');
define('TM_GLOBONUS_BECOME', 'TM_GLOBONUS_BECOME');

if (!class_exists('GlobonusModel')) {
	class GlobonusModel extends PluginModel
	{
		public function getSet($uniacid = 0)
		{
			$set = parent::getSet($uniacid);
			$set['texts'] = array('partner' => empty($set['texts']['partner']) ? '股东' : $set['texts']['partner'], 'center' => empty($set['texts']['center']) ? '股东中心' : $set['texts']['center'], 'become' => empty($set['texts']['become']) ? '成为股东' : $set['texts']['become'], 'bonus' => empty($set['texts']['bonus']) ? '分红' : $set['texts']['bonus'], 'bonus_total' => empty($set['texts']['bonus_total']) ? '累计分红' : $set['texts']['bonus_total'], 'bonus_lock' => empty($set['texts']['bonus_lock']) ? '待结算分红' : $set['texts']['bonus_lock'], 'bonus_pay' => empty($set['texts']['bonus_lock']) ? '已结算分红' : $set['texts']['bonus_pay'], 'bonus_wait' => empty($set['texts']['bonus_wait']) ? '预计分红' : $set['texts']['bonus_wait'], 'bonus_detail' => empty($set['texts']['bonus_detail']) ? '分红明细' : $set['texts']['bonus_detail'], 'bonus_charge' => empty($set['texts']['bonus_charge']) ? '扣除提现手续费' : $set['texts']['bonus_charge']);
			return $set;
		}

        // 代理升级(根据分红数)
        public function upgradeLevelByBonus($openid)
        {
            global  $_W;
            if (empty($openid)) {
                return false;
            }
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
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
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
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
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
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
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;
            /*异步升级*/
            return $this->upgradeLevelByMultiple($openid);
        }

        //异步升级
        private function upgradeLevelByMultiple($openid)
        {
            global $_W;
            load()->func('communication');
            ihttp_request($_W['siteroot']. 'app/wx_shop_api.php?r=upgradeasyn.globonus&i=' . $_W['uniacid'] . '&openid=' . $openid, [], [], 0);
        }

        //递归升级上级层级
        private $upgradeLevelAsynRecursionLevel = 3;
        public function upgradeLevelAsyn($openid,$isselfbuy=1)
        {
            global $_W;
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);

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
            if (!empty($m['agentid']) && $this->upgradeLevelAsynRecursionLevel > 0) {
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
            if (!empty($m['partnernotupgrade'])) {
                return NULL;
            }
            $oldlevel = $newlevel = $this->getLevel($m['openid']);
            if (empty($oldlevel['id'])) {
                $oldlevel = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'bonus' => $set['bonus']);
            }

            //已发放分红总金额  11
            if (in_array('11',$leveltypes_json_toarray)) {
                $bonusmoney = $this->getBonus($openid, array('ok'));
                $newlevel11 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $bonusmoney['ok'] . ' >= bonusmoney and bonusmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel11['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel11;
                }
            }

            // 已提现佣金总金额 10
            if (in_array('10',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $commissionmoney = $info['commission_pay'];
                $newlevel10 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $commissionmoney . ' >= commissionmoney and commissionmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel10['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel10;
                }
            }

            // 自购订单金额 4
            if (in_array('4',$leveltypes_json_toarray)) {
                $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                $ordermoney = $orders['ordermoney'];
                $newlevel4 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordermoney . ' >= ordermoney2 and ordermoney2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                $newlevel5 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount2 and ordercount2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel5['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel5;
                }
            }


            if (in_array('6',$leveltypes_json_toarray) || in_array('8',$leveltypes_json_toarray) || in_array('0',$leveltypes_json_toarray) || strexistsin_array('1',$leveltypes_json_toarray) || in_array('2',$leveltypes_json_toarray) || in_array('3',$leveltypes_json_toarray)) {

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
                    $newlevel0 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney and ordermoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));//这里有bug
                    if (!empty($newlevel0) && $newlevel0['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel0;
                    }
                }

                //一级分销订单金额 1
                if (in_array('1',$leveltypes_json_toarray)) {
                    $ordermoney = $info['order13money'];
                    $newlevel1 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney1 and ordermoney1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel1) && $newlevel1['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel1;
                    }
                }

                //分销订单数量 2
                if (in_array('2',$leveltypes_json_toarray)) {
                    $ordercount = $info['ordercount3'];
                    $newlevel2 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount and ordercount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel2) && $newlevel2['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel2;
                    }
                }

                //一级分销订单数量 3
                if (in_array('3',$leveltypes_json_toarray)) {
                    $ordercount = $info['order13'];
                    $newlevel3 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount1 and ordercount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
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

                    $newlevel6 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel6) && $newlevel6['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel6;
                    }
                }

                //团队总人数 8
                if (in_array('8',$leveltypes_json_toarray)) {
                    $downcount8 = $info['level1'] + $info['level2'] + $info['level3'];

                    $newlevel8 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount8 . ' >= teamcount and teamcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel8) && $newlevel8['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel8;
                    }
                }

            }

            //一级下级人数 7
            if (in_array('7',$leveltypes_json_toarray)) {
                $downcount7 = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']));
                $newlevel7 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount7 . ' >= downcount1 and downcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (!empty($newlevel7) && $newlevel7['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel7;
                }
            }

            //一级团队人数 9
            if (in_array('9',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array());

                $downcount9 = $info['level1'];
                $newlevel9 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount9 . ' >= teamcount1 and teamcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (!empty($newlevel9) && $newlevel9['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel9;
                }
            }

            if ($newlevel > $oldlevel) {
                pdo_update('wx_shop_member', array('partnerlevel' => $newlevel['id']), array('id' => $m['id']));
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
            if (!empty($m['partnernotupgrade'])) {
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
                $newlevel11 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $bonusmoney['ok'] . ' >= bonusmoney and bonusmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel11) || $oldlevel['weight'] == $newlevel11['weight']) {
                    return false;
                }
                if ($newlevel11['weight'] > $newlevel['weight']) $newlevel = $newlevel11;
            }


            //已提现佣金总金额 10
            if (in_array('10',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $commissionmoney = $info['commission_pay'];
                $newlevel10 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $commissionmoney . ' >= commissionmoney and commissionmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel10) || $oldlevel['weight'] == $newlevel10['weight']) {
                    return false;
                }
                if ($newlevel10['weight'] > $newlevel['weight']) $newlevel = $newlevel10;
            }

            //自购订单金额  4
            if (in_array('4',$leveltypes_json_toarray)) {
                $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                $ordermoney = $orders['ordermoney'];
                $newlevel4 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordermoney . ' >= ordermoney2 and ordermoney2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                $newlevel5 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount2 and ordercount2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                    $newlevel0 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney and ordermoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel0) || $oldlevel['weight'] == $newlevel0['weight']) {
                        return null;
                    }
                    if ($newlevel0['weight'] > $newlevel['weight']) $newlevel = $newlevel0;
                }

                //一级分销订单金额  1
                if (in_array('1',$leveltypes_json_toarray)) {
                    $ordermoney = $info['order13money'];
                    $newlevel1 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney1 and ordermoney1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                    if (empty($newlevel1) || $oldlevel['weight'] == $newlevel1['weight']) {
                        return null;
                    }
                    if ($newlevel1['weight'] > $newlevel['weight']) $newlevel = $newlevel1;
                }

                //分销订单数量  2
                if (in_array('2',$leveltypes_json_toarray)) {
                    $ordercount = $info['ordercount3'];
                    $newlevel2 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount and ordercount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel2) || $oldlevel['weight'] == $newlevel2['weight']) {
                        return null;
                    }
                    if ($newlevel2['weight'] > $newlevel['weight']) $newlevel = $newlevel2;
                }

                //一级分销订单数量  3
                if (in_array('3',$leveltypes_json_toarray)) {
                    $ordercount = $info['order13'];
                    $newlevel3 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount1 and ordercount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
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

                    $newlevel6 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel6) || $oldlevel['weight'] == $newlevel6['weight']) {
                        return null;
                    }
                    if ($newlevel6['weight'] > $newlevel['weight']) $newlevel = $newlevel6;
                }

                //团队总人数  8
                if (in_array('8',$leveltypes_json_toarray)) {
                    $downcount8 = $info['level1'] + $info['level2'] + $info['level3'];
                    $newlevel8 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount8 . ' >= teamcount and teamcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel8) || $oldlevel['weight'] == $newlevel8['weight']) {
                        return null;
                    }
                    if ($newlevel8['weight'] > $newlevel['weight']) $newlevel = $newlevel8;
                }

            }

            //一级下级人数  7
            if (in_array('7',$leveltypes_json_toarray)) {
                $downcount7 = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']));
                $newlevel7 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount7 . ' >= downcount1 and downcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel7) || $oldlevel['weight'] == $newlevel7['weight']) {
                    return false;
                }
                if ($newlevel7['weight'] > $newlevel['weight']) $newlevel = $newlevel7;
            }

            //一级团队人数  9
            if (in_array('9',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array());

                $downcount9 = $info['level1'];
                $newlevel9 = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid  and ' . $downcount9 . ' >= teamcount1 and teamcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel9) || $oldlevel['weight'] == $newlevel9['weight']) {
                    return false;
                }
                if ($newlevel9['weight'] > $newlevel['weight']) $newlevel = $newlevel9;
            }

            if ($newlevel['weight'] > $oldlevel['weight']) {
                pdo_update('wx_shop_member', array('partnerlevel' => $newlevel['id']), array('id' => $m['id']));
                $this->sendMessage($m['openid'], array('nickname' => $m['nickname'], 'oldlevel' => $oldlevel, 'newlevel' => $newlevel), TM_WEIGHTBONUS_UPGRADE);
                return true;
            }
        }

		/**
         * 获取所有股东等级
         * @global type $_W
         * @return type
         */
		public function getLevels($all = true, $default = false)
		{
			global $_W;

			if ($all) {
				$levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid order by bonus asc', array(':uniacid' => $_W['uniacid']));
			}
			else {
				$levels = pdo_fetchall('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid and (ordermoney>0 or commissionmoney>0 or bonusmoney>0) order by bonus asc', array(':uniacid' => $_W['uniacid']));
			}

			if ($default) {
				$default = array('id' => '0', 'levelname' => empty($_S['globonus']['levelname']) ? '默认等级' : $_S['globonus']['levelname'], 'bonus' => $_W['shopset']['globonus']['bonus']);
				$levels = array_merge(array($default), $levels);
			}

			return $levels;
		}

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

			if (($message_type == TM_GLOBONUS_PAY) && empty($usernotice['globonus_pay'])) {
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

			if (($message_type == TM_GLOBONUS_UPGRADE) && empty($usernotice['globonus_upgrade'])) {
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
					'keyword1' => array('value' => !empty($tm['upgradetitle']) ? $tm['upgradetitle'] : '股东等级升级通知', 'color' => '#73a68d'),
					'keyword2' => array('value' => $message, 'color' => '#73a68d')
					);
				return $this->sendNotice($openid, $tm, 'upgrade_advanced', $data, $member, $msg);
			}

			if (($message_type == TM_GLOBONUS_BECOME) && empty($usernotice['globonus_become'])) {
				$message = $tm['become'];

				if (empty($message)) {
					return false;
				}

				$message = str_replace('[昵称]', $data['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', $data['partnertime']), $message);
				$msg = array(
					'keyword1' => array('value' => !empty($tm['becometitle']) ? $tm['becometitle'] : '成为股东通知', 'color' => '#73a68d'),
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
				$arr['[时间]'] = date('Y-m-d H:i:s', $data['partnertime']);
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

		public function getLevel($openid)
		{
			global $_W;

			if (empty($openid)) {
				return false;
			}

			$member = m('member')->getMember($openid);

			if (empty($member['partnerlevel'])) {
				return false;
			}

			$level = pdo_fetch('select * from ' . tablename('wx_shop_globonus_level') . ' where uniacid=:uniacid and id=:id limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $member['partnerlevel']));
			return $level;
		}


		public function getInfo($openid, $options = NULL)
		{
			return p('commission')->getInfo($openid, $options);
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

		public function getTotals()
		{
			global $_W;
			return array('total0' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_globonus_bill') . ' where uniacid=:uniacid and status=0 limit 1', array(':uniacid' => $_W['uniacid'])), 'total1' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_globonus_bill') . ' where uniacid=:uniacid and status=1 limit 1', array(':uniacid' => $_W['uniacid'])), 'total2' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_globonus_bill') . ' where uniacid=:uniacid and status=2  limit 1', array(':uniacid' => $_W['uniacid'])));
		}
	}
}

?>

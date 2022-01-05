<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
define('TM_ABONUS_PAY', 'TM_ABONUS_PAY');
define('TM_ABONUS_UPGRADE', 'TM_ABONUS_UPGRADE');
define('TM_ABONUS_BECOME', 'TM_ABONUS_BECOME');
if (!(class_exists('AbonusModel'))) 
{
	class AbonusModel extends PluginModel 
	{
		public function getSet($uniacid = 0) 
		{
			$set = parent::getSet($uniacid);
			$set['texts'] = array('aagent' => (empty($set['texts']['aagent']) ? '区域代理' : $set['texts']['aagent']), 'center' => (empty($set['texts']['center']) ? '区域代理中心' : $set['texts']['center']), 'become' => (empty($set['texts']['become']) ? '成为区域代理' : $set['texts']['become']), 'bonus' => (empty($set['texts']['bonus']) ? '分红' : $set['texts']['bonus']), 'bonus_total' => (empty($set['texts']['bonus_total']) ? '累计分红' : $set['texts']['bonus_total']), 'bonus_lock' => (empty($set['texts']['bonus_lock']) ? '待结算分红' : $set['texts']['bonus_lock']), 'bonus_pay' => (empty($set['texts']['bonus_lock']) ? '已结算分红' : $set['texts']['bonus_pay']), 'bonus_wait' => (empty($set['texts']['bonus_wait']) ? '预计分红' : $set['texts']['bonus_wait']), 'bonus_detail' => (empty($set['texts']['bonus_detail']) ? '分红明细' : $set['texts']['bonus_detail']), 'bonus_charge' => (empty($set['texts']['bonus_charge']) ? '扣除个人所得税' : $set['texts']['bonus_charge']));
			return $set;
		}
		public function getLevels($all = true, $default = false) 
		{
			global $_W;
			if ($all) 
			{
				$levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid order by id asc', array(':uniacid' => $_W['uniacid']));
			}
			else 
			{
				$levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid and (ordermoney>0 or bonusmoney>0) order by bonus asc', array(':uniacid' => $_W['uniacid']));
			}
			if ($default) 
			{
				$default = array('id' => '0', 'levelname' => (empty($_S['abonus']['levelname']) ? '默认等级' : $_S['abonus']['levelname']), 'bonus1' => $_W['shopset']['abonus']['bonus1'], 'bonus2' => $_W['shopset']['abonus']['bonus2'], 'bonus3' => $_W['shopset']['abonus']['bonus3']);
				$levels = array_merge(array($default), $levels);
			}
			return $levels;
		}
		public function getBonus($openid = '', $params = array()) 
		{
			global $_W;
			if (in_array('ok', $params)) 
			{
				$ret['ok'] = pdo_fetchcolumn('select ifnull(sum(paymoney1),0)+ifnull(sum(paymoney2),0)  + ifnull(sum(paymoney3),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('ok1', $params)) 
			{
				$ret['ok1'] = pdo_fetchcolumn('select ifnull(sum(paymoney1),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('ok2', $params)) 
			{
				$ret['ok2'] = pdo_fetchcolumn('select ifnull(sum(paymoney2),0)   from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('ok3', $params)) 
			{
				$ret['ok3'] = pdo_fetchcolumn('select ifnull(sum(paymoney3),0)   from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('lock', $params)) 
			{
				$ret['lock'] = pdo_fetchcolumn('select ifnull(sum(paymoney1),0)+ifnull(sum(paymoney2),0)  + ifnull(sum(paymoney3),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('lock1', $params)) 
			{
				$ret['lock1'] = pdo_fetchcolumn('select ifnull(sum(paymoney1),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('lock2', $params)) 
			{
				$ret['lock2'] = pdo_fetchcolumn('select ifnull(sum(paymoney2),0)   from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('lock3', $params)) 
			{
				$ret['lock3'] = pdo_fetchcolumn('select ifnull(sum(paymoney3),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('total', $params)) 
			{
				$ret['total'] = pdo_fetchcolumn('select ifnull(sum(paymoney1),0)+ifnull(sum(paymoney2),0)  + ifnull(sum(paymoney3),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('total1', $params)) 
			{
				$ret['total1'] = pdo_fetchcolumn('select ifnull(sum(paymoney1),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('total2', $params)) 
			{
				$ret['total2'] = pdo_fetchcolumn('select ifnull(sum(paymoney2),0)   from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			if (in_array('total3', $params)) 
			{
				$ret['total3'] = pdo_fetchcolumn('select ifnull(sum(paymoney3),0)  from ' . tablename('wx_shop_abonus_billp') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			return $ret;
		}
		public function sendMessage($openid = '', $data = array(), $message_type = '') 
		{
			global $_W;
			global $_GPC;
			$set = $this->getSet();
			$tm = $set['tm'];
			$templateid = $tm['templateid'];
			$member = m('member')->getMember($openid);
			$usernotice = unserialize($member['noticeset']);
			$advanced = '';
			if (!(is_array($usernotice))) 
			{
				$usernotice = array();
			}
			if (($message_type == TM_ABONUS_PAY) && empty($usernotice['abonus_pay'])) 
			{
				$message = '';
				$paytitle = '';
				if ($data['aagenttype'] == 1) 
				{
					$message = $tm['pay1'];
					$paytitle = ((empty($tm['paytitle1']) ? '省级代理分红发放通知' : $tm['paytitle1']));
				}
				else if ($data['aagenttype'] == 2) 
				{
					$message = $tm['pay2'];
					$paytitle = ((empty($tm['paytitle2']) ? '市级代理分红发放通知' : $tm['paytitle2']));
				}
				else if ($data['aagenttype'] == 3) 
				{
					$message = $tm['pay3'];
					$paytitle = ((empty($tm['paytitle3']) ? '区级代理分红发放通知' : $tm['paytitle3']));
				}
				if (empty($message)) 
				{
					return false;
				}
				$message = str_replace('[昵称]', $member['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', time()), $message);
				$message = str_replace('[打款方式]', $data['type'], $message);
				if ($data['aagenttype'] == 1) 
				{
					$message = str_replace('[省级分红金额]', $data['paymoney1'], $message);
					$message = str_replace('[市级分红金额]', $data['paymoney2'], $message);
					$message = str_replace('[区级分红金额]', $data['paymoney3'], $message);
					$advanced = 'pay1_advanced';
				}
				else if ($data['aagenttype'] == 2) 
				{
					$message = str_replace('[市级分红金额]', $data['paymoney2'], $message);
					$message = str_replace('[区级分红金额]', $data['paymoney3'], $message);
					$advanced = 'pay2_advanced';
				}
				else if ($data['aagenttype'] == 3) 
				{
					$message = str_replace('[区级分红金额]', $data['paymoney3'], $message);
					$advanced = 'pay3_advanced';
				}
				$msg = array( 'keyword1' => array('value' => $paytitle, 'color' => '#73a68d'), 'keyword2' => array('value' => $message, 'color' => '#73a68d') );
				return $this->sendNotice($openid, $tm, $advanced, $data, $member, $msg);
			}
			if (($message_type == TM_ABONUS_UPGRADE) && empty($usernotice['abonus_upgrade'])) 
			{
				$message = $tm['upgrade'];
				$message = '';
				$message = '';
				$upgradetitle = '';
				if ($data['aagenttype'] == 1) 
				{
					$message = $tm['upgrade1'];
					$upgradetitle = ((empty($tm['upgradetitle1']) ? '省级代理等级升级通知' : $tm['upgradetitle1']));
				}
				else if ($data['aagenttype'] == 2) 
				{
					$message = $tm['upgrade2'];
					$upgradetitle = ((empty($tm['upgradetitle2']) ? '市级代理等级升级通知' : $tm['upgradetitle2']));
				}
				else if ($data['aagenttype'] == 3) 
				{
					$message = $tm['upgrade3'];
					$upgradetitle = ((empty($tm['upgradetitle3']) ? '区级代理等级升级通知' : $tm['upgradetitle3']));
				}
				if (empty($message)) 
				{
					return false;
				}
				$message = str_replace('[昵称]', $member['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', time()), $message);
				$message = str_replace('[旧等级]', $data['oldlevel']['levelname'], $message);
				$message = str_replace('[新等级]', $data['newlevel']['levelname'], $message);
				$advanced = '';
				if ($member['aagenttype'] == 1) 
				{
					$message = str_replace('[旧省级分红比例]', $data['oldlevel']['bonus1'] . '%', $message);
					$message = str_replace('[旧市级分红比例]', $data['oldlevel']['bonus2'] . '%', $message);
					$message = str_replace('[旧区级分红比例]', $data['oldlevel']['bonus3'] . '%', $message);
					$message = str_replace('[新省级分红比例]', $data['newlevel']['bonus1'] . '%', $message);
					$message = str_replace('[新市级分红比例]', $data['newlevel']['bonus2'] . '%', $message);
					$message = str_replace('[新区级分红比例]', $data['newlevel']['bonus3'] . '%', $message);
					$advanced = 'upgrade1_advanced';
				}
				else if ($member['aagenttype'] == 2) 
				{
					$message = str_replace('[旧市级分红比例]', $data['oldlevel']['bonus2'] . '%', $message);
					$message = str_replace('[旧区级分红比例]', $data['oldlevel']['bonus3'] . '%', $message);
					$message = str_replace('[新市级分红比例]', $data['newlevel']['bonus2'] . '%', $message);
					$message = str_replace('[新区级分红比例]', $data['newlevel']['bonus3'] . '%', $message);
					$advanced = 'upgrade2_advanced';
				}
				else if ($member['aagenttype'] == 3) 
				{
					$message = str_replace('[旧区级分红比例]', $data['oldlevel']['bonus3'] . '%', $message);
					$message = str_replace('[新区级分红比例]', $data['newlevel']['bonus3'] . '%', $message);
					$advanced = 'upgrade3_advanced';
				}
				$msg = array( 'keyword1' => array('value' => $upgradetitle, 'color' => '#73a68d'), 'keyword2' => array('value' => $message, 'color' => '#73a68d') );
				return $this->sendNotice($openid, $tm, $advanced, $data, $member, $msg);
			}
			if (($message_type == TM_ABONUS_BECOME) && empty($usernotice['abonus_become'])) 
			{
				$message = $tm['become'];
				if (empty($message)) 
				{
					return false;
				}
				$message = str_replace('[昵称]', $data['nickname'], $message);
				$message = str_replace('[时间]', date('Y-m-d H:i:s', $data['aagenttime']), $message);
				if ($data['aagenttype'] == 1) 
				{
					$aagenttype = '省级代理';
				}
				else if ($data['aagenttype'] == 2) 
				{
					$aagenttype = '市级代理';
				}
				else if ($data['aagenttype'] == 3) 
				{
					$aagenttype = '区级代理';
				}
				$message = str_replace('[代理级别]', $aagenttype, $message);
				$message = str_replace('[代理区域]', $data['aagentareas'], $message);
				$msg = array( 'keyword1' => array('value' => (!(empty($tm['becometitle'])) ? $tm['becometitle'] : '成为区域代理通知'), 'color' => '#73a68d'), 'keyword2' => array('value' => $message, 'color' => '#73a68d') );
				return $this->sendNotice($openid, $tm, 'become_advanced', $data, $member, $msg);
			}
		}
		protected function sendNotice($touser, $tm, $tag, $datas, $member, $msg) 
		{
			global $_W;
			if (!(empty($tm['is_advanced'])) && !(empty($tm[$tag]))) 
			{
				$advanced_template = pdo_fetch('select * from ' . tablename('wx_shop_member_message_template') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $tm[$tag], ':uniacid' => $_W['uniacid']));
				if (!(empty($advanced_template))) 
				{
					$url = ((!(empty($advanced_template['url'])) ? $this->replaceTemplate($advanced_template['url'], $tag, $datas, $member) : ''));
					$advanced_message = array( 'first' => array('value' => $this->replaceTemplate($advanced_template['first'], $tag, $datas, $member), 'color' => $advanced_template['firstcolor']), 'remark' => array('value' => $this->replaceTemplate($advanced_template['remark'], $tag, $datas, $member), 'color' => $advanced_template['remarkcolor']) );
					$data = iunserializer($advanced_template['data']);
					foreach ($data as $d ) 
					{
						$advanced_message[$d['keywords']] = array('value' => $this->replaceTemplate($d['value'], $tag, $datas, $member), 'color' => $d['color']);
					}
					if (!(empty($advanced_template['template_id']))) 
					{
						m('message')->sendTplNotice($touser, $advanced_template['template_id'], $advanced_message);
					}
					else 
					{
						m('message')->sendCustomNotice($touser, $advanced_message);
					}
				}
			}
			else if (!(empty($tm['templateid']))) 
			{
				m('message')->sendTplNotice($touser, $tm['templateid'], $msg);
			}
			else 
			{
				m('message')->sendCustomNotice($touser, $msg);
			}
			return true;
		}
		protected function replaceTemplate($str, $tag, $data, $member) 
		{
			$arr = array('[昵称]' => $member['nickname'], '[时间]' => date('Y-m-d H:i:s', time()), '[提现方式]' => (!(empty($data['type'])) ? $data['type'] : ''), '[旧等级]' => (!(empty($data['oldlevel']['levelname'])) ? $data['oldlevel']['levelname'] : ''), '[新等级]' => (!(empty($data['newlevel']['levelname'])) ? $data['newlevel']['levelname'] : ''));
			switch ($tag) 
			{
				case 'become_advanced': $arr['[时间]'] = date('Y-m-d H:i:s', $data['aagenttime']);
				$arr['[昵称]'] = $data['nickname'];
				case 'pay1_advanced': $arr['[昵称]'] = $data['nickname'];
				$arr['[时间]'] = date('Y-m-d H:i:s', $data['paytime']);
				$arr['[省级分红金额]'] = $data['paymoney1'];
				$arr['[市级分红金额]'] = $data['paymoney2'];
				$arr['[区级分红金额]'] = $data['paymoney3'];
				case 'pay2_advanced': $arr['[昵称]'] = $data['nickname'];
				$arr['[时间]'] = date('Y-m-d H:i:s', $data['paytime']);
				$arr['[市级分红金额]'] = $data['paymoney2'];
				$arr['[区级分红金额]'] = $data['paymoney3'];
				case 'pay3_advanced': $arr['[昵称]'] = $data['nickname'];
				$arr['[时间]'] = date('Y-m-d H:i:s', $data['paytime']);
				$arr['[区级分红金额]'] = $data['paymoney3'];
				case 'upgrade1_advanced': $arr['[旧省级分红比例]'] = ((!(empty($data['oldlevel']['bonus1'])) ? $data['oldlevel']['bonus1'] . '%' : ''));
				$arr['[旧市级分红金额]'] = ((!(empty($data['oldlevel']['bonus2'])) ? $data['oldlevel']['bonus2'] . '%' : ''));
				$arr['[旧区级分红金额]'] = ((!(empty($data['oldlevel']['bonus3'])) ? $data['oldlevel']['bonus3'] . '%' : ''));
				$arr['[新省级分红比例]'] = ((!(empty($data['newlevel']['bonus1'])) ? $data['newlevel']['bonus1'] . '%' : ''));
				$arr['[新市级分红金额]'] = ((!(empty($data['newlevel']['bonus2'])) ? $data['newlevel']['bonus2'] . '%' : ''));
				$arr['[新区级分红金额]'] = ((!(empty($data['newlevel']['bonus3'])) ? $data['newlevel']['bonus3'] . '%' : ''));
				case 'upgrade1_advanced': $arr['[旧市级分红金额]'] = ((!(empty($data['oldlevel']['bonus2'])) ? $data['oldlevel']['bonus2'] . '%' : ''));
				$arr['[旧区级分红金额]'] = ((!(empty($data['oldlevel']['bonus3'])) ? $data['oldlevel']['bonus3'] . '%' : ''));
				$arr['[新市级分红金额]'] = ((!(empty($data['newlevel']['bonus2'])) ? $data['newlevel']['bonus2'] . '%' : ''));
				$arr['[新区级分红金额]'] = ((!(empty($data['newlevel']['bonus3'])) ? $data['newlevel']['bonus3'] . '%' : ''));
				case 'upgrade3_advanced': $arr['[旧区级分红金额]'] = ((!(empty($data['oldlevel']['bonus3'])) ? $data['oldlevel']['bonus3'] . '%' : ''));
				$arr['[新区级分红金额]'] = ((!(empty($data['newlevel']['bonus3'])) ? $data['newlevel']['bonus3'] . '%' : ''));
			}
			foreach ($arr as $key => $value ) 
			{
				$str = str_replace($key, $value, $str);
			}
			return $str;
		}
		public function getLevel($openid) 
		{
			global $_W;
			if (empty($openid)) 
			{
				return false;
			}
			$member = m('member')->getMember($openid);
			if (empty($member['aagentlevel'])) 
			{
				return false;
			}
			$level = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid and id=:id limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $member['aagentlevel']));
			return $level;
		}
		public function getInfo($openid, $options = NULL) 
		{
			return p('commission')->getInfo($openid, $options);
		}

        // 代理升级(根据分红数)
        public function upgradeLevelByBonus($openid)
        {
            global  $_W;
            if (empty($openid)) {
                return false;
            }
            file_put_contents(IA_ROOT . "/data/logs/test.log", "【开始区域代理升级】 upgradeLevelByBonus                      startime=>" . date('m-d h:i:s') . "[openid]=>{$openid}\n", FILE_APPEND);
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
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
            file_put_contents(IA_ROOT . "/data/logs/test.log", "【开始区域代理升级】 upgradeLevelByOrder                      startime=>" . date('m-d h:i:s') . "[openid]=>{$openid}\n", FILE_APPEND);
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;

            /*异步升级*/
            return $this->upgradeLevelByMultiple($openid);
        }

        //代理升级(根据下级数)
        public function upgradeLevelByAgent($openid)
        {
            global $_W;
            if (empty($openid)) {
                return false;
            }
            file_put_contents(IA_ROOT . "/data/logs/test.log", "【开始区域代理升级】 upgradeLevelByAgent                      startime=>" . date('m-d h:i:s') . "[openid]=>{$openid}\n", FILE_APPEND);
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;
            /*异步升级*/
            return $this->upgradeLevelByMultiple($openid);
        }

        //分销商升级(根据佣金提现数)
        public function upgradeLevelByCommissionOK($openid)
        {
            global  $_W;
            if (empty($openid)) {
                return false;
            }
            file_put_contents(IA_ROOT . "/data/logs/test.log", "【开始区域代理升级】 upgradeLevelByCommissionOK                      startime=>" . date('m-d h:i:s') . "[openid]=>{$openid}\n", FILE_APPEND);
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);
            if(empty($levels)) return false;
            /*异步升级*/
            return $this->upgradeLevelByMultiple($openid);
        }

        //异步升级
        private function upgradeLevelByMultiple($openid)
        {
            global $_W;
            file_put_contents(IA_ROOT . "/data/logs/test.log", "【开始区域代理升级】 upgradeLevelByMultiple                      startime=>" . date('m-d h:i:s') . "[openid]=>{$openid}\n", FILE_APPEND);
            load()->func('communication');
            ihttp_request($_W['siteroot']. 'app/wx_shop_api.php?r=upgradeasyn.abonus&i=' . $_W['uniacid'] . '&openid=' . $openid, [], [], 0);
        }

        //递归升级上级层级
        private $upgradeLevelAsynRecursionLevel = 3;
        public function upgradeLevelAsyn($openid,$isselfbuy=1)
        {
            global $_W;
            $levels = pdo_fetchall('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid order by weight desc', ['uniacid' => $_W['uniacid']]);

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
            if (!empty($m['aagentnotupgrade'])) {
                return NULL;
            }
            $oldlevel = $newlevel = $this->getLevel($m['openid']);
            if (empty($oldlevel['id'])) {
                $oldlevel = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'bonus' => $set['bonus']);
            }

            //已发放分红总金额  11
            if (in_array('11',$leveltypes_json_toarray)) {
                $bonusmoney = $this->getBonus($openid, array('ok'));
                $newlevel11 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $bonusmoney['ok'] . ' >= bonusmoney and bonusmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel11['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel11;
                }
            }

            // 已提现佣金总金额 10
            if (in_array('10',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $commissionmoney = $info['commission_pay'];
                $newlevel10 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $commissionmoney . ' >= commissionmoney and commissionmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                if ($newlevel10['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel10;
                }
            }

            // 自购订单金额 4
            if (in_array('4',$leveltypes_json_toarray)) {
                $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                $ordermoney = $orders['ordermoney'];
                $newlevel4 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordermoney . ' >= ordermoney2 and ordermoney2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                $newlevel5 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount2 and ordercount2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                    $newlevel0 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney and ordermoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel0) && $newlevel0['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel0;
                    }
                }

                //一级分销订单金额 1
                if (in_array('1',$leveltypes_json_toarray)) {
                    $ordermoney = $info['order13money'];
                    $newlevel1 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney1 and ordermoney1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel1) && $newlevel1['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel1;
                    }
                }

                //分销订单数量 2
                if (in_array('2',$leveltypes_json_toarray)) {
                    $ordercount = $info['ordercount3'];
                    $newlevel2 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount and ordercount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel2) && $newlevel2['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel2;
                    }
                }

                //一级分销订单数量 3
                if (in_array('3',$leveltypes_json_toarray)) {
                    $ordercount = $info['order13'];
                    $newlevel3 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount1 and ordercount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
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

                    $newlevel6 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel6) && $newlevel6['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel6;
                    }
                }

                //团队总人数 8
                if (in_array('8',$leveltypes_json_toarray)) {
                    $downcount8 = $info['level1'] + $info['level2'] + $info['level3'];

                    $newlevel8 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount8 . ' >= teamcount and teamcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (!empty($newlevel8) && $newlevel8['weight'] > $newlevel['weight']) {
                        $newlevel = $newlevel8;
                    }
                }

            }

            //一级下级人数 7
            if (in_array('7',$leveltypes_json_toarray)) {
                $downcount7 = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']));
                $newlevel7 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount7 . ' >= downcount1 and downcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (!empty($newlevel7) && $newlevel7['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel7;
                }
            }

            //一级团队人数 9
            if (in_array('9',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array());

                $downcount9 = $info['level1'];
                $newlevel9 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount9 . ' >= teamcount1 and teamcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (!empty($newlevel9) && $newlevel9['weight'] > $newlevel['weight']) {
                    $newlevel = $newlevel9;
                }
            }

            if ($newlevel > $oldlevel) {
                pdo_update('wx_shop_member', array('aagentlevel' => $newlevel['id']), array('id' => $m['id']));
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
            if (!empty($m['aagentnotupgrade'])) {
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
                $newlevel11 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $bonusmoney['ok'] . ' >= bonusmoney and bonusmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel11) || $oldlevel['weight'] == $newlevel11['weight']) {
                    return false;
                }
                if ($newlevel11['weight'] > $newlevel['weight']) $newlevel = $newlevel11;
            }


            //已提现佣金总金额 10
            if (in_array('10',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array('pay'));
                $commissionmoney = $info['commission_pay'];
                $newlevel10 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $commissionmoney . ' >= commissionmoney and commissionmoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel10) || $oldlevel['weight'] == $newlevel10['weight']) {
                    return false;
                }
                if ($newlevel10['weight'] > $newlevel['weight']) $newlevel = $newlevel10;
            }

            //自购订单金额  4
            if (in_array('4',$leveltypes_json_toarray)) {
                $orders = pdo_fetch('select sum(og.realprice) as ordermoney,count(distinct og.orderid) as ordercount from ' . tablename('wx_shop_order') . ' o ' . ' left join  ' . tablename('wx_shop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=3 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
                $ordermoney = $orders['ordermoney'];
                $newlevel4 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordermoney . ' >= ordermoney2 and ordermoney2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                $newlevel5 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount2 and ordercount2>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

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
                    $newlevel0 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney and ordermoney>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel0) || $oldlevel['weight'] == $newlevel0['weight']) {
                        return null;
                    }
                    if ($newlevel0['weight'] > $newlevel['weight']) $newlevel = $newlevel0;
                }

                //一级分销订单金额  1
                if (in_array('1',$leveltypes_json_toarray)) {
                    $ordermoney = $info['order13money'];
                    $newlevel1 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid and ' . $ordermoney . ' >= ordermoney1 and ordermoney1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));

                    if (empty($newlevel1) || $oldlevel['weight'] == $newlevel1['weight']) {
                        return null;
                    }
                    if ($newlevel1['weight'] > $newlevel['weight']) $newlevel = $newlevel1;
                }

                //分销订单数量  2
                if (in_array('2',$leveltypes_json_toarray)) {
                    $ordercount = $info['ordercount3'];
                    $newlevel2 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount and ordercount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel2) || $oldlevel['weight'] == $newlevel2['weight']) {
                        return null;
                    }
                    if ($newlevel2['weight'] > $newlevel['weight']) $newlevel = $newlevel2;
                }

                //一级分销订单数量  3
                if (in_array('3',$leveltypes_json_toarray)) {
                    $ordercount = $info['order13'];
                    $newlevel3 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $ordercount . ' >= ordercount1 and ordercount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
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

                    $newlevel6 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel6) || $oldlevel['weight'] == $newlevel6['weight']) {
                        return null;
                    }
                    if ($newlevel6['weight'] > $newlevel['weight']) $newlevel = $newlevel6;
                }

                //团队总人数  8
                if (in_array('8',$leveltypes_json_toarray)) {
                    $downcount8 = $info['level1'] + $info['level2'] + $info['level3'];
                    $newlevel8 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount8 . ' >= teamcount and teamcount>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                    if (empty($newlevel8) || $oldlevel['weight'] == $newlevel8['weight']) {
                        return null;
                    }
                    if ($newlevel8['weight'] > $newlevel['weight']) $newlevel = $newlevel8;
                }

            }

            //一级下级人数  7
            if (in_array('7',$leveltypes_json_toarray)) {
                $downcount7 = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':agentid' => $m['id'], ':uniacid' => $_W['uniacid']));
                $newlevel7 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount7 . ' >= downcount1 and downcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel7) || $oldlevel['weight'] == $newlevel7['weight']) {
                    return false;
                }
                if ($newlevel7['weight'] > $newlevel['weight']) $newlevel = $newlevel7;
            }

            //一级团队人数  9
            if (in_array('9',$leveltypes_json_toarray)) {
                $info = $this->getInfo($m['id'], array());

                $downcount9 = $info['level1'];
                $newlevel9 = pdo_fetch('select * from ' . tablename('wx_shop_abonus_level') . ' where uniacid=:uniacid  and ' . $downcount9 . ' >= teamcount1 and teamcount1>0 and id = ' . $level['id'] . '  order by weight desc limit 1', array(':uniacid' => $_W['uniacid']));
                if (empty($newlevel9) || $oldlevel['weight'] == $newlevel9['weight']) {
                    return false;
                }
                if ($newlevel9['weight'] > $newlevel['weight']) $newlevel = $newlevel9;
            }

            if ($newlevel['weight'] > $oldlevel['weight']) {
                pdo_update('wx_shop_member', array('aagentlevel' => $newlevel['id']), array('id' => $m['id']));
                $this->sendMessage($m['openid'], array('nickname' => $m['nickname'], 'oldlevel' => $oldlevel, 'newlevel' => $newlevel), TM_WEIGHTBONUS_UPGRADE);
                return true;
            }
        }

        //获取佣金数据
		public function getBonusData($year = 0, $month = 0, $week = 0, $openid = '') 
		{
			global $_W;
			$set = $this->getSet();
			$days = get_last_day($year, $month);
			$starttime = strtotime($year . '-' . $month . '-1');
			$endtime = strtotime($year . '-' . $month . '-' . $days);
			$settletimes = intval($set['settledays']) * 86400;
			if ((1 <= $week) && ($week <= 4)) 
			{
				$weekdays = array();
				$i = $starttime;
				while ($i <= $endtime) 
				{
					$ds = explode('-', date('Y-m-d', $i));
					$day = intval($ds[2]);
					$w = ceil($day / 7);
					if (4 < $w) 
					{
						$w = 4;
					}
					if ($week == $w) 
					{
						$weekdays[] = $i;
					}
					$i += 86400;
				}
				$starttime = $weekdays[0];
				$endtime = strtotime(date('Y-m-d', $weekdays[count($weekdays) - 1]) . ' 23:59:59');
			}
			else 
			{
				$endtime = strtotime($year . '-' . $month . '-' . $days . ' 23:59:59');
			}
			$bill = pdo_fetch('select * from ' . tablename('wx_shop_abonus_bill') . ' where uniacid=:uniacid and `year`=:year and `month`=:month and `week`=:week limit 1', array(':uniacid' => $_W['uniacid'], ':year' => $year, ':month' => $month, ':week' => $week));
			if (!(empty($bill)) && empty($openid)) 
			{
				return array('ordermoney' => round($bill['ordermoney'], 2), 'ordercount' => $bill['ordercount'], 'bonusmoney1' => round($bill['bonusmoney1'], 2), 'bonusmoney_send1' => round($bill['bonusmoney_send1'], 2), 'bonusmoney2' => round($bill['bonusmoney2'], 2), 'bonusmoney_send2' => round($bill['bonusmoney_send2'], 2), 'bonusmoney3' => round($bill['bonusmoney3'], 2), 'bonusmoney_send3' => round($bill['bonusmoney_send3'], 2), 'aagentcount1' => $bill['aagentcount1'], 'aagentcount2' => $bill['aagentcount2'], 'aagentcount3' => $bill['aagentcount3'], 'starttime' => $starttime, 'endtime' => $endtime, 'billid' => $bill['id'], 'old' => true);
			}
			$ordermoney = 0;
			$bonusmoney = 0;

			//获取该时间段中所有订单
			$orders = pdo_fetchall('select id,openid,price,addressid, address from ' . tablename('wx_shop_order') . ' where uniacid=' . $_W['uniacid'] . ' and addressid<>0 and status=3 and isabonus=0 and finishtime + ' . $settletimes . '>= ' . $starttime . ' and  finishtime + ' . $settletimes . '<=' . $endtime, array(), 'id');
			$pcondition = '';
			if (!(empty($openid))) 
			{
				$pcondition = ' and m.openid=\'' . $openid . '\'';
			}

			//获取所有区域代理
			$aagents = pdo_fetchall('select m.id,m.nickname,m.openid,m.aagentlevel,m.aagenttype,m.aagentprovinces,m.aagentcitys,m.aagentareas, l.bonus1,l.bonus2,l.bonus3 from ' . tablename('wx_shop_member') . ' m ' . '  left join ' . tablename('wx_shop_abonus_level') . ' l on l.id = m.aagentlevel ' . '  where m.uniacid=:uniacid and  m.isaagent=1 and m.aagentstatus=1 ' . $pcondition, array(':uniacid' => $_W['uniacid']));
			$aagentcount1 = 0;
			$aagentcount2 = 0;
			$aagentcount3 = 0;

			foreach ($aagents as &$a ) 
			{
				if (empty($a['aagentlevel']) || (($a['bonus1'] == NULL) && ($a['bonus2'] == NULL) && ($a['bonus3'] == NULL))) 
				{
					$a['bonus1'] = floatval($set['bonus1']);
					$a['bonus2'] = floatval($set['bonus2']);
					$a['bonus3'] = floatval($set['bonus3']);
				}
				$a['aagentprovinces'] = iunserializer($a['aagentprovinces']);
				$a['aagentareas'] = iunserializer($a['aagentareas']);
				$a['aagentcitys'] = iunserializer($a['aagentcitys']);
				if ($a['aagenttype'] == 1) 
				{
					++$aagentcount1;
				}
				else if ($a['aagenttype'] == 2) 
				{
					++$aagentcount2;
				}
				else if ($a['aagenttype'] == 3) 
				{
					++$aagentcount3;
				}
				$a['bonusmoney1'] = 0;
				$a['bonusmoney2'] = 0;
				$a['bonusmoney3'] = 0;
			}
			unset($a);

			foreach ($orders as $o ) 
			{
				$ordermoney += $o['price'];
				foreach ($aagents as &$a ) 
				{
					if (empty($set['selfbuy'])) 
					{
						if ($a['openid'] == $o['openid']) 
						{
							continue;
						}
					}
					!(isset($a['bonusmoney1'])) && ($a['bonusmoney1'] = 0);
					!(isset($a['bonusmoney2'])) && ($a['bonusmoney2'] = 0);
					!(isset($a['bonusmoney3'])) && ($a['bonusmoney3'] = 0);
					$address = iunserializer($o['address']);
					if (!(is_array($address)))
					{
						$address = pdo_fetch('select * from ' . tablename('wx_shop_member_address') . ' where id=:id limit 1', array(':id' => $o['addressid']));
					}
					if (empty($address) || !(is_array($address))) 
					{
						continue;
					}
					$bonusmoney1 = 0;
					$bonusmoney2 = 0;
					$bonusmoney3 = 0;

					if ($a['aagenttype'] == 1) 
					{
					    //省级区域代理
						if (in_array($address['province'], $a['aagentprovinces'])) 
						{
							$bonusmoney1 = round(($o['price'] * $a['bonus1']) / 100, 2);
						}
						if (in_array($address['province'] . $address['city'], $a['aagentcitys'])) 
						{
							$bonusmoney2 = round(($o['price'] * $a['bonus2']) / 100, 2);
						}
						if (in_array($address['province'] . $address['city'] . $address['area'], $a['aagentareas'])) 
						{
							$bonusmoney3 = round(($o['price'] * $a['bonus3']) / 100, 2);
						}
					}
					else if ($a['aagenttype'] == 2) 
					{
					    //市级区域代理
						if (in_array($address['province'] . $address['city'], $a['aagentcitys'])) 
						{
							$bonusmoney2 = round(($o['price'] * $a['bonus2']) / 100, 2);
						}
						if (in_array($address['province'] . $address['city'] . $address['area'], $a['aagentareas'])) 
						{
							$bonusmoney3 = round(($o['price'] * $a['bonus3']) / 100, 2);
						}
					}
					else if ($a['aagenttype'] == 3) 
					{
					    //区级区域代理
						if (in_array($address['province'] . $address['city'] . $address['area'], $a['aagentareas'])) 
						{
							$bonusmoney3 = round(($o['price'] * $a['bonus3']) / 100, 2);
						}
					}
					$a['bonusmoney1'] += $bonusmoney1;
					$a['bonusmoney2'] += $bonusmoney2;
					$a['bonusmoney3'] += $bonusmoney3;
				}
				unset($a);
			}

			$bonusmoney = 0;
			$bonusmoney1 = 0;
			$bonusmoney2 = 0;
			$bonusmoney3 = 0;

			//计算手续费等杂七杂八
			foreach ($aagents as &$a ) 
			{
				$bonusmoney_send = 0;
				$bonusmoney_send1 = 0;
				$bonusmoney_send2 = 0;
				$bonusmoney_send3 = 0;
				$a['charge'] = 0;
				$a['chargemoney'] = 0;
				if ((floatval($set['paycharge']) <= 0) || ((floatval($set['paybegin']) <= $a['bonusmoney1'] + $a['bonusmoney2'] + $a['bonusmoney3']) && ($a['bonusmoney'] <= floatval($set['payend'])))) 
				{
					$bonusmoney_send1 += round($a['bonusmoney1'], 2);
					$bonusmoney_send2 += round($a['bonusmoney2'], 2);
					$bonusmoney_send3 += round($a['bonusmoney3'], 2);
				}
				else 
				{
					$bonusmoney_send1 += round($a['bonusmoney1'] - (($a['bonusmoney1'] * floatval($set['paycharge'])) / 100), 2);
					$bonusmoney_send2 += round($a['bonusmoney2'] - (($a['bonusmoney2'] * floatval($set['paycharge'])) / 100), 2);
					$bonusmoney_send3 += round($a['bonusmoney3'] - (($a['bonusmoney3'] * floatval($set['paycharge'])) / 100), 2);
					$a['charge'] = floatval($set['paycharge']);
					$a['chargemoney1'] = round(($a['bonusmoney1'] * floatval($set['paycharge'])) / 100, 2);
					$a['chargemoney2'] = round(($a['bonusmoney2'] * floatval($set['paycharge'])) / 100, 2);
					$a['chargemoney3'] = round(($a['bonusmoney3'] * floatval($set['paycharge'])) / 100, 2);
				}
				$a['bonusmoney_send1'] = $bonusmoney_send1;
				$a['bonusmoney_send2'] = $bonusmoney_send2;
				$a['bonusmoney_send3'] = $bonusmoney_send3;
				$bonusmoney1 += $bonusmoney_send1;
				$bonusmoney2 += $bonusmoney_send2;
				$bonusmoney3 += $bonusmoney_send3;
			}
			unset($p);

//			print_r(array('orders' => $orders, 'aagents' => $aagents, 'ordermoney' => round($ordermoney, 2), 'ordercount' => count($orders), 'bonusmoney1' => round($bonusmoney1, 2), 'bonusmoney2' => round($bonusmoney2, 2), 'bonusmoney3' => round($bonusmoney3, 2), 'aagentcount1' => $aagentcount1, 'aagentcount2' => $aagentcount2, 'aagentcount3' => $aagentcount3, 'starttime' => $starttime, 'endtime' => $endtime, 'old' => false));
//			exit();

			return array('orders' => $orders, 'aagents' => $aagents, 'ordermoney' => round($ordermoney, 2), 'ordercount' => count($orders), 'bonusmoney1' => round($bonusmoney1, 2), 'bonusmoney2' => round($bonusmoney2, 2), 'bonusmoney3' => round($bonusmoney3, 2), 'aagentcount1' => $aagentcount1, 'aagentcount2' => $aagentcount2, 'aagentcount3' => $aagentcount3, 'starttime' => $starttime, 'endtime' => $endtime, 'old' => false);
		}


		public function getTotals() 
		{
			global $_W;
			return array('total0' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_abonus_bill') . ' where uniacid=:uniacid and status=0 limit 1', array(':uniacid' => $_W['uniacid'])), 'total1' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_abonus_bill') . ' where uniacid=:uniacid and status=1 limit 1', array(':uniacid' => $_W['uniacid'])), 'total2' => pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_abonus_bill') . ' where uniacid=:uniacid and status=2  limit 1', array(':uniacid' => $_W['uniacid'])));
		}
	}
}
?>
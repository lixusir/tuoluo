<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
define('TM_CREDITSHOP_LOTTERY', 'TM_CREDITSHOP_LOTTERY');
define('TM_CREDITSHOP_EXCHANGE', 'TM_CREDITSHOP_EXCHANGE');
define('TM_CREDITSHOP_WIN', 'TM_CREDITSHOP_WIN');
if (!(class_exists('CreditshopModel'))) 
{
	class CreditshopModel extends PluginModel 
	{
		public function dispatch($addressid, $goods) 
		{
			$dispatch = 0;
			$dispatch_array = array();
			if ($goods['dispatchtype'] == 0) 
			{
				$dispatch = $goods['dispatch'];
			}
			else 
			{
				$merchid = $goods['merchid'];
				if (empty($goods['dispatchid'])) 
				{
					$dispatch_data = m('dispatch')->getDefaultDispatch($merchid);
				}
				else 
				{
					$dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid']);
				}
				if (empty($dispatch_data)) 
				{
					$dispatch_data = m('dispatch')->getNewDispatch($merchid);
				}
				if (!(empty($dispatch_data))) 
				{
					$dkey = $dispatch_data['id'];
					if (!(empty($user_city))) 
					{
						$citys = m('dispatch')->getAllNoDispatchAreas($dispatch_data['nodispatchareas']);
						if (!(empty($citys))) 
						{
							if (in_array($user_city, $citys) && !(empty($citys))) 
							{
								$isnodispatch = 1;
								$has_goodsid = 0;
								if (!(empty($nodispatch_array['goodid']))) 
								{
									if (in_array($goods['goodsid'], $nodispatch_array['goodid'])) 
									{
										$has_goodsid = 1;
									}
								}
								if ($has_goodsid == 0) 
								{
									$nodispatch_array['goodid'][] = $goods['id'];
									$nodispatch_array['title'][] = $goods['title'];
									$nodispatch_array['city'] = $user_city;
								}
							}
						}
					}
					if (($goods['isverify'] == 0) && ($goods['goodstype'] == 0)) 
					{
						$areas = unserialize($dispatch_data['areas']);
						if ($dispatch_data['calculatetype'] == 1) 
						{
							$param = 1;
						}
						else 
						{
							$param = $goods['weight'] * 1;
						}
						if (array_key_exists($dkey, $dispatch_array)) 
						{
							$dispatch_array[$dkey]['param'] += $param;
						}
						else 
						{
							$dispatch_array[$dkey]['data'] = $dispatch_data;
							$dispatch_array[$dkey]['param'] = $param;
						}
					}
				}
				$dispatch_merch = array();
				if (!(empty($dispatch_array))) 
				{
					foreach ($dispatch_array as $k => $v ) 
					{
						$dispatch_data = $dispatch_array[$k]['data'];
						$param = $dispatch_array[$k]['param'];
						$areas = unserialize($dispatch_data['areas']);
						if (!(empty($address))) 
						{
							$dprice = m('dispatch')->getCityDispatchPrice($areas, $address['city'], $param, $dispatch_data);
						}
						else if (!(empty($member['city']))) 
						{
							$dprice = m('dispatch')->getCityDispatchPrice($areas, $member['city'], $param, $dispatch_data);
						}
						else 
						{
							$dprice = m('dispatch')->getDispatchPrice($param, $dispatch_data);
						}
						$dispatch = $dprice;
					}
				}
			}
			return $dispatch;
		}
		public function payResult($logno, $type, $total_fee, $app = false) 
		{
			global $_W;
			$uniacid = $_W['uniacid'];
			$log = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_creditshop_log') . "\r\n\t\t" . '    WHERE `uniacid`=:uniacid AND `logno`=:logno limit 1', array(':uniacid' => $uniacid, ':logno' => $logno));
			$member = m('member')->getMember($log['openid']);
			$goods = $this->getGoods($log['goodsid'], $member, $log['optionid']);
			$goods['dispatch'] = $this->dispatchPrice($log['goodsid'], $log['addressid'], $log['optionid']);
			if (0 < $log['status']) 
			{
				return true;
			}
			$record = array();
			$record['paystatus'] = 1;
			if ($type == 'wechat') 
			{
				$record['paytype'] = 1;
			}
			else if ($type == 'alipay') 
			{
				$record['paytype'] = 2;
			}
			if (!(empty($log)) && ($log['paystatus'] < 1)) 
			{
				if (!(empty($goods)) && ($total_fee == $goods['money'] + $goods['dispatch'])) 
				{
					pdo_update('wx_shop_creditshop_log', $record, array('id' => $log['id']));
				}
			}
		}
		public function dispatchPrice($goodsid, $addressid, $optionid = 0) 
		{
			global $_W;
			global $_GPC;
			$openid = $_W['openid'];
			$uniacid = $_W['uniacid'];
			$member = m('member')->getMember($openid);
			$goods = $this->getGoods($goodsid, $member, $optionid);
			$dispatch = 0;
			$dispatch_array = array();
			$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('wx_shop_member_address') . "\r\n" . '        where id=:id and uniacid=:uniacid limit 1', array(':id' => $addressid, ':uniacid' => $_W['uniacid']));
			if ($goods['dispatchtype'] == 0) 
			{
				$dispatch = $goods['dispatch'];
			}
			else 
			{
				$merchid = $goods['merchid'];
				if (empty($goods['dispatchid'])) 
				{
					$dispatch_data = m('dispatch')->getDefaultDispatch($merchid);
				}
				else 
				{
					$dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid']);
				}
				if (empty($dispatch_data)) 
				{
					$dispatch_data = m('dispatch')->getNewDispatch($merchid);
				}
				if (!(empty($dispatch_data))) 
				{
					$dkey = $dispatch_data['id'];
					if (!(empty($user_city))) 
					{
						$citys = m('dispatch')->getAllNoDispatchAreas($dispatch_data['nodispatchareas']);
						if (!(empty($citys))) 
						{
							if (in_array($user_city, $citys) && !(empty($citys))) 
							{
								$isnodispatch = 1;
								$has_goodsid = 0;
								if (!(empty($nodispatch_array['goodid']))) 
								{
									if (in_array($goods['goodsid'], $nodispatch_array['goodid'])) 
									{
										$has_goodsid = 1;
									}
								}
								if ($has_goodsid == 0) 
								{
									$nodispatch_array['goodid'][] = $goods['id'];
									$nodispatch_array['title'][] = $goods['title'];
									$nodispatch_array['city'] = $user_city;
								}
							}
						}
					}
					if (($goods['isverify'] == 0) && ($goods['goodstype'] == 0)) 
					{
						$areas = unserialize($dispatch_data['areas']);
						if ($dispatch_data['calculatetype'] == 1) 
						{
							$param = 1;
						}
						else 
						{
							$param = $goods['weight'] * 1;
						}
						if (array_key_exists($dkey, $dispatch_array)) 
						{
							$dispatch_array[$dkey]['param'] += $param;
						}
						else 
						{
							$dispatch_array[$dkey]['data'] = $dispatch_data;
							$dispatch_array[$dkey]['param'] = $param;
						}
					}
				}
				$dispatch_merch = array();
				if (!(empty($dispatch_array))) 
				{
					foreach ($dispatch_array as $k => $v ) 
					{
						$dispatch_data = $dispatch_array[$k]['data'];
						$param = $dispatch_array[$k]['param'];
						$areas = unserialize($dispatch_data['areas']);
						if (!(empty($address))) 
						{
							$dprice = m('dispatch')->getCityDispatchPrice($areas, $address, $param, $dispatch_data);
						}
						else if (!(empty($member['city']))) 
						{
							$dprice = m('dispatch')->getCityDispatchPrice($areas, $member, $param, $dispatch_data);
						}
						else 
						{
							$dprice = m('dispatch')->getDispatchPrice($param, $dispatch_data);
						}
						$dispatch = $dprice;
					}
				}
			}
			return $dispatch;
		}
		public function packetmoney($goodsid) 
		{
			global $_W;
			global $_GPC;
			$uniacid = $_W['uniacid'];
			$money = 0;
			$goods = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_goods') . ' where id = ' . $goodsid . ' and uniacid = ' . $uniacid . ' ');
			$size = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_creditshop_log') . ' where goodsid = ' . $goodsid . ' and uniacid = ' . $uniacid . ' and status = 2 ');
			if (!($goods)) 
			{
				return array('status' => 0, 'message' => '??????????????????');
			}
			if ($goods['packettype'] == 1) 
			{
				$MoneyPackage = array('remainSize' => $goods['packetsurplus'] + $size, 'remainMoney' => $goods['surplusmoney']);
				if ($MoneyPackage['remainSize'] == 1) 
				{
					--$MoneyPackage['remainSize'];
					return array('status' => 1, 'money' => intval($MoneyPackage['remainMoney'] * 100) / 100);
				}
				$min = $goods['minpacketmoney'];
				$max = $MoneyPackage['remainMoney'] - ($goods['minpacketmoney'] * ($MoneyPackage['remainSize'] - 1));
				$money = $min + ((mt_rand() / mt_getrandmax()) * ($max - $min));
				if ($money <= $min) 
				{
					$money = $min;
				}
				else 
				{
					$money = $money;
				}
				$money = round($money * 100, 0) / 100;
			}
			else 
			{
				$money = $goods['grant2'];
			}
			return array('status' => 1, 'money' => $money);
		}
		public function getGoods($id, $member, $optionid = 0) 
		{
			global $_W;
			$credit = $member['credit1'];
			$money = $member['credit2'];
			$optionid = intval($optionid);
			$merchid = $_W['merchid'];
			$condition = ' and uniacid=:uniacid ';
			if (0 < $merchid) 
			{
				$condition .= ' and merchid = ' . $merchid . ' ';
			}
			if (empty($id)) 
			{
				return;
			}
			$goods = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_goods') . ' where id=:id ' . $condition . ' limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
			if (empty($goods)) 
			{
				return false;
			}
			if (!(empty($goods['status'])) && empty($goods['status'])) 
			{
				return array('canbuy' => false, 'buymsg' => '?????????');
			}
			$goods = set_medias($goods, 'thumb');
			if ((0 < $goods['credit']) && (0 < $goods['money'])) 
			{
				$goods['acttype'] = 0;
			}
			else if (0 < $goods['credit']) 
			{
				$goods['acttype'] = 1;
			}
			else if (0 < $goods['money']) 
			{
				$goods['acttype'] = 2;
			}
			else 
			{
				$goods['acttype'] = 3;
			}
			if (intval($goods['isendtime']) == 1) 
			{
				$goods['endtime_str'] = date('Y-m-d H:i', $goods['endtime']);
			}
			$goods['timestart_str'] = date('Y-m-d H:i', $goods['timestart']);
			$goods['timeend_str'] = date('Y-m-d H:i', $goods['timeend']);
			$goods['timestate'] = '';
			$goods['canbuy'] = !(empty($goods['status'])) && empty($goods['deleted']);
			if (empty($goods['canbuy'])) 
			{
				$goods['buymsg'] = '?????????';
			}
			else 
			{
				if ($goods['goodstype'] == 3) 
				{
					if (($goods['packetsurplus'] <= 0) || ($goods['surplusmoney'] <= $goods['packetlimit'])) 
					{
						$goods['canbuy'] = false;
						$goods['buymsg'] = ((empty($goods['type']) ? '?????????' : '?????????'));
					}
				}
				else if (0 < $goods['total']) 
				{
					$logcount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_creditshop_log') . '  where goodsid=:goodsid and status>=2  and uniacid=:uniacid  ', array(':goodsid' => $id, ':uniacid' => $_W['uniacid']));
					$goods['logcount'] = $logcount;
					if ($goods['joins'] < $logcount) 
					{
						pdo_update('wx_shop_creditshop_goods', array('joins' => $logcount), array('id' => $id));
					}
				}
				else 
				{
					$goods['canbuy'] = false;
					$goods['buymsg'] = ((empty($goods['type']) ? '?????????' : '?????????'));
				}
				if ($goods['hasoption'] && $optionid) 
				{
					$option = pdo_fetch('select total,credit,money,title as optiontitle,weight from ' . tablename('wx_shop_creditshop_option') . ' where uniacid = ' . $_W['uniacid'] . ' and id = ' . $optionid . ' and goodsid = ' . $id . ' ');
					$goods['credit'] = $option['credit'];
					$goods['money'] = $option['money'];
					$goods['weight'] = $option['weight'];
					$goods['total'] = $option['total'];
					$goods['optiontitle'] = $option['optiontitle'];
					if ($option['total'] <= 0) 
					{
						$goods['canbuy'] = false;
						$goods['buymsg'] = ((empty($goods['type']) ? '?????????' : '?????????'));
					}
				}
				if ($goods['isverify'] == 0) 
				{
					if ($goods['dispatchtype'] == 1) 
					{
						if (empty($goods['dispatchid'])) 
						{
							$dispatch = m('dispatch')->getDefaultDispatch($goods['merchid']);
						}
						else 
						{
							$dispatch = m('dispatch')->getOneDispatch($goods['dispatchid']);
						}
						if (empty($dispatch)) 
						{
							$dispatch = m('dispatch')->getNewDispatch($goods['merchid']);
						}
						$areas = iunserializer($dispatch['areas']);
						if (!(empty($areas)) && is_array($areas)) 
						{
							$firstprice = array();
							foreach ($areas as $val ) 
							{
								$firstprice[] = $val['firstprice'];
							}
							array_push($firstprice, m('dispatch')->getDispatchPrice(1, $dispatch));
							$ret = array('min' => round(min($firstprice), 2), 'max' => round(max($firstprice), 2));
							$goods['areas'] = $ret;
						}
						else 
						{
							$ret = m('dispatch')->getDispatchPrice(1, $dispatch);
						}
						$goods['dispatch'] = $ret;
					}
				}
				else 
				{
					$goods['dispatch'] = 0;
				}
				if ($goods['canbuy']) 
				{
					if (0 < $goods['totalday']) 
					{
						$logcount = pdo_fetchcolumn('select count(*)  from ' . tablename('wx_shop_creditshop_log') . '  where goodsid=:goodsid and status>=2 and  date_format(from_UNIXTIME(`createtime`),\'%Y-%m-%d\') = date_format(now(),\'%Y-%m-%d\') and uniacid=:uniacid  ', array(':goodsid' => $id, ':uniacid' => $_W['uniacid']));
						if ($goods['totalday'] <= $logcount) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = ((empty($goods['type']) ? '???????????????' : '???????????????'));
						}
					}
				}
				if ($goods['canbuy']) 
				{
					if (0 < $goods['chanceday']) 
					{
						$logcount = pdo_fetchcolumn('select count(*)  from ' . tablename('wx_shop_creditshop_log') . '  where goodsid=:goodsid and openid=:openid and status>0 and  date_format(from_UNIXTIME(`createtime`),\'%Y-%m-%d\') = date_format(now(),\'%Y-%m-%d\') and uniacid=:uniacid  ', array(':goodsid' => $id, ':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
						if ($goods['chanceday'] <= $logcount) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = ((empty($goods['type']) ? '???????????????' : '???????????????'));
						}
					}
				}
				if ($goods['canbuy']) 
				{
					if (0 < $goods['chance']) 
					{
						$logcount = pdo_fetchcolumn('select count(*)  from ' . tablename('wx_shop_creditshop_log') . '  where goodsid=:goodsid and openid=:openid and status>0 and  uniacid=:uniacid  ', array(':goodsid' => $id, ':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
						if ($goods['chance'] <= $logcount) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = ((empty($goods['type']) ? '?????????' : '?????????'));
						}
					}
				}
				if ($goods['canbuy']) 
				{
					if (0 < $goods['usermaxbuy']) 
					{
						$logcount = pdo_fetchcolumn('select ifnull(sum(total),0)  from ' . tablename('wx_shop_creditshop_log') . '  where goodsid=:goodsid and openid=:openid  and uniacid=:uniacid ', array(':goodsid' => $id, ':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
						if ($goods['chance'] <= $logcount) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = '?????????';
						}
					}
				}
				if ($goods['canbuy']) 
				{
					if (($credit < $goods['credit']) && (0 < $goods['credit'])) 
					{
						$goods['canbuy'] = false;
						$goods['buymsg'] = '????????????';
					}
				}
				if ($goods['canbuy']) 
				{
					if ($goods['istime'] == 1) 
					{
						if (time() < $goods['timestart']) 
						{
							$goods['canbuy'] = false;
							$goods['timestate'] = 'before';
							$goods['buymsg'] = '???????????????';
						}
						else if ($goods['timeend'] < time()) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = '???????????????';
						}
						else 
						{
							$goods['timestate'] = 'after';
						}
					}
				}
				if ($goods['canbuy']) 
				{
					if (($goods['isendtime'] == 1) && $goods['isverify']) 
					{
						if ($goods['endtime'] < time()) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = '???????????????(???????????????)';
						}
					}
				}
				$levelid = $member['level'];
				$groupid = $member['groupid'];
				if ($goods['canbuy']) 
				{
					if ($goods['buylevels'] != '') 
					{
						$buylevels = explode(',', $goods['buylevels']);
						if (!(in_array($levelid, $buylevels))) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = '???????????????';
						}
					}
				}
				if ($goods['canbuy']) 
				{
					if ($goods['buygroups'] != '') 
					{
						$buygroups = explode(',', $goods['buygroups']);
						if (!(in_array($groupid, $buygroups))) 
						{
							$goods['canbuy'] = false;
							$goods['buymsg'] = '???????????????';
						}
					}
				}
			}
			$goods['followtext'] = ((empty($goods['followtext']) ? '????????????????????????????????????????????????????????????!' : $goods['followtext']));
			$set = $this->getSet();
			$goods['followurl'] = $set['followurl'];
			if (empty($goods['followurl'])) 
			{
				$share = m('common')->getSysset('share');
				$goods['followurl'] = $share['followurl'];
			}
			if ((intval($goods['money']) - $goods['money']) == 0) 
			{
				$goods['money'] = intval($goods['money']);
			}
			if ((intval($goods['minmoney']) - $goods['minmoney']) == 0) 
			{
				$goods['minmoney'] = intval($goods['minmoney']);
			}
			if ((intval($goods['minmoney']) - $goods['minmoney']) == 0) 
			{
				$goods['minmoney'] = intval($goods['minmoney']);
			}
			return $goods;
		}
		public function createENO() 
		{
			global $_W;
			$ecount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_creditshop_log') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
			if ($ecount < 99999999) 
			{
				$ecount = 8;
			}
			else 
			{
				$ecount = strlen($ecount . '');
			}
			$eno = rand(pow(10, $ecount), pow(10, $ecount + 1) - 1);
			while (1) 
			{
				$c = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_creditshop_log') . ' where uniacid=:uniacid and eno=:eno limit 1', array(':uniacid' => $_W['uniacid'], ':eno' => $eno));
				if ($c <= 0) 
				{
					break;
				}
				$eno = rand(pow(10, $ecount), pow(10, $ecount + 1) - 1);
			}
			return $eno;
		}
		public function sendMessage($id = 0) 
		{
			global $_W;
			if (empty($id)) 
			{
				return;
			}
			$log = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
			if (empty($log)) 
			{
				return;
			}
			$member = m('member')->getMember($log['openid']);
			if (empty($member)) 
			{
				return;
			}
			$credit = intval($member['credit1']);
			$goods = $this->getGoods($log['goodsid'], $member);
			if (empty($goods['id'])) 
			{
				return;
			}
			if (0 < $log['optionid']) 
			{
				$goods_option = pdo_fetch('select credit,money from ' . tablename('wx_shop_creditshop_option') . ' where id = :optionid and uniacid = :uniacid ', array(':optionid' => $log['optionid'], ':uniacid' => $_W['uniacid']));
				$goods['credit'] = $goods_option['credit'];
				$goods['money'] = $goods_option['money'];
			}
			$type = $goods['type'];
			$credits = '';
			if ((0 < $goods['credit']) & (0 < $goods['money'])) 
			{
				$credits = $goods['credit'] . '??????+' . $goods['money'] . '???';
			}
			else if (0 < $goods['credit']) 
			{
				$credits = $goods['credit'] . '??????';
			}
			else if (0 < $goods['money']) 
			{
				$credits = $goods['money'] . '???';
			}
			else 
			{
				$credits = '0';
			}
			$shop = m('common')->getSysset('shop');
			$set = $this->getSet();
			$tm = $set['tm'];
			$detailurl = mobileUrl('creditshop/log/detail', array('id' => $id), true);
			if (strexists($detailurl, '/addons/wx_shop/'))
			{
				$detailurl = str_replace('/addons/wx_shop/', '/', $detailurl);
			}
			if ($log['status'] == 2) 
			{
				if (!(empty($type))) 
				{
					if ($log['status'] == 2) 
					{
						$remark = "\r\n" . ' ???' . $shop['name'] . '???????????????????????????';
						if (($goods['goodstype'] == 0) && ($goods['isverify'] == 0)) 
						{
							if (0 < $goods['dispatch']) 
							{
								$remark = "\r\n" . ' ???????????????????????????, ???????????????????????????' . $shop['name'] . '???????????????????????????';
							}
							else 
							{
								$remark = "\r\n" . ' ?????????????????????????????????, ???????????????????????????' . $shop['name'] . '???????????????????????????';
							}
						}
						$msg = array( 'first' => array('value' => '????????????????????????~', 'color' => '#4a5077'), 'keyword1' => array('title' => '????????????', 'value' => '???' . $shop['name'] . '?????????', 'color' => '#4a5077'), 'keyword2' => array('title' => '????????????', 'value' => date('Y-m-d H:i', time()), 'color' => '#4a5077'), 'remark' => array('value' => $remark, 'color' => '#4a5077') );
						if (!(empty($tm['award']))) 
						{
							m('message')->sendTplNotice($log['openid'], $tm['award'], $msg, $detailurl);
						}
						else 
						{
							m('message')->sendCustomNotice($log['openid'], $msg, $detailurl);
						}
					}
				}
				else if ($log['dispatchstatus'] != 1) 
				{
					$remark = "\r\n" . ' ???' . $shop['name'] . '???????????????????????????';
					if ($log['dispatchstatus'] != -1) 
					{
						if (0 < $goods['dispatch']) 
						{
							$remark = "\r\n" . ' ???????????????????????????, ???????????????????????????' . $shop['name'] . '???????????????????????????';
						}
						else 
						{
							$remark = "\r\n" . ' ?????????????????????????????????, ???????????????????????????' . $shop['name'] . '???????????????????????????';
						}
					}
					$msg = array( 'first' => array('value' => '??????????????????????????????~', 'color' => '#4a5077'), 'keyword1' => array('title' => '????????????', 'value' => $goods['title'], 'color' => '#4a5077'), 'keyword2' => array('title' => '????????????', 'value' => $credits, 'color' => '#4a5077'), 'keyword3' => array('title' => '????????????', 'value' => $credit, 'color' => '#4a5077'), 'keyword4' => array('title' => '????????????', 'value' => date('Y-m-d H:i', time()), 'color' => '#4a5077'), 'remark' => array('value' => $remark, 'color' => '#4a5077') );
					if (!(empty($tm['exchange']))) 
					{
						m('message')->sendTplNotice($log['openid'], $tm['exchange'], $msg, $detailurl);
					}
					else 
					{
						m('message')->sendCustomNotice($log['openid'], $msg, $detailurl);
					}
				}
				if (($log['dispatchstatus'] == 1) || ($log['dispatchstatus'] == -1)) 
				{
					$remark = '????????????:  ????????????';
					if (!(empty($log['addressid']))) 
					{
						$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('wx_shop_member_address') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $log['addressid'], ':uniacid' => $_W['uniacid']));
						if (!(empty($address))) 
						{
							$remark = '?????????: ' . $address['realname'] . ' ????????????: ' . $address['mobile'] . ' ????????????: ' . $address['province'] . $address['city'] . $address['area'] . ' ' . $address['address'];
						}
						$remark .= ', ???????????????,??????!';
					}
					$msg = array( 'first' => array('value' => '??????????????????????????????~', 'color' => '#4a5077'), 'keyword1' => array('title' => '????????????', 'value' => $goods['title'], 'color' => '#4a5077'), 'keyword2' => array('title' => '????????????', 'value' => $credits, 'color' => '#4a5077'), 'keyword3' => array('title' => '????????????', 'value' => date('Y-m-d H:i', $log['createtime']), 'color' => '#4a5077'), 'keyword4' => array('title' => '?????????', 'value' => ($goods['isverify'] ? '?????????' : '????????????'), 'color' => '#4a5077'), 'remark' => array('value' => $remark, 'color' => '#4a5077') );
					$noticeopenids = explode(',', $goods['noticeopenid']);
					if (empty($goods['noticeopenid'])) 
					{
						$noticeopenids = explode(',', $set['tm']['openids']);
					}
					if (!(empty($noticeopenids))) 
					{
						foreach ($noticeopenids as $noticeopenid ) 
						{
							if (!(empty($tm['new']))) 
							{
								m('message')->sendTplNotice($noticeopenid, $tm['new'], $msg);
							}
							else 
							{
								m('message')->sendCustomNotice($noticeopenid, $msg);
							}
						}
					}
				}
			}
			else if ($log['status'] == 3) 
			{
				$info = '????????????';
				if (!(empty($log['addressid']))) 
				{
					$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('wx_shop_member_address') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $log['addressid'], ':uniacid' => $_W['uniacid']));
					if (!(empty($address))) 
					{
						$info = ' ?????????: ' . $address['realname'] . ' ????????????: ' . $address['mobile'] . ' ????????????: ' . $address['province'] . $address['city'] . $address['area'] . ' ' . $address['address'];
					}
				}
				$msg = array( 'first' => array('value' => '?????????????????????????????????~', 'color' => '#4a5077'), 'keyword1' => array('title' => '????????????', 'value' => '?????? ' . $credits, 'color' => '#4a5077'), 'keyword2' => array('title' => '????????????', 'value' => $goods['title'], 'color' => '#4a5077'), 'keyword3' => array('title' => '????????????', 'value' => $info, 'color' => '#4a5077'), 'remark' => array('value' => $remark, 'color' => '#4a5077') );
				if (!(empty($tm['send']))) 
				{
					m('message')->sendTplNotice($log['openid'], $tm['send'], $msg, $detailurl);
				}
				else 
				{
					m('message')->sendCustomNotice($log['openid'], $msg, $detailurl);
				}
				$detailurl1 = mobileUrl('creditshop/detail', array('id' => $log['goodsid']), true);
				if (strexists($detailurl1, '/addons/wx_shop/'))
				{
					$detailurl1 = str_replace('/addons/wx_shop/', '/', $detailurl1);
				}
				$msg_saler = array( 'first' => array('value' => '????????????????????????~', 'color' => '#4a5077'), 'keyword1' => array('title' => '????????????', 'value' => '?????? ' . $credits, 'color' => '#4a5077'), 'keyword2' => array('title' => '????????????', 'value' => $goods['title'], 'color' => '#4a5077'), 'keyword3' => array('title' => '????????????', 'value' => $info, 'color' => '#4a5077'), 'remark' => array('value' => $remark, 'color' => '#4a5077') );
				if (!(empty($tm['send']))) 
				{
					m('message')->sendTplNotice($log['verifyopenid'], $tm['send'], $msg_saler, $detailurl1);
				}
				else 
				{
					m('message')->sendCustomNotice($log['verifyopenid'], $msg_saler, $detailurl1);
				}
			}
		}
		public function createQrcode($logid = 0) 
		{
			global $_W;
			global $_GPC;
			$path = IA_ROOT . '/addons/wx_shop/data/creditshop/' . $_W['uniacid'];
			if (!(is_dir($path))) 
			{
				load()->func('file');
				mkdirs($path);
			}
			$url = mobileUrl('creditshop/exchange', array('id' => $logid), true);
			$file = 'exchange_qrcode_' . $logid . '.png';
			$qrcode_file = $path . '/' . $file;
			if (!(is_file($qrcode_file))) 
			{
				require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
				QRcode::png($url, $qrcode_file, QR_ECLEVEL_H, 4);
			}
			return $_W['siteroot'] . '/addons/wx_shop/data/creditshop/' . $_W['uniacid'] . '/' . $file;
		}
		public function perms() 
		{
			return array( 'creditshop' => array( 'text' => $this->getName(), 'isplugin' => true, 'child' => array( 'cover' => array('text' => '????????????'), 'goods' => array('text' => '??????', 'view' => '??????', 'add' => '??????-log', 'edit' => '??????-log', 'delete' => '??????-log'), 'category' => array('text' => '??????', 'view' => '??????', 'add' => '??????-log', 'edit' => '??????-log', 'delete' => '??????-log'), 'adv' => array('text' => '?????????', 'view' => '??????', 'add' => '??????-log', 'edit' => '??????-log', 'delete' => '??????-log'), 'log' => array('text' => '????????????', 'view0' => '??????????????????', 'view1' => '??????????????????', 'exchange' => '????????????-log', 'export0' => '??????????????????-log', 'export1' => '??????????????????-log'), 'notice' => array('text' => '????????????', 'view' => '??????', 'save' => '??????-log'), 'set' => array('text' => '????????????', 'view' => '??????', 'save' => '??????-log') ) ) );
		}
		public function allow($logid, $times = 0, $verifycode = '', $openid = '') 
		{
			global $_W;
			global $_GPC;
			if (empty($openid)) 
			{
				$openid = $_W['openid'];
			}
			$merch_plugin = p('merch');
			$merch_data = m('common')->getPluginset('merch');
			if ($merch_plugin && $merch_data['is_openmerch']) 
			{
				$is_openmerch = 1;
			}
			else 
			{
				$is_openmerch = 0;
			}
			$uniacid = $_W['uniacid'];
			$store = false;
			$lastverifys = 0;
			$verifyinfo = false;
			if ($times <= 0) 
			{
				$times = 1;
			}
			$log = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_log') . ' where id=:id and uniacid=:uniacid  limit 1', array(':id' => $logid, ':uniacid' => $uniacid));
			$goods = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_goods') . ' where uniacid=:uniacid and id = :goodsid ', array(':uniacid' => $uniacid, ':goodsid' => $log['goodsid']));
			$merchid = intval($goods['merchid']);
			if (empty($merchid)) 
			{
				$saler = pdo_fetch('select * from ' . tablename('wx_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			else if ($merch_plugin) 
			{
				$saler = pdo_fetch('select * from ' . tablename('wx_shop_merch_saler') . ' where openid=:openid and uniacid=:uniacid and merchid=:merchid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid, ':merchid' => $merchid));
			}
			if (empty($saler)) 
			{
				return error(-1, '???????????????!');
			}
			if (empty($log)) 
			{
				return error(-1, '??????????????????!');
			}
			if (($log['verifytime'] < time()) && (0 < $log['verifytime'])) 
			{
				return error(-1, '???????????????????????????????????????!');
			}
			if (empty($goods)) 
			{
				return error(-1, '????????????!');
			}
			if (empty($goods['isverify'])) 
			{
				return error(-1, '??????????????????!');
			}
			$storeids = array();
			if (!(empty($goods['storeids']))) 
			{
				$storeids = explode(',', $goods['storeids']);
			}
			if (!(empty($storeids))) 
			{
				if (!(empty($saler['storeid']))) 
				{
					if (!(in_array($saler['storeid'], $storeids))) 
					{
						return error(-1, '??????????????????????????????!');
					}
				}
			}
			if ($goods['verifytype'] == 0) 
			{
				$verifynum = pdo_fetchcolumn('select COUNT(1) from ' . tablename('wx_shop_creditshop_verify') . ' where uniacid = :uniacid and logid = :logid ', array(':uniacid' => $uniacid, ':logid' => $logid));
				if (!(empty($verifynum))) 
				{
					return error(-1, '???????????????????????????');
					if ($goods['verifytype'] == 1) 
					{
						$verifynum = pdo_fetchcolumn('select COUNT(1) from ' . tablename('wx_shop_creditshop_verify') . ' where uniacid = :uniacid and logid = :logid ', array(':uniacid' => $uniacid, ':logid' => $logid));
						if ($goods['verifynum'] <= $verifynum) 
						{
							return error(-1, '???????????????????????????');
						}
						$lastverifys = $goods['verifynum'] - $verifynum;
						if (($lastverifys < 0) && !(empty($goods['verifytype']))) 
						{
							return error(-1, '????????????????????? ' . $goods['verifynum'] . ' ???!');
						}
					}
				}
			}
			else 
			{
				$verifynum = pdo_fetchcolumn('select COUNT(1) from ' . tablename('wx_shop_creditshop_verify') . ' where uniacid = :uniacid and logid = :logid ', array(':uniacid' => $uniacid, ':logid' => $logid));
				return error(-1, '???????????????????????????');
				$lastverifys = $goods['verifynum'] - $verifynum;
				return error(-1, '????????????????????? ' . $goods['verifynum'] . ' ???!');
			}
			if (!(empty($saler['storeid']))) 
			{
				if (0 < $merchid) 
				{
					$stores = pdo_fetch('select * from ' . tablename('wx_shop_merch_store') . ' where id=:id and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':id' => $saler['storeid'], ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
				}
				else 
				{
					$stores = pdo_fetch('select * from ' . tablename('wx_shop_store') . ' where id=:id and uniacid=:uniacid and status=1 and type in(2,3)', array(':id' => $saler['storeid'], ':uniacid' => $_W['uniacid']));
				}
			}
			$carrier = unserialize($log['carrier']);
			return array('log' => $log, 'store' => $store, 'saler' => $saler, 'lastverifys' => $lastverifys, 'goods' => $goods, 'verifyinfo' => $verifyinfo, 'carrier' => $carrier);
		}
		public function verify($logid = 0, $times = 0, $verifycode = '', $openid = '') 
		{
			global $_W;
			global $_GPC;
			$uniacid = $_W['uniacid'];
			$current_time = time();
			if (empty($openid)) 
			{
				$openid = $_W['openid'];
			}
			$merch_plugin = p('merch');
			$merch_data = m('common')->getPluginset('merch');
			if ($merch_plugin && $merch_data['is_openmerch']) 
			{
				$is_openmerch = 1;
			}
			else 
			{
				$is_openmerch = 0;
			}
			$data = $this->allow($logid, $times, $openid);
			if (is_error($data)) 
			{
				return;
			}
			extract($data);
			$log = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_log') . ' where id=:id and uniacid=:uniacid  limit 1', array(':id' => $logid, ':uniacid' => $uniacid));
			$goods = pdo_fetch('select * from ' . tablename('wx_shop_creditshop_goods') . ' where uniacid=:uniacid and id = :goodsid ', array(':uniacid' => $uniacid, ':goodsid' => $log['goodsid']));
			$merchid = intval($goods['merchid']);
			if (empty($merchid)) 
			{
				$saler = pdo_fetch('select * from ' . tablename('wx_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}
			else if ($merch_plugin) 
			{
				$saler = pdo_fetch('select * from ' . tablename('wx_shop_merch_saler') . ' where openid=:openid and uniacid=:uniacid and merchid=:merchid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid, ':merchid' => $merchid));
			}
			if ($goods['isverify']) 
			{
				if ($goods['verifytype'] == 0) 
				{
					pdo_update('wx_shop_creditshop_log', array('status' => 3, 'verifytime' => time(), 'verifyopenid' => $openid, 'time_finish' => time()), array('id' => $logid));
					$data = array('uniacid' => $uniacid, 'openid' => $log['openid'], 'logid' => $logid, 'verifycode' => $log['eno'], 'storeid' => $saler['storeid'], 'verifier' => $openid, 'isverify' => 1, 'verifytime' => time());
					pdo_insert('wx_shop_creditshop_verify', $data);
				}
				else if ($goods['verifytype'] == 1) 
				{
					if ($log['status'] != 3) 
					{
						pdo_update('wx_shop_creditshop_log', array('status' => 3, 'usetime' => time(), 'verifyopenid' => $openid, 'time_finish' => time()), array('id' => $logid));
					}
					$i = 1;
					while ($i <= $times) 
					{
						$data = array('uniacid' => $uniacid, 'openid' => $log['openid'], 'logid' => $logid, 'verifycode' => $log['eno'], 'storeid' => $saler['storeid'], 'verifier' => $openid, 'isverify' => 1, 'verifytime' => time());
						pdo_insert('wx_shop_creditshop_verify', $data);
						++$i;
					}
				}
			}
			return true;
		}
	}
}
?>
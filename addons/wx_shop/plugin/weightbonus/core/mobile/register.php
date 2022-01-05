<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'weightbonus/core/page_login_mobile.php';
class Register_WxShopPage extends WeightbonusMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$set = set_medias($this->set, 'regbg');
		$member = m('member')->getMember($openid);
		if (($member['isweight'] == 1) && ($member['weightstatus'] == 1)) {
			header('location: ' . mobileUrl('weightbonus'));
			exit();
		}

		if ($member['agentblack'] || $member['weightstatus']) {
			include $this->template();
			exit();
		}

		$apply_set = array();
		$apply_set['open_protocol'] = $set['open_protocol'];

		if (empty($set['applytitle'])) {
			$apply_set['applytitle'] = '代理申请协议';
		}
		else {
			$apply_set['applytitle'] = $set['applytitle'];
		}

		$template_flag = 0;
		$diyform_plugin = p('diyform');

		if ($diyform_plugin) {
			$set_config = $diyform_plugin->getSet();
			$weightbonus_diyform_open = $set_config['weightbonus_diyform_open'];

			if ($weightbonus_diyform_open == 1) {
				$template_flag = 1;
				$diyform_id = $set_config['weightbonus_diyform'];

				if (!empty($diyform_id)) {
					$formInfo = $diyform_plugin->getDiyformInfo($diyform_id);
					$fields = $formInfo['fields'];
					$diyform_data = iunserializer($member['diyweightbonusdata']);
					$f_data = $diyform_plugin->getDiyformData($diyform_data, $fields, $member);
				}
			}
		}

		if ($_W['ispost']) {
			if ($set['become'] != '1') {
				show_json(0, '未开启' . $set['texts']['weight'] . '注册!');
			}

			$become_check = intval($set['become_check']);
			$ret['status'] = $become_check;

			if ($template_flag == 1) {
				$memberdata = $_GPC['memberdata'];
				$insert_data = $diyform_plugin->getInsertData($fields, $memberdata);
				$data = $insert_data['data'];
				$m_data = $insert_data['m_data'];
				$mc_data = $insert_data['mc_data'];
				$m_data['diyweightbonusid'] = $diyform_id;
				$m_data['diyweightbonusfields'] = iserializer($fields);
				$m_data['diyweightbonusdata'] = $data;
				$m_data['isweight'] = 1;
				$m_data['weightstatus'] = $become_check;
				$m_data['weighttime'] = $become_check == 1 ? time() : 0;
				unset($m_data['credit1']);
				unset($m_data['credit2']);
				pdo_update('wx_shop_member', $m_data, array('id' => $member['id']));

				if ($become_check == 1) {
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'weighttime' => $m_data['weighttime']), TM_WEIGHTBONUS_BECOME);
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
				$data = array('isweight' => 1, 'weightstatus' => $become_check, 'realname' => trim($_GPC['realname']), 'mobile' => trim($_GPC['mobile']), 'weixin' => trim($_GPC['weixin']), 'weighttime' => $become_check == 1 ? time() : 0);
				pdo_update('wx_shop_member', $data, array('id' => $member['id']));

				if (!empty($member['uid'])) {
					m('member')->mc_update($member['uid'], array('realname' => $data['realname'], 'mobile' => $data['mobile']));
				}
			}

			show_json(1, array('check' => $become_check));
		}

		$order_status = (intval($set['become_order']) == 0 ? 1 : 3);
		$become_check = intval($set['become_check']);
		$to_check_weight = false;

		if ($set['become'] == '2') {
			$status = 1;
			$ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_order') . ' where uniacid=:uniacid and openid=:openid and status>=' . $order_status . ' limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

			if ($ordercount < intval($set['become_ordercount'])) {
				$status = 0;
				$order_count = number_format($ordercount, 0);
				$order_totalcount = number_format($set['become_ordercount'], 0);
			}
			else {
				$to_check_weight = true;
			}
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
				$to_check_weight = true;
			}
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
					$to_check_weight = true;
					$status = 1;
				}
			}
		}

		if ($to_check_weight) {
			if (empty($member['isweight'])) {
				$data = array('isweight' => 1, 'weightstatus' => $become_check, 'weighttime' => time());
				$member['isweight'] = 1;
				$member['weightstatus'] = $become_check;
				pdo_update('wx_shop_member', $data, array('id' => $member['id']));

				if ($become_check == 1) {
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'weight' => $data['weighttime']), TM_WEIGHTBONUS_BECOME);
				}
			}
		}

		include $this->template();
	}
}

?>

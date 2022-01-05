<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Room_WxShopPage //extends PluginMobileLoginPage
{
	public $model;
    public function __construct()
	{
		// parent::__construct();
		$this->model = m('plugin')->loadModel('live');
		// $this->set = $this->model->getSet();
	}
	/**
     * 获取当前直播间商品列表
     * @param int $liveid
     */
	public function getAllGoods($liveid = 0)
	{
		global $_W;
		$goodslist = array();

		if (!empty($liveid)) {
			$goodslist = pdo_fetchall('SELECT lg.*,sg.id as id,lg.id as livegid,sg.marketprice,sg.thumb,sg.title FROM ' . tablename('wx_shop_live_goods') . ' lg LEFT JOIN ' . tablename('wx_shop_goods') . ' sg ON sg.id=lg.goodsid WHERE lg.liveid=:liveid AND lg.uniacid=:uniacid', array(':liveid' => $liveid, ':uniacid' => $_W['uniacid']));
		}

		return $goodslist;
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		// $openid = trim($_W['openid']);
		$openid = !empty(trim($_W['openid']))?trim($_W['openid']):trim($_GPC['openid']);

		$invitation_id = intval($_GPC['invitationid']);

		if (p('invitation') && (0 < $invitation_id)) {
			$invitation_openid = trim($_GPC['invitation_openid']);
			$invitation_data = array('uniacid' => $uniacid, 'invitation_id' => $invitation_id, 'invitation_openid' => $invitation_openid, 'openid' => $openid, 'scan_time' => time());
			pdo_insert('wx_shop_invitation_log', $invitation_data);
			pdo_query('update ' . tablename('wx_shop_invitation') . ' set scan = scan+1 where id = ' . $invitation_id . ' and uniacid = ' . $uniacid . ' ');
		}

		$roomid = intval($_GPC['id']);
         // var_dump($roomid);
		if (empty($roomid)) {

			show_json(0,'指定直播间不存在');
			// $this->message('指定直播间不存在', '', 'error');
		}
        // var_dump($uniacid);
        // var_dump($roomid);
        // $room1 = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_live'));
        // 	var_dump($room1);
		$room = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_live') . ' where uniacid = :uniacid and id = :roomid ', array(':uniacid' => $uniacid, ':roomid' => $roomid));
        // var_dump($room);
		if (empty($room)) {

			show_json(0,'指定直播间不存在');
			// $this->message('指定直播间不存在', '', 'error');
		}

		$menu = unserialize($room['tabs']);

		$nestables = unserialize($room['nestable']);

		if (empty($nestables)) {
			$nestables = array('interaction', 'goods', 'introduce');
		}

		 // var_dump($openid);
		$member = m('member')->getMember($openid);
		$showlevels = ($room['showlevels'] != '' ? explode(',', $room['showlevels']) : array());
		$showgroups = ($room['showgroups'] != '' ? explode(',', $room['showgroups']) : array());
		$showroom = 0;
        // var_dump($member);
		if (!empty($member)) {
			if ((!empty($showlevels) && in_array($member['level'], $showlevels)) || (!empty($showgroups) && in_array($member['groupid'], $showgroups)) || (empty($showlevels) && empty($showgroups))) {
				$showroom = 1;
			}
		}
		else {
			if (empty($showlevels) && empty($showgroups)) {
				$showroom = 1;
			}
		}
		$plugin_commission = p('commission');

		if ($plugin_commission) {
			$set = $plugin_commission->getSet();

			if ($room['showcommission']) {
				$arr = explode(',', $room['showcommission']);

				if (!in_array($member['agentlevel'], $arr)) {
					
                     show_json(0, mobileUrl('live/room/jurisdiction', array('roomid' => $room['id'])));
					// header('location: ' . mobileUrl('live/room/jurisdiction', array('roomid' => $room['id'])));
					exit();
				}
			}
		}

		if (empty($showroom)) {
               show_json(0, mobileUrl('live/room/jurisdiction', array('roomid' => $room['id'])));
			// header('location: ' . mobileUrl('live/room/jurisdiction', array('roomid' => $room['id'])));
			exit();
		}

		if ($room['covertype'] == 1) {
			$room['thumb'] = tomedia($room['cover']);
		}

		if ($room['livetype'] == 2) {
			$room['url'] = $room['video'];
		}
        // var_dump($this->model);
		$room_goods = $this->model->getAllGoods($roomid);

		$coupon = false;
        // var_dump(!empty($room['couponid'])?true:false);
		if (!empty($room['couponid'])) {
			$coupon = true;
			$room_coupon = pdo_fetchall('select lc.*, sc.couponname, sc.coupontype,sc.backtype ,sc.enough,sc.deduct from ' . tablename('wx_shop_live_coupon') . ' lc left join ' . tablename('wx_shop_coupon') . ' sc on sc.id=lc.couponid where lc.uniacid=:uniacid and lc.roomid=:roomid ', array(':uniacid' => $_W['uniacid'], ':roomid' => $roomid));

			foreach ($room_coupon as $key => &$coupon) {
				if ($coupon['backtype'] == 0) {
					if ($coupon['enough'] == '0') {
						$coupon['color'] = 'orange ';
					}
					else {
						$coupon['color'] = 'blue';
					}

					$coupon['display_title'] = '减' . price_format($coupon['deduct'], 2) . '元';
				}

				if ($coupon['backtype'] == 1) {
					$coupon['color'] = 'red ';
					$coupon['display_title'] = '打' . price_format($coupon['discount'], 2) . '折 ';
				}

				if ($coupon['backtype'] == 2) {
					if (($coupon['coupontype'] == '0') || ($coupon['coupontype'] == '2')) {
						$coupon['color'] = 'red ';
					}
					else {
						$coupon['color'] = 'pink ';
					}

					if (!empty($coupon['backmoney']) && (0 < $coupon['backmoney'])) {
						$coupon['display_title'] = $coupon['display_title'] . '送' . $coupon['backmoney'] . '元余额 ';
					}

					if (!empty($coupon['backcredit']) && (0 < $coupon['backcredit'])) {
						$coupon['display_title'] = $coupon['display_title'] . '送' . $coupon['backcredit'] . '积分 ';
					}

					if (!empty($coupon['backredpack']) && (0 < $coupon['backredpack'])) {
						$coupon['display_title'] = $coupon['display_title'] . '送' . $coupon['backredpack'] . '元红包 ';
					}
				}

				$table_roomcoupon = $this->model->getRedisTable('room_coupon_' . $coupon['couponid'] . '_' . $room['livetime'], $roomid);
				$couponset = redis()->hGet($table_roomcoupon, 'couponset');
				$couponset = json_decode($couponset, true);
				$coupon['iscoupon'] = true;

				if ($couponset['coupontotal'] <= $couponset['receivetotal']) {
					$coupon['iscoupon'] = false;
					$coupon['couponmessage'] = '优惠券已领完';
				}

				$table_roomcoupon_hash = $this->model->getRedisTable('roomcoupon_hash_' . $room['livetime'] . '_' . $coupon['couponid'] . '', $roomid);
				$coupon_total = $couponset['receivetotal'];
				$roomcoupon = redis()->hGet($table_roomcoupon_hash, $openid);

				$roomcoupon = json_decode($roomcoupon, true);

				if (!empty($roomcoupon)) {
					if ((intval($couponset['couponlimit']) <= floatval($roomcoupon['limit'])) && (0 < intval($couponset['couponlimit']))) {
						$coupon['iscoupon'] = false;
						$coupon['couponmessage'] = '您已领取过了';
					}
					else {
						if (0 < intval($couponset['couponlimit'])) {
							if (intval($couponset['couponlimit']) <= intval($roomcoupon['limit'])) {
								$coupon['iscoupon'] = false;
								$coupon['couponmessage'] = '您已领取过了';
							}
						}
					}
				}
				else {
					$coupon['iscoupon'] = true;
				}
			}

			unset($coupon);

			show_json(1,array('room_coupon'=>$room_coupon));
		}

		$packet = false;

		if (0 < $room['packetmoney']) {
			$packet = true;
		}

		// $member = m('member')->getMember($_W['openid']);
		$member = m('member')->getMember($openid);
		// $favorite = $this->model->isFavorite($_W['openid'], $roomid);
		$favorite = $this->model->isFavorite($openid, $roomid);
		// var_dump($favorite);
		$emojiList = $this->model->getEmoji();
	    // var_dump($this->model);
	    
		$records = $this->model->handleRecords($roomid, false, $member['id']);

		$fullscreen = intval($room['screen']);
		$video = trim($room['url']);
		$poster = $room['thumb'];
		if (!empty($video) && ($room['livetype'] != 2)) {
			$video_info = $this->model->getLiveInfo($video, $room['liveidentity']);
			if (!is_error($video_info) && !empty($video_info['hls_url'])) {
				$video = $video_info['hls_url'];
				pdo_update('wx_shop_live', array('thumb' => $video_info['poster']), array('uniacid' => $uniacid, 'id' => $roomid));
			}
			else {
				$video = '';
			}
		}

		$wsConfig = json_encode(array('address' => $this->model->getWsAddress(), 'roomid' => $roomid, 'uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'uid' => $member['id'], 'nickname' => $member['nickname'], 'attachurl' => $_W['attachurl'], 'isMobile' => is_mobile(), 'isIos' => is_ios(), 'fullscreen' => $fullscreen, 'siteroot' => $_W['siteroot']));                                                                                                                                                                                                                                                 
		$moneytext = (empty($_W['shopset']['trade']['moneytext']) ? '余额' : $_W['shopset']['trade']['moneytext']);
		pdo_update('wx_shop_live', array('visit' => $room['visit'] + 1), array('uniacid' => $uniacid, 'id' => $roomid));
		$view = pdo_fetch('select * from ' . tablename('wx_shop_live_view') . ' where uniacid = ' . $uniacid . ' and openid = \'' . $openid . '\' and roomid = ' . $roomid . ' ');
		$viewing = pdo_fetch('select max(viewing) as viewing from ' . tablename('wx_shop_live_view') . ' where uniacid = ' . $uniacid . ' and openid = \'' . $openid . '\' ');

		if (!empty($view)) {
			pdo_update('wx_shop_live_view', array('viewing' => $viewing['viewing'] + 1), array('uniacid' => $uniacid, 'openid' => $openid, 'roomid' => $roomid));
		}
		else {
			$view_data = array('uniacid' => $uniacid, 'openid' => $openid, 'roomid' => $roomid);
			$view_data['viewing'] = !empty($viewing) ? $viewing['viewing'] + 1 : 1;
			pdo_insert('wx_shop_live_view', $view_data);
		}

		$sysset = m('common')->getSysset();
		$shop = set_medias($sysset['shop'], 'logo');
		$setting = pdo_fetch('select * from ' . tablename('wx_shop_live_setting') . ' where uniacid = :uniacid  ', array(':uniacid' => $uniacid));
		$followqrcode = (!empty($room['followqrcode']) ? $room['followqrcode'] : $sysset['share']['followqrcode']);

		if (!empty($followqrcode)) {
			$followqrcode = tomedia($followqrcode);
		}

		$_W['shopshare'] = array('title' => !empty($room['share_title']) ? $room['share_title'] : $shop['name'], 'imgUrl' => !empty($room['share_icon']) ? tomedia($room['share_icon']) : tomedia($room['thumb']), 'link' => !empty($room['share_url']) ? $room['share_url'] : mobileUrl('live/room', array('id' => $roomid), true), 'desc' => !empty($room['share_desc']) ? $room['share_desc'] : $room['title']);
		$room['livetime']=date('y-m-d',$room['livetime']);
		$room['createtime']=date('y-m-d',$room['createtime']);
		// $room['lastlivetime']=date('y-m-d',$room['lastlivetime']);
        $room['cover']=tomedia( $room['cover']);
        $room['nestable']=unserialize($room['nestable']);
        $room['tabs']=unserialize($room['tabs']);
        $room['followqrcode']=tomedia( $room['followqrcode']);
        foreach($room_goods as &$room_good){
        	$room_good['thumb']=tomedia($room_good['thumb']);
        	$room_good['liveprice']=price_format($room_good['liveprice'],2);
        	$room_good['marketprice']=price_format($room_good['marketprice'],2);
        }

		show_json(1,array('fullscreen'=>$fullscreen,'room'=>$room,'ismobile'=>is_mobile(),'is_ios'=>is_ios(),'name'=>$_W['shopset']['shop']['name'],'nestables'=>$nestables,'records'=>$records,'room_goods'=>$room_goods,"shopname"=>$shop['name'],'menu'=>$menu,'invitation'=>p('invitation'),'followqrcode'=>$followqrcode,'moneytext'=>$moneytext,'room_goods'=>$room_goods,'emojiList'=>$emojiList));
		// include $this->template();
	}

	public function jurisdiction()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$roomid = intval($_GPC['roomid']);
		$room = pdo_fetch('SELECT jurisdictionurl_show,jurisdiction_url FROM ' . tablename('wx_shop_live') . ' where id = :roomid and uniacid = :uniacid ', array(':uniacid' => $uniacid, ':roomid' => $roomid));

		if (empty($room)) {

			show_json(0,'指定直播间不存在');
			// $this->message('指定直播间不存在', '', 'error');
		}

       show_json(1,array('room'=>$room));
		// include $this->template('live/jurisdiction');
	}

	public function favorite()
	{
		global $_W;
		global $_GPC;
		$roomid = intval($_GPC['roomid']);

		if (!empty($roomid)) {
			$favorite = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_live_favorite') . 'WHERE uniacid=:uniacid AND roomid=:roomid AND openid=:openid LIMIT 1', array(':uniacid' => $_W['uniacid'], ':roomid' => $roomid, ':openid' => $_W['openid']));

			if (empty($favorite)) {
				pdo_insert('wx_shop_live_favorite', array('uniacid' => $_W['uniacid'], 'roomid' => $roomid, 'openid' => $_W['openid'], 'createtime' => time()));
				pdo_query('update ' . tablename('wx_shop_live') . ' set subscribe = subscribe+1 where id = ' . $roomid . ' and uniacid = ' . intval($_W['uniacid']) . ' ');
				show_json(1, array('favorite' => 1));
			}
			else {
				pdo_update('wx_shop_live_favorite', array('deleted' => !empty($favorite['deleted']) ? 0 : 1, 'createtime' => time()), array('id' => $favorite['id']));

				if ($favorite['deleted'] == 0) {
					pdo_query('update ' . tablename('wx_shop_live') . ' set subscribe = subscribe+1 where id = ' . $roomid . ' and uniacid = ' . intval($_W['uniacid']) . ' ');
				}

				show_json(1, array('favorite' => !empty($favorite['deleted']) ? 1 : 0));
			}
		}

		show_json(0, '参数错误');
	}

	public function draw()
	{
		global $_W;
		global $_GPC;
		$type = trim($_GPC['type']);
	}

	public function roomcoupon()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$openid = trim($_W['openid']);
		$roomid = intval($_GPC['roomid']);
		$couponid = intval($_GPC['couponid']);
		$livetime = intval($_GPC['livetime']);
		$open_redis = function_exists('redis') && !is_error(redis());

		if ($open_redis) {
			$redis_key = $_W['setting']['site']['key'] . '_' . $_W['account']['key'] . '_' . $uniacid . '_' . $roomid . '_roomcoupon_' . $openid;
			$redis = redis();

			if (!is_error($redis)) {
				if ($redis->setnx($redis_key, time())) {
					$redis->expireAt($redis_key, time() + 3);
				}
				else {
					show_json(0, array('status' => '-1', 'message' => '操作频繁，请稍后再试!'));
				}
			}
		}

		$table_roomcoupon = $this->model->getRedisTable('room_coupon_' . $couponid . '_' . $livetime, $roomid);
		$couponset = redis()->hGet($table_roomcoupon, 'couponset');
		$room_coupon = array();

		if (empty($couponset)) {
			$room_coupon = pdo_fetch('select lc.couponid,lc.coupontotal,lc.couponlimit,l.living,l.iscoupon,c.couponname from ' . tablename('wx_shop_live_coupon') . " lc\r\n                    left join " . tablename('wx_shop_live') . " as l on l.id = lc.roomid\r\n                    left join " . tablename('wx_shop_coupon') . " as c on c.id = lc.couponid\r\n                    where lc.roomid = :roomid and lc.couponid = :couponid and lc.uniacid = :uniacid ", array(':uniacid' => $uniacid, ':roomid' => $roomid, ':couponid' => $couponid));
			$couponset = array('title' => trim($room_coupon['couponname']), 'couponid' => intval($room_coupon['couponid']), 'coupontotal' => floatval($room_coupon['coupontotal']), 'couponlimit' => intval($room_coupon['couponlimit']), 'receivetotal' => 0, 'livetime' => intval($livetime));
			$couponset = json_encode($couponset);
			$couponset = redis()->hSet($table_roomcoupon, 'couponset', $couponset);
			$couponset = redis()->hGet($table_roomcoupon, 'couponset');
		}

		$couponset = json_decode($couponset, true);

		if ($couponset['coupontotal'] <= $couponset['receivetotal']) {
			show_json(-2, '优惠券已领完！');
		}

		$table_roomcoupon_hash = $this->model->getRedisTable('roomcoupon_hash_' . $livetime . '_' . $couponid . '', $roomid);
		$coupon_total = $couponset['receivetotal'];
		$roomcoupon = redis()->hGet($table_roomcoupon_hash, $openid);

		if (empty($roomcoupon)) {
			$coupon_log = array('limit' => 0, 'time' => 0);
			$coupon_log = json_encode($coupon_log);
			$couponHash = redis()->hSet($table_roomcoupon_hash, $openid, $coupon_log);
			$roomcoupon = redis()->hGet($table_roomcoupon_hash, $openid);
		}

		$roomcoupon = json_decode($roomcoupon, true);
		if ((intval($couponset['couponlimit']) <= floatval($roomcoupon['limit'])) && (0 < intval($couponset['couponlimit']))) {
			show_json(-3, '您已领取过了！');
		}
		else if (0 < intval($couponset['couponlimit'])) {
			if (intval($roomcoupon['limit']) < intval($couponset['couponlimit'])) {
				$member = m('member')->getMember($openid);
				com('coupon')->poster($member, $couponid, 1, 11);
				$roomcoupon['limit'] = $roomcoupon['limit'] + 1;
				$roomcoupon['time'] = time();
				$roomcoupon = json_encode($roomcoupon);
				$couponHash = redis()->hSet($table_roomcoupon_hash, $openid, $roomcoupon);
				++$couponset['receivetotal'];
				$couponset = json_encode($couponset);
				$couponset = redis()->hSet($table_roomcoupon, 'couponset', $couponset);
			}
			else {
				show_json(-3, '您已领取过了！');
			}
		}
		else {
			$member = m('member')->getMember($openid);
			com('coupon')->poster($member, $couponid, 1, 11);
			$roomcoupon['limit'] = $roomcoupon['limit'] + 1;
			$roomcoupon['time'] = time();
			$roomcoupon = json_encode($roomcoupon);
			$couponHash = redis()->hSet($table_roomcoupon_hash, $openid, $roomcoupon);
			++$couponset['receivetotal'];
			$couponset = json_encode($couponset);
			$couponset = redis()->hSet($table_roomcoupon, 'couponset', $couponset);
		}

		pdo_query('update ' . tablename('wx_shop_live') . ' set coupon_num = coupon_num+1 where id= ' . $roomid . ' and uniacid = ' . $uniacid . ' ');
		show_json(1);
	}

	public function roompacket()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$openid = trim($_W['openid']);
		$roomid = intval($_GPC['roomid']);
		$livetime = intval($_GPC['livetime']);
		$open_redis = function_exists('redis') && !is_error(redis());

		if ($open_redis) {
			$redis_key = $_W['setting']['site']['key'] . '_' . $_W['account']['key'] . '_' . $uniacid . '_' . $roomid . '_roompacket_' . $openid;
			$redis = redis();

			if (!is_error($redis)) {
				if ($redis->setnx($redis_key, time())) {
					$redis->expireAt($redis_key, time() + 3);
				}
				else {
					show_json(0, array('status' => '-1', 'message' => '操作频繁，请稍后再试!'));
				}
			}
		}

		$table_packset = $this->model->getRedisTable('packset_' . $livetime, $roomid);
		$packetset = redis()->hGet($table_packset, 'packset');

		if (empty($packetset)) {
			$packet = pdo_fetch('select title,packetmoney,packettotal,packetprice,living,livetime from ' . tablename('wx_shop_live') . ' where id = :roomid and status = 1 and uniacid = :uniacid ', array(':uniacid' => $uniacid, ':roomid' => $roomid));
			$packetset = array('title' => trim($packet['title']), 'packetmoney' => floatval($packet['packetmoney']), 'packettotal' => intval($packet['packettotal']), 'receivemoney' => 0, 'receivetotal' => 0, 'packetprice' => floatval($packet['packetprice']), 'livetime' => intval($packet['livetime']));
			$packetset = json_encode($packetset);
			$packetset = redis()->hSet($table_packset, 'packset', $packetset);
			$packetset = redis()->hGet($table_packset, 'packset');
		}

		$packetset = json_decode($packetset, true);

		if ($packetset['packetmoney'] < $packetset['receivemoney']) {
			show_json(0, '红包金额不足！');
		}

		if ($packetset['packettotal'] < $packetset['receivetotal']) {
			show_json(0, '红包数量不足！');
		}

		$table_roompack_list = $this->model->getRedisTable('roompack_list_' . $livetime, $roomid);
		$table_roompack_hash = $this->model->getRedisTable('roompack_hash_' . $livetime, $roomid);
		$packet_total = redis()->lLen($table_roompack_list);
		$roompack = redis()->hGet($table_roompack_hash, $openid);

		if (empty($roompack)) {
			if (intval($packet_total) < $packetset['packettotal']) {
				$packList = redis()->rPush($table_roompack_list, $openid);
				$index = redis()->lLen($table_roompack_list);

				if ($index <= $packetset['packettotal']) {
					$packet_log = array('money' => 0, 'time' => 0, 'index' => $index);
					$packet_log = json_encode($packet_log);
					$packHash = redis()->hSet($table_roompack_hash, $openid, $packet_log);
					$roompack = redis()->hGet($table_roompack_hash, $openid);
				}
				else {
					show_json(-2, '红包已领完！');
				}
			}
			else {
				show_json(-2, '红包已领完！');
			}
		}
		else {
			$roompack = json_decode($roompack, true);

			if (0 < floatval($roompack['money'])) {
				show_json(-3, '您已领取过了！');
			}
			else if (intval($roompack['index']) <= $packetset['packettotal']) {
				m('member')->setCredit($openid, 'credit2', $packetset['packetprice'], array('0', '【' . $packetset['title'] . '】直播间领取余额红包: ' . price_format($packetset['packetprice'], 2) . '元 '));
				$roompack['money'] = $packetset['packetprice'];
				$roompack['time'] = time();
				$roompack = json_encode($roompack);
				$packHash = redis()->hSet($table_roompack_hash, $openid, $roompack);
				$packetset['receivetotal'] = $packetset['receivetotal'] + 1;
				$packetset['receivemoney'] = floatval($packetset['receivemoney']) + floatval($packetset['packetprice']);
				$packetset = json_encode($packetset);
				$packetset = redis()->hSet($table_packset, 'packset', $packetset);
			}
			else {
				show_json(-2, '红包已领完！');
			}
		}

		show_json(1);
	}
  // 
	// public function handleRecords($roomid = 0, $manage = false)
	// {
	// 	global $_W;

	// 	if ($manage) {
	// 		$uid = 'console' . '_' . $_W['uid'] . '_' . $_W['role'] . '_' . $_W['uniacid'];
	// 	}

	// 	$table = $this->getRedisTable('chat_records', $roomid);
	// 	$table_length = redis()->lLen($table);
	// 	$records_num = ($manage ? 100 : 30);
	// 	$start_index = ($table_length < $records_num ? 0 : $table_length - $records_num);
	// 	$records = redis()->lRange($table, $start_index, $table_length);

	// 	if (empty($records)) {
	// 		return array();
	// 	}

	// 	if ($manage) {
	// 		$table_banned = 'wx_shop_live_banned_' . $roomid;
	// 		$bannedArr = array();
	// 	}

	// 	foreach ($records as &$record) {
	// 		if (empty($record)) {
	// 			continue;
	// 		}

	// 		$record = json_decode($record, true);
	// 		if (($record['type'] == 'image') && !empty($record['text'])) {
	// 			$imgurl = tomedia($record['text']);

	// 			if ($manage) {
	// 				$record['text'] = '<a href="' . $imgurl . '" target="_blank"><img src="' . $imgurl . '"/></a>';
	// 			}
	// 			else {
	// 				$record['text'] = '<img src="' . $imgurl . '"/>';
	// 			}
	// 		}
	// 		else if ($record['type'] == 'redpack') {
	// 			if ($manage) {
	// 				$record['text'] = '[余额红包] ' . $record['text'] . '，请到手机端查看';
	// 			}
	// 			else {
	// 				$drawstatus = $this->getDrawStatus('redpack', $record['pushid'], $roomid);
	// 				$redpacksubtitle = ($drawstatus == 1 ? '已领取' : '立即领取');
	// 				$redpackdrew = ($drawstatus == 1 ? 'drew' : '');
	// 				$record['text'] = '<div class="redpack ' . $redpackdrew . '" data-pushid="' . $record['pushid'] . '" data-title="' . $record['text'] . '"><p class="name">' . $record['text'] . '</p><p class="desc">' . $redpacksubtitle . '</p></div>';
	// 			}
	// 		}
	// 		else if ($record['type'] == 'coupon') {
	// 			if ($manage) {
	// 				$record['text'] = '[优惠券] ' . $record['text'] . '，请到手机端查看';
	// 			}
	// 			else {
	// 				$drawstatus = $this->getDrawStatus('coupon', $record['pushid'], $roomid);
	// 				$couponsubtitle = ($drawstatus == 1 ? '已领取' : '立即领取');
	// 				$coupondrew = ($drawstatus == 1 ? 'drew' : '');
	// 				$record['text'] = '<div class="coupon ' . $coupondrew . '" data-pushid="' . $record['pushid'] . '" data-title="' . $record['text'] . '"><p class="name">' . $record['text'] . '</p><p class="desc">' . $couponsubtitle . '</p></div>';
	// 			}
	// 		}
	// 		else {
	// 			$_this = $this;
	// 			$record['text'] = preg_replace_callback('/\\[([^\\]]+)\\]/', function($matches) use(&$_this) {
	// 				return $_this->emoji2html($matches[1]);
	// 			}, $record['text']);
	// 			$atText = '';

	// 			if (!empty($record['at'])) {
	// 				$atUsers = iunserializer($record['at']);

	// 				if (!empty($atUsers)) {
	// 					foreach ($atUsers as $key => $nickname) {
	// 						$atText .= '<span class="nickname';

	// 						if ($key == $uid) {
	// 							$atText .= ' self';
	// 						}

	// 						$atText .= '" data-uid="' . $key . '" data-nickname="' . $nickname . '">@';

	// 						if ($key == $uid) {
	// 							$atText .= '你';
	// 						}
	// 						else {
	// 							$atText .= $nickname;
	// 						}

	// 						$atText .= ' </span>';
	// 					}
	// 				}
	// 			}

	// 			$record['text'] = $atText . $record['text'];
	// 		}

	// 		if ($record['status'] == 1) {
	// 			$record['text'] = $record['mid'] == $uid ? '你' : '"' . $record['nickname'] . '"';
	// 			$record['text'] .= '撤回了一条消息';
	// 		}
	// 		else {
	// 			if ($record['status'] == 2) {
	// 				if ($manage) {
	// 					$record['text'] = $record['mid_manage'] == $uid ? '你' : $record['nickname_manage'];
	// 					$record['text'] .= '删除了"' . $record['nickname'] . '"的一条消息';
	// 				}
	// 				else if ($record['mid'] == $uid) {
	// 					$record['text'] = '管理员"' . $record['nickname_manage'] . '"删除了你一条消息';
	// 				}
	// 				else {
	// 					$record['text'] = '"' . $record['nickname'] . '"撤回了一条消息';
	// 				}
	// 			}
	// 		}

	// 		if ($manage) {
	// 			$uuid = $record['mid'];

	// 			if (isset($bannedArr[$uuid])) {
	// 				$record['banned'] = 1;
	// 			}

	// 			if (redis()->hExists($table_banned, $uuid)) {
	// 				$record['banned'] = 1;
	// 				$bannedArr[$uuid] = 1;
	// 			}
	// 		}
	// 	}

	// 	unset($record);
	// 	unset($openid);
	// 	unset($imgurl);
	// 	return $records;
	// }



}

?>

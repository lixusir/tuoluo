<?php

?>
<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}


require WX_SHOP_PLUGIN . 'app/core/page_mobile.php';
class Pay_WxShopPage extends AppMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);
        $orderid = intval($_GPC['id']);
        $peerPaySwi = m('common')->getPluginset('sale');
        $peerPaySwi = $peerPaySwi['peerpay']['open'];
        $ispeerpay = m('order')->checkpeerpay($orderid);
        // var_dump($ispeerpay);die;
        if (empty($orderid)) {
            app_error(AppError::$ParamsError);
        }
        if (!(empty($ispeerpay)))
        {
            // var_dump(pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wx_shop_order_peerpay_payinfo') . ' WHERE openid = ":openid" AND pid = :pid', array(':openid' => $_W['openid'], ':pid' => $ispeerpay['id'])));die;
            if (pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wx_shop_order_peerpay_payinfo') . ' WHERE openid = ":openid" AND pid = ":pid"', array(':openid' => $_W['openid'], ':pid' => (int)$ispeerpay['id'])))
            {
                app_error(AppError::$OrderMustPeerPay,'每人只能代付一次');
                exit();
            }
            $peerpayMessage = trim($_GPC['peerpaymessage']);
            $peerpay_info = (double) pdo_fetchcolumn('select SUM(price) from ' . tablename('wx_shop_order_peerpay_payinfo') . ' where pid=:pid limit 1', array(':pid' => $ispeerpay['id']));
            $peerprice = floatval($_GPC['peerprice']);
            if (empty($peerprice) || ($peerprice <= 0))
            {
                /*转跳至代付页面*/
                // app_json(['peerpay'=>1,'id'=>$orderid]);
                app_json(['url'=>'order/pay/peerpayshare','id'=>$orderid]);
                exit();
            }
            else
            {
                $openid = pdo_fetchcolumn('SELECT openid FROM ' . tablename('wx_shop_order') . ' WHERE id = :id AND uniacid = :uniacid LIMIT 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid']));
                $openid = pdo_fetchcolumn('SELECT openid FROM ' . tablename('wx_shop_order') . ' WHERE id = :id AND uniacid = :uniacipeerPaySwiMIT 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid']));
            }
        }


        $order = pdo_fetch('select * from ' . tablename('wx_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));

        if (empty($order)) {
            app_error(AppError::$OrderNotFound);
            exit();
        }


        if ($order['status'] == -1) {
            app_error(AppError::$OrderCannotPay);
        }
         else if (1 <= $order['status']) {
            app_error(AppError::$OrderAlreadyPay);
        }


        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'wx_shop', ':tid' => $order['ordersn']));

        if (!(empty($log)) && ($log['status'] != '0')) {
            app_error(AppError::$OrderAlreadyPay);
        }
        // var_dump('select goodsid,optionid,seckill from  ' . tablename('wx_shop_order_goods') . ' where orderid=:orderid and uniacid=:uniacid and seckill=1 ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));die;
        // 秒杀
        $seckill_goods = pdo_fetchall('select goodsid,optionid,seckill from  ' . tablename('wx_shop_order_goods') . ' where orderid=:orderid and uniacid=:uniacid and seckill=1 ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));
        //var_dump($seckill_goods);die;
        if (!(empty($log)) && ($log['status'] == '0')) {
            pdo_delete('core_paylog', array('plid' => $log['plid']));
            $log = NULL;
        }


        if (empty($log)) {
            $log = array('uniacid' => $uniacid, 'openid' => $member['uid'], 'module' => 'wx_shop', 'tid' => $order['ordersn'], 'fee' => $order['price'], 'status' => 0);
            pdo_insert('core_paylog', $log);
            $plid = pdo_insertid();
        }


        $set = m('common')->getSysset(array('shop', 'pay'));
        $credit = array('success' => false);

        if (isset($set['pay']) && ($set['pay']['credit'] == 1)) {
            $credit = array('success' => true, 'current' => $member['credit2']);
        }


        $wechat = array('success' => false);

        if (!(empty($set['pay']['wxapp'])) && (0 < $order['price']) && $this->iswxapp) {
            $tid = $order['ordersn'];

            if (!(empty($order['ordersn2']))) {
                $var = sprintf('%02d', $order['ordersn2']);
                $tid .= 'GJ' . $var;
            }


            $payinfo = array('openid' => $_W['openid_wa'], 'title' => $set['shop']['name'] . '订单', 'tid' => $tid, 'fee' => $order['price']);
            $res = $this->model->wxpay($payinfo, 14);

            if (!(is_error($res))) {
                $wechat = array('success' => true, 'payinfo' => $res);

                if (!(empty($res['package'])) && strexists($res['package'], 'prepay_id=')) {
                    $prepay_id = str_replace('prepay_id=', '', $res['package']);
                    pdo_update('wx_shop_order', array('wxapp_prepay_id' => $prepay_id), array('id' => $orderid, 'uniacid' => $_W['uniacid']));
                }

            }
             else {
                $wechat['payinfo'] = $res;
            }
        }


        $cash = array('success' => ($order['cash'] == 1) && isset($set['pay']) && ($set['pay']['cash'] == 1) && ($order['isverify'] == 0) && ($order['isvirtual'] == 0));
        $alipay = array('success' => false);

        if (!(empty($set['pay']['nativeapp_alipay'])) && (0 < $order['price']) && !($this->iswxapp)) {
            $params = array('out_trade_no' => $log['tid'], 'total_amount' => $order['price'], 'subject' => $set['shop']['name'] . '订单', 'body' => $_W['uniacid'] . ':0:NATIVEAPP');
            $sec = m('common')->getSec();
            $sec = iunserializer($sec['sec']);
            $alipay_config = $sec['nativeapp']['alipay'];

            if (!(empty($alipay_config))) {
                $res = $this->model->alipay_build($params, $alipay_config);
                $alipay = array('success' => true, 'payinfo' => $res);
            }

        }
        // 秒杀
        if (p('seckill')) 
        {
            foreach ($seckill_goods as $data ) 
            {
                plugin_run('seckill::getSeckill', $data['goodsid'], $data['optionid'], true, $_W['openid']);
            }
        }

        app_json(
            array(
        'order'  => array('id' => $order['id'], 'ordersn' => $order['ordersn'], 'price' => $order['price'], 'title' => $set['shop']['name'] . '订单'),
        'credit' => $credit,
        'wechat' => $wechat,
        'alipay' => $alipay,
        'cash'   => $cash,
        'peerPaySwi'   => $peerPaySwi
            )
        );
    }


    public function complete()
    {
        global $_W;
        global $_GPC;
        $orderid = intval($_GPC['id']);
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];
        if (empty($orderid)) {
            app_error(AppError::$ParamsError);
        }
        //找人代付
        $ispeerpay = m('order')->checkpeerpay($orderid);
        if (!(empty($ispeerpay))) 
        {
            // $_SESSION['peerpay'] = $ispeerpay['id'];
            $peerpay = $_GPC['peerpay'];
            $peerpay = floatval(str_replace(',', '', $peerpay));
            if (($ispeerpay['peerpay_type'] == 0) && ($ispeerpay['peerpay_realprice'] != $peerpay)) 
            {
                show_json(0, '参数错误');
            }
            else if (($ispeerpay['peerpay_type'] == 1) && !(empty($ispeerpay['peerpay_selfpay'])) && ($ispeerpay['peerpay_selfpay'] < $peerpay) && (0 < floatval($ispeerpay['peerpay_selfpay']))) 
            {
                show_json(0, '参数错误');
            }
            if ($peerpay <= 0) 
            {
                show_json(0, '参数错误');
            }
            $openid = pdo_fetchcolumn('select openid from ' . tablename('wx_shop_order') . ' where id=:orderid and uniacid=:uniacid limit 1', array(':orderid' => $orderid, ':uniacid' => $uniacid));
            $peerpay_info = (double) pdo_fetchcolumn('select SUM(price) price from ' . tablename('wx_shop_order_peerpay_payinfo') . ' where pid=:pid limit 1', array(':pid' => $ispeerpay['id']));
        }

        $type = trim($_GPC['type']);

        if (!(in_array($type, array('wechat', 'alipay', 'credit', 'cash')))) {
            app_error(AppError::$OrderPayNoPayType);
        }


        if (($type == 'alipay') && empty($_GPC['alidata'])) {
            app_error(AppError::$ParamsError, '支付宝返回数据错误');
        }


        $set = m('common')->getSysset(array('shop', 'pay'));
        $set['pay']['weixin'] = ((!(empty($set['pay']['weixin_sub'])) ? 1 : $set['pay']['weixin']));
        $set['pay']['weixin_jie'] = ((!(empty($set['pay']['weixin_jie_sub'])) ? 1 : $set['pay']['weixin_jie']));
        $member = m('member')->getMember($openid, true);
        $order = pdo_fetch('select * from ' . tablename('wx_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));

        if (empty($order)) {
            app_error(AppError::$OrderNotFound);
        }


        if (1 <= $order['status']) {
            $this->success($orderid);
        }


        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'wx_shop', ':tid' => $order['ordersn']));

        if (empty($log)) {
            app_error(AppError::$OrderPayFail);
        }


        $order_goods = pdo_fetchall('select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf,og.seckill from  ' . tablename('wx_shop_order_goods') . ' og ' . ' left join ' . tablename('wx_shop_goods') . ' g on og.goodsid = g.id ' . ' where og.orderid=:orderid and og.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));

        foreach ($order_goods as $data ) {
            if (empty($data['status']) || !(empty($data['deleted']))) {
                app_error(AppError::$OrderPayFail, $data['title'] . '<br/> 已下架!');
            }


            $unit = ((empty($data['unit']) ? '件' : $data['unit']));
            $seckillinfo = plugin_run('seckill::getSeckill', $data['goodsid'], $data['optionid'], true, $_W['openid']);
            if ($data['seckill']) 
            {
                if (empty($seckillinfo) || ($seckillinfo['status'] != 0) || ($seckillinfo['endtime'] < time())) 
                {
                    // if ($_W['ispost']) 
                    // {
                    //     show_json(0, $data['title'] . '<br/> 秒杀已结束，无法支付!');
                    // }
                    // else 
                    // {
                    //     $this->message($data['title'] . '<br/> 秒杀已结束，无法支付!', mobileUrl('order'));
                    // }
                    app_error(AppError::$OrderCreateTimeEnd, $data['title'] . '<br/> 秒杀已结束，无法支付!');
                }
            }
             if (($seckillinfo && ($seckillinfo['status'] == 0)) || !(empty($ispeerpay))) 
            {
            }else{
                 if (0 < $data['minbuy']) {
                    if ($data['buycount'] < $data['minbuy']) {
                        app_error(AppError::$OrderCreateMinBuyLimit, $data['title'] . '<br/> ' . $data['min'] . $unit . '起售!');
                    }

                }


                if (0 < $data['maxbuy']) {
                    if ($data['maxbuy'] < $data['buycount']) {
                        app_error(AppError::$OrderCreateOneBuyLimit, $data['title'] . '<br/> 一次限购 ' . $data['maxbuy'] . $unit . '!');
                    }

                }


                if (0 < $data['usermaxbuy']) {
                    $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('wx_shop_order_goods') . ' og ' . ' left join ' . tablename('wx_shop_order') . ' o on og.orderid=o.id ' . ' where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $data['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));

                    if ($data['usermaxbuy'] <= $order_goodscount) {
                        app_error(AppError::$OrderCreateMaxBuyLimit, $data['title'] . '<br/> 最多限购 ' . $data['usermaxbuy'] . $unit);
                    }

                }


                if ($data['istime'] == 1) {
                    if (time() < $data['timestart']) {
                        app_error(AppError::$OrderCreateTimeNotStart, $data['title'] . '<br/> 限购时间未到!');
                    }


                    if ($data['timeend'] < time()) {
                        app_error(AppError::$OrderCreateTimeEnd, $data['title'] . '<br/> 限购时间已过!');
                    }

                }


                if ($data['buylevels'] != '') {
                    $buylevels = explode(',', $data['buylevels']);

                    if (!(in_array($member['level'], $buylevels))) {
                        app_error(AppError::$OrderCreateMemberLevelLimit, '您的会员等级无法购买<br/>' . $data['title'] . '!');
                    }

                }


                if ($data['buygroups'] != '') {
                    $buygroups = explode(',', $data['buygroups']);

                    if (!(in_array($member['groupid'], $buygroups))) {
                        app_error(AppError::$OrderCreateMemberGroupLimit, '您所在会员组无法购买<br/>' . $data['title'] . '!');
                    }

                }
            }

           


            if ($data['totalcnf'] == 1) {
                if (!(empty($data['optionid']))) {
                    $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,stock,`virtual` from ' . tablename('wx_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $data['goodsid'], ':id' => $data['optionid']));

                    if (!(empty($option))) {
                        if ($option['stock'] != -1) {
                            if (empty($option['stock'])) {
                                app_error(AppError::$OrderCreateStockError, $data['title'] . '<br/>' . $option['title'] . ' 库存不足!');
                            }

                        }

                    }

                }
                 else if ($data['stock'] != -1) {
                    if (empty($data['stock'])) {
                        app_error(AppError::$OrderCreateStockError, $data['title'] . '<br/>' . $option['title'] . ' 库存不足!');
                    }

                }

            }

        }

        if ($type == 'cash') {
            if (empty($set['pay']['cash'])) {
                app_error(AppError::$OrderPayFail, '未开启货到付款');
            }


            m('order')->setOrderPayType($order['id'], 3);
            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = 'cash';
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $order['openid'];
            $ret['fee'] = $order['price'];
            $ret['weid'] = $_W['uniacid'];
            $ret['uniacid'] = $_W['uniacid'];
            $pay_result = m('order')->payResult($ret);
            $this->success($orderid);
        }
        // 找人代付
        if (!(empty($ispeerpay))) 
        {
            $total = $peerpay_info + $peerpay;
            if ($ispeerpay['peerpay_realprice'] < $total) 
            {
                show_json(0, '不能超付');
            }
            $log['fee'] = $peerpay;
            $openid = $_W['openid'];
            $member = m('member')->getMember($openid, true);
        }

        $ps = array();
        $ps['tid'] = $log['tid'];
        $ps['user'] = $openid;
        $ps['fee'] = $log['fee'];
        $ps['title'] = $log['title'];

        if ($type == 'credit') {
            if (empty($set['pay']['credit']) && (0 < $ps['fee'])) {
                app_error(AppError::$OrderPayFail, '未开启余额支付');
            }


            if ($ps['fee'] < 0) {
                app_error(AppError::$OrderPayFail, '金额错误');
            }


            $credits = $this->member['credit2'];

            if ($credits < $ps['fee']) {
                app_error(AppError::$OrderPayFail, '余额不足,请充值');
            }


            $fee = floatval($ps['fee']);
            $shopset = m('common')->getSysset('shop');
            $result = m('member')->setCredit($openid, 'credit2', -$fee, array($_W['member']['uid'], $shopset['name'] . 'APP 消费' . $fee));

            if (is_error($result)) {
                app_error(AppError::$OrderPayFail, $result['message']);
            }


            $record = array();
            $record['status'] = '1';
            $record['type'] = 'cash';
            pdo_update('core_paylog', $record, array('plid' => $log['plid']));
            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = $log['type'];
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['weid'] = $log['weid'];
            $ret['uniacid'] = $log['uniacid'];
            @session_start();
            $_SESSION[WX_SHOP_PREFIX . '_order_pay_complete'] = 1;
            // 找人代付
            if (!(empty($ispeerpay))) 
            {
                $peerheadimg = m('member')->getInfo($member['openid']);
                if (empty($peerheadimg['avatar'])) 
                {
                    $peerheadimg['avatar'] = 'http://of6odhdq1.bkt.clouddn.com/d7fd47dc6163ec00abfe644ab3c33ac6.jpg';
                }
                m('order')->peerStatus(array('pid' => $ispeerpay['id'], 'uid' => $member['id'], 'uname' => $member['nickname'], 'usay' => '', 'price' => $log['fee'], 'createtime' => time(), 'headimg' => $peerheadimg['avatar'], 'openid' => $peerheadimg['openid'], 'usay' => trim($_GPC['peerpaymessage'])));
            }

            $pay_result = m('order')->payResult($ret);
            m('order')->setOrderPayType($order['id'], 1);
            $this->success($orderid);
        }
         else if ($type == 'wechat') {
            if (empty($set['pay']['wxapp']) && $this->iswxapp) {
                app_error(AppError::$OrderPayFail, '未开启微信支付');
            }


            $ordersn = $order['ordersn'];

            if (!(empty($order['ordersn2']))) {
                $ordersn .= 'GJ' . sprintf('%02d', $order['ordersn2']);
            }


            $payquery = $this->model->isWeixinPay($ordersn, $order['price']);
            
            if (!(is_error($payquery)) || !empty($ispeerpay) ) {
                $record = array();
                $record['status'] = '1';
                $record['type'] = 'wechat';
                pdo_update('core_paylog', $record, array('plid' => $log['plid']));
                $ret = array();
                $ret['result'] = 'success';
                $ret['type'] = 'wechat';
                $ret['from'] = 'return';
                $ret['tid'] = $log['tid'];
                $ret['user'] = $log['openid'];
                $ret['fee'] = $log['fee'];
                $ret['weid'] = $log['weid'];
                $ret['uniacid'] = $log['uniacid'];
                $ret['deduct'] = intval($_GPC['deduct']) == 1;
                $pay_result = m('order')->payResult($ret);
                @session_start();
                $_SESSION[WX_SHOP_PREFIX . '_order_pay_complete'] = 1;
                m('order')->setOrderPayType($order['id'], 21);
                pdo_update('wx_shop_order', array('apppay' => 2), array('id' => $order['id']));
                $this->success($orderid);
            }


            app_error(AppError::$OrderPayFail);
        }
         else if ($type == 'alipay') {
            if (empty($set['pay']['nativeapp_alipay'])) {
                app_error(AppError::$OrderPayFail, '未开启支付宝支付');
            }


            $sec = m('common')->getSec();
            $sec = iunserializer($sec['sec']);
            $public_key = $sec['nativeapp']['alipay']['public_key'];

            if (empty($public_key)) {
                app_error(AppError::$OrderPayFail, '支付宝公钥为空');
            }


            $alidata = htmlspecialchars_decode($_GPC['alidata']);
            $alidata = json_decode($alidata, true);
            $newalidata = $alidata['alipay_trade_app_pay_response'];
            $newalidata['sign_type'] = $alidata['sign_type'];
            $newalidata['sign'] = $alidata['sign'];
            $alisign = m('finance')->RSAVerify($newalidata, $public_key, false, true);

            if ($alisign) {
                $record = array();
                $record['status'] = '1';
                $record['type'] = 'wechat';
                pdo_update('core_paylog', $record, array('plid' => $log['plid']));
                $ret = array();
                $ret['result'] = 'success';
                $ret['type'] = 'alipay';
                $ret['from'] = 'return';
                $ret['tid'] = $log['tid'];
                $ret['user'] = $log['openid'];
                $ret['fee'] = $log['fee'];
                $ret['weid'] = $log['weid'];
                $ret['uniacid'] = $log['uniacid'];
                $ret['deduct'] = intval($_GPC['deduct']) == 1;
                $pay_result = m('order')->payResult($ret);
                m('order')->setOrderPayType($order['id'], 22);
                pdo_update('wx_shop_order', array('apppay' => 2), array('id' => $order['id']));
                $this->success($order['id']);
            }

        }

    }

    protected function success($orderid)
    {
        global $_W;
        global $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);

        if (empty($orderid)) {
            app_error(AppError::$ParamsError);
        }


        $order = pdo_fetch('select * from ' . tablename('wx_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
        $merchid = $order['merchid'];
        $goods = pdo_fetchall('select og.goodsid,og.price,g.title,g.thumb,og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,g.storeids from ' . tablename('wx_shop_order_goods') . ' og ' . ' left join ' . tablename('wx_shop_goods') . ' g on g.id=og.goodsid ' . ' where og.orderid=:orderid and og.uniacid=:uniacid ', array(':uniacid' => $uniacid, ':orderid' => $orderid));
        $address = false;

        if (!(empty($order['addressid']))) {
            $address = iunserializer($order['address']);

            if (!(is_array($address))) {
                $address = pdo_fetch('select * from  ' . tablename('wx_shop_member_address') . ' where id=:id limit 1', array(':id' => $order['addressid']));
            }

        }


        $carrier = @iunserializer($order['carrier']);
        if (!(is_array($carrier)) || empty($carrier)) {
            $carrier = false;
        }


        $store = false;

        if (!(empty($order['storeid']))) {
            if (0 < $merchid) {
                $store = pdo_fetch('select * from  ' . tablename('wx_shop_merch_store') . ' where id=:id limit 1', array(':id' => $order['storeid']));
            }
             else {
                $store = pdo_fetch('select * from  ' . tablename('wx_shop_store') . ' where id=:id limit 1', array(':id' => $order['storeid']));
            }
        }


        $stores = false;

        if ($order['isverify']) {
            $storeids = array();

            foreach ($goods as $g ) {
                if (!(empty($g['storeids']))) {
                    $storeids = array_merge(explode(',', $g['storeids']), $storeids);
                }

            }

            if (empty($storeids)) {
                if (0 < $merchid) {
                    $stores = pdo_fetchall('select * from ' . tablename('wx_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                }
                 else {
                    $stores = pdo_fetchall('select * from ' . tablename('wx_shop_store') . ' where  uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
                }
            }
             else if (0 < $merchid) {
                $stores = pdo_fetchall('select * from ' . tablename('wx_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
            }
             else {
                $stores = pdo_fetchall('select * from ' . tablename('wx_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
            }
        }


        $text = '';

        if (!(empty($address))) {
            $text = '您的包裹整装待发';
        }


        if (!(empty($order['dispatchtype'])) && empty($order['isverify'])) {
            $text = '您可以到您选择的自提点取货了';
        }


        if (!(empty($order['isverify']))) {
            $text = '您可以到适用门店去使用了';
        }


        if (!(empty($order['virtual']))) {
            $text = '您购买的商品已自动发货';
        }


        if (!(empty($order['isvirtual'])) && empty($order['virtual'])) {
            if (!(empty($order['isvirtualsend']))) {
                $text = '您购买的商品已自动发货';
            }
             else {
                $text = '您已经支付成功';
            }
        }


        if ($_GPC['result'] == 'seckill_refund') {
            $icon = 'e75a';
        }
         else {
            if (!(empty($address))) {
                $icon = 'e623';
            }


            if (!(empty($order['dispatchtype'])) && empty($order['isverify'])) {
                $icon = 'e7b9';
            }


            if (!(empty($order['isverify']))) {
                $icon = 'e7b9';
            }


            if (!(empty($order['virtual']))) {
                $icon = 'e7a1';
            }


            if (!(empty($order['isvirtual'])) && empty($order['virtual'])) {
                if (!(empty($order['isvirtualsend']))) {
                    $icon = 'e7a1';
                }
                 else {
                    $icon = 'e601';
                }
            }

        }

        $result = array(
            'order'   => array('id' => $orderid, 'isverify' => $order['isverify'], 'virtual' => $order['virtual'], 'isvirtual' => $order['isvirtual'], 'isvirtualsend' => $order['isvirtualsend'], 'virtualsend_info' => $order['virtualsend_info'], 'virtual_str' => $order['virtual_str'], 'status' => ($order['paytype'] == 3 ? '订单提交支付' : '订单支付成功'), 'text' => $text, 'price' => $order['price']),
            'paytype' => ($order['paytype'] == 3 ? '需到付' : '实付金额'),
            'carrier' => $carrier,
            'address' => $address,
            'stores'  => $stores,
            'store'   => $store,
            'icon'    => $icon
            );

        if (!(empty($order['virtual'])) && !(empty($order['virtual_str']))) {
            $result['ordervirtual'] = m('order')->getOrderVirtual($order);
            $result['virtualtemp'] = pdo_fetch('SELECT linktext, linkurl FROM ' . tablename('wx_shop_virtual_type') . ' WHERE id=:id AND uniacid=:uniacid LIMIT 1', array(':id' => $order['virtual'], ':uniacid' => $_W['uniacid']));
        }


        app_json($result);
    }

    protected function str($str)
    {
        $str = str_replace('"', '', $str);
        $str = str_replace('\'', '', $str);
        return $str;
    }

        //验证订单
    public function check() 
    {
        global $_W;
        global $_GPC;
        $orderid = intval($_GPC['id']);
        $og_array = m('order')->checkOrderGoods($orderid);
        if (!(empty($og_array['flag']))) 
        {
            show_json(0, $og_array['msg']);
        }
        show_json(1);
    }
    // 请人代付
    public function peerpay() 
    {
        global $_W;
        global $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $orderid = intval($_GPC['id']);
        $PeerPay = com_run('sale::getPeerPay');
        if (empty($orderid) || empty($PeerPay)) 
        {
            // header('location: ' . mobileUrl('order'));
            // exit();
            show_json(0, '参数错误');
        }
        $peerpay = (int) pdo_fetchcolumn('select orderid from ' . tablename('wx_shop_order_peerpay') . ' where orderid=:id and uniacid=:uniacid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
        if (!(empty($peerpay))) 
        {
            // header('location: ' . mobileUrl('order/pay/peerpayshare', array('id' => $peerpay)));
            // exit();
            // app_json(['url'=>'order/pay/peerpayshare','id'=>$orderid]);
            show_json(1, array(
                'peerpayshare' => 1,
                'id' => $peerpay
            ));
        }
        $order = pdo_fetch('select * from ' . tablename('wx_shop_order') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
        if ($_W['ispost']) 
        {
            $data = array();
            $data['uniacid'] = $_W['uniacid'];
            $data['orderid'] = $orderid;
            $data['peerpay_type'] = (int) $_GPC['type'];
            $data['peerpay_price'] = (double) $order['price'];
            $data['peerpay_realprice'] = (($PeerPay['peerpay_price'] < $order['price'] ? round($order['price'] - $PeerPay['peerpay_privilege'], 2) : (double) $order['price']));
            $data['peerpay_selfpay'] = $PeerPay['self_peerpay'];
            $data['peerpay_message'] = trim($_GPC['message']);
            $data['status'] = 0;
            $data['createtime'] = time();
            $res = pdo_insert('wx_shop_order_peerpay', $data);
            $insert_id = pdo_insertid();
            if ($res) 
            {
                // show_json(1, array('url' => mobileUrl('order/pay/peerpayshare', array('id' => $orderid))));
                show_json(1, array(
                    'url' => 'order/pay/peerpayshare',
                    'id' =>  $orderid,
                ));
            }
            show_json(0);
        }
        if (empty($order)) 
        {
            // header('location: ' . mobileUrl('order'));
            // exit();
            show_json(0, '订单为空');
        }
        $ordergoods = pdo_fetch('select * from ' . tablename('wx_shop_order_goods') . ' where orderid=:id and uniacid=:uniacid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
        $goods = pdo_fetch('select * from ' . tablename('wx_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $ordergoods['goodsid'], ':uniacid' => $uniacid));
        $address = iunserializer($order['address']);
        $member = m('member')->getMember($openid, true);
        $orderMember = m('member')->getMember($order['openid'], true);
        if($goods['thumb']){
            $goods['thumb'] = tomedia($goods['thumb']);
        }
        // include $this->template();
        $address['mobile'] = substr_replace($address['mobile'],'****',-4);
        show_json(1, array(
            'orderMember' => $orderMember,
            'goods' => $goods,
            'address' => $address,
            'order' => $order,
            'PeerPay' => $PeerPay,
            'orderid' => $orderid,
        ));
    }

    public function peerpayshare() 
    {
        global $_W;
        global $_GPC;
        $peerid = intval($_GPC['id']);
        $uniacid = $_W['uniacid'];
        $peerpay = pdo_fetch('select p.*,o.openid from ' . tablename('wx_shop_order_peerpay') . ' p join ' . tablename('wx_shop_order') . ' o on o.id=p.orderid where p.orderid=:id and p.uniacid=:uniacid limit 1', array(':id' => $peerid, ':uniacid' => $uniacid));
        if (empty($peerpay)) 
        {
            // header('location: ' . mobileUrl('order'));
            // exit();
            show_json(0, '代付不存在');
        }
        else if ($peerpay['openid'] !== $_W['openid']) 
        {
            // header('location: ' . mobileUrl('order/pay/peerpaydetail', array('id' => $peerid)));
            // exit();
            show_json(1, array(
                'url' => 'order/pay/peerpaydetail',
                'id' => $peerid,
            ));
        }
        $peerpay_info = (double) pdo_fetchcolumn('select SUM(price) price from ' . tablename('wx_shop_order_peerpay_payinfo') . ' where pid=:pid limit 1', array(':pid' => $peerpay['id']));
        $rate = round(($peerpay_info / $peerpay['peerpay_realprice']) * 100, 2);
        $rate_price = round($peerpay['peerpay_realprice'] - $peerpay_info, 2);
        $member = m('member')->getMember($peerpay['openid'], true);
        $ordergoods = pdo_fetch('select * from ' . tablename('wx_shop_order_goods') . ' where orderid=:id and uniacid=:uniacid limit 1', array(':id' => $peerpay['orderid'], ':uniacid' => $uniacid));
        $goods = pdo_fetch('select * from ' . tablename('wx_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $ordergoods['goodsid'], ':uniacid' => $uniacid));
        $_W['shopshare'] = array('title' => '我想对你说：' . $peerpay['peerpay_message'], 'imgUrl' => tomedia($goods['thumb']), 'desc' => $peerpay['peerpay_message'], 'link' => mobileUrl('order/pay/peerpaydetail', array('id' => $peerid), 1));
        // include $this->template();
        $member['avatar'] = tomedia($member['avatar']);
        show_json(1, array(
            'peerpay_info' => $peerpay_info,
            'rate' => $rate,
            'rate_price' => $rate_price,
            'member' => $member,
            // 'goods' => $goods,
            // '_W_shopshare' => $_W['shopshare'],
        ));
    }
    public function peerpaydetail() 
    {
        global $_W;
        global $_GPC;
        $peerid = intval($_GPC['id']);
        $uniacid = $_W['uniacid'];
        $peerpay = pdo_fetch('select p.*,o.openid,o.address,o.id as oid from ' . tablename('wx_shop_order_peerpay') . ' p join ' . tablename('wx_shop_order') . ' o on o.id=p.orderid where p.orderid=:id and p.uniacid=:uniacid limit 1', array(':id' => $peerid, ':uniacid' => $uniacid));
        if (empty($peerpay)) 
        {
            // header('location: ' . mobileUrl('order'));
            show_json(0, '代付不存在');
        }
        $PeerPay = com_run('sale::getPeerPay');
        $member = m('member')->getMember($peerpay['openid'], true);
        $peerpay_info = (double) pdo_fetchcolumn('select SUM(price) price from ' . tablename('wx_shop_order_peerpay_payinfo') . ' where pid=:pid limit 1', array(':pid' => $peerpay['id']));
        $rate_price = round($peerpay['peerpay_realprice'] - $peerpay_info, 2);
        $rate = round(($peerpay_info / $peerpay['peerpay_realprice']) * 100, 2);
        $ordergoods = pdo_fetch('select * from ' . tablename('wx_shop_order_goods') . ' where orderid=:id and uniacid=:uniacid limit 1', array(':id' => $peerpay['orderid'], ':uniacid' => $uniacid));
        $goods = pdo_fetch('select * from ' . tablename('wx_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $ordergoods['goodsid'], ':uniacid' => $uniacid));
        $address = ((!(empty($peerpay['address'])) ? iunserializer($peerpay['address']) : ''));
        $message = 0;
        if ($peerpay['peerpay_type'] == 0) 
        {
            $price = $peerpay['peerpay_realprice'];
        }
        else 
        {
            $message = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_order_peerpay_payinfo') . ' WHERE pid = :pid ORDER BY id DESC LIMIT 3', array(':pid' => $peerpay['id']));
            if(is_array($message)){
                foreach ($message as $key => $value) {
                    if($value['createtime']){
                    $message[$key]['createtime']  =  date('m-d H:i', $value['createtime']);
                    $message[$key]['i']  =  $k+1;
                    }
                }
            }
            $price = (($peerpay['peerpay_selfpay'] < $rate_price ? $peerpay['peerpay_selfpay'] : $rate_price));
        }
        $_W['shopshare'] = array('title' => '我想对你说：' . $peerpay['peerpay_message'], 'imgUrl' => tomedia($ordergoods['thumb']), 'desc' => $peerpay['peerpay_message'], 'link' => mobileUrl('order/pay/peerpaydetail', array('id' => $peerid), 1));
        // include $this->template();
        if($goods['thumb']){
            $goods['thumb'] = tomedia($goods['thumb']);
        }

        show_json(1, array(
            'member' => $member,
            'rate' => $rate,
            'rate_price' => $rate_price,
            'goods' => $goods,
            'address' => $address,
            'peerpay' => $peerpay,
            'message' => $message,
            '_W_shopshare' => $_W['shopshare'],
            'price' => $price,

        ));
    }
}


?>
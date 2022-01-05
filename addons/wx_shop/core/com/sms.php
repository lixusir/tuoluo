<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}

class Sms_WxShopComModel extends ComModel
{
    public function send($mobile, $tplid, $data, $replace = true)
    {
        global $_W;
        $smsset = $this->sms_set();
        $template = $this->sms_verify($tplid, $smsset);
        if (empty($template['status'])) {
            return $template;
        }
        $params = $this->sms_data($template['type'], $data, $replace, $template);
        //17int sms
        load()->func('communication');
        $sms = pdo_fetch("select * from " . tablename('wx_shop_sms_set_17int') . " where uniacid = {$_W['uniacid']}");
        $smsParam= array(
            'account' => $sms['account'],
            'password' => md5($sms['password']),
            'mobile' => $mobile,
            'content' => '【' . $_W['shopset']['shop']['name'] . '】' .$params,
            'requestId' => '1111',
            'extno' => ''
        );

        $request = ihttp_request('http://www.17int.cn/xxsmsweb/smsapi/send.json', json_encode($smsParam), array(
            'Content-Type' => 'application/json'
        ));
//        return array('status' => 1,'message'=>$smsParam);
        return array('status' => 1,'message'=>$request['content']);
//        return array('status' => 0, 'reslut' => $request);
    }

    public function sms_set()
    {
        global $_W;
        return pdo_fetch('SELECT * FROM ' . tablename('wx_shop_sms_set') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));
    }

    public function sms_temp()
    {
        global $_W;
        $list = pdo_fetchall('SELECT id, `type`, `name` FROM ' . tablename('wx_shop_sms') . ' WHERE status=1 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));
        foreach ($list as $i => &$item) {
            if ($item['type'] == 'juhe') {
                $item['name'] = '[聚合]' . $item['name'];
            } else if ($item['type'] == 'dayu') {
                $item['name'] = '[大于]' . $item['name'];
            } else if ($item['type'] == 'aliyun') {
                $item['name'] = '[阿里云]' . $item['name'];
            } else if ($item['type'] == 'emay') {
                $item['name'] = '[亿美]' . $item['name'];
            }
        }
        unset($item);
        return $list;
    }

    public function sms_num($type, $smsset = NULL)
    {
        if (empty($type)) {
            return;
        }
        if (empty($smsset) || !(is_array($smsset))) {
            $smsset = $this->sms_set();
        }
        if ($type == 'emay') {
            include_once WX_SHOP_VENDOR . 'emay/SMSUtil.php';
            $emayClient = new SMSUtil($smsset['emay_url'], $smsset['emay_sn'], $smsset['emay_pw'], $smsset['emay_sk'], array('proxyhost' => $smsset['emay_phost'], 'proxyport' => $smsset['pport'], 'proxyusername' => $smsset['puser'], 'proxypassword' => $smsset['ppw']), $smsset['emay_out'], $smsset['emay_outresp']);
            $num = $emayClient->getBalance();
            if (!(empty($smsset['emay_warn'])) && !(empty($smsset['emay_mobile'])) && ($num < $smsset['emay_warn']) && (($smsset['emay_warn_time'] + (60 * 60 * 24)) < time())) {
                $emayClient = new SMSUtil($smsset['emay_url'], $smsset['emay_sn'], $smsset['emay_pw'], $smsset['emay_sk'], array('proxyhost' => $smsset['emay_phost'], 'proxyport' => $smsset['pport'], 'proxyusername' => $smsset['puser'], 'proxypassword' => $smsset['ppw']), $smsset['emay_out'], $smsset['emay_outresp']);
                $emayResult = $emayClient->send($smsset['emay_mobile'], '【系统预警】' . '您的亿美软通SMS余额为:' . $num . '，低于预警值:' . $smsset['emay_warn'] . ' (24小时内仅通知一次)');
                if (empty($emayResult)) {
                    pdo_update('wx_shop_sms_set', array('emay_warn_time' => time()), array('id' => $smsset['id']));
                }
            }
            return $num;
        }
    }

    protected function sms_verify($tplid, $smsset)
    {
        global $_W;
        $template = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_sms') . ' WHERE id=:id and uniacid=:uniacid ', array(':id' => $tplid, ':uniacid' => $_W['uniacid']));
        $template['data'] = iunserializer($template['data']);
        if (empty($template)) {
            return array('status' => 0, 'message' => '模板不存在!');
        }
        if (empty($template['status'])) {
            return array('status' => 0, 'message' => '模板未启用!');
        }
        if (empty($template['type'])) {
            return array('status' => 0, 'message' => '模板类型错误!');
        }
        return $template;
    }

    protected function sms_data($type, $data, $replace, $template)
    {
        if ($replace) {
            $tempdata = $template['content'];
            if(!is_array($data)) return $data;
            foreach ($data as $key => $value) {
                $tempdata = str_replace('[' . $key . ']', $value, $tempdata);
            }
            return $tempdata;
//            if ($type == 'emay') {
//                $tempdata = $template['content'];
//                foreach ($data as $key => $value) {
//                    $tempdata = str_replace('[' . $key . ']', $value, $tempdata);
//                }
//                $data = $tempdata;
//            } else {
//                $tempdata = iunserializer($template['data']);
//                foreach ($tempdata as &$td) {
//                    foreach ($data as $key => $value) {
//                        $td['data_shop'] = str_replace('[' . $key . ']', $value, $td['data_shop']);
//                    }
//                }
//                unset($td);
//                $newdata = array();
//                foreach ($tempdata as $td) {
//                    $newdata[$td['data_temp']] = $td['data_shop'];
//                }
//                $data = $newdata;
//            }
//        }
//        if ($type == 'juhe') {
//            $result = '';
//            $count = count($data);
//            $i = 0;
//            foreach ($data as $key => $value) {
//                if ((0 < $i) && ($i < $count)) {
//                    $result .= '&';
//                }
//                $result .= '#' . $key . '#=' . $value;
//                ++$i;
//            }
//        } else {
//            if (($type == 'dayu') || ($type == 'aliyun') || ($type == 'aliyun_new')) {
//                $result = json_encode($data);
//            } else if ($type == 'emay') {
//                $result = $data;
//            }
//        }
//        return $result;
        }
    }

    protected function http_post($url, $postData)
    {
        $postData = http_build_query($postData);
        $options = array('http' => array('method' => 'POST', 'header' => 'Content-type:application/x-www-form-urlencoded', 'content' => $postData, 'timeout' => 15 * 60));
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if (!(is_array($result))) {
            $result = json_decode($result, true);
        }
        return $result;
    }

    protected function http_get($url)
    {
        $result = file_get_contents($url, false);
        if (!(is_array($result))) {
            $result = json_decode($result, true);
        }
        return $result;
    }

    public function callsms(array $params)
    {
        global $_W;
        $tag = ((isset($params['tag']) ? $params['tag'] : ''));
        $datas = ((isset($params['datas']) ? $params['datas'] : array()));
        $tm = $_W['shopset']['notice'];
        if (empty($tm)) {
            $tm = m('common')->getSysset('notice');
        }
        $smsid = $tm[$tag . '_sms'];
        $smsclose = $tm[$tag . '_close_sms'];
        if (!(empty($smsid)) && empty($smsclose) && !(empty($params['mobile']))) {
            $sms_data = array();
            foreach ($datas as $i => $value) {
                $sms_data[$value['name']] = $value['value'];
            }
            $this->send($params['mobile'], $smsid, $sms_data);
        }
    }
}

?>
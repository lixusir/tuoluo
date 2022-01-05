<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Level_WxShopPage extends PluginWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        global $_S;
        $set = $_S['commission'];

        $default = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'],'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'commission1' => $set['commission1'], 'commission2' => $set['commission2'], 'commission3' => $set['commission3']);
        $others = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_commission_level') . ' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY weight asc');
        $list = array_merge(array($default), $others);
        include $this->template();
    }

    public function add()
    {
        $this->post();
    }

    public function edit()
    {
        $this->post();
    }

    protected function post()
    {
        global $_W;
        global $_GPC;
        global $_S;
        $set = $_S['commission'];
        $id = trim($_GPC['id']);


        if ($id == 'default') {
            $level = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'commission1' => $set['commission1'], 'commission2' => $set['commission2'], 'commission3' => $set['commission3'],'leveltypes'=>'','weight'=> empty($set['weight']) ? 1 : $set['weight']);
        } else {
            $level = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_commission_level') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));
        }
        strexists($set['leveltype'], '_') ? $leveltype = explode('_', $level['leveltypes']) : $leveltype = explode('&', $level['leveltypes']);

        if ($_W['ispost']) {

            if($id!='default'){
                if(empty($_GPC['leveltypes'])){
                    show_json(0,'条件不得为空！');
                }
                if(empty($_GPC['weight']) || intval($_GPC['weight'])==1){
                    show_json(0,'权重设置出错！');
                }
            }

            if(empty($id))$hasWeight = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_commission_level') . ' WHERE weight=:weight and uniacid=:uniacid  limit 1', array(':weight' => intval($_GPC['weight']), ':uniacid' => $_W['uniacid']));
            else $hasWeight = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_commission_level') . ' WHERE weight=:weight and uniacid=:uniacid  and id!= :id limit 1', array(':id'=>$id,':weight' => intval($_GPC['weight']), ':uniacid' => $_W['uniacid']));
            if(!empty($hasWeight)) show_json(0,'权重不得重复！');

            $data = array('weight' => intval($_GPC['weight']), 'uniacid' => $_W['uniacid'], 'levelname' => trim($_GPC['levelname']), 'commission1' => trim(trim($_GPC['commission1']), '%'), 'commission2' => trim(trim($_GPC['commission2']), '%'), 'commission3' => trim(trim($_GPC['commission3']), '%'), 'commissionmoney' => trim($_GPC['commissionmoney'], '%'), 'ordermoney' => $_GPC['ordermoney'], 'ordercount' => intval($_GPC['ordercount']), 'downcount' => intval($_GPC['downcount']),'teamcount'=>$_GPC['teamcount'],'teamcount1'=>$_GPC['teamcount1'],'ordercount1'=>$_GPC['ordercount1'],'ordercount2'=>$_GPC['ordercount2'],'ordermoney1'=>$_GPC['ordermoney1'],'ordermoney2'=>$_GPC['ordermoney2'],'downcount1'=>$_GPC['downcount1']);

            $data['levelcondition'] = intval($_GPC['levelcondition']);
            $data['leveltypes_json'] = iserializer($_GPC['leveltypes']);

            if (!empty($id)) {
                if ($id == 'default') {
                    $updatecontent = '<br/>等级名称: ' . $set['levelname'] . '->' . $data['levelname'] . '<br/>一级佣金比例: ' . $set['commission1'] . '->' . $data['commission1'] . '<br/>二级佣金比例: ' . $set['commission2'] . '->' . $data['commission2'] . '<br/>三级佣金比例: ' . $set['commission3'] . '->' . $data['commission3'];
                    $set['levelname'] = $data['levelname'];
                    $set['commission1'] = $data['commission1'];
                    $set['commission2'] = $data['commission2'];
                    $set['commission3'] = $data['commission3'];

                    $this->updateSet($set);
                    plog('commission.level.edit', '修改分销商默认等级' . $updatecontent);
                } else {
                    $updatecontent = '<br/>等级名称: ' . $level['levelname'] . '->' . $data['levelname'] . '<br/>一级佣金比例: ' . $level['commission1'] . '->' . $data['commission1'] . '<br/>二级佣金比例: ' . $level['commission2'] . '->' . $data['commission2'] . '<br/>三级佣金比例: ' . $level['commission3'] . '->' . $data['commission3'];
                    pdo_update('wx_shop_commission_level', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                    plog('commission.level.edit', '修改分销商等级 ID: ' . $id . $updatecontent);
                }
            } else {
                pdo_insert('wx_shop_commission_level', $data);
                $id = pdo_insertid();
                plog('commission.level.add', '添加分销商等级 ID: ' . $id);
            }

            show_json(1, array('url' => webUrl('commission/level')));
        }

        $data=[
            'levelcondition' => $level['levelcondition'],
            'leveltype'=>$level['leveltypes'],
            'leveltypes_json'=>$level['leveltypes_json'],
        ];
        include $this->template();
    }

    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);

        if (empty($id)) {
            $id = (is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0);
        }

        $items = pdo_fetchall('SELECT id,levelname FROM ' . tablename('wx_shop_commission_level') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_delete('wx_shop_commission_level', array('id' => $item['id']));
            plog('commission.level.delete', '删除分销商等级 ID: ' . $id . ' 等级名称: ' . $item['levelname']);
        }

        show_json(1);
    }
}

?>

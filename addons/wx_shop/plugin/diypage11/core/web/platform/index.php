<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_WxShopPage extends PluginWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params = array();
        $condition = '';
        $keyword = trim($_GPC['keyword']);

        if (!empty($keyword)) {
            $condition .= ' and ( name like :keyword)';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $sql = 'select * from ' . tablename('wx_shop_platform') .' where uniacid = ' . $_W['uniacid']  . $condition . ' ORDER BY sort desc';

        $list = pdo_fetchall($sql, $params);
        $total = pdo_fetchcolumn('select * from ' . tablename('wx_shop_platform') .' where uniacid = ' . $_W['uniacid']  . $condition, $params);

        unset($row);

        $pager = pagination2($total, $pindex, $psize);
        load()->func('tpl');

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
        $id = trim($_GPC['id']);

        //显示编辑页面
        $platform = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_platform') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));


        //数据提交时
        if ($_W['ispost']) {
//            $this->model->platformRespond();
//            exit();
            $merchid = empty($_GPC['merchid'])? 1 : $_GPC['merchid']; //门店id（这里还不行）

            $data = array('uniacid' => $_W['uniacid'],  'cid' => intval($_GPC['cid']), 'merchid' => intval($merchid), 'name' => trim($_GPC['name']),'num' => intval($_GPC['num']), 'sort'=>$_GPC['sort'],'status'=>intval($_GPC['status']));

            if (!empty($id)) {
                pdo_update('wx_shop_platform', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
            } else {
                pdo_insert('wx_shop_platform', $data);
                $id = pdo_insertid();

                //第一次时才生成keyword 生成唯一keywords = 随机数_uniacid_门店id_台位id
                $keyword = sha1(uniqid(time(), TRUE)).'_'.$_W['uniacid'].'_'.$merchid.'_'.$id;

                $result = $this->model->platformRespond($keyword);
                if($result === false){
                    pdo_delete('wx_shop_platform', array('id' => $id));
                    show_json(0, array('url' => webUrl('diypage/platform')));
                }

                $data['keyword']   = $keyword;
                $data['ticket']   = $result['ticket'];
                $data['qrid']   = $result['qrid'];
                pdo_update('wx_shop_platform', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
            }

            show_json(1, array('url' => webUrl('diypage/platform')));
        }

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

        $items = pdo_fetchall('SELECT id FROM ' . tablename('wx_shop_platform') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_delete('wx_shop_platform', array('id' => $item['id']));
        }

        show_json(1);
    }
}

?>

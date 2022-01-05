<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}

class Plugins_WxShopPage
{
    public function main()
    {
        $plugins = array();

        $plugin=p('creditshop');
        if ($plugin) {
            $plugin_set = $plugin->getSet();            
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "creditshop" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('globonus');
        if ($plugin) {
            $plugin_set = $plugin->getSet();                                                
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "globonus" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('abonus');
        if ($plugin) {
            $plugin_set = $plugin->getSet();              
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "abonus" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('groups');
        if ($plugin) {
            $plugin_set = $plugin->getSet();
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "groups" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('sns');
        if ($plugin) {
            $plugin_set = $plugin->getSet();            
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "sns" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('merch');
        if ($plugin) {
            $plugin_set = $plugin->getSet();  
            //var_dump($plugin_set);die;  
            if (!(empty($plugin_set["parent"]['on_show_wxapp']))) {
                
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "merch" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('lottery');
        if ($plugin) {
            $plugin_set = $plugin->getSet();
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "lottery" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $info['identity']='task';
                $plugins[] = $info;
            }
        }
        $plugin=p('bargain');
        if ($plugin) {
            $plugin_set = $plugin->getSet();
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "bargain" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('live');
        if ($plugin) {
            $plugin_set = $plugin->getSet();
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "live" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }
        $plugin=p('weightbonus');
        if ($plugin) {
            $plugin_set = $plugin->getSet();
            if (!(empty($plugin_set['on_show_wxapp']))) {
                $info = pdo_fetch('select name,identity,thumb from ' . tablename('wx_shop_plugin') . 'where identity = "weightbonus" limit 1');
                $info['thumb'] = tomedia($info['thumb']);
                $plugins[] = $info;
            }
        }

         
        echo json_encode($plugins);
        exit;
    }
}

?>
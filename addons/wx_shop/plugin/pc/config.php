<?php
if (!(defined("IN_IA"))) 
{
	exit("Access Denied");
}
return array("version" => "1.0", "id" => "pc", "name" => "pc商城");
// return array( 'version' => '1.0', 'id' => 'pc', 'name' => 'pc商城', 'v3' => true, 'menu' => array( 'plugincom' => 1,'items' => array( array('title' => '站点设置', 'route' => 'shop'), array('title' => '友情链接', 'route' => 'link'), array( 'title' => '菜单管理', 'route' => 'menu', 'items' => array( array('title' => '顶部菜单', 'param' => array('type' => '')), array('title' => '底部菜单','param' => array('type' => 1)),array('title' => '客户服务','param' => array('type' => 2) ) ) ), array( 'title' => '广告管理', 'items' => array( array('title' => '首页轮播', 'route' => 'slide'), array('title' => '精品推荐', 'route' => 'recommend'),array('title' => '广告管理', 'route' => 'adv') ) ), array( 'title' => '帮助中心', 'items' => array( array('title' => '文章分类', 'route' => 'qa.category'), array('title' => '文章管理', 'route' => 'qa.question'), array('title' => '公告管理', 'route' => '--shop.notice') ) ) ) ) );

?>
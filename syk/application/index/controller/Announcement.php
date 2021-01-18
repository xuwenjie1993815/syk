<?php
namespace app\index\controller;
use think\View;
use think\Db;
use think\Controller;
use think\Config;
use think\Cookie;
use app\index\controller\Base;
class Announcement extends Base
{

    public function index()
    {
    	//通过admintype 获取其消息通知
        $adminInfo = Cookie::get('adminInfo');
        //未读
        $announcement_list = Db::query("SELECT * FROM announcement where (streetName = '0' OR streetName = "."'".$adminInfo['username']."'"." ) AND user_type = ".$adminInfo['type']." AND id in ( select announcement_id FROM announcement_user where user_arr_id like (".'"'."%'".$adminInfo['id']."'%".'"'.")) order by publish_time desc");
        foreach ($announcement_list as $key => $value) {
            $announcement_list[$key]['read'] = '0';
        }


        //已读
        $announcement_list_re = Db::query("SELECT * FROM announcement where (streetName = '0' OR streetName = "."'".$adminInfo['username']."'"." ) AND user_type = ".$adminInfo['type']." AND id in ( select announcement_id FROM announcement_user where user_arr_id not like (".'"'."%'".$adminInfo['id']."'%".'"'.")) order by publish_time desc");
        foreach ($announcement_list_re as $key => $value) {
            $announcement_list_re[$key]['read'] = '1';
        }
        $list = array_merge($announcement_list,$announcement_list_re);


        $this->assign('announcement_list',$list);
        $this->assign('announcement_list_count',count($announcement_list));

    	return $this->fetch();
    }

    //查看通知详情
    public function announcement_show()
    {
    	$id = input('id');
    	$announcement_info = Db::table('announcement')->where('id',$id)->find();
    	$announcement_info['enclosure'] = json_decode($announcement_info['enclosure'],1);
    	$this->assign('announcement_info',$announcement_info);
    	return $this->fetch();
    }

    //查看报错详情
    public function report_show()
    {
    	$id = input('id');
    	$report_info = Db::table('report')->where('id',$id)->find();
    	$report_info['enclosure'] = json_decode($report_info['enclosure'],1);
    	$this->assign('report_info',$report_info);
    	return view('Index/report_show');
    }

    //编辑人员信息
    public function syj_fixed_s1_edit()
    {   
        
        if ($_POST) {
            Db::table('syj_fixed_data')->where('id',$_POST['id'])->update($_POST);
            return array('code'=>'1');
        }
        switch (input('type')) {
            case 'pid':
                $where['pid'] = input('pid');
                break;
            case 'id':
                $where['id'] = input('id');
                break;
            case 'socialSecurity':
                $where['socialSecurity'] = input('socialSecurity');
                break;
        }
        $where['date'] = date('Y-m',time());
        $res = Db::table('syj_fixed_data')->where($where)->find();
        $this->assign('info',$res);
        return view('Syj/syj_fixed_s1_edit');
    }

    //检查身份证/社保号是否有对应的本月数据
    public function check_user()
    {
        $where['date'] = date('Y-m',time());
        //检查是否该用户信息
        switch ($_POST['type']) {
            case 'pid':
                $where['pid'] = $_POST['data'];
                break;
            case 'id':
                $where['id'] = $_POST['data'];
                break;
            case 'socialSecurity':
                $where['socialSecurity'] = $_POST['data'];
                break;
        }
        $res = Db::table('syj_fixed_data')->where($where)->find();
        if (!$res) {
            return array('code' => '0');
        }else{
            return array('code' => '1');
        }
    }


    
}
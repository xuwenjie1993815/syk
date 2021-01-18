<?php
namespace app\index\controller;
use think\View;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel;
class Base extends Controller
{
	//检查是否登录/接口状态
	public function _initialize(){
		//登陆
        $request = Request::instance();
        if (!Cookie::get('adminInfo')) {
			header("Location: ".$request->domain()."/syk/public/index.php/index/login/index.html"); die;
        }else{
        	$this->assign('adminInfo',Cookie::get('adminInfo'));
        }
        //获取角色权限列表
        $adminInfo = Cookie::get('adminInfo');
        //获取菜单(整理排序出来)
        //先取出对应的主菜单
        $role_authority_list_0 = Db::table('role_authority')->alias('r_a')->join('authority a','a.id = r_a.authority_id')->where('r_a.role_id',$adminInfo['type'])->where('a.type','0')->field('a.id')->select();
        $role_authority_list_1 = Db::table('role_authority')->alias('r_a')->join('authority a','a.id = r_a.authority_id')->where('r_a.role_id',$adminInfo['type'])->where('a.type','1')->field('a.id')->select();
        $role_authority_list_2 = Db::table('role_authority')->alias('r_a')->join('authority a','a.id = r_a.authority_id')->where('r_a.role_id',$adminInfo['type'])->where('a.type','2')->field('a.id')->select();
        $authority_list_0 = array();
        foreach ($role_authority_list_0 as $key => $value) {
            $authority_list_0[] = $value['id'];
        }
        $authority_list_1 = array();
        foreach ($role_authority_list_1 as $key => $value) {
            $authority_list_1[] = $value['id'];
        }
        $authority_list_2 = array();
        foreach ($role_authority_list_2 as $key => $value) {
            $authority_list_2[] = $value['id'];
        }
        
        $this->assign('authority_list_0',$authority_list_0);
        $this->assign('authority_list_1',$authority_list_1);
        $this->assign('authority_list_2',$authority_list_2);

        //获取管理员是否有未处理的报错
        //获取登陆用户是否有处理报错权限
        $role_authority = Db::table('role_authority')->where('role_id',$adminInfo['type'])->where('authority_id','1')->find();
        if ($role_authority) {
            $report = Db::table('report')->where('state','1')->find();
            if ($report) {
                $this->assign('is_report','1');
            }else{
                $this->assign('is_report','0');
            }
        }else{
            $this->assign('is_report','0');
        }

        //获取登陆用户是否有未读消息
        $announcement_list = Db::query("SELECT * FROM announcement where (streetName = '0' OR streetName = "."'".$adminInfo['username']."'"." ) AND user_type = ".$adminInfo['type']." AND id in ( select announcement_id FROM announcement_user where user_arr_id like (".'"'."%'".$adminInfo['id']."'%".'"'.")) order by publish_time desc");
        if ($announcement_list) {
            $this->assign('is_announcement','1');
        }else{
            $this->assign('is_announcement','0');
        }

        //查询是否定表数据中的本月数据处于哪个阶段
        $where['state'] = '1';
        $where['date'] = date('Y-m',time());
        $res_fixed = Db::table('syj_fixed_data')->where($where)->value('stage');
        if (!$res_fixed) {
            $res_fixed = 0;
        }
        $this->assign('res_fixed',$res_fixed);
        
        //这些文件需要下载phpexcel，然后放在vendor文件里面。具体参考上一篇数据导出。
        vendor("PHPExcel.Classes.PHPExcel");
        vendor("PHPExcel.Classes.PHPExcel.Writer.IWriter");
        vendor("PHPExcel.Classes.PHPExcel.Writer.Abstract");
        vendor("PHPExcel.Classes.PHPExcel.Writer.Excel5");
        vendor("PHPExcel.Classes.PHPExcel.Writer.Excel2007");
        vendor("PHPExcel.Classes.PHPExcel.IOFactory");

    }  
}
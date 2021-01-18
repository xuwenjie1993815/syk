<?php
namespace app\index\controller;
use think\View;
use think\Db;
use think\Controller;
use think\Config;
use think\Cookie;
use think\Common;
use app\index\controller\Base;
use app\index\controller\Login;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel;
class Index extends Base
{

	public function test()
	{
		return view('Index/test');
	}

    public function index()
    {
        $reportCount = getReportCountByState();
        $this->assign('reportCount',$reportCount);
        $adminInfo = Cookie::get('adminInfo');
        $this->assign('adminInfo',$adminInfo);
        return view('Index/index');
    }



    //注销登陆
    public function loginout()
    {
        Cookie::delete('adminInfo');
        return array('code' => 1);
    }

    //修改密码
    public function change_password()
    {
        if ($_POST) {
            $pwd_hash = $this->getRand(10);
            $data['pwd_hash'] = $pwd_hash;
            $data['password'] = md5(md5(input('newpassword')).$pwd_hash.Config::get('QS_pwdhash'));
            $res = Db::table('user')->where('id',Cookie::get('adminInfo')['id'])->update($data);
            if ($res) {
                Cookie::delete('adminInfo');
                return array('code' => 1);
            }else{
                return array('code' => 2,'msg' => '操作失败,请重试');
            }
        }
        return view('Index/change_password');
    }

    //生成随机字符
    public function getRand($length = 8){
	    // 密码字符集，可任意添加你需要的字符
	    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	    $str = "";
	    for ( $i = 0; $i < $length; $i++ )
	    {
	        $str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
	    }
	    return $str ;
	}


    public function identifyEnterprise()
    {
    	$industry_list = Db::table('industry')->select();
        $this->assign('industry_list',$industry_list?:'');
        $adminInfo = Cookie::get('adminInfo');
        switch ($adminInfo['type']) {
            case '1':
                $where['state'] = 1;
                break;
            case '3':
                $where['state'] = 1;
                $where['admin_id'] = $adminInfo['id'];
                break;
            
            default:
                $where['state'] = 1;
                break;
        }
        $gradecheck_list = Db::table('gradecheck')->where($where)->order('id desc')->select();
        $this->assign('gradecheck_list',$gradecheck_list);
        return view('Index/identifyEnterprise');
    }

    //根据行业id获取行业筛选项
    public function findGradeByIndustryid()
    {
    	$industry_id = $_POST['industry_id'];
    	$grade_list = Db::table('grade')->where('industry_id',$industry_id)->where('state','1')->select();
    	$yingshou = array();
    	$yingshou_html = '';
    	$congye_html = '';
    	$zichan_html = '';
    	foreach ($grade_list as $key => $value) {
    		//营收数据
    		if ($value['yingshou']!='0') {
    			if ($value['type']!='4') {
    				$yingshou_html .= "<option value=".$value['yingshou'].">".explode(',',$value['yingshou'])[0]."万~".explode(',',$value['yingshou'])[1]."万</option>";
    			}else{
	    			$yingshou_html .= "<option value=".$value['yingshou'].">".$value['yingshou']."万以上</option>";
    			}
    		}else{
    			$yingshou_html = "<option value='0'>无需选择</option>";
    		}
    		//从业人员
    		if ($value['congye']!='0') {
    			if ($value['type']!='4') {
    				$congye_html .= "<option value=".$value['congye'].">".explode(',',$value['congye'])[0]."人~".explode(',',$value['congye'])[1]."人</option>";
    			}else{
	    			$congye_html .= "<option value=".$value['congye'].">".$value['congye']."人以上</option>";
    			}
    		}else{
    			$congye_html = "<option value='0'>无需选择</option>";
    		}
    		//资产总额
    		if ($value['zichan']!='0') {
    			if ($value['type']!='4') {
    				$zichan_html .= "<option value=".$value['zichan'].">".explode(',',$value['zichan'])[0]."万~".explode(',',$value['zichan'])[1]."万</option>";
    			}else{
	    			$zichan_html .= "<option value=".$value['zichan'].">".$value['zichan']."万以上</option>";
    			}
    		}else{
    			$zichan_html = "<option value='0'>无需选择</option>";
    		}

    	}
    	return array('code' => 1,'yingshou_html' => $yingshou_html,'congye_html' => $congye_html,'zichan_html' => $zichan_html);

    }

    //查询大中小微型
    public function findGrade()
    {
    	$industry_id = $_POST['industry_id'];
    	$yingshou = $_POST['yingshou'];
    	$zichan = $_POST['zichan'];
    	$congye = $_POST['congye'];
    	//判断大中小型(查询单个筛选项所属的等级,最小的一个即为正式等级)
    	$yingshou_type = 0;
    	$zichan_type = 0;
    	$congye_type = 0;
    	$type = array();
    	if ($yingshou != '0') {
    		$yingshou_type = Db::table('grade')->where('industry_id',$industry_id)->where('yingshou',$yingshou)->value('type');
    		if ($yingshou_type != 0 AND $yingshou_type != '') {
    			$type['yingshou_type'] = $yingshou_type;
    		}
    	}
    	if ($zichan != '0') {
    		$zichan_type = Db::table('grade')->where('industry_id',$industry_id)->where('zichan',$zichan)->value('type');
    		if ($zichan_type != 0 AND $zichan_type != '') {
    			$type['zichan_type'] = $zichan_type;
    		}
    	}
    	if ($congye != '0') {
    		$congye_type = Db::table('grade')->where('industry_id',$industry_id)->where('congye',$congye)->value('type');
    		if ($congye_type != 0 AND $congye_type != '') {
    			$type['congye_type'] = $congye_type;
    		}
    	}
    	switch (min($type)) {
    		case '1':
    			$ret = '微型企业';
    			break;
    		case '2':
    			$ret = '小型企业';
    			break;
    		case '3':
    			$ret = '中型企业';
    			break;
    		case '4':
    			$ret = '大型企业';
    			break;
    		default:
    			# code...
    			break;
    	}
    	$policy = Db::table('industry')->where('id',$industry_id)->value('policy');
    	return array('code' => 1,'type' => $ret,'policy' => $policy);
    	
    }

    //报错上传
    public function uploadError()
    {

    	$where = array();
        if (input('street_id')) {
            $where['street_id'] = input('street_id');
            $where_re['street'] = input('street_id');
        }else{
            $where_re['street'] = '';
        }

    	if (input('admin_id')) {
    		$where['street_id'] = input('admin_id');
    		$where_re['street_id'] = input('admin_id');
    	}else{
    		$where_re['street_id'] = '';
    	}
    	if (input('state')) {
    		$where['state'] = input('state');
    		$where_re['state'] = input('state');
    	}else{
    		$where_re['state'] = '';
    	}
    	if (input('datemin')) {
    		$where_min['upload_time'] = array('egt',input('datemin'));
    		$where_re['datemin'] = input('datemin');
    	}else{
    		$where_re['datemin'] = '';
    	}
    	if (input('datemax')) {
    		$where_max['upload_time'] = array('elt',input('datemax'));
    		$where_re['datemax'] = input('datemax');
    	}else{
    		$where_re['datemax'] = '';
    	}

    	//获取镇街list
    	$admin_list = Db::table('user')->where('type','2')->where('state','1')->select();
    	//获取报错list
    	$report_list = Db::table('report')->where('state','neq','0')->where($where)->where($where_min)->where($where_max)->order('upload_time','desc')->select();
    	//调整数据格式
    	foreach ($report_list as $key => $value) {
    		$report_list[$key]['enclosure'] = json_decode($value['enclosure'],1);
    	}
		$this->assign('where',$where_re);
        $this->assign('admin_list',$admin_list);
        $this->assign('report_list',$report_list);
    	return view('Index/uploadError');
    }

    //处理报错
    public function handleError()
    {

    	$report_id = $_POST['report_id'];
    	$data['admin_return'] = $_POST['admin_return'];
    	$data['state'] = $_POST['state'];
    	$ret = Db::table('report')->where('id',$report_id)->update($data);
        //增加消息通知
        if ($_POST['admin_return']) {
            $adminInfo = Cookie::get('adminInfo');
            $type = '';
            $report_info = Db::table('report')->where('id',$report_id)->find();
            switch ($adminInfo['type']) {
                case '1':
                $data1['title'] = '管理员报错反馈通知';
                $type = '0';
                $data1['user_type'] = '2';
                $data1['streetName'] = $report_info['streetName'];
                    break;
                case '2':
                $data1['title'] = $adminInfo['username'].'：报错反馈通知';
                $type = '1';
                $data1['user_type'] = '1';
                    break;
                default:
                    break;
            }
            $time = time();
            $data1['content'] = $report_info['name'].' '.$report_info['pid'].' '.$report_info['content'].'。反馈内容：'.$report_info['admin_return'];
            $data1['publish_time'] = date('Y-m-d H:i:s',$time);
            //报错目标id
            $data1['target_id'] = $report_id;

            sendmsg($data1,$type,$adminInfo,$report_info['street_id']);
        }
    	if ($ret == 1) {
    		return array('code' => 1);
    	}else{
    		return array('code' => 2);
    	}
    }

    //批量处理报错
    public function handleErrorArr()
    {
    	$report_id_arr = $_POST['report_id_arr'];
    	$list['id'] = array('in',$report_id_arr);
    	$data['admin_return'] = $_POST['admin_return'];
    	$data['state'] = $_POST['state'];
    	$ret = Db::table('report')->where($list)->update($data);
    	return array('code' => 1);

    }

    //上传报错
    public function uploadErrorZj()
    {
    	//获取cookie
    	$adminInfo = Cookie::get('adminInfo');
    	$id = $adminInfo['id'];
    	$where = array();
    	if (input('state')) {
    		$where['state'] = input('state');
    		$where_re['state'] = input('state');
    	}else{
    		$where_re['state'] = '';
    	}
    	if (input('datemin')) {
    		$where['upload_time'] = array('egt',input('datemin'));
    		$where_re['datemin'] = input('datemin');
    	}else{
    		$where_re['datemin'] = '';
    	}
    	if (input('datemax')) {
    		$where['upload_time'] = array('elt',input('datemax'));
    		$where_re['datemax'] = input('datemax');
    	}else{
    		$where_re['datemax'] = '';
    	}

    	//获取报错list
    	$report_list = Db::table('report')->where('state','neq','0')->where('street_id',$id)->where($where)->order('upload_time','desc')->select();
    	//调整数据格式
    	foreach ($report_list as $key => $value) {
    		$report_list[$key]['enclosure'] = json_decode($value['enclosure'],1);
    	}
    	// var_dump($report_list[2]['enclosure']);die;
		$this->assign('where',$where_re);
        $this->assign('report_list',$report_list);
    	return view("Index/uploadErrorZj");
    }

    //镇街上传报错页面
    public function uploadErrorlast()
    {
        //提交
    	if ($_POST) {
    		$typenName = $_POST['typenName'];
    		unset($_POST['typenName']);
    		$date = date('Y-m-d H:i:s',time());
    		$adminInfo = Cookie::get('adminInfo');
    		$_POST['street_id'] = $adminInfo['id'];
    		$_POST['streetName'] = $adminInfo['username'];
    		$_POST['upload_time'] = $date;
            //编辑提交
            if (input('id')) {
                $id = input('id');
                $report_info = Db::table('report')->where('id',$id)->find();
                //处理附件信息
                $enclosure = json_decode($_POST['enclosure'],1);
                $enclosure1 = json_decode($report_info['enclosure'],1);
                $aa = array_merge($enclosure1,$enclosure);
                $_POST['enclosure'] = json_encode($aa,JSON_UNESCAPED_UNICODE);
                Db::table('report')->where('id',$id)->update($_POST);
                //新增通知给管理员
                $data['target_id'] = $id;
                $data['title'] = $adminInfo['username'].'：重新提交'.$typenName;
                $data['content'] = $adminInfo['username']."重新提交了一条".$typenName."的上报，";
                $data['publish_time'] = $date;
                $data['user_type'] = '1';
            }else{
                $report_id = Db::table('report')->insertGetId($_POST);
                $data['target_id'] = $report_id;
                //新增通知给管理员
                $data['title'] = $adminInfo['username'].'：新增'.$typenName;
                $data['content'] = $adminInfo['username']."新上传了一条类型为：'".$typenName."'的上报，";
                $data['publish_time'] = $date;
                $data['user_type'] = '1';
            }

            //消息通知
            sendmsg($data,1,$adminInfo,'');

    		return array('code' => 1);
    	}

        //编辑
        if (input('id')) {
            $id = input('id');
            $report_info = Db::table('report')->where('id',$id)->find();
            $this->assign('report_info',$report_info);//获取镇街list
            $admin_list = Db::table('user')->where('type','2')->where('state','1')->select();
            $this->assign('admin_list',$admin_list);
            return view('Index/uploadErrorEdit');
        }

    	//获取镇街list
    	$admin_list = Db::table('user')->where('type','2')->where('state','1')->select();
    	$this->assign('admin_list',$admin_list);
    	return view('Index/uploadErrorlast');
    }

    //查看消息通知
    public function checkAnnouncement()
    {
    	$adminInfo = Cookie::get('adminInfo');
    	$id = $_POST['id'];
    	$announcement = Db::table('announcement')->where('id',$id)->find();
    	//标记为已读（如果是公告，则不自动标记）
    	if ($announcement['user_type'] == '2' AND $announcement['streetName'] == '0') {

    		$enclosure = json_decode($announcement['enclosure'],1);
    		if ($enclosure == null) {
    			$enclosure_html = '';
    		}else{
	    		$enclosure_html = '';
	    		$url = $_SERVER['REQUEST_SCHEME'] .'://' . $_SERVER['HTTP_HOST'] . str_replace('/index.php' ,'' ,$_SERVER['SCRIPT_NAME']) . '/lib';
	    		foreach ($enclosure as $key => $value) {
	    			$enclosure_html .= '<a style="color: blue;" href="'.$url.'/webuploader/0.1.5/server/upload/'.$value.'" download="">'.$value.'</a>';
	    		}
    		}
    		return array('code' => '2','announcement' => $announcement,'type' => '1','enclosure_html'=> $enclosure_html);
    	}else{
	    	$user_arr_id = Db::table('announcement_user')->where('announcement_id',$id)->value('user_arr_id');
	    	$user_arr_id = explode(',', $user_arr_id);

	    	$user_arr_id = array_diff($user_arr_id, ["'".$adminInfo['id']."'"]);
	    	// var_dump($user_arr_id);die;
	    	Db::table('announcement_user')->where('announcement_id',$id)->update(array('user_arr_id' => implode(',', $user_arr_id)));
    		return array('code' => '1','announcement' => $announcement );
    	}
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


    //公告管理
    public function notice()
    {
    	$announcement_list = Db::table('announcement')->where('user_type','2')->where('streetName','0')->select();
    	$this->assign('announcement_list',$announcement_list);
    	return view('Index/notice');
    }

    //上传公告
    public function uploadNotice()
    {
    	//获取镇街list
    	$adminInfo = Cookie::get('adminInfo');
    	$admin_list = Db::table('user')->where('type','2')->where('state','1')->select();
    	if (input()) {
    		$time = date('Y-m-d H:i:s',time());
    		$_POST['publish_time'] = $time;
    		$_POST['user_type'] = '2';
    		$_POST['streetName'] = '0';
	    	//发送信息给各街道
	        sendmsg($_POST,2,$adminInfo,'');
	        return array('code'=>'1');
    	}

    	$this->assign('admin_list',$admin_list);
    	return view('Index/uploadNotice');
    }

    //公告详情
    public function notice_show()
    {
    	$id = input('id');
    	$announcement_info = Db::table('announcement')->where('id',$id)->find();
    	$announcement_info['enclosure'] = json_decode($announcement_info['enclosure'],1);
    	$this->assign('announcement_info',$announcement_info);
    	return view('Index/notice_show');
    }

    //公告未读情况
    public function notice_check()
    {
    	$id = input('id');
    	$announcement_user_info = Db::table('announcement_user')->where('announcement_id',$id)->find();
    	if ($announcement_user_info['user_arr_id'] != '') {
    		$admin_list = Db::query("select * FROM user WHERE id in (".$announcement_user_info['user_arr_id'].")");
    	}else{
    		$admin_list = '';
    	}
    	$this->assign('announcement_id',$id);
    	$this->assign('admin_list',$admin_list);
    	return view('Index/notice_check');
    }

    //发送阅读公告提醒
    public function remind()
    {
    	$id = input('id');
    	$admin_info = Db::table('user')->where('id',$id)->find();
    	$announcement_id = input('announcement_id');
    	$announcement_info = Db::table('announcement')->where('id',$announcement_id)->find();
    	//发送信息给街道
    	$data['title'] = '阅读公告提醒';
    	$data['content'] = '请及时阅读'."'".$announcement_info['title']."',并及时点击'已读按钮'。";
    	$data['publish_time'] = date('Y-m-d H:i:s',time());
    	$data['user_type'] = '2';
    	$data['streetName'] = $admin_info['username'];
	    sendmsg($data,0,$admin_info,$id);
	    return array('code'=>'1');

    }

    //公告标记已读
    public function read()
    {
    	$id = input('id');
    	$adminInfo = Cookie::get('adminInfo');
    	$user_arr_id = Db::table('announcement_user')->where('announcement_id',$id)->value('user_arr_id');
    	$user_arr_id = explode(',', $user_arr_id);

    	$user_arr_id = array_diff($user_arr_id, ["'".$adminInfo['id']."'"]);
    	// var_dump($user_arr_id);die;
    	Db::table('announcement_user')->where('announcement_id',$id)->update(array('user_arr_id' => implode(',', $user_arr_id)));
		return array('code' => '1','announcement' => $announcement );
    }

    //检查当前用户是否还有未读内容
    public function read_ex()
    {
        $info = Cookie::get('adminInfo');
        $where['a.if_cancel'] = '0';
        $where['a.del_flag'] = '0';
        $where['u.user_arr_id']  = ['neq',' '];
        $user_arr_id = Db::table('announcement_user')->alias('u')->join('announcement a','u.announcement_id = a.id')->where($where)->field('u.user_arr_id')->select();
        $count = 0;
        foreach ($user_arr_id as $key => $value) {
            $user_arr = explode(',', $value['user_arr_id']);
            $re = in_array("'".$info['id']."'", $user_arr);
            if ($re) {
                $count++;
            }
        }
        return array('count'=>$count);
    }

    //户口性质检验(admin)
    public function registered()
    {
    	$type = input('type');
    	
    	$adminInfo = Cookie::get('adminInfo');
    	$where['state'] = '1';
    	if ($adminInfo['type'] == '2') {
    		$where['admin_id'] = $adminInfo['id'];
    	}
    	$list = Db::table('registered')->where($where)->select();
    	foreach ($list as $key => $value) {
    		$list[$key]['enclosure'] = json_decode($value['enclosure'],1);
    	}
    	$this->assign('adminInfo',$adminInfo);
    	$this->assign('list',$list);
		


    	return view('Index/registered');
    }

    //上传户口
    public function uploadregistered()
    {
    	if (input()) {
    		$adminInfo = Cookie::get('adminInfo');
    		$time = date('Y-m-d H:i:s',time());
    		$_POST['upload_time'] = $time;
    		$_POST['username'] = $adminInfo['username'];
    		$_POST['admin_type'] = $adminInfo['type'];
    		$_POST['admin_id'] = $adminInfo['id'];
    		Db::table('registered')->insert($_POST);
	        return array('code'=>'1');
    	}
    	return view('Index/uploadregistered');
    }

    public function saveRegistered()
    {
    	$data['state'] = $_POST['state'];
    	Db::table('registered')->where('id',$_POST['id'])->update($data);
    	return array('code'=>'1');
    }

    //失业金申领管理
    public function syjApply()
    {
        $where = array();
        if (input('street_id')) {
            $where['street_id'] = input('street_id');
            $where_re['street'] = input('street_id');
        }else{
            $where_re['street'] = '';
        }

        if (input('admin_id')) {
            $where['street_id'] = input('admin_id');
            $where_re['street_id'] = input('admin_id');
        }else{
            $where_re['street_id'] = '';
        }
        
        if (input('datemin')) {
            $where_min['upload_time'] = array('egt',input('datemin'));
            $where_re['datemin'] = input('datemin');
        }else{
            $where_re['datemin'] = '';
        }
        if (input('datemax')) {
            $where_max['upload_time'] = array('elt',input('datemax'));
            $where_re['datemax'] = input('datemax');
        }else{
            $where_re['datemax'] = '';
        }

        $adminInfo = Cookie::get('adminInfo');
        if ($adminInfo['type'] == 3) {
            $where['street_id'] = $adminInfo['id'];
            $where['state'] = 1;
        }else{
            $where['state'] = 1;
        }
        $list = Db::table('syjapply')->where($where)->where($where_min)->where($where_max)->select();
        $this->assign('list',$list);
        //获取镇街list
        $admin_list = Db::table('user')->where('type','3')->where('state','1')->select();
        $this->assign('admin_list',$admin_list);
        $this->assign('where',$where_re);
        return view('Index/syjApply');
    }


    //excel导入
    public function into(){
        if (!empty ($_FILES ['file_stu'] ['name'])) {
            $error=$_FILES['file_stu']['error'];

            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode(".", $_FILES ['file_stu'] ['name']);
            $file_type = $file_types [count($file_types) - 1];

            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xlsx" AND strtolower($file_type) != "xls") {
                $this->error('不是Excel文件，重新上传');
            }

            /*设置上传路径*/
            /*百度有些文章写的上传路径经过编译之后斜杠不对。不对的时候用大写的DS代替，然后用连接符链接就可以拼凑路径了。*/
            $savePath = ROOT_PATH . 'public' . DS . 'uploads' . DS .'syjApply' . DS;
            // var_dump($savePath);die;
            /*以时间来命名上传的文件*/
            // 以镇街名称+时间方式命名
            $adminInfo = Cookie::get('adminInfo');
            $str = date('Ymdhis');
            $file_name = $adminInfo['username'].$str . "." . $file_type;


            /*是否上传成功*/
            $aa = copy($tmp_file, $savePath . $file_name);
            if (!copy($tmp_file, $savePath . $file_name)) {
                $this->error('上传失败');
            }

            //数据表syjapply 新增数据
            $data['upload_time'] = date("Y-m-d H:i:s",time());
            $data['street_id'] = $adminInfo['id'];
            $data['streetName'] = $adminInfo['username'];
            $data['enclosure'] = $file_name;
            Db::table('syjapply')->insert($data);

            $this->success('成功上传');
            // $data = array_values($data);
            // $data = json_encode($data);
            // //保存数据
            // $url = config('path')."/bloodEntity/importPatientData";
            // $res = http_request($url,$data,1);
            // $res = json_decode($res,1);
            // if ($res AND !$res['error']) {
            //     $this->success('成功上传'.$res.'条数据');
            // }else{
            //     $this->error('上传失败');
            // }

        }else{
            $this->error('上传失败');die;
        }

    }





    //表格导入
    public function import(){
        if(request()->isPost()){
            $file = request()->file('file_stu');
            
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' .DS.'uploads'. DS . 'excel');
            if($info){
                //获取文件所在目录名
                $path=ROOT_PATH . 'public' . DS.'uploads'.DS .'excel/'.$info->getSaveName();

                //加载PHPExcel类
                vendor("PHPExcel.PHPExcel");
                if (!file_exists($path)) {
                    die('no file!');
                }
                $extension = strtolower( pathinfo($path, PATHINFO_EXTENSION) );
                if ($extension =='xlsx' OR $extension =='xls') {
                    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
                    $objReader = new \PHPExcel_Reader_Excel2007();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                } else if ($extension=='csv') {
                    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
                    $PHPReader = new \PHPExcel_Reader_CSV();
                    //默认输入字符集
                    $PHPReader->setInputEncoding('GBK');
                    //默认的分隔符
                    $PHPReader->setDelimiter(',');
                    //载入文件
                    $objExcel = $PHPReader->load($path,$encode='utf-8');
                }
                // $objExcel = $objReader->load($path,$encode='utf-8');//获取excel文件
                $sheet = $objExcel ->getSheet(0);
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn = $sheet->getHighestColumn(); // 取得总列数
                $a=0;
                //将表格里面的数据循环到数组中
                $adminInfo = Cookie::get('adminInfo');
                for($i=2;$i<=$highestRow;$i++)
                {
                    //*为什么$i=2? (因为Excel表格第一行应该是行业，营收，从业人员，资产范围，从第二行开始，才是我们要的数据。)
                    $data[$a]['name'] = $objExcel->getActiveSheet()->getCell("A".$i)->getValue();//企业名称
                    $data[$a]['industryName'] = $objExcel->getActiveSheet()->getCell("B".$i)->getValue();//行业
                    $data[$a]['yingshou'] = $objExcel->getActiveSheet()->getCell("C".$i)->getValue();//营收
                    $data[$a]['congye'] = $objExcel->getActiveSheet()->getCell("D".$i)->getValue();//从业人员
                    $data[$a]['zichan'] = $objExcel->getActiveSheet()->getCell("E".$i)->getValue();//资产范围
                    $type = $this->checkUnitType($data[$a]['name'],$data[$a]['industryName'],$data[$a]['yingshou'],$data[$a]['congye'],$data[$a]['zichan']);
                    $data[$a]['type'] = $type;
                    $data[$a]['checktime'] = date('Y-m-d H:i:s',time());
                    $data[$a]['admin_id'] = $adminInfo['id'];
                     // 这里的数据根据自己表格里面有多少个字段自行决定
                    $a++;
                }
                //往数据库添加数据
                
                $res = Db::name('gradecheck')->insertAll($data);
                if($res){
                        $this->success('操作成功！');
                }else{
                        $this->error('操作失败！');
                   }
            }else{
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
        }
    }

    //判断中小企业
    public function checkUnitType($name,$industryName,$yingshou,$congye,$zichan)
    {
        // 
        // $name = '测试1';
        // $industryName = '工业';
        // $yingshou = '470';
        // $congye = '2000';
        // $zichan = '25';
        $grade_list = Db::table('grade')->where('industryName',$industryName)->where('state',1)->select();
        //查询出该行业 区分企业大小的依据数据
        //再分别计算出 依据数据在哪个范围内
        foreach ($grade_list as $key => $value) {
            if ($value['yingshou'] != '0') {
                $mo_yingshou[$key] = $value['yingshou'];
            }
            if ($value['congye'] != '0') {
                $mo_congye[$key] = $value['congye'];
            }
            if ($value['zichan'] != '0') {
                $mo_zichan[$key] = $value['zichan'];
            }
        }
        if ($mo_yingshou != NULL) {
            if ($yingshou > $mo_yingshou[0]) {
                $yingshou_type = '4';
            }
            $mo_yingshou1 = explode(',',$mo_yingshou[1]);
            if ( $yingshou >=  $mo_yingshou1[0] AND $yingshou <= $mo_yingshou1[1]) {
                $yingshou_type = '3';
            }
            $mo_yingshou2 = explode(',',$mo_yingshou[2]);
            if ($yingshou >=  $mo_yingshou2[0] AND $yingshou <= $mo_yingshou2[1]) {
                $yingshou_type = '2';
            }
            $mo_yingshou3 = explode(',',$mo_yingshou[3]);
            if ($yingshou >=  $mo_yingshou3[0] AND $yingshou <= $mo_yingshou3[1]) {
                $yingshou_type = '1';
            }
            
        }
        if ($mo_congye != NULL) {
            if ($congye > $mo_congye[0]) {
                $congye_type = '4';
            }
            $mo_congye1 = explode(',',$mo_congye[1]);
            if ( $congye >=  $mo_congye1[0] AND $congye <= $mo_congye1[1]) {
                $congye_type = '3';
            }
            $mo_congye2 = explode(',',$mo_congye[2]);
            if ($congye >=  $mo_congye2[0] AND $congye <= $mo_congye2[1]) {
                $congye_type = '2';
            }
            $mo_congye3 = explode(',',$mo_congye[3]);
            if ($congye >=  $mo_congye3[0] AND $congye <= $mo_congye3[1]) {
                $congye_type = '1';
            }
            
        }
        if ($mo_zichan != NULL) {
            if ($zichan > $mo_zichan[0]) {
                $zichan_type = '4';
            }
            $mo_zichan1 = explode(',',$mo_zichan[1]);
            if ( $zichan >=  $mo_zichan1[0] AND $zichan <= $mo_zichan1[1]) {
                $zichan_type = '3';
            }
            $mo_zichan2 = explode(',',$mo_zichan[2]);
            if ($zichan >=  $mo_zichan2[0] AND $zichan <= $mo_zichan2[1]) {
                $zichan_type = '2';
            }
            $mo_zichan3 = explode(',',$mo_zichan[3]);
            if ($zichan >=  $mo_zichan3[0] AND $zichan <= $mo_zichan3[1]) {
                $zichan_type = '1';
            }
            
        }
        $type[0] = $yingshou_type;
        $type[1] = $congye_type;
        $type[2] = $zichan_type;
        $type = min(array_filter($type));
        return  $type; 
    }

    //失业金申领上传情况
    public function checkUpload()
    {
        $beginThismonth=date('Y-m-d H:i:s',mktime(0,0,0,date('m'),1,date('Y')));
        $endThismonth=date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('t'),date('Y')));
        $where_min['upload_time'] = array('egt',$beginThismonth);
        $where_max['upload_time'] = array('elt',$endThismonth);
        $syjapply_list = Db::table('syjapply')->where($where_min)->where($where_max)->field('street_id')->select();
        $yd = array();
        foreach ($syjapply_list as $key => $value) {
            $yd[$key] = $value['street_id'];
        }
        $street_list = Config::get('street_id');
        $wd = array_diff($street_list, $yd);
        $wd = implode(',', $wd);
        $admin_list = Db::query("select * FROM user WHERE id in (".$wd.")");
        $this->assign('admin_list',$admin_list);
        return view('Index/checkUpload');
    }

    public function remindSyj()
    {
        $id = input('id');
        $admin_info = Db::table('user')->where('id',$id)->find();
        //发送信息给街道
        $data['title'] = '上传失业金申领表提醒';
        $data['content'] = '请及时上传'.date("Y-m").'月失业金申领表。';
        $data['publish_time'] = date('Y-m-d H:i:s',time());
        $data['user_type'] = '2';
        $data['streetName'] = $admin_info['username'];
        sendmsg($data,0,$admin_info,$id);
        return array('code'=>'1');
    }


    //通过report报错详情编辑定表数据
    public function syj_fixed_s1_edit()
    {
        $where['state'] = '1';
        $where['date'] = date('Y-m',time());
        switch (input('type')) {
            case 'pid':
                $where['pid'] = input('pid');
                break;
            case 'socialSecurity':
                $where['socialSecurity'] = input('socialSecurity');
                break;
        };
        $res = Db::table('syj_fixed_data')->where($where)->find();
        $this->assign('info',$res);
        return view('Syj/syj_fixed_s1_edit');
        // return $this->fetch();
    }


}

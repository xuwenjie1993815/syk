<?php
namespace app\index\controller;
use think\View;
use think\Db;
use think\Controller;
use think\Config;
use think\Cookie;
use app\index\controller\Base;
class Syj extends Base
{
	//生成初一表
	public function syj1()
	{
       
		return $this->fetch();
	}

    //生成初二表
    public function syj2()
    {
        //查询本月是否导入金保网数据表
        $res = $this->checkFileDate('2');
        $this->assign('jbw_data_exist',$res);
        //查询是否定表数据中的本月数据处于哪个阶段
        $where['state'] = '1';
        $where['date'] = date('Y-m',time());
        $res_fixed = Db::table('syj_fixed_data')->where($where)->value('stage');
        if (!$res_fixed) {
            $res_fixed = 0;
        }
        $this->assign('res_fixed',$res_fixed);
        return $this->fetch();
    }

    //失业金申领管理
    public function syj3()
    {   
        $adminInfo = Cookie::get('adminInfo');
        $where = array();
        $where['f.type'] = config('stage')['初二'];
        $where['f.state'] = '1';
        switch ($adminInfo['type']) {
            //管理员
            case '1':
                

                if (input('admin_id')) {
                    $where['f.up_id'] = input('admin_id');
                    $where_re['street_id'] = input('admin_id');
                }else{
                    $where_re['street_id'] = '';
                }
                
                if (input('datemin')) {
                    $where_min['f.create_time'] = array('egt',input('datemin'));
                    $where_re['datemin'] = input('datemin');
                }else{
                    $where_re['datemin'] = '';
                }
                if (input('datemax')) {
                    $where_max['f.create_time'] = array('elt',input('datemax'));
                    $where_re['datemax'] = input('datemax');
                }else{
                    $where_re['datemax'] = '';
                }
                break;
            //镇街用户
            case '2':
                $where['f.up_id'] = $adminInfo['id'];
                break;
            
            default:
                # code...
                break;
        }

        if ($where_re['datemin'] != '' || $where_re['datemax'] != '') {
            $list = Db::table('syj_file')->alias('f')->join('user u','f.up_id=u.id')->where($where)->where($where_min)->where($where_max)->field('f.*,u.username')->select();
        }else{
            $list = Db::table('syj_file')->alias('f')->join('user u','f.up_id=u.id')->where($where)->whereTime('f.create_time','m')->field('f.*,u.username')->select();
        }
        
        $this->assign('list',$list);
        //获取镇街list
        $admin_list = Db::table('user')->where('type','2')->where('state','1')->select();
        $this->assign('admin_list',$admin_list);
        $this->assign('where',$where_re);
        return $this->fetch();
    }

    //导入其他数据
    public function syj4()
    {
        //查询本月是否导入金保网数据表
        $res = $this->checkFileDate('4');
        $this->assign('other_data_exist',$res);
        //查询是否定表数据中的本月数据处于哪个阶段
        $where['state'] = '1';
        $where['date'] = date('Y-m',time());
        $res_fixed = Db::table('syj_fixed_data')->where($where)->value('stage');
        if (!$res_fixed) {
            $res_fixed = 0;
        }
        $this->assign('res_fixed',$res_fixed);
        return $this->fetch();
    }

    //定表数据
    public function syj5()
    {
        return $this->fetch();
    }

    public function syj5_data()
    {
        if( request()->isAjax() ){
            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|startBank|startBank|accountProvince|accountCity';
            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_fixed_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('stage',config('stage')['定表'])
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_fixed_data')
                                ->where('state','1')
                                ->where('stage',config('stage')['定表'])
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_fixed_data')
                ->where('state','1')
                ->where('stage',config('stage')['定表'])
                ->where('date',date('Y-m',time()))
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_fixed_data')
                        ->where('state','1')
                        ->where('stage',config('stage')['定表'])
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;
            //转为json返回
            return json( $info );
        }
    }

    //查看初三表数据
    public function syj4_data_show()
    {
        return $this->fetch();
    }

    public function syj4_data()
    {
        if( request()->isAjax() ){
            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|startBank|startBank|accountProvince|accountCity';
            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_fixed_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('stage',config('stage')['初三'])
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_fixed_data')
                                ->where('state','1')
                                ->where('stage',config('stage')['初三'])
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_fixed_data')
                ->where('state','1')
                ->where('stage',config('stage')['初三'])
                ->where('date',date('Y-m',time()))
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_fixed_data')
                        ->where('state','1')
                        ->where('stage',config('stage')['初三'])
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;
            //转为json返回
            return json( $info );
        }
    }

    //查看other数据
    public function syj_other_data_show()
    {
        return $this->fetch();
    }

    public function syj_other_data()
    {        
        if( request()->isAjax() ){
            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|bankBranch';
            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_other_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_other_data')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_other_data')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_other_data')
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;
            //转为json返回
            return json( $info );
        }
    }

    //获取上月定表数据
    public function syj1_data(){
        if( request()->isAjax() ){
            //Db::table('test') = new UserModel;

            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|startBank|startBank|accountProvince|accountCity';

            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_fixed_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('stage',config('stage')['上月'])
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_fixed_data')
                                ->where('state','1')
                                ->where('stage',config('stage')['上月'])
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_fixed_data')
                ->where('state','1')
                ->where('stage',config('stage')['上月'])
                ->where('date',date('Y-m',time()))
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_fixed_data')
                        ->where('state','1')
                        ->where('stage',config('stage')['上月'])
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;

            // $info = [
            //    'draw'=> request()->post('draw'), // ajax请求次数，作为标识符
            //    'recordsTotal'=>count($data),  // 获取到的结果数(每页显示数量)
            //    'recordsFiltered'=>$cnt,       // 符合条件的总数据量
            //    'data'=>$data,　　　　　　　　　　//获取到的数据结果
            // ];
            //转为json返回
            return json( $info );
        }
    }

    //查看
    public function syj2_data_show()
    {
        return $this->fetch();
    }

    //查看金保网数据
    public function syj_jbw_data_show()
    {
        return $this->fetch();
    }

    //获取上月定表数据
    public function syj2_data(){
        if( request()->isAjax() ){
            //Db::table('test') = new UserModel;

            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|startBank|startBank|accountProvince|accountCity';

            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_fixed_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('stage',config('stage')['初一'])
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_fixed_data')
                                ->where('state','1')
                                ->where('stage',config('stage')['初一'])
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_fixed_data')
                ->where('state','1')
                ->where('stage',config('stage')['初一'])
                ->where('date',date('Y-m',time()))
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_fixed_data')
                        ->where('state','1')
                        ->where('stage',config('stage')['初一'])
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;

            // $info = [
            //    'draw'=> request()->post('draw'), // ajax请求次数，作为标识符
            //    'recordsTotal'=>count($data),  // 获取到的结果数(每页显示数量)
            //    'recordsFiltered'=>$cnt,       // 符合条件的总数据量
            //    'data'=>$data,　　　　　　　　　　//获取到的数据结果
            // ];
            //转为json返回
            return json( $info );
        }
    }

    //获取金保网数据
    public function syj_jbw_data(){
        if( request()->isAjax() ){
            //Db::table('test') = new UserModel;

            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|bankBranch';

            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_jbw_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_jbw_data')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_jbw_data')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_jbw_data')
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;

            // $info = [
            //    'draw'=> request()->post('draw'), // ajax请求次数，作为标识符
            //    'recordsTotal'=>count($data),  // 获取到的结果数(每页显示数量)
            //    'recordsFiltered'=>$cnt,       // 符合条件的总数据量
            //    'data'=>$data,　　　　　　　　　　//获取到的数据结果
            // ];
            //转为json返回
            return json( $info );
        }
    }

    //编辑定表数据
    public function syj_fixed_s1_edit()
    {
        if ($_POST) {
            Db::table('syj_fixed_data')->where('id',$_POST['id'])->update($_POST);
            return array('code'=>'1');
        }
        $id = input('id');
        $res = Db::table('syj_fixed_data')->where('id',$id)->find();
        $this->assign('info',$res);
        return $this->fetch();
    }

    //编辑金保网数据
    public function syj_fixed_jbw_edit()
    {
        if ($_POST) {
            Db::table('syj_jbw_data')->where('id',$_POST['id'])->update($_POST);
            return array('code'=>'1');
        }
        $id = input('id');
        $res = Db::table('syj_jbw_data')->where('id',$id)->find();
        $this->assign('info',$res);
        return $this->fetch();
    }

    //编辑镇街数据
    public function syj_fixed_zj_edit()
    {
        if ($_POST) {
            Db::table('syj_zjup_data')->where('id',$_POST['id'])->update($_POST);
            return array('code'=>'1');
        }
        $id = input('id');
        $res = Db::table('syj_zjup_data')->where('id',$id)->find();
        $this->assign('info',$res);
        return $this->fetch();
    }

    //编辑other数据
    public function syj_fixed_other_edit()
    {
        if ($_POST) {
            Db::table('syj_other_data')->where('id',$_POST['id'])->update($_POST);
            return array('code'=>'1');
        }
        $id = input('id');
        $res = Db::table('syj_other_data')->where('id',$id)->find();
        $this->assign('info',$res);
        return $this->fetch();
    }

    //上月定表表格导入
    public function import(){
        if(request()->isPost()){
            $file = request()->file('file_stu');
            if ($file == NULL) {
            	$this->error('请选择文件！');
            }
            //检查本月是否有此类文件导入
            $checkFileDateRet = $this->checkFileDate('1');
            if ($checkFileDateRet == '1') {
                $this->error('当月已上传该文件,如需重新上传,请在文件管理中心修改文件状态。');
            }
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' .DS.'uploads'. DS . 'syjexcel');
            if($info){
                //获取文件所在目录名
                $path=ROOT_PATH . 'public' . DS.'uploads'.DS .'syjexcel/'.$info->getSaveName();
                //把文件路径加入数据库
                $syj_file['title'] = date('Y-m', strtotime(date('Y-m-01') . " - 1 month")).'定表';
                $syj_file['type'] = config('stage')['上月'];
                $syj_file['url'] = $path;
                $syj_file['create_time'] = date('Y-m-d H:i:s',time());
                $syj_file_id = Db::table('syj_file')->insertGetId($syj_file);
                if (!$syj_file_id) {
                    $this->error('文件上传失败,请稍后重试');
                }
                //加载PHPExcel类
                vendor("PHPExcel.PHPExcel");

                if (!file_exists($path)) {
                    die('no file!');
                }
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($extension =='xlsx') {
                    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
                    $objReader = new \PHPExcel_Reader_Excel2007();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                } else if ($extension =='xls') {
                    $objReader = new \PHPExcel_Reader_Excel5();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                }

                // $objExcel = $objReader->load($path,$encode='utf-8');//获取excel文件
                //获取excel表中第几个工作表
                $sheet = $objExcel ->getSheet(1);
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn = $sheet->getHighestColumn(); // 取得总列数
                $a=0;
                //将表格里面的数据循环到数组中
                $adminInfo = Cookie::get('adminInfo');
                
                for($i=4;$i<=$highestRow;$i++)
                {
                    if (!rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue())) {
                        break;
                    }
                    //个人社保号
                    $data[$a]['socialSecurity'] = rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue());
                    $data[$a]['name'] = rtrim($objExcel->getActiveSheet()->getCell("C".$i)->getValue());
                    $data[$a]['sex'] = rtrim($objExcel->getActiveSheet()->getCell("D".$i)->getValue());
                    $data[$a]['pid'] = rtrim($objExcel->getActiveSheet()->getCell("E".$i)->getValue());
                    $data[$a]['age'] = 0;

                    $data[$a]['bankAccount'] = rtrim($objExcel->getActiveSheet()->getCell("G".$i)->getValue());
                    $data[$a]['bankBranch'] = rtrim($objExcel->getActiveSheet()->getCell("H".$i)->getValue());
                    $data[$a]['startBank'] = rtrim($objExcel->getActiveSheet()->getCell("I".$i)->getValue());
                    $data[$a]['accountProvince'] = rtrim($objExcel->getActiveSheet()->getCell("J".$i)->getValue());

                    $data[$a]['accountCity'] = rtrim($objExcel->getActiveSheet()->getCell("K".$i)->getValue());
                    $data[$a]['accountArea'] = rtrim($objExcel->getActiveSheet()->getCell("L".$i)->getValue());
                    $data[$a]['remittancePurpose'] = rtrim($objExcel->getActiveSheet()->getCell("M".$i)->getValue());
                    $data[$a]['benefits'] = rtrim($objExcel->getActiveSheet()->getCell("N".$i)->getValue());

                    $data[$a]['remarks'] = rtrim($objExcel->getActiveSheet()->getCell("O".$i)->getValue());
                    $data[$a]['monthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("P".$i)->getValue());
                    $data[$a]['sendMonthNumber'] = $objExcel->getActiveSheet()->getCell("Q".$i)->getValue();
                    $data[$a]['tel'] = rtrim($objExcel->getActiveSheet()->getCell("R".$i)->getValue());
                    $data[$a]['zj_name'] = rtrim($objExcel->getActiveSheet()->getCell("S".$i)->getValue());
                    $data[$a]['other_remarks'] = rtrim($objExcel->getActiveSheet()->getCell("U".$i)->getValue());

                    $data[$a]['date'] = date('Y-m',time());
                    //获取上月定表阶段的配置
                    $data[$a]['stage'] = config('stage')['上月'];
                    $data[$a]['file_id'] = $syj_file_id;
                    $a++;
                }
                //往数据库添加数据
                
                $res = Db::name('syj_fixed_data')->insertAll($data);
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

    //导入金保网文件
    public function import_jbw()
    {
        if(request()->isPost()){
            $file = request()->file('file_stu');
            if ($file == NULL) {
                $this->error('请选择文件！');
            }
            //检查本月是否有此类文件导入
            $checkFileDateRet = $this->checkFileDate('2');
            if ($checkFileDateRet == '1') {
                $this->error('当月已上传该文件,如需重新上传,请在文件管理中心修改文件状态。');
            }
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' .DS.'uploads'. DS . 'syjexcel');
            if($info){
                //获取文件所在目录名
                $path=ROOT_PATH . 'public' . DS.'uploads'.DS .'syjexcel/'.$info->getSaveName();
                //把文件路径加入数据库
                $syj_file['title'] = date('Y-m', strtotime(date('Y-m-01') . " - 1 month")).'金保网表';
                $syj_file['type'] = config('stage')['初一'];
                $syj_file['url'] = $path;
                $syj_file['create_time'] = date('Y-m-d H:i:s',time());
                $syj_file_id = Db::table('syj_file')->insertGetId($syj_file);
                if (!$syj_file_id) {
                    $this->error('文件上传失败,请稍后重试');
                }
                //加载PHPExcel类
                vendor("PHPExcel.PHPExcel");

                if (!file_exists($path)) {
                    die('no file!');
                }
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($extension =='xlsx') {
                    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
                    $objReader = new \PHPExcel_Reader_Excel2007();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                } else if ($extension =='xls') {
                    $objReader = new \PHPExcel_Reader_Excel5();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                }

                // $objExcel = $objReader->load($path,$encode='utf-8');//获取excel文件
                //获取excel表中第几个工作表
                $sheet = $objExcel ->getSheet(0);
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn = $sheet->getHighestColumn(); // 取得总列数
                $a=0;
                //将表格里面的数据循环到数组中
                $adminInfo = Cookie::get('adminInfo');
                
                for($i=4;$i<=$highestRow;$i++)
                {
                    if (!rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue())) {
                        break;
                    }
                    //个人社保号
                    $data[$a]['name'] = rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue());
                    $data[$a]['sex'] = rtrim($objExcel->getActiveSheet()->getCell("C".$i)->getValue());
                    $data[$a]['pid'] = rtrim($objExcel->getActiveSheet()->getCell("D".$i)->getValue());
                    $data[$a]['socialSecurity'] = rtrim($objExcel->getActiveSheet()->getCell("E".$i)->getValue());
                    $data[$a]['monthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("F".$i)->getValue());

                    $data[$a]['surplusMonthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("G".$i)->getValue());
                    $data[$a]['start_date'] = rtrim($objExcel->getActiveSheet()->getCell("H".$i)->getValue());
                    $data[$a]['end_date'] = rtrim($objExcel->getActiveSheet()->getCell("I".$i)->getValue());
                    $data[$a]['benefits'] = rtrim($objExcel->getActiveSheet()->getCell("J".$i)->getValue());

                    $data[$a]['address'] = rtrim($objExcel->getActiveSheet()->getCell("K".$i)->getValue());
                    $data[$a]['bankAccount'] = rtrim($objExcel->getActiveSheet()->getCell("L".$i)->getValue());
                    $data[$a]['tel'] = rtrim($objExcel->getActiveSheet()->getCell("M".$i)->getValue());
                    $data[$a]['bankBranch'] = rtrim($objExcel->getActiveSheet()->getCell("N".$i)->getValue());

                    $data[$a]['zj_name'] = rtrim($objExcel->getActiveSheet()->getCell("O".$i)->getValue());

                    $data[$a]['date'] = date('Y-m',time());
                    $data[$a]['file_id'] = $syj_file_id;
                    $a++;
                }
                //往数据库添加数据
                
                $res = Db::name('syj_jbw_data')->insertAll($data);
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

    //导入镇街汇总文件
    public function import_zj()
    {
        if(request()->isPost()){
            $file = request()->file('file_stu');
            if ($file == NULL) {
                $this->error('请选择文件！');
            }
            //检查本月当前账号是否有此类文件导入
            //获取上传者id与名称
            $adminInfo = Cookie::get('adminInfo');
            $res = Db::table('syj_file')->where('type',config('stage')['初二'])->where('state','1')->whereTime('create_time', 'm')->where('up_id',$adminInfo['id'])->find();
            if ($res) {
                $this->error('当月已上传该文件,如需重新上传,请删除本月文件后再上传。');
            }
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' .DS.'uploads'. DS . 'syjexcel');
            if($info){
                //获取文件所在目录名
                $path=ROOT_PATH . 'public' . DS.'uploads'.DS .'syjexcel/'.$info->getSaveName();
                //把文件路径加入数据库
                $syj_file['title'] = date('Y-m', strtotime(date('Y-m-01') . " - 1 month")).$adminInfo['username'].'表';
                $syj_file['type'] = config('stage')['初二'];
                $syj_file['url'] = $path;
                $syj_file['create_time'] = date('Y-m-d H:i:s',time());
                $syj_file['up_id'] = $adminInfo['id'];
                $syj_file_id = Db::table('syj_file')->insertGetId($syj_file);
                if (!$syj_file_id) {
                    $this->error('文件上传失败,请稍后重试');
                }
                //加载PHPExcel类
                vendor("PHPExcel.PHPExcel");

                if (!file_exists($path)) {
                    die('no file!');
                }
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($extension =='xlsx') {
                    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
                    $objReader = new \PHPExcel_Reader_Excel2007();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                } else if ($extension =='xls') {
                    $objReader = new \PHPExcel_Reader_Excel5();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                }

                // $objExcel = $objReader->load($path,$encode='utf-8');//获取excel文件
                //获取excel表中第几个工作表
                $sheet = $objExcel ->getSheet(0);
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn = $sheet->getHighestColumn(); // 取得总列数
                $a=0;
                //将表格里面的数据循环到数组中
                
                for($i=4;$i<=$highestRow;$i++)
                {
                    if (!rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue())) {
                        break;
                    }
                    //个人社保号
                    $data[$a]['name'] = rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue());
                    $data[$a]['sex'] = rtrim($objExcel->getActiveSheet()->getCell("C".$i)->getValue());
                    $data[$a]['pid'] = rtrim($objExcel->getActiveSheet()->getCell("D".$i)->getValue());
                    $data[$a]['socialSecurity'] = rtrim($objExcel->getActiveSheet()->getCell("E".$i)->getValue());
                    $data[$a]['monthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("F".$i)->getValue());

                    $data[$a]['surplusMonthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("G".$i)->getValue());
                    $data[$a]['start_date'] = rtrim($objExcel->getActiveSheet()->getCell("H".$i)->getValue());
                    $data[$a]['end_date'] = rtrim($objExcel->getActiveSheet()->getCell("I".$i)->getValue());
                    $data[$a]['benefits'] = rtrim($objExcel->getActiveSheet()->getCell("J".$i)->getValue());

                    $data[$a]['address'] = rtrim($objExcel->getActiveSheet()->getCell("K".$i)->getValue());
                    $data[$a]['bankAccount'] = rtrim($objExcel->getActiveSheet()->getCell("L".$i)->getValue());
                    $data[$a]['tel'] = rtrim($objExcel->getActiveSheet()->getCell("M".$i)->getValue());
                    $data[$a]['bankBranch'] = rtrim($objExcel->getActiveSheet()->getCell("N".$i)->getValue());

                    $data[$a]['zj_name'] = rtrim($objExcel->getActiveSheet()->getCell("O".$i)->getValue());

                    $data[$a]['date'] = date('Y-m',time());
                    $data[$a]['file_id'] = $syj_file_id;
                    $data[$a]['up_id'] = $adminInfo['id'];
                    $a++;
                }
                //往数据库添加数据
                $res = Db::name('syj_zjup_data')->insertAll($data);
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

    //导入其让数据文件
    public function import_other()
    {
        if(request()->isPost()){
            $file = request()->file('file_stu');
            if ($file == NULL) {
                $this->error('请选择文件！');
            }
            //检查本月是否有此类文件导入
            $checkFileDateRet = $this->checkFileDate('4');
            if ($checkFileDateRet == '1') {
                $this->error('当月已上传该文件,如需重新上传,请在文件管理中心修改文件状态。');
            }
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' .DS.'uploads'. DS . 'syjexcel');
            if($info){
                //获取文件所在目录名
                $path=ROOT_PATH . 'public' . DS.'uploads'.DS .'syjexcel/'.$info->getSaveName();
                //把文件路径加入数据库
                $syj_file['title'] = date('Y-m', strtotime(date('Y-m-01') . " - 1 month")).'其他表';
                $syj_file['type'] = config('stage')['初三'];
                $syj_file['url'] = $path;
                $syj_file['create_time'] = date('Y-m-d H:i:s',time());
                $syj_file_id = Db::table('syj_file')->insertGetId($syj_file);
                if (!$syj_file_id) {
                    $this->error('文件上传失败,请稍后重试');
                }
                //加载PHPExcel类
                vendor("PHPExcel.PHPExcel");

                if (!file_exists($path)) {
                    die('no file!');
                }
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($extension =='xlsx') {
                    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
                    $objReader = new \PHPExcel_Reader_Excel2007();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                } else if ($extension =='xls') {
                    $objReader = new \PHPExcel_Reader_Excel5();
                    $objExcel = $objReader ->load($path,$encode='utf-8');
                }

                // $objExcel = $objReader->load($path,$encode='utf-8');//获取excel文件
                //获取excel表中第几个工作表
                $sheet = $objExcel ->getSheet(0);
                $highestRow = $sheet->getHighestRow(); // 取得总行数
                $highestColumn = $sheet->getHighestColumn(); // 取得总列数
                $a=0;
                //将表格里面的数据循环到数组中
                $adminInfo = Cookie::get('adminInfo');
                
                for($i=4;$i<=$highestRow;$i++)
                {
                    if (!rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue())) {
                        break;
                    }
                    //个人社保号
                    $data[$a]['name'] = rtrim($objExcel->getActiveSheet()->getCell("B".$i)->getValue());
                    $data[$a]['sex'] = rtrim($objExcel->getActiveSheet()->getCell("C".$i)->getValue());
                    $data[$a]['pid'] = rtrim($objExcel->getActiveSheet()->getCell("D".$i)->getValue());
                    $data[$a]['socialSecurity'] = rtrim($objExcel->getActiveSheet()->getCell("E".$i)->getValue());
                    $data[$a]['monthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("F".$i)->getValue());

                    $data[$a]['surplusMonthNumber'] = rtrim($objExcel->getActiveSheet()->getCell("G".$i)->getValue());
                    $data[$a]['start_date'] = rtrim($objExcel->getActiveSheet()->getCell("H".$i)->getValue());
                    $data[$a]['end_date'] = rtrim($objExcel->getActiveSheet()->getCell("I".$i)->getValue());
                    $data[$a]['benefits'] = rtrim($objExcel->getActiveSheet()->getCell("J".$i)->getValue());

                    $data[$a]['address'] = rtrim($objExcel->getActiveSheet()->getCell("K".$i)->getValue());
                    $data[$a]['bankAccount'] = rtrim($objExcel->getActiveSheet()->getCell("L".$i)->getValue());
                    $data[$a]['tel'] = rtrim($objExcel->getActiveSheet()->getCell("M".$i)->getValue());
                    $data[$a]['bankBranch'] = rtrim($objExcel->getActiveSheet()->getCell("N".$i)->getValue());

                    $data[$a]['zj_name'] = rtrim($objExcel->getActiveSheet()->getCell("O".$i)->getValue());

                    $data[$a]['date'] = date('Y-m',time());
                    $data[$a]['file_id'] = $syj_file_id;
                    $a++;
                }
                //往数据库添加数据
                $res = Db::name('syj_other_data')->insertAll($data);
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

    //查看本月是否已经上传过报表
    public function checkFileDate($type)
    {
        $res = Db::table('syj_file')->where('type',$type)->where('state','1')->whereTime('create_time', 'm')->find();
        if ($res) {
            return '1';
        }else{
            return '2';
        }
    }

    //查看是否存在差异数据
    public function check_diff_ex()
    {
        $type = $_POST['type'];
        $res = Db::table('syj_difference')->where('type',$type)->where('state','1')->find();
        if ($res) {
            return array('code'=>'1');
        }else{
            return array('code'=>'2');
        }
    }

    //确认已完成初一表
    public function syj1_accomplish()
    {
        $res = Db::table('syj_fixed_data')
            ->where('state','1')
            ->where('stage',config('stage')['上月'])
            ->where('date',date('Y-m',time()))->update(['stage' => '2']);
        return array('code' => '1');
        
    }

    //确认已完成初二表
    public function syj2_accomplish()
    {
        $res = Db::table('syj_fixed_data')
            ->where('state','1')
            ->where('stage',config('stage')['初一'])
            ->where('date',date('Y-m',time()))->update(['stage' => '3']);
        return array('code' => '1');
    }

    //确认已完成初三表
    public function syj3_accomplish()
    {
        $res = Db::table('syj_fixed_data')
            ->where('state','1')
            ->where('stage',config('stage')['初二'])
            ->where('date',date('Y-m',time()))->update(['stage' => '4']);
        return array('code' => '1');
    }

    //确认已完成定表
    public function syj4_accomplish()
    {
        $res = Db::table('syj_fixed_data')
            ->where('state','1')
            ->where('stage',config('stage')['初三'])
            ->where('date',date('Y-m',time()))->update(['stage' => '5']);
        return array('code' => '1');
    }

    //与金保网数据对比差异
    public function check_difference_jbw()
    {
        //检查差异表中是否有本月的金保网差异数据
        $list = Db::table('syj_difference')->where('date',date('Y-m',time()))->where('state','1')->where('type','1')->select();
        //如果不存在,则开始对比数据
        if (!$list) {
            //思路，先导入金保网表里有，原表里没有的。再导入相同身份证号的数据其中有差异的数据
            $syj_jbw_data_list = Db::table('syj_jbw_data')->where('date',date('Y-m',time()))->where('state','1')->select();
            $pid_arr = [];
            foreach ($syj_jbw_data_list as $key => $value) {
                $pid = $value['pid'];
                $check_fixed_data = Db::table('syj_fixed_data')->where('pid',$pid)->find();
                //如果金保网表里有，原表里没有，则为新数据
                if (!$check_fixed_data) {
                    $pid_arr[$key]['pid'] = $pid;
                    $pid_arr[$key]['type'] = '1';
                    $pid_arr[$key]['instructions'] = "新数据";//正式上线后，文字描述换成数字
                    $pid_arr[$key]['date'] = date('Y-m',time());
                }else{
                    if ($check_fixed_data['name'] != $value['name'] OR $check_fixed_data['socialSecurity'] != $value['socialSecurity'] OR $check_fixed_data['monthNumber'] != $value['monthNumber'] OR $check_fixed_data['benefits'] != $value['benefits'] OR $check_fixed_data['bankAccount'] != $value['bankAccount'] OR $check_fixed_data['tel'] != $value['tel'] OR $check_fixed_data['bankBranch'] != $value['bankBranch'] OR $check_fixed_data['zj_name'] != $value['zj_name']) {
                        $pid_arr[$key]['pid'] = $pid;
                        $pid_arr[$key]['type'] = '1';
                        $pid_arr[$key]['instructions'] = "数据差异";//正式上线后，文字描述换成数字
                        $pid_arr[$key]['date'] = date('Y-m',time());
                    }
                }
            }
            Db::table('syj_difference')->insertAll($pid_arr);
        }

        return $this->fetch();
    }

    //查看金保网数据差异表
    public function check_difference_jbw_show()
    {
        if( request()->isAjax() ){
            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'pid|instructions';
            $where['type'] = '1';

            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_difference')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                        ->select();
                $data_keyword = Db::table('syj_difference')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_difference')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->where($where)
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_difference')
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where)->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;
            //转为json返回
            return json( $info );
        }
    }

    //与镇街数据对比差异
    public function check_difference_zj()
    {
        //先检查文件表中已上传的镇街表
        $file_list = Db::table('syj_file')->alias('f')->join('user u','f.up_id = u.id')->where('f.state','1')->where('f.type','3')->field('f.up_id,u.username')->select();
        foreach ($file_list as $k => $v) {
            $up_id = $v['up_id'];
            $username = $v['username'];
            //检查差异表中是否有本月此id的差异数据
            $res = Db::table('syj_difference')->where('date',date('Y-m',time()))->where('state','1')->where('type','2')->where('zj_id',$up_id)->find();

            //如已存在就不对比,如不存在则开始对比并添加差异数据到差异表中
            if (!$res) {
                //思路，先导入镇街表里有，原表里没有的。再导入相同身份证号的数据其中有差异的数据
                $syj_zj_data_list = Db::table('syj_zjup_data')->where('date',date('Y-m',time()))->where('state','1')->where('up_id',$up_id)->select();

                $pid_arr = [];
                foreach ($syj_zj_data_list as $key => $value) {
                    $pid = $value['pid'];
                    $check_fixed_data = Db::table('syj_fixed_data')->where('pid',$pid)->find();
                    //如果镇街表里有，原表里没有，则为新数据
                    if (!$check_fixed_data) {
                        $pid_arr[$key]['pid'] = $pid;
                        $pid_arr[$key]['type'] = '2';
                        $pid_arr[$key]['instructions'] = "新数据";//正式上线后，文字描述换成数字
                        $pid_arr[$key]['date'] = date('Y-m',time());
                        $pid_arr[$key]['zj_id'] = $value['up_id'];
                        $pid_arr[$key]['zj_name'] = $username;
                    }else{
                        if ($check_fixed_data['name'] != $value['name'] OR $check_fixed_data['socialSecurity'] != $value['socialSecurity'] OR $check_fixed_data['monthNumber'] != $value['monthNumber'] OR $check_fixed_data['benefits'] != $value['benefits'] OR $check_fixed_data['bankAccount'] != $value['bankAccount'] OR $check_fixed_data['tel'] != $value['tel'] OR $check_fixed_data['bankBranch'] != $value['bankBranch'] OR $check_fixed_data['zj_name'] != $value['zj_name']) {
                            $pid_arr[$key]['pid'] = $pid;
                            $pid_arr[$key]['type'] = '2';
                            $pid_arr[$key]['instructions'] = "数据差异";//正式上线后，文字描述换成数字
                            $pid_arr[$key]['date'] = date('Y-m',time());
                            $pid_arr[$key]['zj_id'] = $value['up_id'];
                            $pid_arr[$key]['zj_name'] = $username;
                        }
                    }
                }
                Db::table('syj_difference')->insertAll($pid_arr);
            }
        }
        return $this->fetch();
    }

    //查看镇街数据差异表
    public function check_difference_zj_show()
    {
        if( request()->isAjax() ){
            //Db::table('test') = new UserModel;

            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'pid|instructions|zj_name';

            $where['type'] = '2';
            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_difference')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where)
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_difference')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_difference')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->where($where)
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_difference')
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where)->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;

            // $info = [
            //    'draw'=> request()->post('draw'), // ajax请求次数，作为标识符
            //    'recordsTotal'=>count($data),  // 获取到的结果数(每页显示数量)
            //    'recordsFiltered'=>$cnt,       // 符合条件的总数据量
            //    'data'=>$data,　　　　　　　　　　//获取到的数据结果
            // ];
            //转为json返回
            return json( $info );
        }
    }

    //与other数据对比差异
    public function check_difference_other()
    {
        //检查差异表中是否有本月的other差异数据
        $list = Db::table('syj_difference')->where('date',date('Y-m',time()))->where('state','1')->where('type','3')->select();
        //如果不存在,则开始对比数据
        if (!$list) {
            //思路，先导入other表里有，原表里没有的。再导入相同身份证号的数据其中有差异的数据
            $syj_other_data_list = Db::table('syj_other_data')->where('date',date('Y-m',time()))->where('state','1')->select();
            $pid_arr = [];
            foreach ($syj_other_data_list as $key => $value) {
                $pid = $value['pid'];
                $check_fixed_data = Db::table('syj_fixed_data')->where('pid',$pid)->find();
                //如果other表里有，原表里没有，则为新数据
                if (!$check_fixed_data) {
                    $pid_arr[$key]['pid'] = $pid;
                    $pid_arr[$key]['type'] = '3';
                    $pid_arr[$key]['instructions'] = "新数据";//正式上线后，文字描述换成数字
                    $pid_arr[$key]['date'] = date('Y-m',time());
                }else{
                    if ($check_fixed_data['name'] != $value['name'] OR $check_fixed_data['socialSecurity'] != $value['socialSecurity'] OR $check_fixed_data['monthNumber'] != $value['monthNumber'] OR $check_fixed_data['benefits'] != $value['benefits'] OR $check_fixed_data['bankAccount'] != $value['bankAccount'] OR $check_fixed_data['tel'] != $value['tel'] OR $check_fixed_data['bankBranch'] != $value['bankBranch'] OR $check_fixed_data['zj_name'] != $value['zj_name']) {
                        $pid_arr[$key]['pid'] = $pid;
                        $pid_arr[$key]['type'] = '3';
                        $pid_arr[$key]['instructions'] = "数据差异";//正式上线后，文字描述换成数字
                        $pid_arr[$key]['date'] = date('Y-m',time());
                    }
                }
            }
            Db::table('syj_difference')->insertAll($pid_arr);
        }

        return $this->fetch();
    }

    //查看other数据差异表
    public function check_difference_other_show()
    {
        if( request()->isAjax() ){
            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'pid|instructions';
            $where['type'] = '3';
            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_difference')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                        ->select();
                $data_keyword = Db::table('syj_difference')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_difference')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->where($where)
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_difference')
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where)->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;
            //转为json返回
            return json( $info );
        }
    }

    //通过pid查看金保网差异数据详情
    public function check_jbw_difference_info()
    {
        $pid = input('pid');
        $syj_fixed_data_info = Db::table('syj_fixed_data')->where('pid',$pid)->where('date',date('Y-m',time()))->find();
        $syj_jbw_data_info = Db::table('syj_jbw_data')->where('pid',$pid)->where('date',date('Y-m',time()))->find();
        $this->assign('syj_fixed_data_info',$syj_fixed_data_info);
        $this->assign('syj_jbw_data_info',$syj_jbw_data_info);
        return $this->fetch();
    }

    //通过pid查看镇街差异数据详情
    public function check_zj_difference_info()
    {
        $pid = input('pid');
        $syj_fixed_data_info = Db::table('syj_fixed_data')->where('pid',$pid)->where('date',date('Y-m',time()))->find();
        $syj_zj_data_info = Db::table('syj_zjup_data')->where('pid',$pid)->where('date',date('Y-m',time()))->find();
        $this->assign('syj_fixed_data_info',$syj_fixed_data_info);
        $this->assign('syj_zj_data_info',$syj_zj_data_info);
        return $this->fetch();
    }

    //通过pid查看other差异数据详情
    public function check_other_difference_info()
    {
        $pid = input('pid');
        $syj_fixed_data_info = Db::table('syj_fixed_data')->where('pid',$pid)->where('date',date('Y-m',time()))->find();
        $syj_other_data_info = Db::table('syj_other_data')->where('pid',$pid)->where('date',date('Y-m',time()))->find();
        $this->assign('syj_fixed_data_info',$syj_fixed_data_info);
        $this->assign('syj_other_data_info',$syj_other_data_info);
        return $this->fetch();
    }

    //确认金保网差异数据
    public function confirm_jbw_data()
    {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $pid = $_POST['pid'];
        if ($type == 'syj_fixed_data') {
            //如果选择的是已原表为准，则忽略差异
            Db::table('syj_difference')->where('pid',$pid)->where('type','1')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '0']);
        }else{
            //如果选择的是已金保网数据为准，则修改原表数据，并标记已处理
            $syj_jbw_data = Db::table('syj_jbw_data')->where('state','1')->where('date',date('Y-m',time()))->where('pid',$pid)->find();
            //name sex socialSecurity monthNumber monthNumber-surplusMonthNumber=sendMonthNumber benefits bankAccount tel bankBranch zj_name
            $data['name'] = $syj_jbw_data['name'];
            $data['sex'] = $syj_jbw_data['sex'];
            $data['socialSecurity'] = $syj_jbw_data['socialSecurity'];
            $data['monthNumber'] = $syj_jbw_data['monthNumber'];
            $data['sendMonthNumber'] = $syj_jbw_data['monthNumber']-$syj_jbw_data['surplusMonthNumber'];
            $data['benefits'] = $syj_jbw_data['benefits'];
            $data['bankAccount'] = $syj_jbw_data['bankAccount'];
            $data['tel'] = $syj_jbw_data['tel'];
            $data['bankBranch'] = $syj_jbw_data['bankBranch'];
            $data['zj_name'] = $syj_jbw_data['zj_name'];
            Db::table('syj_fixed_data')->where('pid',$pid)->where('date',date('Y-m',time()))->where('state','1')->update($data);
            //标记已处理
            Db::table('syj_difference')->where('pid',$pid)->where('type','1')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '2']);
        }
        return array('code'=>'1');
    }

    //确认镇街表差异数据
    public function confirm_zj_data()
    {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $pid = $_POST['pid'];
        if ($type == 'syj_fixed_data') {
            //如果选择的是已原表为准，则忽略差异
            Db::table('syj_difference')->where('pid',$pid)->where('type','2')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '0']);
        }else{
            //如果选择的是以镇街数据为准，则修改原表数据，并标记已处理
            $syj_zjup_data = Db::table('syj_zjup_data')->where('state','1')->where('date',date('Y-m',time()))->where('pid',$pid)->find();
            //name sex socialSecurity monthNumber monthNumber-surplusMonthNumber=sendMonthNumber benefits bankAccount tel bankBranch zj_name
            $data['name'] = $syj_zjup_data['name'];
            $data['sex'] = $syj_zjup_data['sex'];
            $data['socialSecurity'] = $syj_zjup_data['socialSecurity'];
            $data['monthNumber'] = $syj_zjup_data['monthNumber'];
            $data['sendMonthNumber'] = $syj_zjup_data['monthNumber']-$syj_zjup_data['surplusMonthNumber'];
            $data['benefits'] = $syj_zjup_data['benefits'];
            $data['bankAccount'] = $syj_zjup_data['bankAccount'];
            $data['tel'] = $syj_zjup_data['tel'];
            $data['bankBranch'] = $syj_zjup_data['bankBranch'];
            $data['zj_name'] = $syj_zjup_data['zj_name'];
            
            Db::table('syj_fixed_data')->where('pid',$pid)->where('date',date('Y-m',time()))->where('state','1')->update($data);
            //标记已处理
            Db::table('syj_difference')->where('pid',$pid)->where('type','2')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '2']);
        }
        return array('code'=>'1');
    }

    public function confirm_new_zj_data()
    {
        $pid = $_POST['pid'];
        //type=1为添加到定表数据内，2为忽略此数据
        if ($_POST['type'] == '2') {
            Db::table('syj_difference')->where('pid',$pid)->where('type','2')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '0']);
        }else{
            //如果选择的是以镇街数据为准，则修改原表数据，并标记已处理
            $syj_zjup_data = Db::table('syj_zjup_data')->where('state','1')->where('date',date('Y-m',time()))->where('pid',$pid)->find();

            //name sex socialSecurity monthNumber monthNumber-surplusMonthNumber=sendMonthNumber benefits bankAccount tel bankBranch zj_name
            $data['name'] = $syj_zjup_data['name'];
            $data['sex'] = $syj_zjup_data['sex'];
            $data['socialSecurity'] = $syj_zjup_data['socialSecurity'];
            $data['monthNumber'] = $syj_zjup_data['monthNumber'];
            $data['sendMonthNumber'] = $syj_zjup_data['monthNumber']-$syj_zjup_data['surplusMonthNumber'];
            $data['benefits'] = $syj_zjup_data['benefits'];
            $data['bankAccount'] = $syj_zjup_data['bankAccount'];
            $data['tel'] = $syj_zjup_data['tel'];
            $data['bankBranch'] = $syj_zjup_data['bankBranch'];
            $data['zj_name'] = $syj_zjup_data['zj_name'];
            $data['pid'] = $pid;
            $data['date'] = date('Y-m',time());
            //查看同一时期数据的stage
            $stage = Db::table('syj_fixed_data')->where('date',date('Y-m',time()))->where('state','1')->field('stage')->find();
            $data['stage'] = $stage['stage'];
            Db::table('syj_fixed_data')->insert($data);
            //标记已处理
            Db::table('syj_difference')->where('pid',$pid)->where('type','2')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '2']);
        }
        return array('code'=>'1');
    }

    //确认other差异数据
    public function confirm_other_data()
    {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $pid = $_POST['pid'];
        if ($type == 'syj_fixed_data') {
            //如果选择的是已原表为准，则忽略差异
            Db::table('syj_difference')->where('pid',$pid)->where('type','3')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '0']);
        }else{
            //如果选择的是已金保网数据为准，则修改原表数据，并标记已处理
            $syj_other_data = Db::table('syj_other_data')->where('state','1')->where('date',date('Y-m',time()))->where('pid',$pid)->find();
            //name sex socialSecurity monthNumber monthNumber-surplusMonthNumber=sendMonthNumber benefits bankAccount tel bankBranch zj_name
            $data['name'] = $syj_other_data['name'];
            $data['sex'] = $syj_other_data['sex'];
            $data['socialSecurity'] = $syj_other_data['socialSecurity'];
            $data['monthNumber'] = $syj_other_data['monthNumber'];
            $data['sendMonthNumber'] = $syj_other_data['monthNumber']-$syj_other_data['surplusMonthNumber'];
            $data['benefits'] = $syj_other_data['benefits'];
            $data['bankAccount'] = $syj_other_data['bankAccount'];
            $data['tel'] = $syj_other_data['tel'];
            $data['bankBranch'] = $syj_other_data['bankBranch'];
            $data['zj_name'] = $syj_other_data['zj_name'];
            Db::table('syj_fixed_data')->where('pid',$pid)->where('date',date('Y-m',time()))->where('state','1')->update($data);
            //标记已处理
            Db::table('syj_difference')->where('pid',$pid)->where('type','3')->where('date',date('Y-m',time()))->where('state','1')->update(['state' => '2']);
        }
        return array('code'=>'1');
    }


    //失业金申领上传情况
    public function checkUpload()
    {
        
        $where['state'] = '1';
        $where['type'] = Config::get('stage')['初二'];
        $syjapply_list = Db::table('syj_file')->where($where)->whereTime('create_time','m')->field('up_id')->select();
        $yd = array();
        foreach ($syjapply_list as $key => $value) {
            $yd[$key] = $value['up_id'];
        }
        $street_list = Config::get('street_id');
        $wd = array_diff($street_list, $yd);
        $wd = implode(',', $wd);
        $admin_list = Db::query("select * FROM user WHERE id in (".$wd.")");
        $this->assign('admin_list',$admin_list);
        return view('Index/checkUpload');
    }

    //查看镇街上传的数据
    public function check_zj_list()
    {
        $this->assign('res',input());
        return $this->fetch();
    }

    public function syj_zj_data()
    {
        if( request()->isAjax() ){
            $where['up_id'] = $_POST['up_id'];
            //Db::table('test') = new UserModel;

            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|bankBranch';

            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_zjup_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                        ->select();
                $data_keyword = Db::table('syj_zjup_data')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->where($where)
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_zjup_data')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->where($where)
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_zjup_data')
                        ->where('state','1')
                        ->where($where)
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;

            // $info = [
            //    'draw'=> request()->post('draw'), // ajax请求次数，作为标识符
            //    'recordsTotal'=>count($data),  // 获取到的结果数(每页显示数量)
            //    'recordsFiltered'=>$cnt,       // 符合条件的总数据量
            //    'data'=>$data,　　　　　　　　　　//获取到的数据结果
            // ];
            //转为json返回
            return json( $info );
        }
    }

    //查看镇街汇总数据
    public function check_zj_all_list()
    {
        
        return $this->fetch();
    }

    public function syj_zj_all_data()
    {
        if( request()->isAjax() ){

            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'socialSecurity|name|pid|bankAccount|bankBranch';

            // var_dump($limit_start,$limit_length);die;

            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_zjup_data')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_zjup_data')
                                ->where('state','1')
                                ->where('date',date('Y-m',time()))
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_zjup_data')
                ->where('state','1')
                ->where('date',date('Y-m',time()))
                ->where($where)
                ->order("date $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_zjup_data')
                        ->where('state','1')
                        ->where('date',date('Y-m',time()))->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }   

            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;

            //转为json返回
            return json( $info );
        }
    }

    //删除镇街数据
    public function syj_zj_del()
    {
        $id = $_POST['id'];
        $up_id = $_POST['up_id'];
        Db::table('syj_file')->where('id',$id)->whereTime('create_time', 'm')->update(['state' => '0']);
        $where['up_id'] = $up_id;
        $where['date'] = date('Y-m',time());
        Db::table('syj_zjup_data')->where($where)->update(['state' => '0']);
        return array('code'=>'1');
    }

    //查询人员信息
    public function check_user()
    {
        $where['state'] = '1';
        $where['date'] = date('Y-m',time());
        switch ($_POST['type']) {
            case 'pid':
                $where['pid'] = $_POST['data'];
                break;
            case 'socialSecurity':
                $where['socialSecurity'] = $_POST['socialSecurity'];
                break;
        };
        $res = Db::table('syj_fixed_data')->where($where)->find();
        if ($res) {
            return array('code' => '1');
        }else{
            return array('code' => '2');
        }
    }

    //表格导出处理
    public function export_syj5(){
        //1.从数据库中取出数据
        $where['state'] = '1';
        $where['date'] = date('Y-m',time());
        $where['stage'] = '5';
        $list = Db::name('syj_fixed_data')->where($where)->select();
        //2.加载PHPExcle类库
        vendor('PHPExcel.PHPExcel');
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '序号')                      
                ->setCellValue('B1', '个人社保号')
                ->setCellValue('C1', '姓名')
                ->setCellValue('D1', '性别')
                ->setCellValue('E1', '身份证号码')
                ->setCellValue('F1', '年龄')
                ->setCellValue('G1', '帐号')                      
                ->setCellValue('H1', '具体到支行')
                ->setCellValue('I1', '收款账号开户行')
                ->setCellValue('J1', '收款账号省份')
                ->setCellValue('K1', '收款账号地市')
                ->setCellValue('L1', '收款账号地区码')
                ->setCellValue('M1', '汇款用途')                      
                ->setCellValue('N1', '基本保险金')
                ->setCellValue('O1', '备注')
                ->setCellValue('P1', '应发月数')
                ->setCellValue('Q1', '已发月数')
                ->setCellValue('R1', '联系电话')              
                ->setCellValue('S1', '镇街名称')
                ->setCellValue('T1', '备注');
        //设置F列水平居中
        // $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()
        //             ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // //设置单元格宽度
        // $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(15);
        // $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(30); 
        //6.循环刚取出来的数组，将数据逐一添加到excel表格。
        for($i=0;$i<count($list);$i++){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$i+1);//添加ID
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$list[$i]['socialSecurity']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$list[$i]['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$list[$i]['sex']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$list[$i]['pid']);
            $i1 = $i+2;
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($i+2),'=YEAR(NOW())-MID(E'.$i1.',7,4)');
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($i+2),$list[$i]['bankAccount']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($i+2),$list[$i]['bankBranch']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($i+2),$list[$i]['startBank']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($i+2),$list[$i]['accountProvince']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($i+2),$list[$i]['accountCity']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($i+2),$list[$i]['accountArea']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.($i+2),$list[$i]['remittancePurpose']);
            $objPHPExcel->getActiveSheet()->setCellValue('N'.($i+2),$list[$i]['benefits']);
            $objPHPExcel->getActiveSheet()->setCellValue('O'.($i+2),$list[$i]['remarks']);
            $objPHPExcel->getActiveSheet()->setCellValue('P'.($i+2),$list[$i]['monthNumber']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q'.($i+2),$list[$i]['sendMonthNumber']);
            $objPHPExcel->getActiveSheet()->setCellValue('R'.($i+2),$list[$i]['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('S'.($i+2),$list[$i]['zj_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('T'.($i+2),$list[$i]['other_remarks']);
        }
        //7.设置保存的Excel表格名称
        $filename = date('Y-m',time()).'定表数据.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('定表数据');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");  
        header("Content-Type: application/octet-stream");  
        header("Content-Type: application/download");  
        header('Content-Disposition:inline;filename="'.$filename.'"');  
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
    }

    //查询人员信息
    public function check_user_data()
    {
        if (input('pid')) {
            $where['pid'] = input('pid');
            $where['state'] = '1';
            $res = Db::table('syj_fixed_data')->where($where)->select();
            $this->assign('data',$res);
        }
        return $this->fetch();
    }

   
}
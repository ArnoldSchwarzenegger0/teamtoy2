<?php
// include_once( AROOT . 'lib' . DS . 'QyWechat.class.php');
// include_once( AROOT . 'lib' . DS . 'FlieCache.class.php');
// 实例化企业号API库
function QyWechat()
{
	$options = array(
	    'token'          => c('TOKEN'), //填写应用接口的Token
	    'encodingaeskey' => c('ENCODINGAESKEY'), //填写加密用的EncodingAESKey
	    'appid'          => c('CORPID'), //填写高级调用功能的appid
	    'appsecret'      => c('SECRET'),
	    'agentid'        => c('AGENTID'),
	);
	$weObj = new QyWechat($options);
	return $weObj;
}

// 设置，读取，缓存
function cache($name,$value='',$options=null)
{
	static $cache   =   '';
	if(is_array($options) && empty($cache)){
		$cache = new FileCache($options['expire'], c('cache_path'));
	}else{
		return false;
	}
	if(''=== $value){ // 获取缓存
        return $cache->get($name);
    }elseif(is_null($value)) { // 删除缓存
        return $cache->rm($name);
    }else { // 缓存数据
        if(is_array($options)) {
            $expire     =   isset($options['expire'])?$options['expire']:NULL;
        }else{
            $expire     =   is_numeric($options)?$options:NULL;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}

// 两个二维数组进行比较，返回差集
function array_diff_assoc2($array1, $array2) {
    $result=array();
    foreach ($array1 as $key => $value) {
    	if(!in_array($value,$array2)){
            // $value['userid']=$value['userid'];
    	    $result[]=$value;
   		}
   	}
    return $result;
}

//二维数组去掉重复值,并保留键值
function array_unique_fb($array2D){
    if (!empty($array2D)) {
        foreach ($array2D as $k=>$v){
            $v=join(',',$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k]=$v;
        }
        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的一维数组    
        foreach ($temp as $k => $v){
            $array=explode(',',$v); //再将拆开的数组重新组装
            //下面的索引根据自己的情况进行修改即可
            $temp2[$k]['id'] =$array[0];
            $temp2[$k]['name'] =$array[1];
            $temp2[$k]['parentid'] =$array[2];
        }
        return $temp2;
    }else{
        return false;
    }
}
/**
 * 给指定的人发送文本信息
 * @param int $uid 接收消息人的id
 * @param string $content 消息内容
 */
function sendmsg($uid,$content)
{
    $sql="select eid from user where id=".$uid;
    $eid=get_var($sql);
    $msg = array(
        'touser' => $eid,
        'msgtype' =>"text",
        'agentid' =>c('AGENTID'),
        'text'=>array('content' => $content),
        'safe'=>0
    );
    $msginfo=QyWechat()->sendMessage($msg);
}

/**
 * 获取指定日期的当月的第一天
 * @param int $date 指定日期的时间戳
 */
function getMonthFirstDay($date=null) {
    if(!$date){
        $date=time();
    }else{
        $date=strtotime($date);
    }
    return strtotime(date('Y-m-01', $date));
}
/**
 * 获取指定日期的当月的最后一天最后一秒
 * @param int $date 指定日期的时间戳
 */
function getMonthLastDay($date=null) {
    if(!$date){
        $date=time();
    }else{
        $date=strtotime($date);
    }
    return strtotime(date('Y-m-01 23:59:59', $date) . ' +1 month-1 day');
}
// 多维数组按键排序
function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){ 
    if(is_array($arrays)){ 
        foreach ($arrays as $array){ 
            if(is_array($array)){ 
                $key_arrays[] = $array[$sort_key]; 
            }else{ 
                return false; 
            } 
        } 
    }else{ 
        return false; 
    }
    array_multisort($key_arrays,$sort_order,$sort_type,$arrays); 
    return $arrays; 
}

// 导出excel考勤表
function export($data)
{
    $objPHPExcel = PHPExcel_IOFactory::load(iconv('UTF-8','GB2312',AROOT . 'template' . DS . '考勤数据.xlsx'));
    // $objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('A2:AC2'), 'A6:K13' );
    $objPHPExcel->getActiveSheet()->fromArray($data, NULL, 'A2');
    $file_name='考勤数据(有问题).xlsx';

    // Redirect output to a client’s web browser (Excel2007)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$file_name.'"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}
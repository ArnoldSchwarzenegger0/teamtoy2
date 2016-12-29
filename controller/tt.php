<?php
/*
*@使用方法。
*引入类库。
*$excel = news excelC();
*$excel->fileName = '文件名称';//设置文件名称，默认为时间戳
*$excel->format = '2007';//文件类型，默认为2007，其他为excel5
*$record = array(
    'delKey'=>array('id','addTime','status'),//如果数据$data中有不需要显示的列，可以在此说明。删除不需要导出的键值
    'sort'=>array(
        'keyName'=>array('subjectName','flag'),//按keyName列排序，如果不存在则不排序。
        //'reorder'=>'DESC',//排序方式，DESC为倒序，ASC为正序。如果keyName存在则排序keyName，如果不存在则按数组的键名排序，如果reorder不存在则不排序
    ),//排序   如果sort存在则排序，否则不排序，如果keyName存在则按设置排序，如果不存在则按字符排序，如果reorder不存在或为空或为DES则正序，等于DESC为倒序。
    'excelStyle'=>array(
        'setName'=>'Arial',//字体样式
        'setSize'=>'12',//字体大小
    ),//表格全局样式
        'title'=>array(
        'tableName'=>'学科列表',
        'center'=>true,
        'direction'=>'right',
        'merge'=>'2',
        'setSize'=>'30'),//标题,center垂直,direction为合并方向。right,left,up,down。 merge为合并几个单元格，setSize为字体大小
    'data'=>array(
        array(
            'tableName'=>'科目名称',
            'width'=>'30','setName'=>'宋体',
            'setSize'=>'20',
            'background'=>'red',
            'textColor'=>'white',
            'bold'=>true,
            'underline'=>true,
            'borderColor'=>'cyan',
            'center'=>true,
        ),
        array(
            'tableName'=>'学科', //表名称 tableName为名称
            'width'=>'50', //width为表格宽度
            'center'=>true
        ),//颜色表是：black,white,red,green,blue,yellow,magenta,cyan
    ),setName为字体样式，background为背景颜色,textColor为字体颜色，bold为加粗,underline为下划线，borderColor为边框颜色.
    'merge'=>array(
        //'flag'=>array('keyword'=>'初','direction'=>'right','merge'=>'2'),merge的键值为需要处理数据数组的键值，keyword为如果存在此关键字才执行其他样式操作，如果keyword不存在则执行所有键值为flag的单元格。
        'all'=>array('width'=>'30','setName'=>'宋体','setSize'=>'20','background'=>'red','textColor'=>'white','bold'=>true,'underline'=>true,'borderColor'=>'cyan','center'=>true,),
    ),//
);//导出配置
 
 
*$excel->export($record,$data);//$record为导出配置，$data为数据库的数据,$data可以为数组，也可以为对象。
*
*
*
*/
$address = dirname(dirname(__FILE__)).'/PHPExcel';
include $address.'/PHPExcel.class.php';
include $address.'/PHPExcel/Writer/Excel2007.php';
include $address.'/PHPExcel/Writer/Excel5.php';
include $address.'/PHPExcel/IOFactory.php';
 
/****************************
*生成excel文档。
*/
 
class excelC {
     
    public $format = '2007';//转换格式，默认为2007版本，其他版本，请输入不是2007的数字
     
    public $fileName;//文件名称默认为时间戳。
     
     
    private $objExcel;
     
    private $letters;
     
    public function __construct()
    {
        $this->fileName = time();
         
        $this->fileTitle = '导出数据';
         
        $this->objExcel = new PHPExcel();
        $this->letters = $this->letter();
    }
     
     
    //导出excel的属性
    private function attribute(){
         
         
        $this->objExcel->getProperties()->setCreator("力达行有限公司");//创建人
         
        $this->objExcel->getProperties()->setLastModifiedBy("力达行有限公司");//最后修改人
         
        $this->objExcel->getProperties()->setTitle("导出数据");//标题
         
        $this->objExcel->getProperties()->setSubject("导出数据");//题目
         
        $this->objExcel->getProperties()->setDescription("数据导出");//描述
         
        $this->objExcel->getProperties()->setKeywords("office 导出");//关键字
         
        $this->objExcel->getProperties()->setCategory("excel");//种类
    }
     
     
     
    //设置表(如果只有一个sheet可以忽略该函数，将默认创建。)
    private function sheet(){
         
        $this->objExcel->setActiveSheetIndex(0);//设置当前的表
         
        $this->objExcel->getActiveSheet()->setTitle('excel');//设置表名称。
    }
     
     
    /***************************
    *导出excel
    *@attr $record为表头及样式设置
    *@attr $data为需要导出的数据
    */
    public function export($record=array(),$data=array()){
        if(!$data)return false;
        if(!is_array($record))return false;//表样式及其他设置
         
        //处理获取到的数据
        $data = $this->maniData($record,$data);
         
        //获取整体样式。
        $this->excelData($record,$data);
         
         
        //$this->objExcel->getActiveSheet()->setCellValue('A1', '季度');
         
        $this->down();//导出下载
    }
     
     
    /*
    *处理表格
    */
    private function excelData(&$record,&$data){
        $this->attribute();//设置属性
        $this->sheet();//设置表
        $this->whole($record);//设置整体样式
        $this->tableHeader($record);//设置表格头。
        $this->tableContent($record,$data);//设置表格
        $this->excelTitle($record,2);//设置标题
    }
     
    /*
    *设置表格整体样式
    */
    private function whole(&$record){
        if(!array_key_exists('excelStyle',$record))return false;
         
        $excelStyle = $record['excelStyle'];
         
        $default = $this->objExcel->getDefaultStyle();
         
        if(array_key_exists('setName',$excelStyle))
            $default->getFont()->setName($excelStyle['setName']);//设置字体样式
             
        if(array_key_exists('setSize',$excelStyle))
            $default->getFont()->setSize($excelStyle['setSize']);//设置字体大小
    }
     
    /*
    *设置标题
    */
    private function excelTitle($record,$num){
        $titleL = $this->letters[0];
        if(!array_key_exists('title',$record))return false;
        $this->appOintStyle($titleL ,1,$record['title']);
         
    }
     
    /*
    *设置表格头。
    */
    private function tableHeader($record){
        if(!array_key_exists('data',$record))return false;
        $objExcel = $this->objExcel;
        $letters = $this->letters;
         
        if(!is_array($record['data']))return false;
         
        $i = 0;
        $hang = 2;
        foreach($record['data'] as $k=>$v){
         
            $this->appOintStyle($letters[$i],$hang,$v);
                 
            $i++;
        }
         
    }
     
    private function setCellValue($letter,$data){
     
        if(@$data)
            $this->objExcel->getActiveSheet()->setCellValue($letter, $data);//填充值
         
        return $this;
    }
     
    private function getColumnDimension($letter,$data){
     
        if(@$data)
            $this->objExcel->getActiveSheet()->getColumnDimension($letter)->setWidth($data);//设置宽度
             
        return $this;
    }
     
    private function setName($letter,$data){
     
        if(@$data)
            $this->objExcel->getActiveSheet()->getStyle($letter)->getFont()->setName($data);//设置字体
             
        return $this;
    }
     
    private function setSize($letter,$data){
     
        if(@$data)
             $this->objExcel->getActiveSheet()->getStyle($letter)->getFont()->setSize($data);//设置字体大小
             
        return $this;
    }
     
    private function background($letter,$data){
     
        if(@$data){
            $this->objExcel->getActiveSheet()->getStyle($letter)->getFill()->getStartColor()->setARGB($this->backColor($data));
            $this->objExcel->getActiveSheet()->getStyle($letter)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);//设置背景色样式，无样式将不显示背景色。
        }
             
        return $this;
    }
     
    private function textColor($letter,$data){
     
        if(@$data){
            $this->objExcel->getActiveSheet()->getStyle($letter)->getFont()->getColor()->setARGB($data);//字体颜色
        }
             
        return $this;
    }
     
    private function setBold($letter,$data){
     
        if(@$data){
            $this->objExcel->getActiveSheet()->getStyle($letter)->getFont()->setBold(true);//加粗
        }
             
        return $this;
    }
     
    private function setUnderline($letter,$data){
     
        if(@$data){
            $this->objExcel->getActiveSheet()->getStyle($letter)->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);//下划线
        }
             
        return $this;
    }
     
    private function border($letter,$data){
     
        if(@$data){
            $styleThinBlackBorderOutline = array(
                           'borders' => array (
                                 'outline' => array (
                                       'style' => PHPExcel_Style_Border::BORDER_THIN, //设置border样式
                                       'color' => array ('argb' => $data),          //设置border颜色
                                ),
                          ),
                    );
            $this->objExcel->getActiveSheet()->getStyle($letter)->applyFromArray($styleThinBlackBorderOutline);
        }
             
        return $this;
    }
     
    /*
    *合并
    */
    private function mergeCells($letters,$hang,$direction,$merge){
         
        $merge = $merge-1;
         
        if($merge > 0 && $direction){
         
            //print_r($this->letters);
            $l = array_flip($this->letters);
            $ln = $l[$letters];
             
            switch ($direction)
            {
                case 'left':
                    $signal = $this->letters[($ln-$merge)].$hang.':'.$letters.$hang;
                break;
                case 'right':
                    $signal = $letters.$hang.':'.$this->letters[($ln+$merge)].$hang;
                break;
                case 'up':
                    $signal = $letters.($hang-$merge).':'.$letters.$hang;
                break;
                case 'down':
                    $signal = $letters.$hang.':'.$letters.($hang+$merge);
                break;
                default:
                    $signal = '';
            }
             
            if($signal){
                $this->objExcel->getActiveSheet()->mergeCells($signal);
            }
             
        }
         
        return $this;
    }
     
    /*
    *垂直居中
    */
    private function setVertical($letter,$data){
         
        if($data){
            $this->objExcel->getActiveSheet()->getStyle($letter)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $this->objExcel->getActiveSheet()->getStyle($letter)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        return $this;
         
    }
     
     
     
     
     
     
     
     
     
    /*
    *设置颜色
    */
    private function backColor($color){
         
        $array = array(
            'black' => 'FF000000',   //  System Colour #1 - Black
            'white' => 'FFFFFFFF',   //  System Colour #2 - White
            'red'   => 'FFFF0000',   //  System Colour #3 - Red
            'green' => 'FF00FF00',   //  System Colour #4 - Green
            'blue'  => 'FF0000FF',   //  System Colour #5 - Blue
            'yellow'    => 'FFFFFF00',   //  System Colour #6 - Yellow
            'magenta'   => 'FFFF00FF',   //  System Colour #7- Magenta
            'cyan'  => 'FF00FFFF',   //  System Colour #8- Cyan
        );
        if(array_key_exists($color,$array)){
            return $array[$color];
        } else {
            return false;
        }
                     
    }
    /*
    *设置表
    */
    private function tableContent(&$record,&$data){
        $objExcel = $this->objExcel;
        $letters = $this->letters;
         
        if(array_key_exists('merge',$record))
            $merge = $record['merge'];
        else
            $merge = '';
                     
        $hang = 2;
        foreach($data as $k=>$v){
            $i=0;
            $hang++;
             
            foreach($v as $kk=>$vv){
                $this->setCellValue($letters[$i].$hang, $vv);//设置内容
                $this->Appoint($kk,$vv,$letters[$i],$hang,$merge);
                $i++;
            }
             
        }
    }
     
    /*
    *设置表指定样式
    */
    private function Appoint($kk,$vv,$letters,$hang,$merge){
        if(!$merge)return false;
        if(array_key_exists($kk,$merge)){
            $v = $merge[$kk];
            if(array_key_exists('keyword',$v)){
             
                if(strpos($vv,$v['keyword']) > -1){
                    $this->appOintStyle($letters,$hang,$v);
                }
                 
            } else {
                $this->appOintStyle($letters,$hang,$v);
            }
        } else if(array_key_exists('all',$merge)){
            $v = $merge['all'];
            if(array_key_exists('keyword',$v)){
             
                if(strpos($vv,$v['keyword']) > -1){
                    $this->appOintStyle($letters,$hang,$v);
                }
                 
            } else {
                $this->appOintStyle($letters,$hang,$v);
            }
        }
    }
     
    /*
    *终极样式
    */
    private function appOintStyle($letters,$hang,$v){
     
        $this
                ->setCellValue($letters.$hang,@$v['tableName'])
                ->getColumnDimension($letters,@$v['width'])
                ->setName($letters.$hang,@$v['setName'])
                ->setSize($letters.$hang,@$v['setSize'])
                ->background($letters.$hang,@$v['background'])
                ->textColor($letters.$hang,$this->backColor(@$v['textColor']))
                ->setBold($letters.$hang,@$v['bold'])
                ->setUnderline($letters.$hang,@$v['underline'])
                ->border($letters.$hang,$this->backColor(@$v['borderColor']))
                ->mergeCells($letters,$hang,@$v['direction'],@$v['merge'])
                ->setVertical($letters.$hang,@$v['center']);
    }
     
    /*
    *应为字母列表
    */
    public function letter(){
        return array('A','B','C','D','F','G','H','I','G','K','L','M','N','O','P','Q','R','S','T','U','V','W','H','Y','Z');
    }
     
    /****************************
    *处理数据，排序及删除字段
    */
    private function maniData($record,$data){
        if(!$data)return false;
        if(!is_array($record))return false;//表样式及其他设置
     
        $data = $this->objectToArray($data);//对象转数组
         
        $delKey = (array_key_exists('delKey',$record))?$record['delKey']:'';//是否删除关键字
        $sort = (array_key_exists('sort',$record))?$record['sort']:'';//是否排序
        $data = $this->delSort($data,$delKey,$sort);
        return $data;
    }
     
    /****************************
    *对象转数组
    */
    private function objectToArray($data){
        if(!$data)return false;
        $data = (array)$data;
         
        foreach($data as $k=>$v){
            if(is_object($v) || is_array($v)){
                $data[$k] = (array)$this->objectToArray($v);
            }
        }
        return $data;
    }
     
    /****************************
    *删除键值，并排序
    */
    private function delSort($data,$delKey='',$sort=''){
     
        if(!$data)return false;
         
        $array = array();
        foreach($data as $k=>$v){
         
            //删除数据中的某个键值
            $delData = $this->delData($v,$delKey);
            //按设定键值排序
            $sortData = $this->sortData($delData,$sort);
            $array[$k] = $sortData;
        }
         
        return $array;
         
    }
     
    /****************************
    *删除键值
    */
    public function delData($data,&$delKey){
        if($delKey){
            foreach($delKey as $delVal){
                if(array_key_exists($delVal,$data))//判断键值是否存在
                    unset($data[$delVal]);//清除键名。
            }
        }
        return $data;
    }
     
    /****************************
    *键值排序
    */
    public function sortData($data,&$sort){
        $array = array();
        if($sort){
            if(array_key_exists('keyName',$sort)){
                $keyName = $sort['keyName'];
                if(array_key_exists('reorder',$sort)){
                    if($sort['reorder'] == 'DESC'){
                        krsort($keyName);
                    } else if($sort['reorder'] == 'ASC'){
                        ksort($keyName);
                    }
                }
                foreach($keyName as $vn){
                    $array[$vn] = (array_key_exists($vn,$data))?$data[$vn]:'';
                }
            } else {
                if(array_key_exists('reorder',$sort)){
                    if($sort['reorder'] == 'DESC'){
                        krsort($data);
                    } else if($sort['reorder'] == 'ASC'){
                        ksort($data);
                    }
                    $array = $data;
                }
            }
             
        }
        return $array;
    }
     
     
     
    //导出下载
    private function down(){
         
        if($this->format == '2007'):
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $excel = 'Excel2007';
        else:
            header('Content-Type: application/vnd.ms-excel');
            $excel = 'Excel5';
        endif;
         
        header("Content-Disposition: attachment; filename="$this->fileName"");
        header('Cache-Control: max-age=0');
         
        $objWriter = PHPExcel_IOFactory::createWriter($this->objExcel, $excel);
 
        $objWriter->save('php://output');
         
    }
}
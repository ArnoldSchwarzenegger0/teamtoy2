<?php
/***
TeamToy extenstion info block
##name SYNC
##folder_name sync
##author DHC
##email 947666385@qq.com
##reversion 1
##desp sync 同步微信企业号通讯录、组织架构信息到本地数据库。
##update_url http://tt2net.sinaapp.com/?c=plugin&a=update_package&name=todo_flow
##reverison_url http://tt2net.sinaapp.com/?c=plugin&a=latest_reversion&name=todo_flow
 ***/
// todo flow
// a flow view of all todos
if (!defined('IN')) {
    die('bad request');
}

$plugin_lang          = array();
$plugin_lang['zh_cn'] = array
    (
    'PL_SYNC_TITLE'       => '同步成员信息',
    'PL_SYNC_TODO_TIME'   => '最后活动时间 - %s',
    'PL_SYNC_NO_TODO_NOW' => '暂无TODO',
    'PL_SYNC_TEST'        => '',
);
// 创建用户表



plugin_append_lang($plugin_lang);

add_action('PLUGIN_INSTALL', 'install');
function install()
{

    if (is_admin()) {
        if( !my_sql("SHOW COLUMNS FROM `user111`") ){
            run_sql("CREATE TABLE `user111` ( `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'uid', `name` varchar(32) NOT NULL COMMENT '姓名', `pinyin` varchar(32) DEFAULT NULL COMMENT '姓名拼音', `email` varchar(64) NOT NULL COMMENT '邮箱', `password` varchar(32) NOT NULL COMMENT 'md5后的密码值', `avatar_small` varchar(255) DEFAULT NULL COMMENT '小头像', `avatar_normal` varchar(255) DEFAULT NULL COMMENT '大头像', `gender` tinyint(10) DEFAULT NULL COMMENT '性别', `position` varchar(50) DEFAULT NULL COMMENT '职位', `post` int(11) DEFAULT NULL COMMENT '岗位', `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '用户组', `department` varchar(100) DEFAULT NULL COMMENT '部门', `timeline` datetime DEFAULT NULL,`settings` mediumtext, `is_closed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '帐号状态（0-开启，1-关闭）', `mobile` varchar(32) DEFAULT NULL COMMENT '手机号码', `tel` varchar(32) DEFAULT NULL COMMENT '分机号码', `eid` varchar(32) DEFAULT NULL COMMENT '员工号', `weibo` varchar(32) DEFAULT NULL COMMENT '微博', `wechat` varchar(32) DEFAULT NULL COMMENT '微信', `desp` text COMMENT '备注', `groups` varchar(255) DEFAULT NULL COMMENT '分组', `status` tinyint(2) DEFAULT NULL COMMENT '关注状态', PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`), UNIQUE KEY `email` (`email`), KEY `is_closed` (`is_closed`), KEY `groups` (`groups`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户表'");
        }
        return 'ok';
    }
}

add_action('UI_NAVLIST_LAST', 'syncIcon');
function syncIcon()
{
    if (is_admin()) {
    ?><li <?php if (g('c') == 'plugin' && g('a') == 'sync'): ?>class="active"<?php endif;?>><a href="?c=plugin&a=sync" title="<?=__('PL_SYNC_TITLE')?>" >
	<div><img src="plugin/sync/sync.png"/></div></a>
	</li>
	<?php
    }
}

add_action('PLUGIN_SYNC', 'sync');
function sync()
{
    $data['top']  = $data['top_title']  = __('PL_SYNC_TITLE');
    $data['uids'] = get_data("SELECT `id` FROM `user` WHERE `is_closed` = 0 AND `level` > 0 ");
    $data['js'][] = 'jquery.masonry.min.js';
    // $data['info'] = 'Hello123';
    // if(is_admin()){
        return render($data, 'web', 'plugin', 'sync');
    // }else{
    //     dump('非法访问！');
    //     return forward('?c=dashboard');
    // }
}

add_action('PLUGIN_SYNCUSERINFO', 'syncUserInfo');
function syncUserInfo()
{
    // 获取企业号通讯录
    $departmentuser=QyWechat()->getUserListInfo(1,1);
    // 获取本地用户数据
    $sql="SELECT eid userid,name,department,position,mobile,gender,email,wechat weixinid,avatar_normal avatar,status FROM user";
    $userlist=get_data($sql);
    $users=[];
    if(!empty($userlist)){
        // 对获取的本地用户数据进行处理
        foreach ($userlist as $key => $value) {
            // 去除本地用户数据中的空数据
            $value=(array_filter($value));
            // 获取用户的部门信息
            $sql1="select deptid from user_dept where eid='".$value['userid']."'";
            $userdept=get_data($sql1);
            // 合并用户信息
            if ($userdept) {
                $dept=[];
                foreach ($userdept as $key1 => $value1) {
                    $dept[]=$value1['deptid'];
                }
                $value['department']=$dept;
                $value['status']=$value['status'];
            }
            $users[]=$value;
        }
    }
    // 需要新增、更新的数据
    $insdata=array_diff_assoc2($departmentuser['userlist'],$users);
    // 需要更新、删除的数据
    $deldata=array_diff_assoc2($users,$departmentuser['userlist']);
    // 需要更新的数据
    $updata=[];
    // 根据$insdata、$deldata的数据提取需要更新的数据
    foreach ($insdata as $key => $value) {
        foreach ($deldata as $key1 => $value1) {
            if ($value['userid']==$value1['userid']) {
                $updata[]=$value;
                unset($insdata[$key]);
                unset($deldata[$key1]);
                break;
            }
        }
    }
    // 更新用户信息
    foreach ($updata as $key => $value) {
        // 进行用户信息更新
        if (!empty($value['avatar'])) {
            $avatar_small=$value['avatar'].'64';
        }else{
            $avatar_small=null;
        }
        $sql="UPDATE user SET name='".$value['name']."', email='".$value['email']."', avatar_normal='".$value['avatar']."',avatar_small='".$avatar_small."', gender='".$value['gender']."', position='".$value['position']."', mobile='".$value['mobile']."', wechat='".$value['weixinid']."',status='".$value['status']."' WHERE eid='".$value['userid']."'";
        // run_sql($sql);
        dump($sql);
        // 获取用户信息
        $sql1="SELECT name, email, avatar_normal avatar, gender, position, mobile, eid userid, wechat weixinid,status FROM  user WHERE eid='".$value['userid']."'";
        // 获取用户的部门信息
        $sql2="SELECT eid, deptid FROM user_dept WHERE eid='".$value['userid']."'";
        $res1=get_line($sql1);
        $res2=get_data($sql2);
        $dept=[];
        $data=[];
        $depins=[];
        $depdel=[];
        // 格式化部门信息
        if($res2){
            foreach ($res2 as $key1 => $value1) {
                $dept[]=$value1['deptid'];
            }
        }
        // 获取需要更新的字段
        foreach ($value as $k => $v) {
            if($value[$k]!=$res1[$k] && $k!="department"){
                $data[$k]=$value[$k];
            }elseif ($k=="department") {
                $depinstemp=array_diff_assoc($value['department'],$dept);
                $depdeltemp=array_diff_assoc($dept,$value['department']);
            }
        }
        // 重置需要新增的部门数据下标
        foreach ($depinstemp as $tempkey => $tempvalue) {
            $depins[]=$tempvalue;
        }
        // 重置需要删除的部门数据下标
        foreach ($depdeltemp as $tkey => $tvalue) {
            $depdel[]=$tvalue;
        }
        /*
         * 对部门数据进行新增、删除、更新操作
         * 1.如果需要更新的部门总数等于需要删除的部门总数进行更新操作
         * 2.如果需要更新的部门总数大于需要删除的部门总数进行更新、新增操作
         * 3.如果需要更新的部门总数小于需要删除的部门总数进行更新、删除操作
         */
        if (count($depins) || count($depdel)) {
            if (count($depins) == count($depdel)) {
                foreach ($depins as $inskey => $insvalue) {
                $sql="update user_dept set deptid='".$depins[$inskey]."' where deptid='".$depdel[$inskey]."' and eid='".$value['userid']."'";
                // run_sql($sql);
                dump($sql);
                }
            }elseif (count($depins) > count($depdel)) {
                for ($i=0; $i <count($depins) ; $i++) {
                    if ($i<=count($depdel)&&count($depdel)>0) {
                        $udsql="update user_dept set deptid='".$depins[$i]."' where deptid='".$depdel[$i]."' and eid='".$value['userid']."'";
                        // run_sql($udsql);
                        dump($udsql);
                    }else{
                        $udsql1="INSERT INTO user_dept(eid,deptid) VALUES ('".$value['userid']."','".$depins[$i]."')";
                        // run_sql($udsql1);
                        dump($udsql1);
                    }
                }
            }else{
                for ($i=0; $i <=count($depdel)-1 ; $i++) {
                    dump($i);
                    if ($i<count($depins)) {
                        $sql="update user_dept set deptid='".$depins[$i]."' where deptid='".$depdel[$i]."' and eid='".$value['userid']."'";
                        // run_sql($sql);
                        dump($sql);
                    }else{
                        $sql="delete from user_dept where deptid='".$depdel[$i]."' and eid='".$value['userid']."'";
                        // run_sql($sql);
                        dump($sql);
                    }
                }
            }
        }
    }
    // 新增用户信息
    foreach ($insdata as $key => $value) {
        $user['name'] = "'".s($value['name'])."'";
        $user['pinyin']="'".s(pinyin(strtolower($value['name'])))."'";
        $user['email'] = "'".s($value['email'])."'";
        $user['password']="'".s(md5('89860310'))."'";
        $user['avatar_small']= empty(s($value['avatar']))?"'".s($value['avatar'])."'":"'".s($value['avatar'])."64'";
        $user['avatar_normal'] = "'".s($value['avatar'])."'";
        $user['gender'] = "'".s($value['gender'])."'";
        $user['position'] = "'".s($value['position'])."'";
        $user['department'] = "'".s(json_encode($value['department']))."'";
        $user['timeline']= "'" . s( date( "Y-m-d H:i:s" ) ) . "'";
        $user['mobile'] = "'".s($value['mobile'])."'";
        $user['eid'] = "'".s($value['userid'])."'";
        $user['wechat'] = "'".s($value['weixinid'])."'";
        $user['status'] = "'".s($value['status'])."'";
        $inssql="INSERT INTO `user` (`name`,`pinyin`,`email`,`password`,`avatar_small`,`avatar_normal`, `gender`, `position`, `department`, `timeline`, `mobile`, `eid`, `wechat`, `status`) VALUES (".join(',',$user).")";
        foreach ($value['department'] as $dkey => $dvalue) {
            $insdep="INSERT INTO user_dept(eid,deptid) VALUES ('".s($value['userid'])."','".$dvalue."')";
            // run_sql($insdep);
            dump($insdep);
        }
        // run_sql($inssql);
        dump("----------->".$inssql);
    }
    // 删除用户表中的基本信息与用户部门关系表中用户部门信息
    foreach ($deldata as $key => $value) {
        // $where=empty($value['userid'])?"name='".$value['name']."'":"eid='".$value['userid'];
        // $delsql="DELETE FROM user WHERE ".$where;
        // run_sql($delsql);
        if(!empty($value['userid'])){
            $deluser="DELETE FROM user WHERE eid='".$value['userid']."'";
            $deldept="DELETE FROM user_dept WHERE eid='".$value['userid']."'";
        }else{
            $deluser="DELETE FROM user WHERE name='".$value['name']."'";
            $deldept="DELETE FROM user_dept WHERE name='".$value['name']."'";
        }
        // run_sql($deluser);
        // run_sql($deldept);
        dump($deluser);
        dump($deldept);
    }
}


add_action('PLUGIN_SYNCDEPARTMENT', 'syncDepartment');
function syncDepartment()
{
    $Departmentlist=QyWechat()->getAllDepartment();
    $sql="select deptid id, deptname name, parentid from dept";
    $dept=get_data($sql);
    if (!empty($dept)) {
        $deptlist=[];
        foreach ($Departmentlist['department'] as $key => $value) {
            $data['id']=$value['id'];
            $data['name']=$value['name'];
            $data['parentid']=$value['parentid'];
            $deptlist[]=$data;
        }
        $diff=array_diff_assoc2($deptlist,$dept);
        $diff1=array_diff_assoc2($dept,$deptlist);
        if (empty($diff)||empty($diff1)) {
            if (!empty($diff)) {
                foreach ($diff as $key => $value) {
                    $indata['deptid']="'".s($value['id'])."'";
                    $indata['deptname']="'".s($value['name'])."'";
                    $indata['parentid']="'".s($value['parentid'])."'";
                    $sql="INSERT INTO `dept` (`deptid`, `deptname`, `parentid`) VALUES (".join(',',$indata).")";
                    // $insres=run_sql( $sql );
                }
            }else{
                foreach ($diff1 as $key => $value) {
                    $sql="DELETE FROM dept WHERE deptid='".$value['id']."'";
                    // $delres=run_sql($sql);
                }
            }
        }else{
            $updata=[];
            $insdata=[];
            $ins=[];
            $deldata=[];
            foreach ($diff1 as $key1 => $value1) {
                foreach ($diff as $key2 => $value2) {
                    if ($value1['id']==$value2['id']) {
                        $updata[]=$value2;
                        $sql="UPDATE dept SET deptname='".$value2['name']."', parentid='".$value2['parentid']."' WHERE deptid='".$value2['id']."'";
                        // $upres=run_sql($sql);
                        // dump($upres.$sql);
                        return ajax_echo('<pre>'.$upres.$sql.'</pre>');
                    }else{
                        $insdata[]=$value2;
                    }
                }
            }
            if (array_unique_fb($insdata)) {
                $insdata=array_unique_fb($insdata);
            }
            foreach ($insdata as $inskey => $insvalue) {
                foreach ($updata as $upkey => $upvalue) {
                    if ($insvalue['id']!=$upvalue['id']) {
                        $ins[]=$insvalue;
                        $indata['deptid']="'".s($insvalue['id'])."'";
                        $indata['deptname']="'".s($insvalue['name'])."'";
                        $indata['parentid']="'".s($insvalue['parentid'])."'";
                        $sql="INSERT INTO `dept` (`deptid`, `deptname`, `parentid`) VALUES (".join(',',$indata).")";
                        // $insres=run_sql( $sql );
                        // dump($insres.$sql);
                        return ajax_echo('<pre>'.$insres.$sql.'</pre>');
                    }
                }
            }
            foreach ($diff1 as $diffkey => $diffvalue) {
                foreach ($updata as $upfkey => $upfvalue) {
                    if ($diffvalue['id']!=$upfvalue['id']) {
                        $deldata[]=$diffvalue;
                        $sql="DELETE FROM dept WHERE deptid='".$diffvalue['id']."'";
                        // $delres=run_sql($sql);
                        // dump($delres.$sql);
                        return ajax_echo('<pre>'.$delres.$sql.'</pre>');
                    }
                }
            }
        }
    }else{
        $Departmentlist=QyWechat()->getAllDepartment();
        foreach ($Departmentlist['department'] as $key => $value) {
            $data['deptid']="'".s($value['id'])."'";
            $data['deptname']="'".s($value['name'])."'";
            $data['parentid']="'".s($value['parentid'])."'";
            $sql="INSERT INTO `dept` (`deptid`, `deptname`, `parentid`) VALUES (".join(',',$data).")";
            // $result=run_sql( $sql );
            // $result=$dept->add($data);
            if ($result) {
                // dump($data['deptname'].'导出成功');
               ajax_echo('<pre>'.$data['deptname'].'导出成功</pre>');
            }else{
                // dump($data['deptname'].'导出失败');
                ajax_echo('<pre>'.$data['deptname'].'导出失败</pre>');
            }
        }
    }
}
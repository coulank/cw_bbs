<?php
namespace cws;
require_once("define.php");

if (!isset($_REQUEST['q'])) $_REQUEST['q'] = '';
$_REQUEST['q'] = str_replace('～', '〜', $_REQUEST['q']);
if (!isset($_REQUEST['id'])) $_REQUEST['id'] = null;
if (!isset($cws_request) || !is_array($cws_request)) $cws_request = $_REQUEST;
if (!isset($cws_add_search_q)) $cws_add_search_q = '';
if (!isset($cws_add_notag_q)) $cws_add_notag_q = '';
if (!isset($owner_login)) $owner_login = false;
if (!isset($post_mode)) $post_mode = false;
if (!isset($postform_enable)) $postform_enable = true;
if (!isset($os_mode)) {
    $os_mode = '';
    switch(PHP_OS) {
        case 'Linux':
            $os_mode = 'linux';
        break;
        case 'WINNT':
            $os_mode = 'windows';
        break;
    }
}
$setinfo_owner = isset($setinfo_owner) ? $setinfo_owner : true;
$cws_bbs_name_visible = isset($cws_bbs_name_visible) ? $cws_bbs_name_visible : false;
$cws_thread_htmlsp = isset($cws_thread_htmlsp) ? $cws_thread_htmlsp : true;
$postform_enable = isset($postform_enable) ? $postform_enable : true;
$edit_mode = $owner_login;
$addr_edit_mode = isset($addr_edit_mode) ? $addr_edit_mode : $edit_mode;
$reply_link = isset($reply_link) ? $reply_link : '$0';
$pagable = !(isset($cws_request['p']) && $cws_request['p'] == 0);
$page_limit = isset($page_limit) ? $page_limit : 50;
$idmode = isset($cws_request['id']);
$treemode = $idmode && $pagable;

// $edit_mode = $owner_login && isset($_REQUEST['edit_mode']);

$db = DB::create($dbi);

function get_thread_list($tables, $threads) {
    $table_list = array();
    $thread_list = array();
    $thread_tables = array();
    if (!is_array($tables)) $tables = array($tables);
    if (!is_array($threads)) $threads = array($threads);
    $main_table = 'BBS';
    if (isset($tables[0])) $main_table = $tables[0];
    $lp = function($threads, $table)
            use (&$lp, &$table_list, &$thread_list, &$thread_tables) {
        foreach($threads as $k => $v) {
            if (is_numeric($k)) {
                array_push($table_list, $table);
                array_push($thread_list, $v);
                $thread_tables[$v] = $table;
            } else {
                $lp($v, $k);
            }
        }
    };
    $lp($threads, $main_table);
    
    $table_list = array_unique($table_list);
    $thread_list = array_unique($thread_list);
    if (is_array($tables)) {
        $create_tables = array_unique(array_merge($tables, $table_list));
    } else {
        $create_tables = $table_list;
    }
    return array('table_list' => $table_list, 'thread_list' => $thread_list,
        'thread_tables' => $thread_tables, 'create_tables' => $create_tables);
}
if (!isset($threads)) $threads = 'thread_x';
list('table_list' => $table_list, 'thread_list' => $thread_list, 'thread_tables' => $thread_tables, 'create_tables' => $create_tables)
    = get_thread_list($tables, $threads);
$visible_thread_view = isset($visible_thread_view) ? $visible_thread_view : (count($thread_list) > 0);
$main_thread = $thread_list[0];
$main_table = use_table($main_thread);

foreach ($create_tables as &$a_table) {
    if (!$db->exists($a_table)) {
        $sql = "CREATE TABLE `$a_table` (
            `ID` " . $db->set_inc() . ",
            `posts` " . $db->set_int() . ",
            `thread` " . $db->set_text(60) . ",
            `name` " . $db->set_text(60) . ",
            `addr` " . $db->set_text(60) . ",
            `new` " . $db->set_timestamp() . ",
            `time` " . $db->set_timestamp() . ",
            `text` " . $db->set_text() .
            $db->set_inc_foot() . "
            )";
        $db->execute($sql);
    }
}
$thread_index = isset($thread_index) ? $thread_index : 'thread_index';
function thread_index_check($_db = null, $_thread_index = null) {
    global $thread_index, $db;
    if (is_null($_db)) $_db = $db;
    if (is_null($_thread_index)) $_thread_index = $thread_index;
    if (!$_db->exists($thread_index)) {
        $sql = "CREATE TABLE `$thread_index` (
            `ID` " . $_db->set_inc() . ",
            `posts` " . $_db->set_int() . ",
            `thread` " . $_db->set_text(60) . ",
            `name` " . $_db->set_text(60) . ",
            `addr` " . $_db->set_text(60) . ",
            `new` " . $_db->set_timestamp() . ",
            `time` " . $_db->set_timestamp() . ",
            `text` " . $_db->set_text() .
            $_db->set_inc_foot() . "
            )";
        $_db->execute($sql);
    }
}
thread_index_check();
$index_thread_add = (isset($index_thread_add)) ? $index_thread_add : false;
$index_post_value = $cws->path.($index_thread_add ? ('#'.$threads) : '');
$iplist_table = isset($iplist_table) ? $iplist_table : 'iplist';
if (!$db->exists($iplist_table)) {
    $sql = "CREATE TABLE `$iplist_table` (
        `ID` " . $db->set_inc() . ",
        `addr` " . $db->set_text(60, '') . ",
        `status` " . $db->set_int(null, 0) . ",
        `time` " . $db->set_timestamp() .
        $db->set_inc_foot() . "
        )";
    $db->execute($sql);
}

$login_user = isset($login_user) ? $login_user : '';
if ($owner_login && $login_user === '') {
    $login_user = $owner_user;
}

function use_tables($param = null){
    global $thread_tables, $table_list, $main_table;
    if (is_null($param)) {
        $param = array();
    } else {
        $param = array('thread_list' => $param);
    }
    $return_tables = array();
    $_thread_list = (isset($param['thread_list']) && is_array($param['thread_list']) ? $param['thread_list'] : array());
    $_table_list = (isset($param['table_list']) ? $param['table_list'] : $table_list);
    $_thread_tables = (isset($param['thread_tables']) ? $param['thread_tables'] : $thread_tables);
    if (count($_thread_list) > 0) {
        foreach($_thread_list as &$thread) {
            if (isset($_thread_tables[$thread])) {
                array_push($return_tables, $_thread_tables[$thread]);
            }
        }
        if (count($return_tables) === 0) {
            array_push($return_tables, $main_table);
        } else {
            $return_tables = array_unique($return_tables);
        }
        
        return $return_tables;
    } else {
        return $_table_list;
    }
}
function use_table($param = null){
    return use_tables($param)[0];
}

function where_filter_thread(&$stmt, $param = null){
    global $main_table;
    if (!is_array($param)) $param = array();
    if (!isset($param['threads'])) $param['threads'] = $param;
    $threads = (isset($param['threads']) ? $param['threads'] : array());
    $not_threads = (isset($param['not_threads']) ? $param['not_threads'] : array());
    $thread_field = (isset($param['thread_field']) ? $param['thread_field'] : 'thread');
    $stmt_key = (isset($param['stmt_key']) ? $param['stmt_key'] : 'thread_filter_');
    $return_array = array();

    if (count($threads) > 0) {
        $filter_where_list = array();
        foreach($threads as $k => &$thread) {
            $sk = $stmt_key.$k;
            $stmt[$sk] = $thread;
            array_push($filter_where_list, "`$thread_field` = :$sk");
        }
        $filter_where = implode(' OR ', $filter_where_list);
    } else {
        $filter_where = '';
    }
    if ($filter_where !== '') {
        array_push($return_array, "($filter_where)");
    }

    if (count($not_threads) > 0) {
        $not_where_list = array();
        foreach($not_threads as $k => &$thread) {
            $sk = $stmt_key.$k;
            $stmt[$sk] = $thread;
            array_push($not_where_list, "`$thread_field` <> :$sk");
        }
        $not_where = implode(' AND ', $not_where_list);
    } else {
        $not_where = '';
    }
    if ($not_where !== '') {
        array_push($return_array, "($not_where)");
    }
    
    return implode(' AND ', $return_array);
}

function to_idt($param = null){
    global $db, $main_table, $main_thread;
    if (!is_array($param)) {
        if (is_null($param)) {
            $param = array();
        } else {
            $param = array('id' => $param);
        }
    }
    if (isset($param['id'])) {
        $id = $param['id'];
    } else {
        return null;
    }

    $return_id = $id;
    $thread = (isset($param['thread']) ? $param['thread'] : $main_thread);
    $table = (isset($param['table']) ? $param['table'] : $main_table);
    $id_field = (isset($param['id_field']) ? $param['id_field'] : 'ID');
    $posts_field = (isset($param['posts_field']) ? $param['posts_field'] : 'posts');
    $thread_field = (isset($param['thread_field']) ? $param['thread_field'] : 'thread');
    $return_toarray = (isset($param['return_toarray']) ? $param['return_toarray'] : true);
    $_db = (isset($param['db']) ? $param['db'] : $db);
    if (preg_match('/^_(.*)$/', $id, $m)) {
        $return_id = $m[1];
        if (is_numeric($return_id)) {
            $return_id = intval($return_id);
            $sql = "SELECT `$thread_field`, `$posts_field` FROM `$table` WHERE `$id_field` = ? LIMIT 1";
            $a = $_db->execute_all($sql, $return_id);
            if (isset($a[0])) {
                $id = $a[0][$posts_field];
                $thread = $a[0][$thread_field];
            }
        }
    } else {
        $do_sql = true;
        if (preg_match('/^(\d*)_(.*)$/', $id, $m)) {
            $id = $m[1];
            $thread = $m[2];
        } else {
            if (!is_numeric($id)) {
                $return_id = $id;
                $do_sql = false;
                $thread = '';
            }
        }
        if ($do_sql) {
            $id = intval($id);
            $sql = "SELECT `$id_field` FROM `$table` WHERE `$posts_field` = ? AND `$thread_field` = ? LIMIT 1";
            $a = $_db->execute_all($sql, $id, $thread);
            if (isset($a[0])) {
                $return_id = $a[0][$id_field];
                if (is_numeric($return_id)) $return_id = intval($return_id);
            } else {
                $return_id = null;
            }
        }
    }
    if ($return_toarray) {
        return array('id' => $return_id, 'posts' => $id, 'thread' => $thread, 'table' => $table);
    } else {
        return $return_id;
    }
};

function max_posts($param = null){
    global $db, $main_thread, $main_table;
    if (!is_array($param)) {
        if (is_null($param)) {
            $param = array();
        } else {
            $param = array('thread' => $param);
        }
    }
    $thread = (isset($param['thread']) ? $param['thread'] : $main_thread);
    $table = (isset($param['table']) ? $param['table'] : $main_table);
    $posts_field = (isset($param['posts_field']) ? $param['posts_field'] : 'posts');
    $thread_field = (isset($param['thread_field']) ? $param['thread_field'] : 'thread');
    $posts_max_field = 'posts_max';
    $_db = (isset($param['db']) ? $param['db'] : $db);
    
    $sql = "SELECT max(`$posts_field`) AS `$posts_max_field` FROM  `$table` WHERE  `$thread_field` = ?";
    $a = $_db->execute_all($sql, $thread);
    $return_posts_max = intval($a[0][$posts_max_field]);
    return $return_posts_max;
};

require_once("get.php");
require_once("post.php");

if ($owner_login && isset($_REQUEST['addr']) && isset($_REQUEST['addr_action'])) {
    update_iplist(intval($_REQUEST['blacklist']) !== 0, $_REQUEST['addr']);
    exit();
}
if (stripos($_SERVER['HTTP_USER_AGENT'], 'mobile') === false || preg_match('/(media|video|movie|audio|mult)/i',$_REQUEST['q'])) {
    $accept = 'image/*, video/*, audio/*'.($owner_login?', text/*, application/*':', text/plain, application/pdf');
} else {
    $accept = 'image/*';
}
$file_dir = isset($file_dir) ? $file_dir : '/objects/thread';

if (!isset($cws_top_res_text)) $cws_top_res_text = "[./:トップ]";
if (!isset($cws_top_res_name)) $cws_top_res_name = "";
if (!isset($cws_top_res_htmlsp)) $cws_top_res_htmlsp = false;
$top_res = array(
    'text'=>$cws_top_res_text, 'htmlsp'=>$cws_top_res_htmlsp,
    'ID'=>'index', 'name' => $cws_top_res_name, 'time' => '', 'new' => ''
);
if ($db->exists($thread_index, 'name', $index_post_value)) {
    $sql = "SELECT `text`, `time` FROM `$thread_index` WHERE `name` = ?";
    $index_db_dir = $db->execute_all($sql, $index_post_value)[0];
    $top_res['text'] = $index_db_dir['text'];
    $top_res['new'] = $index_db_dir['time'];
}
if (!isset($cws_top_res_before_text)) $cws_top_res_before_text = "";
$top_res['before_text'] = $cws_top_res_before_text;
if (!isset($cws_top_res_after_text)) $cws_top_res_after_text = "";
$top_res['after_text'] = $cws_top_res_after_text;
$top_res = array($top_res);
// $top_res[0]['text'] .= ($owner_login && $postform_enable && !$edit_mode ? "\n[".set_query('edit_mode').':編集]' : '');
if (!isset($cws_sitemap)) {
    $cws_sitemap = "[./:トップ] [?q=filter%3aimages:画像] [?q=filter%3avideos:動画] [?q=filter%3aaudios:音楽]";
}

if (!isset($page)) $page = 1;
if (!isset($max_page)) $max_page = 1;
if (!isset($order)) $order = '';

function add_search_r(&$add_r, $search_r) {
    $do_add_r = array();
    $check_r = "$search_r ";
    if (!is_array($add_r)) $add_r = explode(' ', $add_r);
    foreach ($add_r as &$v) {
        if (strpos($check_r, "$v ") === false) $do_add_r[] = $v;
    }
    $add_r = implode(' ', $do_add_r);
    if (!empty($search_r)) $do_add_r[] = $search_r;
    return implode(' ', $do_add_r);
}

$cws_add_notag_q = isset($cws_add_notag_q) ? $cws_add_notag_q : '';
$cws_not_tos = isset($cws_not_tos) ? $cws_not_tos : true;
if ($cws_not_tos) {
    $cws_add_notag_q = '-@tos' . ($cws_add_notag_q === '' ? '' : ' ') . $cws_add_notag_q;
}

if (($cws_add_notag_q !== '') && ($cws_request['q'] === '' && !isset($cws_request['id']))) {
    $cws_request['nq'] = $cws_add_notag_q.(isset($cws_request['nq']) ? ' '.$cws_request['nq'] : '');
}
if (isset($cws_add_search_q)) {
    $cws_request['q'] = add_search_r($cws_add_search_q, get_val($cws_request, 'q', ''));
}
if (!$post_mode) {
    if (!isset($cws_err_dump)) $db->dbi->err_dump = true;
    $ret = get_thread();
    list('data' => $arr, 'max' => $max_page, 'limit' => $page_limit, 'count' => $search_count, 'page' => $page,
        'highlight' => $highlight_q, 'order' => $order, 'option' => $option, 'all' => $all) = $ret;

    if (isset($option['view_size'])) {
        array_push($top_res, array(
            'text'=> '' , 'posts' => 'size', 'name' => 'サイズ'
        ));
    }
    if (!isset($alarm_enable)) $alarm_enable = true;
    if (($alarm_enable && isset($_COOKIE['alarm'])) || isset($option['view_alarm'])) {
        $now = new \DateTime();
        $set_hour = 3;
        if (isset($_COOKIE['alarm'])) {
            $set_hour = intval($_COOKIE['alarm']);
        }
        $next_hour = (floor(intval($now->format('H')) / $set_hour) + 1) * $set_hour;
        $next_time = new \DateTime("$next_hour:0:0");
        $diff_time = $now->diff($next_time);
        $diff_hour = intval($diff_time->format('%h'));
        $diff_min = intval($diff_time->format('%i'));
        $diff_sec = intval($diff_time->format('%s'));
        $cur_time = $now->format('Y-m-d H:i:s');
        array_push($top_res, array(
            'text'=>($next_hour % 24).'時まで 後 '
            .(($diff_hour > 0) ? $diff_hour.'時間' : '')
            .(($diff_min > 0) ? $diff_min.'分' : '')
            .(($diff_hour < 1 && $diff_min < 10) ? $diff_sec.'秒' : '')
            , 'posts'=>'alarm', 'name' => 'アラーム', 'time' => $cur_time, 'new' => $cur_time
        ));
    }
    if (!isset($task_enable)) $task_enable = true;
    if (($task_enable && isset($_COOKIE['task'])) || isset($option['view_task'])) {
        $tmp_array = array('text'=>'' , 'posts'=>'task', 'name' => 'タスク');
        $tdb = DB::create($db_sqlite_tmp);
        thread_index_check($tdb);
        if ($tdb->exists($thread_index, 'name', $index_post_value)) {
            $sql = "SELECT `text`, `time` FROM `$thread_index` WHERE `name` = ?";
            $index_db_dir = $tdb->execute_all($sql, $index_post_value)[0];
            $tmp_array['text'] = $index_db_dir['text'];
            $tmp_array['new'] = $index_db_dir['time'];
        }
        unset($tdb);
        array_push($top_res, $tmp_array);
    }
    for ($i = count($top_res) - 1; $i >= 0; $i--) {
        array_unshift($arr, $top_res[$i]);
    }
}
if ($page < $max_page) {
    array_push($arr, array(
        'text'=>"%[".set_query(array('p' => $page + 1)).":続きを読む]"
            . "[".set_query(array('p' => $max_page)).":≫:m-l16]",
        'posts'=>'info-hide', 'name' => '次のページ', 'time' =>''
    ));
}
if ($idmode) {
    $tree_request = $cws_request;
    $tree_text = 'ツリーを読む';
    if (isset($tree_request['p'])) {
        unset($tree_request['p']);
        if (empty($tree_request['q'])) unset($tree_request['q']);
    } else if ($all) {
        unset($tree_request['q']);
        $tree_text = 'ツリーをたたむ';
    } else {
        $tree_request['q'] = 'thread%3Aall';
        $tree_text = 'ツリーを広げる';
    }
    $tree_query = "?".join_query($tree_request);
    array_push($arr, array(
        'text'=>"%[".$tree_query.":$tree_text]",
        'posts'=>'info-hide', 'name' => 'ツリー', 'time' =>''
    ));
}
$asc_flag = false;
if (strtoupper($order) == 'ASC') $asc_flag = true;

function addr_sc($addr) {
    if (preg_match(CWS_HOST_RE, $addr, $m)) {
        return $m[2];
    } else {
        return '';
    }
}
function add_iplist($blacklist = true, $ip_address = null, $done_sc = false) {
    global $iplist_table, $db;
    if (is_null($ip_address)) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $addr = $ip_address;
        $done_sc = true;
    }
    $addr = $done_sc ? $ip_address : addr_sc($ip_address);
    if ($addr !== '') {
        $exists = $db->exists($iplist_table, 'addr', $addr);
        if ($exists) return false;
        $sql = "INSERT INTO `$iplist_table` (`addr`, `status`) VALUES (?, ?)";
        $db->execute($sql, $addr, ($blacklist ? 1 : 0));
        return true;
    }
    return false;
}
function update_iplist($blacklist = true, $ip_address = null, $done_sc = false) {
    global $iplist_table, $db;
    if (is_null($ip_address)) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $addr = $ip_address;
        $done_sc = true;
    }
    $addr = $done_sc ? $ip_address : addr_sc($ip_address);
    if ($addr !== '') {
        add_iplist(false, $addr, true);
        $sql = "SELECT `status` FROM `$iplist_table` WHERE `addr` = ?";
        $result = intval($db->execute($sql, $addr)->fetch()['status']);
        $sql = "UPDATE `$iplist_table` SET `status` = ? WHERE `addr` = ?";
        $db->execute($sql, $result & ~0x1 | ($blacklist ? 0x1 : 0x0), $addr);
        return true;
    }
    return false;
}
function check_blacklist($ip_address = null, $done_sc = false) {
    global $iplist_table, $db;
    if (is_null($ip_address)) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $addr = $ip_address;
        $done_sc = true;
    }
    $addr = $done_sc ? $ip_address : addr_sc($ip_address);
    if ($addr !== '') {
        $sql = "SELECT `status` FROM `$iplist_table` WHERE `addr` = ?";
        $result = $db->execute($sql, $addr);
        if (!is_null($result))
        $result = intval($result->fetch()['status']);
        return ($result & 0x1) !== 0x0;
    }
    return false;
}
$ip_check = !check_blacklist();
?>
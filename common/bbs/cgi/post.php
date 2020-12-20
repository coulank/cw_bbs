<?php
namespace cws;
$index_post_mode = (isset($index_post_mode)) ? $index_post_mode : false;
if ($index_post_mode) { ob_start(); }
$isset_text = isset($_REQUEST['text']);
$post_exit = false;
if ($isset_text || count($_FILES) > 0) {
    $db_now = $db->now();
    $cws_err_dump = true;
    $post_mode = true;
    if (isset($_REQUEST['text'])) $_REQUEST['text'] = str_replace('～', '〜', $_REQUEST['text']);
    $text = isset($_REQUEST['text']) ? $_REQUEST['text'] : '';
    $text = preg_replace('/(\r\n|\r)/', "\n", preg_replace('/\s*$/', '', $text));
    $post_functions = (isset($post_functions)) ? $post_functions : array();
    foreach($post_functions as $func) {
        $post_exit |= $func($text);
    }
    if (!$post_exit && ($isset_text || count($_FILES) > 0)) {
        $post_table = $main_table;
        $post_thread = $main_thread;
    if ($edit_mode && preg_match('/^\#\!/', $text)) {
        preg_match('/^\#\!\s*([^\s]*)\s*([\s\S]*)$/', $text, $m);
        $cmd = explode('-', strtoupper($m[1]).'---');
        $list = array();
        if ($cmd[0] === 'TAG' || $cmd[0] === 'RES' || $cmd[0] === 'RESPONSE' || $cmd[0] === 'REPLY') {
            $cmd[1] = $cmd[0];
            $cmd[0] = 'UPDATE';
        }
        if ($cmd[0] === 'UPDATE') {
            switch($cmd[1]) {
                case 'TAG':
                    $text_re = '/([\-!]?)#?([^#\s]*)(\s*|$)/';
                    $do_not_re = '/(^|\s*)#([^\s\<#]*)/';
                    $update_func = function($u, $k = '') { return "$u #$k"; };
                break;
                case 'RES': case 'RESPONSE':
                    $text_re = '/([\-!]?)>>?(\w+)(\s*|$)/';
                    $do_not_re = '/(^|\s*)>>(\w+)\:?/';
                    $update_func = function($u, $k = '') { return ">>$k $u"; };
                break;
                case 'REPLY':
                    $text_re = '/([\-!]?)@?(\w+)(\s*|$)/';
                    $do_not_re = '/(^|\s*)@(\w+)\:?/';
                    $update_func = function($u, $k = '') { return "@$k $u"; };
                break;
                default:
                $text_re = '/^$/';
                $do_not_re = '/^$/';
                $update_func = function($u, $k = '') { return $u; };
                break;
            }
            preg_replace_callback($text_re, function($m) use (&$list) {
                if ($m[2] === '') return;
                if (!isset($list[$m[2]])) $list[$m[2]] = array('add' => false, 'not' => false);
                if ($m[1] === '') { $list[$m[2]]['add'] = true; }
                else { $list[$m[2]]['not'] = true; $list[$m[2]]['add'] = false; }
            }, $m[2]);
            $ret = get_thread_search();
            $arr = $ret['data']; $i = 0; $c = count($arr);
            while($i < $c) {
                $a_list = $list;
                $data_value = $arr[$i];
                $post_table = isset($data_value['thread']) ? $thread_tables[$data_value['thread']] : $main_table;
                $update_id = $data_value['ID'];
                $update_text = $data_value['text'];
                $update_text = preg_replace_callback($do_not_re, function($m) use (&$a_list) {
                    $ret = $m[0];
                    if (isset($a_list[$m[2]])) {
                        $a_list[$m[2]]['add'] = false;
                        if ($a_list[$m[2]]['not']) $ret = '';
                    }
                    return $ret;
                }, $update_text);
                foreach ($a_list as $list_key => $list_value) {
                    if ($list_value['add']) { $update_text = $update_func($update_text, $list_key); }
                }
                $sql = "UPDATE `$post_table` SET `text` = ?, `time` = $db_now WHERE `ID` = ?";
                $db->execute($sql, $update_text, $update_id);
                ++$i;
            }
        }
        $post_exit = true;
    } else {
        $update_id = isset($_REQUEST['update_target']) ? $_REQUEST['update_target'] : '';
        $posts_num = 1;
        if ($update_id !== '') {
            $idt = to_idt($update_id);
            if (is_array($idt)) {
                list('id' => $update_id, 'posts' => $search_posts, 'thread' => $post_thread, 'table' => $post_table) = $idt;
            }
        } else {
            $posts_num = max_posts($post_thread) + 1;
        }
        $login_user = isset($login_user) ? $login_user : '';
        $ip_check = isset($ip_check) ? $ip_check : true;
        $postform_enable = isset($postform_enable) ? $postform_enable : true;
        if ($ip_check && $postform_enable) {
        $dir = ''; $dir_d = '';
        foreach($_FILES as $key => &$file) {
            if ($file['name'] === '') {
                unset($_FILES[$key]);
            }
        }
        if (count($_FILES) > 0) {
            if ($text !== '') $text .= "\n";
            $date = \date_create();
            $dir = $file_dir.'/'.date_format($date,'Y').'/'.date_format($date,'n').'/';
            $dir_d = path_auto_doc($dir, true);
            // $thm_dir = $dir_d.'thumb/';
            // auto_mkdir($thm_dir);
        }
        $loop_add = array();
        foreach($_FILES as &$file) {
            $name = $file['name'];
            $tmp = $file['tmp_name'];
            $to = $dir_d.$name;
            copy($tmp, $to);
            chmod($to, 0755);
            $path = $dir.$name;
            $type = '';
            if (preg_match($cws->image_re ,$name)) {
                $type = ':image';
                if (preg_match('/(.*)(\.)([^\.]+)$/', $name, $m)) {
                    $ext = mb_strtolower($m[3]);
                }
            } elseif(preg_match($cws->video_re ,$name)) {
                $type = ':video';
            } elseif(preg_match($cws->audio_re ,$name)) {
                $type = ':audio';
            } elseif(preg_match('/\.txt$/' ,$name)) {
                $type = ':inline';
            }
            $loop_add[] = array('path'=>$path,'name'=>$name,'type'=>$type);
        }
        $loop_func = function($edit_text) use (&$loop_add){
            $type_define = '';
            $in_edit_text = $edit_text;
            $out_str = "[$edit_text]";
            if (preg_match('/:(image|video|movie|audio|media|application|text|inline):/', $edit_text.':', $m)) {
                $in_edit_text = \str_replace(':'.$m[1], '', $edit_text);
                if ($m[1] !== 'media') $type_define = ':'.$m[1];
            }
            if ($in_edit_text === '' || substr($in_edit_text, 0, 1) === ':') {
                $out_str = null;
                if (count($loop_add) === 0) { return $out_str; }
                $value = array_shift($loop_add);
                $type = ($type_define === '') ? $value['type'] : $type_define;
                $double_colon = strpos($edit_text, '::');
                if ($double_colon === false) {
                    $alt_name = '';
                    if (preg_replace_callback('/\:([^\/]*)/', function($m) {
                        if (preg_match('/^\s*([^\d\=]+)[\s:\=]*(\d*)(.*)$/', $m[1], $swm)){
                            switch ($swm[1]) {
                                case 'i': case 'b':
                                case 'left': case 'right': case 'none':
                                case 'text-left': case 'text-right': case 'text-center':
                                case 'center':
                                case 'w':
                                case 'h':
                                case 'auto':
                                case 'small':
                                case 's': case 'style':
                                case 'c': case 'charset':
                                case 'cls': case 'class':
                                case 'title':
                                case 'target':
                                case 'object': case 'controls': case 'loop':
                                case 'muted': case 'autoplay': case 'preload':
                                case 'no-object': case 'no-controls': case 'no-loop':
                                case 'no-muted': case 'no-autoplay': case 'no-preload':
                                break;
                                default:
                                    return $m[0];
                                break;
                            }
                        }
                    }, $edit_text) === '') {
                        $alt_name = ':'.$value['name'];
                    }
                    $out_str = '['.$value['path'].$type.$edit_text.']';
                } else {
                    $bef = explode(':', substr($edit_text, 1, $double_colon - 1));
                    if ($bef[0] === '') $bef[0] = $value['name'];
                    $bef = ':'.implode(':', $bef);
                    $aft = substr($edit_text, $double_colon + 1);
                    if ($aft === ':') $aft = '';
                    $set_str = $bef.$type.$aft;
                    $out_str = '['.$value['path'].$set_str.']';
                }
            }
            return $out_str;
        };
    
        if (count($loop_add) > 0) $text = brackets_loop($text, $loop_func);
        $add = array();
        foreach($loop_add as $value) {
            $add[] = '['.$value['path'].':'.$value['name'].$value['type'].']';
        }
        $text .= implode(' ', $add);
        if ($text != '' && $update_id === '') {
            $sql = "INSERT INTO `$post_table` (`text`, `name`, `addr`, `new`, `time`, `thread`, `posts`) VALUES (?, ?, ?, $db_now, $db_now, ?, ?)";
            $db->execute($sql, $text, $login_user, $_SERVER['REMOTE_ADDR'], $post_thread, $posts_num);
        } else if ($text != '' && $owner_login && (is_numeric($update_id))) {
            $sql = "UPDATE `$post_table` SET `text` = ?, `time` = $db_now WHERE `ID` = ?";
            $db->execute($sql, $text, $update_id);
        } else if ($update_id == 'index') {
            if ($db->exists($thread_index, 'name', $index_post_value)) {
                $sql = "UPDATE `$thread_index` SET `text` = ?, `thread` = ?, `addr` = ?, `time` = $db_now WHERE `name` = ?";
                $db->execute($sql, $text, $main_thread, $_SERVER['REMOTE_ADDR'], $index_post_value);
            } else {
                $sql = "INSERT INTO `$thread_index` (`text`, `name`, `thread`, `addr`, `new`, `time`) VALUES (?, ?, ?, ?, $db_now, $db_now)";
                $db->execute($sql, $text, $index_post_value, $main_thread, $_SERVER['REMOTE_ADDR']);
            }
        } else if ($update_id == 'task') {
            $tdbi = DBI::create($db_sqlite_tmp);
            $tdb = DB::create($tdbi);
            thread_index_check($tdb);
            if ($tdb->exists($thread_index, 'name', $index_post_value)) {
                $sql = "UPDATE `$thread_index` SET `text` = ?, `thread` = ?, `addr` = ?, `time` = $db_now WHERE `name` = ?";
                $tdb->execute($sql, $text, $main_thread, $_SERVER['REMOTE_ADDR'], $index_post_value);
            } else {
                $sql = "INSERT INTO `$thread_index` (`text`, `name`, `thread`, `addr`, `new`, `time`) VALUES (?, ?, ?, ?, $db_now, $db_now)";
                $tdb->execute($sql, $text, $index_post_value, $main_thread, $_SERVER['REMOTE_ADDR']);
            }
            unset($tdb); unset($tdbi);
        } else if ($update_id == 'alarm') {
            setcookie($update_id, $text, time()+60*60*24*30*6, '/');
        }}
        $post_exit = true;
    }}
} else if (isset($_REQUEST['id'])) {
    if ($owner_login && isset($_REQUEST['delete_action'])) {
        $delete_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        if ($delete_id !== '') {
            $idt = to_idt($delete_id);
            if (is_array($idt)) {
                list('id' => $delete_id, 'posts' => $search_posts, 'thread' => $post_thread, 'table' => $post_table) = $idt;
            }
        }
        if (is_numeric($delete_id)) {
            $sql = "DELETE FROM `$post_table` WHERE `ID` = ?";
            $db->execute($sql, $delete_id);
            exit();
            // $sql = "UPDATE `$threads` SET `text` = ? WHERE `ID` = ?";
            // $db->execute($sql, 'deleted-owner', $_REQUEST['id']);
        } else if ($delete_id == 'index') {
            $sql = "DELETE FROM `$thread_index` WHERE `name` = ?";
            $db->execute($sql, $index_post_value);
        } else if ($delete_id == 'task') {
            $tdbi = DBI::create($db_sqlite_tmp);
            $tdb = DB::create($tdbi);
            $sql = "DELETE FROM `$thread_index` WHERE `name` = ?";
            $tdb->execute($sql, $index_post_value);
            unset($tdb); unset($tdbi);
        } else if ($delete_id == 'tmp') {
            if ($delete_tmp_path !== '') {
                $db->disconnect();
                auto_rmdir($delete_tmp_path);
            }
        }
        $post_exit = true;
    }
} else {
    if (isset($_REQUEST['no-load'])) { exit(); }
}
if ($post_exit) {
    if ($index_post_mode) {
        $url = preg_replace('/^([^\?]*)(.*)$/', '$1', $_SERVER['REQUEST_URI']);
        if (isset($_REQUEST['no-load'])) { exit(); }
        header("Location: $url");
    }
    exit();
} else {
    ob_end_flush();
}
?>
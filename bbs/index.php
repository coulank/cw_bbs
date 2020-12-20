<?php
namespace cws;
// ini_set('display_errors', "On");
$index_post_mode = true;
$visible_thread_view = false;

require_once($_SERVER['DOCUMENT_ROOT']."/common/setup/set_cws_require.php");
require_once(get_docpath("/common/cw_init/cws.php"));

// $cws_sqlite_host = get_docpath('/db/bbs_content.db');
// $cws_local_flag = false;

if (!isset($tables)) $tables = 'bbs_on';
if (!isset($threads)) $threads = 'thread';
// if (!isset($file_dir)) $file_dir ='/objects/thread/jh';

$cws_debug_mode = true;
$page_limit = 50;

$cws_guest_exit = false;

require_once(get_docpath("/common/bbs/cgi/define.php"));

if (!isset($this_define)) $this_define = array();

$this_define += array(
    'title' => '掲示板サンプル',
    'description' => '趣味で作成した掲示板サイトです',
    'url' => '/bbs',
    'manifest' => '/bbs/thread.json',
);

if (!isset($cws_top_res_text)) {
    $cws_top_res_text = "[../:ログインへ戻る]";
}
if (isset($_REQUEST['id']) || isset($_REQUEST['q'])) {
    $cws_top_res_before_text = "[./:トップへ戻る] ";
}

$alarm_enable = true;

if ($owner_login) {
    $cws_deleted_viewtree = true;
}

$url_name = get_val($_REQUEST, array('cw_thread_jh_name', 'name'));
if (is_null($url_name)) {
    $pathlist = $cws->get_pathlist($_SERVER);
    $url_name = $pathlist[0];
}
$url_name = urldecode($url_name);
$addr_edit_mode = true;

if ($url_name === '') {
    $login_user = '';
    $postform_enable = $owner_login;
    // $postform_enable = false;
    $cws_top_res_after_text = 
    "<br/>以下にログインしたい文字列を入力してください<br/>"
    ."<form onsubmit=\"(function(path){setTimeout(function(){"
    ."location.href=path"
    ."},0)})(this.cw_thread_jh_name.value); return false;\">"
    ."<input style='margin: 6px 20px' type='text' name='cw_thread_jh_name'>"
    ."<input type='submit' value='Enter'></form>";
    if (!$owner_login) {
        $cws_add_notag_q = '-#single';
        $cws_secret_search_q = 'order:asc thread:howto';
        $addr_edit_mode = false;
    } else {
        $cws_thread_htmlsp = false;
        $cws_thread_name_visible = true;
    }
} else {
    $cws_top_res_name = 'Current { '.$url_name.' }';
    $login_user = $url_name;
    $cws_top_res_htmlsp = true;
    if (($url_name == $owner_user || preg_match('/管理|howto|readme/i', $url_name))) {
        $addr_edit_mode = false;
        if (!$owner_login) {
            $owner_login = false;
            $postform_enable = false;
        }
        $cws_thread_htmlsp = false;
    } elseif(preg_match('/te?mp/i', $url_name)){
        $owner_login = true;
        $addr_edit_mode = false;
        $cws_thread_name_visible = true;
        $delete_tmp_path = "$tmp_bbs_path/public";
        $file_dir ="$delete_tmp_path/objects/";
        $cws_sqlite_host = path_auto_doc("$delete_tmp_path/bbs_content.db", true);
        $cws_sqlite_flag = true;
        $this_define['manifest'] = '/bbs/thread_tmp.json';
        $this_define['title'] = '一時所';
    } else {
        $owner_login = true;
        $cws_thread_name_visible = true;
    }
    $cws_secret_search_q = 'thread:'.$url_name;
    $threads = $url_name;
}
require_once(get_docpath("/common/setup/cws_db_setup.php"));


if (!isset($file_dir)) $file_dir ='/objects/jh/thread/'.$login_user;
require_once(get_docpath("/common/bbs/cgi/setup.php"));
require_once(get_docpath("/common/bbs/cgi/set.php"));
?>
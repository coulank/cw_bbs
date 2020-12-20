<?php
namespace cws;

$owner_user = 'root';
// ここにプライベート用のログイン処理を入れている（$owner_loginが有効になる）
// require_once(get_docpath("/{private_login_path}"));
$private_owner_login = $owner_login;
// if (!isset($cws_sqlite_flag)) $cws_sqlite_flag = true;

$request_enable = isset($_REQUEST['q']);
$tmp_bbs_path = get_docpath('/tmp/bbs');
$tmp_bbs_db = $tmp_bbs_path.'/bbs_content.db';
$db_sqlite_tmp = array('service' => 'sqlite', 'host' => $tmp_bbs_db);

$delete_tmp_path = ''; 
$q_or_id_enable = $request_enable || isset($_REQUEST['id']);

define('CWS_HOST_RE', '/(\w+\:\/\/\/?|^\s*)(\[[^\]]+\]|[\w.]+|[\:\w]*)\:?([\d]*)/');

if (!isset($cws_filter_tag) || !is_array($cws_filter_tag)) $cws_filter_tag = array();
$cws_filter_tag += array(
    'creative' => array(
        'highlight' =>
            '#漫画 #創作 #原稿 #絵 #イラスト #クリエイティブ #creative',
        're' => '#(漫画|創作|原稿|絵|イラスト|クリエイティブ|creative)',
        'tag' =>
            '#creative',
    )
);

if (!isset($tables)) $tables = array('BBS', 'ImportBBS');

?>
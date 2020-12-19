<?php
namespace cws;
// $cws_sqlite_local_flag = true;
require_once($_SERVER['DOCUMENT_ROOT']."/common/cw_init/cws_require.php");
if ($cws_local_transfer) {
    // require('cws_transfer_local.php');
}
// 以下のクッキーがあれば自動でプライベートモードになる（実際は別のにしてます）
// $cws_manager_mode = isset($_COOKIE['hogehoge']);
?>
<?php
namespace cws;
// 同じIPアドレスからのアクセスであれば自動でローカルのパスにするサンプル
if ($_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_NAME']) {
    switch($_SERVER['REMOTE_ADDR']) {
        case '127.0.0.1': case '::1':
        break;
        default:
            $do_transfer = true;
            $host = '192.168.x.xx';
            switch($_SERVER['SERVER_PORT']) {
                case '80': case '443':
                break;
                case 'xxx':
                    $host = $host . ':xxxx';
                break;
                default:
                    $do_transfer = false;
                    // $host = $host . ':' . $_SERVER['SERVER_PORT'];
                break;
            }
            if ($do_transfer) {
                header('Location: http://' . $host . $_SERVER['REQUEST_URI']);
            }
        break;
    }
}
?>
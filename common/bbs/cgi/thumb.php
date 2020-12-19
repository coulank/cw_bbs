<?php
function make_thumb_1($org, $thm_w = 60, $thm = '', $ext = '') {
    if ($ext === '' && preg_match('/(.*)(\.)([^\.]+)$/', $thm, $m)) { $ext = $m[3]; }
    list($org_w, $org_h) = getimagesize($org);
    $img = null;
    if ($thm_w < $org_w) {
        $thm_h = round($org_h * $thm_w / $org_w);
        switch ($ext) {
            case 'png':
                $img = ImageCreateFromPNG($org);
            break;
            case 'jpg': case 'jpeg':
                $img = ImageCreateFromJPEG($org);
            break;
            case 'gif':
                $img = ImageCreateFromGIF($org);
            break;
        }
    }
    if ($img !== null) {
        $thm_img = ImageCreateTrueColor($thm_w, $thm_h);
            switch ($ext) {
                case 'png': case 'gif':
                    //ブレンドモードを無効にする
                    imagealphablending($thm_img, false);
                    //完全なアルファチャネル情報を保存するフラグをonにする
                    imagesavealpha($thm_img, true);
                break;
            }
        ImageCopyResized($thm_img, $img, 0, 0, 0, 0, $thm_w, $thm_h, $org_w, $org_h);
        switch ($ext) {
            case 'png':
                ImagePNG($thm_img, $thm);
            break;
            case 'jpg': case 'jpeg':
                ImageJPEG($thm_img, $thm);
            break;
            case 'gif':
                ImageGIF($thm_img, $thm);
            break;
        }
        ImageDestroy($img);
        ImageDestroy($thm_img);
    }
}
// make_thumb_1($to, 60, $thm_dir.$m[1].'_s.'.$ext, $ext);
?>
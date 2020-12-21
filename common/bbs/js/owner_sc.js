if (typeof(window.other_sc) !== 'object') window.other_sc = new Object();
if (typeof(window.other_sc.doc) !== 'object') window.other_sc.doc = new Array();
window.other_sc.doc.push(function(e){
    switch (e.code) {
        case 'KeyC':
            if (mf_g_mode) window.jump_url = '/manage/creative/bbs'; break;
        case 'KeyD':
            if (mf_g_mode) window.jump_url = '?q=%23開発'; break;
        case 'KeyP':
            if (mf_g_mode) window.jump_url = '/manage/bbs'; break;
        case 'KeyL':
            if (mf_g_mode) window.jump_url = '?q=%23生活'; break;
        case 'KeyR':
            if (mf_g_mode) window.jump_url = '?q=%23忘備録'; break;
        case 'KeyS':
            if (mf_g_mode) window.jump_url = '?q=%23🐑'; break;
        case 'KeyY':
            if (mf_g_mode) window.jump_url = '?q=%23やること'; break;
        case 'KeyF':
            if (mf_g_mode) window.jump_url = '?q=%23思索'; break;
        case 'KeyM':
            if (mf_g_mode) window.jump_url = '?q=%23メモ'; break;
        case 'KeyO':
            if (mf_g_mode) window.jump_url = '/bbs'; break;
        case 'Minus':
            if (mf_g_mode) window.jump_url = '/manage/tmp/bbs'; break;
        case 'Digit0':
            if (mf_g_mode) window.jump_url = '/bbs/tmp'; break;
    }
    //console.log(e.code);
});
window.other_sc.form.push(function(e){
    var script_mode = '';
    switch (e.code) {
        case 'KeyG':
            if (e.altKey) { script_mode = 'RESPONSE'; }
        break;
        case 'KeyR':
            if (e.altKey) { script_mode = 'REPLY'; }
        break;
        case 'KeyT':
            if (e.altKey) { script_mode = 'TAG'; }
        break;
    }
    if (script_mode !== '') {
        textarea.value = textarea.value.replace(/(#!\s*\S*\s+|@\S*\s+|^)/, "#!" + script_mode + "\n");
    }
});

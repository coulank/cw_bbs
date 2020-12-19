if (typeof(window.other_sc) !== 'object') window.other_sc = new Object();
if (typeof(window.other_sc.doc) !== 'object') window.other_sc.doc = new Array();
window.other_sc.doc.push(function(e){
    switch (e.keyCode) {
        case 67: // c
        break;
        case 68: // d
            if (mf_g_mode) window.jump_url = '?q=%23開発'; break;
        case 80: // p
        break;
        case 76: // l
            if (mf_g_mode) window.jump_url = '?q=%23生活'; break;
        case 82: // r
            if (mf_g_mode) window.jump_url = '?q=%23忘備録'; break;
        case 83: // s
            if (mf_g_mode) window.jump_url = '?q=%23🐑'; break;
        case 89: // y
            if (mf_g_mode) window.jump_url = '?q=%23やること'; break;
        case 70: // f
            if (mf_g_mode) window.jump_url = '?q=%23思索'; break;
        case 77: // m
            if (mf_g_mode) window.jump_url = '?q=%23メモ'; break;
        case 79: // o
            if (mf_g_mode) window.jump_url = '/thread'; break;
    }
    //     console.log(e.keyCode);
});
window.other_sc.form.push(function(e){
    var script_mode = '';
    switch (e.keyCode) {
        case 71: // g
            if (e.altKey) { script_mode = 'RESPONSE'; }
        break;
        case 82: // r
            if (e.altKey) { script_mode = 'REPLY'; }
        break;
        case 84: // t
            if (e.altKey) { script_mode = 'TAG'; }
        break;
    }
    if (script_mode !== '') {
        textarea.value = textarea.value.replace(/(#!\s*\S*\s+|@\S*\s+|^)/, "#!" + script_mode + "\n");
    }
});

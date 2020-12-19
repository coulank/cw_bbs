function Dom() {
    if (typeof(Dom.instance) == "object") {
        return Dom.instance;
    }
    this.__proto__.getCSS = function(url){
        if (typeof(url) == "string") url = Array(url);
        for (i = 0; i < url.length; i++){
            o = document.createElement("link");
            o.setAttribute("rel", "stylesheet");
            o.setAttribute("type", "text/css");
            o.setAttribute("href", url);
            document.head.appendChild(o);            
        }
    }
    Dom.instance = this;
    return this;
}
var dom = new Dom();

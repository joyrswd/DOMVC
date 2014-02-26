function newElement(name, attr)
{
    var elem = document.createElement(name);
    if (typeof attr == 'object') {
        for (var p in attr) {
            elem.setAttribute(p, attr[p]);
        }
    }
    return elem;
}

function insertAfter(refnode, newnode) 
{
    refnode.parentNode.insertBefore(newnode, refnode.nextSibling);
}

function addEvent(obj, type, func, capture)
{
    capture = (capture) ? true : false;
    if (obj.addEventListener) {
        obj.addEventListener(type, func, capture); 
    } else if(obj.attachEvent) { 
        obj.attachEvent('on' + type, func);
    }
}

function removeEvent(obj, type, func)
{
    if (obj.removeEventListener) {
        obj.removeEventListener(type, func, false); 
    } else if(obj.detachEvent) { 
        obj.detachEvent('on' + type, func);
    }
}

function repeatMethod(interval, func)
{
    var obj={id:null, interval:interval, step:0};
    obj.id = window.setInterval(function(){
        obj.step++;
        if (func.call(obj) !== true) {
           window.clearInterval(obj.id);
        }
    }, interval);
}

function delayMethod(interval, func)
{
    var obj={id:null, interval:interval, step:0};
    obj.id = window.setTimeout(function(){
        if (func.call(obj) !== true) {
           window.clearTimeout(obj.id);
        }
    }, interval);
}
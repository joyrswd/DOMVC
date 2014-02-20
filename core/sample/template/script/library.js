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

function addEvent(obj, type, func)
{
    if (obj.addEventListener) {
        obj.addEventListener(type, func, false); 
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
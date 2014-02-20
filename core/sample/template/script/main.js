var AJAX = new AJAX_CONNECTION();
var pages = {};
var current_page = window.location.href;
(function(){
    var paging = document.getElementById('paging');
    if (paging) {
        var lis = paging.childNodes;
        var i=0,li,pulldown=newElement('select');
        while (li = lis[i++]) {
            if (li.nodeType !== 1) continue;
            var elem = li.firstChild;
            if (elem.nodeType !== 1) {
                elem = elem.nextSibling;
            }
            var attr = (elem.tagName === 'A') ? {value:elem.href} : {selected:'selected'};
            var opt = newElement('option',attr);
            opt.innerText = elem.innerText;
            pulldown.appendChild(opt);
        }
        paging.parentNode.replaceChild(pulldown, paging);
        addEvent(pulldown, 'change', changePage);
        if (pulldown.selectedIndex > 0 && pulldown.options.length > 1) {
            var prev = newElement('a', {href:pulldown.options[pulldown.selectedIndex-1].value});
            prev.innerText = '<';
            pulldown.parentNode.insertBefore(prev, pulldown);                    
        }
        if (pulldown.selectedIndex < pulldown.options.length - 1 && pulldown.options.length > 1) {
            var next = newElement('a', {href:pulldown.options[pulldown.selectedIndex+1].value});
            next.innerText = '>';
            insertAfter(pulldown, next);
        }
    }

    var setEvents = arguments.callee;

    var links = document.body.getElementsByTagName('a');
    var i=0,a;
    while (a = links[i++]) {
        addEvent(a, 'click', changePage);
    }

    var forms = document.body.getElementsByTagName('form');
    var i=0,f;
    while (f = forms[i++]) {
        addEvent(f, 'submit', function(e){
            e.preventDefault();
            var ii=0,ee,data={};
            while (ee = e.target.elements[ii++]) {
                data[ee.name] = ee.value;
            }
            AJAX.post(e.target.action, data, function(doc){
                document.body.innerHTML = doc.getElementsByTagName('body')[0].innerHTML;
                pages = {};
                setEvents();
            });
        });
    }

    function changePage(e){
        e.preventDefault();
        var href = e.target.href || e.target.value;
        if (!pages[current_page]) {
            pages[current_page] = document.createDocumentFragment();
        }
        if (pages[href]) {
            pages[current_page].appendChild(document.body);
            document.head.parentNode.appendChild(pages[href].firstChild);
            current_page = href;
            history.pushState('', '', href);
        } else {
            AJAX.get(href, false, function(doc){
                pages[current_page].appendChild(document.body);
                document.head.parentNode.appendChild(doc.getElementsByTagName('body')[0]);
                current_page = href;
                history.pushState('', '', href);
                setEvents();
            });
        }
    }
})();
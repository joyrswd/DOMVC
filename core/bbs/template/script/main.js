var AJAX = new AJAX_CONNECTION();
var pages = {};
var current_page = window.location.href;
(function(first_call){
    var setEvents = arguments.callee;
    var is_popstate = false;
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
        if (pulldown.selectedIndex < pulldown.options.length - 1 && pulldown.options.length > 1) {
            var next = newElement('a', {href:pulldown.options[pulldown.selectedIndex+1].value});
            next.innerText = '>';
            insertAfter(pulldown, next);
        }
        if (pulldown.selectedIndex > 0 && pulldown.options.length > 1) {
            var prev = newElement('a', {href:pulldown.options[pulldown.selectedIndex-1].value});
            prev.innerText = '<';
            insertAfter(pulldown, prev);
        }
    }

    var links = document.body.getElementsByTagName('a');
    var i=0,a;
    while (a = links[i++]) {
        addEvent(a, 'click', changePage);
    }
    if (first_call === true) {
        addEvent(window, 'popstate', changePage);
    }

    var forms = document.body.getElementsByTagName('form');
    var i=0,f;
    while (f = forms[i++]) {
        addEvent(f, 'submit', function(e){
            e.preventDefault();
            var ii=0,ee,data={};
            while (ee = e.target.elements[ii++]) {
                if (ee.type != 'checkbox' || ee.checked) {
                    data[ee.name] = ee.value;
                }
            }
            AJAX[e.target.method](e.target.action, data, function(doc){
                pages = {};
                current_page = (data.keyword) ? e.target.action+data.keyword : e.target.action;
                document.body.innerHTML = doc.getElementsByTagName('body')[0].innerHTML;
                setEvents();
                finalize(current_page);
            });
        });
    }

    function finalize(href)
    {
        if (is_popstate === false) {
            history.pushState('', '', href);
        }
       if (window.location.hash) {
           var id = window.location.hash.substr(1);
           var elem = document.getElementById(id);
           var y = elem.offsetTop;
           var top = document.documentElement.scrollTop || document.body.scrollTop;
           var gap = (y -  top)/10;
           repeatMethod(30, function(){
            window.scrollBy(0, gap);
            return (this.step < 10);
           });
       }
    }

    function changePage(e){
        e.preventDefault();
        var direction = false;
        if (e.target.href && e.target.innerText !== 'HOME') {
            direction = (e.target.innerText === '<') ? 'left' : 'right';
        }
        is_popstate = (e.target === window);
        if (e.target === window) {
            if (e.state === null) return;
            var href = window.location.href;
        } else {
           var href = this.href || this.value;
       }
        var wrapper = document.getElementById('wrapper');
        repeatMethod(10,function(){
            if (direction !== false) {
                var ml = wrapper.style[direction].substr(0, wrapper.style[direction].length-2) || 0;
                wrapper.style[direction] = ml - 20 + 'px';
            }
            wrapper.style.opacity = ((wrapper.style.opacity * 10 || 10) - 1) / 10;
            if (wrapper.style.opacity > 0) {
                return true;
            } else {
                if (!pages[current_page]) {
                    pages[current_page] = document.createDocumentFragment();
                }
                if (pages[href]) {
                    wrapper.style.cssText = '';
                    pages[current_page].appendChild(wrapper);
                    pages[href].firstChild.style.opacity = 0;
                    document.body.appendChild(pages[href]);
                    showPage(document.getElementById('wrapper'), href);
                } else {
                    AJAX.get(href, false, function(doc){
                        var newrapper = doc.getElementsByTagName('body')[0].getElementsByTagName('div')[0];
                        wrapper.style.cssText = '';
                        pages[current_page].appendChild(wrapper);
                        newrapper.style.opacity = 0;
                        document.body.appendChild(newrapper);
                        setEvents();
                        showPage(newrapper, href);
                    });
                }
            }
        });

        function showPage(newrapper, href){
            if (direction !== false) {
                direction = (direction == 'left') ? 'right': 'left';
                newrapper.style[direction] = '-200px';
            }
            repeatMethod(10,function(){
                if (direction !== false) {
                    var ml = newrapper.style[direction].substr(0, newrapper.style[direction].length-2) || 0;
                    newrapper.style[direction] = parseInt(ml) + 20 + 'px';
                }
                newrapper.style.opacity = ((newrapper.style.opacity * 10 || 0) + 1) / 10;
                if (newrapper.style.opacity < 1) {
                    return true;
                } else {
                    newrapper.style.cssText = '';
                    current_page = href;
                    finalize(href);
                }
            });        
        }
    }

})(true);
function AJAX_CONNECTION(mode, sync)
{
  this.async = !sync;
  this.mode = mode;
  this.connection = null;
  if(window.ActiveXObject){
    try {
      this.connection = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        this.connection = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (ee) {

      }
    }
  } else if(window.XMLHttpRequest){
    this.connection = new XMLHttpRequest();
  } else {

  }
  if (this.connection == null) {
    alert("Can not create an XMLHTTPRequest instance");
  }
}

AJAX_CONNECTION.prototype.request = function (method, url, data, callback)
{
    var that=this;
    this.connection.open(method, url, this.async);
    this.connection.onreadystatechange = function() { 
        if (that.connection.readyState == 4) {
            var content;
            switch (that.mode) {
                case 'text':
                    content = that.connection.responseText;
                    break;
                case 'xml':
                    content = that.connection.responseXML;
                    break;
                default :
                    content = document.createElement('html');
                    content.innerHTML = that.connection.responseText;
                    break;
            }
            callback.call(that.connection, content);
        }
    }
    this.connection.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    var senddata = '';
    if (data) {
        for (name in data) {
            senddata += name+'='+encodeURI(data[name])+'&';
        }
    }
    this.connection.send(senddata);
}

AJAX_CONNECTION.prototype.get = function(url, data, callback){this.request('get', url, data, callback);};
AJAX_CONNECTION.prototype.post = function(url, data, callback){this.request('post', url, data, callback);};

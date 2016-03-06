var system = require('system');
var args = system.args;

var url = args[1];

var httpCode = 0;
var resources = [];
var redirectStatusArray = [300,301,302,303,304,305,306,307,308];

var page = require('webpage').create();

page.code = null;
page.settings.javascriptEnabled = true;
page.settings.webSecurityEnabled = false;
page.settings.loadImages = false;
page.settings.resourceTimeout = 120000; // 120 seconds
page.settings.userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36';


page.onError = function (msg, trace) {
  // Do nothing, ignore javascript errors
};

page.onResourceReceived = function(response) {
    // check if the resource is done downloading and don't need redirections
    if (response.stage !== "end" || redirectStatusArray.indexOf(response.status) > -1) return;

    resources.push(response);
};

page.onResourceTimeout = function(request) {

   if(request.url == url){
      page.code = request.errorCode;
      page.reason = 'The resource exceeded the '+page.settings.resourceTimeout+' ms available.';
      page.errorURL = request.url;
   }
};

page.onResourceError = function(resourceError) {
   if (resourceError.url == url){
      if(page.content == null || page.content == '' || page.content == '<html><head></head><body></body></html>') {
         page.code = (resourceError.errorCode == 5 || resourceError.errorCode == 99) ? 4 : resourceError.errorCode;
         page.reason = (resourceError.errorCode == 5 || resourceError.errorCode == 99) ?  'ERR_CONNECTION_TIMED_OUT' : resourceError.errorString;
         page.errorURL = resourceError.url;
      }
   }
};

page.open(url, function (status) {
  setTimeout(function() {
    if (page.code !== null) {
      console.log((resources[0].status && resources[0].status != 0) ? 'false|'+resources[0].status+'|' : page.code+'|'+page.code+'|<br/>REASON: '+page.reason+'<br/>URL: <a href="'+page.errorURL+'" target="_blank">'+page.errorURL+'</a>');
    }
    else {
      var html = print_page_html(page);
      console.log('false|'+resources[0].status+'|'+html);
    }
    phantom.exit(0);
  }, 5000);
});

function print_page_html(page) {
    var framescount = page.framesCount;

    if (framescount > 0) {
      var html = page.frameContent;
      for (var i = 0; i < framescount; i++) {
          html += "\n<!--- frame " + i + " --->\n";
          page.switchToFrame(i);
          html += print_page_html(page);
          page.switchToParentFrame();
      }
      return html;
    }
    else {
      return page.frameContent;
    }
}

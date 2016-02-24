var system = require('system');
var args = system.args;

var url = args[1];

var httpCode = 0;

var page = require('webpage').create();
page.code = null;
page.settings.javascriptEnabled = true;
page.settings.webSecurityEnabled = false;
page.settings.loadImages = false;
page.settings.resourceTimeout = 20000; // 20 seconds
page.settings.userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36';


page.onError = function (msg, trace) {
  // Do nothing, ignore javascript errors
};

page.onResourceReceived = function (response) {
   page.httpCode = response.status;
};

page.onResourceTimeout = function(request) {
    page.code = request.errorCode;
    page.reason = request.errorString;
};

page.onResourceError = function(resourceError) {
    if(page.content == null || page.content == ''){
      page.code = resourceError.errorCode;
      page.reason = resourceError.errorString;
    }
};

page.open(url, function (status) {
  setTimeout(function() {
    if (page.code !== null) {
      console.log(page.code+'|'+page.reason+'|NULL');
    }
    else {
      var html = print_page_html(page);
      console.log('false|'+page.httpCode+'|'+html);
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

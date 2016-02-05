var system = require('system');
var args = system.args;

var page = require('webpage').create();

var url = args[1];

var httpCode = 0;

page.settings.javascriptEnabled = true;
page.settings.webSecurityEnabled = false;
page.settings.loadImages = false;
//page.settings.resourceTimeout = 120000;

//page.onResourceTimeout = function(request) {
//   console.log(request.errorCode + ' ' + request.errorString);
//   phantom.exit(0);
//};

page.onResourceReceived = function (response) {
//   console.log("RESOURCE RECEIVED");
   httpCode = response.status;
};

page.onLoadFinished = function(status) {
//   console.log ("LOAD FINISHED\n");
  // Set timeout to give phantom some time to 
  // do the javascript redirect etc

  setTimeout(function() {
      
      if (page.framesCount){
         var framescount = page.framesCount+1;

         for (var i = 0; i < framescount; i++){
            page.switchToFrame(i);

            if (page.frameContent.indexOf("type=\"text\"") > -1 && page.frameContent.indexOf("type=\"password\"") > -1){

               if (httpCode == 401 && page.frameContent == null){
                  console.log(httpCode+"|");
                  phantom.exit(0);
               }
               else{
                  console.log(httpCode+"|"+page.frameContent);
                  phantom.exit(0);
               }
            }

         }
      }
      else {
            if (httpCode == 401 && page.content == null){
               console.log(httpCode+"|");
               phantom.exit(0);
            }
            else{
               console.log(httpCode+"|"+page.content);
               phantom.exit(0);
            }
        }
      }, 5000);
};

page.open(url, function (status)
{

/*    if (status !== 'success') 
    {
        console.log('\nFAIL to load the address');
        phantom.exit(0);
    } 
*/

//    else{
         //console.log('Success in fetching the page');
//    }
}
);

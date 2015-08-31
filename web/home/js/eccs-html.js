'use strict'

app.controller('HtmlController', function ($scope, EccsJsonAPI) {
    $scope.jsonApi = EccsJsonAPI.getNew();
    $scope.checkid = getParameterByName('checkid');

    $scope.check = {
        'acsUrl' : undefined,
        'serviceLocation' : undefined,
        'serviceLocation' : undefined,
        'spEntityID' : undefined,
        'checkTime': undefined,
        'entityID': undefined,
    }

    $scope.getCheckHtml = function () {
        var promise = $scope.jsonApi.getCheck($scope.checkid);
        promise.then(function(results) {
            $scope.check = results;
            $scope.checkurl = 'services/html.php?checkid=' + $scope.checkid;
        });
    };

    $scope.getCheckHtml();
});

app.filter('trustAsResourceUrl', ['$sce', function($sce) {
    return function(val) {
        return $sce.trustAsResourceUrl(val);
    };
}])

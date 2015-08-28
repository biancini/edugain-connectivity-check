app.controller('htmlController', function ($scope, $http, $filter, $location) {
    $scope.checkid = getParameterByName('checkid');

    $scope.check = {
        'acsUrl' : undefined,
        'serviceLocation' : undefined,
        'serviceLocation' : undefined,
        'spEntityID' : undefined,
        'checkTime': undefined,
        'entityID': undefined,
    }

    $scope.getCheckHtml = function() {
        url = 'services/json_api.php?action=checkhtml&checkid=' + $scope.checkid;
        $http.get(url).success(function (response) {
            $scope.check = response.result;
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

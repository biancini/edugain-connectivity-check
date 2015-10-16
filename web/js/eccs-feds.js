app.controller('FedsController', function ($scope, EccsJsonAPI, Sorting) {
    // Initialize services
    $scope.jsonApi = EccsJsonAPI.getNew();
    $scope.sorting = Sorting.getNew();

    $scope.sorting.sortingOrder = 'checkDate';
    $scope.sorting.reverse = true;

    $scope.today = new Date();
    $scope.yesterday = new Date().setDate($scope.today.getDate()-1);

    $scope.showResults = function () {
        // Filter and sort results
        $scope.sorting.sort($scope.items);
    };

    var promise = $scope.jsonApi.getFedStatistics();
    promise.then(function(results) {
        $scope.items = results;
        $scope.showResults();
    });
});

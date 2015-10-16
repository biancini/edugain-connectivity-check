app.controller('TestsController', function ($scope, EccsJsonAPI, Filtering, Sorting, Pagination) {
    // Initialize services
    $scope.jsonApi = EccsJsonAPI.getNew();
    $scope.pagination = Pagination.getNew();
    $scope.filtering = Filtering.getNew();
    $scope.sorting = Sorting.getNew();
    $scope.pagination = Pagination.getNew();

    // Initialize constants into services to influence their behaviour
    $scope.filtering.filters = {
        'entityID': getParameterByName('entityid'),
        'spEntityID': undefined,
        'checkTime': 'All',
        'httpStatusCode': undefined,
        'checkResult': 'All',
        'css_class': undefined
    };

    $scope.filtering.exactFilters = ['httpStatusCode'];
    $scope.filtering.attrSupportingAll = ['checkResult', 'checkTime'];

    $scope.sorting.sortingOrder = 'checkTime';
    $scope.sorting.reverse = true;

    $scope.today = new Date();
    $scope.yesterday = new Date().setDate($scope.today.getDate()-1);

    // Catch event from parent controller
    $scope.$on("UPDATE_STATUS", function(event, newfilters) {
        for (var curfilter in newfilters) {
            if (curfilter in $scope.filtering.filters) {
                $scope.filtering.filters[curfilter] = newfilters[curfilter];
            }
        }

        $scope.showResults();
    });

    $scope.showResults = function (pageSize) {
        // Filter and sort results
        $scope.filtering.search($scope.items, $scope.filtering.filters);
        $scope.sorting.sort($scope.filtering.filteredItems);

        // Paginate results
        $scope.pagination.setPageSize(pageSize, $scope.sorting.sortedItems.length);
        $scope.pagination.groupToPages($scope.sorting.sortedItems);
    };

    var promise = $scope.jsonApi.getTests();
    promise.then(function(results) {
        $scope.items = results;
        $scope.showResults();
    });
});

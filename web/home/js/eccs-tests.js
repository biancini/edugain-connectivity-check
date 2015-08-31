'use strict'

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
        'css_class': undefined,
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
            if (curfilter in $scope.filters) {
                $scope.filters[curfilter] = newfilters[curfilter];
            }
        }

        $scope.showResults();
    });

    $scope.showResults = function (pageSize) {
        // Finish to inizialize paginator by injecting total number of pages
        $scope.pagination.setPageSize(pageSize, $scope.numRows);

        // Filter, sort and paginate results
        $scope.filtering.search($scope.items, $scope.filtering.filters);
        $scope.sorting.sort($scope.filtering.filteredItems);
        $scope.pagination.groupToPages($scope.sorting.sortedItems);
    };

    var promise = $scope.jsonApi.getTests();
    promise.then(function(results) {
        $scope.items = results;
        $scope.numRows = $scope.items.length;
        $scope.showResults();
    });
});

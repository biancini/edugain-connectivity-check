'use strict'

app.controller('IdpsController', function ($scope, EccsJsonAPI, Filtering, Sorting, Pagination) {
    // Initialize services
    $scope.jsonApi = EccsJsonAPI.getNew();
    $scope.pagination = Pagination.getNew();
    $scope.filtering = Filtering.getNew();
    $scope.sorting = Sorting.getNew();

    // Initialize constants into services to influence their behaviour
    $scope.filtering.filters = {
        'displayName': undefined,
        'entityID': undefined,
        'registrationAuthority': undefined,
        'currentResult': 'All',
        'ignoreEntity': undefined,
        'css_class': undefined
    };

    $scope.filtering.exactFilters = [];
    $scope.filtering.attrSupportingAll = ['currentResult'];

    $scope.sorting.sortingOrder = 'css_class';
    $scope.sorting.reverse = true;

    // Catch event from parent controller
    $scope.$on("UPDATE_STATUS", function(event, newfilters) {
        for (var curfilter in newfilters) {
            if (curfilter in $scope.filters) {
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

    var promise = $scope.jsonApi.getEntities();
    promise.then(function(results) {
        $scope.items = results;
        $scope.showResults();
    });
});

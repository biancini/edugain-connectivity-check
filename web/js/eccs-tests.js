app.controller('testsController', function ($scope, $http, $filter, $location) {
    $scope.sort = {       
        sortingOrder : 'checkTime',
        reverse : true
    };

    $scope.pageSizes = [
      { name: '10', id: 10 },
      { name: '20', id: 20 },
      { name: '30', id: 30 },
      { name: '40', id: 40 },
      { name: '50', id: 50 },
      { name: '100', id: 100 },
      { name: 'All', id: 'All' }
    ];

    $scope.filteredTests = [];
    $scope.groupedTests = [];
    $scope.pagedTests = [];
    $scope.currentPage = 1;
    $scope.gap = 5;

    var searchMatch = function (haystack, needle, exact) {
        if (!needle) {
            return true;
        }

        if (exact) {
            return haystack.toString().toLowerCase() == needle.toString().toLowerCase();
        }
        else {
            return haystack.toString().toLowerCase().indexOf(needle.toString().toLowerCase()) !== -1;
        }
    };

    $scope.filters = {
        'entityID': getParameterByName('entityid'),
        'spEntityID': undefined,
        'checkTime': undefined,
        'httpStatusCode': undefined,
        'checkResult': 'All'
    };

    $scope.exactFilters = ['httpStatusCode'];

    $scope.$on("UPDATE_STATUS", function(event, newfilters) {
        for (var curfilter in newfilters) {
            if (curfilter in $scope.filters) {
                $scope.filters[curfilter] = newfilters[curfilter];
            }
        }

        $scope.search($scope.filters);
    });

    // init the filtered items
    $scope.search = function (newfilters) {
        $scope.filters = (newfilters) ? newfilters : $scope.filters;

        $scope.filteredTests = $filter('filter')($scope.tests, function (item) {
            for (var attr in $scope.filters) {
                if ((attr != 'checkResult' || $scope.filters[attr] != 'All')
                  && !searchMatch(item[attr], $scope.filters[attr], $scope.exactFilters.indexOf(attr) > -1)) {
                   return false;
                }
            }
            return true;
        });

        // take care of the sorting order
        if ($scope.sort.sortingOrder !== '') {
            $scope.filteredTests = $filter('orderBy')($scope.filteredTests, $scope.sort.sortingOrder, $scope.sort.reverse);
        }

        // now group by pages
        $scope.groupToPages();
    };

    // calculate page in place
    $scope.groupToPages = function () {
        $scope.pagedTests = [];
    
        for (var i = 0; i < $scope.filteredTests.length; i++) {
            if (i % $scope.testsPerPage.id === 0) {
                $scope.pagedTests[Math.floor(i / $scope.testsPerPage.id)] = [ $scope.filteredTests[i] ];
            } else {
                $scope.pagedTests[Math.floor(i / $scope.testsPerPage.id)].push($scope.filteredTests[i]);
            }
        }
    };

    $scope.range = function (size, start, end) {
        var ret = [];
        start = (start < 1) ? 1 : start;
        start = (start > size) ? size : start;
        end = (end > size) ? size : end;
                      
        if (size < end) {
            end = size;
            start = size - $scope.gap;
        }

        for (var i = start; i < end; i++) {
            ret.push(i);
        }

        return ret;
    };
    
    $scope.setPage = function () {
        $scope.currentPage = this.n;
    };

    $scope.getTest = function(pageSize) {
        $scope.testsPerPage = (pageSize) ? pageSize : $scope.pageSizes[2];

        url = 'services/json_api.php?action=checks&rpp=All';
        $http.get(url).success(function (response) {
            $scope.tests = response.results;
            $scope.numRows = response.num_rows;

            $scope.tests.forEach(function (test) {
                // Define css_class with color for specific check status
                if (test.checkResult == '1 - OK') {
                    test.css_class = 'green';
                } else if (test.checkResult == '2 - FORM-Invalid') {
                    test.css_class = 'yellow';
                } else if (test.checkResult == '3 - HTTP-Error') {
                    test.css_class = 'red';
                } else if (test.checkResult == '3 - CURL-Error') {
                    test.css_class = 'red';
                } else {
                    test.css_class = 'white';
                }
            });

            if ($scope.testsPerPage.id == 'All') {
                $scope.testsPerPage.id = $scope.numRows;
            }

            // functions have been describe process the data for display
            $scope.search();
        });
    };

    $scope.getTest();
});

app.controller('idpsController', function ($scope, $http, $filter, $location) {
    $scope.sort = {       
        sortingOrder : 'currentResult',
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

    $scope.filteredIdps = [];
    $scope.groupedIdps = [];
    $scope.pagedIdps = [];
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
        'displayName': undefined,
        'entityID': undefined,
        'registrationAuthority': undefined,
        'currentResult': 'All',
        'ignoreEntity': undefined
    };

    $scope.exactFilters = [];

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

        $scope.filteredIdps = $filter('filter')($scope.idps, function (item) {
            for (var attr in $scope.filters) {
                if ((attr != 'currentResult' || $scope.filters[attr] != 'All')
                  && !searchMatch(item[attr], $scope.filters[attr], $scope.exactFilters.indexOf(attr) > -1)) {
                   return false;
                }
            }
            return true;
        });

        // take care of the sorting order
        if ($scope.sort.sortingOrder !== '') {
            $scope.filteredIdps = $filter('orderBy')($scope.filteredIdps, $scope.sort.sortingOrder, $scope.sort.reverse);
        }

        // now group by pages
        $scope.groupToPages();
    };

    // calculate page in place
    $scope.groupToPages = function () {
        $scope.pagedIdps = [];
    
        for (var i = 0; i < $scope.filteredIdps.length; i++) {
            if (i % $scope.idpsPerPage.id === 0) {
                $scope.pagedIdps[Math.floor(i / $scope.idpsPerPage.id)] = [ $scope.filteredIdps[i] ];
            } else {
                $scope.pagedIdps[Math.floor(i / $scope.idpsPerPage.id)].push($scope.filteredIdps[i]);
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

    $scope.getIdP = function(pageSize) {
        $scope.idpsPerPage = (pageSize) ? pageSize : $scope.pageSizes[2];

        url = 'services/json_api.php?rpp=All';
        $http.get(url).success(function (response) {
            $scope.idps = response.results;
            $scope.numRows = response.num_rows;

            $scope.idps.forEach(function (idp) {
                // Define css_class with color for specific check status
                if (idp.ignoreEntity == 1) {
                    idp.css_class = 'silver';
                } else if (idp.currentResult == '1 - OK') {
                    idp.css_class = 'green';
                } else if (idp.currentResult == '2 - FORM-Invalid') {
                    idp.css_class = 'yellow';
                } else if (idp.currentResult == '3 - HTTP-Error') {
                    idp.css_class = 'red';
                } else if (idp.currentResult == '3 - CURL-Error') {
                    idp.css_class = 'red';
                } else {
                    idp.css_class = 'white';
                }

                // Re-organize contacts
                idp.contacts = []
                idp.technicalContacts.split(',').forEach(function (contact) {
                    if (contact) {
                        idp.contacts.push({'type': 'T', 'mail': contact });
                    }
                });
                idp.supportContacts.split(',').forEach(function (contact) {
                    if (contact) {
                        idp.contacts.push({'type': 'S', 'mail': contact });
                    }
                });
            });

            if ($scope.idpsPerPage.id == 'All') {
                $scope.idpsPerPage.id = $scope.numRows;
            }

            // functions have been describe process the data for display
            $scope.search();
        });
    };

    $scope.getIdP();
});

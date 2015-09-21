var app = angular.module('EccsApplication', []);

app.controller('EccsController', function ($scope) {
    $scope.filters = {
        'css_class': undefined,
        'ignoreEntity': undefined,
        'currentResult': 'All',
        'checkResult': 'All'
    };

    $scope.filterStatus = function (instatus) {
       if (instatus === 'disabled') {
           $scope.filters.css_class = 'All';
           $scope.filters.ignoreEntity = 'true';
       }
       else {
           $scope.filters.css_class = instatus;
           $scope.filters.ignoreEntity = undefined;
       }

       $scope.$broadcast("UPDATE_STATUS", $scope.filters);
    };
});

app.service('EccsJsonAPI', function($http) {
    var jsonApi = {};

    jsonApi.getNew = function() {
        var apis = {
            urlIdp: 'services/json_api.php?action=entities&rpp=All',
            urlTest: 'services/json_api.php?action=checks&rpp=All',
            urlCheck: 'services/json_api.php?action=checkhtml&checkid=',
            urlFeds: 'services/json_api.php?action=fedstats'
        };

        apis.getEntities = function () {
            return $http.get(apis.urlIdp).then(function (response) {
                response.data.results.forEach(function (item) {
                    // Re-organize contacts
                    item.contacts = [];
                    item.technicalContacts.split(',').forEach(function (contact) {
                        if (contact) {
                            item.contacts.push({'type': 'T', 'mail': contact });
                        }
                    });
                    item.supportContacts.split(',').forEach(function (contact) {
                        if (contact) {
                            item.contacts.push({'type': 'S', 'mail': contact });
                        }
                    });
                });
    
                return response.data.results;
            });
        };

        apis.getTests = function () {
            return $http.get(apis.urlTest).then(function (response) {
                return response.data.results;
            });
        };

        apis.getCheck = function (checkid) {
            return $http.get(apis.urlCheck + checkid).then(function (response) {
                return response.data.result;
            });
        };

        apis.getFedStatistics = function () {
            return $http.get(apis.urlFeds).then(function (response) {
                var items = [];
                response.data.results.forEach(function (result) {
                    var curitem = undefined;
                    items.forEach(function (item) {
                        if (item.registrationAuthority == result.registrationAuthority) {
                            curitem = item;
                        }
                    });

                    if (!curitem) {
                        curitem = {
                            'checkDate': result.checkDate,
                            'registrationAuthority': result.registrationAuthority,
                            'totIdps': 0,
                            'idpsOk': 0,
                            'idpsWarn': 0,
                            'idpsError': 0,
                            'idpsDisabled': 0
                        };
                        items.push(curitem);
                    }

                    switch(result.css_class) {
                        case 'silver':
                            curitem['idpsDisabled'] += result.numIdPs;
                            break;
                        case 'green':
                            curitem['idpsOk'] += result.numIdPs;
                            break;
                        case 'yellow':
                            curitem['idpsWarn'] += result.numIdPs;
                            break;
                        case 'red':
                            curitem['idpsError'] += result.numIdPs;
                            break;
                        default:
                            break;
                    }

                    curitem['totIdps'] += result.numIdPs;
                });

                return items;
            });
        };

        return apis;
    };

    return jsonApi;
});

app.service('Pagination', function() {
    var pagination = {};

    pagination.getNew = function() {
        var paginator = {
            pagedItems: [],
            itemsPerPage: undefined,
            currentPage: 1,
            gap: 5,
            pageSizes: [
                { name: '10', id: 10 },
                { name: '20', id: 20 },
                { name: '30', id: 30 },
                { name: '40', id: 40 },
                { name: '50', id: 50 },
                { name: '100', id: 100 },
                { name: 'All', id: 'All' }
            ]
        };

        paginator.setPageSize = function (pageSize, numRows) {
            paginator.itemsPerPage = (pageSize) ? pageSize : paginator.pageSizes[2];

            if (paginator.itemsPerPage.id === 'All') {
                paginator.itemsPerPage.id = numRows;
            }
        };

        paginator.groupToPages = function (items) {
            paginator.pagedItems = [];

            for (var i = 0; i < items.length; i++) {
                if (i % paginator.itemsPerPage.id === 0) {
                    paginator.pagedItems[Math.floor(i / paginator.itemsPerPage.id)] = [ items[i] ];
                } else {
                    paginator.pagedItems[Math.floor(i / paginator.itemsPerPage.id)].push(items[i]);
                }
            }
        };

        paginator.range = function (size, start, end) {
            var ret = [];
            start = (start < 1) ? 1 : start;
            start = (start > size) ? size : start;
            end = (end > size) ? size : end;

            if (size < end) {
                end = size;
                start = size - $scope.gap;
            }

            for (var i = start; i <= end; i++) {
                ret.push(i);
            }

            return ret;
        };

        paginator.getPageRange = function () {
            var size = paginator.pagedItems.length;
            var start = paginator.currentPage - paginator.gap;
            var end = paginator.currentPage + paginator.gap;

            return paginator.range(size, start, end);
        };

        paginator.setPage = function (n) {
            paginator.currentPage = n;
        };

        return paginator;
    };

    return pagination;
});

app.service('Filtering', function($filter) {
    var filtering = {};

    filtering.getNew = function() {
        var filter = {
            filters: {},
            exactFilters: [],
            filteredItems: [],
            attrSupportingAll: []
        };

        filter.searchMatch = function (haystack, needle, exact) {
            if (!needle) {
                return true;
            }

            if (!haystack) {
                return false;
            }

            if (exact) {
                return haystack.toString().toLowerCase() === needle.toString().toLowerCase();
            }
            else {
                return haystack.toString().toLowerCase().indexOf(needle.toString().toLowerCase()) !== -1;
            }
        };

        filter.search = function (items, newfilters) {
            filter.filters = (newfilters) ? newfilters : filter.filters;

            filter.filteredItems = $filter('filter')(items, function (item) {
                for (var attr in filter.filters) {
                    if ((filter.attrSupportingAll.indexOf(attr) === -1 || filter.filters[attr] !== 'All')
                      && !filter.searchMatch(item[attr], filter.filters[attr], filter.exactFilters.indexOf(attr) > -1)) {
                       return false;
                    }
                }
                return true;
            });
        };

        return filter;
    };

    return filtering;
});

app.service('Sorting', function($filter) {
    var sorting = {};

    sorting.getNew = function() {
        var sorter = {
            sortingOrder: undefined,
            reverse: false,
            sortedItems: []
        };

        sorter.sort = function (items) {
            if (sorter.sortingOrder === 'css_class') {
                sorter.sortedItems = $filter('orderBy')(items, function (item) {
                    switch (item.css_class) {
                        case 'green':
                            return 1;
                        case 'yellow':
                            return 2;
                        case 'red':
                            return 3;
                        default:
                            return 0;
                    }
                }, sorter.reverse);
            }
            else if (sorter.sortingOrder !== '') {
                sorter.sortedItems = $filter('orderBy')(items, sorter.sortingOrder, sorter.reverse);
            }
        };

        return sorter;
    };

    return sorting;
});

app.directive("customSort", function() {
    return {
        restrict: 'A',
        transclude: true,
        scope: {
            order: '=',
            sorting: '=',
            updateFn: '&'
        },
        template:
            '<a ng-click="sort_by(order)">'+
            '  <span class="link" ng-transclude></span>'+
            '  <img ng-src="{{selectedCls(order)}}" />'+
            '</a>',
        link: function(scope) {
            // change sorting order
            scope.sort_by = function(newSortingOrder) {
                if (scope.sorting.sortingOrder === newSortingOrder) {
                    scope.sorting.reverse = !scope.sorting.reverse;
                }

                scope.sorting.sortingOrder = newSortingOrder;
                scope.updateFn();
            };

            scope.selectedCls = function(column) {
                if (column === scope.sorting.sortingOrder) {
                    return ('images/' + ((scope.sorting.reverse) ? 'desc' : 'asc') + '.gif');
                }
                else {
                    return 'images/sort.gif';
                }
            };
        }
    };
});

var getParameterByName = function (name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");

    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    var results = regex.exec(location.search);

    return results === null ? undefined : decodeURIComponent(results[1].replace(/\+/g, " "));
};


var app = angular.module("eccs-idps", []);

app.controller('menuController', function ($scope, $http, $filter, $location) {
    $scope.filters = {
        'currentResult': 'All',
        'checkResult': 'All',
        'ignoreEntity': undefined
    };

    $scope.filterStatus = function (instatus) {
       if (instatus == 'disabled') {
           $scope.filters.currentResult = 'All';
           $scope.filters.checkResult = 'All';
           $scope.filters.ignoreEntity = 'true';
       }
       else {
           $status = { 'error' : '3 - ', 'warning' : '2 - ', 'ok' : '1 - ' };
           $scope.filters.currentResult = $status[instatus];
           $scope.filters.checkResult = $status[instatus];
           $scope.filters.ignoreEntity = undefined;
       }

       $scope.$broadcast("UPDATE_STATUS", $scope.filters);
    };
});

app.filter('substring', function() {
   return function(str, start, end) {
      return str.substring(start, end);
   };
});

app.$inject = ['$scope', '$http', '$filter', '$location'];

app.directive("customSort", function() {
  return {
    restrict: 'A',
    transclude: true,    
    scope: {
      order: '=',
      sort: '='
    },
    template : 
      '<a ng-click="sort_by(order)">'+
      '  <span class="link" ng-transclude></span>'+
      '  <img ng-src="{{selectedCls(order)}}" />'+
      '</a>',
    link: function(scope) {
      // change sorting order
      scope.sort_by = function(newSortingOrder) {       
        var sort = scope.sort;
        
        if (sort.sortingOrder == newSortingOrder){
            sort.reverse = !sort.reverse;
        }                    

        sort.sortingOrder = newSortingOrder;        
      };
   
      scope.selectedCls = function(column) {
        if (column == scope.sort.sortingOrder) {
          return ('images/' + ((scope.sort.reverse) ? 'desc' : 'asc') + '.gif');
        }
        else{            
          return 'images/sort.gif' 
        } 
      };      
    }// end link
  }
});

function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? undefined : decodeURIComponent(results[1].replace(/\+/g, " "));
}

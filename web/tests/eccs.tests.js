'use strict'

describe('EccsController', function() {
    var scope;
    var $httpBackend;

    beforeEach(angular.mock.module('EccsApplication'));
    beforeEach(angular.mock.inject(function ($rootScope, $controller, $injector) {
        scope = $rootScope.$new();
        $httpBackend = $injector.get('$httpBackend');
        $controller('EccsController', {$scope: scope});
    }));

    it('should have filter initialized correctly', function () {
        expect(scope.filters.css_class).toBe(undefined);
        expect(scope.filters.ignoreEntity).toBe(undefined);
    });

    it('should have a working EccsJsonAPI service', inject(['EccsJsonAPI', function(EccsJsonAPI) {
        scope.jsonApi = EccsJsonAPI.getNew();

        expect(scope.jsonApi.urlIdp).toBeDefined();
        expect(scope.jsonApi.urlTest).toBeDefined();
        expect(scope.jsonApi.urlCheck).toBeDefined();

        expect(scope.jsonApi.getEntities).toBeDefined();
        expect(scope.jsonApi.getTests).toBeDefined();
        expect(scope.jsonApi.getCheck).toBeDefined();
    }]));

    it('should EccsJsonAPI.getEntities return promise containing array', inject(['EccsJsonAPI', function(EccsJsonAPI) {
        $httpBackend.expectGET().respond(200, '{ results: [\'element1\', \'element2\'] }');
        scope.jsonApi = EccsJsonAPI.getNew();

        var promise = scope.jsonApi.getEntities();
        promise.then(function(results) {
            expect(results).toEqual(['element1', 'element2']);
        });
    }]));

    it('should EccsJsonAPI.getTests return promise containing array', inject(['EccsJsonAPI', function(EccsJsonAPI) {
        $httpBackend.expectGET().respond(200, '{ results: [\'element1\', \'element2\'] }');
        scope.jsonApi = EccsJsonAPI.getNew();

        var promise = scope.jsonApi.getTests();
        promise.then(function(results) {
            expect(results).toEqual(['element1', 'element2']);
        });
    }]));

    it('should EccsJsonAPI.getCheck return promise containing array', inject(['EccsJsonAPI', function(EccsJsonAPI) {
        $httpBackend.expectGET().respond(200, '{ results: \'element\' }');
        scope.jsonApi = EccsJsonAPI.getNew();
        var checkid = 123;

        var promise = scope.jsonApi.getCheck(checkid);
        promise.then(function(results) {
            expect(results).toEqual('element');
        });
    }]));

    it('should Sorting.sort return ordered array ascending', inject(['Sorting', function(Sorting) {
        scope.sorting = Sorting.getNew();
        scope.sorting.sortingOrder = 'field';
        scope.sorting.reverse = false;
        var input = [{field: 'element2'}, {field: 'element3'}, {field: 'element1'}, {field: 'element4'}];

        scope.sorting.sort(input);
        expect(scope.sorting.sortedItems).toEqual([{field: 'element1'}, {field: 'element2'}, {field: 'element3'}, {field: 'element4'}]);
    }]));

    it('should Sorting.sort return ordered array descending', inject(['Sorting', function(Sorting) {
        scope.sorting = Sorting.getNew();
        scope.sorting.sortingOrder = 'field';
        scope.sorting.reverse = true;
        var input = [{field: 'element2'}, {field: 'element3'}, {field: 'element1'}, {field: 'element4'}];

        scope.sorting.sort(input);
        expect(scope.sorting.sortedItems).toEqual([{field: 'element4'}, {field: 'element3'}, {field: 'element2'}, {field: 'element1'}]);
    }]));

    it('should Sorting.sort return ordered array by css_class', inject(['Sorting', function(Sorting) {
        scope.sorting = Sorting.getNew();
        scope.sorting.sortingOrder = 'css_class';
        scope.sorting.reverse = false;
        var input = [{css_class: 'green'}, {css_class: 'yellow'}, {css_class: 'red'}, {css_class: 'random'}];

        scope.sorting.sort(input);
        expect(scope.sorting.sortedItems).toEqual([{css_class: 'random'}, {css_class: 'green'}, {css_class: 'yellow'}, {css_class: 'red'}]);
    }]));

    it('should Filtering.search show only all exact matching elements (1 par)', inject(['Filtering', function(Filtering) {
        scope.filtering = Filtering.getNew();
        scope.filtering.filters = { 'filter': 'value1' };
        scope.filtering.exactFilters = ['filter'];
        scope.filtering.attrSupportingAll = [];

        var input = [{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}];

        scope.filtering.search(input)
        expect(scope.filtering.filteredItems).toEqual([{filter: 'value1', value: 1}]);
    }]));

    it('should Filtering.search show only all exact matching elements (2 pars)', inject(['Filtering', function(Filtering) {
        scope.filtering = Filtering.getNew();
        scope.filtering.filters = {};
        scope.filtering.exactFilters = ['filter'];
        scope.filtering.attrSupportingAll = [];

        var input = [{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}];
        var filters = { 'filter': 'value1' };

        scope.filtering.search(input, filters)
        expect(scope.filtering.filteredItems).toEqual([{filter: 'value1', value: 1}]);
    }]));

    it('should Filtering.search show only all non exact matching elements (1 par)', inject(['Filtering', function(Filtering) {
        scope.filtering = Filtering.getNew();
        scope.filtering.filters = { 'filter': 'value1' };
        scope.filtering.exactFilters = [];
        scope.filtering.attrSupportingAll = [];

        var input = [{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}];

        scope.filtering.search(input)
        expect(scope.filtering.filteredItems).toEqual([{filter: 'value1', value: 1}, {filter: 'value1bis', value: 3}]);
    }]));

    it('should Filtering.search show only all non exact matching elements (2 pars)', inject(['Filtering', function(Filtering) {
        scope.filtering = Filtering.getNew();
        scope.filtering.filters = {};
        scope.filtering.exactFilters = [];
        scope.filtering.attrSupportingAll = [];

        var input = [{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}];
        var filters = { 'filter': 'value1' };

        scope.filtering.search(input, filters)
        expect(scope.filtering.filteredItems).toEqual([{filter: 'value1', value: 1}, {filter: 'value1bis', value: 3}]);
    }]));

    it('should Filtering.search show all elements for attr supporting all', inject(['Filtering', function(Filtering) {
        scope.filtering = Filtering.getNew();
        scope.filtering.filters = { 'filter': 'All' };
        scope.filtering.exactFilters = [];
        scope.filtering.attrSupportingAll = ['filter'];

        var input = [{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}];

        scope.filtering.search(input)
        expect(scope.filtering.filteredItems).toEqual([{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}]);
    }]));

    it('should Filtering.search show all elements for empty filter', inject(['Filtering', function(Filtering) {
        scope.filtering = Filtering.getNew();
        scope.filtering.filters = { 'filter': undefined };
        scope.filtering.exactFilters = [];
        scope.filtering.attrSupportingAll = [];

        var input = [{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}];

        scope.filtering.search(input)
        expect(scope.filtering.filteredItems).toEqual([{filter: 'value1', value: 1}, {filter: 'value2', value: 2}, {filter: 'value1bis', value: 3}]);
    }]));

    it('should Pagination works for elements less than 1 page', inject(['Pagination', function(Pagination) {
        scope.pagination = Pagination.getNew();

        var pageSize = { name: 'pagesize', id: 10 };
        var input = ['item1'];

        scope.pagination.setPageSize(pageSize, input.length);
        scope.pagination.groupToPages(input)
        expect(scope.pagination.getPageRange()).toEqual([1]);
        expect(scope.pagination.pagedItems).toEqual([['item1']]);
    }]));

    it('should Pagination works for elements exactly 1 page', inject(['Pagination', function(Pagination) {
        scope.pagination = Pagination.getNew();

        var pageSize = { name: 'pagesize', id: 10 };
        var input = [];
        for (var i = 1; i <= 10; i++) {
            input.push('item' + i);
        }
        var output = [];
        for (var i = 1; i <= 10; i++) {
            output.push('item' + i);
        }

        scope.pagination.setPageSize(pageSize, input.length);
        scope.pagination.groupToPages(input)
        expect(scope.pagination.getPageRange()).toEqual([1]);
        expect(scope.pagination.pagedItems).toEqual([output]);
    }]));

    it('should Pagination works for elements more than 1 page', inject(['Pagination', function(Pagination) {
        scope.pagination = Pagination.getNew();

        var pageSize = { name: 'pagesize', id: 2 };
        var input = ['item1', 'item2', 'item3', 'item4', 'item5'];

        scope.pagination.setPageSize(pageSize, input.length);
        scope.pagination.groupToPages(input)
        expect(scope.pagination.getPageRange()).toEqual([1, 2, 3]);
        expect(scope.pagination.pagedItems).toEqual([['item1', 'item2'], ['item3', 'item4'], ['item5']]);
    }]));

    it('should Pagination works for all elements', inject(['Pagination', function(Pagination) {
        scope.pagination = Pagination.getNew();

        var pageSize = { name: 'pagesize', id: 'All' };
        var input = ['item1', 'item2', 'item3', 'item4', 'item5'];

        scope.pagination.setPageSize(pageSize, input.length);
        scope.pagination.groupToPages(input)
        expect(scope.pagination.getPageRange()).toEqual([1]);
        expect(scope.pagination.pagedItems).toEqual([['item1', 'item2', 'item3', 'item4', 'item5']]);
    }]));
});


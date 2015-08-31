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
            expect(results).toBe(['element1', 'element2']);
        });
    }]));

    it('should EccsJsonAPI.getTests return promise containing array', inject(['EccsJsonAPI', function(EccsJsonAPI) {
        $httpBackend.expectGET().respond(200, '{ results: [\'element1\', \'element2\'] }');
        scope.jsonApi = EccsJsonAPI.getNew();

        var promise = scope.jsonApi.getTests();
        promise.then(function(results) {
            expect(results).toBe(['element1', 'element2']);
        });
    }]));

    it('should EccsJsonAPI.getCheck return promise containing array', inject(['EccsJsonAPI', function(EccsJsonAPI) {
        $httpBackend.expectGET().respond(200, '{ results: \'element\' }');
        scope.jsonApi = EccsJsonAPI.getNew();
        var checkid = 123;

        var promise = scope.jsonApi.getCheck(checkid);
        promise.then(function(results) {
            expect(results).toBe('element');
        });
    }]));
});


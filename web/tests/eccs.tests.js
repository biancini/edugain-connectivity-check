'use strict'

describe('EccsController', function() {
    var scope;
    beforeEach(angular.mock.module('EccsApplication'));
    beforeEach(angular.mock.inject(function ($rootScope, $controller) {
        scope = $rootScope.$new();
        $controller('EccsController', {$scope: scope});
    }));

    it('should have filter initialized correctly', function () {
        expect(scope.filters.css_class).toBe(undefined);
        expect(scope.filters.ignoreEntity).toBe(undefined);
    });
});


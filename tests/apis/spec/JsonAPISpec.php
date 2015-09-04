<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use PhpSpec\Exception\Example\FailureException;

require_once '../../utils/DBManager.php';

class JsonAPISpec extends ObjectBehavior {
    function it_is_initializable($dbManager) {
        $dbManager->beADoubleOf('DBManager');
        $this->beConstructedWith($dbManager);

        $this->shouldHaveType('JsonAPI');
    }

    function it_getEntities_throw_exception_no_action($dbManager) {
        $dbManager->beADoubleOf('DBManager');
        $this->beConstructedWith($dbManager);

        $this->shouldThrow('Exception')->duringHandle();
    }

    function getMatchers() {
        return [
            'elementLike' => function ($result, $element) {
                foreach($element as $key => $val) {
                    if ($val != $result[$key]) {
                        throw new FailureException(sprintf('Expected value "%s" for key "%s", but obtained "%s".', $val, $key, $result[$key]));
                    }
                }
                return true;
            },
        ];
    }

    function _generate_entity($entityid, $ignore = 0, $currentResult = '1 - OK', $previousResult = '1 - OK') {
        return array(
            'entityID' => "$entityid",
            'registrationAuthority' => "registrationAuthority value",
            'displayName' => "displayName value for $entityid",
            'technicalContacts' => "technicalContacts value",
            'supportContacts' => "supportContacts value",
            'ignoreEntity' => $ignore,
            'lastCheck' => "lastCheck value",
            'currentResult' => $currentResult,
            'previousResult' => $previousResult,
        );
    }

    function _generate_check($checkid, $entityid, $checkResult = '1 - OK') {
        return array(
            'id' => $checkid,
            'entityID' => $entityid,
            'spEntityID' => "spEntityID value",
            'checkTime' => "checkTime value",
            'httpStatusCode' => 200,
            'checkResult' => $checkResult,
            'acsUrls' => "acsUrl value",
            'serviceLocation' => 'serviceLocation value',
        );
    }

    function it_getEntities_works($dbManager, $result) {
        $requestParams = array('action' => 'entities');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_entity('urn:mace:entityid:1'), false));
        $entity = array('entityID' => 'urn:mace:entityid:1');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 1);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 1);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($entity);
    }

    function it_getEntities_status_ok_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'entities');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_entity('urn:mace:entityid:1', $ignore = 0, $currentResult = '1 - OK'), false));
        $entity = array('entityID' => 'urn:mace:entityid:1', 'currentResult' => 'OK', 'css_class' => 'green');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($entity);
    }

    function it_getEntities_status_form_invalid_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'entities');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_entity('urn:mace:entityid:1', $ignore = 0, $currentResult = '2 - FORM-Invalid'), false));
        $entity = array('entityID' => 'urn:mace:entityid:1', 'currentResult' => 'FORM-Invalid', 'css_class' => 'yellow');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($entity);
    }

    function it_getEntities_status_http_error_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action'=> 'entities');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_entity('urn:mace:entityid:1', $ignore = 0, $currentResult = '3 - HTTP-Error'), false));
        $entity = array('entityID' => 'urn:mace:entityid:1', 'currentResult' => 'HTTP-Error', 'css_class' => 'red');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($entity);
    }

    function it_getEntities_status_curl_error_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'entities');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_entity('urn:mace:entityid:1', $ignore = 0, $currentResult = '3 - CURL-Error'), false));
        $entity = array('entityID' => 'urn:mace:entityid:1', 'currentResult' => 'CURL-Error', 'css_class' => 'red');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($entity);
    }

    function it_getEntities_ignored_entity_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'entities');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_entity('urn:mace:entityid:1', $ignore = 1), false));
        $entity = array('entityID' => 'urn:mace:entityid:1', 'css_class' => 'silver');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($entity);
    }

    function it_getEntities_one_page($dbManager, $result) {
        $requestParams = array('action' => 'entities', 'rpp' => 2);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_entity('urn:mace:entityid:1'),
                                   $this->_generate_entity('urn:mace:entityid:2'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(2);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 2);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 1);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(2);
    }

    function it_getEntities_more_page($dbManager, $result) {
        $requestParams = array('action' => 'entities', 'rpp' => 2);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_entity('urn:mace:entityid:1'),
                                   $this->_generate_entity('urn:mace:entityid:2'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(5);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 5);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 3);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(2);
    }

    function it_getEntities_page_two($dbManager, $result) {
        $requestParams = array('action' => 'entities', 'rpp' => 2, 'page' => 2);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_entity('urn:mace:entityid:3'),
                                   $this->_generate_entity('urn:mace:entityid:4'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(5);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 5);
        $returned->shouldHaveKeyWithValue('page', 2);
        $returned->shouldHaveKeyWithValue('total_pages', 3);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(2);
    }

    function it_getEntities_page_all($dbManager, $result) {
        $requestParams = array('action' => 'entities', 'rpp' => 'All');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_entity('urn:mace:entityid:1'),
                                   $this->_generate_entity('urn:mace:entityid:2'),
                                   $this->_generate_entity('urn:mace:entityid:3'),
                                   $this->_generate_entity('urn:mace:entityid:4'),
                                   $this->_generate_entity('urn:mace:entityid:5'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(5);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 5);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 1);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(5);
    }

    function it_getChecks_works($dbManager, $result) {
        $requestParams = array('action' => 'checks');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_check('123123', 'urn:mace:entityid:1'), false));
        $check = array('entityID' => 'urn:mace:entityid:1');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 1);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 1);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($check);
    }

    function it_getChecks_status_ok_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'checks');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_check('123123', 'urn:mace:entityid:1', $checkResult = '1 - OK'), false));
        $check = array('entityID' => 'urn:mace:entityid:1', 'checkResult' => 'OK', 'css_class' => 'green');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($check);
    }

    function it_getChecks_status_form_invalid_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'checks');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_check('123123', 'urn:mace:entityid:1', $checkResult = '2 - FORM-Invalid'), false));
        $check = array('entityID' => 'urn:mace:entityid:1', 'checkResult' => 'FORM-Invalid', 'css_class' => 'yellow');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($check);
    }

    function it_getChecks_status_http_error_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'checks');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_check('123123', 'urn:mace:entityid:1', $checkResult = '3 - HTTP-Error'), false));
        $check = array('entityID' => 'urn:mace:entityid:1', 'checkResult' => 'HTTP-Error', 'css_class' => 'red');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($check);
    }

    function it_getChecks_status_curl_error_correct_ccs_class($dbManager, $result) {
        $requestParams = array('action' => 'checks');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'), array($this->_generate_check('123123', 'urn:mace:entityid:1', $checkResult = '3 - CURL-Error'), false));
        $check = array('entityID' => 'urn:mace:entityid:1', 'checkResult' => 'CURL-Error', 'css_class' => 'red');

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(1);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(1);
        $returned['results'][0]->shouldElementLike($check);
    }

    function is_getChecks_one_page($dbManager, $result) {
        $requestParams = array('action' => 'checks', 'rpp' => 2);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_check(123123, 'urn:mace:entityid:1'),
                                   $this->_generate_check(123124, 'urn:mace:entityid:2'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(2);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 2);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 1);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(2);
    }

    function it_getChecks_more_page($dbManager, $result) {
        $requestParams = array('action' => 'checks', 'rpp' => 2);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_check(123123, 'urn:mace:entityid:1'),
                                   $this->_generate_check(123124, 'urn:mace:entityid:2'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(5);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 5);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 3);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(2);
    }

    function it_getChecks_page_two($dbManager, $result) {
        $requestParams = array('action' => 'checks', 'rpp' => 2, 'page' => 2);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_check(123123, 'urn:mace:entityid:3'),
                                   $this->_generate_check(123124, 'urn:mace:entityid:4'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(5);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 5);
        $returned->shouldHaveKeyWithValue('page', 2);
        $returned->shouldHaveKeyWithValue('total_pages', 3);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(2);
    }

    function it_getChecks_page_all($dbManager, $result) {
        $requestParams = array('action' => 'checks', 'rpp' => 'All');
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array($this->_generate_check(123123, 'urn:ace:entityid:1'),
                                   $this->_generate_check(123124, 'urn:mace:entityid:2'),
                                   $this->_generate_check(123125, 'urn:mace:entityid:3'),
                                   $this->_generate_check(123126, 'urn:mace:entityid:4'),
                                   $this->_generate_check(123127, 'urn:mace:entityid:5'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->escapeStringChars('entityID')->shouldBeCalled()->willReturn('entityID');
        $dbManager->executeStatement(false, Argument::type('QueryBuilder'))->willReturn(5);
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('num_rows', 5);
        $returned->shouldHaveKeyWithValue('page', 1);
        $returned->shouldHaveKeyWithValue('total_pages', 1);
        $returned->shouldHaveKey('results');
        $returned['results']->shouldBeArray();
        $returned['results']->shouldHaveCount(5);
    }

    function it_getCheckHtml_page_all($dbManager, $result) {
        $requestParams = array('action' => 'checkhtml', 'checkid' => 123123);
        $result->beADoubleOf('mysqli_result');
        call_user_func_array(array($result->fetch_assoc(), 'willReturn'),
                             array(array('entityID' => 'urn:mace:entityid:1',
                                         'spEntityID' => 'spEntityID value',
                                         'acsUrls' => 'acsUrl value',
                                         'serviceLocation' => 'serviceLocation value',
                                         'checkTime' => 'checkTime value',
                                         'checkHtml' => 'checkHtml value'),
                                   false));

        $dbManager->beADoubleOf('DBManager');
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeArray();
        $returned->shouldHaveKey('result');
        $returned['result']->shouldBeArray();
        $returned['result']->shouldHaveKeyWithValue('entityID', 'urn:mace:entityid:1');
        $returned['result']->shouldHaveKeyWithValue('spEntityID', 'spEntityID value');
        $returned['result']->shouldHaveKeyWithValue('acsUrl', 'acsUrl value');
        $returned['result']->shouldHaveKeyWithValue('serviceLocation', 'serviceLocation value');
        $returned['result']->shouldHaveKeyWithValue('checkTime', 'checkTime value');
        $returned['result']->shouldHaveKeyWithValue('checkHtml', 'checkHtml value');
    }
}

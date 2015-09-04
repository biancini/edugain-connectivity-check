<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

require_once '../../utils/DBManager.php';

class CheckHtmlSpec extends ObjectBehavior {
    function it_is_initializable($dbManager) {
        $dbManager->beADoubleOf('DBManager');
        $this->beConstructedWith($dbManager);

        $this->shouldHaveType('CheckHtml');
    }

    function it_handle_works($dbManager, $result) {
        $requestParams = array('checkid' => '123123');
        $result->beADoubleOf('mysqli_result');
        $result->fetch_assoc()->willReturn(array('checkHtml' => 'test html page'), false);

        $dbManager->beADoubleOf('DBManager');
        $dbManager->executeStatement(true, Argument::type('QueryBuilder'))->willReturn($result);

        $this->beConstructedWith($dbManager, $requestParams);
        $returned = $this->handle();
        $returned->shouldBeLike('test html page\n');
    }
}

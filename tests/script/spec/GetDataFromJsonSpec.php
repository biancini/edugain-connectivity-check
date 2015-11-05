<?php

namespace spec;

use PhpSpec\ObjectBehavior;

require_once '../../check_script/GetFile.php';

class GetDataFromJsonSpec extends ObjectBehavior {
    function it_is_initializable($getFile) {
        $confArray = array('edugain_db_json' => array('json_feds_url' => 'http://feds-url.com', 'json_idps_url' => 'http://idp-url.com'));
        $getFile->beADoubleOf('GetFile');
        $this->beConstructedWith($confArray, $getFile);

        $this->shouldHaveType('GetDataFromJson');
    }

    function it_obtainFederationsList_works($getFile) {
        $confArray = array('edugain_db_json' => array('json_feds_url' => 'http://feds-url.com', 'json_idps_url' => 'http://idp-url.com'));
        $getFile->beADoubleOf('GetFile');
        $getFile->getFileFromUrl('http://feds-url.com')->shouldBeCalled()->willReturn('{ "federation": "value" }');
        $this->beConstructedWith($confArray, $getFile);

        $returned = $this->obtainFederationsList();

        $returned->shouldBeArray();
        $returned->shouldHaveKeyWithValue('federation', 'value');
    }
}

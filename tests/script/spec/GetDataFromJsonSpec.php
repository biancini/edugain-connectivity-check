<?php

namespace spec;

use PhpSpec\ObjectBehavior;

class GetDataFromJsonSpec extends ObjectBehavior {
    function it_is_initializable() {
        $this->shouldHaveType('GetDataFromJson');
    }

    function it_obtainFederationsList_works() {
        $confArray = array('edugain_db_json' => array('json_feds_url' => 'http://feds-url.com', 'json_idps_url' => 'http://idp-url.com'));
        $arrContextOptions = array();
        $this->beConstructedWith($confArray);

        //$this->get_file = function ($url) { return "ciao"; };
        $this->get_file('http://feds-url.com')->shouldBeCalled()->willReturn("ciao");
        $this->obtainFederationsList();

        //$this->getQuerySql()->shouldReturn("SELECT * FROM table WHERE sql_name = ?");
        //$this->getQueryParams()->shouldReturn(array('s', 'value'));
    }
}

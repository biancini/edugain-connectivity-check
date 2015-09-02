<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueryBuilderSpec extends ObjectBehavior {
    function it_is_initializable() {
        $this->shouldHaveType('QueryBuilder');
    }

    function it_addAllSqlConditions_works_no_like() {
        $params = array('param_name' => 'value');
        $sql = "SELECT * FROM table";

        $this->setSql($sql);
        $this->addAllSqlConditions($params, array(array('param_name', 'sql_name', false, NULL)));

        $this->getQuerySql()->shouldReturn("SELECT * FROM table WHERE sql_name = ?");
        $this->getQueryParams()->shouldReturn(array('s', 'value'));
    }

    //array('f_check_time', 'checkTime', false, array('1' => 'DATE(lastCheck) = curdate()', '2' => 'DATE(lastCheck) = curdate() - interval 1 day')),
    function it_addAllSqlConditions_works_like() {
        $params = array('param_name' => 'value');
        $sql = "SELECT * FROM table";

        $this->setSql($sql);
        $this->addAllSqlConditions($params, array(array('param_name', 'sql_name', true, NULL)));

        $this->getQuerySql()->shouldReturn("SELECT * FROM table WHERE sql_name LIKE ?");
        $this->getQueryParams()->shouldReturn(array('s', '%value%'));
    }
}

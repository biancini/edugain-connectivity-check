<?php

namespace spec;

use PhpSpec\ObjectBehavior;

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

    function it_addAllSqlConditions_works_like() {
        $params = array('param_name' => 'value');
        $sql = "SELECT * FROM table";

        $this->setSql($sql);
        $this->addAllSqlConditions($params, array(array('param_name', 'sql_name', true, NULL)));

        $this->getQuerySql()->shouldReturn("SELECT * FROM table WHERE sql_name LIKE ?");
        $this->getQueryParams()->shouldReturn(array('s', '%value%'));
    }

    function it_addAllSqlConditions_works_map() {
        $params = array('param_name' => 'value');
        $sql = "SELECT * FROM table";
        $map = array('value' => 'mapped value');

        $this->setSql($sql);
        $this->addAllSqlConditions($params, array(array('param_name', 'sql_name', false, $map)));

        $this->getQuerySql()->shouldReturn("SELECT * FROM table WHERE sql_name = ?");
        $this->getQueryParams()->shouldReturn(array('s', 'mapped value'));
    }

    function it_appendConditions_works() {
        $sql = "SELECT * FROM TABLE";
        $this->setSql($sql);
        $this->appendConditions(" ORDER BY field");

        $this->getQuerySql()->shouldReturn("SELECT * FROM TABLE ORDER BY field");
    }

    function it_addQueryParam_works() {
        $this->addQueryParam('value', 's');
        $this->getQueryParams()->shouldReturn(array('s', 'value'));
    }
}

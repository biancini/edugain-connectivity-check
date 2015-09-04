<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DBManagerSpec extends ObjectBehavior {
    function it_is_initializable($mysqli) {
        $mysqli->beADoubleOf('mysqli');
        $this->beConstructedWith($mysqli);

        $this->shouldHaveType('DBManager');
    }

    function it_executeStatement_returns_num_rows_if_no_resultset($query, $stmt, $mysqli) {
        $stmt->beADoubleOf('mysqli_stmt');
        $stmt->execute()->willReturn(true);
        $query->beADoubleOf('QueryBuilder');
        $query->getQuerySql()->willReturn("");
        $query->getQueryParams()->willReturn(array());
        $query->getNumRows($stmt)->willReturn(10);
        $mysqli->beADoubleOf('mysqli');
        $mysqli->prepare("")->willReturn($stmt);

        $this->beConstructedWith($mysqli);
        $this->executeStatement(false, $query, NULL)->shouldReturn(10);
    }

    function it_executeStatement_returns_array_if_resultset($query, $stmt, $mysqli) {
        $query->beADoubleOf('QueryBuilder');
        $query->getQuerySql()->willReturn("");
        $query->getQueryParams()->willReturn(array());
        $result = array(1, 2, 3);
        $stmt->beADoubleOf('mysqli_stmt');
        $stmt->execute()->willReturn(true);
        $stmt->get_result()->willReturn($result);
        $mysqli->beADoubleOf('mysqli');
        $mysqli->prepare("")->willReturn($stmt);

        $this->beConstructedWith($mysqli);
        $this->executeStatement(true, $query, NULL)->shouldReturn($result);
    }
}

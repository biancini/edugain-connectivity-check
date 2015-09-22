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

    function it_executeStatement_returns_num_rows_if_no_resultset($query, $stmt, $mysqli, $result) {
        $query->beADoubleOf('QueryBuilder');
        $query->getQuerySql()->willReturn("");
        $query->getQueryParams()->willReturn(array());
        $result->beADoubleOf('mysqli_result');
        $result->fetch_row()->willReturn(array(10));
        $stmt->beADoubleOf('mysqli_stmt');
        $stmt->execute()->willReturn(true);
        $stmt->get_result()->willReturn($result);
        $mysqli->beADoubleOf('mysqli');
        $mysqli->prepare("")->willReturn($stmt);

        $this->beConstructedWith($mysqli);
        $this->executeStatement(false, $query, NULL)->shouldReturn(10);
    }
}

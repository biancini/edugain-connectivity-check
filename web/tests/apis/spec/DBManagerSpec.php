<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DBManagerSpec extends ObjectBehavior {
    function it_is_initializable() {
        $this->shouldHaveType('DBManager');
    }

    function it_escapeStringChars_escapes_strings_correctly() {
        $input = "example string \n'\"";
        $output = "example string \\n\\'\\\"";

        $this->escapeStringChars($input)->shouldReturn($output);
    }

    function it_executeStatement_returns_true_if_no_resultset($query, $stmt, $mysqli) {
        $query->beADoubleOf('QueryBuilder');
        $query->getQuerySql()->willReturn("");
        $query->getQueryParams()->willReturn(array());
        $stmt->beADoubleOf('mysqli_stmt');
        $stmt->execute()->willReturn(true);
        $mysqli->beADoubleOf('mysqli');
        $mysqli->prepare("")->willReturn($stmt);

        $this->beConstructedWith($mysqli);
        $this->executeStatement(false, $query, NULL)->shouldReturn(true);
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

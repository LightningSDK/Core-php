<?php
namespace Tools;

use Lightning\Tools\Database;

class DatabaseTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        require_once 'bootstrap.php';
    }

    protected function _after()
    {
    }

    // tests
    public function testParseQuery()
    {
        // Basic query
        $database = new Database();
        $values = [];
        $query = $database->parseQuery([
            'select' => '*',
            'from' => 'table',
            'where' => ['column' => 5],
        ], $values);
        $this->assertEquals('SELECT * FROM `table` WHERE `column` = ? ', $query);
        $this->assertEquals([5], $values);

        // Select table.*
        $database = new Database();
        $values = [];
        $query = $database->parseQuery([
            'select' => 'table.*',
            'from' => 'table',
            'where' => ['column' => 5],
        ], $values);
        $this->assertEquals('SELECT `table`.* FROM `table` WHERE `column` = ? ', $query);
        $this->assertEquals([5], $values);

        // Select table.*
        $database = new Database();
        $values = [];
        $query = $database->parseQuery([
            'select' => ['table.*', 'alias' => 'another_field'],
            'from' => 'table',
            'where' => ['column' => 5],
        ], $values);
        $this->assertEquals('SELECT `table`.*, `another_field` AS `alias` FROM `table` WHERE `column` = ? ', $query);
        $this->assertEquals([5], $values);
    }

    public function testParseSort()
    {
        // Select table.*
        $database = new Database();
        $values = [];
        $query = $database->parseQuery([
            'select' => '*',
            'from' => 'table',
            'where' => ['column' => 5],
            'order_by' => ['up' => 'ASC', 'down' => 'DESC'],
        ], $values);
        $this->assertEquals('SELECT * FROM `table` WHERE `column` = ?  ORDER BY `up` ASC,`down` DESC', $query);
        $this->assertEquals([5], $values);
    }

    public function testParseHaving()
    {
        // Select table.*
        $database = new Database();
        $values = [];
        $query = $database->parseQuery([
            'select' => '*',
            'from' => 'table',
            'where' => ['column' => 5],
            'having' => ['some_aggregate' => 5],
        ], $values);
        $this->assertEquals('SELECT * FROM `table` WHERE `column` = ?  HAVING `some_aggregate` = ? ', $query);
        $this->assertEquals([5, 5], $values);
    }

    public function testMergeSelect()
    {
        // Empty queries
        $query = [];
        $filter = [];
        Database::filterQuery($query, $filter);
        $this->assertEquals([], $query);

        // Empty left
        $query = [];
        $filter = [
            'where' => ['test' => 5],
            'select' => '*',
        ];
        Database::filterQuery($query, $filter);
        $this->assertEquals([
            'where' => ['test' => 5],
            'select' => [
                '*'
            ],
        ], $query);

        // Empty right
        $query = [
            'where' => ['test' => 5],
            'select' => '*',
        ];
        $filter = [];
        Database::filterQuery($query, $filter);
        $this->assertEquals([
            'where' => ['test' => 5],
            'select' => '*',
        ], $query);
    }

    public function testMergeJoin()
    {
        // Non array join
        $query = [
            'join' => [
                'join' => 'table1',
                'on' => ['table1.foo' => ['table2.bar']]
            ]
        ];
        $filter = [
            'join' => [
                'left_join' => 'table2',
                'using' => 'bar',
            ],
        ];
        Database::filterQuery($query, $filter);
        $this->assertEquals([
            'join' => [
                [
                    'join' => 'table1',
                    'on' => ['table1.foo' => ['table2.bar']]
                ],
                [
                    'left_join' => 'table2',
                    'using' => 'bar',
                ]
            ],
        ], $query);

        // Non array join right
        $query = [
            'join' => [[
                'join' => 'table1',
                'on' => ['table1.foo' => ['table2.bar']]
            ]]
        ];
        $filter = [
            'join' => [
                'left_join' => 'table2',
                'using' => 'bar',
            ],
        ];
        Database::filterQuery($query, $filter);
        $this->assertEquals([
            'join' => [
                [
                    'join' => 'table1',
                    'on' => ['table1.foo' => ['table2.bar']]
                ],
                [
                    'left_join' => 'table2',
                    'using' => 'bar',
                ]
            ],
        ], $query);

        // Non array join left
        $query = [
            'join' => [
                'join' => 'table1',
                'on' => ['table1.foo' => ['table2.bar']]
            ]
        ];
        $filter = [
            'join' => [[
                'left_join' => 'table2',
                'using' => 'bar',
            ]],
        ];
        Database::filterQuery($query, $filter);
        $this->assertEquals([
            'join' => [
                [
                    'join' => 'table1',
                    'on' => ['table1.foo' => ['table2.bar']]
                ],
                [
                    'left_join' => 'table2',
                    'using' => 'bar',
                ]
            ],
        ], $query);

        // Array join
        $query = [
            'join' => [[
                'join' => 'table1',
                'on' => ['table1.foo' => ['table2.bar']]
            ]]
        ];
        $filter = [
            'join' => [[
                'left_join' => 'table2',
                'using' => 'bar',
            ]],
        ];
        Database::filterQuery($query, $filter);
        $this->assertEquals([
            'join' => [
                [
                    'join' => 'table1',
                    'on' => ['table1.foo' => ['table2.bar']]
                ],
                [
                    'left_join' => 'table2',
                    'using' => 'bar',
                ]
            ],
        ], $query);
    }
}

<?php

namespace Iporm;

use Iporm\Helper;

class Db
{
    private $_con;
    private $_queryType;
    private $_table;
    private $_group;
    private $_groupBy;
    private $_where;
    private $_between;
    private $_innerJoin;
    private $_leftJoin;
    private $_insertKeys;
    private $_insertValues;
    private $_insertOptions;
    private $_setData;
    private $_queryResponse;
    private $_result;
    private $helper;

    /**
     * Db constructor.
     */
    public function __construct()
    {
        $this->_con = Connection::getInstance();
        $this->_innerJoin = '';
        $this->_leftJoin = '';
        $this->_where = '';
        $this->helper = new Helper();
    }

    /**
     * SELECT statement
     *
     * @param string $select
     * @return $this
     */
    public function select($select = '*')
    {
        $this->_group = $select;
        $this->_queryType = 'select';

        return $this;
    }

    /**
     * DELETE statement
     *
     * @return $this
     */
    public function delete()
    {
        $this->_queryType = 'delete';
        return $this;
    }

    /**
     * UPDATE statement
     *
     * @param string $table
     * @param array $dataSet
     * @return $this
     */
    public function update($table, $dataSet = [])
    {
        $this->_queryType = 'update';
        $this->_table = $table;
        $this->setUpdateDataSet($dataSet);

        return $this;
    }

    /**
     * WHERE condition
     *
     * @param array $whereEqualTo
     * @param string $operand
     * @return $this
     */
    public function where($whereEqualTo = [], $operand = '=')
    {
        $this->_where .= $this->setWhere($whereEqualTo, $operand);
        return $this;
    }

    /**
     * WHERE value = A OR value = B
     *
     * @param array $whereEqualTo
     * @return $this
     */
    public function whereOr($whereEqualTo = [])
    {
        $this->_where .= $this->setWhere($whereEqualTo, 'or');
        return $this;
    }

    /**
     * WHERE value IN (..values..)
     *
     * @param array $whereIn
     * @return $this
     */
    public function whereIn($whereIn = [])
    {
        $this->_where .= $this->setWhere($whereIn, 'in');
        return $this;
    }

    /**
     * WHERE value NOT IN (..values..)
     *
     * @param array $whereNotIn
     * @return $this
     */
    public function whereNotIn($whereNotIn = [])
    {
        $this->_where .= $this->setWhere($whereNotIn, 'not in');
        return $this;
    }

    /**
     * INNER JOIN statement
     *
     * @param array $innerJoin
     * @return $this
     */
    public function innerJoin($innerJoin = [])
    {
        $this->setInnerJoin($innerJoin);
        return $this;
    }

    /**
     * LEFT JOIN statement
     *
     * @param array $leftJoin
     * @return $this
     */
    public function leftJoin($leftJoin = [])
    {
        $this->setLeftJoin($leftJoin);
        return $this;
    }

    /**
     * GROUP BY condition
     *
     * @param string $groupBy
     * @return $this
     */
    public function groupBy($groupBy = '')
    {
        $this->setGroupBy($groupBy);
        return $this;
    }

    /**
     * Set table as operation object
     *
     * @param string $table
     * @return $this
     */
    public function from($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * INSERT key and value pairs into table
     *
     * @param string $table
     * @param array $keysAndValues
     * @return $this
     */
    public function insertInto($table, $keysAndValues = [])
    {
        $this->_queryType = 'insert_into';
        $this->_table = $table;

        $insertKeys = [];
        $insertValues = [];

        if($this->helper->isIterable($keysAndValues)) {
            foreach($keysAndValues as $key => $value) {
                $insertKeys[] = $key;

                if(is_null($value)) {
                    $insertValues[] = 'NULL';
                } elseif(is_int($key)) {
                    $insertValues[] = $value;
                } elseif(is_array($value)) {
                    foreach($value as $k => $v) {
                        $insertValues[] = mysqli_real_escape_string($this->_con, $v);
                    }
                } else {
                    $insertValues[] = mysqli_real_escape_string($this->_con, $value);
                }
            }
        }
        
        $this->_insertKeys = $insertKeys;
        $this->_insertValues = $insertValues;

        return $this;
    }

    /**
     * Run custom or preselected query
     *
     * @param bool $queryType
     * @param bool $customQuery
     * @return bool|int
     */
    public function run($queryType = false, $customQuery = false)
    {
        if(!$customQuery) {
            return $this->runQuery();
        } else {
            $this->$this->_queryType = $queryType;
            $query = $this->getCurrentQuery();

            $this->_queryResponse = mysqli_query($this->_con, $query);
        }
    }

    /**
     * Run helper
     *
     * @return bool|int
     */
    private function runQuery()
    {
        switch($this->_queryType) {
            case 'delete':
                $query = $this->getDeleteQuery();
                $this->_queryResponse = mysqli_query($this->_con, $query);
                return true;
            break;

            case 'insert_into':
                $query = $this->getInsertQuery();
                $this->_queryResponse = mysqli_query($this->_con, $query);
                return true;
            break;

            case 'select':
                $query = $this->getSelectQuery();
                $this->_queryResponse = mysqli_query($this->_con, $query);
                if($this->_queryResponse) {
                    $this->_result = $this->getResults();
                }
                if($this->_result && $this->_result > 0) {
                    return $this->_result;
                }
                return false;
            break;

            case 'update':
                $query = $this->getUpdateQuery();
                $this->_queryResponse = mysqli_query($this->_con, $query);
            break;

            default:
                return false;
            break;
        }
    }

    /**
     * Prints active query
     *
     * @return string
     */
    public function show()
    {
        echo $this->getCurrentQuery();
    }

    /**
     * Active query helper
     *
     * @return string
     */
    private function getCurrentQuery()
    {
        switch($this->_queryType) {
            case 'delete':
                $query = $this->getDeleteQuery();
                break;

            case 'insert_into':
                $query = $this->getInsertQuery();
                break;

            case 'select':
                $query = $this->getSelectQuery();
                break;

            case 'update':
                $query = $this->getUpdateQuery();
                break;

            default:
                $query = 'No valid query detected.';
                break;
        }

        return $query;
    }

    /**
     * Forms SELECT query
     *
     * @return string
     */
    private function getSelectQuery()
    {
        $query = 'SELECT ' . $this->_group . "\n\t";
        $query .= ' FROM ' . $this->_table . "\n\t";

        if($this->_innerJoin) {
            $query .= $this->_innerJoin . "\n\t";
        }

        if($this->_leftJoin) {
            $query .= $this->_leftJoin . "\n\t";
        }

        if($this->_groupBy) {
            $query .= $this->_groupBy . "\n\t";
        }

        $query .= $this->_where . "\n\t";

        if($this->_between) {
            $query .= $this->_between . "\n\t";
        }

        return $query;
    }

    /**
     * Forms INSERT query
     *
     * @return string
     */
    private function getInsertQuery()
    {
        // insert options currently just for scalability
        return 'INSERT ' . (empty($this->_insertOptions) ? '' : $this->_insertOptions . ' ') . 'INTO ' 
                . $this->_table . ' (' . implode(',' . "\n\t", $this->_insertKeys) . ')' .
                ' VALUES (' . "\n\t" .
                implode(',' . "\n\t", $this->_insertValues) . "\n" .
                ')' . "\n" .
                '';
    }

    /**
     * Forms DELETE Query
     *
     * @return string
     */
    private function getDeleteQuery()
    {
        $query = 'DELETE ' . "\n\t";
        $query .= ' FROM ' . $this->_table . "\n\t";
        $query .= $this->_where . "\n\t";

        return $query;
    }

    /**
     * Forms UPDATE query
     *
     * @return string
     */
    private function getUpdateQuery()
    {
        $query = 'UPDATE ' . "\n\t";
        $query .= $this->_table . "\n\t";
        $query .= " SET " . "\n\t";
        $query .= $this->_setData . "\n\t";
        $query .= $this->_where;

        return $query;
    }

    /**
     * Sets internal WHERE
     *
     * @param array $whereEqualTo
     * @param string $operand
     * @return string
     */
    private function setWhere($whereEqualTo, $operand)
    {
        $operand = $this->helper->validateOperand($operand);

        if($this->helper->isIterable($whereEqualTo)) {

            if($operand == 'or') {
                return $this->setEqualToOr($whereEqualTo);
            }

            if($operand == 'in') {
                return $this->setIn($whereEqualTo);
            }

            if($operand == 'not in') {
                return $this->setNotIn($whereEqualTo);
            }

            return $this->setEqualTo($whereEqualTo, $operand);
        }

        return '';
    }

    /**
     * Sets internal equal TO
     *
     * @param array $whereEqualTo
     * @param string $operand
     * @return string
     */
    private function setEqualTo($whereEqualTo, $operand)
    {
        $wheres = [];
        if($this->helper->isIterable($whereEqualTo)) {
            foreach($whereEqualTo as $key => $value) {

                if(is_null($value)) {
                    $wheres[] = $key . ' IS NULL';
                }

                if(is_int($key)) {
                    $wheres[] = $value;
                }

                if(is_int($value)) {
                    $wheres[] = $key . ' ' . $operand . ' ' . mysqli_real_escape_string($this->_con, $value);
                }

                if(is_array($value)) {
                    foreach ($value as $k => $v) {
                        $wheres[] = $key . ' ' .$operand . ' ' . mysqli_real_escape_string($this->_con, $v);
                    }
                }

                if(is_string($value)) {
                    $wheres[] = $key . ' ' . $operand . ' ' . mysqli_real_escape_string($this->_con, $value);
                }
            }
        }

        if($this->_where !== '') {
            if(count($wheres) == 1) {
                return "\n\t AND " . $wheres[0];
            }

            return "\n\t" . implode(' AND' . "\n\t", $wheres);
        }

        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    /**
     * Sets internal equal TO OR
     *
     * @param array $whereEqualTo
     * @return string
     */
    private function setEqualToOr($whereEqualTo)
    {
        $wheres = [];
        if($this->helper->isIterable($whereEqualTo)) {
            foreach ($whereEqualTo as $k => $v) {
                if (is_null($v)) {
                    $wheres[] = $k . ' IS NULL';
                } elseif (is_int($k)) {
                    $wheres[] = $v;
                } elseif (is_array($v)) {
                    foreach ($v as $key => $value) {
                        if (is_null($value)) {
                            $wheres[] = $k . ' IS NULL';
                        } elseif (is_int($k)) {
                            $wheres[] = $value;
                        } else {
                            $wheres[] = $k . ' = "' . mysqli_real_escape_string($this->_con, $value) . '"';
                        }
                    }
                } else {
                    $wheres[] = $k . ' = "' . mysqli_real_escape_string($this->_con, $v) . '"';
                }
            }
        }

        if($this->_where !== '') {
            return " AND (\n\t" . implode(' OR' . "\n\t", $wheres) . "\n\t)";
        }

        return " WHERE (\n\t" . implode(' OR'  . "\n\t", $wheres) . "\n\t)";
    }

    /**
     * Sets internal IN
     *
     * @param array $whereIn
     * @return string
     */
    private function setIn($whereIn)
    {
        $wheres = [];
        if($this->helper->isIterable($whereIn)) {
            foreach ($whereIn as $k => $v) {
                if (is_null($v)) {
                    $wheres[] = $k . ' IS NULL';
                } elseif (is_int($k)) {
                    $wheres[] = $v;
                } elseif (is_int($v)) {
                    $wheres[] = $k . ' IN (' . mysqli_real_escape_string($this->_con, $v) . ')';
                } elseif (is_array($v)) {
                    $values = [];
                    foreach ($v as $value) {
                        $values[] = '"' . mysqli_real_escape_string($this->_con, $value) . '"';
                    }
                    $wheres[] = $k . ' IN (' . implode(', ', $values) . ')';
                } else {
                    $wheres[] = $k . ' IN (' . mysqli_real_escape_string($this->_con, $v) . ')';
                }
            }
        }

        if($this->_where !== '') {
            if(count($wheres) == 1) {
                return "\n\t AND " . $wheres[0];
            }

            return "\n\t" . implode(" AND \n\t", $wheres);
        }

        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    /**
     * Set internal NOT IN
     *
     * @param array $whereNotIn
     * @return string
     */
    private function setNotIn($whereNotIn)
    {
        $wheres = [];
        foreach ($whereNotIn as $k => $v) {
            if (is_null($v)) {
                $wheres[] = $k . ' IS NULL';
            } elseif (is_int($k)) {
                $wheres[] = $v;
            } elseif (is_int($v)) {
                $wheres[] = $k . ' NOT IN (' . mysqli_real_escape_string($this->_con, $v) . ')';
            } elseif (is_array($v)) {
                $values = [];
                foreach ($v as $value) {
                    $values[] = '"' . mysqli_real_escape_string($this->_con, $value) . '"';
                }
                $wheres[] = $k . ' NOT IN (' . implode(', ', $values) . ')';
            } else {
                $wheres[] = $k . ' NOT IN (' . mysqli_real_escape_string($this->_con, $v) .')';
            }
        }

        if($this->_where !== '') {
            if(count($wheres) == 1) {
                return "\n\t AND " . $wheres[0];
            }

            return "\n\t" . implode(" AND \n\t", $wheres);
        }

        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    /**
     * Sets internal INNER JOIN
     *
     * @param array $innerJoin
     * @return void
     */
    private function setInnerJoin($innerJoin)
    {
        if($this->helper->isIterable($innerJoin)) {
            foreach ($innerJoin as $join) {
                $this->_innerJoin .= ' INNER JOIN ' . $join . "\n\t";
            }
        }
    }

    /**
     * Sets internal LEFT JOIN
     *
     * @param array $leftJoin
     * @return void
     */
    private function setLeftJoin($leftJoin)
    {
        if($this->helper->isIterable($leftJoin)) {
            foreach ($leftJoin as $join) {
                $this->_leftJoin .= ' JOIN ' . $join . "\n\t";
            }
        }
    }

    /**
     * Sets internal GROUP BY
     *
     * @param array $groupBy
     * @return void
     */
    private function setGroupBy($groupBy)
    {
        if($groupBy) {
            $this->_groupBy = 'GROUP BY' . "\n\t" . $groupBy. "\n";
        }
    }

    /**
     * Sets internal UPDATE data set
     *
     * @param array $dataSet
     * @return void
     */
    private function setUpdateDataSet($dataSet)
    {
        if($this->helper->isIterable($dataSet)) {
            $this->_setData = '';
            $update = [];

            foreach ($dataSet as $k => $v) {
                if (is_numeric($k)) {
                    if (!$v) {
                        continue;
                    }
                    $update[] = mysqli_real_escape_string($this->_con, $v) . ' = VALUES (' . mysqli_real_escape_string($this->_con, $v) . ')';
                } else {
                    if (is_null($v)) {
                        $update[] = $k . ' = NULL';
                    } elseif (is_int($k)) {
                        $update[] = $v;
                    } elseif (is_array($v)) {
                        foreach ($v as $key => $value) {
                            if (is_null($value)) {
                                $update[] = $k . ' = NULL';
                            } elseif (is_int($k)) {
                                $update[] = $value;
                            } else {
                                $update[] = $k . ' = "' . mysqli_real_escape_string($this->_con, $value) . '"';
                            }
                        }
                    } else {
                        $update[] = $k . ' = "' . mysqli_real_escape_string($this->_con, $v) . '"';
                    }
                }
            }

            if (count($update)) {
                $this->_setData = "\t" . implode(',' . "\n\t", $update) . "\n";
            }
        }
    }

    /**
     * Returns SELECT statement data set
     *
     * @return array
     */
    public function getSelected()
    {
        $result = [];
        $i = 0;
        
        while($i < $this->_result) {
            $result[] = mysqli_fetch_assoc($this->_queryResponse);
            $i++;
        }

        return $result;
    }

    /**
     * Returns number of result rows
     *
     * @return int
     */
    public function getResults()
    {
        return mysqli_num_rows($this->_queryResponse);
    }

    /**
     * Id of inserted row
     *
     * @return int|string
     */
    public function getInsertedId()
    {
        return mysqli_insert_id($this->_con);
    }

    /**
     * Number of affected rows
     *
     * @return int
     */
    public function getAffected()
    {
        return mysqli_affected_rows($this->_con);
    }
}

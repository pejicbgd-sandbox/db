<?php

namespace Iporm;

use Helper;

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


    public function __construct()
    {
        $this->_con = Connection::getInstance();
        $this->_innerJoin = '';
        $this->_leftJoin = '';
        $this->_where = '';
        $this->helper = new Helper();
    }

    public function select($select = '*')
    {
        $this->_group = $select;
        $this->_queryType = 'select';
        return $this;
    }

    public function delete()
    {
        $this->_queryType = 'delete';
        return $this;
    }

    public function update($table, $dataSet = [])
    {
        try {
            $helper->validate($table, 'string');
        } catch(InvalidTypeException $e) {
            echo $e->getMessageText();
        }

        $this->_queryType = 'update';
        $this->_table = $table;
        $this->setUpdateDataSet($dataSet);
        return $this;
    }

    public function where($whereEqualTo, $operand = '=')
    {
        try {
            $helper->validate($whereEqualTo, 'array');
        } catch(InvalidTypeException $e) {
            echo $e->getMessageText();
        }

        $this->_where .= $this->setWhere($whereEqualTo, $operand);
        return $this;
    }

    public function whereOr($whereEqualTo = [])
    {
        $this->_where .= $this->setWhere($whereEqualTo, 'or');
        return $this;
    }

    public function whereIn($whereIn = [])
    {
        $this->_where .= $this->setWhere($whereIn, 'in');
        return $this;
    }

    public function whereNotIn($whereNotIn = [])
    {
        $this->_where .= $this->setWhere($whereNotIn, 'not in');
        return $this;
    }

    public function innerJoin($innerJoin = [])
    {
        $this->setInnerJoin($innerJoin);
        return $this;
    }

    public function leftJoin($leftJoin = [])
    {
        $this->setLeftJoin($leftJoin);
        return $this;
    }

    public function groupBy($groupBy = '')
    {
        $this->setGroupBy($groupBy);
        return $this;
    }

    public function from($table)
    {
        try {
            $helper->validateScalar($table, 'string');
        } catch(InvalidTypeException $e) {
            echo $e->getMessageText();
        }

        $this->_table = $table;
        return $this;
    }

    public function insertInto($table, $keysAndValues)
    {
        $this->_queryType = 'insert_into';
        $this->_table = $table;
        $insertValues = [];

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

        $this->_insertKeys = $insertKeys;
        $this->_insertValues = $insertValues;

        return $this;
    }

    // TODO can parameters order be reversed here, and should it be?
    public function run($customQuery = false, $queryType = false)
    {
        if($customQuery && !in_array($queryType, $this->getValidQueryTypes())) {
            // TODO Exception handling here 
            $queryType = 'select';
        }

        if(!$customQuery) {
            return $this->runQuery();
        }
        
        //return self::run_custom_query($customQuery);
    }

    public function getSelected()
    {
        $result = [];
        $i = 0;

        // TODO exception handling is $this->_result set here
        while($i < $this->_result) {
            $result[] = mysqli_fetch_assoc($this->_queryResponse);
            $i++;
        }

        return $result;
    }

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

    public function show()
    {
        switch($this->_queryType)
        {
            case 'delete':
                echo $this->getDeleteQuery();
                break;

            case 'insert_into':
                echo $this->getInsertQuery();
                break;

            case 'select':
                echo $this->getSelectQuery();
                break;

            case 'update':
                echo $this->getUpdateQuery();
                break;

            default:
                echo 'No valid query detected.';
                break;
        }
    }

/*--------- GET SELECTED FORMATED QUERIES ------------*/

    private function getSelectQuery()
    {
        $query = 'SELECT ' . $this->_group . "\n\t";
        $query .= ' FROM ' . $this->_table . "\n\t";

        if($this->_innerJoin)
        {
            $query .= $this->_innerJoin . "\n\t";
        }

        if($this->_leftJoin)
        {
            $query .= $this->_leftJoin . "\n\t";
        }

        if($this->_groupBy)
        {
            $query .= $this->_groupBy . "\n\t";
        }

        $query .= $this->_where . "\n\t";

        if($this->_between)
        {
            $query .= $this->_between . "\n\t";
        }

        return $query;
    }

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

    private function getDeleteQuery()
    {
        $query = 'DELETE ' . "\n\t";
        $query .= ' FROM ' . $this->_table . "\n\t";
        $query .= $this->_where . "\n\t";

        return $query;
    }

    private function getUpdateQuery()
    {
        $query = 'UPDATE ' . "\n\t";
        $query .= $this->_table . "\n\t";
        $query .= " SET " . "\n\t";
        $query .= $this->_setData . "\n\t";
        $query .= $this->_where;

        return $query;
    }

    private function setWhere($whereEqualTo, $operand)
    {
        // Insted default value, Exception handler
        $operand = $this->validateOperand($operand);

        $wheres = [];
        if($this->isIterable($whereEqualTo)) {

            if($operand == 'between') {
                return $this->setBetween($whereEqualTo);
            }

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

    private function setEqualTo($whereEqualTo, $operand)
    {
        $wheres = [];
        if($this->isIterable($whereEqualTo)) {
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

    public function setEqualToOr($where_equal_to)
    {
        $wheres = [];
        foreach ($where_equal_to as $k => $v) {

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
                        $wheres[] = sprintf($k . ' = "%s"', mysqli_real_escape_string($this->_con, $value));
                    }
                }
            } else {
                $wheres[] = sprintf($k . ' = "%s"', mysqli_real_escape_string($this->_con, $v));
            }
        }

        if($this->_where !== '') {
            return " AND (\n\t" . implode(' OR' . "\n\t", $wheres) . "\n\t)";
        }

        return " WHERE (\n\t" . implode(' OR'  . "\n\t", $wheres) . "\n\t)";
    }

    private function setBetween($whereEqualTo)
    {
        $parameter = $whereEqualTo[0];
        $value1 = $whereEqualTo[1][0];
        $value2 = $whereEqualTo[1][1];

        if(!is_string($whereEqualTo[0]) || !is_array($whereEqualTo[1])) {
            //throw new InvalidValueFormatException();
        }

        $wheres[] = sprintf(' %s' . ' BETWEEN ' . $value1 . ' AND ' . $value2, mysqli_real_escape_string($this->_con, $parameter));

        if($this->_where !== '') {
            return "\n\t" . implode(" AND \n\t", $wheres);
        }

        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    public function setIn($whereIn)
    {
        $wheres = [];
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

        if($this->_where !== '') {
            if(count($wheres) == 1) {
                return "\n\t AND " . $wheres[0];
            }

            return "\n\t" . implode(" AND \n\t", $wheres);
        }

        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    public function setNotIn($whereNotIn)
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


    private function setInnerJoin($innerJoin)
    {
        if($this->isIterable($innerJoin))
        {
            foreach ($innerJoin as $join)
            {
                $this->_innerJoin .= ' INNER JOIN ' . $join . "\n\t";
            }
        }
    }

    private function setLeftJoin($leftJoin)
    {
        if($this->isIterable($leftJoin))
        {
            foreach ($leftJoin as $join)
            {
                $this->_leftJoin .= ' JOIN ' . $join . "\n\t";
            }
        }
    }

    private function setGroupBy($groupBy)
    {
        if($groupBy)
        {
            $this->_groupBy = 'GROUP BY' . "\n\t" . $groupBy. "\n";
        }
    }

    public function setUpdateDataSet($dataSet)
    {
        if($this->isIterable($dataSet))
        {
            $this->_setData = '';
            $update = [];

            foreach ($dataSet as $k => $v) {
                //TODO neka automatska provera tipova, dinamicki unos koji je tip trazen ("is_numeric")
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

/*--------- RESULT GETTERS BASED ON QUERY TYPE  ------------*/

    public function getResults()
    {
        return mysqli_num_rows($this->_queryResponse);
    }

    public function getInsertedId()
    {
        return mysqli_insert_id($this->_con);
    }

    public function getAffected()
    {
        return mysqli_affected_rows($this->_con);
    }

/*--------- VARIOUS HELPERS  ------------*/

    private function getValidQueryTypes()
    {
        return ['delete','insert_into','select','update'];
    }

    private function getValidOperands()
    {
        return ['=', '!=', '<', '>', 'between', 'in', 'not in', 'or'];
    }

    private function validateOperand($operand)
    {
        $return = $operand;
        if(!in_array($operand, $this->getValidOperands()))
        {
            $return = '=';
        }

        return $return;
    }

}

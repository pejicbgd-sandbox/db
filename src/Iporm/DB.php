<?php

namespace Iporm;

class DB
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

    public function __construct()
    {
        $this->_con = Connection::getInstance();
        $this->_innerJoin = '';
        $this->_leftJoin = '';
        $this->_where = '';
    }

    public function select($select = '*')
    {
        $this->_group = $select;
        $this->_queryType = 'select';
        return $this;
    }

    /**
    *
    */
    public function from($table)
    {
        $this->_table = $table;
        return $this;
    }

    public function insertInto($table, $keys_and_values)
    {
        $this->_queryType = 'insert_into';
        $this->_table = $table;
        $insertValues = [];

        foreach($keys_and_values as $key => $value)
        {
            $insertKeys[] = $key;
            if(is_null($value))
            {
                $insertValues[] = 'NULL';
            }
            elseif(is_int($key))
            {
                $insertValues[] = $value;
            }
            elseif(is_array($value))
            {
                foreach ($value as $k => $v)
                {
                    $insertValues[] = sprintf('%s', mysqli_real_escape_string($this->_con, $v));
                }
            }
            else
            {
                $insertValues[] = sprintf('"%s"', mysqli_real_escape_string($this->_con, $value));
            }
        }

        $this->_insertKeys = $insertKeys;
        $this->_insertValues = $insertValues;

        return $this;
    }

    public function delete()
    {
        $this->_queryType = 'delete';
        return $this;
    }

    public function update($table, $data_set = [])
    {
        $this->_queryType = 'update';
        $this->_table = $table;
        $this->setUpdateDataSet($data_set);
        return $this;
    }

    public function where($where_equal_to = [], $operand = '=')
    {
        $this->_where .= $this->setWhere($where_equal_to, $operand);
        return $this;
    }

    public function whereOr($where_equal_to = [])
    {
        $this->_where .= $this->setWhere($where_equal_to, 'or');
        return $this;
    }

    public function whereIn($where_in = [])
    {
        $this->_where .= $this->setWhere($where_in, 'in');
        return $this;
    }

    public function whereNotIn($where_not_in = [])
    {
        $this->_where .= $this->setWhere($where_not_in, 'not in');
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

    public function run($custom_query = false, $query_type = false)
    {
        $valid_query_types = $this->getValidQueryTypes();
        if($custom_query && !in_array($query_type, $valid_query_types))
        {
            $query_type = 'select';
        }

        if(!$custom_query)
        {
            return $this->runQuery();
        }
        else
        {
            //return self::run_custom_query($custom_query);
        }
    }

    public function getSelected()
    {
        $result = [];
        $i = 0;
        while( $i < $this->_result)
        {
            $result[] = mysqli_fetch_assoc($this->_queryResponse);
            $i++;
        }
        return $result;
    }

    private function runQuery()
    {
        switch($this->_queryType)
        {
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
                if($this->_queryResponse)
                {
                    $this->_result = $this->getResults();
                }
                if($this->_result && $this->_result > 0)
                {
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
                echo '';
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
        return 'INSERT ' . (empty($this->_insertOptions) ? '' : $this->_insertOptions . ' ') . 'INTO ' . $this->_table .
                ' (' . implode(',' . "\n\t", $this->_insertKeys) . ')' .
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

    private function setWhere($where_equal_to, $operand)
    {
        $operand = $this->validateOperand($operand);

        $wheres = [];
        if($this->isIterable($where_equal_to))
        {
            if($operand == 'between')
            {
                return $this->setBetween($where_equal_to);
            }

            if($operand == 'or')
            {
                return $this->setEqualToOr($where_equal_to);
            }

            if($operand == 'in')
            {
                return $this->setIn($where_equal_to);
            }

            if($operand == 'not in')
            {
                return $this->setNotIn($where_equal_to);
            }

            return $this->setEqualTo($where_equal_to, $operand);
        }
        return '';
    }

    private function setEqualTo($where_equal_to, $operand)
    {
        if($this->isIterable($where_equal_to))
        {
            foreach($where_equal_to as $key => $value)
            {
                if(is_null($value))
                {
                    $wheres[] = $key . ' IS NULL';
                }

                if(is_int($key))
                {
                    $wheres[] = $value;
                }

                if(is_int($value))
                {
                    $wheres[] = sprintf($key . ' ' . $operand . ' %s', mysqli_real_escape_string($this->_con, $value));
                }

                if(is_array($value))
                {
                    foreach ($value as $k => $v)
                    {
                        $wheres[] = sprintf($key . ' ' .$operand . ' "%s"', mysqli_real_escape_string($this->_con, $v));
                    }
                }

                if(is_string($value))
                {
                    $wheres[] = sprintf($key . ' ' . $operand . ' "%s"', mysqli_real_escape_string($this->_con, $value));
                }
            }
        }

        if($this->_where !== '')
        {
            if(count($wheres) == 1)
            {
                return "\n\t AND " . $wheres[0];
            }
            return "\n\t" . implode(' AND' . "\n\t", $wheres);
        }
        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    public function setEqualToOr($where_equal_to)
    {
        $wheres = [];
        foreach ($where_equal_to as $k => $v)
        {
            if (is_null($v))
            {
                $wheres[] = $k . ' IS NULL';
            }
            elseif (is_int($k))
            {
                $wheres[] = $v;
            }
            elseif (is_array($v))
            {
                foreach ($v as $key => $value)
                {
                    if (is_null($value))
                    {
                        $wheres[] = $k . ' IS NULL';
                    }
                    elseif (is_int($k))
                    {
                        $wheres[] = $value;
                    }
                    else
                    {
                        $wheres[] = sprintf($k . ' = "%s"', mysqli_real_escape_string($this->_con, $value));
                    }
                }
            }
            else
            {
                $wheres[] = sprintf($k . ' = "%s"', mysqli_real_escape_string($this->_con, $v));
            }
        }

        if($this->_where !== '')
        {
            return " AND (\n\t" . implode(' OR' . "\n\t", $wheres) . "\n\t)";
        }
        return " WHERE (\n\t" . implode(' OR'  . "\n\t", $wheres) . "\n\t)";
    }

    private function setBetween($where_equal_to)
    {
        $parameter = $where_equal_to[0];
        $value1 = $where_equal_to[1][0];
        $value2 = $where_equal_to[1][1];

        if(!is_string($where_equal_to[0]) || !is_array($where_equal_to[1]))
        {
            //throw new InvalidValueFormatException();
        }

        $wheres[] = sprintf(' %s' . ' BETWEEN ' . $value1 . ' AND ' . $value2, mysqli_real_escape_string($this->_con, $parameter));

        if($this->_where !== '')
        {
            return "\n\t" . implode(" AND \n\t", $wheres);
        }
        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    public function setIn($where_in)
    {
        $wheres = [];
        foreach ($where_in as $k => $v)
        {
            if (is_null($v))
            {
                $wheres[] = $k . ' IS NULL';
            }
            elseif (is_int($k))
            {
                $wheres[] = $v;
            }
            elseif (is_int($v))
            {
                $wheres[] = sprintf($k . ' IN (%s)', mysqli_real_escape_string($this->_con, $v));
            }
            elseif (is_array($v))
            {
                $values = array();
                foreach ($v as $value)
                {
                    $values[] = '"' . mysqli_real_escape_string($this->_con, $value) . '"';
                }
                $wheres[] = sprintf($k . ' IN (%s)', implode(', ', $values));
            }
            else
            {
                $wheres[] = sprintf($k . ' IN (%s)', mysqli_real_escape_string($this->_con, $v));
            }
        }

        if($this->_where !== '')
        {
            if(count($wheres) == 1)
            {
                return "\n\t AND " . $wheres[0];
            }
            return "\n\t" . implode(" AND \n\t", $wheres);
        }
        return " WHERE \n\t" . implode(" AND \n\t", $wheres);
    }

    public function setNotIn($where_not_in)
    {
        $wheres = [];
        foreach ($where_not_in as $k => $v)
        {
            if (is_null($v))
            {
                $wheres[] = $k . ' IS NULL';
            }
            elseif (is_int($k))
            {
                $wheres[] = $v;
            }
            elseif (is_int($v))
            {
                $wheres[] = sprintf($k . ' NOT IN (%s)', mysqli_real_escape_string($this->_con, $v));
            }
            elseif (is_array($v))
            {
                $values = array();
                foreach ($v as $value)
                {
                    $values[] = '"' . mysqli_real_escape_string($this->_con, $value) . '"';
                }
                $wheres[] = sprintf($k . ' NOT IN (%s)', implode(', ', $values));
            }
            else
            {
                $wheres[] = sprintf($k . ' NOT IN (%s)', mysqli_real_escape_string($this->_con, $v));
            }
        }

        if($this->_where !== '')
        {
            if(count($wheres) == 1)
            {
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

    private function setGroupBy($group_by)
    {
        if($group_by)
        {
            $this->_groupBy = 'GROUP BY' . "\n\t" . $group_by. "\n";
        }
    }

    public function setUpdateDataSet($data_set)
    {
        if($this->isIterable($data_set))
        {
            $this->_setData = '';
            $update = array();
            foreach ($data_set as $k => $v)
            {
                if (is_numeric($k))
                {
                    if (!$v)
                    {
                        continue;
                    }

                    $update[] = mysqli_real_escape_string($this->_con, $v) . ' = VALUES(' . mysqli_real_escape_string($this->_con, $v) . ')';
                }
                else
                {
                    if (is_null($v))
                    {
                        $update[] = $k . ' = NULL';
                    }
                    elseif (is_int($k))
                    {
                        $update[] = $v;
                    }
                    elseif (is_array($v))
                    {
                        foreach ($v as $key => $value)
                        {
                            if (is_null($value))
                            {
                                $update[] = $k . ' = NULL';
                            }
                            elseif (is_int($k))
                            {
                                $update[] = $value;
                            }
                            else
                            {
                                $update[] = sprintf($k . ' = "%s"', mysqli_real_escape_string($this->_con, $value));
                            }
                        }
                    }
                    else
                    {
                        $update[] = sprintf($k . ' = "%s"', mysqli_real_escape_string($this->_con, $v));
                    }
                }
            }

            if (count($update))
            {
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
        //TODO prebaci ovo u odvojenu get metodu
        $valid_operands = $this->getValidOperands();
        if(!in_array($operand, $valid_operands))
        {
            $return = '=';
        }
        return $return;
    }

    public function isIterable($where_equal_to)
    {
        return (is_array($where_equal_to) && count($where_equal_to));
    }
}

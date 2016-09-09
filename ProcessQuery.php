<?php

class ProcessQuery extends DB_Connector
{
    private $sql_file;
    private $sql_type;
    private $inject_vars;

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param   string  $query  The sql query with parameter placeholders
     * @param    array  $params The array of substitution parameters
     * @return  string          The interpolated query
     */
    private function interpolateQuery($query, $params)
    {
        if (empty($params))
        {
            return (string) $query;
        }

        $keys   = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value)
        {
            if (is_string($key))
            {
                $keys[] = '/:'.$key.'/';
            }
            else
            {
                $keys[] = '/[?]/';
            }

            if (is_array($value))
            {
                $values[$key] = implode(',', $value);
            }

            if (is_null($value))
            {
                $values[$key] = 'NULL';
            }
        }

        // Walk the array to see if we can add single-quotes to strings
        array_walk($values, create_function('&$v, $k', 'if (!is_numeric($v) && $v!="NULL") $v = "\'".$v."\'";'));

        return (string) preg_replace($keys, $values, $query, 1, $count);
    }

    // replace any non-ascii character with its hex code - supports multi-byte characters
    private function escape($value)
    {
        $return = '';

        for ($i = 0; $i < strlen($value); ++$i)
        {
            $char = $value[$i];
            $ord  = ord($char);

            if ($char !== "'" && $char !== "\"" && $char !== '\\' && $ord >= 32 && $ord <= 126)
            {
                $return .= $char;
            }
            else
            {
                $return .= '\\x' . dechex($ord);
            }

        }

        return (string) $return;
    }

    public function __construct($sql_file, $sql_type)
    {
        $this->sql_file = $sql_file;
        $this->sql_type = $sql_type;
    }

    public function inject($parameters)
    {
        $this->inject_vars = $parameters;

        return $this;
    }

    public function prepare($parameters)
    {
        try
        {
            // checking and establish a live db connector
            if (empty($this->dbh))
            {
                self::$db = $this->connect();
            }

            if ($this->sql_type == 'select')
            {
                $query = Stash::getQuery($this->sql_file, $this->sql_type);

                if (!empty($this->inject_vars))
                {
                    foreach ($this->inject_vars as $var => $value)
                    {
                        $value = strip_tags(stripslashes($this->escape($value)));
                        $query = str_replace('$'.$var, $value, $query);
                    }
                }

                $stmt = self::$db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

                if (!$stmt)
                {
                    echo "\nPDO::errorInfo():\n";
                    print_r(self::$db->errorInfo());

                    return FALSE;
                }
                else
                {
                    if ($stmt->execute($parameters))
                    {
                        //return $stmt->fetchAll(PDO::FETCH_CLASS);
                        $data = array();

                        foreach (new LastIterator(new DbRowIterator($stmt)) as $row)
                        {
                            $data[] = $row;
                        }

                        return $data;
                    }
                    else
                    {
                        echo "\nPDO::errorInfo():\n";
                        print_r(self::$db->errorInfo());

                        return FALSE;
                    }
                }
            }
            else
            {
                $query = Stash::getQuery($this->sql_file, $this->sql_type);

                if (!empty($this->inject_vars))
                {
                    foreach ($this->inject_vars as $var => $value)
                    {
                        $value = strip_tags(stripslashes($this->escape($value)));
                        $query = str_replace('$'.$var, $value, $query);
                    }
                }

                try
                {
                    self::$db->beginTransaction();
                    $stmt = self::$db->prepare($query);

                    if (!$stmt)
                    {
                        echo "\nPDO::errorInfo():\n";
                        print_r(self::$db->errorInfo());

                        return FALSE;
                    }
                    else
                    {
                        $exec = $stmt->execute($parameters);
                        $stmt->closeCursor();

                        self::$db->commit();

                        if ($this->sql_type = 'insert' && $exec === TRUE)
                        {
                            return self::$db->lastInsertId();
                        }
                        else
                        {
                            return $exec;
                            //return $stmt->rowCount();
                        }
                    }

                }
                catch (PDOException $e)
                {
                    self::$db->rollBack();

                    return $e->getMessage();
                }

            }
        }
        catch (PDOException $exception)
        {
            echo $this->PDOException($exception, self::DISPLAY_TEXT);
        }
    }

    // prints the query string in plain text
    public function text($parameters = FALSE)
    {
        $query = Stash::getQuery($this->sql_file, $this->sql_type);

        if (!empty($this->inject_vars))
        {
            foreach ($this->inject_vars as $var => $value)
            {
                $value = strip_tags(stripslashes($this->escape($value)));;
                $query = str_replace('$'.$var, $value, $query);
            }
        }

        return $this->interpolateQuery($query, $parameters);
    }

    // plain queries without any prepare data
    public function execute()
    {
        try
        {
            // checking and establish a live db connector
            if (empty($this->dbh))
            {
                self::$db = $this->connect();
            }

            $query = Stash::getQuery($this->sql_file, $this->sql_type);

            if (!empty($this->inject_vars))
            {
                foreach ($this->inject_vars as $var => $value)
                {
                    $value = strip_tags(stripslashes($this->escape($value)));
                    $query = str_replace('$'.$var, $value, $query);
                }
            }

            $stmt = self::$db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

            if ($stmt->execute())
            {
                //return $stmt->fetchAll(PDO::FETCH_CLASS);
                $data = array();

                foreach (new LastIterator(new DbRowIterator($stmt)) as $row)
                {
                    $data[] = $row;
                }

                return $data;
            }
        }
        catch (PDOException $exception)
        {
            echo self::PDOException($exception, self::DISPLAY_TEXT);
        }
    }
}

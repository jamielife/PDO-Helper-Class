<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/config.php';
	
class pdoHelper extends PDO
{
    public $dbhandle;

    protected $query;
    protected $result;
    protected $time;
    
    public $debug = false;

    function __construct(){
		    
	    global $config; 
	    $dsn = "mysql:host=".$config['dbHost'].";dbname=".$config['dbName'].";charset=utf8";
        $this->dbhandle = new PDO($dsn, $config['dbUser'], $config['dbPass']);
        $this->dbhandle->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->dbhandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->time = microtime(true);
    }

	/*
	 * Helper MySQL Select Function.
	 *
	 * @param   string  $select - column(s) data to be queried.
	 * @param   string  $from   - table(s) that the column(s) data will be queried from.
	 * @param   array   $where  - (optional) matching clause for query
     *                                       containing at least 1 array('table', 'operator', 'value').
     *                                       example: array(array('color', '=', 'blue'), array('location', '<=', 3)).
     * @param   array   $join   - (optional) additional table to join based on specific matching info
     *                                       containing at least 1 array('tableB', 'tableA.matchingColumn', 'tableB.matchingColumn').
     *                                       example: array(array('planets', 'attributes.name', 'planets.name')).
	 * @return  array/bool      SQL query results or false on empty/error.
     *
	 */
    public function select($select, $from, $where=false, $join=false, $order=false, $caseSensitive = false){
        $this->query = "SELECT {$select} ";
        $this->query .= "FROM {$from} ";

        if($join){
            if(gettype($join) == "array"):
                foreach($join as $newJoin){
                    if(count($newJoin)%3 !== 0):
                        echo 'ERROR: variable "join" takes exactly 3 indices. ';
                        return false;
                    else:
                        $this->query .= "INNER JOIN {$newJoin[0]} ON {$newJoin[1]} = {$newJoin[2]} ";
                    endif;
                }
            else:
                echo 'ERROR: variable "join" should be of type ARRAY. ';
                return false;
            endif;
        }

        $valueArray=null;
        if($where){
            if(gettype($where) == "array"):
                $whereClause = "WHERE ";
                
                if($caseSensitive) $whereClause .= " BINARY ";
                
                if(count($where) == 0):
                    echo 'ERROR: variable "where" takes at least 1 index. ';
                    return false;
                else:
                    foreach ($where as $newWhere){
                      $formatNewWhere = str_replace(".", "_", $newWhere[0]);
                      $whereClause .= "{$newWhere[0]} {$newWhere[1]} :{$formatNewWhere} AND ";
                      $valueArray[":{$formatNewWhere}"] = $newWhere[2];
                    }
                endif;
                $this->query .= substr_replace($whereClause, "", -5, strlen(" AND "));
            else:
                echo 'ERROR: variable "where" should be of type ARRAY. ';
                return false;
            endif;
        }

        if($order){
            if(gettype($order) == "array"):
                if(count($order)== 2):
                    $this->query .= " ORDER BY {$order[0]} $order[1] ";
                else:
                    echo 'ERROR: variable "order" should be of length: 2. ';
                endif;
            else:
                echo 'ERROR: variable "order" should be of type ARRAY. ';
            endif;
        }
        //echo $this->query;

        $stmt = $this->dbhandle->prepare($this->query);
        $stmt->execute($valueArray);
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt->closeCursor();

        return $results;
    }


	/*
	 * Helper MySQL Insert Function.
	 *
	 * @param   string  $select - column(s) data to be queried.
	 * @return  array/bool      SQL query results or false on empty/error.
     *
	 */

    //array('table_name' => array('column1', 'column2', 'column3', ...)
    public function insert($insertArray){
        $this->query = "INSERT INTO ";
        if($insertArray){
            if(gettype($insertArray) == "array"):
                if(count($insertArray) == 0):
                    return 'ERROR: variable "insertInto" takes at least 1 index. ';
                    return false;
                else:
                    foreach($insertArray as $newTable => $newInsert){
                        $this->query .= $newTable . "(";
                        if(gettype($newInsert) == "array"){
                            $this->query .= implode(",", array_keys($newInsert)) . ") VALUES(";
                            foreach($newInsert as $column => $value){
                                $columnArray[] = ":" . $column;
                                $valueArray[":" . $column] = $value;
                            }
                            $this->query .= implode(",", $columnArray) . ")";
                        }
                    }
                endif;
            else:
                return 'ERROR: variable "insertArray" should be of type ARRAY. ';
                //return false;
            endif;
        }
        try{
            $stmt = $this->dbhandle->prepare($this->query);
            $stmt->execute($valueArray);
            $stmt->closeCursor();
            //printf('<pre>%s</pre><br /><br />', var_export($this->dbhandle->lastInsertId(), true));;
            return $this->dbhandle->lastInsertId();
        } catch(PDOException $e) {
            if($e->errorInfo[1] == 1062) //printf('<pre>%s</pre><br /><br />', var_export($e, true));
            return $e;
            //dump to file
            // maybe build handler function to dump:
                    //echo $this->query;
                    //print_r($valueArray);
            //print_r($e->errorInfo[2]);
            //echo (int)$e->getCode() . " : " . $e->getMessage();
        }



    }

    function update($updateArray, $where){
        $this->query = "UPDATE ";
        if($updateArray){
            if(gettype($updateArray) == "array"):
                if(count($updateArray) == 0):
                    echo 'ERROR: variable "updateArray" takes at least 1 index. ';
                    return false;
                else:
                    foreach($updateArray as $newTable => $newInsert){

                        $this->query .= $newTable . " SET ";

                        if(gettype($newInsert) == "array"){
                            //$this->query .= implode(",", array_keys($newInsert)) . ") VALUES(";
                            foreach($newInsert as $column => $value){
                                $columnArray[] = $column . "=?";
                                $valueArray[] = $value;
                            }
                            $this->query .= implode(",", $columnArray);
                        }
                    }
                endif;
            else:
                echo 'ERROR: variable "updateArray" should be of type ARRAY. ';
                return false;
            endif;

        }
        if($where){
            $this->query .= " WHERE ";
            if(gettype($where) == "array"):
                if(count($where) == 0):
                    echo 'ERROR: variable "where" takes at least 1 index. ';
                    return false;
                elseif(count(array_keys($where)) + count($where) != 2):
                    echo 'ERROR: variable "where" only accepts exactly 1 key => value pair. ';
                    return false;
                else:
                    foreach($where as $column => $value){
                        $this->query .= $column. "=?";
                        array_push($valueArray, $value);
                    }
                endif;
            else:
                echo 'ERROR: variable "where" should be of type ARRAY. ';
                return false;
            endif;
        }
        //echo $this->query;
        $stmt = $this->dbhandle->prepare($this->query);
        $stmt->execute($valueArray);
        $stmt->closeCursor();

        return $stmt->rowCount();
    }

    // deprecated
    public function deleteOLD($from, $where){
        $this->query = "DELETE FROM {$from} ";

        if($where){
            $whereClause = "WHERE ";
            if(gettype($where) == "array"):
                if(count($where) == 0):
                    echo 'ERROR: variable "where" takes at least 1 index. ';
                    return false;
                else:
                    $whereClause .= "{$where[0]} {$where[1]} ";
                    $questionmarks = implode(',',str_split(str_repeat('?',count($where[2]))));
                    $whereClause .= " ($questionmarks)";
                endif;
            else:
                echo 'ERROR: variable "where" should be of type ARRAY. ';
            endif;
            $this->query .= $whereClause;
        }
        echo $this->query;

        $stmt = $this->dbhandle->prepare($this->query);
        //$stmt->execute($where[2]);
        return $stmt->rowCount();
    }

// DELETE FROM table1, table2, table3
// USING table1
//    INNER JOIN table2 USING(data_id)
//    INNER JOIN table3 USING(data_id)
// WHERE table1.data_id = 111

    public function delete($from, $where, $using=null, $join=null){
        $this->query = "DELETE FROM {$from} ";

        if($using) $this->query .= "USING {$using} ";

        if($join){
            if(gettype($join) == "array"):
                foreach($join as $newJoin){
                    if(count($newJoin)%3 !== 0):
                        echo 'ERROR: variable "join" takes exactly 3 indices. ';
                        return false;
                    else:
                        $this->query .= "INNER JOIN {$newJoin[0]} ON {$newJoin[1]} = {$newJoin[2]} ";
                    endif;
                }
            else:
                echo 'ERROR: variable "join" should be of type ARRAY. ';
                return false;
            endif;
        }

        if($where){
            $whereClause = "WHERE ";
            if(gettype($where) == "array"):
                if(count($where) == 0):
                    echo 'ERROR: variable "where" takes at least 1 index. ';
                    return false;
                else:
                    $whereClause .= "{$where[0]} {$where[1]} ";
                    $questionmarks = implode(',',str_split(str_repeat('?',count($where[2]))));
                    $whereClause .= " ($questionmarks)";
                endif;
            else:
                echo 'ERROR: variable "where" should be of type ARRAY. ';
            endif;
            $this->query .= $whereClause;
        }
        if(gettype($where[2]) != "array") $where[2] = array($where[2]);
        
        //echo $this->query;
		$stmt = $this->dbhandle->prepare($this->query);
        $stmt->execute($where[2]);
        return $stmt->rowCount();
    }

    public function table_exists($table){
        try {
            $stmt = $this->dbhandle->query("select 1 from `{$table}`");
            return $stmt->rowCount();
        } catch (PDOException $ex) {
            return false;
        }
    }

    public function debug(){
        echo $this->query . "<br /><br />";
        echo "Memory allowed: " . ini_get('memory_limit') . "<br />";
        //echo "Memory used: " . ceil(memory_get_usage() / 1000000) . "mb<br />";
        echo "Memory used: " . ceil(memory_get_usage() / 1000) . "kb<br />";
        if($this->debug):
            $this->time = microtime(true) - $this->time;
            echo number_format((float)$this->time, 2, '.', '') . ' seconds';
        endif;
    }

    function __destruct() {
        if($this->debug) $this->debug();
        unset($this->dbhandle);
    }
}
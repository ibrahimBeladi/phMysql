<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 Ibrahim BinAlshikh, phMysql library.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace phMysql;
use Exception;
use phMysql\MySQLTable;
/**
 * A base class that is used to construct MySQL queries. It can be used as a base 
 * class for constructing other MySQL queries.
 * @author Ibrahim
 * @version 1.8.9
 */
abstract class MySQLQuery{
    /**
     * The name of database schema that the query will be executed on.
     * @var string 
     * @since 1.8.7
     */
    private $schemaName;
    /**
     * An attribute that is set to true if the query is un update or insert of 
     * blob datatype.
     * @var boolean 
     */
    private $isFileInsert;
    /**
     * Line feed character.
     * @since 1.8.1
     */
    const NL = "\n";
    /**
     * A constant that indicates an error has occurred while executing the query.
     * @var string 
     * @since 1.4 
     */
    const QUERY_ERR = 'query_error';
    /**
     * A constant that indicates a table structure is not linked with the instance.
     * @since 1.8.3
     */
    const NO_STRUCTURE = 'no_struture';
    /**
     * An array that contains the supported MySQL query types.
     * @since 1.1
     */
    const Q_TYPES = array(
        'select','update','delete','insert','show','create','alter','drop'
    );
    /**
     * A constant for the query 'select * from'.
     * @since 1.0
     */
    const SELECT = 'select * from ';
    /**
     * A constant for the query 'insert into'.
     * @since 1.0
     */
    const INSERT = 'insert into ';
    /**
     * A constant for the query 'delete from'.
     * @since 1.0
     */
    const DELETE = 'delete from ';
    /**
     * The query that will be constructed using class methods.
     * @var string 
     * @since 1.0
     */
    private $query;
    /**
     * A string that represents the type of the query such as 'select' or 'update'.
     * @var string 
     * @since 1.0
     */
    private $queryType;
    /**
     * Constructs a query that can be used to get the number of tables in a 
     * schema given its name.
     * The result of executing 
     * the query is a table with one row and one column. The column name will be 
     * 'tables_count' which will contain an integer value that indicates the 
     * number of tables in the schema. If the schema does not exist or has no tables, 
     * the result in the given column will be 0.
     * @param string $schemaName The name of the schema.
     * @since 1.8
     */
    public function schemaTablesCount($schemaName){
        $this->query = 'select count(*) as tables_count from information_schema.tables where TABLE_TYPE = \'BASE TABLE\' and TABLE_SCHEMA = \''.$schemaName.'\';';
        $this->queryType = 'select';
    }
    /**
     * Constructs a query which can be used to update the server's global 
     * variable 'max_allowed_packet'.
     * The value of the attribute is in bytes. The developer might want to 
     * update the value of this variable if he wants to send large data to 
     * database using one query. The maximum value this attribute can have is 
     * 1073741824 bytes.
     * @param int $size The new size.
     * @param string $unit One of 4 values: 'B' for byte, 'KB' for kilobyte, 
     * 'MB' for megabyte and 'GB' for gigabyte. If the given value is none of the 
     * 4, the type will be set to 'MP'.
     */
    public function setMaxPackete($size,$unit='MB'){
        $max = 1073741824;
        $updatedSize = 0;
        $uUnit = strtoupper($unit);
        if($uUnit != 'MB' && $uUnit != 'B' && $uUnit != 'KB' && $uUnit != 'GB'){
            $uUnit = 'MB';
        }
        switch ($uUnit){
            case 'B':{
                $updatedSize = $size < $max && $size > 0 ? $size : $max;
                break;
            }
            case 'KB':{
                $new = $size*1024;
                $updatedSize = $new < $max && $new > 0 ? $new : $max;
                break;
            }
            case 'MB':{
                $new = $size*1024*1024;
                $updatedSize = $new < $max && $new > 0 ? $new : $max;
                break;
            }
            case 'GB':{
                $new = $size*1024*1024*1024;
                $updatedSize = $new < $max && $new > 0 ? $new : $max;
                break;
            }
            default:{
                $updatedSize = $max;
            }
        }
        $this->query = 'set global max_allowed_packet = '.$updatedSize.';';
    }
    /**
     * Constructs a query that can be used to get all tables in a schema given its name.
     * The result of executing the query is a table with one colum. The name 
     * of the column is 'TABLE_NAME'. The column will simply contain all the 
     * names of the tables in the schema. If the given schema does not exist 
     * or has no tables, The result will be an empty table.
     * @param string $schemaName The name of the schema.
     * @since 1.8 
     */
    public function getSchemaTables($schemaName) {
        $this->query = 'select TABLE_NAME from information_schema.tables where TABLE_TYPE = \'BASE TABLE\' and TABLE_SCHEMA = \''.$schemaName.'\'';
        $this->queryType = 'select';
    }
    /**
     * Constructs a query that can be used to get the number of views in a 
     * schema given its name.
     * The result of executing the query is a table with one row and one column.
     *  The column name will be 'views_count' which will contain an integer 
     * value that indicates the number of views in the schema. If the schema 
     * does not exist or has no views, the result in the given column will be 0.
     * @param string $schemaName The name of the schema.
     * @since 1.8
     */
    public function schemaViewsCount($schemaName){
        $this->query = 'select count(*) as views_count from information_schema.tables where TABLE_TYPE = \'VIEW\' and TABLE_SCHEMA = \''.$schemaName.'\';';
        $this->queryType = 'select';
    }
    /**
     * Sets the name of database that the query will be executed on.
     * @param string $name Database schema name. It must be non-empty string.
     * @since 1.8.7
     */
    public function setSchemaName($name) {
        $nameT = trim($name);
        if(strlen($nameT) != 0){
            $this->schemaName = $nameT;
        }
    }
    /**
     * Returns database schema name that the query will be executed on.
     * @return string Database schema name. If not set, the method will 
     * return null.
     * @since 1.8.7
     */
    public function getSchemaName() {
        return $this->schemaName;
    }
    /**
     * Constructs a query that can be used to get the names of all views in a 
     * schema given its name.
     * The result of executing the query is a table with one colum. The name 
     * of the column is 'TABLE_NAME'. The column will simply contain all the 
     * names of the views in the schema. If the given schema does not exist 
     * or has no views, The result will be an empty table.
     * @param string $schemaName The name of the schema.
     * @since 1.8 
     */
    public function getSchemaViews($schemaName) {
        $this->query = 'select TABLE_NAME from information_schema.tables where TABLE_TYPE = \'VIEW\' and TABLE_SCHEMA = \''.$schemaName.'\'';
        $this->queryType = 'select';
    }
    public function __construct() {
        $this->query = self::SELECT.' a_table';
        $this->queryType = 'select';
        $this->setIsBlobInsertOrUpdate(false);
    }
    /**
     * Constructs a query that can be used to alter the properties of a table
     * given its name.
     * @param array $alterOps An array that contains the alter operations.
     * @since 1.4
     */
    public function alter($alterOps){
        $schema = $this->getSchemaName() === null ? '' : $this->getSchemaName().'.';
        $q = 'alter table '.$schema.''.$this->getStructureName().self::NL;
        $count = count($alterOps);
        for($x = 0 ; $x < $count ; $x++){
            if($x + 1 == $count){
                $q .= $alterOps[$x].';'.self::NL;
            }
            else{
                $q .= $alterOps[$x].','.self::NL;
            }
        }
        $this->setQuery($q, 'alter');
    }
    /**
     * Constructs a query that can be used to add a primary key to a table.
     * @param MySQLTable $table The table that will have the primary key.
     * @since 1.8.8
     */
    public function addPrimaryKey($table) {
        if($table instanceof MySQLTable){
            $primaryCount = $table->primaryKeyColsCount();
            if($primaryCount != 0){
                $stm = 'alter table '.$table->getName().' add constraint '.$table->getPrimaryKeyName().' primary key (';
                $index = 0;
                $alterStm = '';
                foreach ($table->getColumns() as $col){
                    if($col->isPrimary()){
                        if($index + 1 == $primaryCount){
                            $stm .= $col->getName().')';
                        }
                        else{
                            $stm .= $col->getName().',';
                        }
                        if($col->isAutoInc()){
                            $alterStm .= 'alter table '.$table->getName().' modify '.$col.' auto_increment;'.self::NL;
                        }
                        $index++;
                    }
                }
                if(strlen($stm) !== 0){
                    $stm .= ';'.MySQLQuery::NL.$alterStm;
                    $this->setQuery($stm, 'alter');
                    return;
                }
                $this->setQuery('', 'alter');
            }
        }
    }
    /**
     * Constructs a query that can be used to alter a table and add a 
     * foreign key to it.
     * @param ForeignKey $key An object of type <b>ForeignKey</b>.
     * @since 1.4
     */
    public function addForeignKey($key){
        $ownerTable = $key->getOwner();
        $sourceTable = $key->getSource();
        if($sourceTable !== null && $ownerTable !== null){
            $query = 'alter table '.$ownerTable->getName()
                    . ' add constraint '.$key->getKeyName().' foreign key (';
            $ownerCols = $key->getOwnerCols();
            $ownerCount = count($ownerCols);
            $i0 = 0;
            foreach ($ownerCols as $col){
                if($i0 + 1 == $ownerCount){
                    $query .= $col->getName().') ';
                }
                else{
                    $query .= $col->getName().', ';
                }
                $i0++;
            }
            $query .= 'references '.$key->getSourceName().'(';
            $sourceCols = $key->getSourceCols();
            $refCount = count($sourceCols);
            $i1 = 0;
            foreach ($sourceCols as $col){
                if($i1 + 1 == $refCount){
                    $query .= $col->getName().') ';
                }
                else{
                    $query .= $col->getName().', ';
                }
                $i1++;
            }
            $onDelete = $key->getOnDelete();
            if($onDelete !== null){
                $query .= 'on delete '.$onDelete.' ';
            }
            $onUpdate = $key->getOnUpdate();
            if($onUpdate !== null){
                $query .= 'on update '.$onUpdate;
            }
        }
        $this->setQuery($query, 'alter');
    }
    /**
     * Constructs a query that can be used to create a new table.
     * @param MySQLTable $table an instance of <b>MySQLTable</b>.
     * @param boolean $inclSqlComments If set to true, a set of comment will appear 
     * in the generated SQL which description what is happening in every SQL Statement.
     * @since 1.4
     */
    private function createTable($table,$inclSqlComments=false){
        if($table instanceof MySQLTable){
            $query = '';
            if($inclSqlComments === true){
                $query .= '-- Structure of the table \''.$this->getStructureName().'\''.self::NL;
                $query .= '-- Number of columns: \''.count($this->getStructure()->columns()).'\''.self::NL;
                $query .= '-- Number of forign keys count: \''.count($this->getStructure()->forignKeys()).'\''.self::NL;
                $query .= '-- Number of primary key columns count: \''.$this->getStructure()->primaryKeyColsCount().'\''.self::NL;
            }
            $query .= 'create table if not exists '.$table->getName().'('.self::NL;
            $keys = $table->colsKeys();
            $count = count($keys);
            for($x = 0 ; $x < $count ; $x++){
                if($x + 1 == $count){
                    $query .= '    '.$table->columns()[$keys[$x]].self::NL;
                }
                else{
                    $query .= '    '.$table->columns()[$keys[$x]].','.self::NL;
                }
            }
            $query .= ')'.self::NL;
            $comment = $table->getComment();
            if($comment !== null){
                $query .= 'comment \''.$comment.'\''.self::NL;
            }
            $query .= 'ENGINE = '.$table->getEngine().self::NL;
            $query .= 'DEFAULT CHARSET = '.$table->getCharSet().self::NL;
            $query .= 'collate = '.$table->getCollation().';'.self::NL;
            $coutPk = $this->getStructure()->primaryKeyColsCount();
            if($coutPk >= 1){
                if($inclSqlComments === true){
                    $query .= '-- Add Primary key to the table.'.self::NL;
                }
                $this->addPrimaryKey($table);
                $q = $this->getQuery();
                if(strlen($q) != 0){
                    //no need to append ';\n' as it was added before.
                    $query .= $q;
                }
            }
            //add forign keys
            $count2 = count($table->forignKeys());
            if($inclSqlComments === true && $count2 != 0){
                $query .= '-- Add Forign keys to the table.'.self::NL;
            }
            for($x = 0 ; $x < $count2 ; $x++){
                $this->addForeignKey($table->forignKeys()[$x]);
                $query .= $this->getQuery().';'.self::NL;
            }
            if($inclSqlComments === true){
                $query .= '-- End of the Structure of the table \''.$this->getStructureName().'\''.self::NL;
            }
            $this->setQuery($query, 'create');
        }
    }
    /**
     * Escape any MySQL special characters from a string.
     * @param string $query The string that the characters will be escaped from.
     * @return string A string with escaped MySQL characters.
     * @since 1.4
     */
    public static function escapeMySQLSpeciarChars($query){
        $escapedQuery = '';
        $query = ''.$query;
        if($query){
            $mysqlSpecial = array(
                "\\","'"
            );
            $mysqlSpecialEsc = array(
                "\\\\","\'"
            );
            $count = count($mysqlSpecial);
            for($i = 0 ; $i < $count ; $i++){
                if($i == 0){
                    $escapedQuery = str_replace($mysqlSpecial[$i], $mysqlSpecialEsc[$i], $query);
                }
                else{
                    $escapedQuery = str_replace($mysqlSpecial[$i], $mysqlSpecialEsc[$i], $escapedQuery);
                }
            }
        }
        return $escapedQuery;
    }
    /**
     * Constructs a query that can be used to show database table engines.
     * The result of executing the query will be a table with the following 
     * columns:
     * <ul>
     * <li>Engine</li>
     * <li>Support</li>
     * <li>Comment</li>
     * <li>Transactions</li>
     * <li>Savepoints</li>
     * </ul>
     * @since 1.8.7
     */
    public function showEngines() {
        $this->show('engines');
    }
    /**
     * Constructs a query that can be used to show some information about something.
     * @param string $toShow The thing that will be shown.
     * @since 1.4
     */
    public function show($toShow){
        $this->setQuery('show '.$toShow.';', 'show');
    }
    /**
     * Returns the value of the property $query.
     * It is simply the query that was constructed by calling any method 
     * of the class.
     * @return string a MySql query.
     * @since 1.0
     */
    public function getQuery(){
        return $this->query;
    }
    /**
     * Returns the type of the query.
     * @return string The type of the query (such as 'select', 'update').
     * @since 1.0
     */
    public function getType(){
        return $this->queryType;
    }
    /**
     * Sets the value of the property $query. 
     * The type of the query must be taken from the array MySQLQuery::Q_TYPES.
     * @param string $query a MySQL query.
     * @param string $type The type of the query (such as 'select', 'update').
     * @since 1.0
     * @throws Exception If the given query type is not supported. 
     */
    public function setQuery($query,$type){
        $ltype = strtolower($type.'');
        if(in_array($ltype, self::Q_TYPES)){
            $this->query = $query;
            $this->queryType = $ltype;
        }
        else{
            throw new Exception('Unsupported query type: \''.$type.'\'');
        }
    }
    /**
     * Constructs a query that can be used to select all columns from a table.
     * @param int $limit The value of the attribute 'limit' of the select statement. 
     * If zero or a negative value is given, it will not be ignored. 
     * Default is -1.
     * @param int $offset The value of the attribute 'offset' of the select statement. 
     * If zero or a negative value is given, it will not be ignored. 
     * Default is -1.
     * @since 1.0
     */
    public function selectAll($limit=-1,$offset=-1){
        $this->select(array(
            'limit'=>$limit,
            'offset'=>$offset
        ));
    }
    /**
     * Constructs a select query which is used to count number of rows on a 
     * table.
     * @param array $options An associative array of options to customize the 
     * select query. Available options are:
     * <ul>
     * <li><b>as</b>: A name for the column that will contain 
     * count result. If not provided, the value 'count' is used as 
     * default value.</li>
     * <li><b>where</b>: An associative array. The indices can 
     * be values and the value at each index is an objects of type 'Column'. 
     * Or the indices can be column indices or columns names taken from MySQLTable object and 
     * the values are set for each index. The second way is recommended as one 
     * table might have two columns with the same values.</li>
     * <li><b>conditions</b>: An array that can contains conditions (=, !=, &lt;, 
     * &lt;=, &gt; or &gt;=). If anything else is given at specific index, '=' will be used. In 
     * addition, If not provided or has invalid value, an array of '=' conditions 
     * is used.</li>
     * <li><b>join-operators</b>: An array that contains a set of MySQL join operators 
     * like 'and' and 'or'. If not provided or has invalid value, 
     * an array of 'and's will be used.</li>
     * </ul>
     * @since 1.8.9
     */
    public function selectCount($options=[]) {
        $asPart = ' as count';
        $where = '';
        if(gettype($options) == 'array'){
            if(isset($options['as'])){
                $trimmedAs = trim($options['as']);
                if(strlen($trimmedAs) != 0){
                    $asPart = ' as '. str_replace(' ', '_', $trimmedAs);
                }
            }
            $options['join-operators'] = isset($options['join-operators']) && 
                    gettype($options['join-operators']) == 'array' ? $options['join-operators'] : [];
            $options['conditions'] = isset($options['conditions']) && 
                    gettype($options['conditions']) == 'array' ? $options['conditions'] : [];
            if(isset($options['where']) && isset($options['conditions'])){
                $cols = [];
                $vals = [];
                foreach($options['where'] as $valOrColIndex => $colOrVal){
                    if($colOrVal instanceof Column){
                        $cols[] = $colOrVal;
                        $vals[] = $valOrColIndex;
                    }
                    else{
                        if(gettype($valOrColIndex) == 'integer'){
                            $testCol = $this->getStructure()->getColByIndex($valOrColIndex);
                        }
                        else{
                            $testCol = $this->getStructure()->getCol($valOrColIndex);
                        }
                        $cols[] = $testCol;
                        $vals[] = $colOrVal;
                    }
                }
                $where = $this->createWhereConditions($cols, $vals, $options['conditions'], $options['join-operators']);
            }
            else{
                $where = '';
            }
            if(trim($where) == 'where'){
                $where = '';
            }
        }
        $this->setQuery('select count(*)'.$asPart.' from '.$this->getStructureName().$where.';', 'select');
    }
    /**
     * Constructs a 'select' query.
     * @param array $selectOptions An associative array which contains 
     * options to construct different select queries. The available options are: 
     * <ul>
     * <li><b>colums</b>: An optional array which can have the keys of columns that 
     * will be select.</li>
     * <li><b>limit</b>: The 'limit' attribute of the query.</li>
     * <li><b>offset</b>: The 'offset' attribute of the query. Ignored if the 
     * option 'limit' is not set.</li>
     * <li><b>condition-cols-and-vals</b>: An associative array. The indices can 
     * be values and the value at each index is an objects of type 'Column'. 
     * Or the indices can be column indices or columns names taken from MySQLTable object and 
     * the values are set for each index. The second way is recommended as one 
     * table might have two columns with the same values.</li>
     * <li><b>where</b>: Similar to 'condition-cols-and-vals'.</li>
     * <li><b>conditions</b>: An array that can contains conditions (=, !=, &lt;, 
     * &lt;=, &gt; or &gt;=). If anything else is given at specific index, '=' will be used. In 
     * addition, If not provided or has invalid value, an array of '=' conditions 
     * is used.</li>
     * <li><b>join-operators</b>: An array that contains a set of MySQL join operators 
     * like 'and' and 'or'. If not provided or has invalid value, 
     * an array of 'and's will be used.</li>
     * <li><b>select-max</b>: A boolean value. Set to true if you want to select maximum 
     * value of a column. Ignored in case the option 'columns' is set.</li>
     * <li><b>select-min</b>: A boolean value. Set to true if you want to select minimum 
     * value of a column. Ignored in case the option 'columns' or 'select-max' is set.</li>
     * <li><b>column</b>: The column which contains maximum or minimum value.</li>
     * <li><b>rename-to</b>: Rename the max or min column to the given name.</li>
     * <li><b>group-by</b>: An indexed array that contains 
     * sub associative arrays which has 'group by' columns info. The sub associative 
     * arrays can have the following indices:
     * <ul>
     * <li>col: The name of the column.</li>
     * </ul></li>
     * <li><b>order-by</b>: An indexed array that contains 
     * sub associative arrays which has columns 'order by' info. The sub associative 
     * arrays can have the following indices:
     * <ul>
     * <li><b>col<b>: The name of the column.</li>
     * <li>order-type: An optional string to represent the order. It can 
     * be 'A' for ascending or 'D' for descending</li>
     * </ul></li>
     * </ul>
     * @since 1.8.3
     */
    public function select($selectOptions=array(
        'colums'=>array(),
        'condition-cols-and-vals'=>array(),
        'conditions'=>array(),
        'join-operators'=>array(),
        'limit'=>-1,
        'offset'=>-1,
        'select-min'=>false,
        'select-max'=>false,
        'column'=>'',
        'rename-to'=>'',
        'order-by'=>null,
        'group-by'=>null
        )) {
        $table = $this->getStructure();
        if($table instanceof MySQLTable){
            $vNum = $table->getMySQLVersion();
            $vSplit = explode('.', $vNum);
            if(intval($vSplit[0]) <= 5 && intval($vSplit[1]) < 6){
                
            }
            $selectQuery = 'select ';
            $limit = isset($selectOptions['limit']) ? $selectOptions['limit'] : -1;
            $offset = isset($selectOptions['offset']) ? $selectOptions['offset'] : -1;
            if($limit > 0 && $offset > 0){
                $limitPart = ' limit '.$limit.' offset '.$offset;
            }
            else if($limit > 0 && $offset <= 0){
                $limitPart = ' limit '.$limit;
            }
            else{
                $limitPart = '';
            }
            $groupByPart = '';
            if(isset($selectOptions['group-by']) && gettype($selectOptions['group-by']) == 'array'){
                $groupByPart = $this->_buildGroupByCondition($selectOptions['group-by']);
            }
            $orderByPart = '';
            if(isset($selectOptions['order-by']) && gettype($selectOptions['order-by']) == 'array'){
                $orderByPart = $this->_buildOrderByCondition($selectOptions['order-by']);
            }
            if(isset($selectOptions['columns']) && count($selectOptions['columns']) != 0){
                $count = count($selectOptions['columns']);
                $i = 0;
                $colsFound = 0;
                foreach ($selectOptions['columns'] as $column){
                    if($table->hasColumn($column)){
                        $colsFound++;
                        if($i + 1 == $count){
                            $selectQuery .= $this->getColName($column).' from '.$this->getStructureName();
                        }
                        else{
                            $selectQuery .= $this->getColName($column).',';
                        }
                    }
                    else{
                        if($i + 1 == $count && $colsFound != 0){
                            $selectQuery = trim($selectQuery, ',');
                            $selectQuery .= ' from '.$this->getStructureName();
                        }
                        else if($i + 1 == $count && $colsFound == 0){
                            $selectQuery .= '* from '.$this->getStructureName();
                        }
                    }
                    $i++;
                }
            }
            else if(isset ($selectOptions['select-max']) && $selectOptions['select-max'] === true){
                $renameTo = isset($selectOptions['rename-to']) ? $selectOptions['rename-to'] : '';
                if(strlen($renameTo) != 0){
                    $renameTo = 'as '.$renameTo;
                }
                else{
                    $renameTo = '';
                }
                if(isset($selectOptions['column']) && $table->hasColumn($selectOptions['column'])){
                    $selectQuery .= 'max('.$this->getColName($selectOptions['column']).') '.$renameTo.' from '.$table->getName();
                    $limitPart = '';
                }
                else{
                    return false;
                }
            }
            else if(isset ($selectOptions['select-min']) && $selectOptions['select-min'] === true){
                $renameTo = isset($selectOptions['rename-to']) ? $selectOptions['rename-to'] : '';
                if(strlen($renameTo) != 0){
                    $renameTo = 'as '.$renameTo;
                }
                else{
                    $renameTo = '';
                }
                if(isset($selectOptions['column']) && $table->hasColumn($selectOptions['column'])){
                    $selectQuery .= 'min('.$this->getColName($selectOptions['column']).') '.$renameTo.' from '.$table->getName();
                    $limitPart = '';
                }
                else{
                    return false;
                }
            }
            else{
                $selectQuery .= '* from '.$this->getStructureName();
            }
            if(!isset($selectOptions['condition-cols-and-vals'])){
                $selectOptions['condition-cols-and-vals'] = isset($selectOptions['where']) ? $selectOptions['where'] : [];
            }
            $selectOptions['join-operators'] = isset($selectOptions['join-operators']) && 
                    gettype($selectOptions['join-operators']) == 'array' ? $selectOptions['join-operators'] : array();
            $selectOptions['conditions'] = isset($selectOptions['conditions']) && 
                    gettype($selectOptions['conditions']) == 'array' ? $selectOptions['conditions'] : array();
            if(isset($selectOptions['condition-cols-and-vals']) && isset($selectOptions['conditions'])){
                $cols = array();
                $vals = array();
                foreach($selectOptions['condition-cols-and-vals'] as $valOrColIndex => $colOrVal){
                    if($colOrVal instanceof Column){
                        $cols[] = $colOrVal;
                        $vals[] = $valOrColIndex;
                    }
                    else{
                        if(gettype($valOrColIndex) == 'integer'){
                            $testCol = $this->getStructure()->getColByIndex($valOrColIndex);
                        }
                        else{
                            $testCol = $this->getStructure()->getCol($valOrColIndex);
                        }
                        $cols[] = $testCol;
                        $vals[] = $colOrVal;
                    }
                }
                $where = $this->createWhereConditions($cols, $vals, $selectOptions['conditions'], $selectOptions['join-operators']);
            }
            else{
                $where = '';
            }
            if(trim($where) == 'where'){
                $where = '';
            }
            $this->setQuery($selectQuery.$where.$groupByPart.$orderByPart.$limitPart.';', 'select');
            return true;
        }
        return false;
    }
    /**
     * Constructs the 'order by' part of a query.
     * @param array $orderByArr An indexed array that contains 
     * sub associative arrays which has columns 'order by' info. The sub associative 
     * arrays can have the following indices:
     * <ul>
     * <li>col: The name of the column.</li>
     * <li>order-type: An optional string to represent the order. It can 
     * be 'A' for ascending or 'D' for descending</li>
     * </ul>
     * @return string The string that represents order by part.
     */
    private function _buildOrderByCondition($orderByArr){
        $colsCount = count($orderByArr);
        $orderByStr = 'order by ';
        $actualColsArr = [];
        for($x = 0 ; $x < $colsCount ; $x++){
            $colName = isset($orderByArr[$x]['col']) ? $orderByArr[$x]['col'] : null;
            $colObj = $this->getCol($colName);
            if($colObj instanceof Column){
                $orderType = isset($orderByArr[$x]['order-type']) ? strtoupper($orderByArr[$x]['order-type']) : null;
                $actualColsArr[] = [
                    'object'=>$colObj,
                    'order-type'=>$orderType
                ];
            }
        }
        $actualCount = count($actualColsArr);
        for($x = 0 ; $x < $actualCount ; $x++){
            $colObj = $actualColsArr[$x]['object'];
            $orderByStr .= $colObj->getName();
            $orderType = $actualColsArr[$x]['order-type'];
            if($orderType == 'A'){
                $orderByStr .= ' asc';
            }
            else if($orderType == 'D'){
                $orderByStr .= ' desc';
            }
            if($x + 1 != $actualCount){
                $orderByStr .= ', ';
            }
        }
        if($orderByStr == 'order by '){
            return '';
        }
        return ' '.trim($orderByStr);
    }
    /**
     * Constructs the 'group by' part of a query.
     * @param array $groupByArr An indexed array that contains 
     * sub associative arrays which has 'group by' columns info. The sub associative 
     * arrays can have the following indices:
     * <ul>
     * <li>col: The name of the column.</li>
     * </ul>
     * @return string The string that represents order by part.
     */
    private function _buildGroupByCondition($groupByArr){
        $colsCount = count($groupByArr);
        $groupByStr = 'group by ';
        $actualColsArr = [];
        for($x = 0 ; $x < $colsCount ; $x++){
            $colName = isset($groupByArr[$x]['col']) ? $groupByArr[$x]['col'] : null;
            $colObj = $this->getCol($colName);
            if($colObj !== null){
                $actualColsArr[] = [
                    'object'=>$colObj
                ];
            }
        }
        $actualCount = count($actualColsArr);
        for($x = 0 ; $x < $actualCount ; $x++){
            $colObj = $actualColsArr[$x]['object'];
            $groupByStr .= $colObj->getName();
            if($x + 1 != $actualCount){
                $groupByStr .= ', ';
            }
        }
        if($groupByStr == 'group by '){
            return '';
        }
        return ' '.trim($groupByStr);
    }

    /**
     * Constructs a 'where' condition given a date.
     * @param string $date A date or timestamp.
     * @param string $colName The name of the column that will contain 
     * the date value.
     * @param string $format The format of the date. The supported formats 
     * are:
     * <ul>
     * <li>YYYY-MM-DD HH:MM:SS</li>
     * <li>YYYY-MM-DD</li>
     * <li>YYYY</li>
     * <li>MM</li>
     * <li>DD</li>
     * <li>HH:MM:SS</li>
     * <li>HH</li>
     * <li>MM</li>
     * <li>SS</li>
     * </ul>
     */
    public static function createDateCondition($date,$colName,$format='YYYY-MM-DD HH:MM:SS') {
        $formatInUpperCase = strtoupper(trim($format));
        $condition = '';
        if($formatInUpperCase == 'YYYY-MM-DD HH:MM:SS'){
            $dateTimeSplit = explode(' ', $date);
            if(count($date) == 2){
                $datePart = explode('-', $dateTimeSplit[0]);
                $timePart = explode(':', $dateTimeSplit[0]);
                if(count($datePart) == 3 && count($timePart) == 3){
                    $condition = 'year('.$colName.') = '.$datePart[0].' and '
                            .'month('.$colName.') = '.$datePart[1].' and '
                            .'day('.$colName.') = '.$datePart[2].' and '
                            .'hour('.$colName.') = '.$datePart[2].' and '
                            .'minute('.$colName.') = '.$datePart[2].' and '
                            .'second('.$colName.') = '.$datePart[2].' and ';
                }
            }
        }
        else if($formatInUpperCase == 'YYYY-MM-DD'){
            $datePart = explode('-', $date);
            if(count($datePart) == 3){
                $condition = 'year('.$colName.') = '.$datePart[0].' and '
                            .'month('.$colName.') = '.$datePart[1].' and '
                            .'day('.$colName.') = '.$datePart[2];
            }
        }
        else if($formatInUpperCase == 'YYYY'){
            $asInt = intval($date);
            if($asInt > 1900 && $asInt < 10000){
                $condition = 'year('.$colName.') = '.$date;
            }
        }
        else if($formatInUpperCase == 'MM'){
            $asInt = intval($date);
            if($asInt > 0 && $asInt < 13){
                $condition = 'month('.$colName.') = '.$date;
            }
        }
        else if($formatInUpperCase == 'DD'){
            $asInt = intval($date);
            if($asInt > 0 && $asInt < 32){
                $condition = 'day('.$colName.') = '.$date;
            }
        }
        else if($formatInUpperCase == 'HH:MM:SS'){
            $datePart = explode(':', $date);
            if(count($datePart) == 3){
                $condition = 'hour('.$colName.') = '.$datePart[0].' and '
                            .'minute('.$colName.') = '.$datePart[1].' and '
                            .'second('.$colName.') = '.$datePart[2];
            }
        }
        else if($formatInUpperCase == 'HH'){
            $asInt = intval($date);
            if($asInt > 0 && $asInt < 24){
                $condition = 'hour('.$colName.') = '.$date;
            }
        }
        else if($formatInUpperCase == 'SS'){
            $asInt = intval($date);
            if($asInt > 0 && $asInt < 60){
                $condition = 'second('.$colName.') = '.$date;
            }
        }
        else if($formatInUpperCase == 'MM'){
            $asInt = intval($date);
            if($asInt > 0 && $asInt < 59){
                $condition = 'minute('.$colName.') = '.$date;
            }
        }
        return $condition;
    }
    private function _selectIn($optionsArr){
        
    }
    /**
     * Selects a values from a table given specific columns values.
     * @param array $cols An array that contains an objects of type 'Column'.
     * @param array $vals An array that contains values. 
     * @param array $valsConds An array that can contains two possible values: 
     * '=' or '!='. If anything else is given at specific index, '=' will be used. 
     * Note that if the value at '$vals[$index]' is equal to 'IS NULL' or 'IS NOT NULL', 
     * The value at '$valsConds[$index]' is ignored. 
     * @param array $jointOps An array of conditions (Such as 'or', 'and', 'xor').
     * @since 1.6
     */
    public function selectByColsVals($cols,$vals,$valsConds,$jointOps,$limit=-1,$offset=-1){
        $where = '';
        $count = count($cols);
        $index = 0;
        foreach($cols as $col){
            $equalityCond = trim($valsConds[$index]);
            if($equalityCond != '!=' && $equalityCond != '='){
                $equalityCond = '=';
            }
            if($col instanceof Column){
                $valUpper = strtoupper(trim($vals[$index]));
                if($valUpper == 'IS NULL' || $valUpper == 'IS NOT NULL'){
                    if($index + 1 == $count){
                        $where .= $col->getName().' '.$vals[$index].'';
                    }
                    else{
                        $where .= $col->getName().' '.$vals[$index].' '.$jointOps[$index].' ';
                    }
                }
                else{
                    if($index + 1 == $count){
                        $where .= $col->getName().' '.$equalityCond.' ';
                        if($col->getType() == 'varchar' || $col->getType() == 'datetime' || $col->getType() == 'timestamp' || $col->getType() == 'text' || $col->getType() == 'mediumtext'){
                            $where .= '\''.$vals[$index].'\'' ;
                        }
                        else{
                            $where .= $vals[$index];
                        }
                    }
                    else{
                        $where .= $col->getName().' '.$equalityCond.' ';
                        if($col->getType() == 'varchar' || $col->getType() == 'datetime' || $col->getType() == 'timestamp' || $col->getType() == 'text' || $col->getType() == 'mediumtext'){
                            $where .= '\''.$vals[$index].'\' '.$jointOps[$index].' ' ;
                        }
                        else{
                            $where .= $vals[$index].' '.$jointOps[$index].' ';
                        }
                    }
                }
            }
            $index++;
        }
        if($limit > 0 && $offset > 0){
            $lmit = 'limit '.$limit.' offset '.$offset;
        }
        else if($limit > 0 && $offset <= 0){
            $lmit = 'limit '.$limit;
        }
        else{
            $lmit = '';
        }
        $this->setQuery(self::SELECT.$this->getStructureName().' where '.$where.' '.$lmit.';', 'select');
    }
    /**
     * Constructs a query that can be used to insert a new record.
     * @param array $colsAndVals An associative array. The array can have two 
     * possible structures:
     * <ul>
     * <li>A column index taken from MySQLTable object as an index with a 
     * value as the value of the column (Recommended).</li>
     * <li>A value as an index with an object of type 'Column' as it is value.</li>
     * </ul>
     * The second way is not recommended as it may cause some issues if two columns 
     * have the same value.
     * @since 1.8.2
     */
    public function insertRecord($colsAndVals) {
        $cols = '';
        $vals = '';
        $count = count($colsAndVals);
        $index = 0;
        $comma = '';
        foreach($colsAndVals as $valOrColIndex=>$colObjOrVal){
            if($index + 1 == $count){
                $comma = '';
            }
            else{
                $comma = ',';
            }
            if($colObjOrVal instanceof Column){
                //a value as an index with an object of type Column
                $cols .= $colObjOrVal->getName().$comma;
                if($valOrColIndex !== 'null'){
                    $type = $colObjOrVal->getType();
                    if($type == 'varchar' || $type == 'datetime' || $type == 'timestamp' || $type == 'text' || $type == 'mediumtext'){
                        $vals .= '\''.self::escapeMySQLSpeciarChars($valOrColIndex).'\''.$comma;
                    }
                    else if($type == 'decimal' || $type == 'double' || $type == 'float'){
                        $vals .= '\''.$valOrColIndex.'\''.$comma;
                    }
                    else if($type == 'tinyblob' || $type == 'mediumblob' || $type == 'longblob'){
                        $fixedPath = str_replace('\\', '/', $valOrColIndex);
                        if(file_exists($fixedPath)){
                            $file = fopen($fixedPath, 'r');
                            $data = '';
                            if($file !== false){
                                $fileContent = fread($file, filesize($fixedPath));
                                if($fileContent !== false){
                                    $data = '\''. addslashes($fileContent).'\'';
                                    $vals .= $data.$comma;
                                    $this->setIsBlobInsertOrUpdate(true);
                                }
                                else{
                                    $vals .= 'null'.$comma;
                                }
                                fclose($file);
                            }
                            else{
                                $vals .= 'null'.$comma;
                            }
                        }
                        else{
                            $vals .= 'null'.$comma;
                        }
                    }
                    else{
                         $vals .= $valOrColIndex.$comma;
                    }
                }
                else{
                    $vals .= 'null'.$comma;
                }
            }
            else{
                //an index with a value
                if(gettype($valOrColIndex) == 'integer'){
                    $column = $this->getStructure()->getColByIndex($valOrColIndex);
                }
                else{
                    $column = $this->getStructure()->getCol($valOrColIndex);
                }
                if($column instanceof Column){
                    $cols .= $column->getName().$comma;
                    if($colObjOrVal !== 'null'){
                        $type = $column->getType();
                        if($type == 'varchar' || $type == 'datetime' || $type == 'timestamp' || $type == 'text' || $type == 'mediumtext'){
                            $vals .= '\''.self::escapeMySQLSpeciarChars($colObjOrVal).'\''.$comma;
                        }
                        else if($type == 'decimal' || $type == 'double' || $type == 'float'){
                            $vals .= '\''.$colObjOrVal.'\''.$comma;
                        }
                        else if($type == 'tinyblob' || $type == 'mediumblob' || $type == 'longblob'){
                            $fixedPath = str_replace('\\', '/', $colObjOrVal);
                            if(file_exists($fixedPath)){
                                $file = fopen($fixedPath, 'r');
                                $data = '';
                                if($file !== false){
                                    $fileContent = fread($file, filesize($fixedPath));
                                    if($fileContent !== false){
                                        $data = '\''. addslashes($fileContent).'\'';
                                        $vals .= $data.$comma;
                                        $this->setIsBlobInsertOrUpdate(true);
                                    }
                                    else{
                                        $vals .= 'null'.$comma;
                                    }
                                    fclose($file);
                                }
                                else{
                                    $vals .= 'null'.$comma;
                                }
                            }
                            else{
                                $vals .= 'null'.$comma;
                            }
                        }
                        else{
                            $vals .= $colObjOrVal.$comma;
                        }
                    }
                    else{
                        $vals .= 'null'.$comma;
                    }
                }
            }
            $index++;
        }
        
        $cols = ' ('.$cols.')';
        $vals = ' ('.$vals.')';
        $this->setQuery(self::INSERT.$this->getStructureName().$cols.' values '.$vals.';', 'insert');
    }
    /**
     * Removes a record from the table.
     * @param array $columnsAndVals An associative array. The indices of the array 
     * should be the values of the columns and the value at each index is 
     * an object of type 'Column'.
     * @param array $valsConds An array that can have only two possible values, 
     * '=' and '!='. The number of elements in this array must match number of 
     * elements in the array $cols.
     * @param array $jointOps An array which contains conditional operators 
     * to join conditions. The operators can be logical or bitwise. Possible 
     * values include: &&, ||, and, or, |, &, xor. It is optional in case there 
     * is only one condition.
     * @since 1.8.2
     */
    public function deleteRecord($columnsAndVals,$valsConds,$jointOps=[]) {
        $cols = [];
        $vals = [];
        foreach ($columnsAndVals as $valOrIndex => $colObjOrVal){
            if($colObjOrVal instanceof Column){
                $cols[] = $colObjOrVal;
                $vals[] = $valOrIndex;
            }
            else{
                if(gettype($valOrIndex) == 'integer'){
                    $testCol = $this->getStructure()->getColByIndex($valOrIndex);
                }
                else{
                    $testCol = $this->getStructure()->getCol($valOrIndex);
                }
                $cols[] = $testCol;
                $vals[] = $colObjOrVal;
            }
        }
        $query = 'delete from '.$this->getStructureName();
        $this->setQuery($query.$this->createWhereConditions($cols, $vals, $valsConds, $jointOps).';', 'delete');
    }
    /**
     * A method that is used to create the 'where' part of any query in case 
     * of multiple columns.
     * @param array $cols An array that holds an objects of type 'Column'.
     * @param array $vals An array that contains columns values. The number of 
     * elements in this array must match number of elements in the array $cols.
     * @param array $valsConds An array that can have only two possible values, 
     * '=' and '!='. The number of elements in this array must match number of 
     * elements in the array $cols.
     * @param array $jointOps An array which contains conditional operators 
     * to join conditions. The operators can be logical or bitwise. Possible 
     * values include: &&, ||, and, or, |, &, xor.
     * @return string A string that represents the 'where' part of the query.
     * @since 1.8.2
     */
    private function createWhereConditions($cols,$vals,$valsConds,$jointOps){
        $colsCount = count($cols);
        $valsCount = count($vals);
        $condsCount = count($valsConds);
        $joinOpsCount = count($jointOps);
        if($colsCount == 0 || $valsCount == 0){
            return '';
        }
        while ($colsCount != $condsCount){
            $valsConds[] = '=';
            $condsCount = count($valsConds);
        }
        while (($colsCount - 1) != $joinOpsCount){
            $jointOps[] = 'and';
            $joinOpsCount = count($jointOps);
        }
        if($colsCount != $valsCount || $colsCount != $condsCount || ($colsCount - 1) != $joinOpsCount){
            return '';
        }
        $index = 0;
        $count = count($cols);
        $where = ' where ';
        $supportedConds = ['=','!=','<','<=','>','>='];
        foreach ($cols as $col){
            //first, check given condition
            $equalityCond = trim($valsConds[$index]);
            if(!in_array($equalityCond, $supportedConds)){
                $equalityCond = '=';
            }
            //then check if column object is given
            if($col instanceof Column){
                //then check value
                $valUpper = gettype($vals[$index]) != 'array' ? strtoupper(trim($vals[$index])) : '';
                if($valUpper == 'IS NULL' || $valUpper == 'IS NOT NULL'){
                    if($index + 1 == $count){
                        $where .= $col->getName().' '.$valUpper.'';
                    }
                    else{
                        $where .= $col->getName().' '.$valUpper.' '.$jointOps[$index].' ';
                    }
                }
                else{
                    if($index + 1 == $count){
                        if($col->getType() == 'varchar' || $col->getType() == 'text' || $col->getType() == 'mediumtext'){
                            $where .= $col->getName().' '.$equalityCond.' ';
                            $where .= '\''.self::escapeMySQLSpeciarChars($vals[$index]).'\'' ;
                        }
                        else if($col->getType() == 'decimal' || $col->getType() == 'float' || $col->getType() == 'double'){
                            $where .= $col->getName().' '.$equalityCond.' ';
                            $where .= '\''.$vals[$index].'\'' ;
                        }
                        else if($col->getType() == 'datetime' || $col->getType() == 'timestamp'){
                            if(gettype($vals[$index]) == 'array'){
                                $value = $vals[$index];
                                if(isset($value['value'])){
                                    if(isset($value['format'])){
                                        $str = $this->createDateCondition($value['value'], $col->getName(), $value['format']);
                                    }
                                    else{
                                        $str = $this->createDateCondition($value['value'], $col->getName());
                                    }
                                    if(strlen($str) !== 0){
                                        $where .= '('.$str.') ';
                                    }
                                }
                            }
                            else{
                                $where .= 'date('.$col->getName().') '.$equalityCond.' ';
                                $where .= '\''.self::escapeMySQLSpeciarChars($vals[$index]).'\' ';
                            }
                        }
                        else{
                            $where .= $col->getName().' '.$equalityCond.' ';
                            $where .= $vals[$index];
                        }
                    }
                    else{
                        if($col->getType() == 'varchar' || $col->getType() == 'text' || $col->getType() == 'mediumtext'){
                            $where .= $col->getName().' '.$equalityCond.' ';
                            $where .= '\''.self::escapeMySQLSpeciarChars($vals[$index]).'\' '.$jointOps[$index].' ' ;
                        }
                        else if($col->getType() == 'decimal' || $col->getType() == 'float' || $col->getType() == 'double'){
                            $where .= $col->getName().' '.$equalityCond.' ';
                            $where .= '\''.$vals[$index].'\'' ;
                        }
                        else if($col->getType() == 'datetime' || $col->getType() == 'timestamp'){
                            if(gettype($vals[$index]) == 'array'){
                                $value = $vals[$index];
                                if(isset($value['value'])){
                                    if(isset($value['format'])){
                                        $str = $this->createDateCondition($value['value'], $col->getName(), $value['format']);
                                    }
                                    else{
                                        $str = $this->createDateCondition($value['value'], $col->getName());
                                    }
                                    if(strlen($str) !== 0){
                                        $where .= '('.$str.') '.$jointOps[$index].' ';
                                    }
                                }
                            }
                            else{
                                $where .= 'date('.$col->getName().') '.$equalityCond.' ';
                                $where .= '\''.self::escapeMySQLSpeciarChars($vals[$index]).'\' ';
                            }
                        }
                        else{
                            $where .= $col->getName().' '.$equalityCond.' ';
                            $where .= $vals[$index].' '.$jointOps[$index].' ';
                        }
                    }
                }
            }
            $index++;
        }
        return ' '.trim($where);
    }
    
    /**
     * Constructs a query that can be used to update a record.
     * @param array $colsAndNewVals An associative array. The key must be the 
     * new value and the value of the index is an object of type 'Column'.
     * @param array $colsAndVals An associative array that contains columns and 
     * values for the 'where' clause. The indices should be the values and the 
     * value at each index should be an object of type 'Column'. 
     * The number of elements in this array must match number of elements 
     * in the array $colsAndNewVals.
     * @param array $valsConds An array that can have only two possible values, 
     * '=' and '!='. The number of elements in this array must match number of 
     * elements in the array $colsAndNewVals.
     * @param array $jointOps An array which contains conditional operators 
     * to join conditions. The operators can be logical or bitwise. Possible 
     * values include: &&, ||, and, or, |, &, xor. It is optional in case there 
     * is only one condition.
     * @since 1.8.2
     */
    public function updateRecord($colsAndNewVals,$colsAndVals,$valsConds,$jointOps=array()) {
        $colsStr = '';
        $comma = '';
        $index = 0;
        $count = count($colsAndNewVals);
        foreach($colsAndNewVals as $newValOrIndex => $colObjOrNewVal){
            if($index + 1 == $count){
                $comma = '';
            }
            else{
                $comma = ',';
            }
            if($colObjOrNewVal instanceof Column){
                $newValLower = strtolower($newValOrIndex);
                if(trim($newValLower) !== 'null'){
                    $type = $colObjOrNewVal->getType();
                    if($type == 'varchar' || $type == 'datetime' || $type == 'timestamp' || $type == 'text' || $type == 'mediumtext'){
                        $colsStr .= ' '.$colObjOrNewVal->getName().' = \''.self::escapeMySQLSpeciarChars($newValOrIndex).'\''.$comma ;
                    }
                    else if($type == 'decimal' || $type == 'float' || $type == 'double'){
                        $colsStr .= '\''.$newValOrIndex.'\''.$comma;
                    }
                    else if($type == 'tinyblob' || $type == 'mediumblob' || $type == 'longblob'){
                        $fixedPath = str_replace('\\', '/', $newValOrIndex);
                        if(file_exists($fixedPath)){
                            $file = fopen($fixedPath, 'r');
                            $data = '';
                            if($file !== false){
                                $fileContent = fread($file, filesize($fixedPath));
                                if($fileContent !== false){
                                    $data = '\''. addslashes($fileContent).'\'';
                                    $colsStr .= $data.$comma;
                                    $this->setIsBlobInsertOrUpdate(true);
                                }
                                else{
                                    $colsStr .= 'null'.$comma;
                                }
                                fclose($file);
                            }
                            else{
                                $colsStr .= 'null'.$comma;
                            }
                        }
                        else{
                            $colsStr .= 'null'.$comma;
                        }
                    }
                    else{
                        $colsStr .= ' '.$colObjOrNewVal->getName().' = '.$newValOrIndex.$comma;
                    }
                }
                else{
                    $colsStr .= ' '.$colObjOrNewVal->getName().' = null'.$comma;
                }
            }
            else{
                $column = $this->getStructure()->getColByIndex($newValOrIndex);
                if(gettype($newValOrIndex) == 'integer'){
                    $column = $this->getStructure()->getColByIndex($newValOrIndex);
                }
                else{
                    $column = $this->getStructure()->getCol($newValOrIndex);
                }
                if($column instanceof Column){
                    $newValLower = strtolower($colObjOrNewVal);
                    if(trim($newValLower) !== 'null'){
                        $type = $column->getType();
                        if($type == 'varchar' || $type == 'datetime' || $type == 'timestamp' || $type == 'text' || $type == 'mediumtext'){
                            $colsStr .= ' '.$column->getName().' = \''.self::escapeMySQLSpeciarChars($colObjOrNewVal).'\''.$comma ;
                        }
                        else if($type == 'decimal' || $type == 'float' || $type == 'double'){
                            $colsStr .= '\''.$newValOrIndex.'\''.$comma;
                        }
                        else if($type == 'tinyblob' || $type == 'mediumblob' || $type == 'longblob'){
                            $fixedPath = str_replace('\\', '/', $colObjOrNewVal);
                            if(file_exists($fixedPath)){
                                $file = fopen($fixedPath, 'r');
                                $data = '';
                                if($file !== false){
                                    $fileContent = fread($file, filesize($fixedPath));
                                    if($fileContent !== false){
                                        $data = '\''. addslashes($fileContent).'\'';
                                        $colsStr .= $data.$comma;
                                        $this->setIsBlobInsertOrUpdate(true);
                                    }
                                    else{
                                        $colsStr .= 'null'.$comma;
                                    }
                                    fclose($file);
                                }
                                else{
                                    $colsStr .= 'null'.$comma;
                                }
                            }
                            else{
                                $colsStr .= 'null'.$comma;
                            }
                        }
                        else{
                            $colsStr .= ' '.$column->getName().' = '.$colObjOrNewVal.$comma;
                        }
                    }
                    else{
                        $colsStr .= ' '.$column->getName().' = null'.$comma;
                    }
                }
            }
            $index++;
        }
        $colsArr = array();
        $valsArr = array();
        foreach ($colsAndVals as $valueOrIndex=>$colObjOrVal){
            if($colObjOrVal instanceof Column){
                $colsArr[] = $colObjOrVal;
                $valsArr[] = $valueOrIndex;
            }
            else{
                if(gettype($valueOrIndex) == 'integer'){
                    $testCol = $this->getStructure()->getColByIndex($valueOrIndex);
                }
                else{
                    $testCol = $this->getStructure()->getCol($valueOrIndex);
                }
                $colsArr[] = $testCol;
                $valsArr[] = $colObjOrVal;
            }
        }
        $this->setQuery('update '.$this->getStructureName().' set '.$colsStr.$this->createWhereConditions($colsArr, $valsArr, $valsConds, $jointOps).';', 'update');
    }
    /**
     * Checks if the query represents a blob insert or update.
     * The aim of this method is to fix an issue with setting the collation 
     * of the connection while executing a query.
     * @return boolean The Function will return true if the query represents an 
     * insert or un update of blob datatype. false if not.
     * @since 1.8.5
     */
    public function isBlobInsertOrUpdate(){
        return $this->isFileInsert;
    }
    /**
     * Sets the property that is used to check if the query represents an insert 
     * or an update of a blob datatype.
     * The attribute is used to fix an issue with setting the collation 
     * of the connection while executing a query.
     * @param boolean $boolean true if the query represents an insert or an update 
     * of a blob datatype. false if not.
     * @since 1.8.5
     */
    public function setIsBlobInsertOrUpdate($boolean) {
        $this->isFileInsert = $boolean === true ? true : false;
    }
    /**
     * Updates a table columns that has a datatype of blob from source files.
     * @param array $arr An associative array of keys and values. The keys will 
     * be acting as the columns names and the values should be a path to a file 
     * on the host machine.
     * @param string $id  the ID of the record that will be updated.
     * @since 1.2
     */
    public function updateBlobFromFile($arr,$id,$idColName){
        $cols = '';
        $count = count($arr);
        $index = 0;
        foreach($arr as $col => $val){
            $fixedPath = str_replace('\\', '/', $val);
            $file = fopen($fixedPath, 'r');
            $data = '\'\'';
            if($file !== false){
                $fileContent = fread($file, filesize($fixedPath));
                if($fileContent !== false){
                    $data = '\''. addslashes($fileContent).'\'';
                    $this->setIsBlobInsertOrUpdate(true);
                }
            }
            if($index + 1 == $count){
                $cols .= $col.' = '.$data;
            }
            else{
                $cols .= $col.' = '.$data.', ';
            }
            $index++;
        }
        $this->setQuery('update '.$this->getStructureName().' set '.$cols.' where '.$idColName.' = '.$id, 'update');
    }
    /**
     * Constructs a query that can be used to select maximum value of a table column.
     * @param string $col The name of the column as specified while initializing 
     * linked table. This value should return an object of type Column 
     * when passed to the method MySQLQuery::getCol().
     * @param string $rename The new name of the column that contains max value. 
     * The default value is 'max'.
     * @since 1.3
     */
    public function selectMax($col,$rename='max'){
        return $this->select(array(
            'column'=> $col,
            'select-max'=>true,
            'rename-to'=>$rename
        ));
    }
    /**
     * Constructs a query that can be used to select minimum value of a table column.
     * @param string $col The name of the column as specified while initializing 
     * linked table. This value should return an object of type Column 
     * when passed to the method MySQLQuery::getCol().
     * @param string $rename The new name of the column that contains min value. 
     * The default value is 'min'.
     * @since 1.3
     */
    public function selectMin($col,$rename='min'){
        return $this->select(array(
            'column'=>$col,
            'select-min'=>true,
            'rename-to'=>$rename
        ));
    }
    /**
     * Constructs a query that can be used to create the table which is linked 
     * with the query class.
     * @param boolean $inclComments If set to true, the generated MySQL 
     * query will have basic comments explaining the structure.
     * @return boolean Once the query is structured, the method will return 
     * true. If the query is not created, the method will return false. 
     * The query will not constructed if the method 'MySQLQuery::getStructure()' 
     * did not return an object of type 'Table'.
     * @since 1.5
     */
    public function createStructure($inclComments=false){
        $t = $this->getStructure();
        if($t instanceof MySQLTable){
            $this->createTable($t,$inclComments);
            return true;
        }
        return false;
    }
    /**
     * Returns the name of the column from the table given its key.
     * @param string $colKey The name of the column key.
     * @return string The name of the column in the table. If no column was 
     * found, the method will return the string MySQLTable::NO_SUCH_COL. If there is 
     * no table linked with the query object, the method will return the 
     * string MySQLQuery::NO_STRUCTURE.
     * @since 1.5
     */
    public function getColName($colKey){
        $col = $this->getCol($colKey);
        if($col instanceof Column){
            return $col->getName();
        }
        return $col;
    }
    /**
     * Returns a column from the table given its key.
     * @param string $colKey The name of the column key.
     * @return string|Column The the column in the table. If no column was 
     * found, the method will return the string 'MySQLTable::NO_SUCH_COL'. If there is 
     * no table linked with the query object, the method will return the 
     * string MySQLQuery::NO_STRUCTURE.
     * @since 1.6
     */
    public function &getCol($colKey){
        $structure = $this->getStructure();
        $retVal = self::NO_STRUCTURE;
        if($structure instanceof MySQLTable){
            $col = $structure->getCol($colKey);
            if($col instanceof Column){
                return $col;
            }
            $retVal = MySQLTable::NO_SUCH_COL;
        }
        return $retVal;
    }
    /**
     * Returns the index of a column given its key.
     * @param string $colKey The name of the column key.
     * @return int  The index of the column if found starting from 0. 
     * If the column was not found, the method will return -1.
     * @since 1.8.4
     */
    public function getColIndex($colKey){
        $col = $this->getCol($colKey);
        $index = $col instanceof Column ? $col->getIndex() : -1;
        return $index;
    }
    /**
     * Returns the table that is used for constructing queries.
     * @return MySQLTable The table that is used for constructing queries.
     * @since 1.5
     */
    public abstract function getStructure();
    /**
     * Returns the name of the table that is used to construct queries.
     * @return string The name of the table that is used to construct queries. 
     * if no table is linked, the method will return the string MySQLQuery::NO_STRUCTURE.
     * @since 1.5
     */
    public function getStructureName(){
        $s = $this->getStructure();
        if($s instanceof MySQLTable){
            return $s->getName();
        }
        return self::NO_STRUCTURE;
    }
    
    public function __toString() {
        return 'Query: '.$this->getQuery().'<br/>'.'Query Type: '.$this->getType().'<br/>';
    }
}

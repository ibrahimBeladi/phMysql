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
use phMysql\MySQLTable;
use phMysql\MySQLColumn;
/**
 * Experimental class. DO NOT USE.
 *
 * @author Ibrahim
 */
class JoinTable extends MySQLTable{
    /**
     * The number of joins which was performed before.
     * @var type 
     * @since 1.9.0
     */
    private static $JoinsCount = 0;
    private $leftTable;
    private $rightTable;
    private $joinType;
    private $joinCond;
    public function __construct($leftTable,$rightTable,$tableName) {
        parent::__construct();
        if(!$this->setName($tableName)){
            $this->setName('T'.self::$JoinsCount);
        }
        self::$JoinsCount++;
        if($leftTable instanceof MySQLTable){
            $this->leftTable = $leftTable;
        }
        else if($leftTable instanceof MySQLQuery){
            $this->leftTable = $leftTable->getTable();
        }
        else{
            $this->leftTable = new MySQLTable('left_table');
        }
        if($rightTable instanceof MySQLTable){
            $this->rightTable = $rightTable;
        }
        else if($leftTable instanceof MySQLQuery){
            $this->rightTable = $rightTable->getTable();
        }
        else{
            $this->rightTable = new MySQLTable('right_table');
        }
        $this->joinType = 'left';
        $this->_addAndValidateColmns();
    }
    public function getJoinCondition() {
        return $this->joinCond;
    }
    public function setJoinCondition($cols,$conds=[],$joinOps=[]) {
        if(gettype($cols) == 'array'){
            while (count($conds) < count($cols)){
                $conds[] = '=';
            }
            while (count($joinOps) < count($cols)){
                $joinOps[] = 'and';
            }
            $index = 0;
            foreach ($cols as $leftCol => $rightCol){
                $leftColObj = $this->getLeftTable()->getCol($leftCol);
                if($leftColObj instanceof MySQLColumn){
                    $rightColObj = $this->getRightTable()->getCol($rightCol);
                    if($rightColObj instanceof MySQLColumn){
                        if($rightColObj->getType() == $leftColObj->getType()){
                            $cond = $conds[$index];
                            if(strlen($this->joinCond) == 0){
                                $this->joinCond = 'on '. $this->getLeftTable()->getName().'.'
                                       . $leftColObj->getName().' '.$cond.' '
                                       . $this->getRightTable()->getName().'.'
                                       . $rightColObj->getName();
                            }
                            else {
                                $joinOp = $joinOps[$index - 1];
                                if($joinOp != 'and' && $joinOp != 'or'){
                                    $joinOp = 'and';
                                }
                                $this->joinCond .= 
                                       ' '.$joinOp.' '.$this->getLeftTable()->getName().'.'
                                       . $leftColObj->getName().' '.$cond.' '
                                       . $this->getRightTable()->getName().'.'
                                       . $rightColObj->getName();
                            }
                        }
                    }
                }
                $index++;
            }
        } 
    }
    public function getJoinType() {
        return $this->joinType;
    }
    /**
     * 
     * @return MySQLTable
     */
    public function getRightTable() {
        return $this->rightTable;
    }
    /**
     * 
     * @return MySQLTable
     */
    public function getLeftTable() {
        return $this->leftTable;
    }
    private function _addAndValidateColmns() {
        $commonColsKeys = [];
        $leftColsKeys = $this->getLeftTable()->colsKeys();
        $rightColsKeys = $this->getRightTable()->colsKeys();
        foreach ($rightColsKeys as $col){
            foreach ($leftColsKeys as $col2){
                if($col == $col2){
                    $commonColsKeys[] = $col2;
                }
            }
        }
        $commonCols = [];
        $rightCols = $this->getRightTable()->getColsNames();
        $leftCols = $this->getLeftTable()->getColsNames();
        foreach ($rightCols as $col){
            foreach ($leftCols as $col2){
                if($col == $col2){
                    $commonCols[] = $col2;
                }
            }
        }
        $colsArr = [];
        foreach ($leftColsKeys as $col){
            if(in_array($col, $commonColsKeys)){
                $colsArr['left-'.$col] = $this->getLeftTable()->getCol($col);
            }
            else{
                $colsArr[$col] = $this->getLeftTable()->getCol($col);
            }
        }
        foreach ($rightColsKeys as $col){
            if(in_array($col, $commonColsKeys)){
                $colsArr['right-'.$col] = $this->getRightTable()->getCol($col);
            }
            else{
                $colsArr[$col] = $this->getRightTable()->getCol($col);
            }
        }
        $index = 0;
        $leftCount = count($leftCols);
        foreach ($colsArr as $colkey => $colObj){
            if($colObj instanceof MySQLColumn){
                if(in_array($colObj->getName(), $commonCols)){
                    if($index < $leftCount){
                        $colObj->setName('left_'.$colObj->getName());
                    }
                    else{
                        $colObj->setName('right_'.$colObj->getName());
                    }
                }
            }
            $this->addColumn($colkey, $colObj);
            $index++;
        }
    }
}

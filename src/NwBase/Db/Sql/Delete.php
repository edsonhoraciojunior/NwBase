<?php
/**
 * Natural Web Ltda. (http://www.naturalweb.com.br)
 *
 * @copyright Copyright (c) Natural Web Ltda. (http://www.naturalweb.com.br)
 * @license   BSD-3-Clause
 * @package   NwBase\Db\Sql
 */
namespace NwBase\Db\Sql;

use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Delete as Zend_Delete;

/**
 * Montagem de Select
 *
 * @category   NwBase
 * @package    NwBase\Db
 * @subpackage Sql
 * @author     Renato Moura <renato@naturalweb.com.br>
 */
class Delete extends Zend_Delete
{
    /**
     * Create where clause
     * Foi Alterado para aceitar o object Predicate, antes da string da coluna
     *
     * @param Where|\Closure|string|array $predicate   Where clause
     * @param string                      $combination One of the OP_* constants from Predicate\PredicateSet
     *
     * @see Zend\Db\Sql.Delete::where()
     * @return Delete
     */
    public function where($predicate, $combination = Predicate\PredicateSet::OP_AND)
    {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
            
        } elseif ($predicate instanceof \Closure) {
            $predicate($this->where);
            
        } else {
            if (is_string($predicate)) {
                // String $predicate should be passed as an expression
                $predicate = new Predicate\Expression($predicate);
                $this->where->addPredicate($predicate, $combination);
            } elseif (is_array($predicate)) {
    
                foreach ($predicate as $pkey => $pvalue) {
                    // loop through predicates
    
                    if (is_string($pkey) && strpos($pkey, '?') !== false) {
                        // First, process strings that the abstraction replacement character ?
                        // as an Expression predicate
                        $predicate = new Predicate\Expression($pkey, $pvalue);
                        
                    } elseif ($pvalue instanceof Predicate\PredicateInterface) {
                        // Predicate type is ok
                        $predicate = $pvalue;
                            
                    } elseif (is_string($pkey)) {
                        // Otherwise, if still a string, do something intelligent with the PHP type provided
    
                        if (is_null($pvalue)) {
                            // map PHP null to SQL IS NULL expression
                            $predicate = new Predicate\IsNull($pkey, $pvalue);
                        } elseif (is_array($pvalue)) {
                            // if the value is an array, assume IN() is desired
                            $predicate = new Predicate\In($pkey, $pvalue);
                        } else {
                            // otherwise assume that array('foo' => 'bar') means "foo" = 'bar'
                            $predicate = new Predicate\Operator($pkey, Predicate\Operator::OP_EQ, $pvalue);
                        }
                    } else {
                        // must be an array of expressions (with int-indexed array)
                        $predicate = new Predicate\Expression($pvalue);
                    }
                    $this->where->addPredicate($predicate, $combination);
                }
            }
        }
        return $this;
    }
}

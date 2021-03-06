<?php
/**
 * Natural Web Ltda. (http://www.naturalweb.com.br)
 *
 * @copyright 2013 - Copyright (c) Natural Web Ltda. (http://www.naturalweb.com.br)
 * @license   BSD-3-Clause http://opensource.org/licenses/BSD-3-Clause
 */
namespace NwBase\Entity;

use NwBase\Model\InterfaceModel;

/**
 * Interface das Entity
 *
 * @category NwBase
 * @package  NwBase\Entity
 * @author   Renato Moura <renato@naturalweb.com.br>
 */
interface InterfaceEntity
{
    /**
     * Retorna id da entidade
     *
     * @return int
     */
    public function getId();
    
    /**
     * Retorna descrição da entidade
     *
     * @return string
     */
    public function getDescription();
    
    /**
     * Retorna se a entidade esta armazenado
     *
     * @return boolean
     */
    public function getStored();
    
    /**
     * Retorna o array  com as propriedade modificadas
     *
     * @return array
     */
    public function getModified();
    
    /**
     * Valida se uma propriedade foi alterada do valor original
     *
     * @param string $property Nome da Propriedade
     *
     * @return boolean
     */
    public function hasModified($property);
    
    /**
     * Limpa a lista das propriedades modificadas
     *
     * @return void
     */
    public function clearModified();
    
    /**
     * Set todas as propriedades existente na entidade
     * 
     * @param array $data Dados de Entrada
     * 
     * @return InterfaceEntity
     */
    public function exchangeArray($data);
    
    /**
     * Extrai as propriedade para um array
     * 
     * @return array
     */
    public function getArrayCopy();
    
    /**
     * Executa o metodo getArrayCopy
     *
     * @return array
     */
    public function toArray();
    
    /**
     * Seta o valor da propriedade
     *
     * @param string $property Name Property
     * @param mixed  $value    Valor da Propriedade
     *
     * @return InterfaceEntity
     */
    public function setProperty($property, $value);
    
    /**
     * Retorna os dados como uma string
     *
     * @return string
     */
    public function toString();
    
    /**
     * Metodo chamado antes da inserção da entidade
     * 
     * @param InterfaceModel $model Model que chamou
     * 
     * @return void
     */
    public function preInsert(InterfaceModel $model);
    
    /**
     * Metodo chamado depois da inserção da entidade
     *
     * @param InterfaceModel $model Model que chamou
     * 
     * @return void
     */
    public function postInsert(InterfaceModel $model);
    
    /**
     * Metodo chamado antes da alteração da entidade
     *
     * @param InterfaceModel $model Model que chamou
     * 
     * @return void
     */
    public function preUpdate(InterfaceModel $model);
    
    /**
     * Metodo chamado depois da alteração da entidade
     *
     * @param InterfaceModel $model Model que chamou
     * 
     * @return void
     */
    public function postUpdate(InterfaceModel $model);
    
    /**
     * Metodo chamado antes da exclusão da entidade
     *
     * @param InterfaceModel $model Model que chamou
     * 
     * @return void
     */
    public function preDelete(InterfaceModel $model);
    
    /**
     * Metodo chamado depois da alteração da entidade
     *
     * @param InterfaceModel $model Model que chamou
     * 
     * @return void
     */
    public function postDelete(InterfaceModel $model);
}

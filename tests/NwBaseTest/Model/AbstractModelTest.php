<?php

namespace NwBaseTest\Model;

// Somente para os Testes
use NwBase\Model\AbstractModel;

require_once __DIR__ . '/_files/FooBarModel.php';

use NwBaseTest\Model\FooBarModel;
use NwBaseTest\Entity\FooBarEntity;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\TableIdentifier;
use Zend\ServiceManager\ServiceManager;

class AbstractModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $adapter;
    protected $model;
    protected $tableNameTest = 'table_test';
    
    public function setUp()
    {
        $driver = "pdo_sqlite";
        $charset  = 'utf8';
        $dsn = sprintf("sqlite::memory:");
        $db = array(
                'driver'         => $driver,
                'dsn'            => $dsn,
                'charset'        => $charset,
        );
        $this->adapter = new Adapter($db);
        
        $sql = '
        CREATE TABLE IF NOT EXISTS '.$this->tableNameTest.' (
            foo INTEGER PRIMARY KEY,
            bar VARCHAR(20) NOT NULL,
            poliforlismo VARCHAR(20)
        );';
        $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
        $this->adapter->query("INSERT INTO {$this->tableNameTest} VALUES (1, 'valor 1', null)", Adapter::QUERY_MODE_EXECUTE);
        $this->adapter->query("INSERT INTO {$this->tableNameTest} VALUES (2, 'valor 2', null)", Adapter::QUERY_MODE_EXECUTE);
        $this->adapter->query("INSERT INTO {$this->tableNameTest} VALUES (3, 'valor 3', null)", Adapter::QUERY_MODE_EXECUTE);
        $this->adapter->query("INSERT INTO {$this->tableNameTest} VALUES (4, 'valor 4', null)", Adapter::QUERY_MODE_EXECUTE);
        
        $this->model = new FooBarModel($this->adapter);
    }
    
    public function tearDown()
    {
        $this->adapter->query('DROP TABLE ' . $this->tableNameTest, Adapter::QUERY_MODE_EXECUTE);
    }
    
    public function assertPreConditions()
    {
        $this->assertTrue(
            class_exists($class = 'NwBase\Model\AbstractModel'),
            "Classe Abstract Model não existe: " . $class
        );
    }
    
    public function testAbstractModelConstructedSetAdapter()
    {
        $this->assertSame($this->tableNameTest, $this->model->getTableName(), "Deveria buscar o nome da tabela do metadata");
                
        $this->assertSame($this->adapter, $this->model->getAdapter(), "Deveria retornar o Adapter");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testMetodoBuscaColunasPrimarias()
    {
        $columnPrimary = array('foo');
        
        $this->assertSame($columnPrimary, $this->model->getColumnPrimary(), "Deveria buscar o nome da coluna primary");
        $this->assertAttributeEquals($columnPrimary, "_columnPrimary", $this->model, "Não setou o nome da coluna primary como deveria na popriedade");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException LogicException
     * @expectedExceptionMessage Coluna primary não definida
     */
    public function testMetodoGetColunaPrimaryIndefinadaThrownException()
    {
        $this->adapter->query('DROP TABLE ' . $this->tableNameTest, Adapter::QUERY_MODE_EXECUTE);
    
        $sql = 'CREATE TABLE IF NOT EXISTS '.$this->tableNameTest.' (foo INTEGER, bar VARCHAR(10), poliforlismo VARCHAR(10));';
        $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
    
        $model = new FooBarModel($this->adapter);
        $coluna = $model->getColumnPrimary();
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testMetodoBuscaQuantidadeColunasDoModel()
    {
        $expected = array('foo', 'bar', 'poliforlismo');
        $this->assertEquals(count($expected), count($this->model->getColumns()), "Deveria retornar a listagem de colunas");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException LogicException
     * @expectedExceptionMessage Table name not found
     */
    public function testMetodoGetNomeTabelaIndefinadaThrownException()
    {
        $mockAbstract = $this->getMockForAbstractClass('NwBase\Model\AbstractModel', array($this->adapter));
    
        $nome = $mockAbstract->getTableName();
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testMetodoGetSelectDaAbstractModel()
    {
        // Resultado
        $where = array('id_teste' => 1, 'nome' => 'foobar');
        $order = "nome asc";
        $limit = 5;
        $offset = 2;
        
        $select = new Select(new TableIdentifier($this->tableNameTest));
        $select->where($where);
        $select->order($order);
        $select->limit($limit);
        $select->offset($offset);
        
        $return = $this->model->getSelect($where, $order, $limit, $offset);
        $this->assertInstanceOf('Zend\Db\Sql\Select', $return, "Tipo de Retorno invalido");
        $this->assertEquals($select, $return, "Select All não retornou corretamante");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testMetodoFetchAllDaAbstractModel()
    {
        $prototype = new FooBarEntity();
        
        $select = new Select($this->tableNameTest);
        $statement = $this->adapter->createStatement($select->getSqlString());
        $dataSource = $statement->execute();
        
        $resultSet = new ResultSet();
        $resultSet->initialize($dataSource);
        $resultSet->setArrayObjectPrototype($prototype);
        
        $return = $this->model->fetchAll();
        $this->assertInstanceOf('Zend\Db\ResultSet\ResultSet', $return);
        $this->assertEquals($resultSet, $return);
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testFetchRowReturnsEntiy()
    {
        $where = array('foo' => 1);
        
        $values = array('foo' => 1, 'bar' => 'valor 1');
        $entity = new FooBarEntity();
        $entity->exchangeArray($values);
        
        $return = $this->model->fetchRow($where);
        $this->assertInstanceOf('NwBase\Entity\AbstractEntity', $return, "Tipo do prototype inesperado");
        $this->assertEquals($entity, $return, "Não retornou o prototype esperado");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testMetodoFindByIdRetornoPrototype()
    {
        $where = array('id' => 1);
        
        $values = array('foo' => 1, 'bar' => 'valor 1');
        $entity = new FooBarEntity();
        $entity->exchangeArray($values);
        
        $prototype = new \ArrayObject();
        $prototype->id   = 1;
        $prototype->nome = 'foobar';
    
        $return = $this->model->findById(1);
        $this->assertInstanceOf('NwBase\Entity\AbstractEntity', $return, "Tipo do prototype inesperado");
        $this->assertEquals($entity, $return, "Não retornou o prototype esperado");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testFetchPairs()
    {
        $arrayExpected = array(
            ''  => 'Selecione',
            '2' => 'valor 2',
            '3' => 'valor 3',
            '4' => 'valor 4',
        );
        
        $where = array(new \Zend\Db\Sql\Predicate\Operator('foo', '>', 1));
        $resultPairs = $this->model->fetchPairs('foo', 'bar', $where, '', array(''=>'Selecione'));
        
        $this->assertInstanceOf('NwBase\Db\ResultSet\ResultSetPairs', $resultPairs, "Objeto de Result Invalido");
        $this->assertEquals($arrayExpected, $resultPairs->toArray(), "Valores pareado invalidos");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testCount()
    {
        $where = array('foo <= ?' => 3);
    
        $count = $this->model->count($where);
    
        $this->assertEquals(3, $count, "Contagem de valores invalida");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testIsUnique()
    {
        $value = "valor 1";
        $is_unique = $this->model->isUnique('bar', $value, 2);
        $this->assertFalse($is_unique, "Deveria encontrar o registro e retornar falso");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \LogicException
     * @expectedExceptionMessage Coluna "baz" não existe na tabela
     */
    public function testIsUniqueThrownException()
    {
        $value = "valor 1";
        $is_unique = $this->model->isUnique('baz', $value, 2);
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testIsUniqueValueNull()
    {
        $value = null;
        $is_unique = $this->model->isUnique('bar', $value, 2);
        $this->assertTrue($is_unique, "Deveria encontrar o registro e retornar falso");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testCanDeleteAnEntity()
    {
        $myEntity = new FooBarEntity();
        $myEntity->setFoo(2);
        
        $rowsAfetados = $this->model->delete($myEntity);
        
        $this->assertEquals(1, $rowsAfetados, "Valor de linhas retornadas invalidas");
        
        // Se realmente exclui o registro
        $select = new Select($this->tableNameTest);
        $select->where(array('foo' => 2));
        $statement = $this->adapter->createStatement($select->getSqlString());
        $dataSource = $statement->execute();
        $this->assertEquals(0, $dataSource->count(), "Não excluiu o registro");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \Exception
     * @expectedExceptionMessage Valor da chave primaria não definida
     */
    public function testCanDeleteAnEntityEmptyThrowException()
    {
        $myEntity = new FooBarEntity();
        
        $rowsAfetados = $this->model->delete($myEntity);
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \Exception
     */
    public function testCanDeleteAnWhereClouser()
    {
        $spec = function (Where $where) {
            $where->in("foo", array(1,3,4));
        };
        
        $rowsAfetados = $this->model->delete($spec);
        $this->assertEquals(3, $rowsAfetados, "Valor de linhas retornadas invalidas");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testCanInsertAnEntityEBuscaLastValue()
    {
        $myEntity = new FooBarEntity();
        $myEntity->setBar('mais um valor');
        
        $return = $this->model->insert($myEntity);
    
        $this->assertEquals(1, $return, "Valor de retorno invalido, não inseriu o registro");
        $this->assertEquals(5, $this->model->getLastInsertValue(), "Valor LastInsertValur invalido");
        
        // Se realmente exclui o registro
        $where = array("bar LIKE 'mais um valor'");
        $select = new Select($this->tableNameTest);
        $select->where($where);
        $statement = $this->adapter->createStatement($select->getSqlString());
        $dataSource = $statement->execute();
        $this->assertEquals(1, $dataSource->count(), "Não encontrou o registro inserido");
        
        $expectedEntity = new FooBarEntity($dataSource->current());
        $this->assertEquals($expectedEntity, $myEntity, "Deveria alterar a Entity e definir o id primary");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \Exception
     */
    public function testCanInsertAnEntityThrowException()
    {    
        $myEntity = new FooBarEntity();
        
        $return = $this->model->insert($myEntity);
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \Exception
     */
    public function testCanInsertAnArrayDireto()
    {
        $campos = array('bar' => 'valor 5', 'poliforlismo' => 'outro');
        
        $return = $this->model->insert($campos);
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     */
    public function testCanUpdateAnEntity()
    {
        $myEntity = new FooBarEntity();
        $myEntity->setFoo(2);
        $myEntity->setBar('trocar o valor');
    
        $rowsAfetados = $this->model->update($myEntity);
    
        $this->assertEquals(1, $rowsAfetados, "Valor de linhas retornadas invalidas");
    
        // Se realmente exclui o registro
        $select = new Select($this->tableNameTest);
        $select->where(array('foo' => 2));
        $statement = $this->adapter->createStatement($select->getSqlString());
        $dataSource = $statement->execute();
        $this->assertEquals(1, $dataSource->count(), "Não excluiu o registro");
        
        $expected = array(
            'foo' => '2',
            'bar' => 'trocar o valor',
            'poliforlismo' => null,
        );
        $this->assertEquals($expected, $dataSource->current(), "Retorno invalido");
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \Exception
     * @expectedExceptionMessage Valor da chave primaria não definida
     */
    public function testCanUpdateAnEntityEmptyThrowException()
    {
        $myEntity = new FooBarEntity();
        $myEntity->setBar('trocar o valor');
        
        $rowsAfetados = $this->model->update($myEntity);
    }
    
    /**
     * @depends testAbstractModelConstructedSetAdapter
     * @expectedException \Exception
     */
    public function testCanUpdateAnWhereClouser()
    {
        $spec = function (Where $where) {
            $where->in("foo", array(1,3,4));
        };
        
        $set = array('poliforlismo' => 'novo valor');
        $rowsAfetados = $this->model->update($set, $spec);
    }
    
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Metodo "buscaPorUsuario" inválido
     */
    public function testMagicCallMethodInvalidThrowException()
    {
        $this->model->buscaPorUsuario('foo');
    }
    
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Coluna "teste" para busca não existe na tabela
     */
    public function testMagicCallColumnInvalidThrowException()
    {
        $this->model->findByTeste('foo');
    }
    
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Argumento obrigatorio para busca, no metodo "findByFoo"
     */
    public function testMagicCallArgumentEmptyThrowException()
    {
        $this->model->findByFoo();
    }
    
    public function testMagicCallFindByColumn()
    {
        $values = array('foo' => 3, 'bar' => 'valor 3');
        $expected = new FooBarEntity();
        $expected->exchangeArray($values);
        
        $return = $this->model->findByBar('valor 3');
    
        $this->assertEquals($expected, $return);
    }
    
    public function testServiceLocatorAwareInterface()
    {
        $services = new ServiceManager();
        $services->setService('Zend\Db\Adapter\Adapter', $this->adapter);
    
        $model = new FooBarModel();
    
        $this->assertAttributeEmpty("_serviceLocator", $model);
        
        $model->setServiceLocator($services);
        
        // Service
        $this->assertEquals($services, $model->getServiceLocator());
        $this->assertAttributeEquals($services, '_serviceLocator', $model);
    }
    
    public function testSetTableGateway()
    {
        $model = new FooBarModel();
        
        $newTableGateway = new TableGateway(new TableIdentifier($this->tableNameTest), $this->adapter);
        $return = $model->setTableGateway($newTableGateway);
        
        $this->assertAttributeEquals($newTableGateway, '_tableGateway', $model);
        $this->assertEquals($model, $return, "Deveria retornar a propria instancia");
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A Identificação da tabela no objeto deve corresponder
     */
    public function testSetTableGatewayThrowException()
    {
        $model = new FooBarModel();
    
        $newTableGateway = new TableGateway($this->tableNameTest, $this->adapter);
        $model->setTableGateway($newTableGateway);
    }
    
    public function testGetAdapterAndGetTablegatewayAndMetadata()
    {
        $services = new ServiceManager();
        $services->setService('Zend\Db\Adapter\Adapter', $this->adapter);
    
        $model = new FooBarModel();
        $model->setServiceLocator($services);
        
        $this->assertAttributeEmpty("_dbAdapter", $model);
        $this->assertAttributeEmpty("_tableGateway", $model);
        $this->assertAttributeEmpty("_metadataTable", $model);
        
        // Service
        $this->assertEquals($services, $model->getServiceLocator());
        $this->assertAttributeEquals($services, '_serviceLocator', $model);
    
        // Adapter
        $this->assertEquals($this->adapter, $model->getAdapter());
        $this->assertAttributeEquals($this->adapter, '_dbAdapter', $model);
        
        // tablegateway
        $prototype = new FooBarEntity();
        $prototype->setServiceLocator($services);
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype($prototype);
        $tableGateway = new TableGateway(new TableIdentifier($this->tableNameTest), $this->adapter, null, $resultSetPrototype);
    
        $this->assertEquals($tableGateway, $model->getTableGateway(), "Deveria buscar o TableGateway");
        $this->assertAttributeEquals($tableGateway, '_tableGateway', $model);
    
        // Metadata
        $metadata = new Metadata($this->adapter);
        $metadataTable = $metadata->getTable($this->tableNameTest);
    
        $this->assertEquals($metadataTable, $model->getMetadataTable(), "Deveria buscar o Metadata");
        $this->assertAttributeEquals($metadataTable, "_metadataTable", $model, "Não setou a metadata table como deveria");
    }
    
    public function testSetAndGetDefaultCache()
    {
        $cache = $this->getMock('Zend\Cache\Storage\StorageInterface');
        AbstractModel::setDefaultCache($cache);
        
        $model = new FooBarModel();
        $this->assertAttributeSame($cache, '_defaultCache', $model);
        
        $this->assertSame($cache, $model::getDefaultCache());
        $this->assertSame($cache, AbstractModel::getDefaultCache());
    }
    
    public function testSetAndGetMetadataCache()
    {
        $cache = $this->getMock('Zend\Cache\Storage\StorageInterface');
        
        $model = new FooBarModel();
        $model->setMetadataCache($cache);
        
        $this->assertAttributeSame($cache, '_metadataCache', $model);
    
        $this->assertSame($cache, $model->getMetadataCache());
    }
    
    public function testGetMetadataCacheDefault()
    {
        $cache = $this->getMock('Zend\Cache\Storage\StorageInterface');
        AbstractModel::setDefaultCache($cache);
        
        $model = new FooBarModel();
        
        $this->assertAttributeEquals(null, '_metadataCache', $model);
        $this->assertSame($cache, $model->getMetadataCache());
        $this->assertAttributeEquals($cache, '_metadataCache', $model);
    }
}

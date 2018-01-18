<?php namespace Spoom\MVC\Model;

use Spoom\MVC\Model;
use Spoom\MVC\ModelInterface;

//
abstract class Statement {

  /**
   * @var string
   */
  private $_method;
  /**
   * @var ModelInterface
   */
  private $_model;

  /**
   * @var DefinitionInterface[][]
   */
  private $_definition = [
    Model::DEFINITION_FIELD  => [],
    Model::DEFINITION_FILTER => [],
    Model::DEFINITION_SORT   => []
  ];

  /**
   * @param string         $method
   * @param ModelInterface $model
   */
  public function __construct( string $method, ModelInterface $model ) {
    $this->_method = $method;
    $this->_model  = $model;
  }

  /**
   * Wrapper around the execute
   *
   * @param int|null $limit
   * @param int      $offset
   *
   * @return array|int|null
   */
  public function __invoke( ?int $limit, int $offset ) {

    // pre-process definitions
    foreach( $this->_definition as $definition_list ) {
      foreach( $definition_list as $definition ) {
        $definition->apply();
      }
    }

    //
    $result = $this->execute( $limit, $offset );

    // post-process result with the definitions
    foreach( $this->_definition as $definition_list ) {
      foreach( $definition_list as $definition ) {
        $definition->finish( $result );
      }
    }

    return $result;
  }

  /**
   * Execute the statement
   *
   * @param int|null $limit
   * @param int      $offset
   *
   * @return mixed
   */
  abstract protected function execute( ?int $limit, int $offset );

  /**
   * Add new definition o the statement
   *
   * @param string $method
   * @param string $name
   * @param null   $value
   * @param int    $slot
   *
   * @throws \InvalidArgumentException
   * @throws \LogicException
   */
  public function addDefinition( string $method, string $name, $value = null, int $slot = 0 ) {

    //
    $operator = null;
    $name     = Model::operator( $name, $operator );
    if( !isset( $this->_definition[ $method ][ $name ] ) ) {

      //
      $tmp                                   = clone $this->_model->getDefinition( $method, $name );
      $this->_definition[ $method ][ $name ] = $tmp->setStatement( $this );
    }

    $this->_definition[ $method ][ $name ]->add( $operator, $value, $slot );
  }

  /**
   * @return string
   */
  public function getMethod() {
    return $this->_method;
  }
  /**
   * @return ModelInterface
   */
  public function getModel() {
    return $this->_model;
  }
}
<?php namespace Spoom\MVC\Model;

use Spoom\MVC\Model;
use Spoom\MVC\ModelInterface;

//
interface StatementInterface {

  /**
   * Execute the statement
   *
   * @param int $limit
   * @param int $offset
   *
   * @return array|int|null
   */
  public function __invoke( int $limit, int $offset );

  /**
   * Add definition to the statement
   *
   * @param DefinitionInterface $definition
   * @param string              $operator
   * @param mixed|null          $value
   * @param int                 $slot
   *
   * @return static
   */
  public function addDefinition( DefinitionInterface $definition, string $operator, $value = null, int $slot = 0 );

  /**
   * @return string
   */
  public function getMethod();
  /**
   * @return ModelInterface
   */
  public function getModel();
}

//
class Statement implements StatementInterface {

  /**
   * @var array
   */
  private $_source;
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
   * @param array          $source
   * @param string         $method
   * @param ModelInterface $model
   */
  public function __construct( array &$source, string $method, ModelInterface $model ) {
    $this->_source = &$source;

    $this->_method = $method;
    $this->_model  = $model;
  }

  //
  public function __invoke( int $limit, int $offset ) {

    switch( $this->_method ) {
      //
      case ModelInterface::METHOD_SEARCH:

        //
        $list = $this->getSourceList( $limit, $offset );
        return $this->getFieldList( $list );

      //
      case ModelInterface::METHOD_CREATE:

        $_list = $this->getFieldList( $this->_model->getField(), $limit, $offset );
        foreach( $_list as $item ) {
          $this->_source[] = $item;
        }
        return $_list;

      //
      case ModelInterface::METHOD_UPDATE:

        $_list = $this->getFieldList( $this->_model->getField(), 1 );
        $field = $_list[ 0 ];

        //
        $list = $this->getSourceList( $limit, $offset );

        $_list = [];
        foreach( $list as $item ) {
          foreach( $this->_source as $i => $_item ) {
            if( $this->_model->key( $item ) == $this->_model->key( $_item ) ) {
              $this->_source[ $i ] = $field + $_item;
              $_list[]             = $this->_source[ $i ];
              break;
            }
          }
        }

        return count( $_list );

      //
      case ModelInterface::METHOD_REMOVE:

        //
        $list = $this->getSourceList( $limit, $offset );

        $_list = [];
        foreach( $list as $item ) {
          foreach( $this->_source as $i => $_item ) {
            if( $this->_model->key( $item ) == $this->_model->key( $_item ) ) {

              $_list[] = $_item;
              array_splice( $this->_source, $i, 1 );
              break;
            }
          }
        }

        return count( $_list );
    }

    return null;
  }

  //
  public function addDefinition( DefinitionInterface $definition, string $operator, $value = null, int $slot = 0 ) {

    //
    $type = $definition->getType();
    $name = $definition->getName();
    if( !isset( $this->_definition[ $type ][ $name ] ) ) {

      //
      $tmp                                 = clone $definition;
      $this->_definition[ $type ][ $name ] = $tmp->setStatement( $this );
    }

    $this->_definition[ $type ][ $name ]->add( $operator, $value, $slot );
    return $this;
  }

  /**
   * Get source list with applied filter, sort and limits
   *
   * @param int $limit
   * @param int $offset
   *
   * @return array
   */
  protected function getSourceList( int $limit = 0, int $offset = 0 ): array {

    $list = $this->_source;
    foreach( ( $this->_definition[ ModelInterface::DEFINITION_FILTER ] ?? [] ) as $definition ) {
      $list = $definition( $list );
    }

    foreach( ( $this->_definition[ ModelInterface::DEFINITION_SORT ] ?? [] ) as $definition ) {
      $list = $definition( $list );
    }

    return array_slice( $list, $offset, $limit === 0 ? null : $limit );
  }
  /**
   * Rebuild list with field definitions
   *
   * This will filter out unneccessary fields
   *
   * @param array $list
   * @param int   $limit
   * @param int   $offset
   *
   * @return array
   */
  protected function getFieldList( array $list, int $limit = 0, int $offset = 0 ) {

    $list  = array_slice( $list, $offset, $limit === 0 ? null : $limit );
    $_list = array_combine( array_keys( $list ), array_fill( 0, count( $list ), [] ) );
    foreach( ( $this->_definition[ ModelInterface::DEFINITION_FIELD ] ?? [] ) as $definition ) {
      $_list = $definition( $_list, $list );
    }

    return $_list;
  }

  //
  public function getMethod() {
    return $this->_method;
  }
  //
  public function getModel() {
    return $this->_model;
  }
}
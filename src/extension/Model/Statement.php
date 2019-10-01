<?php namespace Spoom\MVC\Model;

use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper\Number;
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
  private $_source = [];
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
  private $_definition;

  /**
   * @param array          $source
   * @param string         $method
   * @param ModelInterface $model
   */
  public function __construct( array &$source, string $method, ModelInterface $model, array $definition_list ) {
    $this->_source = &$source;

    // TODO it should check the source for elements validity (keyless item or something like that)

    $this->_method = $method;
    $this->_model  = $model;
    $this->_definition = $definition_list;
  }

  //
  public function __invoke( int $limit, int $offset ) {

    // prevent any write (create, update, remove) operation due to lack of any key definition in the model. Item identification depends on these definitions
    if( $this->_method !== ModelInterface::METHOD_SEARCH && empty( $this->_model->getKey() ) ) {
      throw new \LogicException( "Unable to perform any modification without key definitions in " . static::class . " model" );
    }

    $backup = $this->_source;
    $_result = [];
    try {

      // setup definitions for the statement
      foreach( $this->_definition as $definition_list ) {
        foreach( $definition_list as $definition ) {
          $definition->attach( $this );
        }
      }

      switch( $this->_method ) {
        //
        case ModelInterface::METHOD_SEARCH:

          //
          $list = array_values( $this->getList( $limit, $offset ) );

          //
          $_result = array_combine( array_keys( $list ), array_fill( 0, count( $list ), [] ) );
          foreach( $this->_definition[ DefinitionInterface::FIELD ] as $definition ) {

            if( !$definition( $_result, $list ) && $definition instanceof Model\Definition\Field ) {

              $formatter = $definition->getFormatter();
              foreach( $list as $i => $item ) {
                $value                                  = $item[ $definition->getField( true ) ] ?? null;
                $_result[ $i ][ $definition->getName() ] = isset( $formatter ) ? $formatter( $value, $list, $definition, $i ) : $value;
              }
            }
          }

          break;

        //
        case ModelInterface::METHOD_CREATE:

          $_list  = $this->getFieldList( $limit, $offset );
          foreach( $_list as $i => $item ) {
            // TODO check and handle key-less items, and consider autoincrement functionality

            $_result[ $i ]   = $this->_model->key( $item );
            $this->_source[] = $item;
          }

          break;

        //
        case ModelInterface::METHOD_UPDATE:

          //
          $list = $this->getList( $limit, $offset );

          $_list = $this->getFieldList( 1, 0, $list );
          $field = $_list[ 0 ];

          foreach( $list as $i => $item ) {
            foreach( $this->_source as $_i => $_item ) {
              if( $this->_model->key( $item ) == $this->_model->key( $_item ) ) {
                $_result[ $i ]        = $this->_model->key( $item );
                $this->_source[ $_i ] = $field + $_item;
                break;
              }
            }
          }

          break;

        //
        case ModelInterface::METHOD_REMOVE:

          //
          $list = $this->getList( $limit, $offset );

          foreach( $list as $i => $item ) {
            foreach( $this->_source as $_i => $_item ) {
              if( $this->_model->key( $item ) == $this->_model->key( $_item ) ) {

                $_result[ $i ] = $this->_model->key( $item );
                array_splice( $this->_source, $_i, 1 );
                break;
              }
            }
          }

          break;
      }

      // finish definitions for the statement
      $result = $_result;
      foreach( $this->_definition as $definition_list ) {
        foreach( $definition_list as $definition ) {
          $definition->apply( $result, $_result );
        }
      }

    } catch( \Throwable $e ) {

      // apply definition's revert for the statement
      foreach( $this->_definition as $definition_list ) {
        foreach( $definition_list as $definition ) {
          $definition->revert( $e, $_result );
        }
      }

      // revert any modification done to the source
      $this->_source = $backup;
    }

    // apply definition's cleanup for the statement
    foreach( $this->_definition as $definition_list ) {
      foreach( $definition_list as $definition ) try {
        $definition->detach( $result, $_result );
      } catch( \Throwable $e ) {
        // TODO this should at least log the exception, not completly ignore
      }
    }

    return $result;
  }

  /**
   * Get list with applied filter, sort and limits
   *
   * @param int $limit
   * @param int $offset
   *
   * @return array
   */
  protected function getList( int $limit = 0, int $offset = 0 ): array {

    $list = $this->_source;
    foreach( $this->_definition[ DefinitionInterface::FILTER ] as $definition ) {

      // execute definition and handle basic filter definition if it's not prevented
      if( !$definition( $list, $this->_source ) && $definition instanceof Model\Definition ) {

        $_list = [];
        foreach( $list as $item ) {

          $value = $item[ $definition->getField( true ) ] ?? null;
          foreach( $definition->get( 0 ) as $operator => $test ) {
            if( !$this->match( $value, new Operator( $operator ), $test ) ) {
              continue 2;
            }
          }

          $_list[] = $item;
        }

        $list = $_list;
      }
    }

    //
    foreach( $this->_definition[ DefinitionInterface::SORT ] as $definition ) {

      // handle basic sort definitions
      if( !$definition( $list, $this->_source ) && $definition instanceof Model\Definition ) {
        foreach( $definition->get( 0 ) as $operator => $_ ) {
          $list = $this->sort( $list, new Operator( $operator ), $definition->getField( true ) );
        }
      }
    }

    //
    return array_slice( $list, $offset, $limit === 0 ? null : $limit, true );
  }
  /**
   * @param int $limit
   * @param int $offset
   *
   * @return array
   */
  protected function getFieldList( int $limit = 0, int $offset = 0, array $list = [] ) {

    // FIXME maybe we should check if the field's has lesser slot than the limit
    $limit = $limit < 1 ? ( count( $this->_model->getField() ) - $offset ) : min( ( count( $this->_model->getField() ) - $offset ), $limit );

    $result = array_fill( $offset, $limit, [] );
    foreach( $this->_definition[ DefinitionInterface::FIELD ] as $definition ) {

      // handle basic filter definitions
      if( !$definition( $result, $list ) && $definition instanceof Model\Definition\Field ) {

        $formatter = $definition->getFormatter();
        for( $i = $offset; $i < ( $offset + $limit ); ++$i ) {
          $value                                = $definition->get( $i, Operator::DEFAULT );
          $result[ $i ][ $definition->getName() ] = isset( $formatter ) ? $formatter( $value, $list, $definition, $i ) : $value;
        }
      }
    }

    return $result;
  }

  /**
   * Test input against one or more test value (with the given operator)
   *
   * @param mixed       $input
   * @param Operator    $operator
   * @param array|mixed $test
   *
   * @return bool
   */
  protected function match( $input, Operator $operator, $test ) {

    $result    = false;
    $test_list = !$operator->isAny() || !Collection::isNumeric( $test ) ? [ $test ] : $test;
    switch( $operator->getType() ) {
      case Model\Operator::DEFAULT:
        foreach( $test_list as $_value ) {
          $result = $result || ( $operator->isLoose() ? $input == $_value : $input === $_value );
        }
        break;

      //
      case Model\Operator::GREATER:
        foreach( $test_list as $_value ) {
          $result = $result || ( ( $operator->isLoose() && $input >= $_value ) || ( !$operator->isLoose() && $input > $_value ) );
        }
        break;

      //
      case Model\Operator::LESSER:
        foreach( $test_list as $_value ) {
          $result = $result || ( ( $operator->isLoose() && $input <= $_value ) || ( !$operator->isLoose() && $input < $_value ) );
        }
        break;

      //
      case Model\Operator::CONTAIN:
        $method = $operator->isLoose() ? 'stripos' : 'strpos';
        foreach( $test_list as $_value ) {
          $result = $result || $method( $input, $_value ) !== false;
        }
        break;

      //
      case Model\Operator::BEGIN:
        $method = $operator->isLoose() ? 'stripos' : 'strpos';
        foreach( $test_list as $_value ) {
          $result = $result || $method( $input, $_value ) === 0;
        }
        break;

      //
      case Model\Operator::END:
        $method = $operator->isLoose() ? 'stripos' : 'strpos';
        foreach( $test_list as $_value ) {
          $result = $result || $method( $input, $_value ) === strlen( $input ) - strlen( $_value );
        }
        break;

      //
      case Model\Operator::REGEXP:
        foreach( $test_list as $_value ) {
          $result = $result || preg_match( $input, $_value ) !== false;
        }
        break;

      // TODO implement every operator
    }

    return $operator->isNot() ? !$result : $result;
  }
  /**
   * @param array    $list
   * @param Operator $operator
   * @param          $field
   *
   * @return array
   */
  protected function sort( array $list, Operator $operator, $field ) {
    usort( $list, $operator->isNot() ? function ( $item, $_item ) use ( $field ) {
      $test  = $item[ $field ] ?? null;
      $_test = $_item[ $field ] ?? null;

      return Number::is( $test ) && Number::is( $_test ) ? (int) $_test - (int) $test : strcmp( (string) $_test, (string) $test );
    } : function ( $item, $_item ) use ( $field ) {
      $test  = $item[ $field ] ?? null;
      $_test = $_item[ $field ] ?? null;

      return Number::is( $test ) && Number::is( $_test ) ? (int) $test - (int) $_test : strcmp( (string) $test, (string) $_test );
    } );

    return $list;
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
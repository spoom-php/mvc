<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;
use Spoom\Core\Helper\Collection;
use Spoom\MVC\ModelInterface;

//
class Field extends Model\Definition {

  /**
   * Default is to include field automatically in every search and editable
   */
  const FLAG_NONE = 0;
  /**
   * Field must be manually included in searches
   */
  const FLAG_MANUAL = 1;
  /**
   * Disable modification of the field
   */
  const FLAG_STATIC = 2;
  /**
   * Required field for creating an item
   */
  const FLAG_REQUIRED = 4;

  /**
   * @var callable|null
   */
  private $_formatter;
  /**
   * @var mixed
   */
  protected $default;

  /**
   * @param string        $name
   * @param callable|null $formatter
   * @param int           $flag
   * @param mixed         $default
   *
   * @throws \InvalidArgumentException
   */
  public function __construct( string $name, ?callable $formatter = null, int $flag = self::FLAG_NONE, ?string $field = null, $default = null ) {
    parent::__construct( $name, $field, null, [ Model\Operator::DEFAULT ], $flag );

    $this->_formatter = $formatter;
    $this->default    = $default;
  }

  //
  public function __invoke( array &$_, array $__ ) {
    return false;
  }

  // There is no need to revert anything on error
  public function revert( \Throwable $_, array $__ ) {}
  // There is no need to apply the definition
  public function apply( array &$_, array $__ ) {}

  //
  public function getType() {
    return Model\Definition::FIELD;
  }
  /**
   * @return callable|null
   */
  public function getFormatter(): ?callable {
    return $this->_formatter;
  }
}

//
// TODO! Unable to handle more than one foreign field for create/update/delete due to missing revert functionality for that. It needs a backup/restore style transaction in the models
class FieldForeign extends Model\Definition {

  /**
   * @var ModelInterface
   */
  private $_model;

  /**
   * @var string
   */
  private $_foreign_key;
  /**
   * @var string|null
   */
  private $_multiple;
  /**
   * @var mixed
   */
  protected $default;

  /**
   * @var array[]
   */
  private $_search = [];

  /**
   * TODO proper documentation
   *
   * @param string         $name
   * @param string         $field       Item's property to use as a filter value for foreign_key
   * @param ModelInterface $model       Foreign model
   * @param string         $foreign_key Foreign model's filter
   * @param string|null    $multiple    empty string for simple list or the map property name
   * @param array[]        $search
   * @param int            $flag
   *
   * @throws \InvalidArgumentException
   */
  public function __construct( string $name, string $field, ModelInterface $model, string $foreign_key, ?string $multiple = null, array $search = [], int $flag = self::FLAG_NONE ) {
    parent::__construct( $name, $field, null, [ Model\Operator::DEFAULT ], $flag );

    $this->_model = $model;

    $this->_foreign_key = $foreign_key;
    $this->_multiple    = $multiple;

    $this->_search = $search + [ [], [], [] ];
  }

  //
  public function __clone() {
    parent::__clone();

    $this->_model  = clone $this->_model;
    $this->_search = Collection::copy( $this->_search );
  }
  //
  public function __invoke( array &$_, array $__ ) {
    return true;
  }

  //
  public function revert( \Throwable $_, array $__ ) {
    // TODO! handle reverts if needed
  }
  //
  public function apply( array &$list, array $_list ) {

    switch( $this->getStatement()->getMethod() ) {
      case Model::METHOD_SEARCH:

        // collect foreign keys to be able to search those in one operation
        $foreign_key_list = array_reduce( $list ?? [], function( $list, $item ) {
          if( ($tmp = $item[ $this->getField() ] ?? null) ) {
            $list[] = $tmp;
          }

          return $list;
        }, [] );

        // there must be only one slot in the list, so there is no need for a loop..
        // support only the default operator, no-no for custom operators right now
        $operator_list = $this->slot_list[ 0 ];
        if( array_key_exists( Model\Operator::DEFAULT, $operator_list ) ) {
          $foreign_map = [];

          // merge default field search params with the one added from the model
          $_search = Collection::read( $operator_list[ Model\Operator::DEFAULT ], [] ) + [ [],[],[] ];
          $search = array_reduce( [ 0, 1, 2 ], function( $list, $i ) use ( $_search ) {
            $list[ $i ] += $_search[ $i ] ?? [];
            return $list;
          }, $this->_search );

          // loop through the foreign items
          [ $filter_list, $field_list, $sort_list ] = $search;
          $foreign_list = $this->_model->set( $filter_list + [ "{$this->_foreign_key}[]" => array_unique( $foreign_key_list ) ], $field_list, $sort_list )->search();
          foreach( $foreign_list as $foreign_item ) {

            $key_list = $foreign_item[ $this->_foreign_key ] ?? [];
            foreach( Collection::read( $key_list, [ $key_list ] ) as $key ) {
              if( $this->_multiple === null ) $foreign_map[ $key ] = $foreign_map[ $key ] ?? $foreign_item;
              else {

                $foreign_map[ $key ] = $foreign_map[ $key ] ?? [];
                if( empty( $this->_multiple ) ) $foreign_map[ $key ][] = $foreign_item;
                else $foreign_map[ $key ][ $foreign_item[ $this->_multiple ] ] = $foreign_item;
              }
            }
          }

          // fill up items with their foreign ones
          foreach( $list as &$item ) {
            $tmp = $item[ $this->getField() ] ?? null;
            if( isset( $foreign_map[ $tmp ] ) ) $item[ $this->getName() ] = $foreign_map[ $tmp ];
            else $item[ $this->getName() ] = $this->_multiple !== null ? [] : null;
          }
        }

        break;

      case Model::METHOD_CREATE:

        // loop through every raw item passed to the create method
        $create_list = [];
        foreach( $this->slot_list as $i => $operator_list ) {
          $item_field = $list[ $i ][ $this->getField() ] ?? null;

          // support only the default operator, no-no for custom operators right now
          if( $item_field !== null && array_key_exists( Model\Operator::DEFAULT, $operator_list ) ) {

            $item_list = $operator_list[ Model\Operator::DEFAULT ];
            if( $item_list !== null ) {

              $create_list = [];
              $item_list = $this->_multiple === null ? [ $item_list ] : $item_list;
              foreach( $item_list as $_key => $item ) {
                if( $item !== null ) {

                  $_item = $item + [ $this->_foreign_key => $item_field ];
                  if( !empty( $this->_multiple ) ) {
                    $_item[ $this->_multiple ] = $_key;
                  }

                  $create_list[] = $_item;
                }
              }

              if( !empty( $create_list ) ) {
                $this->_model->set( [], $create_list )->create( 0 );
              }
            }
          }
        }

      break;

      case Model::METHOD_UPDATE:

        // get the primary key without the foreign field, which is used as a separate filter later. This will allow limited supports for
        // multi-key models, because the foreign field part of the key doesn't matter in this case, we can still filter securly on update
        // and remove operations
        $key = array_filter( $this->_model->getKey(true), function( $tmp ) { return $tmp !== $this->_foreign_key; } );
        if( count( $key ) > 1 ) throw new \LogicException("Can't update foreign field with multi-primary models");
        $key = array_pop( $key );

        // loop through every raw item passed to the update method
        foreach( $this->slot_list as $i => $operator_list ) {
          $item_field = $list[ $i ][ $this->getField() ] ?? null;

          // support only the default operator, no-no for custom operators right now
          if( $item_field !== null && array_key_exists( Model\Operator::DEFAULT, $operator_list ) ) {

            $item_list = $operator_list[ Model\Operator::DEFAULT ];
            if( $item_list === null ) $this->_model->set( [ $this->_foreign_key => $item_field ] )->remove();
            else {

              $create_list = [];
              $item_list = $this->_multiple === null ? [ $item_list ] : $item_list;
              foreach( $item_list as $_key => $item ) {
                if( $item === null ) ; // TODO this should support removing items from the foreign model
                else {

                  $_item = $item + [ $this->_foreign_key => $item_field ];
                  if( !empty( $this->_multiple ) ) {
                    $_item[ $this->_multiple ] = $_key;
                  }

                  $create_list[] = $_item;
                }
              }

              if( !empty( $create_list ) ) {
                $result_list = $this->_model->set( [], $create_list )->create( 0 );
                $this->_model->set( [ "{$this->_foreign_key}[]" => $item_field, "{$key}[]!=" => array_column( $result_list, $key ) ] )->remove();
              }
            }
          }
        }

      break;

      // we should remove every foreign element on ===null or remove specific items on actual values
      case Model::METHOD_REMOVE:

        // TODO! this should remove everything only if the values === NULL like in the UPDATE

        // TODO this should filter out NULL values, not everything like FALSE
        $key_list = array_filter( array_map( function( $item ) { return $item[ $this->getField() ] ?? null; }, $list ?? [] ) );

        $this->_model->set( [ "{$this->_foreign_key}[]" => $key_list ] )->remove();

      break;
    }
  }

  //
  public function getType() {
    return Model\Definition::FIELD;
  }
}
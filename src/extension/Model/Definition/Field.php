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
  public function __construct( string $name, ?callable $formatter = null, int $flag = self::FLAG_NONE, $default = null ) {
    parent::__construct( $name, null, [], $flag );

    $this->_formatter = $formatter;
    $this->default    = $default;
  }

  //
  public function execute( array $list, array $_list = [] ): array {

    foreach( $_list as $i => $_item ) {
      foreach( ( $this->slot_list[ $i ] ?? $this->slot_list[ 0 ] ?? [] ) as $operator => $value ) {

        $_value                         = $_item[ $this->getName() ] ?? null;
        $formatter                      = $this->_formatter ?? null;
        $list[ $i ][ $this->getName() ] = $formatter ? $formatter( $_value, $_list, $this, isset( $this->slot_list[ $i ] ) ? $i : 0 ) : $_value;
      }
    }

    return $list;
  }

  //
  public function getType() {
    return Model\Definition::FIELD;
  }
}

//
class FieldForeign extends Field {

  /**
   * @var ModelInterface
   */
  private $_model;

  /**
   * @var string
   */
  private $_key;
  /**
   * @var string
   */
  private $_foreign_key;
  /**
   * @var string|null
   */
  private $_multiple;

  /**
   * @var array[]
   */
  private $_search = [];

  /**
   * TODO proper documentation
   *
   * @param string         $name
   * @param ModelInterface $model       Foreign model
   * @param string         $key         Item's property to use as a filter value
   * @param string         $foreign_key Foreign model's filter
   * @param string|null    $multiple    empty string for simple list or the map property name
   * @param array          $default
   * @param array[]        $search
   * @param int            $flag
   *
   * @throws \InvalidArgumentException
   */
  public function __construct( string $name, ModelInterface $model, string $key, string $foreign_key, ?string $multiple = null, array $default = [], array $search = [], int $flag = self::FLAG_NONE ) {
    parent::__construct( $name, null, $flag, $default );

    $this->_model = $model;

    $this->_key         = $key;
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
  public function execute( array $list, array $_list = [] ): array {

    // collect connection keys
    $keys = [];
    foreach( $list as $item ) {
      $tmp = $item[ $this->_key ];
      if( isset( $tmp ) ) $keys[] = $tmp;
    }

    $search = $this->_search;
    foreach( $this->slot_list as $slot => $operator_list ) {
      foreach( $operator_list as $operator => $value ) {

        $value = $value !== null ? $value : $this->default;
        if( Collection::is( $value ) ) {
          $search = Collection::merge( $search, $value );
        }

        $connection_list = [];
        $tmp             = $this->_model->set( $search[ 0 ] + [ $this->_foreign_key => array_unique( $keys ) ], $search[ 1 ], $search[ 2 ] )->search();
        foreach( $tmp as $t ) {

          $key_list = $t[ Model::definition( $this->_foreign_key ) ];
          foreach( Collection::read( $key_list, [ $key_list ] ) as $key ) {
            if( $this->_multiple === null ) $connection_list[ $key ] = isset( $connection_list[ $key ] ) ? $connection_list[ $key ] : $t;
            else {

              //
              if( !isset( $connection_list[ $key ] ) ) {
                $connection_list[ $key ] = [];
              }

              if( empty( $this->_multiple ) ) $connection_list[ $key ][] = $t;
              else $connection_list[ $key ][ $t[ $this->_multiple ] ] = $t;
            }
          }
        }

        // fill items with their connected items
        foreach( $list as &$item ) {
          $tmp = $item[ $this->_key ];
          if( isset( $connection_list[ $tmp ] ) ) $item[ $this->getName() ] = $connection_list[ $tmp ];
          else $item[ $this->getName() ] = isset( $this->_multiple ) ? [] : null;
        }
      }
    }

    return $list;
  }
}
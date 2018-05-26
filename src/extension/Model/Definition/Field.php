<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;
use Spoom\Core\Helper\Collection;
use Spoom\MVC\ModelInterface;

//
class Field extends Model\Definition {

  /**
   * Clear all flags
   */
  const FLAG_NONE = 0;
  /**
   * Auto included field for search
   */
  const FLAG_AUTO = 1;
  /**
   * Required field for create
   */
  const FLAG_REQUIRED = 2;
  /**
   * Enable to update the field on update operations
   */
  const FLAG_WRITEABLE = 4;

  /**
   * Editable on update, optional on create
   */
  const FLAG_AW = self::FLAG_AUTO | self::FLAG_WRITEABLE;
  /**
   * Required to create, but after it's read-only
   */
  const FLAG_AR = self::FLAG_AUTO | self::FLAG_REQUIRED;
  /**
   * Required to create, but it's editable on update too
   */
  const FLAG_ARW = self::FLAG_AUTO | self::FLAG_WRITEABLE | self::FLAG_REQUIRED;

  /**
   * @var int
   */
  private $_flag;
  /**
   * @var callable|null
   */
  private $_formatter;
  /**
   * @var string
   */
  private $_field;

  /**
   * @param string        $field
   * @param callable|null $formatter
   * @param int           $flag
   */
  public function __construct( string $field, ?callable $formatter = null, int $flag = self::FLAG_AW ) {

    $this->_field     = $field;
    $this->_formatter = $formatter;
    $this->_flag      = $flag;
  }

  //
  public function __clone() { }
  //
  public function __invoke( array $list, array $_list = [] ): array {

    foreach( $_list as $i => $_item ) {
      foreach( ( $this->slot_list[ $i ] ?? $this->slot_list[ 0 ] ?? [] ) as $operator => $value ) {

        $_value                         = $_item[ $this->getName() ] ?? null;
        $formatter                      = $this->_formatter ?? null;
        $list[ $i ][ $this->getName() ] = $formatter ? $formatter( $_value, $_list, $this->_statement ) : $_value;
      }
    }

    return $list;
  }

  /**
   * @return int
   */
  public function getFlag(): int {
    return $this->_flag;
  }
  /**
   * @param int $value
   *
   * @return static
   */
  public function setFlag( int $value ) {

    $this->_flag = $value;
    return $this;
  }

  //
  public function getType() {
    return Model::DEFINITION_FIELD;
  }
  //
  public function getName() {
    return $this->_field;
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
  private $_name;
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
   * @param string         $field
   * @param ModelInterface $model       Foreign model
   * @param string         $key         Item's property to use as a filter value
   * @param string         $foreign_key Foreign model's filter
   * @param string|null    $multiple    empty string for simple list or the map property name
   * @param array[]        $search
   * @param int            $flag
   */
  public function __construct( string $field, ModelInterface $model, string $key, string $foreign_key, ?string $multiple = null, array $search = [], int $flag = self::FLAG_NONE ) {
    parent::__construct( $field, null, $flag );

    $this->_name  = $field;
    $this->_model = $model;

    $this->_key         = $key;
    $this->_foreign_key = $foreign_key;
    $this->_multiple    = $multiple;

    $this->_search = $this->_search + [ [], [], [] ];
  }

  //
  public function __clone() {
    $this->_model  = clone $this->_model;
    $this->_search = Collection::copy( $this->_search );
  }

  //
  public function __invoke( array $list, array $_list = [] ): array {

    // collect connection keys
    $keys = [];
    foreach( $list as $item ) {
      $tmp = $item[ $this->_key ];
      if( isset( $tmp ) ) $keys[] = $tmp;
    }

    $search = $this->_search;
    foreach( $this->slot_list as $slot => $operator_list ) {
      foreach( $operator_list as $operator => $value ) {

        if( Collection::is( $value ) ) {
          $search = Collection::merge( $search, $value );
        }

        $connection_list = [];
        $tmp             = $this->_model->set( $search[ 0 ] + [ $this->_foreign_key => array_unique( $keys ) ], $search[ 1 ], $search[ 2 ] )->search();
        foreach( $tmp as $t ) {

          $key_list = $t[ Model::operator( $this->_foreign_key ) ];
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
          if( isset( $connection_list[ $tmp ] ) ) $item[ $this->_name ] = $connection_list[ $tmp ];
          else $item[ $this->_name ] = isset( $this->_multiple ) ? [] : null;
        }
      }
    }

    return $list;
  }
}
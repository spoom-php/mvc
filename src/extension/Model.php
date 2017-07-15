<?php namespace Spoom\MVC;

use Spoom\Core\Helper\Collection;
use Spoom\MVC\Model\ItemInterface;

/**
 * Interface ModelInterface
 */
interface ModelInterface {

  const DEFINITION_FILTER = 'filter';
  const DEFINITION_FIELD  = 'field';
  const DEFINITION_SORT   = 'sort';
  const DEFINITION_LIMIT  = 'limit';

  /**
   * Extract a key data from the context
   *
   * @param array|object $context
   *
   * @return array|null
   */
  public function key( $context ): ?array;
  /**
   * Create an (empty) statement
   *
   * @param array|null $filter Initial filters in the statement
   *
   * @return StatementInterface
   */
  public function statement( ?array $filter = null ): StatementInterface;
  /**
   * Create an item object
   *
   * With or without predefined data
   *
   * @param array|null $context Initial data if any
   * @param array|null $key     Key of the item if any
   *
   * @return ItemInterface
   */
  public function item( ?array $context = null, ?array $key = null ): ItemInterface;

  /**
   * Post-process data after a read operation
   *
   * @param array $context
   *
   * @return array The processed data
   */
  public function unwrap( array $context ): array;
  /**
   * Pre-process item's data before write operations
   *
   * @param array              $context
   * @param StatementInterface $statement
   *
   * @return array The processed data to use in the model
   */
  public function wrap( array $context, StatementInterface $statement ): array;

  /**
   * Get the key definitions
   *
   * Every list item is a key definition that has multiple property names. All of them (in a key) must exists and match to define the item
   *
   * @return array[]
   */
  public function getKey(): array;
  /**
   * Get filter/field/sort or limit definitions
   *
   * @param string|null $type Definition's type or ===null to all definition
   *
   * @return array
   */
  public function getDefinition( string $type = null ): array;
}
/**
 * Class Model
 */
abstract class Model implements ModelInterface {

  /**
   * Multiple key definitions in order (first is the most relevant)
   *
   * @var array[]
   */
  protected $_key;

  /**
   * @var callable[][]
   */
  protected $_definition = [
    self::DEFINITION_FIELD  => [],
    self::DEFINITION_FILTER => [],
    self::DEFINITION_SORT   => [],
    self::DEFINITION_LIMIT  => []
  ];

  //
  public function key( $context ): ?array {

    //
    $context = Collection::read( $context, [] );
    foreach( $this->_key as $keys ) {

      $result = [];
      foreach( $keys as $key ) {
        if( !isset( $context[ $key ] ) ) break;
        else $result[ $key ] = $context[ $key ];
      }

      if( count( $result ) == count( $keys ) ) {
        return $result;
      }
    }

    return null;
  }

  //
  public function getKey(): array {
    return $this->_key;
  }
  //
  public function getDefinition( string $type = null ): array {
    return $type === null ? $this->_definition : ( $this->_definition[ $type ] ?? [] );
  }

  //
  public function getItem( $context = null, $exist = null ) {
    return new Model\Item( $this, $context, $exist );
  }

  /**
   * Add a new filter/field or sort definition to the model
   *
   * @param string   $name
   * @param string   $type
   * @param callable $definition
   *
   * @throws \InvalidArgumentException
   */
  protected function addDefinition( string $name, string $type, callable $definition ) {
    if( !isset( $this->_definition[ $type ] ) ) throw new \InvalidArgumentException( 'Type must be: ' . implode( ', ', array_keys( $this->_definition ) ) );
    else $this->_definition[ $type ][ $name ] = $definition;
  }
}

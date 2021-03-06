<?php namespace Spoom\MVC;

use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper\Number;
use Spoom\MVC\Model\Definition\Field;
use Spoom\MVC\Model\DefinitionInterface;

/**
 * TODO model should support aggregations
 * TODO model should support backup/restore
 */
interface ModelInterface {

  const METHOD_SEARCH = 'search';
  const METHOD_CREATE = 'create';
  const METHOD_UPDATE = 'update';
  const METHOD_REMOVE = 'remove';

  const FIELD_ALL = [ '*' ];

  /**
   * Create item(s)
   *
   * with the stored field set(s)
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return array List with the created or modified item's keys, in the order of the provided field slots
   */
  public function create( int $limit = 1, int $offset = 0, bool $reset = true ): array;
  /**
   * Remove item(s)
   *
   * that match the stored filters and inside the given limits (and sort)
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return array List with the removed item's keys, in the order of the remove
   */
  public function remove( int $limit = 0, int $offset = 0, bool $reset = true ): array;
  /**
   * Update item(s)
   *
   * that match the stored filters and inside the given limits (and sort)
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return array List with the modified item's keys, in the order of the update
   */
  public function update( int $limit = 0, int $offset = 0, bool $reset = true ): array;
  /**
   * Load an array of items
   *
   * with the given filters, sort and limitation
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return array[] Zero indexed list with the items data as any associative array
   */
  public function search( int $limit = 0, int $offset = 0, bool $reset = true ): array;

  /**
   * Read only one field's value from an item
   *
   * @param string $name The field name to get
   * @param int    $offset
   * @param bool   $reset
   *
   * @return mixed
   */
  public function field( string $name, int $offset = 0, bool $reset = true );
  /**
   * Get a list of field values from a list of items
   *
   * @param string $name The field name to get
   * @param int    $limit
   * @param int    $offset
   * @param bool   $reset
   *
   * @return array
   */
  public function fieldList( string $name, int $limit = 0, int $offset = 0, bool $reset = true ): array;

  /**
   * Create or update large number of items with different data
   *
   * @param array $shared Common data for every data element
   * @param int   $chunk  Limit the items for iterated creation
   * @param bool  $reset  Reset the internal state, after the command
   *
   * @return array Array of new/updated item's keys
   */
  public function batch( array $shared = [], int $chunk = 0, bool $reset = true ): array;
  /**
   * Get one item from the Model
   *
   * @param int  $offset The returned item offset in the result array
   * @param bool $reset  Reset the internal state, after the command
   *
   * @return null|array
   */
  public function get( int $offset = 0, bool $reset = true ): ?array;
  /**
   * Process a huge search result chunked to prevent memory issues
   *
   * @param callable $callback Handler for every chunk. The callable first parameter is the actual chunk, the second is the offset
   * @param int      $chunk    Chuck size. Cannot be non-positive
   * @param int      $limit    Upper limit of the looped items
   * @param int      $offset   Skiped items from the beginning of the list
   * @param bool     $reset    Reset the internal state, after the command
   *
   * @return static
   */
  public function each( callable $callback, int $chunk, int $limit = 0, int $offset = 0, bool $reset = true );
  /**
   * Count the result
   *
   * @param bool $reset Reset the internal state, after the command
   *
   * @return int
   */
  public function count( bool $reset = true ): int;

  /**
   * Extract a key data from the context
   *
   * @param array|object $context
   * @param bool         $primary Check only for primary key
   *
   * @return array|null
   */
  public function key( $context, bool $primary = false ): ?array;
  /**
   * Set (or clear) internal state in one command
   *
   * @param array|null $filter Alias of setFilter (or removeFilter if ===null)
   * @param array|null $field  Alias of setField (or removeField if ===null)
   * @param array|null $sort   Alias of setSort (or removeSort if ===null)
   *
   * @return static
   */
  public function set( ?array $filter = null, ?array $field = null, ?array $sort = null );
  /**
   * Create a new statement
   *
   * @param string $method
   *
   * @return Model\StatementInterface
   */
  public function statement( string $method ): Model\StatementInterface;

  /**
   * Get a(ll) field(s) from slot(s)
   *
   * @param int|null    $slot Specific slot or every slot
   * @param string|null $name A specific field in a slot. Cannot be used with $slot===null
   *
   * @return array|array[]|mixed Fields, a list of fields
   */
  public function getField( ?int $slot = null, ?string $name = null );
  /**
   * Set the fields
   *
   * The array will be reindexed from zero
   *
   * @param array[] $list List of associative arrays of field names (key) and their meta (value). Every list item is a slot
   *
   * @return static
   */
  public function setField( array $list );
  /**
   * Add or set fields for a slot
   *
   * @param array    $list  Associative list of fields (key) and their meta (value)
   * @param bool     $merge Set or merge the slot
   * @param int|null $slot  NULL means new slot
   *
   * @return static
   */
  public function addField( array $list, bool $merge = true, ?int $slot = 0 );
  /**
   * Remove field(s)
   *
   * @param string[]|null $list Fields to remove (===null to all)
   * @param int|null      $slot Remove from a specific slot or every (===null) slot
   *
   * @return static
   */
  public function removeField( ?array $list = null, ?int $slot = null );

  /**
   * Get filters
   *
   * TODO add ability to get a filter with every operator in the filters (maybe a getFilterOperator() method?)
   *
   * @param string|null $name Get a specific filter identified by name (with the operator)
   *
   * @return mixed
   */
  public function getFilter( ?string $name = null );
  /**
   * Set the filters
   *
   * An associative array of filters (with AND relation to each other). Filter names can
   * contain operator (default: Input\Filter::OPERATOR_EQUAL)
   *
   * @param array $list  Associative array of filters with AND relations
   * @param bool  $merge Replace or extend the existed filters
   *
   * @return static
   */
  public function setFilter( array $list, bool $merge = false );
  /**
   * Add or replace a filter
   *
   * @param string $name     The filter's name. It CAN'T contain operator
   * @param mixed  $value    The data for the filter
   * @param string $operator Operator to apply on filter use
   *
   * @return static
   */
  public function addFilter( string $name, $value, string $operator = Model\Operator::DEFAULT );
  /**
   * Remove filter(s) from the statement
   *
   * It will clear all filters if $names parameter is
   *
   * @param null|string[] $list By names (can contain operator. Default: Input\Filter::OPERATOR_EQUAL)
   *
   * @return static
   */
  public function removeFilter( ?array $list = null );

  /**
   * Get the list of sorts (in order, and with optional reverse prefix)
   *
   * @return string[]
   */
  public function getSort(): array;
  /**
   * Set the sorts
   *
   * @param array $list
   *
   * @return static
   */
  public function setSort( array $list );
  /**
   * Add sort for the statement
   *
   * @param string $name
   * @param bool   $reverse
   *
   * @return static
   */
  public function addSort( string $name, bool $reverse = false );
  /**
   * Remove (all) sort
   *
   * @param string[]|null $list
   *
   * @return static
   */
  public function removeSort( ?array $list = null );

  /**
   * Get the key definitions
   *
   * Every list item is a key definition that has multiple property names. All of them (in a key) must exists and match to define the item
   *
   * @param bool $primary Get only the primary key
   *
   * @return array
   */
  public function getKey( $primary = false ): array;
  /**
   * Get filter/field/sort definitions
   *
   * @param string|null $type Definition's type or ===null to all definition
   *
   * @return array<string,array<string,DefinitionInterface>>|array<string,DefinitionInterface>
   */
  public function getDefinitionList( string $type = null ): array;
  /**
   * Get filter/field/sort definitions
   *
   * @param string $type
   * @param string $name
   *
   * @return DefinitionInterface
   * @throws \InvalidArgumentException
   * @throws \LogicException
   */
  public function getDefinition( string $type, string $name ): DefinitionInterface;
}
//
abstract class Model implements ModelInterface {
  // TODO there should be a __clone implementation to prevent linked data problems, but be careful with the performance impact

  /**
   * Multiple key definitions in order (first is the primary key)
   *
   * @var array[]
   */
  private $_key = [];

  /**
   * @var DefinitionInterface[][]
   */
  protected $_definition = [
    Model\Definition::FIELD  => [],
    Model\Definition::FILTER => [],
    Model\Definition::SORT   => []
  ];

  /**
   * List of associative array of fields (key) and their meta (value)
   *
   * @var array[]
   */
  private $_field = [];
  /**
   * Associative list of filters (key, with operator prefix) and their data (value)
   *
   * @var array
   */
  private $_filter = [];
  /**
   * List of applied sorts (in order), with reverse operator prefix
   *
   * @var string[]
   */
  private $_sort = [];

  //
  public function create( int $limit = 1, int $offset = 0, bool $reset = true ): array {
    $result = $this->invoke( static::METHOD_CREATE, $limit, $offset );

    if( $reset ) $this->set();
    return $result;
  }
  //
  public function update( int $limit = 0, int $offset = 0, bool $reset = true ): array {
    $result = $this->invoke( static::METHOD_UPDATE, $limit, $offset );

    if( $reset ) $this->set();
    return $result;
  }
  //
  public function remove( int $limit = 0, int $offset = 0, bool $reset = true ): array {
    $result = $this->invoke( static::METHOD_REMOVE, $limit, $offset );

    if( $reset ) $this->set();
    return $result;
  }
  //
  public function search( int $limit = 0, int $offset = 0, bool $reset = true ): array {
    $result = $this->invoke( static::METHOD_SEARCH, $limit, $offset );

    if( $reset ) $this->set();

    // `->invoke()` tries to keep the item indexes of the input fields, but in the search that doesn't matter (there must be only one field slot anyway)
    // and it's easier to use the result list from zero indexes later
    return array_values( $result );
  }

  //
  public function field( string $name, int $offset = 0, bool $reset = true ) {

    $result = $this->fieldList( $name, 1, $offset, $reset );
    return !empty( $result ) ? $result[0] : null;
  }
  //
  public function fieldList( string $name, int $limit = 0, int $offset = 0, bool $reset = true ): array {

    $list = $this->addField( [ $name ] )->search( $limit, $offset, $reset );
    return Collection::mapList( $list, $name );
  }

  //
  public function batch( array $shared = [], int $chunk = 0, bool $reset = true ): array {

    $statement = clone $this;
    for( $i = 0, $length = count( $statement->getField() ); $i < $length; ++$i ) {
      $statement->addField( $shared, true, $i );
    }

    $result = [];
    if( $chunk < 0 ) throw new \InvalidArgumentException( "Chunk ({$chunk}) must be greater (or equal) than zero" );
    else if( empty( $chunk ) ) $result = $statement->create();
    else {

      $offset = 0;
      $limit  = count( $this->getField() );
      do {

        $next = ( $limit - $offset ) < $chunk ? ( $limit - $offset ) : $chunk;
        $list = $this->create( $next, $offset );

        $result = Collection::merge( $result, $list );
        $offset += $next;

      } while( $limit > $offset );
    }

    if( $reset ) $this->set();
    return $result;
  }
  //
  public function get( int $offset = 0, bool $reset = true ): ?array {

    $result = $this->search( 1, $offset, $reset );
    return !empty( $result ) ? $result[0] : null;
  }
  //
  public function each( callable $callback, int $chunk, int $limit = 0, int $offset = 0, bool $reset = true ) {

    // validate the chunk size
    if( $chunk < 1 ) throw new \InvalidArgumentException( "Chunk ({$chunk}) must be greater than zero" );
    else if( $limit < 0 || $offset < 0 ) throw new \InvalidArgumentException( "Limit ({$limit}) and offset ({$offset}) must be greater (or equal) than zero" );
    else {

      $_offset = 0;
      do {

        $next = $limit && ( $limit - $_offset ) < $chunk ? ( $limit - $_offset ) : $chunk;
        $list = $this->search( $next, $_offset + $offset, false );
        if( !empty( $list ) ) {
          if( $callback( $list, $_offset ) === false ) break;
          else $_offset += $next;
        }

      } while( !empty( $list ) && ( $limit < 1 || $limit > ( $_offset + $offset ) ) );
    }

    if( $reset ) $this->set();
    return $this;
  }
  //
  public function count( bool $reset = true ): int {
    return count( $this->search( 0, 0, $reset ) );
  }

  //
  public function key( $context, bool $primary = false ): ?array {

    //
    $context = Collection::cast( $context, [] );
    foreach( $this->_key as $i => $keys ) {

      // $this->_key[0] is the primary key
      if( $primary && $i > 0 ) break;

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
  public function set( ?array $filter = null, ?array $field = null, ?array $sort = null ) {

    $filter === null ? $this->removeFilter() : $this->setFilter( $filter );
    $field === null ? $this->removeField() : $this->setField( $field );
    $sort === null ? $this->removeSort() : $this->setSort( $sort );

    return $this;
  }


  /**
   * Invoke statement based on the model
   *
   * This will use the current model's data, and the provided limits to run the method
   *
   * @param string $method
   * @param int    $limit
   * @param int    $offset
   *
   * @return array
   * @throws \InvalidArgumentException When the offset or the limit less than zero
   */
  protected function invoke( string $method, int $limit = 0, int $offset = 0 ): array {

    if( $limit < 0 || $offset < 0 ) throw new \InvalidArgumentException( "Limit ({$limit}) and offset ({$offset}) must be greater (or equal) than zero" );
    else {

      $statement = $this->statement( $method );
      return $statement( $limit, $offset );
    }
  }

  //
  public function getField( ?int $slot = null, ?string $name = null ) {

    if( $slot === null ) return $this->_field;
    else {

      $list = $this->_field[ $slot ] ?? [];
      return $name === null ? $list : ( $list[ $name ] ?? null );
    }
  }
  //
  public function setField( array $list ) {

    // check for numerical outer array
    if( !Collection::isNumeric( $list ) ) throw new \InvalidArgumentException( 'List must be a numeric array' );
    else {

      // add every inner arrays
      $slot = 0;
      foreach( $list as $field_list ) {
        $this->addField( $field_list, false, $slot++ );
      }
    }

    return $this;
  }
  //
  public function addField( array $list, bool $merge = true, ?int $slot = 0 ) {
    $slot = $slot === null ? count( $this->_field ) : $slot;

    // handle adding of all fields
    if( $list === static::FIELD_ALL ) {

      $list = [];
      foreach( $this->getDefinitionList( DefinitionInterface::FIELD ) as $field ) {
        /** @var Definition\Field $field */

        // skip any field that has already added to prevent value overwrites
        if( $this->getField( $slot, $field->getName() ) === null ) {
          $list[ $field->getName() ] = null;
        }
      }
    }

    // pre-process list to handle simple field definitions
    $_list = [];
    foreach( $list as $key => $value ) {
      if( Number::is( $key, true ) ) $_list[ $value ] = null;
      else $_list[ $key ] = $value;
    }

    //
    if( !empty( $_list ) ) {
      $this->_field[ $slot ] = $_list + ( $merge ? ( $this->_field[ $slot ] ?? [] ) : [] );
    }

    return $this;
  }
  //
  public function removeField( ?array $list = null, ?int $slot = null ) {

    // handle simple versions (clear all, and all in a slot)
    if( $list === null && $slot === null ) $this->_field = [];
    else if( $list === null ) unset( $this->_field[ $slot ] );
    else {

      foreach( $list as $name ) {
        if( $slot !== null ) {
          unset( $this->_field[ $slot ][ $name ] );

          // removes the slot if it's become empty
          if( empty( $this->_field[ $slot ] ) ) {
            unset( $this->_field[ $slot ] );
          }

        } else {

          foreach( $this->_field as $i => $_ ) {
            unset( $this->_field[ $i ][ $name ] );

            // removes the slot if it's become empty
            if( empty( $this->_field[ $i ] ) ) {
              unset( $this->_field[ $i ] );
            }
          }
        }
      }
    }

    return $this;
  }

  //
  public function getFilter( ?string $name = null ) {
    return $name === null ? $this->_filter : ( $this->_filter[ $name ] ?? null );
  }
  //
  public function setFilter( array $list, bool $merge = false ) {

    $this->_filter = $merge ? ( $list + $this->_filter ) : $list;
    return $this;
  }
  //
  public function addFilter( string $name, $value, string $operator = Model\Operator::DEFAULT ) {

    $this->_filter[ Model\Operator::attach( $operator, $name ) ] = $value;
    return $this;
  }
  //
  public function removeFilter( ?array $names = null ) {

    if( $names === null ) $this->_filter = [];
    else {

      foreach( $names as $name ) {
        unset( $this->_filter[ $name ] );
      }
    }

    return $this;
  }

  //
  public function getSort(): array {
    return $this->_sort;
  }
  //
  public function setSort( array $list ) {

    $this->_sort = [];
    foreach( $list as $name ) {

      // force stringify and prevent duplicates (keep only the latest one)
      $name = (string) $name;
      $this->removeSort( [ $name ] );

      $this->_sort[] = $name;
    }

    return $this;
  }
  //
  public function addSort( string $name, bool $reverse = false ) {

    // prepend operator and prevent duplicates (keep only the latest one)
    $operator = $reverse ? Model\Operator::FLAG_NOT : Model\Operator::DEFAULT;
    $name     = Model\Operator::attach( $operator, $name );
    $this->removeSort( [ $name ] );

    $this->_sort[] = $name;
    return $this;
  }
  //
  public function removeSort( ?array $list = null ) {

    if( $list === null ) $this->_sort = [];
    else {

      foreach( $list as $name ) {
        if( ( $tmp = array_search( $name, $this->_sort ) ) !== false ) {
          array_splice( $this->_sort, $tmp, 1 );
        }
      }
    }

    return $this;
  }

  //
  public function getKey( $primary = false ): array {
    return $primary ? $this->_key[ 0 ] : $this->_key;
  }
  /**
   * Set the keys for the model
   *
   * The first key is the primary. Every key value must have a filter definition
   *
   * @param array[] $list
   *
   * @return $this
   * @throws \LogicException
   */
  protected function setKey( array $list ) {

    foreach( $list as $key_list ) {
      foreach( $key_list as $key ) {

        // this will throw an exception if the filter is not available
        $this->getDefinition( DefinitionInterface::FILTER, $key );
      }
    }

    $this->_key = $list;
    return $this;
  }

  //
  public function getDefinitionList( string $type = null ): array {
    if( $type === null ) return $this->_definition;
    else if( !isset( $this->_definition[ $type ] ) ) throw new \InvalidArgumentException( 'Type must be: ' . implode( ', ', array_keys( $this->_definition ) ) );
    else return $this->_definition[ $type ];
  }
  /**
   * Make a definition list for the $method type
   *
   * This should be used to push definition list into Statement's
   *
   * @param string $method
   *
   * @return array<string,array<string,DefinitionInterface>>
   * @throws \InvalidArgumentException
   * @throws \LogicException
   */
  protected function makeDefinitionList( string $method ): array {
    $model = clone $this;
    $definition_list = Collection::copy( $model->getDefinitionList() );

    switch( $method ) {
      //
      case static::METHOD_SEARCH:

        // add default fields that is not already in the model
        foreach( $definition_list[ Model\Definition::FIELD ] as $field ) {
          if( !( $field->getFlag() & Field::FLAG_MANUAL ) && !$model->getField( 0, $field->getName() ) ) {
            // TODO add support for a default value
            $model->addField( [ $field->getName() => null ] );
          }
        }

        break;

      case static::METHOD_CREATE:

        // add default create values for fields that is flagged as required and not already in the model
        foreach( $definition_list[ Model\Definition::FIELD ] as $field ) {
          if( $field->getFlag() & Field::FLAG_REQUIRED ) {

            $slot_list = $model->getField();
            foreach( $slot_list as $slot => $_field_list ) {
              if( !isset( $_field_list[ $field->getName() ] ) ) {
                // TODO add support for a default value
                $model->addField( [ $field->getName() => null ], true, $slot );
              }
            }
          }
        }

        break;

      case static::METHOD_UPDATE:

        // remove fields that are not updatable
        foreach( $definition_list[ Model\Definition::FIELD ] as $field ) {
          if( ( $field->getFlag() & Field::FLAG_STATIC ) && $model->getField( 0, $field->getName() ) ) {
            $model->removeField( [ $field->getName() ], 0 );
          }
        }

        break;
    }

    // add fields from the model into the statement
    foreach( $model->getField() as $slot => $field_list ) {
      foreach( $field_list as $name => $value ) {
        $operator   = null;
        $definition = $definition_list[ Model\Definition::FIELD ][ Model\Operator::detach( $name, $operator ) ] ?? null;
        if( empty( $definition ) ) throw new \LogicException( "There is no definition for '{$name}' " . Model\Definition::FIELD . " in " . get_class( $model ) );
        else $definition->set( $value, $operator, $slot );
      }
    }

    switch( $method ) {
      case static::METHOD_UPDATE:
      case static::METHOD_REMOVE:
      case static::METHOD_SEARCH:

        //
        foreach( $model->getFilter() as $name => $value ) {
          $operator   = null;
          $definition = $definition_list[ Model\Definition::FILTER ][ Model\Operator::detach( $name, $operator ) ] ?? null;
          if( empty( $definition ) ) throw new \LogicException( "There is no definition for '{$name}' " . Model\Definition::FILTER . " in " . get_class( $model ) );
          else $definition->set( $value, $operator );
        }

        //
        foreach( $model->getSort() as $name ) {
          $operator   = null;
          $definition = $definition_list[ Model\Definition::SORT ][ Model\Operator::detach( $name, $operator ) ] ?? null;
          if( empty( $definition ) ) throw new \LogicException( "There is no definition for '{$name}' " . Model\Definition::SORT . " in " . get_class( $model ) );
          else $definition->set( $value, $operator );
        }

        break;
    }

    // remove empty definitions
    foreach( $definition_list as $type => $list ) {
      foreach( $list as $name => $definition ) {
        if( empty( $definition->get() ) ) {
          unset( $definition_list[ $type ][ $name ] );
        }
      }
    }

    return $definition_list;
  }
  /**
   * Add multiple definition to the model
   *
   * @param DefinitionInterface[] $definition_list
   *
   * @throws \InvalidArgumentException
   */
  protected function addDefinitionList( array $definition_list ) {
    foreach( $definition_list as $definition ) {
      $this->addDefinition( $definition );
    }
  }

  //
  public function getDefinition( string $type, string $name ): DefinitionInterface {

    $list = $this->getDefinitionList( $type );
    if( !isset( $list[ $name ] ) ) throw new \LogicException( "There is no definition for '{$name}' {$type} in " . get_class( $this ) );
    else return $list[ $name ];
  }
  /**
   * Add a new definition to the model
   *
   * @param DefinitionInterface $definition
   *
   * @throws \InvalidArgumentException
   */
  protected function addDefinition( DefinitionInterface $definition ) {
    if( !isset( $this->_definition[ $definition->getType() ] ) ) throw new \InvalidArgumentException( 'Type must be: ' . implode( ', ', array_keys( $this->_definition ) ) );
    else $this->_definition[ $definition->getType() ][ $definition->getName() ] = $definition;
  }
}
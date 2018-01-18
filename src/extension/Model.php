<?php namespace Spoom\MVC;

use Spoom\Core\Helper\Collection;
use Spoom\MVC\Model\DefinitionInterface;
use Spoom\MVC\Model\ItemInterface;

/**
 * Interface ModelInterface
 *
 * TODO model should support aggregations
 */
interface ModelInterface {

  const DEFINITION_FILTER = 'filter';
  const DEFINITION_FIELD  = 'field';
  const DEFINITION_SORT   = 'sort';
  const DEFINITION_LIMIT  = 'limit';

  const METHOD_SEARCH = 'search';
  const METHOD_CREATE = 'create';
  const METHOD_UPDATE = 'update';
  const METHOD_REMOVE = 'remove';

  const OPERATOR_REVERSE = '!';

  const OPERATOR_UNKNOWN  = '';
  const OPERATOR_EQUAL    = '=';
  const OPERATOR_EQUALNOT = '!=';
  const OPERATOR_GREATER  = '<';
  const OPERATOR_LESSER   = '>';

  const OPERATOR_GREATEREQUAL = '<=';
  const OPERATOR_LESSEREQUAL  = '>=';

  const OPERATOR_BEGIN      = '^=';
  const OPERATOR_BEGINNOT   = '^!';
  const OPERATOR_END        = '$=';
  const OPERATOR_ENDNOT     = '$!';
  const OPERATOR_CONTAIN    = '*=';
  const OPERATOR_CONTAINNOT = '*!';
  const OPERATOR_SEARCH     = '?=';

  const OPERATOR_PATTERN    = '%=';
  const OPERATOR_PATTERNNOT = '%!';
  const OPERATOR_REGEXP     = '|=';
  const OPERATOR_REGEXPNOT  = '|!';

  const OPERATOR = [
    'greaterequal' => self::OPERATOR_GREATEREQUAL,
    'lesserequal'  => self::OPERATOR_LESSEREQUAL,
    'begin'        => self::OPERATOR_BEGIN,
    'beginnot'     => self::OPERATOR_BEGINNOT,
    'end'          => self::OPERATOR_END,
    'endnot'       => self::OPERATOR_ENDNOT,
    'contain'      => self::OPERATOR_CONTAIN,
    'containnot'   => self::OPERATOR_CONTAINNOT,
    'search'       => self::OPERATOR_SEARCH,
    'pattern'      => self::OPERATOR_PATTERN,
    'patternnot'   => self::OPERATOR_PATTERNNOT,
    'regexp'       => self::OPERATOR_REGEXP,
    'regexpnot'    => self::OPERATOR_REGEXPNOT,

    'equal'    => self::OPERATOR_EQUAL,
    'equalnot' => self::OPERATOR_EQUALNOT,
    'greater'  => self::OPERATOR_GREATER,
    'lesser'   => self::OPERATOR_LESSER,

    '' => self::OPERATOR_UNKNOWN
  ];

  /**
   * Create item(s)
   *
   * with the stored field set(s)
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return array
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
   * @return int The number of the affected rows
   */
  public function remove( int $limit = 0, int $offset = 0, bool $reset = true ): int;
  /**
   * Update item(s)
   *
   * that match the stored filters and inside the given limits (and sort)
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return int The number of the affected rows
   */
  public function update( int $limit = 0, int $offset = 0, bool $reset = true ): int;
  /**
   * Load an array of items
   *
   * with the given filters, sort and limitation
   *
   * @param int  $limit
   * @param int  $offset
   * @param bool $reset Reset the internal state, after the command
   *
   * @return array
   */
  public function search( int $limit = 0, int $offset = 0, bool $reset = true ): array;

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
   * @return null|ItemInterface
   */
  public function get( int $offset = 0, bool $reset = true ): ?ItemInterface;
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
   * @return Model\Statement
   */
  public function statement( string $method ): Model\Statement;

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
  public function removeField( ?array $list = null, ?int $slot = 0 );

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
  public function addFilter( string $name, $value, string $operator = self::OPERATOR_EQUAL );
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
   * @param string[] $list
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
   * Get filter/field/sort or limit definitions
   *
   * @param string|null $method Definition's type or ===null to all definition
   *
   * @return DefinitionInterface[]|DefinitionInterface[][]
   */
  public function getDefinitionList( string $method = null ): array;
  /**
   * Get filter/field/sort or limit definitions
   *
   * @param string|null $method Definition's type or ===null to all definition
   * @param string      $name
   *
   * @return DefinitionInterface
   * @throws \InvalidArgumentException
   * @throws \LogicException
   */
  public function getDefinition( string $method, string $name );
}
/**
 * Class Model
 */
abstract class Model implements ModelInterface {

  /**
   * Multiple key definitions in order (first is the primary key)
   *
   * @var array[]
   */
  protected $_key;

  /**
   * @var DefinitionInterface[][]
   */
  protected $_definition = [
    self::DEFINITION_FIELD  => [],
    self::DEFINITION_FILTER => [],
    self::DEFINITION_SORT   => []
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
  public function update( int $limit = 0, int $offset = 0, bool $reset = true ): int {
    $result = $this->invoke( static::METHOD_UPDATE, $limit, $offset );

    if( $reset ) $this->set();
    return $result();
  }
  //
  public function remove( int $limit = 0, int $offset = 0, bool $reset = true ): int {
    $result = $this->invoke( static::METHOD_REMOVE, $limit, $offset );

    if( $reset ) $this->set();
    return $result();
  }
  //
  public function search( int $limit = 0, int $offset = 0, bool $reset = true ): array {
    $result = $this->invoke( static::METHOD_SEARCH, $limit, $offset );

    if( $reset ) $this->set();
    return $result;
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
  public function get( int $offset = 0, bool $reset = true ): ?ItemInterface {

    $result = $this->search( 1, $offset, $reset );
    return !empty( $result ) ? $result[ 0 ] : null;
  }
  //
  public function each( callable $callback, int $chunk, int $limit = 0, int $offset = 0, bool $reset = true ) {

    // validate the chunk size
    if( $chunk < 1 ) throw new \InvalidArgumentException( "Chunk ({$chunk}) must be greater than zero" );
    else if( $limit < 0 || $offset < 0 ) throw new \InvalidArgumentException( "Limit ({$limit}) and offset ({$offset}) must be greater (or equal) than zero" );
    else {

      do {

        $next = $limit && ( $limit - $offset ) < $chunk ? ( $limit - $offset ) : $chunk;
        $list = $this->search( $next, $offset );
        if( $callback( $list, $offset ) === false ) break;
        else $offset += $next;

      } while( count( $list ) && ( $limit < 1 || $limit > $offset ) );
    }

    if( $reset ) $this->set();
    return $this;
  }
  //
  public function count( bool $reset = true ): int {
    return count( $this->search( $reset ) );
  }

  //
  public function key( $context, bool $primary = false ): ?array {

    //
    $context = Collection::read( $context, [] );
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
  public function item( array $context = null, ?array $key = null ): ItemInterface {
    return new Model\Item( $this, $context, $key );
  }
  //
  public function set( ?array $filter = null, ?array $field = null, ?array $sort = null ) {

    $filter === null ? $this->removeFilter() : $this->setFilter( $filter );
    $field === null ? $this->removeField() : $this->setField( $field );
    $sort === null ? $this->removeSort() : $this->setSort( $sort );

    return $this;
  }

  /**
   * Apply model to a statement
   *
   * @param Model\Statement $statement
   *
   * @return Model\Statement
   * @throws \InvalidArgumentException
   * @throws \LogicException
   */
  protected function apply( Model\Statement $statement ) {

    foreach( $this->getField() as $slot => $field_list ) {
      foreach( $field_list as $name => $value ) {
        $statement->addDefinition( static::DEFINITION_FIELD, $name, $value, $slot );
      }
    }

    switch( $statement->getMethod() ) {
      case static::METHOD_UPDATE:
      case static::METHOD_REMOVE:
      case static::METHOD_SEARCH:

        //
        foreach( $this->getFilter() as $name => $value ) {
          $statement->addDefinition( static::DEFINITION_FILTER, $name, $value );
        }

        //
        foreach( $this->getSort() as $name ) {
          $statement->addDefinition( static::DEFINITION_SORT, $name );
        }

        break;
    }

    return $statement;
  }
  /**
   * Invoke statement based on the model
   *
   * This will use the current model's data, and the provided limits to run the method
   *
   * @param string   $method
   * @param int|null $limit
   * @param int      $offset
   *
   * @return array|int
   * @throws \InvalidArgumentException When the offset or the limit less than zero
   */
  protected function invoke( string $method, int $limit = 0, int $offset = 0 ) {

    if( $limit < 0 || $offset < 0 ) throw new \InvalidArgumentException( "Limit ({$limit}) and offset ({$offset}) must be greater (or equal) than zero" );
    else {

      $statement = $this->statement( $method );

      //
      $tmp = $statement( $limit, $offset );
      if( !is_array( $tmp ) ) $result = $tmp;
      else {

        $result = [];
        foreach( $tmp as $item ) {
          $result[] = $this->item( $item, $this->key( $item ) );
        }
      }

      return $result;
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
    if( !Collection::isArrayNumeric( $list ) ) throw new \InvalidArgumentException( 'List must be a numeric array' );
    else {

      // check for inner arrays
      foreach( $list as $fields ) {
        if( !Collection::is( $fields, true, true ) ) {
          throw new \InvalidArgumentException( 'List must be a numeric array of iterables' );
        }
      }

      // normalize the list to start with zero
      $this->_field = array_values( $list );
    }

    return $this;
  }
  //
  public function addField( array $list, bool $merge = true, ?int $slot = 0 ) {

    $slot                  = $slot === null ? count( $this->_field ) : $slot;
    $this->_field[ $slot ] = $list + ( $merge ? ( $this->_field[ $slot ] ?? [] ) : [] );

    return $this;
  }
  //
  public function removeField( ?array $list = null, ?int $slot = 0 ) {

    // handle simple versions (clear all, and all in a slot)
    if( $list === null && $slot === null ) $this->_field = [];
    else if( $list === null ) unset( $this->_field[ $slot ] );
    else {

      foreach( $list as $name ) {
        if( $slot !== null ) unset( $this->_field[ $slot ][ $name ] );
        else foreach( $this->_field as $i => $fields ) {
          unset( $this->_field[ $i ][ $name ] );
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
  public function addFilter( string $name, $value, string $operator = self::OPERATOR_EQUAL ) {

    $this->_filter[ static::operator( $name, $operator ) ] = $value;
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
    $operator = $reverse ? self::OPERATOR_REVERSE : '';
    $name     = static::operator( $name, $operator );
    $this->removeSort( [ $name ] );

    $this->_sort[] = $name;
    return $this;
  }
  //
  public function removeSort( ?array $list = null ) {
    foreach( $list as $name ) {
      if( ( $tmp = array_search( $name, $this->_sort ) ) !== false ) {
        array_splice( $this->_sort, $tmp, 1 );
      }
    }

    return $this;
  }

  //
  public function getKey( $primary = false ): array {
    return $primary ? $this->_key[ 0 ] : $this->_key;
  }

  //
  public function getDefinitionList( string $method = null ): array {
    if( $method === null ) return $this->_definition;
    else if( !isset( $this->_definition[ $method ] ) ) throw new \InvalidArgumentException( 'Type must be: ' . implode( ', ', array_keys( $this->_definition ) ) );
    else return $this->_definition[ $method ];
  }
  //
  public function getDefinition( string $method, string $name ) {

    $list = $this->getDefinitionList( $method );
    if( !isset( $list[ $name ] ) ) throw new \LogicException( "There is no definition for '{$name}' {$method} in " . get_class( $this ) );
    else return $list[ $name ];
  }
  /**
   * Add a new filter/field or sort definition to the model
   *
   * @param string              $name
   * @param string              $method
   * @param DefinitionInterface $definition
   *
   * @throws \InvalidArgumentException
   */
  protected function addDefinition( string $name, string $method, DefinitionInterface $definition ) {
    if( !isset( $this->_definition[ $method ] ) ) throw new \InvalidArgumentException( 'Type must be: ' . implode( ', ', array_keys( $this->_definition ) ) );
    else $this->_definition[ $method ][ $name ] = $definition;
  }

  /**
   * Extract or inject operator
   *
   * @param string      $name
   * @param string|null $operator NULL for extract
   *
   * @return string The name with or without the operator
   */
  public static function operator( $name, &$operator = null ) {
    if( $operator !== null ) return $operator . $name;
    else {

      $tmp      = preg_replace( '^[^a-z0-9_-]+', '', $name );
      $operator = substr( $name, 0, strlen( $name ) - strlen( $tmp ) );
      return $tmp;
    }
  }
}

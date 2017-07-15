<?php namespace Spoom\MVC;

use Spoom\Core\Helper\Collection;

/**
 * Interface StatementInterface
 */
interface StatementInterface {

  const SORT_REVERSE = '!';

  const OPERATOR_UNKNOWN = '';
  const OPERATOR_EQUAL   = '=';
  const OPERATOR_NOT     = '!';
  const OPERATOR_GREATER = '<';
  const OPERATOR_LESSER  = '>';

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

    'equal'   => self::OPERATOR_EQUAL,
    'not'     => self::OPERATOR_NOT,
    'greater' => self::OPERATOR_GREATER,
    'lesser'  => self::OPERATOR_LESSER,

    '' => self::OPERATOR_UNKNOWN
  ];

  /**
   * Create a new item with the given fields
   *
   * @return array Array of new item's keys
   */
  public function create(): array;
  /**
   * Remove all item that match the filters and inside the given limits (and sort)
   *
   * @return int The number of the affected rows
   */
  public function remove(): int;
  /**
   * Update all item that match the filters and inside the given limits (and sort)
   *
   * @return int The number of the affected rows
   */
  public function update(): int;
  /**
   * Load an array of items with the given filters, sort and limitation
   *
   * @return Model\ItemInterface[]
   */
  public function search(): array;

  /**
   * Create or update large number of items with different data
   *
   * @param array $shared Common data for every data element
   *
   * @return array Array of new/updated item's keys
   */
  public function batch( array $shared = [] ): array;
  /**
   * Get one item from the Model
   *
   * @param int $offset The returned item offset in the result array
   *
   * @return Model\ItemInterface|null
   */
  public function get( int $offset = 0 ): ?Model\ItemInterface;
  /**
   * Process a huge search result chunked to prevent memory issues
   *
   * @param callable $callback Handler for every chunk. The callable first parameter is the actual chunk, the second is the offset
   * @param int      $size     Chuck size. Cannot be non-positive
   *
   * @return static
   */
  public function chunk( callable $callback, int $size );
  /**
   * Count the results
   *
   * @return int
   */
  public function count(): int;

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
   * Get the limit
   *
   * @param int $offset
   *
   * @return int
   */
  public function getLimit( int &$offset = 0 ): int;
  /**
   * Set the limit for the statement
   *
   * @param int $value  The maximum number of items to get
   * @param int $offset The minimum number of items to skip. ===null means no change
   *
   * @return static
   */
  public function setLimit( int $value, ?int $offset = null );

  /**
   * @return ModelInterface
   */
  public function getModel(): ModelInterface;
}
/**
 * Class Statement
 */
abstract class Statement implements StatementInterface {

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

  /**
   * Maximum number of results
   *
   * Zero means everything
   *
   * @var int
   */
  private $_limit = 0;
  /**
   * Number of results to skip from the beginning
   *
   * @var int
   */
  private $offset = 0;

  //
  public function batch( array $shared = [] ): array {

    $statement = clone $this;
    for( $i = 0, $length = count( $statement->getField() ); $i < $length; ++$i ) {
      $statement->addField( $shared, true, $i );
    }

    return $statement->create();
  }
  //
  public function get( int $offset = 0 ): ?Model\ItemInterface {

    $statement = clone $this;
    $statement->setLimit( 1, $offset );

    $result = $statement->search();
    return !empty( $result ) ? $result[ 0 ] : null;
  }
  //
  public function chunk( callable $callback, int $size ) {

    // validate the chunk sized
    if( $size < 1 ) throw new \InvalidArgumentException( 'Size can only be positive number' );
    else {

      // FIXME negative limits?!

      // setup the offset and max length
      $length = $this->getLimit( $offset );
      if( $length ) $length += $offset;

      $statement = clone $this;
      while( true ) {

        // load the next chunk
        $statement->setLimit( $size, $offset );
        $list = $statement->search();
        if( $callback( $list, $offset ) === false ) break;
        else {

          $offset += $size;
          if( !count( $list ) || ( $length && $offset > $length ) ) break;
        }
      }
    }

    return $this;
  }
  //
  public function count(): int {
    return count( $this->search() );
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

    $this->_filter[ $operator . $name ] = $value;
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
    $name = ( $reverse ? self::SORT_REVERSE : '' ) . $name;
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
  public function getLimit( int &$offset = 0 ): int {

    $offset = $this->offset;
    return $this->_limit;
  }
  //
  public function setLimit( int $value, ?int $offset = null ) {

    $this->_limit = $value;
    if( $offset !== null ) {
      $this->offset = $offset;
    }
  }
}

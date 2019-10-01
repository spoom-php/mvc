<?php namespace Spoom\MVC\Model;

//
interface DefinitionInterface {

  const FIELD  = 'field';
  const FILTER = 'filter';
  const SORT   = 'sort';

  /**
   *
   */
  public function __clone();
  /**
   * Custom execution in the statement
   *
   * @param array $list The current list contains every change
   * @param array $_list The original list from the storage
   *
   * @return bool This must be true to skip the default definition processing by the statement
   */
  public function __invoke( array &$list, array $_list );

  /**
   * Attach the definition for the Statement
   *
   * This will be called before any statement execution. The `$statement` input should be saved in the definition
   * for the next calls (`->__invoke()`, `->apply()`, `->revert()` and `detach()`)
   *
   * @param StatementInterface $statement The statement to apply
   */
  public function attach( StatementInterface $statement );
  /**
   * Revert applied modifications due to an exception
   *
   * This will be called after any exception occur in the statement execution process. This can be before the `->attach()` method, so
   * the stored statement may be null
   *
   * @param \Throwable $exception
   * @param array $_list The original result list
   */
  public function revert( \Throwable $exception, array $_list );
  /**
   * Complete definitions for the statement
   *
   * @param array $list The result list modified by the previous definitions
   * @param array $_list The original result list
   */
  public function apply( array &$list, array $_list );
  /**
   * Detach from the Statement
   *
   * This is the last method called in the Statement execution, even after an `revert()`
   *
   * @param array $list The result list modified by the previous definitions
   * @param array $_list The original result list
   */
  public function detach( array $list, array $_list );

  /**
   * Add or replace value for an operator
   *
   * @param mixed|null $value
   * @param string     $operator
   * @param int        $slot
   *
   * @return static
   */
  public function set( $value = null, string $operator = Operator::DEFAULT, int $slot = 0 );
  /**
   * @param int|null    $slot
   * @param string|null $operator
   *
   * @return array|mixed
   */
  public function get( ?int $slot = null, string $operator = null );

  /**
   * @return int
   */
  public function getFlag();
  /**
   * @return StatementInterface
   */
  public function getStatement(): StatementInterface;
  /**
   * @return string
   */
  public function getType();
  /**
   * @return string
   */
  public function getName();
}

//
abstract class Definition implements DefinitionInterface {

  /**
   * Clear all flags
   */
  const FLAG_NONE = 0;

  /**
   * @var StatementInterface|null
   */
  protected $_statement;
  /**
   * @var array[]
   */
  protected $slot_list = [];
  /**
   * @var int
   */
  private $_flag;

  /**
   * @var string
   */
  private $_name;
  /**
   * @var string|null
   */
  private $_field;

  /**
   * @var string|null
   */
  private $_operator;
  /**
   * @var string[]|null
   */
  private $_operator_list;

  /**
   * @param string      $name
   * @param string|null $operator
   * @param array|null  $operator_list
   * @param int         $flag
   */
  public function __construct( string $name, ?string $field, ?string $operator = null, ?array $operator_list = null, int $flag = self::FLAG_NONE ) {

    // check default operator availability
    if( $operator_list !== null && $operator !== null && !in_array( $operator, $operator_list ) ) {
      throw new \InvalidArgumentException( 'Default operator must be one of the following: ' . implode( ', ', $operator_list ) );
    }

    $this->_name = $name;
    $this->_field = $field;
    $this->_flag = $flag;

    $this->_operator      = $operator;
    $this->_operator_list = $operator_list;
  }
  //
  public function __clone() { }

  //
  public function attach( StatementInterface $statement ) {
    $this->_statement = $statement;
  }
  //
  public function detach( array $list, array $_list ) {
    $this->_statement = null;
  }

  //
  public function set( $value = null, string $operator = Operator::DEFAULT, int $slot = 0 ) {

    $operator = $this->_operator !== null && $operator === Operator::DEFAULT ? $this->_operator : $operator;
    if( $this->_operator_list !== null && !in_array( $operator, $this->_operator_list ) ) {
      throw new \InvalidArgumentException( 'Operator must be one of the following: ' . implode( ', ', $this->_operator_list ) );
    }

    //
    if( !array_key_exists( $slot, $this->slot_list ) ) {
      $this->slot_list[ $slot ] = [];
    }

    $this->slot_list[ $slot ][ $operator ] = $value;
    return $this;
  }
  //
  public function get( ?int $slot = null, string $operator = null ) {

    //
    if( $operator === null ) return ($slot === null ? $this->slot_list : ($this->slot_list[ $slot ] ?? []));
    else {

      //
      $operator = $this->_operator !== null && $operator === Operator::DEFAULT ? $this->_operator : $operator;
      return $this->slot_list[ $slot ][ $operator ] ?? null;
    }
  }

  //
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
  public function getStatement(): StatementInterface {
    return $this->_statement;
  }
  //
  public function getName() {
    return $this->_name;
  }
  /**
   * @param bool $fallback Returns the definition's name when the field is empty
   *
   * @return string|null
   */
  public function getField( bool $fallback = false ): ?string {
    return $this->_field ?? ( $fallback ? $this->getName() : null );
  }

  /**
   * Available operators
   *
   * @return null|string[]
   */
  public function getOperatorList(): ?array {
    return $this->_operator_list;
  }
}
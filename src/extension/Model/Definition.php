<?php namespace Spoom\MVC\Model;

use Spoom\MVC\Model;

//
interface DefinitionInterface {

  const FILTER = 'filter';
  const FIELD  = 'field';
  const SORT   = 'sort';

  //
  public function __clone();

  /**
   * @param StatementInterface $statement
   */
  public function setup( StatementInterface $statement );
  /**
   * @param array $list
   * @param array $_list
   *
   * @return array
   */
  public function execute( array $list, array $_list = [] ): array;

  /**
   * Add or replace new operator for the definition
   *
   * @param string     $operator
   * @param mixed|null $value
   * @param int        $slot
   */
  public function setOperator( string $operator, $value = null, int $slot = 0 );

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
   * @var StatementInterface
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
  public function __construct( string $name, ?string $operator = null, ?array $operator_list = null, int $flag = self::FLAG_NONE ) {

    // check default operator availability
    if( $operator_list !== null && $operator !== null && !in_array( $operator, $operator_list ) ) {
      throw new \InvalidArgumentException( 'Default operator must be one of the following: ' . implode( ', ', $operator_list ) );
    }

    $this->_name = $name;
    $this->_flag = $flag;

    $this->_operator      = $operator;
    $this->_operator_list = $operator_list;
  }
  //
  public function __clone() { }

  public function setup( StatementInterface $statement ) {
    $this->_statement = $statement;
  }
  /**
   * @param null|string $value
   *
   * @return Model\Operator
   * @throws \InvalidArgumentException Not available operator
   */
  protected function operator( ?string $value = null ): Model\Operator {

    $result = $value ?? $this->_operator;
    if( $this->_operator_list !== null && empty( $this->_operator_list ) ) throw new \LogicException( 'This definition doesn\'t have any operator!' );
    else if( $this->_operator_list !== null && !in_array( $result, $this->_operator_list ) ) throw new \InvalidArgumentException( 'Operator must be one of the following: ' . implode( ', ', $this->_operator_list ) );
    else return new Model\Operator( $result );
  }

  //
  public function setOperator( string $operator, $value = null, int $slot = 0 ) {

    //
    if( !array_key_exists( $slot, $this->slot_list ) ) {
      $this->slot_list[ $slot ] = [];
    }

    $this->slot_list[ $slot ][ $operator ] = $value;
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
  public function getStatement(): StatementInterface {
    return $this->_statement;
  }
  //
  public function getName() {
    return $this->_name;
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
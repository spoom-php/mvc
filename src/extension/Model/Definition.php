<?php namespace Spoom\MVC\Model;

//
interface DefinitionInterface {

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
   * @param string $name
   * @param int    $flag
   */
  public function __construct( string $name, int $flag = self::FLAG_NONE ) {
    $this->_name = $name;
    $this->_flag = $flag;
  }
  //
  public function __clone() { }

  public function setup( StatementInterface $statement ) {
    $this->_statement = $statement;
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
}
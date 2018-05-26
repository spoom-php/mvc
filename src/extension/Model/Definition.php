<?php namespace Spoom\MVC\Model;

//
interface DefinitionInterface {

  //
  public function __clone();

  /**
   * Add new operator for the definition
   *
   * @param string     $operator
   * @param mixed|null $value
   * @param int        $slot
   */
  public function add( string $operator, $value = null, int $slot = 0 );
  /**
   * @param array $list
   * @param array $_list
   *
   * @return array
   */
  public function __invoke( array $list, array $_list = [] ): array;

  /**
   * @param Statement $value
   *
   * @return static
   */
  public function setStatement( Statement $value );

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
   * @var Statement
   */
  protected $_statement;
  /**
   * @var array[]
   */
  protected $slot_list = [];

  //
  public function add( string $operator, $value = null, int $slot = 0 ) {

    //
    if( !array_key_exists( $slot, $this->slot_list ) ) {
      $this->slot_list[ $slot ] = [];
    }

    $this->slot_list[ $slot ][ $operator ] = $value;
  }

  //
  public function setStatement( Statement $value ) {
    $this->_statement = $value;
    return $this;
  }
}
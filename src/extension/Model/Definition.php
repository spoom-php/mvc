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
   * Apply definition to the statement
   */
  public function apply();
  /**
   * Post-process the statement's result
   *
   * @param mixed $result
   */
  public function finish( &$result );

  /**
   * @param Statement $value
   *
   * @return static
   */
  public function setStatement( Statement $value );
}

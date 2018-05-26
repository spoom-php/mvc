<?php namespace Spoom\MVC\Model\Definition;

use Spoom\Core\Helper\Number;
use Spoom\MVC\Model;
use Spoom\MVC\ModelInterface;

//
class Sort extends Model\Definition {

  /**
   * @var string
   */
  private $_field;
  /**
   * @var string
   */
  private $_operator;

  /**
   * @param string $field    Field name that is being sorted
   * @param string $operator Default operator
   */
  public function __construct( $field, string $operator = Model::OPERATOR_DEFAULT ) {
    $this->_field    = $field;
    $this->_operator = $operator;
  }

  //
  public function __clone() { }
  //
  public function __invoke( array $list, array $_list = [] ): array {

    foreach( ( $this->slot_list[ 0 ] ?? [] ) as $operator => $value ) {

      $_operator = $operator === ModelInterface::OPERATOR_DEFAULT ? $this->_operator : $operator;

      usort( $list, $_operator === ModelInterface::OPERATOR_MODIFIER_INVERT ? function ( $item, $_item ) {
        $test  = $item[ $this->_field ] ?? null;
        $_test = $_item[ $this->_field ] ?? null;

        return Number::is( $test ) && Number::is( $_test ) ? $_test - $test : strcmp( $_test, $test );
      } : function ( $item, $_item ) {
        $test  = $item[ $this->_field ] ?? null;
        $_test = $_item[ $this->_field ] ?? null;

        return Number::is( $test ) && Number::is( $_test ) ? $test - $_test : strcmp( $test, $_test );
      } );
    }

    return $list;
  }

  //
  public function getType() {
    return Model::DEFINITION_SORT;
  }
  //
  public function getName() {
    return $this->_field;
  }
}

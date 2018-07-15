<?php namespace Spoom\MVC\Model\Definition;

use Spoom\Core\Helper\Number;
use Spoom\MVC\Model;

//
class Sort extends Model\Definition {

  /**
   * @param string $name     Field name that is being sorted
   * @param string $operator Default operator
   */
  public function __construct( string $name, string $operator = Model\Operator::DEFAULT ) {
    parent::__construct( $name, $operator, [ Model\Operator::DEFAULT, Model\Operator::FLAG_NOT ] );
  }

  //
  public function execute( array $list, array $_list = [] ): array {

    foreach( ( $this->slot_list[ 0 ] ?? [] ) as $operator => $value ) {

      $_operator = $this->operator( $operator );
      usort( $list, $_operator->isNot() ? function ( $item, $_item ) {
        $test  = $item[ $this->getName() ] ?? null;
        $_test = $_item[ $this->getName() ] ?? null;

        return Number::is( $test ) && Number::is( $_test ) ? $_test - $test : strcmp( $_test, $test );
      } : function ( $item, $_item ) {
        $test  = $item[ $this->getName() ] ?? null;
        $_test = $_item[ $this->getName() ] ?? null;

        return Number::is( $test ) && Number::is( $_test ) ? $test - $_test : strcmp( $test, $_test );
      } );
    }

    return $list;
  }

  //
  public function getType() {
    return Model\Definition::SORT;
  }
}

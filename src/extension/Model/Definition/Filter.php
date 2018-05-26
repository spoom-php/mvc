<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;
use Spoom\MVC\ModelInterface;
use Spoom\Core\Helper\Collection;

//
class Filter extends Model\Definition {

  /**
   * @var string
   */
  private $_field;
  /**
   * @var string
   */
  private $_operator;
  /**
   * @var string[]|null
   */
  private $_operator_list;

  /**
   * @param string        $field         Field name that is being filtered
   * @param string        $operator      Default operator
   * @param string[]|null $operator_list Accepted operators
   */
  public function __construct( string $field, string $operator = Model::OPERATOR_MODIFIER_EQUAL, ?array $operator_list = null ) {
    $this->_field         = $field;
    $this->_operator      = $operator;
    $this->_operator_list = $operator_list;
  }

  //
  public function __clone() { }
  //
  public function __invoke( array $list, array $_list = [] ): array {

    $_list = [];
    foreach( $list as $item ) {

      $test = $item[ $this->_field ] ?? null;
      foreach( ( $this->slot_list[ 0 ] ?? [] ) as $operator => $value ) {
        $_operator = $operator === ModelInterface::OPERATOR_DEFAULT ? $this->_operator : $operator;

        $equal     = strpos( $_operator, ModelInterface::OPERATOR_MODIFIER_EQUAL ) !== false;
        $invert    = strpos( $_operator, ModelInterface::OPERATOR_MODIFIER_INVERT ) !== false;
        $multiple  = strpos( $_operator, ModelInterface::OPERATOR_MODIFIER_MULTIPLE ) !== false;
        $_operator = str_replace( [ ModelInterface::OPERATOR_MODIFIER_EQUAL, ModelInterface::OPERATOR_MODIFIER_INVERT, ModelInterface::OPERATOR_MODIFIER_MULTIPLE ], '', $_operator );

        $result     = false;
        $value_list = !$multiple || !Collection::isArrayNumeric( $value ) ? [ $value ] : $value;
        switch( $_operator ) {
          case '':
            foreach( $value_list as $_value ) {
              $result = $result || $test === $_value;
            }
            break;

          //
          case ModelInterface::OPERATOR_GREATER:
            foreach( $value_list as $_value ) {
              $result = $result || ( ( $equal && $test >= $_value ) || ( !$equal && $test > $_value ) );
            }
            break;

          //
          case ModelInterface::OPERATOR_LESSER:
            foreach( $value_list as $_value ) {
              $result = $result || ( ( $equal && $test <= $_value ) || ( !$equal && $test < $_value ) );
            }
            break;

          //
          case ModelInterface::OPERATOR_CONTAIN:
            foreach( $value_list as $_value ) {
              $result = $result || stripos( $test, $_value ) !== false;
            }
            break;

          //
          case ModelInterface::OPERATOR_BEGIN:
            foreach( $value_list as $_value ) {
              $result = $result || stripos( $test, $_value ) === 0;
            }
            break;

          //
          case ModelInterface::OPERATOR_END:
            foreach( $value_list as $_value ) {
              $result = $result || stripos( $test, $_value ) === strlen( $test ) - strlen( $_value );
            }
            break;

          //
          case ModelInterface::OPERATOR_REGEXP:
            foreach( $value_list as $_value ) {
              $result = $result || preg_match( $test, $_value ) !== false;
            }
            break;

          // TODO implement every operator
        }

        if( $invert ) $result = !$result;
        if( !$result ) continue 2;
      }

      $_list[] = $item;
    }

    return $_list;
  }

  //
  public function getType() {
    return Model::DEFINITION_FILTER;
  }
  //
  public function getName() {
    return $this->_field;
  }
}

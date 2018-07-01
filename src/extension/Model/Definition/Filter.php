<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;
use Spoom\MVC\ModelInterface;
use Spoom\Core\Helper\Collection;

//
abstract class Filter extends Model\Definition {

  /**
   * @var string
   */
  private $_operator;
  /**
   * @var string[]|null
   */
  private $_operator_list;

  /**
   * @param string     $name          Name of the filter
   * @param string     $operator      Default operator
   * @param array|null $operator_list Supported operators
   */
  public function __construct( string $name, string $operator = Model::OPERATOR_EQUAL, ?array $operator_list = null ) {
    parent::__construct( $name );

    $this->_operator      = $operator;
    $this->_operator_list = $operator_list;
  }

  /**
   * Test input against one or more test value (with the given operator)
   *
   * @param mixed       $input
   * @param string      $operator `ModelInterface::OPERATOR_` "flag" list
   * @param array|mixed $test
   *
   * @return bool
   */
  protected function match( $input, $operator, $test ) {
    $_operator = $operator === ModelInterface::OPERATOR_DEFAULT ? $this->_operator : $operator;

    $equal     = strpos( $_operator, ModelInterface::OPERATOR_EQUAL ) !== false;
    $invert    = strpos( $_operator, ModelInterface::OPERATOR_INVERT ) !== false;
    $multiple  = strpos( $_operator, ModelInterface::OPERATOR_MULTIPLE ) !== false;
    $_operator = str_replace( [ ModelInterface::OPERATOR_EQUAL, ModelInterface::OPERATOR_INVERT, ModelInterface::OPERATOR_MULTIPLE ], '', $_operator );

    $result    = false;
    $test_list = !$multiple || !Collection::isNumeric( $test ) ? [ $test ] : $test;
    switch( $_operator ) {
      case '':
        foreach( $test_list as $_value ) {
          $result = $result || $input == $_value;
        }
        break;

      //
      case ModelInterface::OPERATOR_GREATER:
        foreach( $test_list as $_value ) {
          $result = $result || ( ( $equal && $input >= $_value ) || ( !$equal && $input > $_value ) );
        }
        break;

      //
      case ModelInterface::OPERATOR_LESSER:
        foreach( $test_list as $_value ) {
          $result = $result || ( ( $equal && $input <= $_value ) || ( !$equal && $input < $_value ) );
        }
        break;

      //
      case ModelInterface::OPERATOR_CONTAIN:
        foreach( $test_list as $_value ) {
          $result = $result || stripos( $input, $_value ) !== false;
        }
        break;

      //
      case ModelInterface::OPERATOR_BEGIN:
        foreach( $test_list as $_value ) {
          $result = $result || stripos( $input, $_value ) === 0;
        }
        break;

      //
      case ModelInterface::OPERATOR_END:
        foreach( $test_list as $_value ) {
          $result = $result || stripos( $input, $_value ) === strlen( $input ) - strlen( $_value );
        }
        break;

      //
      case ModelInterface::OPERATOR_REGEXP:
        foreach( $test_list as $_value ) {
          $result = $result || preg_match( $input, $_value ) !== false;
        }
        break;

      // TODO implement every operator
    }

    return $invert ? !$result : $result;
  }

  //
  public function getType() {
    return Model::DEFINITION_FILTER;
  }
}
//
class FilterCustom extends Filter {

  /**
   * @var callable
   */
  private $callback;

  /**
   * {@inheritdoc}
   *
   * @param string     $name          Name of the filter
   * @param callable   $callback      Callback that will be called on every list item, which may filtered out based on the result of the callback
   * @param string     $operator      Default operator
   * @param array|null $operator_list Supported operators
   */
  public function __construct( string $name, callable $callback, string $operator = Model::OPERATOR_EQUAL, $operator_list = null ) {
    parent::__construct( $name, $operator, $operator_list );

    $this->callback = $callback;
  }

  //
  public function execute( array $list, array $_list = [] ): array {

    $_list = [];
    foreach( $list as $item ) {
      foreach( ( $this->slot_list[ 0 ] ?? [] ) as $operator => $test ) {

        $callback = $this->callback;
        if( !$callback( $item, $operator, $test ) ) {
          continue 2;
        }
      }

      $_list[] = $item;
    }

    return $_list;
  }
}
//
class FilterField extends Filter {

  /**
   * @var string|null
   */
  private $_field;

  /**
   * {@inheritdoc}
   *
   * @param string      $name          Name of the filter
   * @param string|null $field         Field name that is being filtered
   * @param string      $operator      Default operator
   * @param array|null  $operator_list Supported operators
   */
  public function __construct( string $name, ?string $field = null, string $operator = Model::OPERATOR_EQUAL, $operator_list = null ) {
    parent::__construct( $name, $operator, $operator_list );

    $this->_field = $field;
  }

  //
  public function execute( array $list, array $_list = [] ): array {

    $_list = [];
    foreach( $list as $item ) {

      $value = $item[ $this->getField() ?? $this->getName() ] ?? null;
      foreach( ( $this->slot_list[ 0 ] ?? [] ) as $operator => $test ) {
        if( !$this->match( $value, $operator, $test ) ) {
          continue 2;
        }
      }

      $_list[] = $item;
    }

    return $_list;
  }

  /**
   * @return string|null
   */
  public function getField(): ?string {
    return $this->_field;
  }
}
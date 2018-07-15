<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;
use Spoom\Core\Helper\Collection;

//
abstract class Filter extends Model\Definition {

  /**
   * @param string     $name          Name of the filter
   * @param string     $operator      Default operator
   * @param array|null $operator_list Supported operators
   *
   * @throws \InvalidArgumentException Not available default operator
   */
  public function __construct( string $name, string $operator = Model\Operator::DEFAULT, ?array $operator_list = null ) {
    parent::__construct( $name, $operator, $operator_list );
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
    $_operator = $this->operator( $operator );

    $result    = false;
    $test_list = !$_operator->isAny() || !Collection::isNumeric( $test ) ? [ $test ] : $test;
    switch( $_operator->getType() ) {
      case Model\Operator::DEFAULT:
        foreach( $test_list as $_value ) {
          $result = $result || ( $_operator->isLoose() ? $input == $_value : $input === $_value );
        }
        break;

      //
      case Model\Operator::GREATER:
        foreach( $test_list as $_value ) {
          $result = $result || ( ( $_operator->isLoose() && $input >= $_value ) || ( !$_operator->isLoose() && $input > $_value ) );
        }
        break;

      //
      case Model\Operator::LESSER:
        foreach( $test_list as $_value ) {
          $result = $result || ( ( $_operator->isLoose() && $input <= $_value ) || ( !$_operator->isLoose() && $input < $_value ) );
        }
        break;

      //
      case Model\Operator::CONTAIN:
        $method = $_operator->isLoose() ? 'stripos' : 'strpos';
        foreach( $test_list as $_value ) {
          $result = $result || $method( $input, $_value ) !== false;
        }
        break;

      //
      case Model\Operator::BEGIN:
        $method = $_operator->isLoose() ? 'stripos' : 'strpos';
        foreach( $test_list as $_value ) {
          $result = $result || $method( $input, $_value ) === 0;
        }
        break;

      //
      case Model\Operator::END:
        $method = $_operator->isLoose() ? 'stripos' : 'strpos';
        foreach( $test_list as $_value ) {
          $result = $result || $method( $input, $_value ) === strlen( $input ) - strlen( $_value );
        }
        break;

      //
      case Model\Operator::REGEXP:
        foreach( $test_list as $_value ) {
          $result = $result || preg_match( $input, $_value ) !== false;
        }
        break;

      // TODO implement every operator
    }

    return $_operator->isNot() ? !$result : $result;
  }

  //
  public function getType() {
    return static::FILTER;
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
  public function __construct( string $name, callable $callback, string $operator = Model\Operator::DEFAULT, ?array $operator_list = null ) {
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
  public function __construct( string $name, ?string $field = null, string $operator = Model\Operator::DEFAULT, ?array $operator_list = null ) {
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
<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;

//
class Filter extends Model\Definition {

  /**
   * @param string      $name          Name of the filter
   * @param string|null $field         Field name that is being filtered
   * @param string      $operator      Default operator
   * @param array|null  $operator_list Supported operators
   *
   * @throws \InvalidArgumentException Not available default operator
   */
  public function __construct( string $name, ?string $field = null, string $operator = Model\Operator::DEFAULT, ?array $operator_list = null ) {
    parent::__construct( $name, $field, $operator, $operator_list );
  }

  //
  public function __invoke( array &$_, array $__ ) {
    return false;
  }

  // There is no need to revert anything on error
  public function revert( \Throwable $_, array $__ ) {}
  // There is no need to apply the definition
  public function apply( array &$_, array $__ ) {}

  //
  public function getType() {
    return static::FILTER;
  }
}
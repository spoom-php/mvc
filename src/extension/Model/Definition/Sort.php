<?php namespace Spoom\MVC\Model\Definition;

use Spoom\MVC\Model;
use Spoom\MVC\Model\StatementInterface;

//
class Sort extends Model\Definition {

  /**
   * @param string      $name     Field name that is being sorted
   * @param null|string $field
   * @param string      $operator Default operator
   *
   * @throws \InvalidArgumentException
   */
  public function __construct( string $name, ?string $field = null, string $operator = Model\Operator::DEFAULT ) {
    parent::__construct( $name, $field, $operator, [ Model\Operator::DEFAULT, Model\Operator::FLAG_NOT ] );
  }

  //
  public function __invoke( ?array &$_, ?array $__ ) {
    return false;
  }

  // There is no need to revert anything on error
  public function revert( \Throwable $_, ?array $__ ) {}
  // There is no need to apply the definition
  public function apply( ?array &$_, ?array $__ ) {}

  //
  public function getType() {
    return Model\Definition::SORT;
  }
}
//
class SortRandom extends Model\Definition {

  /**
   * @var int|null
   */
  private $_seed;

  /**
   * @param string   $name Field name that is being sorted
   * @param int|null $seed Optional seed for the random sorting
   *
   * @throws \InvalidArgumentException
   */
  public function __construct( string $name, ?int $seed = null ) {
    parent::__construct( $name, null, null, [] );

    $this->_seed = $seed;
  }

  //
  public function __invoke( array &$list, array $_ ) {
    if( $this->_seed === null ) shuffle( $list );
    else {

      mt_srand( $this->_seed );
      $order = array_map( function ( $_ ) { return mt_rand(); }, range( 1, count( $list ) ) );
      array_multisort( $order, $list );
      mt_srand();
    }

    return true;
  }

  //
  public function attach( StatementInterface $statement ) {
    if( !( $statement instanceof Model\Statement ) ) throw new \TypeError( 'Statement must be an instance of ' . Model\Statement::class . ' in order to use this type of sorting' );
    else parent::attach( $statement );
  }

  // There is no need to revert anything on error
  public function revert( \Throwable $_, array $__ ) {}
  // There is no need to apply the definition
  public function apply( array &$_, array $__ ) {}

  /**
   * @return int|null
   */
  public function getSeed() {
    return $this->_seed;
  }
  /**
   * @param int|null $value
   *
   * @return $this
   */
  public function setSeed( int $value = null ) {
    $this->_seed = $value;
    return $this;
  }

  //
  public function getType() {
    return Model\Definition::SORT;
  }
}

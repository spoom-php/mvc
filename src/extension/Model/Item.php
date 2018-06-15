<?php namespace Spoom\MVC\Model;

use Spoom\Core\Helper\Collection;
use Spoom\Core\Storage;
use Spoom\Core\StorageInterface;
use Spoom\MVC\ModelInterface;

/**
 * Interface ItemInterface
 */
interface ItemInterface extends StorageInterface {

  /**
   * Fetch data from the model
   *
   * By default this is just fetch the data from the model, and doesn't change the storage
   *
   * @param bool  $overwrite  Change storage data with the loaded ones
   * @param array $field_list Add more fields to the item
   *
   * @return static
   */
  public function fetch( bool $overwrite = false, array $field_list = [] );
  /**
   * Commit changes to the model
   *
   * This will create an item if there was no key, or update it otherwise
   *
   * @param bool $all Update only changed values, or everything
   *
   * @return static
   */
  public function commit( bool $all = false );
  /**
   * Revert item's data to the model state
   *
   * @return static
   */
  public function revert();
  /**
   * Remove item from the model
   *
   * @return static
   * @throws \LogicException Try to remove without a key
   */
  public function remove();

  /**
   * Get the unique key(s)
   *
   * @return array|null
   */
  public function getKey(): ?array;
  /**
   * Set the unique key
   *
   * @param array|null $value
   *
   * @return static
   */
  public function setKey( ?array $value );
  /**
   * Check if the element is exists in the model
   *
   * @return bool
   */
  public function isExist(): bool;

  /**
   * @return ModelInterface
   */
  public function getModel(): ModelInterface;
}
/**
 * Class Item
 *
 * TODO add some cache for the ->isExist() method
 */
class Item extends Storage implements ItemInterface {

  /**
   * Item unique index in the source
   *
   * @var array
   */
  private $_key;
  /**
   * Item source
   *
   * @var ModelInterface
   */
  private $_model;

  /**
   * @var array
   */
  private $persistent;

  /**
   * @param ModelInterface $model
   * @param array          $context
   * @param array|null     $key
   *
   * @throws \TypeError
   */
  public function __construct( ModelInterface $model, array $context, ?array $key = null ) {
    parent::__construct( $context );

    $this->_model = $model;
    $this->_key   = $key;
  }

  //
  public function fetch( bool $overwrite = false, array $field_list = [] ) {
    if( !isset( $this->_key ) ) throw new \LogicException( "Item's key can't be empty..there is nothing to fetch!" );
    else {

      $item             = $this->_model->set( $this->_key, $field_list )->get();
      $this->persistent = Collection::read( $item ?? [] );

      if( $overwrite ) {
        $this[ '' ] = $this->persistent;
      }
    }

    return $this;
  }
  //
  public function commit( bool $all = false ) {

    // check existance and update the values for the diff
    $exist = $this->isExist();
    if( $exist && !$all ) $this->fetch();

    // define which data should be written to the model
    $data = $all || !$exist ? Collection::read( $this ) : array_diff_assoc( Collection::read( $this ), $this->persistent );

    // perform the create/update
    $this->_model->setField( [ $data ] );
    if( $exist ) $this->_model->setFilter( $this->_key )->update();
    else $this->_key = $this->_model->create();

    // 
    $this->persistent = Collection::read( $this );
    return $this;
  }
  //
  public function revert() {

    $this[ '' ] = $this->persistent;
    return $this;
  }
  //
  public function remove() {
    if( !isset( $this->_key ) ) throw new \LogicException( "Item's key can't be empty..there is nothing to remove!" );
    else {

      $this->_model->set( $this->_key )->remove();
      $this->persistent = [];

      return $this;
    }
  }

  //
  public function getKey(): ?array {
    return $this->_key;
  }
  //
  public function setKey( ?array $value ) {
    if( $this->_key != $value ) {
      $this->persistent = [];
    }

    $this->_key = $value;
    return $this;
  }

  //
  public function isExist(): bool {
    return !empty( $this->_key ) && $this->_model->set( $this->_key )->count() > 0;
  }

  //
  public function getModel(): ModelInterface {
    return $this->_model;
  }
}

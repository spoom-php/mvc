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
   * @param bool $overwrite Change storage data with the loaded ones
   *
   * @return static
   * @throws \LogicException Try to fetch without a key
   */
  public function fetch( bool $overwrite = true );
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
   * @param array          $context item data (with ot without key)
   * @param ModelInterface $model   item handler model
   * @param bool|null      $exist   initial exists state
   */
  public function __construct( ModelInterface $model, array $context ) {
    parent::__construct( $context );

    $this->_model = $model;
    $this->_key   = $this->_model->key( $context );
  }

  //
  public function fetch( bool $overwrite = true ) {
    if( !isset( $this->_key ) ) throw new \LogicException( "Item's key can't be empty..there is nothing to fetch!" );
    else {

      $item             = $this->_model->statement( $this->_key )->get();
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
    $statement = $this->_model->statement()->setField( [ $data ] );
    if( $exist ) $statement->setFilter( $this->_key )->update();
    else $this->_key = $statement->create();

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

      $this->_model->statement( $this->_key )->remove();
      $this->persistent = [];

      return $this;
    }
  }

  //
  public function getKey():?array {
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
    return !empty( $this->_key ) && $this->_model->statement( $this->_key )->count() > 0;
  }

  //
  public function getModel(): ModelInterface {
    return $this->_model;
  }
}

<?php namespace Spoom\MVC\View\Processor;

use Spoom\Core;
use Spoom\Core\Helper;
use Spoom\MVC\View;
use Spoom\MVC\View\ProcessorInterface;

/**
 * Class Converter
 *
 * @property bool $empty Add empty properties (as NULL) or not
 */
class Converter implements ProcessorInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * @var Core\ConverterInterface
   */
  private $_converter;
  /**
   * Add empty properties (as NULL) or not
   *
   * @var bool
   */
  private $_empty;

  /**
   * @param Core\ConverterInterface $converter
   * @param bool                    $empty
   */
  public function __construct( Core\ConverterInterface $converter, bool $empty = true ) {

    $this->_converter = $converter;
    $this->_empty     = $empty;
  }

  /**
   * @param View                        $view
   * @param null|Helper\StreamInterface $stream
   *
   * @return null|string
   * @throws Core\ConverterFailException
   */
  public function __invoke( View $view, ?Helper\StreamInterface $stream = null ) {

    // TODO remove empty (===null) values from the view (based on the _empty property)

    return $this->_converter->serialize( Helper\Collection::read( $view ), $stream );
  }

  /**
   * @return Core\ConverterInterface
   */
  public function getConverter(): Core\ConverterInterface {
    return $this->_converter;
  }

  /**
   * @return bool
   */
  public function isEmpty(): bool {
    return $this->_empty;
  }
  /**
   * @param bool $value
   *
   * @return static
   */
  public function setEmpty( bool $value ) {
    $this->_empty = $value;
    return $this;
  }
}

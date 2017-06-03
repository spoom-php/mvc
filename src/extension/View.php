<?php namespace Spoom\MVC;

use Spoom\Core\Helper;
use Spoom\MVC\View\ProcessorInterface;
use Spoom\Core\Helper\Structure;
use Spoom\Core\Helper\Text;

/**
 * Interface ViewInterface
 */
interface ViewInterface {

  /**
   * Render the view into the default format
   *
   * @return string
   */
  public function __toString();

  /**
   * Render the view into a string
   *
   * @param string|null                 $format The expected result format, or the stored format if null
   * @param Helper\StreamInterface|null $stream Output stream instead of the default string result
   *
   * @return null|string
   */
  public function render( ?string $format = null, ?Helper\StreamInterface $stream = null ):?string;

  /**
   * Check if the view can be rendered into that format
   *
   * @param string $format The tested format name
   *
   * @return bool
   */
  public function hasFormat( string $format ): bool;
  /**
   * @return string|null The default format
   */
  public function getFormat():?string;
  /**
   * Set the default format
   *
   * @param string|null $value
   *
   * @return static
   * @throws \InvalidArgumentException Unsupported format
   */
  public function setFormat( ?string $value );
}
/**
 * Class View
 */
abstract class View extends Structure implements ViewInterface {

  /**
   * Available format names. Only these formats can be rendered from the view
   *
   * @var string[]
   */
  protected const FORMAT_LIST = [];

  /**
   * The default render format
   *
   * @var null|string
   */
  private $_format;

  /**
   * @inheritdoc
   *
   * @param null|string $format
   */
  public function __construct( $input, ?string $format = null ) {
    parent::__construct( $input );

    $this->setFormat( $format );
  }

  //
  function __toString() {
    return $this->render();
  }

  //
  public function render( ?string $format = null, ?Helper\StreamInterface $stream = null ):?string {

    try {

      // check if the format is available for the view
      $format = $format ?? $this->getFormat();
      if( !$this->hasFormat( $format ) ) throw new \InvalidArgumentException( "Usupported format: {$format}" );
      else {

        // call pre render handler if exists
        $method = Text::camelify( 'generate.' . $format );
        $result = is_callable( [ $this, $method ] ) ? $this->{$method}( $stream ) : null;

        // TODO call post render event

        return $result;
      }

    } catch( \Throwable $e ) {
      // TODO $this->_exception = $e;
    }

    return null;
  }

  /**
   * Generate string from the view's data with the given processor
   *
   * @param string                    $format    The expected result format
   * @param ProcessorInterface|string $processor The processor that will generate the result
   * @param resource|null             $stream
   *
   * @return null|string
   * @throws \Throwable
   */
  protected function generate( string $format, ProcessorInterface $processor, &$stream = null ):?string {

    // TODO call pre generate event

    // run the processor to generate the final content
    return $processor( $this, $stream );
  }

  //
  public function hasFormat( string $format ): bool {
    return in_array( $format, static::FORMAT_LIST );
  }
  //
  public function getFormat():?string {
    return $this->_format;
  }
  //
  public function setFormat( ?string $value ) {
    if( $value && !$this->hasFormat( $value ) ) throw new \InvalidArgumentException( "Usupported format: {$value}" );
    else {

      $this->_format = $value;
      return $this;
    }
  }
}

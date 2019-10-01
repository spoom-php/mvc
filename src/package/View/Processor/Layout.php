<?php namespace Spoom\MVC\View\Processor;

use Spoom\Core;
use Spoom\Core\Helper;
use Spoom\MVC\View;
use Spoom\MVC\View\ProcessorInterface;

/**
 * Class Layout
 *
 * @property Core\FileInterface $file
 * @property bool               $runnable
 */
class Layout implements ProcessorInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Layout file
   *
   * @var Core\FileInterface
   */
  private $_file;
  /**
   * Execute the included file's php code or not (use fileread or include)
   *
   * @var bool
   */
  private $_runnable;

  /**
   * @param Core\FileInterface $file
   * @param bool               $runnable Execute the included file's php code or not (use fileread or include)
   */
  public function __construct( Core\FileInterface $file, bool $runnable = true ) {

    $this->_file     = $file;
    $this->_runnable = $runnable;
  }

  //
  public function __invoke( View $view, ?Helper\StreamInterface $stream = null ) {

    // check for file existance
    if( !$this->_file->exist() || !$this->_file->isReadable() ) throw new Core\FilePermissionException( $this->_file, Core\File::META_PERMISSION_READ );
    else if( !$this->_runnable ) return $this->_file->stream()->read( 0, null, $stream );
    else {

      // include the file with output buffering
      ob_start();

      /** @noinspection PhpIncludeInspection */
      @include (string) $this->_file;

      // return the result to the right "stream"
      if( !$stream ) return ob_get_contents();
      else {

        $stream->write( ob_get_contents() );
        return null;
      }
    }
  }

  /**
   * @return Core\FileInterface
   */
  public function getFile(): Core\FileInterface {
    return $this->_file;
  }
  /**
   * @param Core\FileInterface $value
   *
   * @return static
   */
  public function setFile( Core\FileInterface $value ) {
    $this->_file = $value;
    return $this;
  }
  /**
   * @return bool
   */
  public function isRunnable(): bool {
    return $this->_runnable;
  }
  /**
   * @param bool $value
   *
   * @return static
   */
  public function setRunnable( bool $value ) {
    $this->_runnable = $value;
    return $this;
  }
}

<?php namespace Spoom\MVC\View;

use Spoom\MVC\View;
use Spoom\Core\Helper;

/**
 * Interface ProcessorInterface
 * @package Spoom\MVC\View
 */
interface ProcessorInterface {

  /**
   * @param View                        $view   The view to process
   * @param null|Helper\StreamInterface $stream Output stream if presented
   *
   * @return string|null
   */
  public function __invoke( View $view, ?Helper\StreamInterface $stream = null );
}

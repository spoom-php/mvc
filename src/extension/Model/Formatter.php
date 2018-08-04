<?php namespace Spoom\MVC\Model;

use Spoom\MVC\Model;
use Spoom\MVC\Model\Definition\Field;
use Spoom\Core\Helper;
use Spoom\Core\Exception;

//
class Formatter {

  /**
   * @var bool
   */
  protected $nullable;
  /**
   * @var mixed
   */
  protected $default;

  /**
   * @param mixed|null $default
   * @param bool       $nullable
   */
  public function __construct( $default = null, bool $nullable = true ) {
    $this->default  = $default;
    $this->nullable = $nullable;
  }

  /**
   * Format and validate the input
   *
   * @param mixed $input The input to check and format
   * @param array $list  Full list of items with every field in them
   * @param Field $field The field that is using the formatter
   * @param int   $slot  The list index of the item
   *
   * @return mixed The formatter result
   * @throws FormatterException
   */
  public function __invoke( $input, array $list, Field $field, int $slot ) {

    //
    if( $this->isWrite( $field ) && is_null( $input ) && !$this->nullable ) {
      throw new FormatterExceptionNull( $field );
    }

    return is_null( $input ) ? $this->default : $input;
  }

  /**
   * Check if the current field is used on a write operation
   *
   * @param Field $field
   *
   * @return bool
   */
  protected function isWrite( Field $field ) {
    return in_array( $field->getStatement()->getMethod(), [ Model::METHOD_CREATE, Model::METHOD_UPDATE ] );
  }
}
//
class FormatterList extends Formatter {

  /**
   * @var array
   */
  private $allow_list;

  /**
   * @param array $allow_list Allowed list of field values
   * @param null  $default
   * @param bool  $nullable
   */
  public function __construct( array $allow_list, $default = null, bool $nullable = true ) {
    parent::__construct( $default, $nullable );

    $this->allow_list = $allow_list;
  }

  //
  public function __invoke( $input, array $list, Field $field, int $slot ) {

    //
    if( $this->isWrite( $field ) && $input !== null && !in_array( $input, $this->allow_list ) ) {
      throw new FormatterExceptionList( $field, $this->allow_list );
    }

    return parent::__invoke( $input, $list, $field, $slot );
  }
}
//
class FormatterNumber extends Formatter {

  /**
   * @var int|null
   */
  private $minimum;
  /**
   * @var int|null
   */
  private $maximum;
  /**
   * @var int
   */
  private $precision;

  /**
   * @param int|null $minimum   Minimum value on write (NULL means no limit)
   * @param int|null $maximum   Maximum value on write (NULL means no limit)
   * @param int      $precision Decimal count
   * @param null     $default
   * @param bool     $nullable
   */
  public function __construct( ?int $minimum = null, ?int $maximum = null, int $precision = 0, $default = null, bool $nullable = true ) {
    parent::__construct( $default, $nullable );

    $this->minimum   = $minimum;
    $this->maximum   = $maximum;
    $this->precision = $precision;
  }

  //
  public function __invoke( $input, array $list, Field $field, int $slot ) {

    if( $input !== null ) {

      //
      if( $this->isWrite( $field ) ) {
        if( !Helper\Number::is( $input ) ) throw new FormatterExceptionInvalid( $field, '/^[0-9]+$/' );
        else if( $this->minimum !== null && $this->maximum !== null && ( $this->minimum > $input || $this->maximum < $input ) ) throw new FormatterExceptionLength( $field, $this->minimum, $this->maximum );
        else if( $this->minimum !== null && $this->minimum > $input ) throw new FormatterExceptionLength( $field, $this->minimum );
        else if( $this->maximum !== null && $this->maximum < $input ) throw new FormatterExceptionLength( $field, null, $this->maximum );
      }

      // FIXME change this after `Helper\Number::read` supports precision
      $input = round( Helper\Number::read( $input, 0 ), $this->precision );
      if( $this->precision === 0 ) $input = intval( $input );
    }

    return parent::__invoke( $input, $list, $field, $slot );
  }
}
//
class FormatterString extends Formatter {

  /**
   * @var int|null
   */
  private $minimum;
  /**
   * @var int|null
   */
  private $maximum;
  /**
   * @var string|null
   */
  private $pattern;

  /**
   * @param int|null    $minimum Minimum value length on write (NULL means no limit)
   * @param int|null    $maximum Minimum value length on write (NULL means no limit)
   * @param null|string $pattern Optional pattern to match on write
   * @param null        $default
   * @param bool        $nullable
   */
  public function __construct( ?int $minimum = null, ?int $maximum = null, ?string $pattern = null, $default = null, bool $nullable = true ) {
    parent::__construct( $default, $nullable );

    $this->minimum = $minimum;
    $this->maximum = $maximum;
    $this->pattern = $pattern;
  }

  //
  public function __invoke( $input, array $list, Field $field, int $slot ) {

    if( $input !== null ) {
      $input = Helper\Text::read( $input );

      if( $this->isWrite( $field ) ) {
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $input ) : strlen( $input );
        if( $this->minimum !== null && $this->maximum !== null && ( $this->minimum > $length || $this->maximum < $length ) ) throw new FormatterExceptionLength( $field, $this->minimum, $this->maximum );
        else if( $this->minimum !== null && $this->minimum > $length ) throw new FormatterExceptionLength( $field, $this->minimum );
        else if( $this->maximum !== null && $this->maximum < $length ) throw new FormatterExceptionLength( $field, null, $this->maximum );
        else if( $this->pattern && !preg_match( $this->pattern, $input ) ) throw new FormatterExceptionInvalid( $field, $this->pattern );
      }
    }

    return parent::__invoke( $input, $list, $field, $slot );
  }
}
//
class FormatterDatetime extends Formatter {

  /**
   * @var string
   */
  private $pattern;

  /**
   * @param string $pattern Default output format for writes
   * @param null   $default
   * @param bool   $nullable
   */
  public function __construct( string $pattern = 'Y-m-d\TH:i:sP', $default = null, bool $nullable = true ) {
    parent::__construct( $default, $nullable );

    $this->pattern = $pattern;
  }

  //
  public function __invoke( $input, array $list, Field $field, int $slot ) {

    if( $input !== null ) {

      $tmp = strtotime( $input );
      if( $this->isWrite( $field ) && $tmp < 1 ) {
        throw new FormatterExceptionInvalid( $field, $this->pattern );
      }

      $input = date( $this->pattern, $tmp );
    }

    return parent::__invoke( $input, $list, $field, $slot );
  }
}

//
interface FormatterException extends Helper\ThrowableInterface {
}
class FormatterExceptionNull extends Exception implements FormatterException {

  const ID = '0#spoom-mvc';

  public function __construct( Field $field ) {

    $data = [ 'field' => (string) $field->getName() ];
    parent::__construct( Helper\Text::apply( 'Field is required, cannot be empty', $data ), static::ID, $data );
  }
}
class FormatterExceptionInvalid extends Exception implements FormatterException {

  const ID = '0#spoom-mvc';

  public function __construct( Field $field, string $format ) {

    $data = [ 'field' => (string) $field->getName(), 'format' => $format ];
    parent::__construct( Helper\Text::apply( 'Field must match the \'{format}\' format', $data ), static::ID, $data );
  }
}
class FormatterExceptionLength extends Exception implements FormatterException {

  const ID = '0#spoom-mvc';

  public function __construct( Field $field, ?int $minimum = null, ?int $maximum = null ) {

    $message = '';
    switch( true ) {
      case $minimum !== null && $maximum !== null:
        $message = "Field value must be between {minimum} and {maximum}";
        break;
      case $minimum !== null:
        $message = "Field value is too short, must be greater than {minimum}";
        break;
      case $maximum !== null:
        $message = "Field value is too large, must be lower than {maximum}";
        break;
    }

    $data = [ 'field' => (string) $field->getName(), 'minimum' => $minimum, 'maximum' => $maximum ];
    parent::__construct( Helper\Text::apply( $message, $data ), static::ID, $data );
  }
}
class FormatterExceptionList extends Exception implements FormatterException {

  const ID = '0#spoom-mvc';

  public function __construct( Field $field, array $allow_list ) {

    $data = [ 'field' => (string) $field->getName(), 'list' => implode( ',', $allow_list ) ];
    parent::__construct( Helper\Text::apply( "", $data ), static::ID, $data );
  }
}
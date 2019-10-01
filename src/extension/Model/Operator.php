<?php namespace Spoom\MVC\Model;

//
class Operator {

  /**
   * Simple equality check
   *
   * Use `static::FLAG_LOOSE` to match with loose comparison (instead of strict)
   */
  const DEFAULT = '';
  /**
   * Match values that is greater than the test value(s)
   *
   * Use `static::FLAG_LOOSE` to match greater OR equal values
   */
  const GREATER = '>';
  /**
   * Match values that is lesser than the test value(s)
   *
   * Use `static::FLAG_LOOSE` to match lesser OR equal values
   */
  const LESSER = '<';
  /**
   * Search from the beginning in texts
   *
   * Use `static::FLAG_LOOSE` to match without case-sensitivity
   */
  const BEGIN = '^';
  /**
   * Search from the end in texts
   *
   * Use `static::FLAG_LOOSE` to match without case-sensitivity
   */
  const END = '$';
  /**
   * Simple containing search in texts
   *
   * Use `static::FLAG_LOOSE` to match without case-sensitivity
   */
  const CONTAIN = '*';
  /**
   * Natural language search in texts
   */
  const SEARCH = '%';
  /**
   * Search with custom pattern
   */
  const PATTERN = '?';
  /**
   * Search with regexp pattern
   */
  const REGEXP = '|';

  /**
   * This should indicate loose matching (ignore case, inclusive intervals, etc)
   */
  const FLAG_LOOSE = '=';
  /**
   * Invert the final result of the operator
   */
  const FLAG_NOT = '!';
  /**
   * Allow to apply the operator on multiple values
   */
  const FLAG_ANY = '[]';

  const NOT_EQUAL     = self::FLAG_NOT . self::DEFAULT;
  const EQUAL_ANY     = self::FLAG_ANY . self::DEFAULT;
  const NOT_EQUAL_ANY = self::FLAG_ANY . self::FLAG_NOT . self::DEFAULT;

  const GREATER_ANY       = self::FLAG_ANY . self::GREATER;
  const GREATER_EQUAL     = self::GREATER . self::FLAG_LOOSE;
  const GREATER_EQUAL_ANY = self::FLAG_ANY . self::GREATER . self::FLAG_LOOSE;

  const LESSER_ANY       = self::FLAG_ANY . self::LESSER;
  const LESSER_EQUAL     = self::LESSER . self::FLAG_LOOSE;
  const LESSER_EQUAL_ANY = self::FLAG_ANY . self::LESSER . self::FLAG_LOOSE;

  /**
   * Main type of the operator
   *
   * @var string
   */
  private $_type;
  /**
   * Flag list
   *
   * @var string[]
   */
  private $_flag_list = [];

  /**
   * @param string $value
   */
  public function __construct( string $value ) {

    $flag_list = [ static::FLAG_ANY, static::FLAG_NOT, static::FLAG_LOOSE ];
    foreach( $flag_list as $flag ) {
      if( strpos( $value, $flag ) !== false ) {
        $this->_flag_list[] = $flag;
      }
    }

    $this->_type = str_replace( $flag_list, '', $value );
  }
  /**
   * @return string
   */
  public function __toString(): string {

    $result   = '';
    $template = [ static::FLAG_ANY, static::FLAG_NOT, null, static::FLAG_LOOSE ];
    foreach( $template as $flag ) {
      if( $flag === null ) $result .= $this->_type;
      else if( $this->isFlag( $flag ) ) $result .= $flag;
    }

    return $result;
  }

  /**
   * @return string
   */
  public function getType(): string {
    return $this->_type;
  }
  /**
   * @param string $flag
   *
   * @return bool
   */
  public function isFlag( string $flag ): bool {
    return in_array( $flag, $this->_flag_list );
  }

  /**
   * @return bool
   */
  public function isLoose(): bool {
    return $this->isFlag( static::FLAG_LOOSE );
  }
  /**
   * @return bool
   */
  public function isNot(): bool {
    return $this->isFlag( static::FLAG_NOT );
  }
  /**
   * @return bool
   */
  public function isAny(): bool {
    return $this->isFlag( static::FLAG_ANY );
  }

  /**
   * Attach $operator into a definition $name
   *
   * @param string $operator
   * @param string $name
   *
   * @return string The $name with the attached $operator
   */
  public static function attach( string $operator, string $name ): string {
    return $name . $operator;
  }
  /**
   * Detach $operator from a definition $name
   *
   * @param string      $name A definition name with or without operator
   * @param string|null $operator The detached operator "output" if any
   *
   * @return string The $name without the attached $operator
   */
  public static function detach( string $name, ?string &$operator = null ): string {
    $tmp      = preg_replace( '/[^a-z0-9_-]+$/i', '', $name );
    $operator = substr( $name, strlen( $tmp ) );
    return $tmp;
  }
}
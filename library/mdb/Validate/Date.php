<?php
/**
 * Zend_Validate_Abstract
 */
require_once 'Zend/Validate/Abstract.php';

/**
 * mdb_Validate_Date
 * 
 * The class Zend_Validate_Date is based on Travello_Validate_Date written by Ralf Eggert
 * <r.eggert@travello.de> to provide a validation for dates, but adds a few extra features
 * such as support for PHP date format syntax and valid date ranges
 * 
 * @package    Framework
 * @author     Ralf Eggert <r.eggert@travello.de>
 * @author     Steve Goodman <steve@oeic.net>
 * @copyright  Copyright (c) 2007 Travello GmbH
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class mdb_Validate_Date extends Zend_Validate_Abstract {
	/**
	 * Array of validation failure messages
	 *
	 * @var array
	 */
	protected $_messages = array ();
	
	/**
	 * Validation failure message key for when the value does not follow the YYYY-MM-DD format
	 */
	const NOT_FORMATTED = 'dateNotFormatted';
	
	/**
	 * Validation failure message key for when the value does not appear to be a valid date
	 */
	const INVALID = 'dateInvalid';
	
	/**
	 * Validation failure message key for when the value is before the allowed date range.
	 */
	const BEFORE_START_DATE = 'dateBeforeStartDate';
	
	/**
	 * Validation failure message key for when the value is after the allowed date range.
	 */
	const AFTER_END_DATE = 'dateAfterEndDate';
	
	/**
	 * Constant used when beginning or end of a date range is defined by the current day
	 */
	const TODAY = 'TODAY';
	
	/**
	 * Validation failure message template definitions
	 *
	 * @var array
	 */
	protected $_messageTemplates = array (self::NOT_FORMATTED => "'%value%' is not a valid date of the format '%format%'", self::INVALID => "'%value%' does not appear to be a valid date", self::BEFORE_START_DATE => "%value% is before the earliest allowed date of %startDate%", self::AFTER_END_DATE => "%value% is after the latest allowed date of %endDate%" );
	
	/**
	 * @var array
	 */
	protected $_messageVariables = array ('startDate' => '_startDate', 'endDate' => '_endDate', 'format' => '_format', 'value' => '_value' );
	
	/**
	 * Date format string
	 *
	 * @var string
	 */
	protected $_format;
	
	/**
	 * Date parts array
	 *
	 * @var array
	 */
	protected $_parts = array ();
	
	/**
	 * Beginning of acceptable date range
	 *
	 * @var string
	 */
	protected $_startDate;
	
	/**
	 * End of acceptable date range
	 *
	 * @var string
	 */
	protected $_endDate;
	
	/**
	 * The passed in date to validate
	 *
	 * @var string
	 */
	protected $_value;
	
	/**
	 * Sets validator options
	 *
	 * @param  mixed $format
	 * @return void
	 */
	public function __construct($format = 'm/d/Y', $startDate = null, $endDate = null) {
		$matches = array();
		if (! preg_match ( '=([djFmMnyY])[./\- ]([djFmMnyY])[./\- ]([djFmMnyY])=', $format, $matches )) {
			throw new Zend_Validate_Exception ( 'Invalid date format. The supported PHP formatting codes are d, j, F, m, M, n, y and Y.' );
		}
		
		$this->setFormat ( $format );
		
		array_shift ( $matches );
		
		$this->setParts ( $matches );
		
		$this->setStartDate ( $startDate );
		$this->setEndDate ( $endDate );
	}
	
	/**
	 * Returns the format string
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->_format;
	}
	
	/**
	 * Sets the format string
	 *
	 * @param  mixed $pattern
	 * @return Zend_Validate_Date Provides a fluent interface
	 */
	public function setFormat($format) {
		$this->_format = ( string ) $format;
		return $this;
	}
	
	/**
	 * Returns the date parts array
	 *
	 * @return array
	 */
	public function getParts() {
		return $this->_parts;
	}
	
	/**
	 * Sets the date parts array
	 *
	 * @param  array $parts
	 * @return Zend_Validate_Date Provides a fluent interface
	 */
	public function setParts($parts) {
		$this->_parts = ( array ) $parts;
		return $this;
	}
	
	/**
	 * Sets the start date for the valid date range
	 *
	 * @param string $date
	 * @return Zend_Validate_Date Provides a fluent interface
	 */
	public function setStartDate($date) {
		$this->_startDate = $date;
		return $this;
	}
	
	/**
	 * Sets the end date for the valid date range
	 *
	 * @param string $date
	 * @return Zend_Validate_Date Provides a fluent interface
	 */
	public function setEndDate($date) {
		$this->_endDate = $date;
		return $this;
	}
	
	/**
	 * Returns the start date
	 *
	 * @return string
	 */
	public function getStartDate() {
		return ($this->_startDate ? ($this->_startDate == self::TODAY ? time () : strtotime ( $this->_startDate )) : NULL);
	}
	
	/**
	 * Returns the start date
	 *
	 * @return string
	 */
	public function getEndDate() {
		return ($this->_endDate ? ($this->_endDate == self::TODAY ? time () : strtotime ( $this->_endDate )) : NULL);
	}
	
	/**
	 * Defined by Zend_Validate_Interface
	 *
	 * Returns true if and only if $value is filled with a value
	 *
	 * @param  string $value
	 * @return boolean
	 */
	public function isValid($value) {
		$this->_value = $value;
		$this->_messages = array ();
		
		$div1 = substr ( $this->getFormat (), 1, 1 );
		$div2 = substr ( $this->getFormat (), 3, 1 );
		
		$parts [0] = substr ( $this->getFormat (), 0, 1 );
		$parts [1] = substr ( $this->getFormat (), 2, 1 );
		$parts [2] = substr ( $this->getFormat (), 4, 1 );
		
		$scanformat = (in_array ( $parts [0], array ('F', 'M' ) ) ? '%[a-zA-Z]' : '%d') . $div1 . (in_array ( $parts [1], array ('F', 'M' ) ) ? '%[a-zA-Z]' : '%d') . $div2 . (in_array ( $parts [2], array ('F', 'M' ) ) ? '%[a-zA-Z]' : '%d');
		$scan = sscanf ( $value, $scanformat );
		
		foreach ( $this->_parts as $partKey => $partValue ) {
			if ($partValue == 'y' || $partValue == 'Y') {
				$year = $scan [$partKey];
				if (strlen ( $year ) != 4 && $partValue == 'Y') {
					$this->_error ( self::NOT_FORMATTED );
					return false;
				} elseif (strlen ( $year ) != 2 && $partValue == 'y') {
					$this->_error ( self::NOT_FORMATTED );
					return false;
				}
			} elseif (in_array ( $partValue, array ('F', 'm', 'M', 'n' ) )) {
				if ($partValue == 'M') {
					$months = array ('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
					$month = array_search ( ucfirst ( strtolower ( $scan [$partKey] ) ), $months );
					if ($month === false) {
						$this->_error ( self::NOT_FORMATTED );
						return false;
					}
				} elseif ($partValue == 'F') {
					$months = array ('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
					$month = array_search ( ucfirst ( strtolower ( $scan [$partKey] ) ), $months );
					if ($month === false) {
						$this->_error ( self::NOT_FORMATTED );
						return false;
					}
				} else {
					$month = $scan [$partKey];
				}
			} elseif ($partValue == 'd' || $partValue == 'j') {
				$day = $scan [$partKey];
			}
		}
		
		if (! checkdate ( $month, $day, $year )) {
			$this->_error ( self::NOT_FORMATTED );
			return false;
		}
		
		$startTime = $this->getStartDate ();
		if ($startTime) {
			if ($startTime > mktime ( 0, 0, 0, $month, $day, $year )) {
				$this->_error ( self::BEFORE_START_DATE );
				return false;
			}
		}
		
		$endTime = $this->getEndDate ();
		if ($endTime) {
			if ($endTime < mktime ( 0, 0, 0, $month, $day, $year )) {
				$this->_error ( self::AFTER_END_DATE );
				return false;
			}
		}
		
		return true;
	}
}

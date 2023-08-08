<?php
/**
 * @see       https://github.com/zendframework/zend-mail for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-mail/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mail\Header;

use Zend\Mime\Mime;

class GenericHeader implements HeaderInterface, UnstructuredInterface
{
    /**
     * @var string
     */
    protected $fieldName = null;

    /**
     * @var string
     */
    protected $fieldValue = null;

    /**
     * Header encoding
     *
     * @var null|string
     */
    protected $encoding;

    /**
     * @param string $headerLine
     * @return GenericHeader
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = self::splitHeaderLine($headerLine);
        $value  = HeaderWrap::mimeDecodeValue($value);
        $header = new static($name, $value);

        return $header;
    }

    /**
     * Splits the header line in `name` and `value` parts.
     *
     * @param string $headerLine
     * @return string[] `name` in the first index and `value` in the second.
     * @throws Exception\InvalidArgumentException If header does not match with the format ``name:value``
     */
    public static function splitHeaderLine($headerLine)
    {
        $parts = explode(':', $headerLine, 2);

        $name = strtolower(trim($parts[0]));
        $value = HeaderValue::filter(trim($parts[1]));
      
        //фиксы
        if(stripos($name,"subject")!==false && !preg_match("~^\=\?~is",$value)){
            $value = '=?UTF-8?B?'. base64_encode($value) .'?=';
        }

        if (count($parts) !== 2) {
            throw new Exception\InvalidArgumentException('Header must match with the format "name:value"');
        }

        if (! HeaderName::isValid($name)) {
            throw new Exception\InvalidArgumentException('Invalid header name detected');
        }

        if (! HeaderValue::isValid($value)) {
            throw new Exception\InvalidArgumentException('Invalid header value detected ' . $headerLine);
        }

        $parts[1] = $value;

        return $parts;
    }

    /**
     * Constructor
     *
     * @param string $fieldName  Optional
     * @param string $fieldValue Optional
     */
    public function __construct($fieldName = null, $fieldValue = null)
    {
        if ($fieldName) {
            $this->setFieldName($fieldName);
        }

        if ($fieldValue !== null) {
            $this->setFieldValue($fieldValue);
        }
    }

    /**
     * Set header name
     *
     * @param  string $fieldName
     * @return GenericHeader
     * @throws Exception\InvalidArgumentException;
     */
    public function setFieldName($fieldName)
    {
        if (! is_string($fieldName) || empty($fieldName)) {
            throw new Exception\InvalidArgumentException('Header name must be a string');
        }

        // Pre-filter to normalize valid characters, change underscore to dash
        $fieldName = str_replace(' ', '-', ucwords(str_replace(['_', '-'], ' ', $fieldName)));

        if (! HeaderName::isValid($fieldName)) {
            throw new Exception\InvalidArgumentException(
                'Header name must be composed of printable US-ASCII characters, except colon.'
            );
        }

        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set header value
     *
     * @param  string $fieldValue
     * @return GenericHeader
     * @throws Exception\InvalidArgumentException;
     */
    public function setFieldValue($fieldValue)
    {
        $fieldValue = (string) $fieldValue;

        if (! HeaderWrap::canBeEncoded($fieldValue)) {
            throw new Exception\InvalidArgumentException(
                'Header value must be composed of printable US-ASCII characters and valid folding sequences.'
            );
        }

        $this->fieldValue = $fieldValue;
        $this->encoding   = null;

        return $this;
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        if (HeaderInterface::FORMAT_ENCODED === $format) {
            return HeaderWrap::wrap($this->fieldValue, $this);
        }

        return $this->fieldValue;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        if (! $this->encoding) {
            $this->encoding = Mime::isPrintable($this->fieldValue) ? 'ASCII' : 'UTF-8';
        }

        return $this->encoding;
    }

    public function toString()
    {
        $name = $this->getFieldName();
        if (empty($name)) {
            throw new Exception\RuntimeException('Header name is not set, use setFieldName()');
        }
        $value = $this->getFieldValue(HeaderInterface::FORMAT_ENCODED);

        return $name . ': ' . $value;
    }
}

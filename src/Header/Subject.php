<?php
/**
 * @see       https://github.com/zendframework/zend-mail for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-mail/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mail\Header;

use Zend\Mail\Headers;
use Zend\Mime\Mime;

/**
 * Subject header class methods.
 *
 * @see https://tools.ietf.org/html/rfc2822 RFC 2822
 * @see https://tools.ietf.org/html/rfc2047 RFC 2047
 */
class Subject implements UnstructuredInterface
{
    /**
     * @var string
     */
    protected $subject = '';

    /**
     * Header encoding
     *
     * @var null|string
     */
    protected $encoding;

    public static function fromString($headerLine)
    {

        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);

        //пробуем склеить части заголовков
        if(preg_match_all('~(\=\?.*?\?B\?)(.*?)(\?\=)~is',$value,$match)){
            if(count($match[2])>1){
                $clean = '';
                foreach ($match[2] as $data){
                    $clean .= $data;
                }
                $value = trim( $match[1][0] . $clean . $match[3][0]  );
            }
        }

        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'subject') {
            throw new Exception\InvalidArgumentException('Invalid header line for Subject string');
        }

        $header = new static();
        $header->setSubject(trim($value));

        return $header;
    }

    public function getFieldName()
    {
        return 'Subject';
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        if (HeaderInterface::FORMAT_ENCODED === $format) {
            return HeaderWrap::wrap($this->subject, $this);
        }

        return $this->subject;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        if (! $this->encoding) {
            $this->encoding = Mime::isPrintable($this->subject) ? 'ASCII' : 'UTF-8';
        }

        return $this->encoding;
    }

    public function setSubject($subject)
    {
        $subject = (string) $subject;

        if (! HeaderWrap::canBeEncoded($subject)) {
            $subject = '=?UTF-8?B?'. base64_encode($subject) .'?=';
            if (! HeaderWrap::canBeEncoded($subject)) {
                throw new Exception\InvalidArgumentException(
                    'Subject value must be composed of printable US-ASCII or UTF-8 characters.'
                );
            }
        }

        $this->subject  = $subject;
        $this->encoding = null;

        return $this;
    }

    public function toString()
    {
        return 'Subject: ' . $this->getFieldValue(HeaderInterface::FORMAT_ENCODED);
    }
}

<?php
/**
 * @see       https://github.com/zendframework/zend-mail for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-mail/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mail\Header;

final class HeaderValue
{
    /**
     * No public constructor.
     */
    private function __construct()
    {
    }

    /**
     * Filter the header value according to RFC 2822
     *
     * @see    http://www.rfc-base.org/txt/rfc-2822.txt (section 2.2)
     * @param  string $value
     * @return string
     */
    public static function filter($value)
    {
        $result = '';
        $total  = mb_strlen($value);

        // Filter for CR and LF characters, leaving CRLF + WSP sequences for
        // Long Header Fields (section 2.2.3 of RFC 2822)
        for ($i = 0; $i < $total; $i += 1) {

            $char = mb_substr($value,$i,1);

            $ord = mb_ord($char);

            /**
             * @todo Lanetz фикс, добавлена возможность раборты с кирилицей
             */
            if ($ord === 10 || ($ord > 127 && $ord < 1040)) {
                continue;
            }

            if ($ord === 13) {
                if ($i + 2 >= $total) {
                    continue;
                }

                $lf = mb_ord(mb_substr($value,$i+1,1));
                $sp = mb_ord(mb_substr($value,$i+2,1));

                if ($lf !== 10 || $sp !== 32) {
                    continue;
                }

                $result .= "\r\n ";
                $i += 2;
                continue;
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * Determine if the header value contains any invalid characters.
     *
     * @see    http://www.rfc-base.org/txt/rfc-2822.txt (section 2.2)
     * @param string $value
     * @return bool
     */
    public static function isValid($value)
    {
        $total = mb_strlen($value);

        for ($i = 0; $i < $total; $i += 1) {

            $char = mb_substr($value,$i,1);

            $ord = mb_ord($char);

            // bare LF means we aren't valid

            if ($ord === 10 || ($ord > 127 && $ord < 1040)) {
                return false;
            }

            if ($ord === 13) {
                if ($i + 2 >= $total) {
                    return false;
                }

                $lf = mb_ord(mb_substr($value,$i+1,1));
                $sp = mb_ord(mb_substr($value,$i+2,1));

                if ($lf !== 10 || ! in_array($sp, [9, 32], true)) {
                    return false;
                }

                // skip over the LF following this
                $i += 2;
            }
        }

        return true;
    }

    /**
     * Assert that the header value is valid.
     *
     * Raises an exception if invalid.
     *
     * @param string $value
     * @throws Exception\RuntimeException
     * @return void
     */
    public static function assertValid($value)
    {
        if (! self::isValid($value)) {
            throw new Exception\RuntimeException('Invalid header value detected');
        }
    }
}

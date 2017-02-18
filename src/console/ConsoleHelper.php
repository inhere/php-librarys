<?php


namespace inhere\librarys\console;

/**
 *
 */
class ConsoleHelper
{

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR.'.'.PHP_WINDOWS_VERSION_MINOR.'.'.PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * get key Max Width
     *
     * @param  array  $data
     * [
     *     'key1'      => 'value1',
     *     'key2-test' => 'value2',
     * ]
     * @return int
     */
    public static function getKeyMaxWidth(array $data, $expactInt = true)
    {
        $keyMaxWidth = 0;

        foreach ($data as $key => $value) {
            // key is not a integer
            if ( !$expactInt || !is_numeric($key) ) {
                $width = mb_strlen($key, 'UTF-8');
                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }

        return $keyMaxWidth;
    }

    /**
     * spliceArray
     * @param  array  $data
     * e.g [
     *     'system'  => 'Linux',
     *     'version'  => '4.4.5',
     * ]
     * @param  int    $keyMaxWidth
     * @param  array  $opts
     * @return string
     */
    public static function spliceKeyValue(array $data, array $opts = [])
    {
        $text = '';
        $opts = array_merge([
            'leftChar'    => '',   // e.g '  ', ' * '
            'sepChar'     => ' ',  // e.g ' | ' => OUT: key | value
            'keyStyle'    => '',   // e.g 'info','commont'
            'keyMaxWidth' => null, // if not set, will automatic calculation
        ], $opts);

        if ( !is_numeric($opts['keyMaxWidth']) ) {
            $opts['keyMaxWidth'] = self::getKeyMaxWidth($data);
        }

        $keyStyle = trim($opts['keyStyle']);

        foreach ($data as $key => $value) {
            $text .= $opts['leftChar'];

            if ($opts['keyMaxWidth']) {
                $key = str_pad($key, $opts['keyMaxWidth'], ' ');
                $text .= ( $keyStyle ? "<{$keyStyle}>$key</{$keyStyle}> " : $key ) . $opts['sepChar'];
            }

            // if value is array, translate array to string
            if ( is_array($value) ) {
                $temp = '';

                foreach ($value as $key => $val) {
                    if (is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }

                    $temp .= (!is_numeric($key) ? "$key: " : '') . "<info>$val</info>, ";
                }

                $value = rtrim($temp, ' ,');
            } else if (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = (string)$value;
            }

            $text .= "$value\n";
        }

        return $text;
    }
}

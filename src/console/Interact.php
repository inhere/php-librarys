<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: CliInteract 命令行交互
 * file: CliInteract.php
 */

namespace inhere\librarys\console;
use inhere\librarys\exceptions\InvalidArgumentException;

/**
 * Class Interact
 * @package inhere\librarys\console
 */
class Interact
{
    const STAR_LINE = "*************************************%s*************************************\n";

    const NL = "\n";// new line
    const TAB    = '    ';
    const NL_TAB = "\n    ";// new line + tab

//////////////////////////////////////// Interactive ////////////////////////////////////////

    /**
     * 多行信息展示
     * @param  mixed $data
     * @param  string $title
     * @return void
     */
    public static function panel($data, $title='Info panel')
    {
        $data = is_array($data) ? array_filter($data) : [trim($data)];
        self::write("\n  " . sprintf(self::STAR_LINE,"<bold>$title</bold>"), false);

        foreach ($data as $label => $value) {
            $line = '  * ';
            if (!is_numeric($label)) {
                $line .= "$label: ";
            }

            self::write($line . $value);
        }

        $star = $title ? substr(self::STAR_LINE, 0, strlen($title)): '';

        self::write('  ' . sprintf(self::STAR_LINE, $star ));
    }

    /**
     * 多选一
     * @param  string $question 说明
     * @param  mixed $option  选项数据
     * @param  bool $allowExit  有退出选项 默认 true
     * @return string
     */
    public static function choice($question, $option, $allowExit=true)
    {
        echo self::NL_TAB . $question;

        $option    = is_array($option) ? $option : explode(',', $option);
        // no set key
        $isNumeric = isset($option[0]);
        $keys = [];

        foreach ($option as $key => $value) {
            $keys[] = $isNumeric ? ++$key : $key;

            echo self::NL_TAB . " $key) $value";
        }

        if ($allowExit) {
            $keys[] = 'q';

            echo self::NL_TAB . ' q) quit';
        }

        echo self::NL_TAB . 'You choice : ';

        $r = self::readRow();

        if ( !in_array($r, $keys) ) {
            echo self::TAB . "warning! option $r) don't exists! please entry again! :";

            $r = self::readRow();
        }

        if ($r === 'q' || !in_array($r, $keys) ) {
            exit("\n\n Quit,ByeBye.\n");
        }

        return $r;
    }

    /**
     * 确认, 发出信息要求确认；返回 true | false
     * @param  string $question 发出的信息
     * @param bool $default
     * @return bool
     */
    public static function confirm($question, $default = true)
    {
        $question = ucfirst(trim($question));
        $defaultText = $default ? 'yes' : 'no';

        $message = "<comment>$question</comment>\n    Please confirm (yes|no) [default:<info>$defaultText</info>]: ";
        static::write($message, false);

        $answer = self::readRow();

        return $answer ? !strncasecmp($answer, 'y', 1) : (bool)$default;
    }

    /**
     * 询问，提出问题；返回 输入的结果
     * @param  string $question 问题
     * @param null $default 默认值
     * @param \Closure $validator
     * @example
     *  $answer = Interact::ask('Are you sure publish?', null, function ($answer) {
     *      if (!is_integer($answer)) {
     *           throw new \RuntimeException('You must type an integer.');
     *       }
     *
     *       return $answer;
     *   });
     *
     * @return string
     */
    public static function ask($question, $default = null, \Closure $validator = null)
    {
        if (!$question) {
            throw new InvalidArgumentException('Please provide a question!');
        }

        // $question = ucfirst(trim($question));
        static::write(ucfirst(trim($question)));
        $answer = self::readRow();

        if ('' === $answer && null === $default ) {
            static::error('A value is required.');
            static::ask($question, $default, $validator);
        }

        return $answer;
    }

    /**
     * 持续询问，提出问题；
     * 若输入了值且验证成功则返回 输入的结果
     * 否则，会连续询问 $allowed 次， 若任然错误，退出
     * @param  string $question 问题
     * @param callable $callbackVerify (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
     * e.g.
     * Interact::loopAsk('please entry you age?', function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         return 'Allow the input range is 1-100';
     *     }
     *
     *     return true;
     * } );
     *
     * @param int $allowed 允许错误次数
     * @return string
     */
    public static function loopAsk($question, callable $callbackVerify = null, $allowed=3)
    {
        $question = ucfirst(trim($question));

        if (!$question) {
            throw new InvalidArgumentException('Please provide a question!');
        }

        $allowed = ((int)$allowed > 6 || $allowed < 1) ? 3 : (int)$allowed;
        $loop  = true;
        $key  = 1;

        while ($loop) {
            echo "\n    $question ";
            $answer = self::readRow();

            if ($callbackVerify && is_callable($callbackVerify)) {
                $msg = call_user_func($callbackVerify, $answer);

                if ($msg === true) {
                    break;
                }

                echo self::TAB. '  ' .($msg ?: 'Verify failure!!');
            } else if ( $answer !== '') {
                break;
            }

            if ($key === $allowed) {
                exit(self::NL_TAB."You've entered incorrectly $allowed times in a row !!\n");
            }

            $key++;
        }

        /** @var string $answer */
        return $answer;
    }

    public static function primary($messages, $type = 'IMPORTANT')
    {
        static::block($messages, $type, 'primary');
    }
    public static function success($messages, $type = 'SUCCESS')
    {
        static::block($messages, $type, 'success');
    }
    public static function info($messages, $type = 'INFO')
    {
        static::block($messages, $type, 'info');
    }
    public static function warning($messages, $type = 'WARNING')
    {
        static::block($messages, $type, 'warning');
    }
    public static function danger($messages, $type = 'DANGER')
    {
        static::block($messages, $type, 'danger');
    }
    public static function error($messages, $type = 'ERROR')
    {
        static::block($messages, $type, 'error');
    }
    public static function comment($messages, $type = 'COMMENT')
    {
        static::block($messages, $type, 'comment');
    }
    public static function question($messages, $type = '')
    {
        static::block($messages, $type, 'question');
    }

    /**
     * @param $messages
     * @param string|null $type
     * @param string|array $style
     */
    public static function block($messages, $type = null, $style='default')
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        // add type
        if (null !== $type) {
            $messages[0] = sprintf('[%s] %s', $type, $messages[0]);
        }

        $text = implode(PHP_EOL, $messages);
        $color = static::getColor();

        if (is_string($style) && $color->hasStyle($style)) {
            $text = "<{$style}>$text</{$style}>";
        }

        // $this->write($text);
        static::write($text);
    }

    /**
     * @var Color
     */
    private static $color;

    public static function getColor()
    {
        if (!static::$color) {
            static::$color = new Color();
        }

        return static::$color;
    }

    /**
     * 读取输入信息
     * @return string
     */
    public static function readRow()
    {
        return trim(fgets(STDIN));
    }

    /**
     * 输出，
     * @param  string $text
     * @param bool $newLine true 会在前添加换行符并自动缩进 false 原样输出，不添加换行符
     * @param  boolean $exit
     */
    public static function write($text, $newLine=true, $exit=false)
    {
        $text = static::getColor()->format($text);
        echo $text . ($newLine ? self::NL : '');

        $exit && exit();
    }

    /**
     * @param $msg
     */
    public static function title($msg)
    {
        $msg = ucfirst(trim($msg));
        $length = mb_strlen($msg, 'UTF-8');
        $str = str_pad('=',$length + 6, '=');

        static::write("   $msg   ");
        static::write($str."\n");
    }

    /**
     * @param $msg
     */
    public static function section($msg)
    {
        $msg = ucfirst(trim($msg));
        $length = mb_strlen($msg, 'UTF-8');
        $str = str_pad('-',$length + 6, '-');

        static::write("   $msg   ");
        static::write($str."\n");
    }

} // end class

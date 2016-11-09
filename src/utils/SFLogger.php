<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/9/27
 * Time: 14:17
 */

namespace inhere\librarys\utils;

use Psr\Log\AbstractLogger;
use inhere\librarys\helpers\PublicHelper;

/**
 * simple file logger SFLogger
 * Class SFLogger
 * @package inhere\librarys\utils
 */
class SFLogger extends AbstractLogger
{
    /**
     * logger instance list
     * @var static[]
     */
    private static $loggers = [];

    /**
     * @var bool
     */
    private $_hasLogged = false;

    /**
     * log text records list
     * @var array
     */
    private $_records = [];

    /**
     * 日志实例名称
     * @var string
     */
    public $name = 'default';

    /**
     * file content max size. (M)
     * @var int
     */
    protected $maxSize = 2;

    /**
     * 存放日志的基础路径
     * @var string
     */
    protected $basePath;

    /**
     * log path = $bashPath + $subFolder
     * 文件夹名称
     * @var string
     */
    protected $subFolder;

    /**
     * 日志文件名称处理
     * @var \Closure
     */
    protected $filenameHandler;

    /**
     * @var array
     */
    protected $levels = [];

    /**
     * level name
     * @var string
     */
    protected $levelName = 'info';

    /**
     * split file by level name
     * @var bool
     */
    protected $splitFile = false;

    /**
     * default format
     */
    const SIMPLE_FORMAT = "[{datetime}] {channel}.{level_name}: {message} {context} {extra}\n";

    /**
     * 格式
     * @var string
     */
    public $format = '[{datetime}] {channel}.{level_name}: {message} {context}';

    /**
     * create new instance or get exists instance
     * @param string|array $config
     * @return static
     */
    public static function make($config)
    {
        if ( $config && is_string($config) ) {
            $name = $config;

            if ( isset(self::$loggers[$name]) ) {
                return self::$loggers[$name];
            }

            // $config = \Yii::$app->settings->get($name);
            // $config = \Yii::$app->params[$name];

            if ( !isset($config['name']) ) {
                $config['name'] = $name;
            }
        }

        if (!$config || !is_array($config)) {
            throw new \InvalidArgumentException('Log config is must be an array and not allow empty.');
        }

        if ( !isset($config['name']) ) {
            $config['name'] = 'default';
        }

        $name = $config['name'];

        if ( !isset(self::$loggers[$name]) ) {
            self::$loggers[$name] = new static($config);
        }

        return self::$loggers[$name];
    }

    /**
     * @param $name
     * @return bool
     */
    public static function has($name)
    {
        return isset(self::$loggers[$name]);
    }

    /**
     * exists logger instance
     * @return bool
     */
    public static function existsLogger()
    {
        return count(self::$loggers) > 0;
    }

    /**
     * @param $name
     * @param bool $make
     * @return static|null
     */
    public static function get($name, $make = true)
    {
        if ( self::has($name) ) {
            return self::$loggers[$name];
        }

        return $make ? self::make($name) : null;
    }

    /**
     * fast get logger instance
     * @param $name
     * @param $args
     * @return SLogger
     */
    public static function __callStatic($name, $args)
    {
        $args['name'] = $name;

        return self::make($args);
    }

    /**
     * save all logger's info to files.
     */
    public static function flushAll()
    {
        foreach (self::$loggers as $logger) {
            $logger->save();
        }
    }


    /**
     * create new instance
     * @param array $config
     * @throws \InvalidArgumentException
     */
    private function __construct(array $config = [])
    {
        $this->name = $config['name'];

        foreach (['basePath', 'subFolder', 'format', 'splitFile'] as $name) {
            if ( isset($config[$name]) ) {
                $this->$name = $config[$name];
            }
        }

        if (isset($config['filenameHandler'])) {
            $this->setFilenameHandler($config['filenameHandler']);
        }
    }

    /**
     * 发生异常直接写入
     * @param \Exception $e
     * @param array $context
     * @param bool $logRequest
     * @return bool
     */
    public function ex(\Exception $e, array $context = [], $logRequest = true)
    {
        return $this->exception($e, $context, $logRequest);
    }
    public function exception(\Exception $e, array $context = [], $logRequest = true)
    {
        $message = $e->getMessage() . PHP_EOL;
        $message .= 'Called At ' . $e->getFile() . ', On Line: ' . $e->getLine() . PHP_EOL;
        $message .= 'Catch the exception by: ' . get_class($e);
        $message .= "\nCode Trace :\n" . $e->getTraceAsString();

        // If log the request info
        if ($logRequest && !PhpHelper::isCli()) {
            $message .= PHP_EOL;

            $context['request'] = [
                'HOST' => $this->getServer('HTTP_HOST'),
                'METHOD' => $this->getServer('request_method'),
                'URI' => $this->getServer('request_uri'),
                'REFERER' => $this->getServer('HTTP_REFERER'),
            ];
        }

        $this->format .= PHP_EOL;
        $this->log('exception', $message, $context);

        return $this->save();
    }

    /**
     * please config like:
     * 'log.trace' => [
     *      'basePath'  => PROJECT_PATH . '/app/runtime/logs',
     *      'subFolder' => 'web'
     *  ],
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function trace($message = '', array $context = [])
    {
        if ( !YII_DEBUG ) {
            return false;
        }

        $msg = '';

        if ( $message ) {
            $msg = "\n  MSG: $message.";
        }

        $file = $method = $line = 'Unknown';

        if ( $data = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3) ) {
            if (isset($data[0]['file'])) {
                $file = $data[0]['file'];
            }

            if (isset($data[0]['line'])) {
                $line = $data[0]['line'];
            }

            if (isset($data[1])) {
                $t = $data[1];

                $method = self::arrayRemove($t, 'class', 'CLASS') . '::' .  self::arrayRemove($t, 'function', 'METHOD');
            }
        }

        $message = "\n  POS: $method, At $file Line [$line]. $msg\n  DATA:";
        $this->log('trace', $message, $context);

        return true;
    }

    public function debug($message, array $context = array())
    {
        parent::debug($message, $context);

        $this->save();
    }

    /**
     * record log info to file
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function log($level, $message, array $context = [])
    {
        $string = $this->dataFormatter($level, $message, $context);

        // $this->write($string);

        $this->_records[$level][] = $string;

        return null;
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->_hasLogged) {
            return true;
        }

        $uri = $this->getServer('REQUEST_URI', 'Unknown');
        $str = "####### REQUEST [$uri] BEGIN ####### \n";

        foreach ($this->_records as $level => $levelRecords) {
            $this->levelName = $level;

            if ( $this->splitFile ) {
                $str = "####### REQUEST [$uri] BEGIN ####### \n";
            }

            foreach ($levelRecords as $text) {
                $str .= $text . "\n";
            }

            $this->write($str, false);
        }

        $this->_records = [];
        $this->_hasLogged = true;

        return true;
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @return string
     */
    protected function dataFormatter($level, $message, array $context)
    {
        $format = $this->format ? : self::SIMPLE_FORMAT;
        $record = [
            '{datetime}' => date('Y-m-d H:i:s'),
            '{message}'  => $message,
            '{level_name}' => $level,
        ];

        $record['{channel}'] = self::arrayRemove($context, 'channel', 'App');
        $record['{context}'] = $context ? json_encode($context) : '';

        return strtr($format, $record);
    }

    /**
     * write log info to file
     * @param string $str
     * @param bool $end
     * @return bool
     */
    protected function write($str, $end = true)
    {
        if ($this->_hasLogged) {
            return true;
        }

        $file = $this->getLogPath() . $this->getFilename();
        $dir  = dirname($file);

        if ( !is_dir($dir) ) {
            mkdir($dir, 0775, true);
        }

        // check file size
        if ( is_file($file) && filesize($file) > $this->maxSize*1000*1000 ) {
            rename($file, substr($file, 0, -3) . time(). '.log');
        }

        if ($end) {
            $this->_hasLogged = true;
        }

        return error_log($str . PHP_EOL, 3, $file);
    }


    /**
     * get log path
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getLogPath()
    {
        if (!$this->basePath) {
            throw new \InvalidArgumentException('The property basePath is required.');
        }

        return $this->basePath . '/' . ( $this->subFolder ? $this->subFolder . '/' : '' );
    }

    /**
     * 设置日志文件名处理
     * @param \Closure $handler
     * @return $this
     */
    public function setFilenameHandler(\Closure $handler)
    {
        $this->filenameHandler = $handler;

        return $this;
    }

    /**
     * 得到日志文件名
     * @return string
     */
    public function getFilename()
    {
        if ( $handler = $this->filenameHandler ) {
            return $handler($this);
        }

        return ( $this->splitFile ? $this->levelName : $this->name ) . '_' . date('Y-m-d') . '.log';
    }

    /**
     * get value and unset it
     * @param $arr
     * @param $key
     * @param null $default
     * @return null
     */
    public static function arrayRemove($arr, $key, $default = null)
    {
        if (isset($arr[$key])) {
            $value = $arr[$key];
            unset($arr[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * get value from $_SERVER
     * @param $name
     * @param string $default
     * @return string
     */
    public function getServer($name, $default = '')
    {
        $name = strtoupper($name);

        return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
    }

    /**
     * @param $message
     * @param array $context
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param string $name
     * @return SLogger
     */
    public function getLogger($name = 'default')
    {
        return self::make($name);
    }

    /**
     * @return array
     */
    public function getLoggerNames()
    {
        return array_keys(self::$loggers);
    }

    public static function sendLog()
    {
        // Yii::$app->gearman->doBackground();
    }
}
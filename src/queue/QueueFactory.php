<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/29
 * Time: 上午10:36
 */

namespace inhere\library\queue;

/**
 * Class QueueFactory
 * @package inhere\library\queue
 */
final class QueueFactory
{
    const DRIVER_DB = 'db';
    const DRIVER_PHP = 'PHP';
    const DRIVER_SYSV = 'sysv';
    const DRIVER_REDIS = 'redis';

    /**
     * driver map
     * @var array
     */
    private static $driverMap = [
        'db' => DbQueue::class,
        'php' => PhpQueue::class,
        'sysv' => SysVQueue::class,
        'redis' => RedisQueue::class,
    ];

    /**
     * @param string $driver
     * @param array $config
     * @return QueueInterface
     */
    public static function make(array $config = [], $driver = '')
    {
        if (!$driver && isset($config['driver'])) {
            $driver = $config['driver'];
            unset($config['driver']);
        }

        if ($driver && ($class = self::getDriverClass($driver))) {
            return new $class($config);
        }

        return new PhpQueue($config);
    }

    /**
     * @param $driver
     * @return mixed|null
     */
    public static function getDriverClass($driver)
    {
        return self::$driverMap[$driver] ?? null;
    }

    /**
     * @return array
     */
    public static function getDriverMap(): array
    {
        return self::$driverMap;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-02-27
 * Time: 18:58
 */

namespace Inhere\Library\Components;

use Inhere\Exceptions\ConnectionException;
use Inhere\Library\Traits\LiteConfigTrait;
use Inhere\Library\Traits\LiteEventTrait;
use Redis;

/**
 * Class RedisClient
 * @package App\Componets
 * 
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 * @method int    del(array|string $keys)
 * @method string dump($key)
 * @method int    exists($key)
 * @method int    expire($key, $seconds)
 * @method int    expireAt($key, $timestamp)
 * @method array  keys($pattern)
 * @method int    move($key, $db)
 * @method mixed  object($subCommand, $key)
 * @method int    persist($key)
 * @method int    pExpire($key, $milliseconds)
 * @method int    pExpireAt($key, $timestamp)
 * @method int    pTtl($key)
 * @method string randomKey()
 * @method mixed  rename($key, $target)
 * @method int    renameNx($key, $target)
 * @method array  scan($cursor, array $options = null)
 * @method array  sort($key, array $options = null)
 * @method int    ttl($key)
 * @method mixed  type($key)
 * @method int    append($key, $value)
 * @method int    bitCount($key, $start = null, $end = null)
 * @method int    bitOp($operation, $retKey, ...$keys)
 * @method array  bitField($key, $subCommand, ...$subCommandArg)
 * @method int    decr($key)
 * @method int    decrBy($key, $decrement)
 * @method string get($key)
 * @method int    getBit($key, $offset)
 * @method string getRange($key, $start, $end)
 * @method string getSet($key, $value)
 * @method int    incr($key)
 * @method int    incrBy($key, $increment)
 * @method string incrByFloat($key, $increment)
 * @method array  mGet(array $keys)
 * @method mixed  mSet(array $dictionary)
 * @method int    mSetNx(array $dictionary)
 * @method mixed  pSetEx($key, $milliseconds, $value)
 * @method mixed  //set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method mixed  set($key, $value, $timeout = 0)
 * @method int    setBit($key, $offset, $value)
 * @method int    setEx($key, $seconds, $value)
 * @method int    setNx($key, $value)
 * @method int    setRange($key, $offset, $value)
 * @method int    strLen($key)
 * @method int    hDel($key, array $fields)
 * @method int    hExists($key, $field)
 * @method string hGet($key, $field)
 * @method array  hGetAll($key)
 * @method int    hIncrBy($key, $field, $increment)
 * @method string hIncrByFloat($key, $field, $increment)
 * @method array  hKeys($key)
 * @method int    hLen($key)
 * @method array  hMGet($key, array $fields)
 * @method mixed  hMSet($key, array $dictionary)
 * @method array  hScan($key, $cursor, array $options = null)
 * @method int    hSet($key, $field, $value)
 * @method int    hSetNx($key, $field, $value)
 * @method array  hVals($key)
 * @method int    hStrLen($key, $field)
 * @method array  bLPop(array $keys, $timeout)
 * @method array  bRPop(array $keys, $timeout)
 * @method array  bRPopLPush($source, $destination, $timeout)
 * @method string lIndex($key, $index)
 * @method int    lInsert($key, $whence, $pivot, $value)
 * @method int    lLen($key)
 * @method string lPop($key)
 * @method int    lPush($key, array $values)
 * @method int    lPushX($key, $value)
 * @method array  lRange($key, $start, $stop)
 * @method int    lRem($key, $count, $value)
 * @method mixed  lSet($key, $index, $value)
 * @method mixed  lTrim($key, $start, $stop)
 * @method string rPop($key)
 * @method string rPopLPush($source, $destination)
 * @method int    rPush($key, array $values)
 * @method int    rPushX($key, $value)
 * @method int    sAdd($key, array $members)
 * @method int    sCard($key)
 * @method array  sDiff(array $keys)
 * @method int    sDiffStore($destination, array $keys)
 * @method array  sInter(array $keys)
 * @method int    sInterStore($destination, array $keys)
 * @method int    sIsMember($key, $member)
 * @method array  sMembers($key)
 * @method int    sMove($source, $destination, $member)
 * @method string sPop($key, $count = null)
 * @method string sRandMember($key, $count = null)
 * @method int    sRem($key, $member)
 * @method array  sScan($key, $cursor, array $options = null)
 * @method array  sUnion(array $keys)
 * @method int    sUnionStore($destination, array $keys)
 * @method int    zAdd($key, array $membersAndScoresDictionary)
 * @method int    zCard($key)
 * @method string zCount($key, $min, $max)
 * @method string zIncrBy($key, $increment, $member)
 * @method int    zInterStore($destination, array $keys, array $options = null)
 * @method array  zRange($key, $start, $end, $withScores = null)
 * @method array  zRangeByScore($key, $start, $end, array $options = array())
 * @method int    zRank($key, $member)
 * @method int    zRem($key, $member)
 * @method int    zRemRangeByRank($key, $start, $stop)
 * @method int    zRemRangeByScore($key, $min, $max)
 * @method array  zRevRange($key, $start, $end, $withScores = null)
 * @method array  zRevRangeByScore($key, $start, $end, array $options = array())
 * @method int    zRevRank($key, $member)
 * @method int    zUnionStore($destination, array $keys, array $options = null)
 * @method string zScore($key, $member)
 * @method array  zScan($key, $cursor, array $options = null)
 * @method array  zRangeByLex($key, $start, $stop, array $options = null)
 * @method array  zRevRangeByLex($key, $start, $stop, array $options = null)
 * @method int    zRemRangeByLex($key, $min, $max)
 * @method int    zLexCount($key, $min, $max)
 * @method int    pfAdd($key, array $elements)
 * @method mixed  pfMerge($destinationKey, array $sourceKeys)
 * @method int    pfCount(array $keys)
 * @method mixed  pubSub($subCommand, $argument)
 * @method int    publish($channel, $message)
 * @method mixed  discard()
 * @method array  exec()
 * @method mixed  multi()
 * @method mixed  unwatch()
 * @method mixed  watch($key)
 * @method mixed  eval($script, $numKeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed  evalSha($script, $numKeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed  script($subCommand, $argument = null)
 * @method mixed  auth($password)
 * @method string echo ($message)
 * @method mixed  ping($message = null)
 * @method mixed  select($database)
 * @method mixed  bgRewriteAOF()
 * @method mixed  bgSave()
 * @method mixed  client($subCommand, $argument = null)
 * @method mixed  config($subCommand, $argument = null)
 * @method int    dbSize()
 * @method mixed  flushAll()
 * @method mixed  flushDb()
 * @method array  info($section = null)
 * @method int    lastSave()
 * @method mixed  save()
 * @method mixed  slaveOf($host, $port)
 * @method mixed  slowLog($subCommand, $argument = null)
 * @method array  time()
 * @method array  command()
 * @method int    geoAdd($key, $longitude, $latitude, $member)
 * @method array  geoHash($key, array $members)
 * @method array  geoPos($key, array $members)
 * @method string geoDist($key, $member1, $member2, $unit = null)
 * @method array  geoRadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method array  geoRadiusByMember($key, $member, $radius, $unit, array $options = null)
 */
class RedisClient
{
    use LiteEventTrait, LiteConfigTrait;
    
    // ARGS: ($name, $mode, $config)
    const CONNECT = 'connect';
    // ARGS: ($name, $mode)
    const DISCONNECT = 'disconnect';
    // ARGS: ($method, array $args)
    const BEFORE_EXECUTE = 'beforeExecute';
    // ARGS: ($method, array $data)
    const AFTER_EXECUTE = 'afterExecute';

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var array
     */
    private $config = [
        'host' => '127.0.0.1',
        'port' => '6379',
        'timeout' => 0.0,
        'database' => '0',

        'password' => null,

        'options' => [],
    ];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    public function connect()
    {
        if (!$this->redis) {
            return $this;
        }

        try {
            $config = $this->config;
            $client = new \Redis();
            $client->connect($config['host'], (int) $config['port'], $config['timeout']);

            if ($config['password'] && !$client->auth($config['password'])) {
                throw new \RuntiemException("Auth failed on connect to the redis.");
            }

            if ($config['database']) {
                $this->redis->select((int) $config['database']);
            }

            $options = $config['options'] ?? [];

            foreach ($options as $name => $value) {
                $client->setOption($name, $value);
            }

            $this->redis = $client;
        } catch (\Throwable $e) {
            throw new ConnectionException("Connect error: {$e->getMessage()}");
        }

        return $this;
    }

    public function reconnect()
    {
        $this->redis = null;
        $this->connect();
    }

    /**
     * disconnect
     * @return bool
     */
    public function disconnect()
    {
        $this->redis = null;
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $this->connect();
        $upperMethod = strtoupper($method);

        // exists 
        if (\method_exists($this->redis, $upperMethod)) {
            // trigger before event (read)
            $this->fire(self::BEFORE_EXECUTE, [$upperMethod, $args]);

            $ret = $this->redis->$upperMethod(...$args);

            // trigger after event (read)
            $this->fire(self::AFTER_EXECUTE, [$upperMethod, ['args' => $args, 'ret' => $ret]]);

            return $ret;
        }

        throw new UnknownMethodException("Call the redis method [$method] don't exists!");
    }
    
    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**************************************************************************
     * basic method
     *************************************************************************/

    /**
     * redis 中 key 是否存在
     * @param string $key
     * @return bool
     */
    public function existsKey($key)
    {
        return (bool)$this->exists($key);
    }

    /**
     * 集合是否存在
     * @param $key
     * @return bool
     */
    public function existsSet($key)
    {
        return $this->sCard($key) > 0;
    }

    /**
     * 集合是否存在元素 $member
     * @param $key
     * @param $member
     * @return bool
     */
    public function existsInSet($key, $member)
    {
        return (bool)$this->sIsMember($key, $member);
    }

    /**
     * 有序集合是否存在
     * @param $key
     * @return bool
     */
    public function existsZSet($key)
    {
        return $this->zCard($key) > 0;
    }

    /**
     * 有序集合是否存在元素
     * @param $key
     * @param $member
     * @return bool
     */
    public function existsInZSet($key, $member)
    {
        return null !== $this->zScore($key, $member);
    }

    /**
     * @param $key
     * @return bool
     */
    public function existsList($key)
    {
        return $this->existsKey($key) && $this->lLen($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function existsHTable($key)
    {
        return $this->hLen($key) > 0;
    }

    /**
     * check hash table is empty or is not exists.
     * @param $key
     * @return bool
     */
    public function isEmptyHTable($key)
    {
        return 0 === $this->hLen($key);
    }

    /**
     * @param $key
     * @param $field
     * @return bool
     */
    public function existsInHTable($key, $field)
    {
        return 1 === $this->hExists($key, $field);
    }

    /**************************************************************************
     * cache
     *************************************************************************/

    /**
     * 添加缓存 - key 不存在时才会添加
     * @param $key
     * @param string|array $value
     * @param int $seconds
     * @return mixed
     */
    public function addCache($key, $value, $seconds = 3600)
    {
        // return $this->set($key, serialize($value), 'EX', $seconds, 'NX');
        return $this->exists($key) ? true : $this->setCache($key, $value, $seconds);
    }

    /**
     * 设置缓存 - key 存在会直接覆盖原来的值，不存在即是添加
     * @param $key
     * @param $seconds
     * @param string|array $value 要存储的数据 可以是字符串或者数组
     * @return mixed
     */
    public function setCache($key, $value, $seconds = 3600)
    {
        // return $this->set($key, serialize($value), 'EX', $seconds);
        return $this->setEx($key, $seconds, serialize($value));
    }

    /**
     * @param $key
     * @param null $default
     * @return string
     */
    public function getCache($key, $default = null)
    {
        return ($data = $this->get($key)) ? unserialize($data) : $default;
    }

    /**
     * @param $key
     * @return int 成功删除的条数
     */
    public function delCache($key)
    {
        $data = $this->get($key);
        $this->del($key);
        return $data;
    }

    /**************************************************************************
     * getter/setter
     *************************************************************************/
    
    /**
     * get Connection
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @return array
     */
    public function getStats()
    {
        // used_memory
        return $this->info();
    }
}

<?php
/**
 * @author Inhere
 * @version v1.0
 * Use : this
 * Date : 2015-1-10
 * 提供依赖注入服务的容器，
 * 注册、管理容器的服务。
 * 共享服务初次激活服务后将会被保存，即后期获取时若不特别声明，都是获取已激活的服务实例
 * File: Container.php
 */

namespace Inhere\Library\DI;

use Inhere\Exceptions\NotFoundException;
use Inhere\Library\Helpers\Obj;
use Inhere\Library\Traits\NameAliasTrait;
use Psr\Container\ContainerInterface;

/**
 * Class Container
 * @package Inhere\Library\DI
 */
class Container implements ContainerInterface, \ArrayAccess, \IteratorAggregate, \Countable
{
    use NameAliasTrait;

    /**
     * 当前容器名称，初始时即固定
     * @var string
     */
    public $name;

    /**
     * 当前容器的父级容器
     * @var Container
     */
    protected $parent;

    /**
     * 服务别名
     * @var array
     * [
     *  'alias name' => 'id',
     *  'alias name2' => 'id'
     * ]
     */
    protected $aliases = [];

    /**
     * $services 已注册的服务
     * $services = [
     *       'id' => Service Object
     *       ... ...
     *   ];
     * @var Service[]
     */
    private $services = [];

    /**
     * Container constructor.
     * @param array $services
     * @param Container|null $parent
     * @throws \InvalidArgumentException
     * @throws \Inhere\Exceptions\DependencyResolutionException
     */
    public function __construct(array $services = [], Container $parent = null)
    {
        $this->parent = $parent;

        $this->sets($services);
    }

    public function __destruct()
    {
        $this->clear();
    }

    /*******************************************************************************
     * Service Add
     ******************************************************************************/

    /**
     * 在容器注册服务
     * @param string $id 服务组件注册id
     * @param mixed (string|array|object|callback) $definition 服务实例对象 | 服务信息定义
     * string:
     *  $definition = className
     * array:
     *  $definition = [
     *     // 1. 仅类名 $definition['_args']则传入对应构造方法
     *     'target' => 'className',
     *     // 2. 类的静态方法, $definition['_args']则传入对应方法 className::staticMethod(_args...)
     *     'target' => 'className::staticMethod',
     *     // 3. 类的动态方法, $definition['_args']则传入对应方法 (new className)->method(_args...)
     *     'target' => 'className->method',
     *
     *     '_options' => [...] 一些服务设置(别名,是否共享)
     *
     *     // 设置参数方式
     *     '_args' => [
     *         arg1,arg2,arg3,...
     *     ]
     *
     *     // 设置属性方式1
     *     '_props' => [
     *         arg1,arg2,arg3,...
     *     ]
     *     // 设置属性方式2， // prop1 prop2 prop3 将会被收集 到 _props[], 组成 方式1 的形式
     *     prop1 => arg1,
     *     prop2 => arg2,
     *     ... ...
     *  ]
     * object:
     *  $definition = new xxClass();
     * closure:
     *  $definition = function($di){ return xxx;};
     * @param array $opts
     * [
     *  'shared' => (bool), 是否共享
     *  'locked' => (bool), 是否锁定服务
     *  'aliases' => (array), 别名
     *  'active' => (bool), 立即激活
     * ]
     * @return $this
     * @internal param bool $shared 是否共享
     * @internal param bool $locked 是否锁定服务
     * @throws \Inhere\Exceptions\DependencyResolutionException
     * @throws \InvalidArgumentException
     */
    public function set(string $id, $definition, array $opts = [])
    {
        $id = $this->_checkServiceId($id);

        // 已锁定的服务，不能更改
        if ($this->isLocked($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot override frozen service "%s".', $id));
        }

        $args = $props = [];
        $opts = array_merge([
            'aliases' => null,
            'shared' => true,
            'locked' => false,
            'active' => false,
        ], $opts);

        // 已经是个服务实例 object 不是闭包 closure
        if (\is_object($definition)) {
            $this->services[$id] = new Service($definition, $args, $opts['shared'], $opts['locked']);

            return $this;
        }

        // a string; is target
        if (\is_string($definition) || \is_callable($definition)) {
            $callback = $this->createCallback($definition);

            // a Array 详细设置服务信息
        } elseif (\is_array($definition)) {
            if (empty($definition['target'])) {
                throw new \InvalidArgumentException("Configuration errors, the 'target' is must be defined! ID: $id");
            }

            $target = $definition['target'];

            // 在配置中 设置一些信息
            if (isset($definition['_options'])) {
                $opts = array_merge($opts, $definition['_options']);
                $this->alias($opts['aliases'], $id);
                unset($definition['_options']);
            }

            // 收集方法参数
            if (isset($definition['_args'])) {
                $args = $definition['_args'];
                unset($definition['_args']);
            }

            // 收集对象属性
            if (isset($definition['_props'])) {
                $props = $definition['_props'];
            } else {
                unset($definition['target']);
                $props = $definition;
            }

            $callback = $this->createCallback($target, $args, $props);
        } else {
            throw new \InvalidArgumentException('Invalid parameter!');
        }

        $this->services[$id] = new Service($callback, $args, $opts['shared'], $opts['locked']);

        // active service
        if ($opts['active']) {
            $this->get($id);
        }

        return $this;
    }

    /**
     * 通过设置配置的多维数组 注册多个服务. 服务详细设置请看{@see self::set()}
     * @param array $services
     * @example
     * $services = [
     *      'service1 id'  => 'xx\yy\className',
     *      'service2 id'  => ... ,
     *      'service3 id'  => ...
     * ]
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \Inhere\Exceptions\DependencyResolutionException
     */
    public function sets(array $services)
    {
        $IServiceProvider = ServiceProviderInterface::class;

        foreach ($services as $id => $definition) {
            if (!$id || !$definition) {
                continue;
            }

            // string. is a Service Provider class name
            if (\is_string($definition) && is_subclass_of($definition, $IServiceProvider)) {
                $this->registerServiceProvider(new $definition);

                continue;
            }

            // set service
            $this->set($id, $definition);
        }

        return $this;
    }

    /**
     * 注册受保护的服务 like class::lock()
     * @param  string $id [description]
     * @param $definition
     * @param $share
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \Inhere\Exceptions\DependencyResolutionException
     */
    public function protect(string $id, $definition, $share = false)
    {
        return $this->lock($id, $definition, $share);
    }

    /**
     * (注册)锁定的服务，也可在注册后锁定,防止 getNew() 强制重载
     * @param  string $id description
     * @param $definition
     * @param $share
     * @return $this
     * @throws \Inhere\Exceptions\DependencyResolutionException
     * @throws \InvalidArgumentException
     */
    public function lock(string $id, $definition, $share = false)
    {
        return $this->set($id, $definition, [
            'shared' => $share,
            'locked' => true,
        ]);
    }

    /**
     * 注册服务提供者(可能含有多个服务)
     * @param  ServiceProviderInterface $provider 在提供者内添加需要的服务到容器
     * @return $this
     */
    public function registerServiceProvider(ServiceProviderInterface $provider)
    {
        $provider->register($this);

        return $this;
    }

    /**
     * @param array $providers
     * @return $this
     */
    public function registerServiceProviders(array $providers)
    {
        /** @var ServiceProviderInterface $provider */
        foreach ($providers as $provider) {
            $provider->register($this);
        }

        return $this;
    }

    /**
     * 创建(类实例/类的方法)回调
     * @param mixed $target
     * @param array $arguments
     * @param array $props
     * @return callable
     * @throws \InvalidArgumentException
     * @throws \Inhere\Exceptions\DependencyResolutionException
     */
    public function createCallback($target, array $arguments = [], array $props = []): callable
    {
        // a Closure OR a callable Object
        if (\is_object($target) && method_exists($target, '__invoke')) {
            return $target;
        }

        $arguments = array_values($arguments);
        /** @see $this->set() $definition is array */
        $target = trim($target);

        if (($pos = strpos($target, '::')) !== false) {
            $callback = function (self $self) use ($target, $arguments) {
                if ($arguments) {
                    return $target(...$arguments);
                }

                return $target($self);
            };
        } elseif (($pos = strpos($target, '->')) !== false) {
            $class = substr($target, 0, $pos);
            $method = substr($target, $pos + 2);

            $callback = function (self $self) use ($class, $method, $arguments, $props) {
                $object = new $class;

                Obj::init($object, $props);

                if ($arguments) {
                    return $object->$method(...$arguments);
                }

                return $object->$method($self);
            };
        } else {
            // 仅是个 class name
            $class = $target;

            try {
                $reflection = new \ReflectionClass($class);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage());
            }

            /** @var \ReflectionMethod */
            $reflectionMethod = $reflection->getConstructor();

            // If there are no parameters, just return a new object.
            if (null === $reflectionMethod) {
                $callback = function () use ($class, $props) {
                    return Obj::init(new $class, $props);
                };
            } else {
                $arguments = $arguments ?: Obj::getMethodArgs($reflectionMethod);

                // Create a callable
                $callback = function () use ($reflection, $arguments, $props) {
                    $object = $reflection->newInstanceArgs($arguments);

                    return Obj::init($object, $props);
                };
            }

            unset($reflection, $reflectionMethod);
        }

        return $callback;
    }

    /*******************************************************************************
     * Service(Instance) Get
     ******************************************************************************/

    /**
     * get 获取已注册的服务组件实例
     * - 共享服务总是获取已存储的实例
     * - 其他的则总是返回新的实例
     * @param  string $id 要获取的服务组件id
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            // a class name.
            if (strpos($id, '\\') && class_exists($id)) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                $this->set($id, $id);
            } else {
                throw new \InvalidArgumentException("The service '$id' was not found, has not been registered!");
            }
        }

        return $this->getInstance($id);
    }

    /**
     * 强制获取服务的新实例，针对共享服务
     * @param $id
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getNew(string $id)
    {
        return $this->new($id);
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function new(string $id)
    {
        return $this->getInstance($id, true, true);
    }

    /**
     * 若存在服务则返回 否则返回 null
     * @param string $id
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function getIfExist($id)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return $this->getInstance($id, false);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function raw(string $id)
    {
        $id = $this->resolveAlias($id);

        if ($service = $this->getService($id, true)) {
            return $service->getCallback();
        }

        throw new \InvalidArgumentException("get service define error for ID: $id");
    }

    /**
     * 删除服务
     * @param $id
     */
    public function del(string $id)
    {
        $id = $this->resolveAlias($id);

        if (isset($this->services[$id])) {
            unset($this->services[$id]);
        }
    }

    /**
     * @param string|array $definition
     * @return mixed
     */
    public function make($definition)
    {
        return Obj::smartCreate($definition);
    }

    /*******************************************************************************
     * Helper
     ******************************************************************************/

    /**
     * get 获取已注册的服务组件实例
     * @param $id
     * @param bool $thrErr
     * @param bool $forceNew 强制获取服务的新实例
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function getInstance(string $id, $thrErr = true, $forceNew = false)
    {
        if (!$id) {
            throw new \InvalidArgumentException(sprintf(
                'The first parameter must be a non-empty string, %s given',
                \gettype($id)
            ));
        }

        $id = $this->resolveAlias($id);

        if ($service = $this->getService($id, $thrErr)) {
            return $service->get($this, $forceNew);
        }

        return null;
    }

    /**
     * 获取某一个服务的信息
     * @param $id
     * @param bool $thrErr
     * @return Service|null
     * @throws \InvalidArgumentException
     */
    public function getService(string $id, $thrErr = false)
    {
        $id = $this->resolveAlias($id);

        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if ($thrErr) {
            throw new \InvalidArgumentException("The service '$id' was not found, has not been registered!");
        }

        return null;
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function resolveAlias(string $alias)
    {
        // is a real id
        if (isset($this->services[$alias])) {
            return $alias;
        }

        return $this->aliases[$alias] ?? $alias;
    }

    /**
     * clear
     */
    public function clear()
    {
        $this->parent = null;
        $this->services = $this->aliases = [];
    }

    /*******************************************************************************
     * Getter/Setter
     ******************************************************************************/

    /**
     * 获取全部服务信息
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Method to set property parent
     * @param   Container $parent Parent container.
     * @return  static  Return self to support chaining.
     */
    public function setParent(Container $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * 获取全部服务id
     * @param bool $toArray
     * @return array
     */
    public function getIds($toArray = true): array
    {
        $ids = array_keys($this->services);

        return $toArray ? $ids : implode(', ', $ids);
    }

    /**
     * @param $id
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isShared(string $id): bool
    {
        if ($service = $this->getService($id)) {
            return $service->isShared();
        }

        return false;
    }

    /**
     * @param $id
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isLocked(string $id): bool
    {
        if ($service = $this->getService($id)) {
            return $service->isLocked();
        }

        return false;
    }

    /**
     * 是已注册的服务
     * @param string $id
     * @return bool|Service
     */
    public function has($id)
    {
        return $this->exists($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        $id = $this->resolveAlias($id);

        return isset($this->services[$id]);
    }

    /**
     * @param $id
     * @return string
     * @throws \InvalidArgumentException
     */
    private function _checkServiceId(string $id): string
    {
        if (!\is_string($id)) {
            throw new \InvalidArgumentException('Set up the service Id can be a string!');
        }

        if (!$id = trim(str_replace(' ', '', $id), '.')) {
            throw new \InvalidArgumentException('You must set up the service Id name!');
        }

        // if (!preg_match('/^\w[\w-.]{1,56}$/i', $id)) {
        //     throw new \InvalidArgumentException("The service Id[$id] is invalid string！");
        // }

        return $id;
    }

    /**
     * @param $name
     * @return bool|Service
     */
    public function __isset($name)
    {
        return $this->exists($name);
    }

    /**
     * @param $name
     * @param $value
     * @throws \InvalidArgumentException
     * @throws \Inhere\Exceptions\DependencyResolutionException
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param $name
     * @return bool
     * @throws \InvalidArgumentException
     * @throws NotFoundException
     */
    public function __get($name)
    {
        if ($service = $this->getService($name)) {
            return $service->get($this);
        }

        $method = 'get' . ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new NotFoundException('Getting a Unknown property! ' . \get_class($this) . "::{$name}");
    }

    /*******************************************************************************
     * Interfaces implement
     ******************************************************************************/

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->services);
    }

    /**
     * Defined by IteratorAggregate interface
     * Returns an iterator for this object, for use with foreach
     * @return \ArrayIterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->services);
    }

    /**
     * Checks whether an offset exists in the iterator.
     * @param   mixed $offset The array offset.
     * @return  boolean  True if the offset exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return $this->exists($offset);
    }

    /**
     * Gets an offset in the iterator.
     * @param   mixed $offset The array offset.
     * @return  mixed  The array value if it exists, null otherwise.
     * @throws \InvalidArgumentException
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets an offset in the iterator.
     * @param   mixed $offset The array offset.
     * @param   mixed $value The array value.
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \Inhere\Exceptions\DependencyResolutionException
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * Unset an offset in the iterator.
     * @param   mixed $offset The array offset.
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->del($offset);
    }

}

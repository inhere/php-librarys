<?php
/**
 * @author Inhere
 * @version v1.0
 * Use : InterfaceServiceProvider.php
 * Date : 2014-7-10
 */

namespace inhere\library\di;

interface InterfaceServiceProvider
{
    /**
     * 注册一项服务(可能含有多个服务)提供者到容器中
     * @param Container $container
     * @return mixed
     */
    public function register(Container $container);
}

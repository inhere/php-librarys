<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:45
 */

namespace inhere\library\queue;

/**
 * Class PhpQueue
 * @package inhere\library\queue
 */
class PhpQueue extends BaseQueue
{
    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * {@inheritDoc}
     */
    protected function doPush($data, $priority = self::PRIORITY_NORM)
    {
        $this->queue->push($data);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doPop()
    {
        return $this->queue->pop();
    }
}
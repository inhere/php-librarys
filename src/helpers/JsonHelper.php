<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/8/10 0010
 * Time: 00:41
 */

namespace inhere\librarys\helpers;
use inhere\librarys\exceptions\NotFoundException;

/**
 * Class JsonHelper
 * @package inhere\librarys\helpers
 */
class JsonHelper
{
    /**
     * @param $file
     * @param bool|true $toArray
     * @return mixed|null|string
     */
    public static function loadFile($file, $toArray=true)
    {
        if (!file_exists($file)) {
            throw new NotFoundException("û���ҵ��򲻴�����Դ�ļ�{$file}");
        }

        $data = file_get_contents($file);

        if ( !$data ) {
            return null;
        }

        $data = preg_replace(array(

            // ȥ�����ж���ע��/* .... */
            '/\/\*.*?\*\/\s*/is',

            // ȥ�����е���ע��//....
            '/\/\/.*?[\r\n]/is',

            // ȥ���հ�
            '/(?!\w)\s*?(?!\w)/is'

        ),  array('','',' '), $data);

        if ($toArray) {
            return json_decode($data, true);
        }

        return $data;
    }

    /**
     * @param string $input �ļ� �� ����
     * @param bool $output �Ƿ�������ļ��� Ĭ�Ϸ��ظ�ʽ��������
     * @param array $options �� $output=true,��ѡ����Ч
     * $options = [
     *      'type'      => 'min' // ����������� min ѹ������ raw ������
     *      'file'      => 'xx.json' // ����ļ�·��;�����ļ��������ȡ����·��
     * ]
     * @return string | bool
     */
    public static function json($input, $output=false, array $options=[])
    {
        if (!is_string($input)) {
            return false;
        }

        $data = trim($input);

        if ( file_exists($input) ) {
            $data = file_get_contents($input);
        }

        if ( !$data ) {
            return false;
        }

        $data = preg_replace(array(

            // ȥ�����ж���ע��/* .... */
            '/\/\*.*?\*\/\s*/is',

            // ȥ�����е���ע��//....
            '/\/\/.*?[\r\n]/is',

            // ȥ���հ���
            "/(\n[\r])+/is"

        ),  array('','',"\n"), $data);

        if (!$output) {
            return $data;
        }

        $default = [ 'type' => 'min' ];
        $options = array_merge($default, $options);

        if ( file_exists($input) && (empty($options['file']) || !is_file($options['file']) ) )
        {
            $dir   = dirname($input);
            $name  = basename($input, '.json');
            $file  = $dir . '/' . $name . '.' . $options['type'].'.json';
            $options['file'] = $file;
        }

        static::saveAs($data, $options['file'], $options['type']);

        return $data;
    }

    /**
     * @param $data
     * @param $output
     * @param array $options
     */
    public static function saveAs($data, $output, array $options = [])
    {
        $default = [ 'type' => 'min',  'file' => '' ];
        $options = array_merge($default, $options);

        $dir   = dirname($output);

        if ( !file_exists($dir) ) {
            trigger_error('���õ�json�ļ����'.$dir.'Ŀ¼�����ڣ�');
        }

        $name  = basename($output, '.json');
        $file  = $dir . '/' . $name . '.' . $options['type'].'.json';

        if ( $options['type '] === 'min' ) {
            // ȥ���հ�
            $data = preg_replace('/(?!\w)\s*?(?!\w)/i', '',$data);
        }

        @file_put_contents($file, $data);

    }
}

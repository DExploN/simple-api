<?php
declare(strict_types=1);

function custom_json_encode(array $data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}

function web_path($type, $fileName)
{
    global $config;
    return $config['host_name'] .$config[$type] . '/' . $fileName;
}

function full_path($type, $fileName)
{
    global $config;
    return ROOT_DIR . DIRECTORY_SEPARATOR .$config[$type] . DIRECTORY_SEPARATOR . $fileName;
}
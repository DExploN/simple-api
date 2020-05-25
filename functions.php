<?php
declare(strict_types=1);

function custom_json_encode(array $data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}
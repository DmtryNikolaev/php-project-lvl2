<?php

namespace Differ\Parsers;

use Symfony\Component\Yaml\Yaml;

function getDataFromFile(string $filepath)
{
    $fileExtension = substr($filepath, -4);

    switch ($fileExtension) {
        case 'json':
            return json_decode(file_get_contents($filepath), true);
        case 'yaml':
            return Yaml::parseFile($filepath);
        default:
            throw new \Exception('Ошибка в передаче файла');
    }

    return '{}';
}
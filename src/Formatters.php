<?php

namespace Differ\Formatters;

use function Differ\Parsers\getDataFromFile;
use function Differ\Differ\clearedData;

function addOperatorToKeys(array $data): array
{
    $result = collect($data)->reduce(function ($result, $value, $key): array {
        $result['* ' . $key] = is_array($value) ? addOperatorToKeys($value) : $value;

        return $result;
    }, []);

    return $result;
}

function formattedJson(array $data1, array $data2): array
{
    $mergedFiles = collect(array_merge($data1, $data2))->sortKeys();

    $result = $mergedFiles->reduce(function ($carry, $value, $key) use ($data1, $data2): object {
        $isKeyContainsTwoFiles = array_key_exists($key, $data1) && array_key_exists($key, $data2);
        $isKeyContainsOnlyFirstFile = array_key_exists($key, $data1) && !array_key_exists($key, $data2);
        $isKeyContainsOnlySecondFile = !array_key_exists($key, $data1) && array_key_exists($key, $data2);

        if ($isKeyContainsTwoFiles) {
            $valueFirstFile = $data1[$key];
            $valueSecondFile = $data2[$key];

            if (is_array($valueFirstFile) && is_array($valueSecondFile)) {
                $carry->put($key, formattedJson($valueFirstFile, $valueSecondFile));
            } elseif ($valueFirstFile === $valueSecondFile) {
                $carry->put($key, $value);
            } elseif ($valueFirstFile !== $valueSecondFile) {
                $carry->put($key, $valueFirstFile);
                $carry->put($key, $value);
            }
        } elseif ($isKeyContainsOnlySecondFile) {
            $carry->put($key, $value);
        } elseif ($isKeyContainsOnlyFirstFile) {
            $carry->put($key, $value);
        }

        return $carry;
    }, collect([]))->all();

    return $result;
}

function formattedDefault(array $data1, array $data2): array
{
    $mergedFiles = collect(array_merge($data1, $data2))->sortKeys();

    $result = $mergedFiles->reduce(function ($carry, $value, $key) use ($data1, $data2): object {
        $isKeyContainsTwoFiles = array_key_exists($key, $data1) && array_key_exists($key, $data2);
        $isKeyContainsOnlyFirstFile = array_key_exists($key, $data1) && !array_key_exists($key, $data2);
        $isKeyContainsOnlySecondFile = !array_key_exists($key, $data1) && array_key_exists($key, $data2);

        $emptySecondFileValue = str_replace("* ", "- ", $key);
        $emptyFirstFileValue = str_replace("* ", "+ ", $key);

        if ($isKeyContainsTwoFiles) {
            $valueFirstFile = $data1[$key];
            $valueSecondFile = $data2[$key];

            if (is_array($valueFirstFile) && is_array($valueSecondFile)) {
                $carry->put($key, formattedDefault($valueFirstFile, $valueSecondFile));
            } elseif ($valueFirstFile === $valueSecondFile) {
                $carry->put($key, $value);
            } elseif ($valueFirstFile !== $valueSecondFile) {
                $carry->put($emptySecondFileValue, $valueFirstFile);
                $carry->put($emptyFirstFileValue, $value);
            }
        } elseif ($isKeyContainsOnlySecondFile) {
            $carry->put($emptyFirstFileValue, $value);
        } elseif ($isKeyContainsOnlyFirstFile) {
            $carry->put($emptySecondFileValue, $value);
        }

        return $carry;
    }, collect([]))->all();

    return $result;
}

function formattedPlain(array $data1, array $data2, string $path = ""): array
{
    $mergedFiles = collect(array_merge($data1, $data2))->sortKeys();

    $result = $mergedFiles->reduce(function ($result, $value, $key) use ($path, $data1, $data2): object {
        $currPath = $path . $key;

        $isKeyContainsTwoFiles = array_key_exists($key, $data1) && array_key_exists($key, $data2);
        $isKeyContainsOnlyFirstFile = array_key_exists($key, $data1) && !array_key_exists($key, $data2);
        $isKeyContainsOnlySecondFile = !array_key_exists($key, $data1) && array_key_exists($key, $data2);

        if ($isKeyContainsTwoFiles) {
            $valueFirstFile = $data1[$key];
            $valueSecondFile = $data2[$key];

            if (is_array($valueFirstFile) && is_array($valueSecondFile)) {
                $result->push(formattedPlain($valueFirstFile, $valueSecondFile, $currPath . "."));
            } elseif ($valueFirstFile !== $valueSecondFile) {
                $valueFirstFile =  is_array($valueFirstFile)  ? '[complex value]' : var_export($data1[$key], true);
                $valueSecondFile = is_array($valueSecondFile) ? '[complex value]' : var_export($data2[$key], true);

                $result->push("Property '{$currPath}' was updated. From {$valueFirstFile} to {$valueSecondFile}");
            }
        } elseif ($isKeyContainsOnlyFirstFile) {
            $result->push("Property '{$currPath}' was removed");
        } elseif ($isKeyContainsOnlySecondFile) {
            $value = is_array($value) ? '[complex value]' : var_export($value, true);
            $result->push("Property '{$currPath}' was added with value: {$value}");
        }

        return $result;
    }, collect([]))->all();

    return $result;
}

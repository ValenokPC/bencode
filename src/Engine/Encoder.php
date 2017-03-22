<?php

namespace SandFoxMe\Bencode\Engine;

use SandFoxMe\Bencode\Types\ListType;

/**
 * Class Encoder
 * @package SandFoxMe\Bencode\Engine
 * @author Anton Smirnov
 * @license MIT
 */
class Encoder
{
    private $data;

    public function __construct($data, array $options = [])
    {
        $this->data = $data;
    }

    public function encode(): string
    {
        return $this->encodeValue($this->data);
    }

    private function encodeValue($value): string
    {
        // first check if we have integer
        if (is_int($value)) {
            return $this->encodeInteger($value);
        }

        // lists and dictionaries

        // array first
        if (is_array($value)) {
            if ($this->isSequentialArray($value)) {
                return $this->encodeList($value);
            } else {
                return $this->encodeDictionary($value);
            }
        }

        // traversables next
        if ($value instanceof ListType) {
            // ListType forces traversable object to be list
            return $this->encodeList($value);
        }

        // all other traversables are dictionaries
        if ($value instanceof \Traversable) {
            return $this->encodeDictionary($value);
        }

        // also treat stdClass as a dictionary
        if ($value instanceof \stdClass) {
            return $this->encodeDictionary((array)$value);
        }

        // everything else is a string
        return $this->encodeString($value);
    }

    private function encodeInteger(int $integer): string
    {
        return "i{$integer}e";
    }

    private function encodeString(string $string): string
    {
        return implode([strlen($string), ':', $string]);
    }

    private function encodeList($array): string
    {
        $listData = [];

        foreach ($array as $value) {
            $listData []= $this->encodeValue($value);
        }

        $list = implode($listData);

        return "l{$list}e";
    }

    private function encodeDictionary($array): string
    {
        $dictData = [];

        foreach ($array as $key => $value) {
            // do not use php array keys here to prevent numeric strings becoming integers again
            $dictData[] = [strval($key), $value];
        }

        // sort by keys - rfc requirement
        usort($dictData, function ($a, $b) {
            return strcmp($a[0], $b[0]);
        });

        $dict = implode(array_map(function ($row) {
            list($key, $value) = $row;
            return $this->encodeString($key) . $this->encodeValue($value); // key is always a string
        }, $dictData));

        return "d{$dict}e";
    }

    private function isSequentialArray(array $array): bool
    {
        $index = 0;

        foreach ($array as $key => $value) {
            if ($key !== $index) {
                return false;
            }

            $index += 1;
        }

        return true;
    }
}
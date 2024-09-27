<?php
namespace Teambank\RatenkaufByEasyCreditApiV3\Integration;

interface StorageInterface {

    public function set($key, $value);
    public function get($key);
    public function clear();

}
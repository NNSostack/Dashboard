<?php

class Metric {
    public ?string $name = null;
    public array $data = [];
    public array $parentData = [];
    public array $config;

    function __construct($config) {
        $this->config = $config;
        $this->name = $config["name"];
    }
}
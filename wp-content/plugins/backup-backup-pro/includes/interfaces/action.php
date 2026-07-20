<?php

namespace BMI\Plugin\Interfaces;

if (!defined('ABSPATH')) exit;

interface Action {
    public function execute();
}
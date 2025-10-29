<?php

function getStorage($key) {
    // Use /tmp/data on Render, local data folder otherwise
    $baseDir = getenv('RENDER') ? '/tmp/data' : __DIR__ . '/../../data';
    
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }
    
    $filename = $baseDir . '/' . $key . '.json';
    
    if (!file_exists($filename)) {
        return null;
    }
    
    $data = file_get_contents($filename);
    return json_decode($data, true);
}

function setStorage($key, $value) {
    $baseDir = getenv('RENDER') ? '/tmp/data' : __DIR__ . '/../../data';
    
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }
    
    $filename = $baseDir . '/' . $key . '.json';
    file_put_contents($filename, json_encode($value, JSON_PRETTY_PRINT));
}

function removeStorage($key) {
    $baseDir = getenv('RENDER') ? '/tmp/data' : __DIR__ . '/../../data';
    $filename = $baseDir . '/' . $key . '.json';
    
    if (file_exists($filename)) {
        unlink($filename);
    }
}
<?php

require 'config.php';

const BASE_URL = 'https://api.github.com';

function github_api_get($path) {
    $url = sprintf('%s%s?client_id=%s&client_secret=%s', BASE_URL, $path, CLIENT_ID, CLIENT_SECRET);
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: hello'
    ));
    
    $json = curl_exec($ch);
    
    $api_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($api_status !== 200) {
        return null;
    }
    
    $data = json_decode($json);
    
    return $data;
}

function github_user_gists($username) {
    $data = github_api_get("/users/$username/gists");
    
    return $data;
}

function github_gist($id) {
    $data = github_api_get("/gists/$id");
    
    return $data;
}

// ---

function view_header() {
?>
<style>
    body {
        font-family: monospace;
    }
</style>
<?php
}

function view_link($url, $text) {
    echo '<a href="', $url, '">', $text, '</a>';
}

function view_break() {
    echo '<br />';
}

// ---

function view_user_gists($username) {
    $gists = github_user_gists($username);
    
    if (is_null($gists)) {
        http_response_code(400);
        exit;
    }
    
    view_header();
    
    foreach ($gists as $gist) {
        view_link("$gist->id/", $gist->id);
        
        foreach ((array)$gist->files as $filename => $file) {
            view_break();
            view_link("$gist->id/$filename", $filename);
        }
        
        view_break();
        view_break();
    }
}

function view_gist($id) {
    $gist = github_gist($id);
    
    if (is_null($gist)) {
        http_response_code(400);
        exit;
    }
    
    foreach ((array)$gist->files as $filename => $file) {
        view_link($filename, $filename);
        view_break();
    }
}

function view_gist_file($id, $filename) {
    $gist = github_gist($id);
    
    if (is_null($gist)) {
        http_response_code(400);
        exit;
    }
    
    $files = (array)$gist->files;
    
    if (!isset($files[$filename])) {
        http_response_code(400);
        exit;
    }
    
    $file = $files[$filename];
    
    header('Content-Type: ' . $file->type);
    
    file_put_contents('php://output', $file->content);
}

$username = 'garciat';
$gist_id = $_GET['gist_id'];
$filename = $_GET['filename'];

if (empty($gist_id)) {
    view_user_gists($username);
    exit;
}

if (empty($filename)) {
    view_gist($gist_id);
    exit;
}

view_gist_file($gist_id, $filename);
exit;

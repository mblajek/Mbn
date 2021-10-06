<?php
set_time_limit(1);
$q = filter_input(INPUT_GET, 'text');
$q = ($q === null) ? filter_input(INPUT_POST, 'text') : $q;
if ($q !== null) {
    if ($q === 'worker.js') {
        header('Content-Type: text/javascript');
        readfile('page/calc_worker.js');
        die;
    }
    header('Content-Type: text/plain');
    require_once 'release/mbn.min.php';
    try {
        die(Mbn::calc($q));
    } catch (Exception $e) {
        die($e->getMessage());
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <title>MbnCalc</title>
    <link rel="icon" href="favicon.ico" type="image/png"/>
    <meta charset="UTF-8">
    <meta name="description" content="Mbn Calculator">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
    <meta name="theme-color" content="green">
    <link rel="stylesheet" href="calc_style.css"/>
    <link rel="manifest" href="calc_manifest.json">
</head>
<body>
<script src="mbn.min.js"></script>
<main id="main">
    <a id="home" href="<?=env::homepage;?>"><img src="favicom.ico" alt="home"/></a>
    <div id="buttons">
        <button onclick="mbnChange(0, true);" id="mbnST"></button>
        <button onclick="mbnChange(-1);">&lt;</button>
        <input id="mbnP" oninput="mbnChange(0)"/>
        <button onclick="mbnChange(1);">&gt;</button>
        <button id="addOpt">+</button>
    </div>
    <div class="info"><a href=".">Mbn</a>.calc / const: PI, E, eps</div>
    <div class="info">func: abs, ceil, floor, round, sqrt, sgn, int</div>
    <textarea rows="2" id="inputField"></textarea>
    <div style="display: block">
        =>
        <div style="float: right;" id="timeOutput"></div>
    </div>
    <input readonly id="outputField" onfocus="this.select();">
    <div id="additionalOptions" style="display: none">
        <button id="reloadAll">reload</button>
        <button id="newCalc">new window</button>
    </div>
</main>
<script src="calc_script.js"></script>
</body>
</html>

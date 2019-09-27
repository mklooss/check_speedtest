#!/usr/bin/env php
<?php

/*
usage and default values: 
    --warnping 60
    --critping 90
    --warndownload 60
    --critdownload 40
    --warnupload 60
    --critupload 40
    --speedtestcli /usr/bin/speedtest
 */

// default values?
$warnping     = 60;
$critping     = 90;
$warndownload = 60;
$critdownload = 40;
$warnupload   = 60;
$critupload   = 40;

$options = array(
    'warnping'     => 60,
    'critping'     => 90,
    'warndownload' => 60,
    'critdownload' => 40,
    'warnupload'   => 60,
    'critupload'   => 40,
    'speedtestcli' => shell_exec('which speedtest')
);

$longopt = array(
    'warnping:',
    'critping:',
    'warndownload:',
    'critdownload:',
    'warnupload:',
    'critupload:',
    'speedtestcli:',
);
$params = getopt("a:b:c:d:e:f:h", $longopt);

if (isset($params['h']))
{
    echo "usage and default values: "."\n";
    foreach ($longopt as $_opt)
    {
        $_opt = str_replace(':', '', $_opt);
        echo "\t--".$_opt . " ";
        echo $options[$_opt];
        echo "\n";
    }
    exit;
}

if (isset($params['speedtestcli']) && !empty($params['speedtestcli']))
{
    $options['speedtestcli'] = $params['speedtestcli'];
}
$speedtestcli = escapeshellcmd(trim($options['speedtestcli']));
$json = shell_exec($speedtestcli.' --json');
if (!strstr($json, '{') && !strstr($json, ':') && !strstr($json, '}') && !strstr($json, '"'))
{
    echo "CRITICAL: does not looks like JSON o.O";
    exit(2);
}
$data = json_decode($json, true);

$ip = null;
$download = null;
$upload = null;
$ping = null;

if (isset($data['client']['ip']))
{
    $ip = $data['client']['ip'];
}
if (isset($data['download']))
{
    // to mbit
    $download = round((float)$data['download'] / 1024 / 1024, 2);
}
if (isset($data['upload']))
{
    // to mbit
    $upload = round((float)$data['upload'] / 1024 / 1024, 2);
}
if (isset($data['ping']))
{
    $ping = round((float)$data['ping'], 2);
}

foreach ($params as $_key => $_val)
{
    if (strstr($_key, 'warn') || strstr($_key, 'crit'))
    {
        $options[$_key]     = (float) $_val;
    }
}

$fp = '';
$fd = '';
$fu = '';

$status = 'OK';
// warn
if ($options['warnping'] < $ping)
{
    $fp = '!';
    $status = 'WARNING';
}
if ($options['warndownload'] > $download)
{
    $fd = '!';
    $status = 'WARNING';
}
if ($options['warnupload'] > $upload)
{
    $fu = '!';
    $status = 'WARNING';
}

// crit
if ($options['critping'] < $ping)
{
    $fp = '!';
    $status = 'CRITICAL';
}
if ($options['critdownload'] > $download)
{
    $fd = '!';
    $status = 'CRITICAL';
}
if ($options['critupload'] > $upload)
{
    $fu = '!';
    $status = 'CRITICAL';
}

var_dump($options);

echo "{$status}: {$fp}Ping {$ping}ms - {$fd}Download {$download} Mbit/s - {$fu}Upload {$upload} Mbit/s";
echo " | ping={$ping}ms download={$download} upload={$upload}";
echo "\n";

if ($status == 'OK')
{
    exit(0);
}
if ($status == 'WARNING')
{
    exit(1);
}
if ($status == 'CRITICAL')
{
    exit(2);
}
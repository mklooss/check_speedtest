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
 */

// default values?
$warnping     = 60;
$critping     = 90;
$warndownload = 60;
$critdownload = 40;
$warnupload   = 60;
$critupload   = 40;
$speedtestcli   = shell_exec('which speedtest');

$longopt = array(
    'warnping:',
    'critping:',
    'warndownload:',
    'critdownload:',
    'warnupload:',
    'critupload:',
    'speedtestcli:',
);
$options = getopt("a:b:c:d:e:f:h", $longopt);

if (isset($options['h']))
{
    echo "usage and default values: "."\n";
    foreach ($longopt as $_opt)
    {
        $_opt = str_replace(':', '', $_opt);
        echo "\t--".$_opt . " ";
        echo $$_opt;
        echo "\n";
    }
    exit;
}

if (isset($options['speedtestcli']) && !empty($options['speedtestcli']))
{
    $speedtestcli = $options['speedtestcli'];
}
$speedtestcli = escapeshellcmd(trim($speedtestcli));

var_dump($speedtestcli);
exit;

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

foreach ($options as $_key => $_val)
{
    if ($_key == 'warnping')
    {
    $warnping     = (float) $_val;
    }
    if ($_key == 'critping')
    {
    $critping     = (float) $_val;
    }
    if ($_key == 'warndownload')
    {
    $warndownload = (float) $_val;
    }
    if ($_key == 'critdownload')
    {
    $critdownload = (float) $_val;
    }
    if ($_key == 'warnupload')
    {
    $warnupload   = (float) $_val;
    }
    if ($_key == 'critupload')
    {
    $critupload   = (float) $_val;
    }
}

$fp = '';
$fd = '';
$fu = '';

$status = 'OK';
// warn
if ($warnping < $ping)
{
    $fp = '!';
    $status = 'WARNING';
}
if ($warndownload > $download)
{
    $fd = '!';
    $status = 'WARNING';
}
if ($warnupload > $upload)
{
    $fu = '!';
    $status = 'WARNING';
}

// crit
if ($critping < $ping)
{
    $fp = '!';
    $status = 'CRITICAL';
}
if ($critdownload > $download)
{
    $fd = '!';
    $status = 'CRITICAL';
}
if ($critupload > $upload)
{
    $fu = '!';
    $status = 'CRITICAL';
}

echo "{$status}: {$fp}Ping {$ping}ms - {$fd}Download {$download} Mbit/s - {$fu}Upload {$upload} Mbit/s | ping={$ping}ms download={$download} upload={$upload}";
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
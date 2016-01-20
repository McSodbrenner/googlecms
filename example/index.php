<pre>
<?php

set_time_limit(10);

$id = '1LNvJWag9RNHiF6dtKSFVgYhbK_y7jFa4C3UTI-RUiO0';

$start = microtime(true);

include('../src/GoogleCMS.php');
$cms = new McSodbrenner\GoogleCMS\GoogleCMS($id, 20);
$data = $cms->getData('.');

print_r($data);
die(microtime(true)-$start);


<pre>
<?php

set_time_limit(15);

//$id = '1LNvJWag9RNHiF6dtKSFVgYhbK_y7jFa4C3UTI-RUiO0';
$id = '1K5u3SMjwIX1TazI_ZIZj2MxIC3pfKt-3Iz43D_MlNOU';

$start = microtime(true);

include('../src/GoogleCMS.php');
$cms = new GoogleCMS($id, 20);
$data = $cms->getData('|');

print_r($data);
die(microtime(true)-$start);


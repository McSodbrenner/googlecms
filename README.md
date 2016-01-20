GoogleCMS
===========

A PHP class that uses Google Spreadsheets as CMS.

Use this spreadsheet as an example on how to construct a spreadsheet:
https://docs.google.com/spreadsheets/d/1LNvJWag9RNHiF6dtKSFVgYhbK_y7jFa4C3UTI-RUiO0/edit#gid=0

Take a look at the example that show the parsed result of the example sheet above.

Usage
-----

```
// Extract this id from the URL of your spreadsheet
$spreadsheet_id = '1LNvJWag9RNHiF6dtKSFVgYhbK_y7jFa4C3UTI-RUiO0';

// With the Google Data API it is only possible to get one spreadsheet per request
// So we request all sheets in parallel
// Because Google prevents request flooding we build blocks of 10 requests that are excecuted in parallel.
// This parameter is optional (default = 10)
$request_block_size = 10;

include('src/GoogleCMS.php');
$cms = new McSodbrenner\GoogleCMS\GoogleCMS($spreadsheet_id, $request_block_size);


// Now we get the data. It is possible to build keys like "content1.subkey1" or "content1|subkey1" that are transformed into an array.
// This parameter is optional (default = '.')
$array_divider = '.';

$data = $cms->getData($array_divider);

print_r($data);
```

Hints
-----

* If you have to content ids with the same name, the content is put into an array.
* Use `[IGNORE]` in front of a sheet name to ignore the sheet in the results.

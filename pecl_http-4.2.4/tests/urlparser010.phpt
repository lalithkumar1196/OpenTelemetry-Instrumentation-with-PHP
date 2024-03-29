--TEST--
url parser multibyte/locale/topct
--SKIPIF--
<?php
include "skipif.inc";
if (!defined("http\\Url::PARSE_MBLOC") or
	!utf8locale()) {
	die("skip need http\\Url::PARSE_MBLOC support and LC_CTYPE=*.UTF-8");
}
if (PHP_OS == "Darwin") {
  die("skip Darwin\n");
}
?>
--FILE--
<?php
echo "Test\n";
include "skipif.inc";
utf8locale();

$urls = array(
	"http://mike:paßwort@𐌀𐌁𐌂.it/for/€/?by=¢#ø"
);

foreach ($urls as $url) {
	var_dump(new http\Url($url, null, http\Url::PARSE_MBLOC|http\Url::PARSE_TOPCT));
}
?>
DONE
--EXPECTF--
Test
object(http\Url)#%d (8) {
  ["scheme"]=>
  string(4) "http"
  ["user"]=>
  string(4) "mike"
  ["pass"]=>
  string(12) "pa%C3%9Fwort"
  ["host"]=>
  string(15) "𐌀𐌁𐌂.it"
  ["port"]=>
  NULL
  ["path"]=>
  string(15) "/for/%E2%82%AC/"
  ["query"]=>
  string(9) "by=%C2%A2"
  ["fragment"]=>
  string(6) "%C3%B8"
}
DONE

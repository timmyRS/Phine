<?php /** @noinspection PhpUnhandledExceptionInspection */
if(empty($argv[1]))
{
	die("Syntax: php parse.php <file>\n");
}
if(!is_file(__DIR__."/vendor/autoload.php"))
{
	echo "vendor/autoload.php was not found, attempting to generate it...\n";
	passthru("composer dump-autoload -o -d \"".__DIR__."\"");
	if(!is_file(__DIR__."/vendor/autoload.php"))
	{
		die("Welp, that didn't work. Try again as root/administrator.\n");
	}
}
require __DIR__."/vendor/autoload.php";
use Phine\Code;
print_r((new Code(file_get_contents($argv[1])))->sections);

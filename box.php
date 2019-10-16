<?php /** @noinspection PhpUnhandledExceptionInspection */
if(!is_file(__DIR__."/vendor/autoload.php"))
{
	echo "vendor/autoload.php was not found, attempting to generate it...\n";
	passthru("composer dump-autoload -o -d \"".__DIR__."\"");
	if(!is_file(__DIR__."/vendor/autoload.php"))
	{
		die("Welp, that didn't work. Try again as root/administrator.\n");
	}
}
require "vendor/autoload.php";
use Phine\
{AbstractSection, Code, Project};
if(empty($argv[1]))
{
	die("Syntax: php box.php <file> [desired_line_width = 80] [--no-minify]\n");
}
$code = file_get_contents($argv[1]);
echo "Parsing code...";
$time = microtime(true);
$code = new Code($code);
echo " Done in ".(microtime(true) - $time)." seconds.\n";
$project = new Project($code);
if(@$argv[3] !== "--no-minify")
{
	echo "Renaming variables and functions...";
	$time = microtime(true);
	$project->minifyNames();
	echo " Done in ".(microtime(true) - $time)." seconds.\n";
}
$out_name = substr($argv[1], -4) == ".php" ? substr($argv[1], 0, -4).".box.php" : $argv[1].".box";
$time = microtime(true);
echo "Writing boxed code to $out_name...";
$fh = fopen($out_name, "w");
$i_limit = count($code->sections) - 1;
$line = "";
$desired_line_length = $argv[2] ?? 80;
function writeLine($line)
{
	global $desired_line_length, $fh;
	if($line !== "")
	{
		$remaining = $desired_line_length - strlen($line);
		fwrite($fh, $line);
		if($remaining >= 4)
		{
			fwrite($fh, "/".str_repeat("*", $remaining - 2)."/");
		}
		else if($remaining >= 2)
		{
			fwrite($fh, str_repeat("/", $remaining));
		}
		fwrite($fh, "\n");
	}
}

function handleSection(AbstractSection $section)
{
	global $line, $desired_line_length;
	$section_code = $section->getCode();
	if(strlen($line) + strlen($section_code) > $desired_line_length)
	{
		if($section->canSplit())
		{
			$arr = $section->split($desired_line_length - strlen($line));
			for($i = 0; $i < count($arr); $i++)
			{
				handleSection($arr[$i]);
			}
			return;
		}
		writeLine($line);
		$line = "";
	}
	$line .= $section_code;
	if(strlen($line) > $desired_line_length)
	{
		writeLine($line);
		$line = "";
	}
}

for($i = 0; $i <= $i_limit; $i++)
{
	$section = $code->sections[$i];
	handleSection($section);
	if($line !== "" && $section->requiresDelimiter() && $i < $i_limit && !$code->sections[$i + 1]->delimits())
	{
		$line .= " ";
	}
}
writeLine($line);
fflush($fh);
fclose($fh);
echo " Done in ".(microtime(true) - $time)." seconds.\n";

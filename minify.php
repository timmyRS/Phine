<?php /** @noinspection PhpUnhandledExceptionInspection */
if(empty($argv[1]) || !file_exists($argv[1]))
{
	die("Syntax: php minify.php <file or folder>\n");
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
use Phine\
{Code, Project};
$project = new Project();
function recursivelyIndex(string $input_path, $output_path = null)
{
	if(is_file($input_path))
	{
		if($output_path === null)
		{
			$output_path = substr($input_path, -4) == ".php" ? substr($input_path, 0, -4).".min.php" : $input_path.".min";
		}
		global $project;
		$code = file_get_contents($input_path);
		echo "Parsing $input_path...";
		$time = microtime(true);
		$project->files[$output_path] = new Code($code);
		echo " Done in ".(microtime(true) - $time)." seconds.\n";
	}
	else
	{
		if($output_path === null)
		{
			$output_path = $input_path."-min";
		}
		foreach(scandir($input_path) as $file)
		{
			if(substr($file, 0, 1) == "." || substr($file, -8) == ".min.php" || substr($file, -4) == ".min")
			{
				continue;
			}
			recursivelyIndex($input_path."/".$file, $output_path."/".$file);
		}
	}
}

recursivelyIndex(rtrim($argv[1], "/"));
echo "Renaming variables and functions...";
$time = microtime(true);
$project->minifyNames();
echo " Done in ".(microtime(true) - $time)." seconds.\n";
foreach($project->files as $name => $code)
{
	$dir = dirname($name);
	if(!is_dir($dir))
	{
		mkdir($dir, 0777, true);
	}
	file_put_contents($name, $code->getCode());
}

<?php
namespace Phine;
class Project
{
	/**
	 * @var Code[] $files
	 */
	public $files;

	/**
	 * @param Code[]|Code $files
	 */
	function __construct($files = [])
	{
		if(is_array($files))
		{
			$this->files = $files;
		}
		else if($files instanceof Code)
		{
			$this->files = [$files];
		}
		else
		{
			$this->files = [];
		}
	}

	/**
	 * Minifies the names of variables and functions.
	 *
	 * @todo Give the most commonly-used things the shortest names.
	 */
	function minifyNames()
	{
		$names = range("a", "z");
		array_push($names, "_");
		$i = 0;
		$i_limit = 26;
		$map = [
			"__construct" => "__construct"
		];
		$func_list = [];
		$state = 0;
		foreach($this->files as $code)
		{
			$j_limit = count($code->sections) - 1;
			for($j = 0; $j <= $j_limit; $j++)
			{
				$section = $code->sections[$j];
				if($section instanceof VariableSection)
				{
					if(!$section->isBuiltIn())
					{
						if(!array_key_exists($section->content, $map))
						{
							$map[$section->content] = $names[$i++];
							if($i == $i_limit)
							{
								self::generateMoreNames($names, $i_limit);
							}
						}
						$section->content = $map[$section->content] ?? $section->content;
					}
				}
				else if($section instanceof StringSection)
				{
					foreach($map as $org => $short)
					{
						$section->content = str_replace("\$".$org, "\$".$short, $section->content);
					}
				}
				else
				{
					if($state !== 0)
					{
						if($state === 1)
						{
							if(!array_key_exists($section->content, $map))
							{
								$map[$section->content] = $names[$i++];
								array_push($func_list, $section->content);
								if($i == $i_limit)
								{
									self::generateMoreNames($names, $i_limit);
								}
							}
						}
						$section->content = $map[$section->content] ?? $section->content;
						$state = 0;
					}
					else if($section->content == "function")
					{
						$state = 1;
					}
					else if($section->content == "->")
					{
						$state = 2;
					}
					else if(array_key_exists($section->content, $map) && in_array($section->content, $func_list))
					{
						$section->content = $map[$section->content];
					}
				}
			}
		}
	}

	private static function generateMoreNames(array &$names, int &$i_limit)
	{
		$_names = $names;
		foreach($_names as $name)
		{
			for($i = 0; $i < 27; $i++)
			{
				array_push($names, $name.$_names[$i]);
			}
		}
		$i_limit += (($i_limit + 1) * ($i_limit + 1));
	}
}

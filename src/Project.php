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
	 */
	function minifyNames()
	{
		$symbols = [];
		$func_list = [];
		$next_is_function = false;
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
						if(array_key_exists($section->content, $symbols))
						{
							$symbols[$section->content]++;
						}
						else
						{
							$symbols[$section->content] = 1;
						}
					}
				}
				else if($section instanceof Section)
				{
					if($next_is_function)
					{
						if($section->content != "(")
						{
							if(array_key_exists($section->content, $symbols))
							{
								$symbols[$section->content]++;
							}
							else
							{
								$symbols[$section->content] = 1;
								array_push($func_list, $section->content);
							}
						}
						$next_is_function = false;
					}
					else if($section->content == "function")
					{
						$next_is_function = true;
					}
				}
			}
		}
		assert(!$next_is_function);
		assert(arsort($symbols));
		$names = range("a", "z");
		array_push($names, "_");
		$i = 0;
		$i_limit = 26;
		$map = [
			"__construct" => "__construct"
		];
		foreach($symbols as $name => $uses)
		{
			$map[$name] = $names[$i++];
			if($i == $i_limit)
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
		$map = array_reverse($map, true);
		foreach($this->files as $code)
		{
			$i_limit = count($code->sections) - 1;
			for($i = 0; $i <= $i_limit; $i++)
			{
				$section = $code->sections[$i];
				if($section instanceof VariableSection)
				{
					if(!$section->isBuiltIn())
					{
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
				else //if($section instanceof Section)
				{
					if($next_is_function)
					{
						$section->content = $map[$section->content] ?? $section->content;
						$next_is_function = false;
					}
					else if($section->content == "function" || $section->content == "->")
					{
						$next_is_function = true;
					}
					else if(in_array($section->content, $func_list))
					{
						$section->content = $map[$section->content];
					}
				}
			}
		}
	}
}

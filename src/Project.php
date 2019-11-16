<?php
namespace Phine;
class Project
{
	/**
	 * Rename everything. No limits.
	 *
	 * @see Project::minifyNames()
	 */
	const NO_LIMITS = 0;
	/**
	 * Don't rename public class properties or methods.
	 *
	 * @see Project::minifyNames()
	 */
	const NO_PUBLIC_PROPERTIES_OR_METHODS = 0b001;
	/**
	 * Don't rename classes.
	 *
	 * @see Project::minifyNames()
	 */
	const NO_CLASS_NAMES = 0b010;
	/**
	 * Don't rename namespaces.
	 *
	 * @see Project::minifyNames()
	 */
	const NO_NAMESPACE_NAMES = 0b100;
	/**
	 * Don't rename anything public-facing.
	 * More specifically, this means namespaces, classes, and public class properties and methods will not be renamed.
	 *
	 * @see Project::minifyNames()
	 */
	const NO_PUBLIC = self::NO_PUBLIC_PROPERTIES_OR_METHODS | self::NO_CLASS_NAMES | self::NO_NAMESPACE_NAMES;
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

	function getCode(): string
	{
		$phpmode = false;
		$code = "";
		foreach($this->files as $file)
		{
			$i_limit = count($file->sections) - 1;
			for($i = 0; $i <= $i_limit; $i++)
			{
				if($file->sections[$i] instanceof Section)
				{
					if($file->sections[$i]->content == "<?php")
					{
						if($phpmode)
						{
							continue;
						}
						$phpmode = true;
					}
					else if($file->sections[$i]->content == "?>")
					{
						if(!$phpmode)
						{
							continue;
						}
						$phpmode = false;
					}
				}
				$code .= $file->sections[$i]->getCode();
				if($file->sections[$i]->requiresDelimiter() && $i < $i_limit && !$file->sections[$i + 1]->delimits())
				{
					$code .= " ";
				}
			}
		}
		return $code;
	}

	/**
	 * Minifies the names of variables and functions.
	 *
	 * @param int $flags
	 * @see Project::NO_LIMITS
	 * @see Project::NO_PUBLIC_PROPERTIES_OR_METHODS
	 * @see Project::NO_CLASS_NAMES
	 * @see Project::NO_NAMESPACE_NAMES
	 * @see Project::NO_PUBLIC
	 */
	function minifyNames(int $flags = self::NO_LIMITS)
	{
		$rename_properties_and_methods = !($flags & 0b001);
		$rename_classes = !($flags & 0b010);
		$rename_namespaces = !($flags & 0b100);
		$symbols = [];
		$literal_list = [];
		$class_scope = 0;
		$public = true;
		$next_is_static = false;
		$next_is_literal = false;
		foreach($this->files as $file_name => $code)
		{
			$i_limit = count($code->sections) - 1;
			for($i = 0; $i <= $i_limit; $i++)
			{
				$section = $code->sections[$i];
				if($section instanceof VariableSection)
				{
					if(!$section->isBuiltIn())
					{
						if($class_scope === 0 || !$public || $rename_properties_and_methods)
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
						$public = true;
					}
				}
				else if($section instanceof Section)
				{
					if($next_is_static)
					{
						$next_is_static = false;
					}
					else if($next_is_literal)
					{
						if($section->content != "(" && $section->content != "__construct" && (!$public || $rename_properties_and_methods))
						{
							if(array_key_exists($section->content, $symbols))
							{
								$symbols[$section->content]++;
							}
							else
							{
								$symbols[$section->content] = 1;
								array_push($literal_list, $section->content);
							}
						}
						$next_is_literal = false;
						$public = true;
					}
					else if($section->content == "function")
					{
						$next_is_literal = true;
					}
					else if($section->content == "class")
					{
						if($rename_classes)
						{
							$next_is_literal = true;
						}
						$class_scope = 1;
					}
					else if($section->content == "::")
					{
						$next_is_static = true;
					}
					else if($rename_namespaces && $section->content == "namespace")
					{
						$next_is_literal = true;
					}
					else if($section->content == "private")
					{
						$public = false;
					}
					else if($class_scope !== 0)
					{
						if($section->content == "{")
						{
							$class_scope++;
						}
						else if($section->content == "}")
						{
							if(--$class_scope == 1)
							{
								$class_scope = 0;
							}
							$public = true;
						}
						else if(!$public && $section->content == ";")
						{
							$public = false;
						}
					}
				}
			}
		}
		assert($class_scope === 0);
		assert($public === true);
		assert($next_is_static === false);
		assert($next_is_literal === false);
		assert(arsort($symbols));
		$names = range("a", "z");
		array_push($names, "_");
		$i = 0;
		$i_limit = 26;
		$map = [];
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
		$next_is_function = false;
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
					if(in_array($section->content, $literal_list))
					{
						$section->content = $map[$section->content];
					}
					else
					{
						foreach($map as $org => $short)
						{
							$section->content = str_replace("\$".$org, "\$".$short, $section->content);
						}
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
					else if(in_array($section->content, $literal_list))
					{
						$section->content = $map[$section->content];
					}
				}
			}
		}
	}

	function removeComments()
	{
		foreach($this->files as $code)
		{
			$code->removeComments();
		}
	}
}

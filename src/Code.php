<?php
namespace Phine;
class Code
{
	/**
	 * @var AbstractSection[] $sections
	 */
	public $sections = [];

	/**
	 * Constructs the Code class, converting the given code into an array of sections.
	 *
	 * @param string $code
	 */
	function __construct(string $code)
	{
		$chars = str_split($code);
		$size = count($chars);
		$section = "";
		$phpmode = false;
		$string = 0;
		$escape = false;
		$comment = 0;
		$next_requires_delimiter = false;
		for($i = 0; $i < $size; $i++)
		{
			$char = $chars[$i];
			if(!$phpmode)
			{
				$section .= $char;
				if(substr($section, -5) == "<?php")
				{
					$phpmode = true;
					array_push($this->sections, Section::fromCode($section));
					$section = "";
					$next_requires_delimiter = false;
				}
				continue;
			}
			if($comment !== 0)
			{
				if($comment == 1)
				{
					if($char == "\n")
					{
						array_push($this->sections, new CommentSection(rtrim($section, "\r")));
						$section = "";
						$comment = 0;
					}
					else
					{
						$section .= $char;
					}
				}
				else if($comment == 2)
				{
					$section .= $char;
					if($char == "/" && substr($section, -2, 1) == "*")
					{
						array_push($this->sections, new CommentSection(substr($section, 0, -2)));
						$section = "";
						$comment = 0;
					}
				}
				continue;
			}
			if($string !== 0)
			{
				if($escape)
				{
					$section .= $char;
					$escape = false;
					continue;
				}
				switch($char)
				{
					case "\"":
						if($string == 1)
						{
							array_push($this->sections, new StringSection($section, "\""));
							$string = 0;
							$section = "";
							$next_requires_delimiter = false;
						}
						else
						{
							$section .= $char;
						}
						break;
					case "'":
						if($string == 2)
						{
							array_push($this->sections, new StringSection($section, "'"));
							$string = 0;
							$section = "";
							$next_requires_delimiter = false;
						}
						else
						{
							$section .= $char;
						}
						break;
					case "\\":
						$escape = true;
						$section .= $char;
						break;
					default:
						$section .= $char;
				}
				continue;
			}
			switch($char)
			{
				case "\r":
					break;
				case "\"":
					if($section !== "")
					{
						array_push($this->sections, Section::fromCode($section));
						$section = "";
						$next_requires_delimiter = false;
					}
					$string = 1;
					break;
				case "'":
					if($section !== "")
					{
						array_push($this->sections, Section::fromCode($section));
						$section = "";
						$next_requires_delimiter = false;
					}
					$string = 2;
					break;
				case "\n":
				case "\t":
				case " ":
					if($section !== "")
					{
						$sec = Section::fromCode($section);
						if($next_requires_delimiter)
						{
							if($sec instanceof Section)
							{
								$sec->requires_delimiter = true;
							}
							$next_requires_delimiter = false;
						}
						else if($section == "class")
						{
							$next_requires_delimiter = true;
						}
						array_push($this->sections, $sec);
						$section = "";
					}
					break;
				case ";":
				case ",":
				case "(":
				case ")":
				case "[":
				case "]":
				case "{":
				case "}":
				case "\\":
					if($section !== "")
					{
						array_push($this->sections, Section::fromCode($section));
						$section = "";
						$next_requires_delimiter = false;
					}
					array_push($this->sections, Section::fromCode($char));
					break;
				case "!":
				case "\$":
					if($section !== "")
					{
						array_push($this->sections, Section::fromCode($section));
						$section = $char;
					}
					else
					{
						$section .= $char;
					}
					break;
				case "+":
				case "-":
				case ".":
				case "=":
					if(in_array($section, [
						"",
						"+",
						"-",
						"*",
						"/",
						".",
						"=",
						"!",
						"!=",
						"<",
						">",
						"|",
						"&"
					]))
					{
						$section .= $char;
					}
					else
					{
						array_push($this->sections, Section::fromCode($section));
						$section = $char;
					}
					break;
				case ":":
					if($section !== "" && $section != ":")
					{
						array_push($this->sections, Section::fromCode($section));
						$section = $char;
					}
					else
					{
						$section .= $char;
						if($section == "::")
						{
							array_push($this->sections, Section::fromCode($section));
							$section = "";
							$next_requires_delimiter = false;
						}
					}
					break;
				case "/":
					$section .= $char;
					if($section == "//")
					{
						$section = "";
						$next_requires_delimiter = false;
						$comment = 1;
					}
					else if($section != "/")
					{
						array_push($this->sections, Section::fromCode($section));
						$section = "/";
						$next_requires_delimiter = false;
					}
					break;
				case "*":
					if($section == "/")
					{
						$section = "";
						$next_requires_delimiter = false;
						$comment = 2;
					}
					else
					{
						if($section !== "")
						{
							array_push($this->sections, Section::fromCode($section));
							$section = "";
							$next_requires_delimiter = false;
						}
						array_push($this->sections, Section::fromCode("*"));
					}
					break;
				case ">":
					$section .= $char;
					if(substr($section, -2) == "->")
					{
						if(strlen($section) > 2)
						{
							array_push($this->sections, Section::fromCode(substr($section, 0, -2)));
						}
						array_push($this->sections, Section::fromCode("->"));
						$section = "";
						$next_requires_delimiter = true;
					}
					else if(substr($section, -2) == "?>")
					{
						if(strlen($section) > 2)
						{
							array_push($this->sections, Section::fromCode(substr($section, 0, -2)));
						}
						array_push($this->sections, Section::fromCode("?>"));
						$phpmode = false;
						$section = "";
						$next_requires_delimiter = false;
					}
					break;
				default:
					$section .= $char;
			}
		}
		if($section !== "")
		{
			array_push($this->sections, Section::fromCode($section));
		}
	}

	function getCode(): string
	{
		$code = "";
		$i_limit = count($this->sections) - 1;
		for($i = 0; $i <= $i_limit; $i++)
		{
			$code .= $this->sections[$i]->getCode();
			if($this->sections[$i]->requiresDelimiter() && $i < $i_limit && !$this->sections[$i + 1]->delimits())
			{
				$code .= " ";
			}
		}
		return $code;
	}

	function removeComments()
	{
		$this->sections = array_values(array_filter($this->sections, function($section)
		{
			return !$section instanceof CommentSection;
		}));
	}
}

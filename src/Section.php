<?php
namespace Phine;
class Section
{
	static $delimiters = [];
	public $content;
	public $requires_delimiter;

	function __construct(string $content, bool $requires_delimiter = false)
	{
		$this->content = $content;
		$this->requires_delimiter = $requires_delimiter;
	}

	static function fromCode(string $code): Section
	{
		if(substr($code, 0, 1) == "\$")
		{
			return new VariableSection(substr($code, 1));
		}
		return new Section($code, in_array($code, [
			"echo",
			"else",
			"function",
			"return",
			"new",
			"instanceof",
			"throw",
			"public",
			"private",
			"var",
			"static",
			"abstract",
			"<?php",
			"use",
			"class",
			"namespace",
			"extends",
			"require",
			"require_once",
			"include"
		]));
	}

	function getCode()
	{
		return $this->content;
	}

	function delimits(): bool
	{
		return in_array(substr($this->content, 0, 1), self::$delimiters);
	}

	/**
	 * @return bool
	 * @see Section::split()
	 */
	function canSplit(): bool
	{
		return false;
	}

	/**
	 * Splits this Section into multiple parts, with the first part's length being influenceable.
	 *
	 * @param int $desired_length_for_first_part
	 * @return Section[]
	 * @see Section::canSplit()
	 */
	function split(int $desired_length_for_first_part): array
	{
		return [$this];
	}
}

Section::$delimiters = [
	";",
	",",
	"\$",
	"\"",
	"'",
	"(",
	")",
	"{",
	"}",
	"[",
	"]",
	"\\",
	".",
	"+",
	"-",
	"*",
	"&",
	"|",
	"=",
	"<",
	">",
	"?"
];

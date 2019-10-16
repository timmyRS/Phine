<?php
namespace Phine;
abstract class AbstractSection
{
	static $delimiters = [];
	public $content;

	function __construct(string $content)
	{
		$this->content = $content;
	}

	static function fromCode(string $code): AbstractSection
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

	abstract function getCode(): string;

	abstract function delimits(): bool;

	abstract function requiresDelimiter(): bool;

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

AbstractSection::$delimiters = [
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


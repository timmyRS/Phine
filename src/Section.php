<?php
namespace Phine;
class Section extends AbstractSection
{
	public $requires_delimiter;

	function __construct(string $content, bool $requires_delimiter = false)
	{
		parent::__construct($content);
		$this->requires_delimiter = $requires_delimiter;
	}

	function getCode(): string
	{
		return $this->content;
	}

	function delimits(): bool
	{
		return in_array(substr($this->content, 0, 1), AbstractSection::$delimiters);
	}

	function requiresDelimiter(): bool
	{
		return $this->requires_delimiter;
	}
}

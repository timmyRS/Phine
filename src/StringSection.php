<?php
namespace Phine;
class StringSection extends AbstractSection
{
	private $delimiter;

	function __construct(string $content, string $delimiter)
	{
		parent::__construct($content);
		$this->delimiter = $delimiter;
	}

	function getCode(): string
	{
		return $this->delimiter.$this->content.$this->delimiter;
	}

	function delimits(): bool
	{
		return true;
	}

	function requiresDelimiter(): bool
	{
		return false;
	}

	/**
	 * @return bool
	 * @see Section::split()
	 */
	function canSplit(): bool
	{
		return strpos($this->content, "\$") === false && strpos($this->content, "\\") === false && strlen($this->content) > 1;
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
		$len = max($desired_length_for_first_part, 3);
		$parts = [
			new StringSection(substr($this->content, 0, $len - 2), $this->delimiter)
		];
		$remaining = substr($this->content, $len - 2);
		if($remaining !== "")
		{
			array_push($parts, new Section("."));
			array_push($parts, new StringSection($remaining, $this->delimiter));
		}
		return $parts;
	}
}

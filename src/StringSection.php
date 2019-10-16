<?php
namespace Phine;
class StringSection extends Section
{
	function __construct(string $content)
	{
		parent::__construct($content, false);
	}

	function getCode()
	{
		return "\"".$this->content."\"";
	}

	function delimits(): bool
	{
		return true;
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
			new StringSection(substr($this->content, 0, $len - 2))
		];
		$remaining = substr($this->content, $len - 2);
		if($remaining !== "")
		{
			array_push($parts, new Section("."));
			array_push($parts, new StringSection($remaining));
		}
		return $parts;
	}
}

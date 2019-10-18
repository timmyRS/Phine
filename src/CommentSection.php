<?php
namespace Phine;
class CommentSection extends AbstractSection
{
	function getCode(): string
	{
		return "/*".$this->content."*/";
	}

	function delimits(): bool
	{
		return true;
	}

	function requiresDelimiter(): bool
	{
		return false;
	}
}

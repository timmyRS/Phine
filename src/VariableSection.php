<?php
namespace Phine;
class VariableSection extends Section
{
	static $built_ins = [];

	function __construct(string $content)
	{
		parent::__construct($content, true);
	}

	function getCode()
	{
		return "\$".$this->content;
	}

	function isBuiltIn(): bool
	{
		return in_array($this->content, VariableSection::$built_ins);
	}

	function delimits(): bool
	{
		return true;
	}
}

VariableSection::$built_ins = [
	"GLOBALS",
	"_SERVER",
	"_GET",
	"_POST",
	"_FILES",
	"_REQUEST",
	"_SESSION",
	"_ENV",
	"_COOKIE",
	"php_errormsg",
	"HTTP_RAW_POST_DATA",
	"http_response_header",
	"argc",
	"argv",
	"this"
];

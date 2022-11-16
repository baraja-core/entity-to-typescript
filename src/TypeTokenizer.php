<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript;


use Baraja\EntityToTypescript\Entity\DocToken;

/**
 * Inspired by https://en.php.brj.cz/string-tokenization-in-php
 */
final class TypeTokenizer
{
	public const TokenTypes = [
		'array' => 'array',
		'<' => '\<',
		'>' => '\>',
		'{' => '\{',
		'}' => '\}',
		'or' => '\|',
		'list' => '\[\]',
		'type' => '[a-zA-Z]+',
		'space' => '\s+',
		'comma' => ',',
		'other' => '.+?',
	];


	/**
	 * @return array<int, DocToken>
	 */
	public static function tokenize(string $haystack): array
	{
		$re = '~(' . implode(')|(', self::TokenTypes) . ')~A';
		$types = array_keys(self::TokenTypes);

		preg_match_all($re, $haystack, $tokenMatch, PREG_SET_ORDER);

		$len = 0;
		$count = count($types);
		$tokens = [];
		foreach ($tokenMatch as $match) {
			$type = null;
			for ($i = 1; $i <= $count; $i++) {
				if (isset($match[$i]) === false) {
					break;
				}
				if ($match[$i] !== '') {
					$type = $types[$i - 1];
					break;
				}
			}
			$token = new DocToken;
			$token->value = $match[0];
			$token->offset = $len;
			$token->type = (string) $type;

			$tokens[] = $token;
			$len += strlen($match[0]);
		}

		if ($len !== strlen($haystack)) {
			$text = substr($haystack, 0, $len);
			$line = substr_count($text, "\n") + 1;
			$col = $len - strrpos("\n" . $text, "\n") + 1;
			$token = str_replace("\n", '\n', substr($haystack, $len, 10));

			throw new \LogicException(sprintf('Unexpected "%s" on line %s, column %s.', $token, $line, $col));
		}

		return $tokens;
	}
}

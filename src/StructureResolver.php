<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript;


use Baraja\EntityToTypescript\Entity\DocToken;
use Baraja\EntityToTypescript\Entity\StructureItem;
use Baraja\EntityToTypescript\Entity\StructureResponse;

final class StructureResolver
{
	public function process(\ReflectionProperty $property): string|StructureResponse
	{
		$line = TypescriptHelpers::resolveCommentAsSingleLine($property);
		if ($line === null) {
			return 'unknown[]';
		}

		$tokens = TypeTokenizer::tokenize($line);

		$structure = [];
		$lastToken = null;
		$lastStructureItem = null;
		$levelHasBeenUsed = false;
		$arrayLevels = [];
		$finalTokenKey = null;
		foreach ($tokens as $tokenKey => $token) {
			if ($token->type === 'space') {
				continue;
			}
			if ($token->type === 'list') {
				$structure[] = new StructureItem(code: '[]');
			} elseif ($arrayLevels === [] && $levelHasBeenUsed) {
				$finalTokenKey = $tokenKey > 0 && $tokenKey < count($tokens) - 1 ? $tokenKey : null;
				break;
			} elseif ($token->type === 'type') {
				$expanded = PropertyTypeResolver::expandClassName(
					$token->value,
					PropertyTypeResolver::getPropertyDeclaringClass($property),
				);
				if ($arrayLevels === [] && $lastToken !== null && class_exists($expanded) === false) {
					break;
				}
				$structureItem = $lastStructureItem ?? new StructureItem;
				if (class_exists($expanded) && TypescriptHelpers::isBuiltinType($expanded) === false) {
					$structureItem->type = $expanded;
				}
				if ($lastToken !== null) {
					if ($lastToken->type === '<') {
						$structureItem->key = $token->value;
					}
					if ($lastToken->type === 'comma') {
						array_pop($structure);
						$structureItem->value = $token->value;
					}
				}
				$structure[] = $structureItem;
				$lastStructureItem = &$structureItem;
			} elseif ($token->type === 'array') {
				$structureItem = new StructureItem(code: 'Record');
				$lastStructureItem = &$structureItem;
			} elseif ($token->type === '<' || $token->type === '{') {
				if ($lastToken === null || $lastToken->type !== 'array') {
					throw new \InvalidArgumentException('Parse error: Can not start array without "array" prefix.');
				}
				$arrayLevels[] = $token->type;
				$levelHasBeenUsed = true;
			} elseif ($token->type === '>' || $token->type === '}') {
				// remove last item
				array_pop($arrayLevels);
				if ($lastStructureItem !== null
					&& $lastStructureItem->key !== null
					&& $lastStructureItem->value === null
				) {
					array_pop($structure);
					$structureItem = $lastStructureItem;
					$structureItem->value = $structureItem->key;
					$structure[] = $structureItem;
				}
				$lastStructureItem = null;
			}

			$lastToken = $token;
		}

		$response = new StructureResponse;
		$response->sourceLine = $line;
		$response->structure = $structure;
		$response->comment = $finalTokenKey !== null
			? $this->resolveComment($tokens, $finalTokenKey)
			: null;

		return $response;
	}


	/**
	 * @param array<int, DocToken> $tokens
	 */
	private function resolveComment(array $tokens, int $fromToken = 0): string
	{
		$return = '';
		for ($i = $fromToken; isset($tokens[$i]); $i++) {
			$return .= $tokens[$i]->value;
		}

		return trim($return);
	}
}

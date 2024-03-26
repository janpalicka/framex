<?php
declare(strict_types = 1);

namespace Contributte\FrameX\Middleware;

use Contributte\FrameX\Http\ErrorResponse;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationMiddleware
{

	/** @var array<string> */
	private array $allowedRoutes = [];

	/** @var array<string> */
	private array $allowedTokens = [];

	/**
	 * @param array<string> $allowed
	 */
	public function whitelist(array $allowed): void
	{
		$this->allowedRoutes = $allowed;
	}

	/**
	 * @param array<string> $tokens
	 */
	public function allowTokens(array $tokens): void
	{
		$this->allowedTokens = $tokens;
	}

	public function __invoke(ServerRequestInterface $request, callable $next): mixed
	{
		// Whitelist
		$currentRoute = $request->getUri()->getPath();
		if (in_array($currentRoute, $this->allowedRoutes, true) || in_array('*', $this->allowedRoutes, true)) {
			return $next($request);
		}

		/** @var string|array<mixed> $headerToken */
		$headerToken = $request->getHeader('Authorization');

		if (is_string($headerToken) === false) {
			return ErrorResponse::create()->withErrorCode(401)->withMessage('Unauthorized');
		}

		/** @var string|false|null $token */
		$token = explode(' ', $headerToken)[1] ?? null;

		if ($token === false || $token === null) {
			return ErrorResponse::create()->withErrorCode(403)->withMessage('Missing token');
		}

		// using predefined api tokens instead of ldap/authorization
		if (in_array($token, $this->allowedTokens, true)) {
			return $next($request);
		}

		return ErrorResponse::create()->withErrorCode(403)->withMessage('Bad auth token');
	}

}

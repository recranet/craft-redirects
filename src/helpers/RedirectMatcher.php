<?php

namespace recranet\redirects\helpers;

/**
 * Pure matching logic, decoupled from Craft so it can be unit tested.
 * Operates on plain database rows (arrays).
 */
final class RedirectMatcher
{
    /**
     * Find the first matching redirect row for a path.
     *
     * @param array $redirects Rows from the redirects table (enabled ones)
     * @param string $path Request path, or a full URL
     * @param int|null $siteId Current site ID (null matches any)
     * @param string|null $hostInfo e.g. "https://example.com", enables full-URL matching
     * @param \DateTimeInterface|null $now Reference time for expiry checks (UTC)
     * @param string|null $queryString The request's query string, without "?". When null, a query
     *   string in $path is used instead (e.g. from the Test URL tool)
     * @return array|null The matched row, with toUrl resolved for regex matches
     */
    public static function match(
        array $redirects,
        string $path,
        ?int $siteId = null,
        ?string $hostInfo = null,
        ?\DateTimeInterface $now = null,
        ?string $queryString = null,
    ): ?array {
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // The query string never takes part in path matching
        [$path, $pathQuery] = self::splitQuery($path);
        $requestPairs = self::queryPairs($queryString ?? $pathQuery);

        // Accept a full URL as input (e.g. from the Test URL tool)
        if (preg_match('#^(https?://[^/]+)(/.*)?$#i', $path, $matches)) {
            $hostInfo = $matches[1];
            $path = $matches[2] ?? '/';
        }

        $path = '/' . ltrim($path, '/');
        $normalizedPath = strtolower(rtrim($path, '/'));

        // Exact-match candidates: the path, and the full URL when the host is known
        $candidates = [$normalizedPath];
        if ($hostInfo) {
            $candidates[] = strtolower(rtrim($hostInfo, '/')) . $normalizedPath;
        }

        $exactMatches = [];
        $queryMatches = [];
        $regexRows = [];

        foreach ($redirects as $row) {
            if (!self::isActive($row, $siteId, $now)) {
                continue;
            }

            if (($row['matchType'] ?? 'exact') === 'regex') {
                $regexRows[] = $row;
                continue;
            }

            [$fromPath, $fromQuery] = self::splitQuery((string)($row['fromUrl'] ?? ''));
            $from = strtolower(rtrim($fromPath, '/'));
            if (!in_array($from, $candidates, true)) {
                continue;
            }

            // A From URL with a query string only matches requests that carry all of its parameters
            $fromPairs = self::queryPairs($fromQuery);
            if (!$fromPairs) {
                $exactMatches[] = $row;
            } elseif (!array_diff($fromPairs, $requestPairs)) {
                $queryMatches[] = $row + ['_queryParams' => count($fromPairs)];
            }
        }

        // A matching query string wins over the bare path: most parameters first,
        // then site-specific before global, then oldest first
        if ($queryMatches) {
            usort($queryMatches, function (array $a, array $b) {
                return [-$a['_queryParams'], ($a['siteId'] ?? null) === null, (int)($a['id'] ?? 0)]
                    <=> [-$b['_queryParams'], ($b['siteId'] ?? null) === null, (int)($b['id'] ?? 0)];
            });
            unset($queryMatches[0]['_queryParams']);

            return $queryMatches[0];
        }

        // Site-specific wins over global
        foreach ($exactMatches as $row) {
            if (($row['siteId'] ?? null) !== null) {
                return $row;
            }
        }
        if ($exactMatches) {
            return $exactMatches[0];
        }

        // Regex matches, in deterministic order: priority (lower first),
        // then site-specific before global, then oldest first
        usort($regexRows, function (array $a, array $b) {
            return [(int)($a['priority'] ?? 0), ($a['siteId'] ?? null) === null, (int)($a['id'] ?? 0)]
                <=> [(int)($b['priority'] ?? 0), ($b['siteId'] ?? null) === null, (int)($b['id'] ?? 0)];
        });

        $subjects = [$path];
        if ($hostInfo) {
            $subjects[] = rtrim($hostInfo, '/') . $path;
        }

        foreach ($regexRows as $row) {
            $pattern = '#' . $row['fromUrl'] . '#i';

            foreach ($subjects as $subject) {
                if (@preg_match($pattern, $subject)) {
                    // Support $1, $2 etc. backreferences in toUrl
                    $row['toUrl'] = @preg_replace($pattern, (string)$row['toUrl'], $subject);
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Match a front-end request: first the site-relative path, then the full path.
     *
     * On a subfolder site (e.g. https://example.com/de) Craft strips the base path,
     * so "/de/old-page" arrives as "old-page". Redirects can be entered either way.
     *
     * @param string $path Site-relative path (Craft's getPathInfo())
     * @param string|null $fullPath Path including the site's base path (Craft's getFullPath())
     * @param string|null $queryString The request's query string, without "?"
     */
    public static function matchRequest(
        array $redirects,
        string $path,
        ?string $fullPath,
        ?int $siteId = null,
        ?string $hostInfo = null,
        ?\DateTimeInterface $now = null,
        ?string $queryString = null,
    ): ?array {
        $match = self::match($redirects, $path, $siteId, $hostInfo, $now, $queryString);

        if ($match === null && $fullPath !== null && trim($fullPath, '/') !== trim($path, '/')) {
            $match = self::match($redirects, $fullPath, $siteId, $hostInfo, $now, $queryString);
        }

        return $match;
    }

    /**
     * Append the incoming query string to a destination URL.
     */
    public static function appendQueryString(string $toUrl, string $queryString): string
    {
        if ($queryString === '') {
            return $toUrl;
        }

        return $toUrl . (str_contains($toUrl, '?') ? '&' : '?') . $queryString;
    }

    /**
     * The request's query string without the parameters a From URL matched on, so those
     * aren't carried over to the destination: ("/book?type=264", "type=264&utm_source=x") -> "utm_source=x".
     */
    public static function remainingQueryString(string $fromUrl, string $queryString): string
    {
        $fromPairs = self::queryPairs(self::splitQuery($fromUrl)[1]);
        if (!$fromPairs) {
            return $queryString;
        }

        $remaining = array_filter(
            explode('&', $queryString),
            fn(string $pair) => $pair !== '' && !in_array(self::normalizePair($pair), $fromPairs, true),
        );

        return implode('&', $remaining);
    }

    /**
     * Split a URL or path into the part before "?" and the query string after it.
     *
     * @return array{0: string, 1: string}
     */
    private static function splitQuery(string $url): array
    {
        $parts = explode('?', $url, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * A query string as a list of normalized "key=value" pairs, so encoding ("type%5B0%5D" vs
     * "type[0]"), case and parameter order don't matter when comparing.
     *
     * @return string[]
     */
    private static function queryPairs(string $queryString): array
    {
        $pairs = [];
        foreach (explode('&', $queryString) as $pair) {
            if ($pair !== '') {
                $pairs[] = self::normalizePair($pair);
            }
        }

        return array_values(array_unique($pairs));
    }

    private static function normalizePair(string $pair): string
    {
        $parts = explode('=', $pair, 2);

        return strtolower(urldecode($parts[0]) . '=' . urldecode($parts[1] ?? ''));
    }

    /**
     * The path part of a site's base URL, without trailing slash: "https://example.com/de/" -> "/de", root sites -> "".
     */
    public static function basePath(?string $baseUrl): string
    {
        $path = (string)parse_url((string)$baseUrl, PHP_URL_PATH);

        return rtrim($path, '/');
    }

    /**
     * A site-relative URI as a root-relative path that includes the site's base path:
     * ("old-page", "/de") -> "/de/old-page", ("old-page", "") -> "/old-page".
     */
    public static function sitePath(string $uri, string $basePath = ''): string
    {
        return rtrim($basePath, '/') . '/' . ltrim($uri, '/');
    }

    private static function isActive(array $row, ?int $siteId, \DateTimeInterface $now): bool
    {
        if (!($row['enabled'] ?? true)) {
            return false;
        }

        $rowSiteId = $row['siteId'] ?? null;
        if ($rowSiteId !== null && $siteId !== null && (int)$rowSiteId !== $siteId) {
            return false;
        }

        if (!empty($row['expiryDate'])) {
            $expiry = new \DateTimeImmutable($row['expiryDate'], new \DateTimeZone('UTC'));
            if ($expiry <= $now) {
                return false;
            }
        }

        return true;
    }
}

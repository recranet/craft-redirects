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
     * @return array|null The matched row, with toUrl resolved for regex matches
     */
    public static function match(
        array $redirects,
        string $path,
        ?int $siteId = null,
        ?string $hostInfo = null,
        ?\DateTimeInterface $now = null,
    ): ?array {
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

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
        $regexRows = [];

        foreach ($redirects as $row) {
            if (!self::isActive($row, $siteId, $now)) {
                continue;
            }

            if (($row['matchType'] ?? 'exact') === 'regex') {
                $regexRows[] = $row;
                continue;
            }

            $from = strtolower(rtrim((string)($row['fromUrl'] ?? ''), '/'));
            if (in_array($from, $candidates, true)) {
                $exactMatches[] = $row;
            }
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
     */
    public static function matchRequest(
        array $redirects,
        string $path,
        ?string $fullPath,
        ?int $siteId = null,
        ?string $hostInfo = null,
        ?\DateTimeInterface $now = null,
    ): ?array {
        $match = self::match($redirects, $path, $siteId, $hostInfo, $now);

        if ($match === null && $fullPath !== null && trim($fullPath, '/') !== trim($path, '/')) {
            $match = self::match($redirects, $fullPath, $siteId, $hostInfo, $now);
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

<?php

namespace recranet\redirects\tests;

use PHPUnit\Framework\TestCase;
use recranet\redirects\helpers\RedirectMatcher;

class RedirectMatcherTest extends TestCase
{
    private function row(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'siteId' => null,
            'fromUrl' => '/old-page',
            'toUrl' => '/new-page',
            'type' => 301,
            'matchType' => 'exact',
            'enabled' => 1,
            'expiryDate' => null,
        ], $overrides);
    }

    // --- Exact matching ---

    public function testExactMatch(): void
    {
        $match = RedirectMatcher::match([$this->row()], '/old-page');

        $this->assertNotNull($match);
        $this->assertSame('/new-page', $match['toUrl']);
    }

    public function testNoMatchReturnsNull(): void
    {
        $this->assertNull(RedirectMatcher::match([$this->row()], '/other-page'));
    }

    public function testTrailingSlashNormalization(): void
    {
        $this->assertNotNull(RedirectMatcher::match([$this->row()], '/old-page/'));
        $this->assertNotNull(RedirectMatcher::match([$this->row(['fromUrl' => '/old-page/'])], '/old-page'));
    }

    public function testCaseInsensitiveMatch(): void
    {
        $this->assertNotNull(RedirectMatcher::match([$this->row()], '/OLD-Page'));
        $this->assertNotNull(RedirectMatcher::match([$this->row(['fromUrl' => '/Old-Page'])], '/old-page'));
    }

    public function testHomepagePath(): void
    {
        $match = RedirectMatcher::match([$this->row(['fromUrl' => '/'])], '/');

        $this->assertNotNull($match);
    }

    public function testPathWithoutLeadingSlash(): void
    {
        $this->assertNotNull(RedirectMatcher::match([$this->row()], 'old-page'));
    }

    // --- Enabled / expiry ---

    public function testDisabledRedirectIsSkipped(): void
    {
        $this->assertNull(RedirectMatcher::match([$this->row(['enabled' => 0])], '/old-page'));
    }

    public function testExpiredRedirectIsSkipped(): void
    {
        $now = new \DateTimeImmutable('2026-07-14 12:00:00', new \DateTimeZone('UTC'));
        $expired = $this->row(['expiryDate' => '2026-07-01 00:00:00']);

        $this->assertNull(RedirectMatcher::match([$expired], '/old-page', null, null, $now));
    }

    public function testFutureExpiryStillMatches(): void
    {
        $now = new \DateTimeImmutable('2026-07-14 12:00:00', new \DateTimeZone('UTC'));
        $active = $this->row(['expiryDate' => '2026-08-01 00:00:00']);

        $this->assertNotNull(RedirectMatcher::match([$active], '/old-page', null, null, $now));
    }

    public function testNullExpiryAlwaysMatches(): void
    {
        $this->assertNotNull(RedirectMatcher::match([$this->row(['expiryDate' => null])], '/old-page'));
    }

    // --- Site scoping ---

    public function testOtherSiteRedirectIsSkipped(): void
    {
        $this->assertNull(RedirectMatcher::match([$this->row(['siteId' => 2])], '/old-page', 1));
    }

    public function testGlobalRedirectMatchesAnySite(): void
    {
        $this->assertNotNull(RedirectMatcher::match([$this->row(['siteId' => null])], '/old-page', 1));
    }

    public function testSiteSpecificWinsOverGlobal(): void
    {
        $global = $this->row(['id' => 1, 'siteId' => null, 'toUrl' => '/global-target']);
        $siteSpecific = $this->row(['id' => 2, 'siteId' => 1, 'toUrl' => '/site-target']);

        $match = RedirectMatcher::match([$global, $siteSpecific], '/old-page', 1);

        $this->assertSame('/site-target', $match['toUrl']);
    }

    public function testNullSiteIdMatchesSiteSpecificRedirect(): void
    {
        $this->assertNotNull(RedirectMatcher::match([$this->row(['siteId' => 2])], '/old-page', null));
    }

    // --- Full-URL matching ---

    public function testFullUrlFromUrlMatchesWithHostInfo(): void
    {
        $row = $this->row(['fromUrl' => 'https://example.com/old-page']);

        $match = RedirectMatcher::match([$row], '/old-page', null, 'https://example.com');

        $this->assertNotNull($match);
    }

    public function testFullUrlFromUrlDoesNotMatchOtherHost(): void
    {
        $row = $this->row(['fromUrl' => 'https://example.com/old-page']);

        $this->assertNull(RedirectMatcher::match([$row], '/old-page', null, 'https://other.com'));
    }

    public function testFullUrlFromUrlDoesNotMatchWithoutHostInfo(): void
    {
        $row = $this->row(['fromUrl' => 'https://example.com/old-page']);

        $this->assertNull(RedirectMatcher::match([$row], '/old-page'));
    }

    public function testAbsoluteUrlAsInputPath(): void
    {
        $match = RedirectMatcher::match([$this->row()], 'https://example.com/old-page');

        $this->assertNotNull($match);
    }

    public function testAbsoluteUrlInputMatchesFullUrlRedirect(): void
    {
        $row = $this->row(['fromUrl' => 'https://example.com/old-page']);

        $this->assertNotNull(RedirectMatcher::match([$row], 'https://example.com/old-page'));
    }

    // --- Regex matching ---

    public function testRegexMatch(): void
    {
        $row = $this->row(['matchType' => 'regex', 'fromUrl' => '^/blog/(.*)$', 'toUrl' => '/articles/$1']);

        $match = RedirectMatcher::match([$row], '/blog/my-post');

        $this->assertNotNull($match);
        $this->assertSame('/articles/my-post', $match['toUrl']);
    }

    public function testRegexMultipleBackreferences(): void
    {
        $row = $this->row(['matchType' => 'regex', 'fromUrl' => '^/blog/(\d{4})/(.*)$', 'toUrl' => '/articles/$1/$2']);

        $match = RedirectMatcher::match([$row], '/blog/2024/my-post');

        $this->assertSame('/articles/2024/my-post', $match['toUrl']);
    }

    public function testRegexNoMatch(): void
    {
        $row = $this->row(['matchType' => 'regex', 'fromUrl' => '^/blog/(.*)$', 'toUrl' => '/articles/$1']);

        $this->assertNull(RedirectMatcher::match([$row], '/shop/product'));
    }

    public function testExactMatchWinsOverRegex(): void
    {
        $regex = $this->row(['id' => 1, 'matchType' => 'regex', 'fromUrl' => '^/blog/.*$', 'toUrl' => '/regex-target']);
        $exact = $this->row(['id' => 2, 'fromUrl' => '/blog/post', 'toUrl' => '/exact-target']);

        $match = RedirectMatcher::match([$regex, $exact], '/blog/post');

        $this->assertSame('/exact-target', $match['toUrl']);
    }

    public function testSiteSpecificRegexWinsOverGlobalRegex(): void
    {
        $global = $this->row(['id' => 1, 'matchType' => 'regex', 'siteId' => null, 'fromUrl' => '^/blog/.*$', 'toUrl' => '/global']);
        $siteSpecific = $this->row(['id' => 2, 'matchType' => 'regex', 'siteId' => 1, 'fromUrl' => '^/blog/.*$', 'toUrl' => '/site']);

        $match = RedirectMatcher::match([$global, $siteSpecific], '/blog/post', 1);

        $this->assertSame('/site', $match['toUrl']);
    }

    public function testRegexMatchesFullUrlSubject(): void
    {
        $row = $this->row(['matchType' => 'regex', 'fromUrl' => '^https://example\.com/promo/(.*)$', 'toUrl' => '/campaign/$1']);

        $match = RedirectMatcher::match([$row], '/promo/summer', null, 'https://example.com');

        $this->assertSame('/campaign/summer', $match['toUrl']);
    }

    public function testExpiredRegexIsSkipped(): void
    {
        $now = new \DateTimeImmutable('2026-07-14 12:00:00', new \DateTimeZone('UTC'));
        $row = $this->row([
            'matchType' => 'regex',
            'fromUrl' => '^/blog/.*$',
            'expiryDate' => '2026-07-01 00:00:00',
        ]);

        $this->assertNull(RedirectMatcher::match([$row], '/blog/post', null, null, $now));
    }

    public function testRegexPriorityOrdersOverlappingPatterns(): void
    {
        $broad = $this->row(['id' => 1, 'matchType' => 'regex', 'priority' => 10, 'fromUrl' => '^/blog/(.*)$', 'toUrl' => '/articles/$1']);
        $specific = $this->row(['id' => 2, 'matchType' => 'regex', 'priority' => 5, 'fromUrl' => '^/blog/archive/(.*)$', 'toUrl' => '/archive/$1']);

        $match = RedirectMatcher::match([$broad, $specific], '/blog/archive/old-post');

        $this->assertSame('/archive/old-post', $match['toUrl']);
    }

    public function testRegexPriorityBeatsSitePriority(): void
    {
        $siteSpecific = $this->row(['id' => 1, 'matchType' => 'regex', 'siteId' => 1, 'priority' => 10, 'fromUrl' => '^/blog/.*$', 'toUrl' => '/site']);
        $global = $this->row(['id' => 2, 'matchType' => 'regex', 'siteId' => null, 'priority' => 1, 'fromUrl' => '^/blog/.*$', 'toUrl' => '/global']);

        $match = RedirectMatcher::match([$siteSpecific, $global], '/blog/post', 1);

        $this->assertSame('/global', $match['toUrl']);
    }

    public function testEqualPriorityFallsBackToSiteThenId(): void
    {
        $globalOld = $this->row(['id' => 1, 'matchType' => 'regex', 'siteId' => null, 'fromUrl' => '^/blog/.*$', 'toUrl' => '/global-old']);
        $siteNew = $this->row(['id' => 2, 'matchType' => 'regex', 'siteId' => 1, 'fromUrl' => '^/blog/.*$', 'toUrl' => '/site-new']);

        $match = RedirectMatcher::match([$globalOld, $siteNew], '/blog/post', 1);

        $this->assertSame('/site-new', $match['toUrl']);
    }

    public function testEqualPriorityAndSiteFallsBackToOldestId(): void
    {
        $older = $this->row(['id' => 1, 'matchType' => 'regex', 'fromUrl' => '^/blog/.*$', 'toUrl' => '/older']);
        $newer = $this->row(['id' => 2, 'matchType' => 'regex', 'fromUrl' => '^/blog/.*$', 'toUrl' => '/newer']);

        $match = RedirectMatcher::match([$newer, $older], '/blog/post');

        $this->assertSame('/older', $match['toUrl']);
    }

    // --- Subfolder sites (e.g. /de) ---

    public function testSubfolderSiteMatchesFromUrlWithBasePath(): void
    {
        // Request /de/old-page on a site at /de: Craft passes "old-page" as path info
        $row = $this->row(['fromUrl' => '/de/old-page', 'toUrl' => '/de/new-page']);
        $match = RedirectMatcher::matchRequest([$row], 'old-page', 'de/old-page');

        $this->assertNotNull($match);
        $this->assertSame('/de/new-page', $match['toUrl']);
    }

    public function testSubfolderSiteMatchesSiteRelativeFromUrl(): void
    {
        $match = RedirectMatcher::matchRequest([$this->row()], 'old-page', 'de/old-page');

        $this->assertNotNull($match);
    }

    public function testSiteRelativePathIsTriedFirst(): void
    {
        $relative = $this->row(['id' => 1, 'fromUrl' => '/old-page', 'toUrl' => '/relative']);
        $full = $this->row(['id' => 2, 'fromUrl' => '/de/old-page', 'toUrl' => '/full']);
        $match = RedirectMatcher::matchRequest([$full, $relative], 'old-page', 'de/old-page');

        $this->assertSame('/relative', $match['toUrl']);
    }

    public function testSubfolderSiteMatchesFullUrlWithBasePath(): void
    {
        $row = $this->row(['fromUrl' => 'https://example.com/de/old-page']);
        $match = RedirectMatcher::matchRequest([$row], 'old-page', 'de/old-page', null, 'https://example.com');

        $this->assertNotNull($match);
    }

    public function testSubfolderSiteRegexAnchoredOnBasePath(): void
    {
        $row = $this->row(['fromUrl' => '^/de/blog/(.*)$', 'toUrl' => '/de/articles/$1', 'matchType' => 'regex']);
        $match = RedirectMatcher::matchRequest([$row], 'blog/post', 'de/blog/post');

        $this->assertSame('/de/articles/post', $match['toUrl']);
    }

    public function testMatchRequestWithoutFullPath(): void
    {
        $this->assertNull(RedirectMatcher::matchRequest([$this->row(['fromUrl' => '/de/old-page'])], 'old-page', null));
    }

    // --- Query string handling ---

    public function testAppendQueryString(): void
    {
        $this->assertSame('/new?utm_source=x', RedirectMatcher::appendQueryString('/new', 'utm_source=x'));
    }

    public function testAppendQueryStringMergesWithExisting(): void
    {
        $this->assertSame('/new?a=1&utm_source=x', RedirectMatcher::appendQueryString('/new?a=1', 'utm_source=x'));
    }

    public function testAppendEmptyQueryString(): void
    {
        $this->assertSame('/new', RedirectMatcher::appendQueryString('/new', ''));
    }
}

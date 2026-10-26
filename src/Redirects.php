<?php

namespace recranet\redirects;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\base\Plugin;
use craft\db\Query;
use craft\db\Table;
use craft\events\ElementEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\Application;
use craft\web\UrlManager;
use recranet\redirects\helpers\RedirectMatcher;
use recranet\redirects\models\Settings;
use recranet\redirects\services\RedirectsService;
use yii\base\Event;

/**
 * @property RedirectsService $redirectsService
 */
class Redirects extends Plugin
{
    public string $schemaVersion = '2.2.0';
    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    /**
     * Old element URIs captured before save, keyed by "elementId-siteId".
     */
    private array $oldElementUris = [];

    public static function config(): array
    {
        return [
            'components' => [
                'redirectsService' => RedirectsService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->registerCpRoutes();
        $this->registerRedirectInterception();
        $this->registerAutoRedirects();
        $this->registerPermissions();
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('redirects/_settings', [
            'settings' => $this->getSettings(),
        ]);
    }

    private function registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('redirects', 'Redirects'),
                    'permissions' => [
                        'redirects:manage' => [
                            'label' => Craft::t('redirects', 'Manage redirects'),
                        ],
                    ],
                ];
            }
        );
    }

    /**
     * Automatically create a redirect when an element's URI changes
     * (e.g. when an entry slug is edited).
     */
    private function registerAutoRedirects(): void
    {
        // Regular element saves
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            fn(ElementEvent $event) => $this->stashOldUri($event->element)
        );
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            fn(ElementEvent $event) => $this->handleUriChange($event->element)
        );

        // Slug/URI updates propagated to descendants (e.g. children of a moved/renamed structure entry)
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_UPDATE_SLUG_AND_URI,
            fn(ElementEvent $event) => $this->stashOldUri($event->element)
        );
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI,
            fn(ElementEvent $event) => $this->handleUriChange($event->element)
        );
    }

    private function stashOldUri(ElementInterface $element): void
    {
        if (
            !$this->getSettings()->autoCreateRedirects ||
            !$element->id ||
            !$element->siteId ||
            ElementHelper::isDraftOrRevision($element)
        ) {
            return;
        }

        $oldUri = (new Query())
            ->select(['uri'])
            ->from(Table::ELEMENTS_SITES)
            ->where(['elementId' => $element->id, 'siteId' => $element->siteId])
            ->scalar();

        if ($oldUri) {
            $this->oldElementUris[$element->id . '-' . $element->siteId] = $oldUri;
        }
    }

    private function handleUriChange(ElementInterface $element): void
    {
        if (!$element->id || !$element->siteId || ElementHelper::isDraftOrRevision($element)) {
            return;
        }

        $key = $element->id . '-' . $element->siteId;
        $oldUri = $this->oldElementUris[$key] ?? null;
        unset($this->oldElementUris[$key]);

        if (
            $oldUri === null ||
            $element->uri === null ||
            $oldUri === $element->uri ||
            $oldUri === Element::HOMEPAGE_URI ||
            $element->uri === Element::HOMEPAGE_URI
        ) {
            return;
        }

        try {
            $this->redirectsService->createAutoRedirect(
                $oldUri,
                $element->uri,
                (int)$element->siteId,
                $this->getSettings()->autoRedirectType,
            );
        } catch (\Throwable $e) {
            Craft::warning("Auto redirect creation failed: {$e->getMessage()}", __METHOD__);
        }
    }

    private function registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['redirects'] = 'redirects/redirects/index';
                $event->rules['redirects/import'] = 'redirects/redirects/import';
                $event->rules['redirects/new'] = 'redirects/redirects/edit';
                $event->rules['redirects/<id:\d+>'] = 'redirects/redirects/edit';
            }
        );
    }

    private function registerRedirectInterception(): void
    {
        Event::on(
            Application::class,
            Application::EVENT_INIT,
            function () {
                $request = Craft::$app->getRequest();

                // Skip console requests, CP requests, action requests, live preview
                if (
                    $request->getIsConsoleRequest() ||
                    $request->getIsCpRequest() ||
                    $request->getIsActionRequest() ||
                    $request->getIsLivePreview()
                ) {
                    return;
                }

                try {
                    // getPathInfo() has the site's base path stripped (e.g. "/de" on a subfolder site);
                    // getFullPath() keeps it, so redirects entered as "/de/old-page" match too
                    $siteId = Craft::$app->getSites()->getCurrentSite()->id;
                    $redirect = $this->redirectsService->findRedirectByPath(
                        $request->getPathInfo(),
                        $siteId,
                        $request->getHostInfo(),
                        $request->getFullPath(),
                    );

                    if ($redirect) {
                        // Preserve the incoming query string (e.g. utm parameters)
                        $toUrl = RedirectMatcher::appendQueryString(
                            $redirect->toUrl,
                            $request->getQueryStringWithoutPath(),
                        );

                        Craft::$app->getResponse()->redirect($toUrl, $redirect->type);
                        Craft::$app->end();
                    }
                } catch (\Throwable $e) {
                    Craft::warning("Redirect interception failed: {$e->getMessage()}", __METHOD__);
                }
            }
        );
    }

    public function getCpNavItem(): ?array
    {
        if (!Craft::$app->getUser()->checkPermission('redirects:manage')) {
            return null;
        }

        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('redirects', 'Redirects');
        $item['subnav'] = [
            'redirects' => ['label' => Craft::t('redirects', 'Redirects'), 'url' => 'redirects'],
            'import' => ['label' => Craft::t('redirects', 'Import'), 'url' => 'redirects/import'],
        ];

        return $item;
    }
}

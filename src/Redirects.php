<?php

namespace recranet\redirects;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\Application;
use craft\web\UrlManager;
use recranet\redirects\models\Settings;
use recranet\redirects\services\NotFoundService;
use recranet\redirects\services\RedirectsService;
use yii\base\Event;

/**
 * @property RedirectsService $redirectsService
 * @property NotFoundService $notFoundService
 */
class Redirects extends Plugin
{
    public string $schemaVersion = '1.4.0';
    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'redirectsService' => RedirectsService::class,
                'notFoundService' => NotFoundService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->registerCpRoutes();
        $this->registerRedirectInterception();
        $this->register404Logging();
    }

    private function registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['redirects'] = 'redirects/redirects/index';
                $event->rules['redirects/import'] = 'redirects/redirects/import';
                $event->rules['redirects/404s'] = 'redirects/redirects/404s';
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
                    $path = $request->getPathInfo();
                    $redirect = $this->redirectsService->findRedirectByPath($path);

                    if ($redirect) {
                        $this->redirectsService->recordHit($redirect->id);
                        Craft::$app->getResponse()->redirect($redirect->toUrl, $redirect->type);
                        Craft::$app->end();
                    }
                } catch (\Throwable $e) {
                    Craft::warning("Redirect interception failed: {$e->getMessage()}", __METHOD__);
                }
            }
        );
    }

    private function register404Logging(): void
    {
        if (!$this->getSettings()->logging404Enabled) {
            return;
        }

        Event::on(
            \yii\web\Response::class,
            \yii\web\Response::EVENT_BEFORE_SEND,
            function ($event) {
                $response = $event->sender;
                $request = Craft::$app->getRequest();

                if (
                    $response->statusCode === 404 &&
                    !$request->getIsConsoleRequest() &&
                    !$request->getIsCpRequest() &&
                    !$request->getIsActionRequest()
                ) {
                    try {
                        $path = $request->getPathInfo();
                        $this->notFoundService->logNotFound($path);
                    } catch (\Throwable $e) {
                        Craft::warning("Could not log 404: {$e->getMessage()}", __METHOD__);
                    }
                }
            }
        );
    }

    protected function createSettingsModel(): ?Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('redirects/_settings', [
            'settings' => $this->getSettings(),
        ]);
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('redirects', 'Redirects');
        $item['subnav'] = [
            'redirects' => ['label' => Craft::t('redirects', 'Redirects'), 'url' => 'redirects'],
            'import' => ['label' => Craft::t('redirects', 'Import'), 'url' => 'redirects/import'],
            '404s' => ['label' => Craft::t('redirects', '404 Log'), 'url' => 'redirects/404s'],
        ];

        return $item;
    }
}

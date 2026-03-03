<?php

namespace custom\redirects;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\Application;
use craft\web\UrlManager;
use custom\redirects\services\RedirectsService;
use yii\base\Event;

/**
 * @property RedirectsService $redirectsService
 */
class Redirects extends Plugin
{
    public string $schemaVersion = '1.2.0';
    public bool $hasCpSection = true;

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

                $path = $request->getPathInfo();
                $redirect = $this->redirectsService->findRedirectByPath($path);

                if ($redirect) {
                    $this->redirectsService->recordHit($redirect->id);
                    Craft::$app->getResponse()->redirect($redirect->toUrl, $redirect->type);
                    Craft::$app->end();
                }
            }
        );
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = 'Redirects';
        $item['subnav'] = [
            'redirects' => ['label' => 'Redirects', 'url' => 'redirects'],
            'import' => ['label' => 'Import', 'url' => 'redirects/import'],
        ];

        return $item;
    }
}

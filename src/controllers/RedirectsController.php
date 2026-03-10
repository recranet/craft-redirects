<?php

namespace recranet\redirects\controllers;

use Craft;
use craft\web\Controller;
use craft\web\UploadedFile;
use recranet\redirects\models\RedirectModel;
use recranet\redirects\Redirects;
use yii\web\Response;

class RedirectsController extends Controller
{
    public function actionIndex(): Response
    {
        $siteId = Craft::$app->getRequest()->getQueryParam('siteId');
        $siteId = $siteId !== null && $siteId !== '' ? (int)$siteId : null;

        $redirects = Redirects::getInstance()->redirectsService->getAllRedirects($siteId);

        return $this->renderTemplate('redirects/_index', [
            'redirects' => $redirects,
            'sites' => Craft::$app->getSites()->getAllSites(),
            'selectedSiteId' => $siteId,
        ]);
    }

    public function actionEdit(?int $id = null): Response
    {
        if ($id) {
            $redirect = Redirects::getInstance()->redirectsService->getRedirectById($id);

            if (!$redirect) {
                throw new \yii\web\NotFoundHttpException('Redirect not found');
            }
        } else {
            $redirect = new RedirectModel();

            // Pre-fill fromUrl from query param (e.g. from 404 log)
            $fromUrl = Craft::$app->getRequest()->getQueryParam('fromUrl');
            if ($fromUrl) {
                $redirect->fromUrl = $fromUrl;
            }

            // Pre-fill siteId from query param (e.g. from 404 log)
            $siteId = Craft::$app->getRequest()->getQueryParam('siteId');
            if ($siteId !== null && $siteId !== '') {
                $redirect->siteId = (int)$siteId;
            }
        }

        return $this->renderTemplate('redirects/_edit', [
            'redirect' => $redirect,
            'typeOptions' => RedirectModel::typeOptions(),
            'matchTypeOptions' => RedirectModel::matchTypeOptions(),
            'siteOptions' => RedirectModel::siteOptions(),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $model = new RedirectModel();
        $model->id = $request->getBodyParam('id') ?: null;
        $model->siteId = $request->getBodyParam('siteId') !== '' ? $request->getBodyParam('siteId') : null;
        if ($model->siteId !== null) {
            $model->siteId = (int)$model->siteId;
        }
        $model->fromUrl = $request->getBodyParam('fromUrl');
        $model->toUrl = $request->getBodyParam('toUrl');
        $model->type = (int)$request->getBodyParam('type', 301);
        $model->matchType = $request->getBodyParam('matchType', 'exact');
        $model->label = $request->getBodyParam('label');
        $model->notes = $request->getBodyParam('notes');
        $model->enabled = (bool)$request->getBodyParam('enabled', true);

        $service = Redirects::getInstance()->redirectsService;

        if (!$service->saveRedirect($model)) {
            Craft::$app->getSession()->setError(Craft::t('redirects', 'Could not save redirect.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $model,
                'typeOptions' => RedirectModel::typeOptions(),
                'matchTypeOptions' => RedirectModel::matchTypeOptions(),
                'siteOptions' => RedirectModel::siteOptions(),
            ]);

            return null;
        }

        // Chain detection warning
        $chainWarning = $service->detectChain($model);
        if ($chainWarning) {
            Craft::$app->getSession()->setNotice(Craft::t('redirects', 'Redirect saved.') . ' ' . Craft::t('redirects', 'Warning:') . " $chainWarning");
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('redirects', 'Redirect saved.'));
        }

        return $this->redirectToPostedUrl($model);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Redirects::getInstance()->redirectsService->deleteRedirectById((int)$id);

        return $this->asJson(['success' => true]);
    }

    public function actionToggle(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        $service = Redirects::getInstance()->redirectsService;
        $redirect = $service->getRedirectById($id);

        if (!$redirect) {
            return $this->asJson(['success' => false, 'error' => 'Redirect not found']);
        }

        $redirect->enabled = !$redirect->enabled;
        $service->saveRedirect($redirect);

        return $this->asJson(['success' => true, 'enabled' => $redirect->enabled]);
    }

    // --- Bulk actions ---

    public function actionBulkEnable(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ids = Craft::$app->getRequest()->getRequiredBodyParam('ids');
        Redirects::getInstance()->redirectsService->bulkSetEnabled((array)$ids, true);

        return $this->asJson(['success' => true]);
    }

    public function actionBulkDisable(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ids = Craft::$app->getRequest()->getRequiredBodyParam('ids');
        Redirects::getInstance()->redirectsService->bulkSetEnabled((array)$ids, false);

        return $this->asJson(['success' => true]);
    }

    public function actionBulkDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ids = Craft::$app->getRequest()->getRequiredBodyParam('ids');
        Redirects::getInstance()->redirectsService->bulkDelete((array)$ids);

        return $this->asJson(['success' => true]);
    }

    public function actionBulkChangeType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $ids = $request->getRequiredBodyParam('ids');
        $type = (int)$request->getRequiredBodyParam('type');

        Redirects::getInstance()->redirectsService->bulkSetType((array)$ids, $type);

        return $this->asJson(['success' => true]);
    }

    // --- Export ---

    public function actionExport(): Response
    {
        $siteId = Craft::$app->getRequest()->getQueryParam('siteId');
        $siteId = $siteId !== null && $siteId !== '' ? (int)$siteId : null;

        $csv = Redirects::getInstance()->redirectsService->exportCsv($siteId);

        $response = Craft::$app->getResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="redirects-export.csv"');
        $response->content = $csv;

        return $response;
    }

    // --- Import ---

    public function actionImport(): Response
    {
        return $this->renderTemplate('redirects/_import', [
            'siteOptions' => RedirectModel::siteOptions(),
        ]);
    }

    public function actionDownloadExampleCsv(): Response
    {
        $csv = "from,to,type,site,label,notes\n";
        $csv .= "/old-page,/new-page,301,,Livegang,Homepage moved\n";
        $csv .= "/blog/old-post,/articles/new-post,301,default,Redesign,Blog restructured\n";
        $csv .= "/promo,https://example.com/campaign,302,,Campagne,Temporary promo redirect\n";

        $response = Craft::$app->getResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="redirects-example.csv"');
        $response->content = $csv;

        return $response;
    }

    public function actionUploadCsv(): ?Response
    {
        $this->requirePostRequest();

        $file = UploadedFile::getInstanceByName('csvFile');

        if (!$file) {
            Craft::$app->getSession()->setError(Craft::t('redirects', 'No file uploaded.'));
            return $this->renderTemplate('redirects/_import', [
                'siteOptions' => RedirectModel::siteOptions(),
            ]);
        }

        if (!in_array($file->getExtension(), ['csv', 'txt'])) {
            Craft::$app->getSession()->setError(Craft::t('redirects', 'Please upload a CSV file.'));
            return $this->renderTemplate('redirects/_import', [
                'siteOptions' => RedirectModel::siteOptions(),
            ]);
        }

        $handle = fopen($file->tempName, 'r');
        if (!$handle) {
            Craft::$app->getSession()->setError(Craft::t('redirects', 'Could not read file.'));
            return $this->renderTemplate('redirects/_import', [
                'siteOptions' => RedirectModel::siteOptions(),
            ]);
        }

        $headers = fgetcsv($handle, 0, ',', '"');
        if (!$headers) {
            fclose($handle);
            Craft::$app->getSession()->setError(Craft::t('redirects', 'CSV file is empty or invalid.'));
            return $this->renderTemplate('redirects/_import', [
                'siteOptions' => RedirectModel::siteOptions(),
            ]);
        }

        // Read up to 5 preview rows
        $previewRows = [];
        for ($i = 0; $i < 5; $i++) {
            $row = fgetcsv($handle, 0, ',', '"');
            if ($row === false) {
                break;
            }
            $previewRows[] = $row;
        }
        fclose($handle);

        // Copy to temp location
        $tempFilename = uniqid('redirect_import_', true) . '.csv';
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
        copy($file->tempName, $tempPath);

        return $this->renderTemplate('redirects/_import_mapping', [
            'headers' => $headers,
            'previewRows' => $previewRows,
            'tempFilename' => $tempFilename,
            'siteOptions' => RedirectModel::siteOptions(),
        ]);
    }

    public function actionProcessImport(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $tempFilename = $request->getRequiredBodyParam('tempFilename');
        $mapping = $request->getRequiredBodyParam('mapping');
        $defaultSiteId = $request->getBodyParam('defaultSiteId');
        $defaultSiteId = $defaultSiteId !== null && $defaultSiteId !== '' ? (int)$defaultSiteId : null;

        // Validate temp filename to prevent directory traversal
        if (preg_match('/[\/\\\\]/', $tempFilename)) {
            Craft::$app->getSession()->setError(Craft::t('redirects', 'Invalid file reference.'));
            return $this->renderTemplate('redirects/_import', [
                'siteOptions' => RedirectModel::siteOptions(),
            ]);
        }

        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;

        if (!file_exists($tempPath)) {
            Craft::$app->getSession()->setError(Craft::t('redirects', 'Temporary file not found. Please re-upload.'));
            return $this->renderTemplate('redirects/_import', [
                'siteOptions' => RedirectModel::siteOptions(),
            ]);
        }

        $handle = fopen($tempPath, 'r');
        $headers = fgetcsv($handle, 0, ',', '"'); // Skip header row

        $rows = [];
        while (($csvRow = fgetcsv($handle, 0, ',', '"')) !== false) {
            $row = [];
            foreach ($mapping as $colIndex => $field) {
                if ($field !== 'skip' && isset($csvRow[$colIndex])) {
                    $row[$field] = trim($csvRow[$colIndex]);
                }
            }

            // Only import rows that have at least fromUrl
            if (!empty($row['fromUrl'])) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        // Clean up temp file
        @unlink($tempPath);

        $results = Redirects::getInstance()->redirectsService->importRedirects($rows, $defaultSiteId);

        return $this->renderTemplate('redirects/_import_results', [
            'imported' => $results['imported'],
            'total' => $results['total'],
            'errors' => $results['errors'],
        ]);
    }

    // --- 404 Log ---

    public function action404s(): Response
    {
        $siteId = Craft::$app->getRequest()->getQueryParam('siteId');
        $siteId = $siteId !== null && $siteId !== '' ? (int)$siteId : null;

        $notFounds = Redirects::getInstance()->notFoundService->getAllNotFounds($siteId);

        return $this->renderTemplate('redirects/_404s', [
            'notFounds' => $notFounds,
            'sites' => Craft::$app->getSites()->getAllSites(),
            'selectedSiteId' => $siteId,
        ]);
    }

    public function actionDelete404(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');
        Redirects::getInstance()->notFoundService->deleteNotFoundById((int)$id);

        return $this->asJson(['success' => true]);
    }

    public function actionDeleteAll404s(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');
        $siteId = $siteId !== null && $siteId !== '' ? (int)$siteId : null;

        Redirects::getInstance()->notFoundService->deleteAllNotFounds($siteId);

        return $this->asJson(['success' => true]);
    }
}

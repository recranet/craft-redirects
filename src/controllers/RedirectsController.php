<?php

namespace custom\redirects\controllers;

use Craft;
use craft\web\Controller;
use craft\web\UploadedFile;
use custom\redirects\models\RedirectModel;
use custom\redirects\Redirects;
use yii\web\Response;

class RedirectsController extends Controller
{
    public function actionIndex(): Response
    {
        $redirects = Redirects::getInstance()->redirectsService->getAllRedirects();

        return $this->renderTemplate('redirects/_index', [
            'redirects' => $redirects,
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
        }

        return $this->renderTemplate('redirects/_edit', [
            'redirect' => $redirect,
            'typeOptions' => RedirectModel::typeOptions(),
            'matchTypeOptions' => RedirectModel::matchTypeOptions(),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $model = new RedirectModel();
        $model->id = $request->getBodyParam('id') ?: null;
        $model->fromUrl = $request->getBodyParam('fromUrl');
        $model->toUrl = $request->getBodyParam('toUrl');
        $model->type = (int)$request->getBodyParam('type', 301);
        $model->matchType = $request->getBodyParam('matchType', 'exact');
        $model->label = $request->getBodyParam('label');
        $model->notes = $request->getBodyParam('notes');
        $model->enabled = (bool)$request->getBodyParam('enabled', true);

        $service = Redirects::getInstance()->redirectsService;

        if (!$service->saveRedirect($model)) {
            Craft::$app->getSession()->setError('Could not save redirect.');

            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $model,
                'typeOptions' => RedirectModel::typeOptions(),
                'matchTypeOptions' => RedirectModel::matchTypeOptions(),
            ]);

            return null;
        }

        // Chain detection warning
        $chainWarning = $service->detectChain($model);
        if ($chainWarning) {
            Craft::$app->getSession()->setNotice("Redirect saved. Warning: $chainWarning");
        } else {
            Craft::$app->getSession()->setNotice('Redirect saved.');
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

    // --- Export ---

    public function actionExport(): Response
    {
        $csv = Redirects::getInstance()->redirectsService->exportCsv();

        $response = Craft::$app->getResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="redirects-export.csv"');
        $response->content = $csv;

        return $response;
    }

    // --- Import ---

    public function actionImport(): Response
    {
        return $this->renderTemplate('redirects/_import');
    }

    public function actionDownloadExampleCsv(): Response
    {
        $csv = "from,to,type,label,notes\n";
        $csv .= "/old-page,/new-page,301,Livegang,Homepage moved\n";
        $csv .= "/blog/old-post,/articles/new-post,301,Redesign,Blog restructured\n";
        $csv .= "/promo,https://example.com/campaign,302,Campagne,Temporary promo redirect\n";

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
            Craft::$app->getSession()->setError('No file uploaded.');
            return $this->renderTemplate('redirects/_import');
        }

        if (!in_array($file->getExtension(), ['csv', 'txt'])) {
            Craft::$app->getSession()->setError('Please upload a CSV file.');
            return $this->renderTemplate('redirects/_import');
        }

        $handle = fopen($file->tempName, 'r');
        if (!$handle) {
            Craft::$app->getSession()->setError('Could not read file.');
            return $this->renderTemplate('redirects/_import');
        }

        $headers = fgetcsv($handle, 0, ',', '"');
        if (!$headers) {
            fclose($handle);
            Craft::$app->getSession()->setError('CSV file is empty or invalid.');
            return $this->renderTemplate('redirects/_import');
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
        ]);
    }

    public function actionProcessImport(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $tempFilename = $request->getRequiredBodyParam('tempFilename');
        $mapping = $request->getRequiredBodyParam('mapping');

        // Validate temp filename to prevent directory traversal
        if (preg_match('/[\/\\\\]/', $tempFilename)) {
            Craft::$app->getSession()->setError('Invalid file reference.');
            return $this->renderTemplate('redirects/_import');
        }

        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;

        if (!file_exists($tempPath)) {
            Craft::$app->getSession()->setError('Temporary file not found. Please re-upload.');
            return $this->renderTemplate('redirects/_import');
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

        $results = Redirects::getInstance()->redirectsService->importRedirects($rows);

        return $this->renderTemplate('redirects/_import_results', [
            'imported' => $results['imported'],
            'total' => $results['total'],
            'errors' => $results['errors'],
        ]);
    }

    // --- 404 Log ---

    public function action404s(): Response
    {
        $notFounds = Redirects::getInstance()->notFoundService->getAllNotFounds();

        return $this->renderTemplate('redirects/_404s', [
            'notFounds' => $notFounds,
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

        Redirects::getInstance()->notFoundService->deleteAllNotFounds();

        return $this->asJson(['success' => true]);
    }
}

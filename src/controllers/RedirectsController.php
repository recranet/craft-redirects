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
        }

        return $this->renderTemplate('redirects/_edit', [
            'redirect' => $redirect,
            'typeOptions' => RedirectModel::typeOptions(),
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
        $model->label = $request->getBodyParam('label');
        $model->notes = $request->getBodyParam('notes');
        $model->enabled = (bool)$request->getBodyParam('enabled', true);

        if (!Redirects::getInstance()->redirectsService->saveRedirect($model)) {
            Craft::$app->getSession()->setError('Could not save redirect.');

            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $model,
                'typeOptions' => RedirectModel::typeOptions(),
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice('Redirect saved.');

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

    public function actionImport(): Response
    {
        return $this->renderTemplate('redirects/_import');
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
}

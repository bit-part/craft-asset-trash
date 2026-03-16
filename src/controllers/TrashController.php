<?php

namespace bitpart\assettrash\controllers;

use bitpart\assettrash\AssetTrash;
use Craft;
use craft\elements\User;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TrashController extends Controller
{
    private const PAGE_SIZE = 50;
    /**
     * Display the trash item list.
     */
    public function actionIndex(): Response
    {
        $this->requirePermission('assetTrash-viewTrash');

        $request = Craft::$app->getRequest();
        $volumeId = $request->getQueryParam('volumeId');
        $page = (int)$request->getQueryParam('page', 1);
        $limit = self::PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $service = AssetTrash::getInstance()->trash;

        $items = $service->getTrashItems(
            $volumeId ? (int)$volumeId : null,
            $offset,
            $limit
        );
        $total = $service->getTotalTrashItems(
            $volumeId ? (int)$volumeId : null
        );

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        // Pre-load deleted-by users to avoid N+1 queries in template
        $userIds = array_filter(array_unique(array_map(
            fn($item) => $item->deletedByUserId,
            $items
        )));
        $usersById = [];
        if (!empty($userIds)) {
            $users = User::find()->id($userIds)->status(null)->all();
            foreach ($users as $user) {
                $usersById[$user->id] = $user;
            }
        }

        return $this->renderTemplate('asset-trash/_index', [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'volumeId' => $volumeId,
            'volumes' => $volumes,
            'usersById' => $usersById,
        ]);
    }

    /**
     * Get detail info for a trash item (for modal).
     */
    public function actionDetail(): Response
    {
        $this->requirePermission('assetTrash-viewTrash');

        $id = Craft::$app->getRequest()->getRequiredParam('id');
        $item = AssetTrash::getInstance()->trash->getTrashItemById((int)$id);

        if ($item === null) {
            throw new NotFoundHttpException(Craft::t('asset-trash', 'Trash item not found.'));
        }

        // Resolve volume name
        $volume = Craft::$app->getVolumes()->getVolumeById($item->volumeId);
        $volumeName = $volume?->name ?? Craft::t('asset-trash', '(deleted volume)');

        // Resolve deleted-by user
        $deletedByUser = null;
        if ($item->deletedByUserId) {
            $deletedByUser = Craft::$app->getUsers()->getUserById($item->deletedByUserId);
        }

        return $this->renderTemplate('asset-trash/_detail', [
            'item' => $item,
            'volumeName' => $volumeName,
            'deletedByUser' => $deletedByUser,
        ]);
    }

    /**
     * Restore a single trash item.
     */
    public function actionRestore(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('assetTrash-restoreAssets');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');

        if (AssetTrash::getInstance()->trash->restoreItem($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('asset-trash', 'Asset restored.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('asset-trash', 'Could not restore the asset.'));
        }

        return $this->redirectToPostedUrl() ?? $this->redirect('asset-trash');
    }

    /**
     * Permanently delete a single trash item.
     */
    public function actionPermanentDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('assetTrash-permanentlyDelete');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');

        if (AssetTrash::getInstance()->trash->permanentlyDelete($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('asset-trash', 'Asset permanently deleted.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('asset-trash', 'Could not delete the asset.'));
        }

        return $this->redirectToPostedUrl() ?? $this->redirect('asset-trash');
    }

    /**
     * Permanently delete multiple trash items.
     */
    public function actionBulkDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('assetTrash-permanentlyDelete');

        $ids = Craft::$app->getRequest()->getRequiredBodyParam('ids');
        if (!is_array($ids)) {
            throw new BadRequestHttpException('ids must be an array.');
        }
        $ids = array_map('intval', $ids);

        $total = count($ids);
        $count = 0;
        foreach ($ids as $id) {
            if (AssetTrash::getInstance()->trash->permanentlyDelete($id)) {
                $count++;
            }
        }

        if ($count === $total) {
            Craft::$app->getSession()->setNotice(
                Craft::t('asset-trash', '{count} asset(s) permanently deleted.', ['count' => $count])
            );
        } else {
            Craft::$app->getSession()->setError(
                Craft::t('asset-trash', '{count} of {total} asset(s) permanently deleted.', ['count' => $count, 'total' => $total])
            );
        }

        return $this->redirectToPostedUrl() ?? $this->redirect('asset-trash');
    }

    /**
     * Restore multiple trash items.
     */
    public function actionBulkRestore(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('assetTrash-restoreAssets');

        $ids = Craft::$app->getRequest()->getRequiredBodyParam('ids');
        if (!is_array($ids)) {
            throw new BadRequestHttpException('ids must be an array.');
        }
        $ids = array_map('intval', $ids);

        $total = count($ids);
        $count = 0;
        foreach ($ids as $id) {
            if (AssetTrash::getInstance()->trash->restoreItem($id)) {
                $count++;
            }
        }

        if ($count === $total) {
            Craft::$app->getSession()->setNotice(
                Craft::t('asset-trash', '{count} asset(s) restored.', ['count' => $count])
            );
        } else {
            Craft::$app->getSession()->setError(
                Craft::t('asset-trash', '{count} of {total} asset(s) restored.', ['count' => $count, 'total' => $total])
            );
        }

        return $this->redirectToPostedUrl() ?? $this->redirect('asset-trash');
    }

    /**
     * Empty the entire trash.
     */
    public function actionEmptyTrash(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('assetTrash-emptyTrash');

        $volumeId = Craft::$app->getRequest()->getBodyParam('volumeId');
        $count = AssetTrash::getInstance()->trash->emptyTrash(
            $volumeId ? (int)$volumeId : null
        );

        Craft::$app->getSession()->setNotice(
            Craft::t('asset-trash', '{count} asset(s) permanently deleted.', ['count' => $count])
        );

        return $this->redirectToPostedUrl() ?? $this->redirect('asset-trash');
    }
}

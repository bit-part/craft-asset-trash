<?php

namespace bitpart\assettrash;

use bitpart\assettrash\models\Settings;
use bitpart\assettrash\services\TrashService;
use Craft;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\events\DeleteElementEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Elements;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;

/**
 * Asset Trash plugin for Craft CMS 5.
 *
 * Intercepts asset deletion, moves files to a trash directory,
 * and provides a CP interface for restoration or permanent deletion.
 *
 * @property TrashService $trash
 */
class AssetTrash extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSection = false;
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'trash' => TrashService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->registerEventHandlers();
        $this->registerAssetSidebarLink();
    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('asset-trash/_settings', [
            'settings' => $this->getSettings(),
        ]);
    }

    private function registerAssetSidebarLink(): void
    {
        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest() || !$request->getIsCpRequest() || $request->getIsAjax()) {
            return;
        }

        $label = json_encode(Craft::t('asset-trash', 'Asset Trash'));

        Craft::$app->getView()->registerJs(<<<JS
(function() {
    var el = document.querySelector('.elements[data-type="craft\\\\elements\\\\Asset"]');
    if (!el) return;
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    var nav = sidebar.querySelector('nav');
    if (!nav) return;
    var ul = document.createElement('ul');
    ul.style.marginTop = '12px';
    ul.style.borderTop = '1px solid var(--gray-200, #e5e7eb)';
    ul.style.paddingTop = '12px';
    var li = document.createElement('li');
    var a = document.createElement('a');
    a.href = Craft.getCpUrl('asset-trash');
    a.textContent = {$label};
    li.appendChild(a);
    ul.appendChild(li);
    nav.appendChild(ul);
})();
JS, View::POS_READY);
    }

    private function registerEventHandlers(): void
    {
        // Intercept asset deletion
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (DeleteElementEvent $event) {
                if ($event->element instanceof Asset && !$event->element->getIsDraft() && !$event->element->getIsRevision()) {
                    $this->trash->trashAsset($event->element);
                }
            }
        );

        // GC: auto-purge expired items
        Event::on(
            Gc::class,
            Gc::EVENT_RUN,
            function () {
                $settings = $this->getSettings();
                if ($settings->autoPurge && $settings->retentionDays > 0) {
                    $this->trash->purgeExpired();
                }
            }
        );

        // Register CP URL rules
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['asset-trash'] = 'asset-trash/trash/index';
                $event->rules['asset-trash/detail'] = 'asset-trash/trash/detail';
            }
        );

        // Register permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('asset-trash', 'Asset Trash'),
                    'permissions' => [
                        'assetTrash-viewTrash' => [
                            'label' => Craft::t('asset-trash', 'View trash'),
                            'nested' => [
                                'assetTrash-restoreAssets' => [
                                    'label' => Craft::t('asset-trash', 'Restore assets'),
                                ],
                                'assetTrash-permanentlyDelete' => [
                                    'label' => Craft::t('asset-trash', 'Permanently delete assets'),
                                ],
                                'assetTrash-emptyTrash' => [
                                    'label' => Craft::t('asset-trash', 'Empty trash'),
                                ],
                            ],
                        ],
                    ],
                ];
            }
        );
    }
}

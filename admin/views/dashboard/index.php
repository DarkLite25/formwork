<?php $this->layout('admin') ?>
<div class="row">
    <div class="col-m-1-3">
        <div class="component">
            <span class="h4"><?= $this->translate('admin.dashboard.welcome') ?></span>
        </div>
        <div class="component">
            <h3 class="caption"><?= $this->translate('admin.dashboard.quick-actions') ?></h3>
<?php
            if ($admin->user()->permissions()->has('pages.create')):
?>
            <button type="button" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('admin.pages.new-page') ?></button>
<?php
            endif;

            if ($admin->user()->permissions()->has('cache.clear')):
?>
            <button type="button" data-command="clear-cache"><?= $this->icon('cache-clear') ?> <?= $this->translate('admin.cache.clear') ?></button>
<?php
            endif;

            if ($admin->user()->permissions()->has('backup')):
?>
            <button type="button" data-command="make-backup"><?= $this->icon('clock-rotate-left') ?> <?= $this->translate('admin.backup.backup') ?></button>
<?php
            endif;

            if ($admin->user()->permissions()->has('options.updates')):
?>
            <a class="button" role="button" href="<?= $admin->uri('/options/updates/'); ?>"><?= $this->icon('arrows-rotate-clockwise') ?> <?= $this->translate('admin.updates.check') ?></a>
<?php
            endif;
?>
        </div>
    </div>
    <div class="col-m-2-3">
        <div class="component">
            <div class="row">
                <div class="col-xs-1-2"><h3 class="caption"><?= $this->translate('admin.dashboard.statistics') ?></h3></div>
                <div class="col-xs-1-2">
                    <div class="ct-legend ct-legend-right">
                        <span class="ct-legend-label ct-series-a"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('admin.dashboard.statistics.visits') ?></span>
                        <span class="ct-legend-label ct-series-b"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('admin.dashboard.statistics.unique-visitors') ?></span>
                    </div>
                </div>
            </div>
            <div class="ct-chart" data-chart-data="<?= $this->escapeAttr($statistics); ?>"></div>
        </div>
    </div>
</div>
<div class="component">
<h3 class="caption"><?= $this->translate('admin.dashboard.last-modified-pages') ?></h3>
    <?= $lastModifiedPages ?>
</div>

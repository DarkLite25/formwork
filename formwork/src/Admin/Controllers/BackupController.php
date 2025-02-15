<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Backupper;
use Formwork\Exceptions\TranslatedException;
use Formwork\Formwork;
use Formwork\Response\FileResponse;
use Formwork\Response\JSONResponse;
use Formwork\Response\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use RuntimeException;

class BackupController extends AbstractController
{
    /**
     * Backup@make action
     */
    public function make(): JSONResponse
    {
        $this->ensurePermission('backup.make');
        $backupper = new Backupper();
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            return JSONResponse::error($this->admin()->translate('admin.backup.error.cannot-make', $e->getTranslatedMessage()), 500);
        }
        $filename = basename($file);
        return JSONResponse::success($this->admin()->translate('admin.backup.ready'), 200, [
            'filename' => $filename,
            'uri'      => $this->admin()->uri('/backup/download/' . urlencode(base64_encode($filename)) . '/')
        ]);
    }

    /**
     * Backup@download action
     */
    public function download(RouteParams $params): Response
    {
        $this->ensurePermission('backup.download');
        $file = Formwork::instance()->config()->get('backup.path') . basename(base64_decode($params->get('backup')));
        try {
            if (FileSystem::isFile($file, false)) {
                return new FileResponse($file, true);
            }
            throw new RuntimeException($this->admin()->translate('admin.backup.error.cannot-download.invalid-filename'));
        } catch (TranslatedException $e) {
            $this->admin()->notify($this->admin()->translate('admin.backup.error.cannot-download', $e->getTranslatedMessage()), 'error');
            return $this->admin()->redirectToReferer(302, '/dashboard/');
        }
    }
}

<?php

namespace common\components;

use common\models\Location;
use Endroid\QrCode\QrCode;
use yii\base\BaseObject;
use yii\helpers\FileHelper;
use yii\helpers\Url;

class QrCodeService extends BaseObject
{
    public $basePath;
    public $baseUrl;

    public function generateLocationQr(Location $location)
    {
        $directory = $this->basePath ?: '@frontend/web/uploads/qr';
        $directory = \Yii::getAlias($directory);

        FileHelper::createDirectory($directory, 0775, true);

        $payload = Url::to(['/report/scan', 'locationId' => $location->id], true);
        $qrCode = new QrCode($payload);
        $qrCode->setSize(320);
        $qrCode->setMargin(10);

        $fileName = 'location-' . $location->id . '.png';
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($filePath, $qrCode->writeString());

        return [
            'path' => $filePath,
            'url' => rtrim($this->baseUrl ?: '/uploads/qr', '/') . '/' . $fileName,
            'payload' => $payload,
        ];
    }
}
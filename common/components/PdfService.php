<?php

namespace common\components;

use Mpdf\Mpdf;
use yii\base\BaseObject;
use yii\helpers\FileHelper;

class PdfService extends BaseObject
{
    public function renderHtmlToFile($html, $filePath, array $config = [])
    {
        FileHelper::createDirectory(dirname($filePath), 0775, true);

        $mpdf = new Mpdf(array_merge([
            'tempDir' => sys_get_temp_dir(),
            'mode' => 'utf-8',
            'format' => 'A4',
        ], $config));

        $mpdf->WriteHTML($html);
        $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

        return $filePath;
    }
}
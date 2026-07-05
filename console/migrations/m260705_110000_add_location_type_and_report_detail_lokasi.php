<?php

use common\models\Location;
use yii\db\Migration;

class m260705_110000_add_location_type_and_report_detail_lokasi extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%location}}', 'jenis_lokasi', $this->smallInteger()->notNull()->defaultValue(Location::JENIS_LOKASI_INTERNAL)->after('description'));
        $this->createIndex('idx-location-jenis_lokasi', '{{%location}}', 'jenis_lokasi');

        $this->addColumn('{{%report}}', 'detail_lokasi', $this->text()->null()->after('location_id'));

        $now = time();
        $rows = [
            [
                'code' => Location::CODE_INTERNAL_LANTAI_1,
                'name' => 'Gedung Utama Lantai 1',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_INTERNAL_LANTAI_2,
                'name' => 'Gedung Utama Lantai 2',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_INTERNAL_LANTAI_3,
                'name' => 'Gedung Utama Lantai 3',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_INTERNAL_ROOFTOP,
                'name' => 'Rooftop',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_INTERNAL_GEDUNG_LAMA,
                'name' => 'Gedung Lama (Pelatihan)',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_INTERNAL_HALAMAN_APEL,
                'name' => 'Halaman apel',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_INTERNAL_LAINNYA,
                'name' => 'Lainnya',
                'description' => 'Referensi lokasi internal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_INTERNAL,
            ],
            [
                'code' => Location::CODE_EKSTERNAL,
                'name' => 'Eksternal/Luar Instansi',
                'description' => 'Referensi lokasi eksternal instansi',
                'jenis_lokasi' => Location::JENIS_LOKASI_EKSTERNAL,
            ],
        ];

        foreach ($rows as $row) {
            $code = $row['code'];
            $this->upsert('{{%location}}',
                [
                    'code' => $code,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'jenis_lokasi' => $row['jenis_lokasi'],
                    'qr_token' => 'seed-' . strtolower($code),
                    'is_active' => 1,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'jenis_lokasi' => $row['jenis_lokasi'],
                    'is_active' => 1,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%report}}', 'detail_lokasi');

        $this->dropIndex('idx-location-jenis_lokasi', '{{%location}}');
        $this->dropColumn('{{%location}}', 'jenis_lokasi');
    }
}

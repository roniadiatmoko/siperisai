<?php

namespace backend\models;

use yii\base\Model;

class ReportAnalyticsFilterForm extends Model
{
    public const PERIOD_7D = '7d';
    public const PERIOD_30D = '30d';
    public const PERIOD_THIS_MONTH = 'this_month';
    public const PERIOD_CUSTOM = 'custom';

    public const DATE_BASIS_INCIDENT = 'incident_time';
    public const DATE_BASIS_CREATED = 'created_at';

    public $period = self::PERIOD_30D;
    public $date_basis = self::DATE_BASIS_INCIDENT;
    public $start_date;
    public $end_date;

    public function rules()
    {
        return [
            [['period', 'date_basis'], 'required'],
            [['start_date', 'end_date'], 'safe'],
            [['period'], 'in', 'range' => array_keys(self::periodOptions())],
            [['date_basis'], 'in', 'range' => array_keys(self::dateBasisOptions())],
            [['start_date', 'end_date'], 'required', 'when' => function (self $model) {
                return $model->period === self::PERIOD_CUSTOM;
            }],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'period' => 'Periode',
            'date_basis' => 'Acuan tanggal',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
        ];
    }

    public function validateCustomRange($attribute, $params)
    {
        if ($this->period !== self::PERIOD_CUSTOM || $this->hasErrors()) {
            return;
        }

        $start = strtotime((string) $this->start_date . ' 00:00:00');
        $end = strtotime((string) $this->end_date . ' 23:59:59');

        if ($start === false || $end === false) {
            $this->addError($attribute, 'Format tanggal custom tidak valid.');
            return;
        }

        if ($start > $end) {
            $this->addError('end_date', 'Tanggal selesai harus lebih besar atau sama dengan tanggal mulai.');
            return;
        }

        if (($end - $start) > 366 * 86400) {
            $this->addError('end_date', 'Maksimal rentang custom adalah 366 hari.');
        }
    }

    public function beforeValidate()
    {
        if ($this->period === self::PERIOD_CUSTOM) {
            if (empty($this->start_date) || empty($this->end_date)) {
                $today = date('Y-m-d');
                $this->end_date = $this->end_date ?: $today;
                $this->start_date = $this->start_date ?: date('Y-m-d', strtotime('-6 days'));
            }
        }

        return parent::beforeValidate();
    }

    public function afterValidate()
    {
        parent::afterValidate();
        $this->validateCustomRange('start_date', []);
    }

    public static function periodOptions()
    {
        return [
            self::PERIOD_7D => '7 Hari Terakhir',
            self::PERIOD_30D => '30 Hari Terakhir',
            self::PERIOD_THIS_MONTH => 'Bulan Ini',
            self::PERIOD_CUSTOM => 'Custom',
        ];
    }

    public static function dateBasisOptions()
    {
        return [
            self::DATE_BASIS_INCIDENT => 'Waktu kejadian',
            self::DATE_BASIS_CREATED => 'Waktu pelaporan',
        ];
    }

    public function getRangeTimestamps()
    {
        $now = time();

        switch ($this->period) {
            case self::PERIOD_7D:
                $start = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days', $now)));
                $end = strtotime(date('Y-m-d 23:59:59', $now));
                break;
            case self::PERIOD_THIS_MONTH:
                $start = strtotime(date('Y-m-01 00:00:00', $now));
                $end = strtotime(date('Y-m-d 23:59:59', $now));
                break;
            case self::PERIOD_CUSTOM:
                $start = strtotime((string) $this->start_date . ' 00:00:00');
                $end = strtotime((string) $this->end_date . ' 23:59:59');
                break;
            case self::PERIOD_30D:
            default:
                $start = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days', $now)));
                $end = strtotime(date('Y-m-d 23:59:59', $now));
                break;
        }

        return [
            'start' => (int) $start,
            'end' => (int) $end,
        ];
    }

    public function getDateColumn()
    {
        return $this->date_basis === self::DATE_BASIS_CREATED
            ? self::DATE_BASIS_CREATED
            : self::DATE_BASIS_INCIDENT;
    }

    public function toQueryParams()
    {
        $formName = $this->formName();

        return [
            $formName => [
                'period' => $this->period,
                'date_basis' => $this->date_basis,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ],
        ];
    }
}

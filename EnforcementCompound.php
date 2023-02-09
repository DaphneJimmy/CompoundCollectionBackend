<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

class EnforcementCompound extends \yii\db\ActiveRecord
{
    public $enforcement_user_name;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{enforcement_compound}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class'              => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => "WAITTING_UPLOAD_IMAGES"],
            ['status', 'in', 'range' => ["WAITTING_UPLOAD_IMAGES", "PAID", "UNPAID", "CANCELLED"]],
            ['enforcement_user_id', 'exist', 'targetClass' => EnforcementUser::class, 'targetAttribute' => ['enforcement_user_id' => 'id']],
            ['witness_user_id', 'exist', 'targetClass' => EnforcementUser::class, 'targetAttribute' => ['witness_user_id' => 'id']],
            [['compound_number'], 'unique', 'message' => '{attribute}  already exist.'],
            [['del', 'vehicle_owner_name', 'vehicle_owner_ic_number', 'vehicle_owner_address', 'parking_id', 'car_color_id',
                'region_id', 'traffic_laws_id', 'tax_text', 'car_type_text', 'remark', 'modified_by_user_id', 'compound_money',
                'discount_money', 'payment_money', 'actual_payment_money', 'cancelled_info', 'approved_by', "place_of_offence", "coupon_expired_time", "car_type_id", "status", "order_money", "compound_end_time", "compound_start_time"], 'safe'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                   => 'No.',
            'created_at'           => 'Created By',
            'updated_at'           => 'Modified By',
            'compound_number'      => 'Compound No',
            'traffic_laws_text'    => 'Offence',
            'license_plate_number' => 'Vehicle No',
            'compound_date'        => 'Compound Date',
//            'coupon_expired_time'        => 'Coupon Expired Time',
            //            'place_of_offence'        => 'Place Of Offence',
            'status'               => 'Status',
        ];
    }
    public function merchantAttributeLabels()
    {
        return [
            'CheckboxColumn'       => 'CheckboxColumn',
            'created_at'           => 'Created By',
            'compound_number'      => 'Compound No',
            'license_plate_number' => 'Vehicle No',
            'traffic_laws_text'    => 'Offence',
            'compound_money'       => 'Amount',
            'discount_money'       => 'Discount',
            'payment_money'        => 'Final Amount',
        ];
    }

    public function compoundCollectionAttributeLabels()
    {
        return [
            'date'                                  => 'Date',
            'total_DBKKH_compound_collection_order' => 'Total DBKKH Compound Collection Order',
            'total_DBKKH_compound_collection'       => 'Total DBKKH Compound Collection',
            'total_DBKK_compound_collection_order'  => 'Total DBKK Compound Collection Order',
            'total_DBKK_compound_collection'        => 'Total DBKK Compound Collection',
            'total_compound_collection'             => 'Total Compound Collection',
            'total_collection'                      => 'Total Collection',
        ];
    }
    

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['user_id'], $fields['enforcement_user_id']);

        $fields['created_at'] = function (self $model) {
            return date("Y-m-d H:i:s", $model->created_at);
        };

        $fields['updated_at'] = function (self $model) {
            return date("Y-m-d H:i:s", $model->updated_at);
        };

        return $fields;
    }
    public function getEnforcementuser()
    {
        return $this->hasOne(EnforcementUser::className(), ['id' /*管理表主见id*/=> 'enforcement_user_id' /**/])->alias('enforcementuser');
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: xiaoguo0426
 * Date: 2016/11/14
 * Time: 15:43
 */

namespace common\services;

use common\models\AgentSetting;
use common\models\Consumer;
use common\models\EnforcementCompound;
use common\models\MerchantPaymentLog;
use common\models\MerchantSetting;
use common\models\ParkingOrder;
use Yii;
use yii\helpers\ArrayHelper;
use yii\sphinx\Query;

class ReportsService
{
    public static function getMerchantPaymentLogTotal($time,$order_type)
    {
        $return = array();
        $list = MerchantPaymentLog::find()->select('money')->where(['between', 'created_at', $time, $time+24*60*60-1])
            ->andWhere(['order_type' => $order_type,'merchant_user_id'=>getUserId()])->asArray()->all();
        $return['quantity_total'] = count($list);
        $return['money_total'] = array_sum(array_column($list,'money'));
        return $return;
    }

    public static function getMerchantPaymentTypeLogTotal($time,$order_type,$payment_method_name)
    {
        $return = array();
        $list = MerchantPaymentLog::find()->select('money')->where(['between', 'created_at', $time, $time+24*60*60-1])
            ->andWhere(['order_type' => $order_type])
            ->andWhere(['payment_method_name' => $payment_method_name])
            ->asArray()->all();
        $return['quantity_total'] = count($list);
        $return['money_total'] = array_sum(array_column($list,'money'));
        return $return;
    }
    /**
     * 获取单产品运营数据 (固本 套餐卡 罚单) 一共多少钱  一共多少订单  传入开始结束时间戳 结束时间戳
     */
    public function getOperationalDataTypeByType($data)
    {
        $operationalData                     = MerchantPaymentLog::find()
            ->where(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        $info                                = [];
        $info["total_sales"]                 = 0;
        $info["number_of_sales"]             = 0;
        $info["coupon_total_sales"]          = 0;
        $info["coupon_number_of_sales"]      = 0;
        $info["season_pass_total_sales"]     = 0;
        $info["season_pass_number_of_sales"] = 0;
        $info["compound_total_sales"]        = 0;
        $info["compound_number_of_sales"]    = 0;
        if (!empty($operationalData)) {
            $info["total_sales"]     = array_sum(ArrayHelper::getColumn($operationalData, 'money'));
            $info["number_of_sales"] = sizeof($operationalData);

            $orderType = ArrayHelper::index($operationalData, null, 'order_type');
            //print_r($orderType);exit;
            //获取固本统计数据
            if (isset($orderType["coupon"])) {
                $info["coupon_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["coupon"], 'money'));
                $info["coupon_number_of_sales"] = sizeof($orderType["coupon"]);
            }

            if (isset($orderType["season_pass"])) {

                $info["season_pass_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["season_pass"], 'money'));
                $info["season_pass_number_of_sales"] = sizeof($orderType["season_pass"]);
            }

            if (isset($orderType["compound"])) {
                $info["compound_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["compound"], 'money'));
                $info["compound_number_of_sales"] = sizeof($orderType["compound"]);
            }
        }

        return $info;
    }

    /**
     * 获取用户统计数据
     */
    public function getConsumerData($data)
    {
        $consumerData                     = Consumer::find()
            ->where(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        //新用户当天
        $return['new_consumer_total'] = count($consumerData);
        //当天活跃用户
        $return['active_consumer_total'] = count($consumerData);
        return $return;
    }
    /**
     * 获取停车订单统计数据
     */
    public function getParkingOrderData($data)
    {
        $list = ParkingOrder::find()->select('order_money')
            ->where(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        $return['parking_order_total'] = count($list);
        $return['parking_order_money_total'] = array_sum(array_column($list,'order_money'));
        return $return;
    }
    /**
     * 搜索数据组装
     */
    public function searchData($searchModelName,$Params)
    {
        if (isset($Params[$searchModelName]['start_date'])|| isset($Params[$searchModelName]['end_date'])){
            if (empty($Params[$searchModelName]['start_date'])||empty($Params[$searchModelName]['end_date'])){
                if (empty($Params[$searchModelName]['start_date'])&&empty($Params[$searchModelName]['start_date'])){
                    $start=strtotime(date('Y-m-d').'00:00:00');
                    $end=time();
                }else{
//                    $this->error('Date cannot be blank');
                    return false;
                }
            }
            $start = strtotime($Params[$searchModelName]['start_date']);
            $end = strtotime($Params[$searchModelName]['end_date']);
        }else{
            $start=strtotime(date('Y-m-d').'00:00:00');
            $end=time();
        }
        $Params[$searchModelName]['start_date'] = $start;
        $Params[$searchModelName]['end_date'] = $end;
        $Params[$searchModelName]['date_type'] = isset($Params[$searchModelName]['date_type'])?$Params[$searchModelName]['date_type']:1;
        return $Params;
    }


    public function searchParkingByArea($searchModelName,$Params)
    {
        if (isset($Params[$searchModelName]['start_date'])|| isset($Params[$searchModelName]['end_date'])){
            if (empty($Params[$searchModelName]['start_date'])||empty($Params[$searchModelName]['end_date'])){
                if (empty($Params[$searchModelName]['start_date'])&&empty($Params[$searchModelName]['start_date'])){
                    $start=strtotime(date('Y-m-d').'00:00:00');
                    $end=time();
                }else{
                    return false;
                }
            }
            $start = strtotime($Params[$searchModelName]['start_date']);
            $end = strtotime($Params[$searchModelName]['end_date']);
        }else{
            $start  =  strtotime(date('m-01-Y'));
            $end    =  time();
        }
        $Params[$searchModelName]['start_date'] = $start;
        $Params[$searchModelName]['end_date'] = $end;
        return $Params;
    }

    public function enforcementCompoundUnpaidInfo($searchModelName,$Params)
    {
        if (isset($Params[$searchModelName]['start_date'])|| isset($Params[$searchModelName]['end_date'])){
            if (empty($Params[$searchModelName]['start_date'])||empty($Params[$searchModelName]['end_date'])){
                if (empty($Params[$searchModelName]['start_date'])&&empty($Params[$searchModelName]['start_date'])){
                    $start=strtotime(date('Y-m-d').'00:00:00');
                    $end=time();
                }else{
                    return false;
                }
            }
            $start = strtotime($Params[$searchModelName]['start_date']);
            $end = strtotime($Params[$searchModelName]['end_date']);
        }else{
            $start  =  strtotime(date('m-01-Y'));
            $end    =  time();
        }
        $Params[$searchModelName]['start_date'] = $start;
        $Params[$searchModelName]['end_date'] = $end;
        $Params[$searchModelName]['date_type'] = isset($Params[$searchModelName]['date_type'])?$Params[$searchModelName]['date_type']:4;
        return $Params;
    }

    /**
     * 获取商家单产品运营数据 (固本 套餐卡 罚单) 一共多少钱  一共多少订单  传入开始结束时间戳 结束时间戳
     */
    public function getMerchantOperationalDataTypeByType($data)
    {
        $operationalData         = MerchantPaymentLog::find()
            ->where(["merchant_user_id" => $data["merchant_user_id"], "order_type" => $data["order_type"]])
            ->andWhere(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        $info["total_sales"]     = 0;
        $info["number_of_sales"] = 0;

        $cash_total_sales     = 0;
        $cash_number_of_sales = 0;
        $payment_info["cash"] = [
            "total_sales"     => $cash_total_sales,
            "number_of_sales" => $cash_number_of_sales,
        ];

        $card_total_sales     = 0;
        $card_number_of_sales = 0;
        $payment_info["card"] = [
            "total_sales"     => $card_total_sales,
            "number_of_sales" => $card_number_of_sales,
        ];

        $ewallet_total_sales     = 0;
        $ewallet_number_of_sales = 0;
        $payment_info["ewallet"] = [
            "total_sales"     => $ewallet_total_sales,
            "number_of_sales" => $ewallet_number_of_sales,
        ];

        $typeInfo = [];

        if (!empty($operationalData)) {
            $info["total_sales"]     = array_sum(ArrayHelper::getColumn($operationalData, 'money'));
            $info["number_of_sales"] = sizeof($operationalData);

            switch ($data["order_type"]) {
                case "coupon":
                    $coupon_info = [];

                    foreach ($operationalData as $couponVal) {
                        $fields = json_decode($couponVal["fields"], true);
                        //print_r($fields);exit;
                        foreach ($fields as $coupon) {
                            $coupon_setting_id = $coupon["coupon_setting_id"];
                            $coupon_name       = $coupon["coupon_name"];
                            if (!isset($coupon_info[$coupon_setting_id]["count"])) {
                                $coupon_info[$coupon_setting_id]["count"] = 0;
                            }
                            $coupon_info[$coupon_setting_id] = [
                                "coupon_setting_id" => $coupon_setting_id,
                                "name"              => $coupon_name,
                                "count"             => $coupon_info[$coupon_setting_id]["count"] + 1,
                            ];
                        }
                        switch ($couponVal["payment_method_id"]) {
                            case "1":
                                $cash_total_sales     = $cash_total_sales + $couponVal["money"];
                                $cash_number_of_sales = $cash_number_of_sales + 1;
                                $payment_info["cash"] = [
                                    "total_sales"     => $cash_total_sales,
                                    "number_of_sales" => $cash_number_of_sales,
                                ];
                                break;
                            case "2":
                            case "3":
                                $card_total_sales     = $card_total_sales + $couponVal["money"];
                                $card_number_of_sales = $card_number_of_sales + 1;
                                $payment_info["card"] = [
                                    "total_sales"     => $card_total_sales,
                                    "number_of_sales" => $card_number_of_sales,
                                ];
                                break;
                            case "4":
                            case "5":
                            case "6":
                            case "7":
                                $ewallet_total_sales     = $ewallet_total_sales + $couponVal["money"];
                                $ewallet_number_of_sales = $ewallet_number_of_sales + 1;
                                $payment_info["ewallet"] = [
                                    "total_sales"     => $ewallet_total_sales,
                                    "number_of_sales" => $ewallet_number_of_sales,
                                ];
                                break;

                        }
                    }
                    $typeInfo = $coupon_info;

                    break;
                case "season_pass":
                    $seasonPassInfo = [];
                    foreach ($operationalData as $seasonPassVal) {
                        $fields = json_decode($seasonPassVal["fields"], true);
                        //print_r($fields);exit;
                        foreach ($fields as $seasonPass) {
                            $coupon_setting_id = $seasonPass["coupon_setting_id"];
                            $coupon_name       = $seasonPass["coupon_name"];
                            if (!isset($seasonPassInfo[$coupon_setting_id]["count"])) {
                                $seasonPassInfo[$coupon_setting_id]["count"] = 0;
                            }
                            $seasonPassInfo[$coupon_setting_id] = [
                                "coupon_setting_id" => $coupon_setting_id,
                                "name"              => $coupon_name,
                                "count"             => $seasonPassInfo[$coupon_setting_id]["count"] + 1,
                            ];
                        }
                        //var_dump($seasonPassVal["payment_method_id"]);
                        switch ($seasonPassVal["payment_method_id"]) {
                            case "1":
                                $cash_total_sales     = $cash_total_sales + $seasonPassVal["money"];
                                $cash_number_of_sales = $cash_number_of_sales + 1;
                                $payment_info["cash"] = [
                                    "total_sales"     => $cash_total_sales,
                                    "number_of_sales" => $cash_number_of_sales,
                                ];
                                break;
                            case "2":
                            case "3":
                                $card_total_sales     = $card_total_sales + $seasonPassVal["money"];
                                $card_number_of_sales = $card_number_of_sales + 1;
                                $payment_info["card"] = [
                                    "total_sales"     => $card_total_sales,
                                    "number_of_sales" => $card_number_of_sales,
                                ];
                                break;
                            case "4":
                            case "5":
                            case "6":
                            case "7":
                                $ewallet_total_sales     = $ewallet_total_sales + $seasonPassVal["money"];
                                $ewallet_number_of_sales = $ewallet_number_of_sales + 1;
                                $payment_info["ewallet"] = [
                                    "total_sales"     => $ewallet_total_sales,
                                    "number_of_sales" => $ewallet_number_of_sales,
                                ];
                                break;

                        }
                        //print_r($payment_info);exit;
                    }
                    $typeInfo = $seasonPassInfo;
                    break;
                case "compound":
                    foreach ($operationalData as $compoundVal) {
                        switch ($compoundVal["payment_method_id"]) {
                            case "1":
                                $cash_total_sales     = $cash_total_sales + $compoundVal["money"];
                                $cash_number_of_sales = $cash_number_of_sales + 1;
                                $payment_info["cash"] = [
                                    "total_sales"     => $cash_total_sales,
                                    "number_of_sales" => $cash_number_of_sales,
                                ];
                                break;
                            case "2":
                            case "3":
                                $card_total_sales     = $card_total_sales + $compoundVal["money"];
                                $card_number_of_sales = $card_number_of_sales + 1;
                                $payment_info["card"] = [
                                    "total_sales"     => $card_total_sales,
                                    "number_of_sales" => $card_number_of_sales,
                                ];
                                break;
                            case "4":
                            case "5":
                            case "6":
                            case "7":
                                $ewallet_total_sales     = $ewallet_total_sales + $compoundVal["money"];
                                $ewallet_number_of_sales = $ewallet_number_of_sales + 1;
                                $payment_info["ewallet"] = [
                                    "total_sales"     => $ewallet_total_sales,
                                    "number_of_sales" => $ewallet_number_of_sales,
                                ];
                                break;

                        }
                    }
                    $typeInfo = [];
                    break;
            }
        }

        return ["info" => $info, "payment_info" => $payment_info, "type_info" => $typeInfo];
    }
    public static function getMerchantPaymentLogTotalMoneyDay($data)
    {
        $money_total = 0;
        $list = MerchantPaymentLog::find()->select('money')
            ->where(['between', 'created_at', $data["start_time"], $data["end_time"]])
            ->andWhere(['merchant_user_id'=>$data['merchant_user_id']])
            ->andWhere(['<>','order_type','mix_order'])
            ->asArray()->all();
        $money_total = array_sum(array_column($list,'money'));
        return $money_total;
    }

    public function getEnforcementCompoundOffences($data){
        $operationalData  = EnforcementCompound::find()
            ->where(['=', 'enfrocement_user_id', $data["enforce_id"]])->where(['=', 'status','PAID'])->where(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        $info          = [];
        $info["total_amount_per_date"] = 0;
        if (!empty($operationalData)) {
            $info["total_amount_per_date"] = array_sum(ArrayHelper::getColumn($operationalData, 'actual_payment_money'));
        }
        return $info;

    }

    /**
     * 获取单产品运营数据 (固本 套餐卡 罚单) 一共多少钱  一共多少订单  传入开始结束时间戳 结束时间戳
     */
    public function getParkingSalesColorData($data)
    {
        $operationalData                     = ParkingOrder::find()
            ->where(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        $info                                = [];
        $info["total_sales"]                 = 0;
        $info["number_of_sales"]             = 0;
        $info["green_total_sales"]          = 0;
        $info["green_number_of_sales"]      = 0;
        $info["yellow_total_sales"]     = 0;
        $info["yellow_number_of_sales"] = 0;
        $info["red_total_sales"]        = 0;
        $info["red_number_of_sales"]    = 0;
        if (!empty($operationalData)) {
            $info["total_sales"]     = array_sum(ArrayHelper::getColumn($operationalData, 'order_money'));
            $info["number_of_sales"] = (array_sum(ArrayHelper::getColumn($operationalData, 'parking_end_time'))-
                    array_sum(ArrayHelper::getColumn($operationalData, 'parking_start_time')))/(60*60);

            $orderType = ArrayHelper::index($operationalData, null, 'coupon_color_id');
           
            if (isset($orderType["1"])) {
                $info["green_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["1"], 'order_money'));
                $info["green_number_of_sales"]     = (array_sum(ArrayHelper::getColumn($orderType["1"], 'parking_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["1"], 'parking_start_time')))/(60*60);
            }
            if (isset($orderType["2"])) {

                $info["yellow_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["2"], 'order_money'));
                $info["yellow_number_of_sales"]     = (array_sum(ArrayHelper::getColumn($orderType["2"], 'parking_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["2"], 'parking_start_time')))/(60*60);
            }
            if (isset($orderType["3"])) {
                $info["red_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["3"], 'order_money'));
                $info["red_number_of_sales"]     = (array_sum(ArrayHelper::getColumn($orderType["3"], 'parking_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["3"], 'parking_start_time')))/(60*60);
            }
        }
        

        return $info;
    }

    public function getParkingAreaSalesColorData($data)
    {
        $operationalData = ParkingOrder::find()->where(["between", "created_at", $data["start_time"], $data["end_time"]])
        ->andWhere(['=', 'area_name', $data["area_name"]])->asArray()->all();
        $info                           = [];
        $info["total_sales"]            = 0;
        $info["number_of_sales"]        = 0;
        $info["green_total_sales"]      = 0;
        $info["green_number_of_sales"]  = 0;
        $info["yellow_total_sales"]     = 0;
        $info["yellow_number_of_sales"] = 0;
        $info["red_total_sales"]        = 0;
        $info["red_number_of_sales"]    = 0;

        if (!empty($operationalData)) {
            $info["total_sales"]     = array_sum(ArrayHelper::getColumn($operationalData, 'order_money'));
            $info["number_of_sales"] = (array_sum(ArrayHelper::getColumn($operationalData, 'parking_end_time'))-
                    array_sum(ArrayHelper::getColumn($operationalData, 'parking_start_time')))/(60*60);

            $orderType = ArrayHelper::index($operationalData, null, 'coupon_color_id');
           
            if (isset($orderType["1"])) {
                $info["green_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["1"], 'order_money'));
                $info["green_number_of_sales"]     = (array_sum(ArrayHelper::getColumn($orderType["1"], 'parking_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["1"], 'parking_start_time')))/(60*60);
            }
            if (isset($orderType["2"])) {

                $info["yellow_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["2"], 'order_money'));
                $info["yellow_number_of_sales"]     = (array_sum(ArrayHelper::getColumn($orderType["2"], 'parking_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["2"], 'parking_start_time')))/(60*60);
            }
            if (isset($orderType["3"])) {
                $info["red_total_sales"]     = array_sum(ArrayHelper::getColumn($orderType["3"], 'order_money'));
                $info["red_number_of_sales"]     = (array_sum(ArrayHelper::getColumn($orderType["3"], 'parking_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["3"], 'parking_start_time')))/(60*60);
            }
        }

        return $info;
    }

    public function getUnpaidCompoundSearch($data)
    {
        $operationalData = EnforcementCompoundUnpaid::find()->select(['license_plate_number','id','created_at','actual_payment_money'])->where(['=', 'license_plate_number', $data["vehicle_no"]])->andWhere(['=', 'status', 'UNPAID'])->andFilterWhere(['between', 'created_at',  $data["start_time"], $data["end_time"]])->asArray()->all(); 
        $info                           = [];
        $info["total_compound_unpaid"]  = 0;
        $info["total_amount"]           = 0;
        $info["total_compound_unpaid"] = count($operationalData);
        if (!empty($operationalData)) {
            $total_amount = 0;
            $created_date = "";
            $fee = 0;
            foreach($operationalData as $key=>$val){
                $created_date =$val['created_at'];
                $diff = abs(time() - strtotime($created_date));
                $day = abs($diff / 86400);

                if($day<=30){
                    $fee = 30;
                }
                elseif($day>30 && $day<=60){
                    $fee = 50;
                }
                elseif($day>60){
                    $fee = 100;
                }
                $total_amount = $total_amount + $fee;
            }
            $info["total_amount"] = $total_amount;
        }

        return $info;
    }

    function dateDifference($start_date, $end_date)
    {
        // 1 day = 24 hours 
        // 24 * 60 * 60 = 86400 seconds
        return ceil(abs($diff / 86400));
    }

    function calculateFee($dateDiff)
    {
        if($dateDiff<=30){
            $fee = 30;
        }
        elseif($dateDiff>30 && $dateDiff<=60){
            $fee = 50;
        }
        elseif($dateDiff>60){
            $fee = 100;
        }
        return $fee;
    }



    /**
     * 月卡销售状态
     */
    public function getseasonPasssalesSalesData($data,$coupon_setting_list=null)
    {
        $operationalData                     = MerchantPaymentLog::find()
            ->where(["between", "created_at", $data["start_time"], $data["end_time"]])
            ->andWhere(['order_type'=>'season_pass'])
            ->asArray()->all();
        $list =array();
        foreach ($operationalData as $key=>$val){
            $list = array_merge($list,json_decode($val['fields'],true));
        }
        $orderType = ArrayHelper::index($list, null, 'code');
        $info["total_pcs"]     = sizeof($list);
        $info["total_amount"] = number_format(array_sum(ArrayHelper::getColumn($operationalData, 'money')),2);
        $info["total_amount_value"] = array_sum(ArrayHelper::getColumn($operationalData, 'money'));
        foreach ($coupon_setting_list as $key=>$value){
            $info[$value['code']."_pcs"]     = isset($orderType[$value['code']])?sizeof($orderType[$value['code']]):0;
            $info[$value['code']."_amount"]    = isset($orderType[$value['code']])?'RM '.number_format(array_sum(ArrayHelper::getColumn($orderType[$value['code']], 'money')),2):'RM 0.00';
            $info[$value['code']."_amount_value"]    = isset($orderType[$value['code']])?array_sum(ArrayHelper::getColumn($orderType[$value['code']], 'money')):0;
        }
        return $info;
    }
    /**
     * Agent固本销售状态
     */
    public function getAgentSalesData($data,$coupon_setting_list=null)
    {
        $merchant = MerchantSetting::find()->where(['type'=>'special'])->asArray()->all();
        $merchant_id_arr = array_column($merchant,'id');
        $operationalData                     = MerchantPaymentLog::find()
            ->where(["between", "created_at", $data->start_date, $data->end_date])
            ->andWhere(['order_type'=>'coupon','merchant_user_id'=>$merchant_id_arr])
            ->asArray()->all();
        $list =array();
        foreach ($operationalData as $key=>$val){
            $list = array_merge($list,json_decode($val['fields'],true));
        }
        //组装代理固本数据
        $orderType = ArrayHelper::index($list, null, ['agent_id','code']);
        //获取代理
        $agent = AgentSetting::find()->asArray()->all();
        $info_list = array();
        foreach ($agent as $key=>$value){
            $info_list[$key]['name'] = $value['name'];
            foreach ($coupon_setting_list as $k=>$val){
                $info_list[$key][$val['code']."_quantity"] = isset($orderType[$value['id']][$val['code']])?
                    sizeof($orderType[$value['id']][$val['code']]):0;
                $info_list[$key][$val['code']."_amount"] = isset($orderType[$value['id']][$val['code']])?
                    number_format(array_sum(ArrayHelper::getColumn($orderType[$value['id']][$val['code']], 'money')),2):'RM 0.00';
            }
        }
        return $info_list;
    }

    /**
     * agent固本销售状态
     */
    public function getcouponSalesData($data,$coupon_setting_list=null)
    {
        $operationalData                     = MerchantPaymentLog::find()
            ->where(["between", "created_at", $data["start_time"], $data["end_time"]])
            ->andWhere(['order_type'=>'coupon'])
            ->asArray()->all();
        $list =array();
        foreach ($operationalData as $key=>$val){
            $list = array_merge($list,json_decode($val['fields'],true));
        }
        //处理固本数量 1 box = 250 booklet
        foreach ($list as $key=>$value){
            if ($value['uom']==1){
                $list[$key]['total_number'] = $value['quantity']*250;//一箱转换
            }else{
                $list[$key]['total_number'] = $value['quantity'];
            }
        }
        $orderType = ArrayHelper::index($list, null, 'code');
        $info["total_pcs"]     = array_sum(ArrayHelper::getColumn($list, 'total_number'));
        $info["total_amount"] = number_format(array_sum(ArrayHelper::getColumn($operationalData, 'money')),2);
        $info["total_amount_value"] = array_sum(ArrayHelper::getColumn($operationalData, 'money'));
        foreach ($coupon_setting_list as $key=>$value){
            $info[$value['code']."_pcs"]     = isset($orderType[$value['code']])?array_sum(ArrayHelper::getColumn($orderType[$value['code']], 'total_number')):0;
            $info[$value['code']."_amount"]    = isset($orderType[$value['code']])?'RM '.number_format(array_sum(ArrayHelper::getColumn($orderType[$value['code']], 'money')),2):'RM 0.00';
            $info[$value['code']."_amount_value"]    = isset($orderType[$value['code']])?array_sum(ArrayHelper::getColumn($orderType[$value['code']], 'money')):0;
        }
        return $info;
    }

    /**
     * Agent固本销售状态
     */
    public function getAgentSalesTotalData()
    {
        $merchant = MerchantSetting::find()->where(['type'=>'special'])->asArray()->all();
        $merchant_id_arr = array_column($merchant,'id');
        $operationalData                     = MerchantPaymentLog::find()
            ->where(["between", "created_at", strtotime(date('Y-m-d').'00:00:00'), time()])
            ->andWhere(['order_type'=>'coupon','merchant_user_id'=>$merchant_id_arr])
            ->asArray()->all();
        $data['agent_sales_total'] = array_sum(ArrayHelper::getColumn($operationalData, 'money'));
        $data['agent_number_total'] = count($operationalData);
        return $data;
    }


    /**
     * Agent固本销售状态
     */
    public function getCompoundSalesTotalData()
    {
        $operationalData                     = EnforcementCompound::find()->select('compound_money')
            ->where(["between", "created_at", strtotime(date('Y-m-d').'00:00:00'), time()])
            ->asArray()->all();
        foreach ($operationalData as $key=>$val){
            $compound_money = json_decode($val['compound_money'],true);
            $operationalData[$key]['money'] = isset($compound_money[0]['money'])?$compound_money[0]['money']:0;
        }
        $data['compound_sales_total'] = array_sum(ArrayHelper::getColumn($operationalData, 'money'));
        $data['compound_number_total'] = count($operationalData);
        return $data;
    }
    public function getEnforcementCompoundCollectionData($data)
    {
        $operationalData = EnforcementCompound::find()->where(["between", "created_at", $data["start_time"], $data["end_time"]])->asArray()->all();
        $list = array();
            $info                                = [];
        $info["total_DBKKH_compound_collection_order"] = 0;
        $info["total_DBKKH_compound_collection"]       = 0;
        $info["total_DBKK_compound_collection_order"]  = 0;
        $info["total_DBKK_compound_collection"]        = 0;
        $info["total_compound_collection"]             = 0;
        $info["total_collection"]                      = 0;
        
        if (!empty($operationalData)) {
            $info["total_collection"]     = array_sum(ArrayHelper::getColumn($operationalData, 'order_money'));
            $info["total_compound_collection"] = (array_sum(ArrayHelper::getColumn($operationalData, 'compound_end_time'))-
                    array_sum(ArrayHelper::getColumn($operationalData, 'compound_start_time')))/(60*60);

            $orderType = ArrayHelper::index($operationalData, null, 'compound_id');
           
            if (isset($orderType["1"])) {
                $info["total_DBKKH_compound_collection"]     = array_sum(ArrayHelper::getColumn($orderType["1"], 'order_money'));
                $info["total_DBKKH_compound_collection_order"]     = (array_sum(ArrayHelper::getColumn($orderType["1"], 'compound_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["1"], 'compound_start_time')))/(60*60);
            }
            if (isset($orderType["2"])) {

                $info["total_DBKK_compound_collection"]     = array_sum(ArrayHelper::getColumn($orderType["2"], 'order_money'));
                $info["total_DBKK_compound_collection_order"]     = (array_sum(ArrayHelper::getColumn($orderType["2"], 'compound_end_time'))
                        -array_sum(ArrayHelper::getColumn($orderType["2"], 'compound_start_time')))/(60*60);
            }
            
        }

        return $info;

    }
}

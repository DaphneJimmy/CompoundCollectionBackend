<?php

namespace admin\controllers;

use common\controllers\AdminController;
use common\models\CouponSetting;
use common\models\MerchantPaymentLogSearch;
use common\models\MerchantSalesReport;
use common\models\MerchantSalesReportSearch;
use common\models\ParkingSales;
use common\models\ParkingSalesSearch;
use common\models\EnforcementCompoundUnpaid;
use common\models\EnforcementCompoundUnpaidSearch;
use common\models\ParkingAreaSales;
use common\models\ParkingAreaSalesSearch;
use common\models\EnforcementCompound;
use common\models\EnforcementCompoundSearch;
use common\models\EnforcementCompoundCollectionSearch;
use common\models\SalesSummary;
use common\models\SalesSummarySearch;
use common\services\AdminCommonService;
use common\services\ReportsService;
use kartik\mpdf\Pdf;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Reports  管理
 */
class ReportsController extends AdminController
{

    /**
     * 控制器名称
     * @var string
     */
    public $controller_title = 'Reports';

    /**
     * 需要权限控制的方法
     * @var array
     */
    public $access = [
        'sales-summary' => 'Sales Summary',
        'merchant-sales-report' => 'Merchant Sales Report',
        'parking-sales' => 'Parking Sales Report',
        'parking-sales-print' => 'Parking Sales Print Report',
        'parking-area-sales' => 'Parking Area Sales Report',    
        'parking-area-sales-print' => 'Parking Area Sales Print Report',  
        'coupon-sales' => 'Coupon Sales Report',
        'season-pass-sales' => 'Season Pass Sales Report',
        'agent-sales' => 'Agent Sales Report',
        'compound-collection-report' => 'Compound Collection',
        'compound-collection-report-print' => 'Compound Collection Print',
        'unpaid-compound' => 'TOP Unpaid Compound Report',
        'unpaid-compound-print' => 'TOP Unpaid Compound print Report',
    ];

    /**
     * 菜单模块选择器
     * @var array
     */
    public $menu = [
        'sales-summary' => 'Sales Summary',
        'merchant-sales-report' => 'Merchant Sales Report',
        'parking-sales' => 'Parking Sales Report',
        'parking-sales-print' => 'Parking Sales Print Report',
        'parking-area-sales' => 'Parking Area Sales Report', 
        'parking-area-sales-print' => 'Parking Area Sales Print Report',  
        'coupon-sales' => 'Coupon Sales Report',
        'season-pass-sales' => 'Season Pass Sales Report',
        'agent-sales' => 'Agent Sales Report',
        'compound-collection-report' => 'Compound Collection',
        'compound-collection-report-print' => 'Compound Collection Print',
        'unpaid-compound' => 'TOP Unpaid Compound Report',
        'unpaid-compound-print' => 'TOP Unpaid Compound print Report',
    ];
    /**
     * Lists all ChargeTransferRecord models.
     * @return mixed
     */
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }
    public $layout = "index";

    public function actionSalesSummary()
    {
        $view                       = $this->view;
        $Params = Yii::$app->request->get();
        $Model = new SalesSummary();
        $searchModel = new SalesSummarySearch();
        $listColumns            = array_keys($Model->attributeLabels());
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'SalesSummarySearch';
        $reports_service = new ReportsService();
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->adminSearch($searchDataInfo);

        //条形图

        $view->params["xAxis"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"day_time"));
        $view->params["yAxis_coupon_sales"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_coupon_sales_value"));
        $view->params["yAxis_season_pass_sales"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_season_pass_sales_value"));
        $view->params["yAxis_compound_payment"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_compound_payment_value"));
        $bar_chart_max =max(ArrayHelper::getColumn($dataProvider->allModels,"total_coupon_sales_value"))+
                        max(ArrayHelper::getColumn($dataProvider->allModels,"total_season_pass_sales_value"))+
                        max(ArrayHelper::getColumn($dataProvider->allModels,"total_compound_payment_value"));
        $view->params["bar_chart_max"] = $bar_chart_max+($bar_chart_max/10);
        return $this->render('sales-summary', [
            'dataProvider' => $dataProvider,
            'listColumns' => $listColumns,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type(),
            'params' => $searchDataInfo[$searchModelName],
        ]);

    }

    public function actionSalesSummaryPrint()
    {
        $view                       = $this->view;
        $Params = Yii::$app->request->get();
        $Model = new SalesSummary();
        $searchModel = new SalesSummarySearch();
        $listColumns            = array_keys($Model->attributeLabels());
        //处理默认时间格式
        $searchModelName = 'SalesSummarySearch';
        $reports_service = new ReportsService();
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->adminSearch($searchDataInfo);

        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();
        $list = $dataProvider->allModels;
        $total = count($list);
        $echart_img = 'https://dbkk-admin.uniappstech.com/assets/upload/echart-img/sales_summary_chart_img.png';
        $html = $this->renderPartial('sales-summary-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'columns_list'=>$Model->attributeLabels(),'total'=>$total,'echart_img'=>$echart_img,]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'     => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Summary Sales Report.pdf',
            'methods'     => [
                'SetTitle'    => 'Summary Sales Report',
            ],
        ]);
        return $pdf->render();
    }

    public function actionMerchantSalesReport()
    {
        $view                       = $this->view;
        $Params = Yii::$app->request->get();
        $Model = new MerchantSalesReport();
        $searchModel = new MerchantSalesReportSearch();
        $listColumns            = array_keys($Model->attributeLabels());
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'MerchantSalesReportSearch';
        $reports_service = new ReportsService();
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->search($searchDataInfo);


        //条形图
        $merchant = \common\models\MerchantSalesReport::all();
        $start_date = strtotime(date('Y-m-01'.' 00:00:00',time()));
        $end_date = strtotime(date('Y-m-'.date('t').' 23:59:59',time()));
        $day = $service->getAllDay(1,$start_date,$end_date);
        $list =array();
        foreach ($merchant as $key=>$value){
            $list[$key]['login_id'] = $value['login_id'];
            foreach ($day as $k=>$v){
                $data=[
                    'start_time'=>$v,
                    'end_time'=>$v+24*60*60-1,
                    'merchant_user_id'=>$value['id'],
                ];
                $total_money = $reports_service->getMerchantPaymentLogTotalMoneyDay($data);
                $list[$key]['total_money'][$k]['money'] = $total_money;
                $list[$key]['total_money'][$k]['date'] = date('d/m',$v);
            }
            $list_data[] =json_encode(ArrayHelper::getColumn($list[$key]['total_money'],"money"));
        }
        $view->params["legend_data"] =json_encode(ArrayHelper::getColumn($list,"login_id"));
        $view->params["xAxis_data"] =json_encode(ArrayHelper::getColumn($list[0]['total_money'],"date"));
        $view->params["list_data"] =  implode('@',$list_data);
        $view->params["list_data_name"] = implode(',',ArrayHelper::getColumn($list,"login_id"));
        $view->params["list_data_total"] = count($list);
        return $this->render('merchant-sales-report', [
            'dataProvider' => $dataProvider,
            'listColumns' => $listColumns,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type(),
            'params' => $searchDataInfo[$searchModelName],
        ]);
    }

    public function actionCompoundCollectionReport()
    {
        $Params = Yii::$app->request->get();
        $enforcementCompoundModel = new EnforcementCompound();
    
        $searchModel = new EnforcementCompoundCollectionSearch();
        $listColumns = array_keys($enforcementCompoundModel->compoundCollectionAttributeLabels());
        $service = new AdminCommonService();

        $searchModelName = 'EnforcementCompoundCollectionSearch';
        $reports_service = new ReportsService();
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->enforcementCompoundCollectionSearch($searchDataInfo);
      
        //条形图
        $view                       = $this->view;
        $view->params["xAxis"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"day_time"));
        $view->params["yAxis_blue_compound"]  = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKKH_compound_collection"));
        $view->params["yAxis_green_compound"] = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKK_compound_collection"));
        $bar_chart_max =max(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKKH_compound_collection"))+
            max(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKK_compound_collection"));
        $view->params["bar_chart_max"] = $bar_chart_max+($bar_chart_max/10);

       return $this->render('compound-collection-report', [
            'dataProvider' => $dataProvider,
            'listColumns' => $listColumns,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type(),
            'params' => $searchDataInfo[$searchModelName],
       ]); 
    }

    public function actionCompoundCollectionReportPrint()
    {
        $Params = Yii::$app->request->get();
        $enforcementCompoundModel = new EnforcementCompound();
    
        $searchModel = new EnforcementCompoundCollectionSearch();
        $listColumns = array_keys($enforcementCompoundModel->compoundCollectionAttributeLabels());
        $service = new AdminCommonService();

        $searchModelName = 'EnforcementCompoundCollectionSearch';
        $reports_service = new ReportsService();
        $searchDataInfo  = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->enforcementCompoundCollectionSearch($searchDataInfo);
        //条形图

        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();
        $list = $dataProvider->allModels;
        $total = count($list);



        $view = $this->view;
        $view->params["total_DBKKH_compound_collection_order"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKKH_compound_collection_order"));
        $view->params["total_DBKKH_compound_collection"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKKH_compound_collection"));
        $view->params["total_DBKK_compound_collection_order"] = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKK_compound_collection_order"));
        $view->params["total_DBKK_compound_collection"] = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_DBKK_compound_collection"));
        $view->params["total_compound_collection"]   = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_compound_collection"));
        $view->params["total_collection"]   = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_collection"));

        $echart_img = Url::home(true).'/assets/upload/echart-img/parking_sales_chart_img.png';
        $html = $this->renderPartial('compound-collection-report-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'columns_list'=>$enforcementCompoundModel->compoundCollectionAttributeLabels(),'total'=>$total,'echart_img'=>$echart_img,]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'   => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Enforcement Compound Collection Report.pdf',
            'methods'     => [
            'SetTitle'    => 'Compound Collection Report',
            ],
        ]);
        return $pdf->render();
    }

    public function actionUnpaidCompound()
    {

        $Params = Yii::$app->request->get();
        $compoundModel = new EnforcementCompoundUnpaid();
        $searchModel   = new EnforcementCompoundUnpaidSearch();
        $listColumns   = array_keys($compoundModel->attributeLabels());
        $service = new AdminCommonService();

        $searchModelName = 'EnforcementCompoundUnpaidSearch';
        $reports_service = new ReportsService();
        $searchDataInfo  = $reports_service->enforcementCompoundUnpaidInfo($searchModelName,$Params);
        $dataProvider    = $searchModel->EnforcementCompoundUnpaidSearch($searchDataInfo);

        return $this->render('top-unpaid-compound', [
            'dataProvider' => $dataProvider,
            'listColumns' => $listColumns,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type_unpaid_compound(),
            'params'    => $searchDataInfo[$searchModelName],
        ]);

    }

    public function actionUnpaidCompoundPrint()
    {

        $Params = Yii::$app->request->get();
        $compoundModel = new EnforcementCompoundUnpaid();
        $searchModel = new EnforcementCompoundUnpaidSearch();

        $service = new AdminCommonService();
        $searchModelName = 'EnforcementCompoundUnpaidSearch';
        $reports_service = new ReportsService();
        $searchDataInfo  = $reports_service->enforcementCompoundUnpaidInfo($searchModelName,$Params);
        $dataProvider    = $searchModel->EnforcementCompoundUnpaidSearch($searchDataInfo);

        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();

        $list  = $dataProvider->allModels;
        $total = count($list);

        $html = $this->renderPartial('top-unpaid-compound-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'columns_list'=>$compoundModel->attributeLabels(),'total'=>$total]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'   => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Top Unpaid Compound Report.pdf',
            'methods'     => [
            'SetTitle'    => 'Top Unpaid Compound Report',
            ],
        ]);
        return $pdf->render();
    }

    public function actionParkingAreaSales()
    {

        $Params = Yii::$app->request->get();
        $parkingAreaSalesModel = new ParkingAreaSales();
        $searchAreaModel       = new ParkingAreaSalesSearch();
        $listColumns           = array_keys($parkingAreaSalesModel->attributeLabels());
        $service = new AdminCommonService();
        //处理默认时间格式

        $searchModelName = 'ParkingAreaSalesSearch';
        $reports_service = new ReportsService();

        $searchDataInfo  = $reports_service->searchParkingByArea($searchModelName,$Params);
        $dataProvider    = $searchAreaModel->parkingAreaSalesSearch($searchDataInfo);
        //条形图
        $view = $this->view;
        $view->params["xAxis"]= json_encode(ArrayHelper::getColumn($dataProvider->allModels,"area_name"));
        $view->params["yAxis_green_sales"]  = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_green"));
        $view->params["yAxis_yellow_sales"] = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_yellow"));
        $view->params["yAxis_red_sales"]    = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_red"));
        $bar_chart_max =max(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_green"))+
            max(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_yellow"))+
            max(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_red"));
        $view->params["bar_chart_max"] = $bar_chart_max+($bar_chart_max/10);

       return $this->render('parking-area-sales', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchAreaModel,
            'params' => $searchDataInfo[$searchModelName],
        ]);
    }

    public function actionParkingAreaSalesPrint()
    {

        $Params = Yii::$app->request->get();
        $parkingAreaSalesModel = new ParkingAreaSales();
        $searchModel = new ParkingAreaSalesSearch();
        //处理默认时间格式
        $searchModelName = 'ParkingAreaSalesSearch';
        $reports_service = new ReportsService();
        $searchDataInfo  = $reports_service->searchParkingByArea($searchModelName,$Params);
        $dataProvider    = $searchModel->parkingAreaSalesSearch($searchDataInfo);
        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();
        $list = $dataProvider->allModels;
        $total = count($list);
        $view = $this->view;
        $view->params["total_green_hours"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours_green"));
        $view->params["total_green_sales"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_green"));
        $view->params["total_yellow_hours"] = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours_yellow"));
        $view->params["total_yellow_sales"] = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_yellow"));
        $view->params["total_red_hours"]   = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours_red"));
        $view->params["total_red_sales"]   = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_red"));
        $view->params["total_parking_hours"]= array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours"));
        $view->params["total_sales"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_sales"));

       $echart_img = Url::home(true).'/assets/upload/echart-img/parking_sales_chart_img.png';
        $html = $this->renderPartial('parking-area-sales-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'columns_list'=>$parkingAreaSalesModel->attributeLabels(),'total'=>$total,'echart_img'=>$echart_img,]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'   => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Parking Area Sales Report.pdf',
            'methods'     => [
            'SetTitle'    => 'Parking Area Sales Report',
            ],
        ]);
        return $pdf->render();

    }


    public function actionParkingSales()
    {

        $Params = Yii::$app->request->get();
        $parkingSalesModel = new ParkingSales();
        $searchModel = new ParkingSalesSearch();
        $listColumns            = array_keys($parkingSalesModel->attributeLabels());
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'ParkingSalesSearch';
        $reports_service = new ReportsService();
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->parkingSalesSearch($searchDataInfo);
        //条形图
        $view                       = $this->view;
        $view->params["xAxis"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"day_time"));
        $view->params["yAxis_green_sales"]  = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_green"));
        $view->params["yAxis_yellow_sales"] = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_yellow"));
        $view->params["yAxis_red_sales"]    = json_encode(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_red"));
        $bar_chart_max =max(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_green"))+
            max(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_yellow"))+
            max(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_red"));
        $view->params["bar_chart_max"] = $bar_chart_max+($bar_chart_max/10);
        return $this->render('parking-sales', [
            'dataProvider' => $dataProvider,
            'listColumns' => $listColumns,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type(),
            'params' => $searchDataInfo[$searchModelName],
        ]);

    }

    public function actionParkingSalesPrint()
    {

        $Params = Yii::$app->request->get();
        $parkingSalesModel = new ParkingSales();
        $searchModel = new ParkingSalesSearch();
        //处理默认时间格式
        $searchModelName = 'ParkingSalesSearch';
        $reports_service = new ReportsService();
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->parkingSalesSearch($searchDataInfo);
        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();
        $list = $dataProvider->allModels;
        $total = count($list);

        #view at table footer
        $view = $this->view;
        $view->params["total_green_hours"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours_green"));
        $view->params["total_green_sales"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_green"));
        $view->params["total_yellow_hours"] = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours_yellow"));
        $view->params["total_yellow_sales"] = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_yellow"));
        $view->params["total_red_hours"]   = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours_red"));
        $view->params["total_red_sales"]   = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_sales_red"));
        $view->params["total_parking_hours"]= array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_parking_hours"));
        $view->params["total_sales"]  = array_sum(ArrayHelper::getColumn($dataProvider->allModels,"total_sales"));

        $echart_img = Url::home(true).'/assets/upload/echart-img/parking_sales_chart_img.png';
        $html = $this->renderPartial('parking-sales-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'columns_list'=>$parkingSalesModel->attributeLabels(),'total'=>$total,'echart_img'=>$echart_img,]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'     => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Parking Sales Report.pdf',
            'methods'     => [
                'SetTitle'    => 'Parking Sales Report',
            ],
        ]);
        return $pdf->render();

    }

    public function actionCouponSales()
    {

        $Params = Yii::$app->request->get();
        $searchModel = new MerchantPaymentLogSearch();
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'MerchantPaymentLogSearch';
        $reports_service = new ReportsService();
        $coupon_setting_list = CouponSetting::find()->where(['period_tracking'=>0])->asArray()->all();
        //列表
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);

        $dataProvider       = $searchModel->couponSalesDataSearch($searchDataInfo,$coupon_setting_list);
        //条形图
        $view                       = $this->view;
        $view->params["xAxis"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"day_time"));
        //组装条形图series
        $series = array();
        $bar_chart_max= 0;
        foreach ($coupon_setting_list as $key=>$value){
            $series[$key]=
                [
                    'name'=>$value['code'].' Coupon',
                    'type'=>'bar',
                    'stack'=>'one',
                    'data'=>ArrayHelper::getColumn($dataProvider->allModels,$value['code'].'_amount_value'),
                ]
            ;
            $bar_chart_max+= max(ArrayHelper::getColumn($dataProvider->allModels,$value['code'].'_amount_value'));
        }
        $view->params["bar_chart_max"] = $bar_chart_max+($bar_chart_max/10);


        return $this->render('coupon-sales', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type(),
            'params' => $searchDataInfo[$searchModelName],
            'coupon_setting_list' => $coupon_setting_list,
            'series' => json_encode($series),
        ]);

    }

    public function actionCouponSalesPrint()
    {

        $Params = Yii::$app->request->get();
        $searchModel = new MerchantPaymentLogSearch();
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'MerchantPaymentLogSearch';
        $reports_service = new ReportsService();
        $coupon_setting_list = CouponSetting::find()->where(['period_tracking'=>0])->asArray()->all();
        //列表
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->couponSalesDataSearch($searchDataInfo,$coupon_setting_list);
        foreach ($coupon_setting_list as $key=>$value){
            $coupon_setting_list[$key]['coupon_name']=$value['code'].' Coupon (Booklet)';
        }
        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();
        $list = $dataProvider->allModels;
//        var_dump($list);exit();
        $total = count($list);
        $echart_img = 'https://dbkk-admin.uniappstech.com/assets/upload/echart-img/coupon_sales_chart_img.png';
        $html = $this->renderPartial('coupon-sales-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'coupon_setting_list'=>$coupon_setting_list,'total'=>$total,'echart_img'=>$echart_img,]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'     => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Coupon Sales Reports.pdf',
            'methods'     => [
                'SetTitle'    => 'Coupon Sales Reports',
            ],
        ]);
        return $pdf->render();
    }
    public function actionSeasonPassSales()
    {

        $Params = Yii::$app->request->get();
        $searchModel = new MerchantPaymentLogSearch();
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'MerchantPaymentLogSearch';
        $reports_service = new ReportsService();
        $coupon_setting_list = CouponSetting::find()->where(['period_tracking'=>1])->asArray()->all();
        //列表
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->seasonPasssalesSalesDataSearch($searchDataInfo,$coupon_setting_list);
        //条形图
        $view                       = $this->view;
        $view->params["xAxis"] =json_encode(ArrayHelper::getColumn($dataProvider->allModels,"day_time"));
        //组装条形图series
        $series = array();
        $bar_chart_max= 0;
        foreach ($coupon_setting_list as $key=>$value){
            $series[$key]=
                [
                    'name'=>$value['name'],
                    'type'=>'bar',
                    'stack'=>'one',
                    'data'=>ArrayHelper::getColumn($dataProvider->allModels,$value['code'].'_amount_value'),
                ]
            ;
            $bar_chart_max+= max(ArrayHelper::getColumn($dataProvider->allModels,$value['code'].'_amount_value'));
        }
        $view->params["bar_chart_max"] = $bar_chart_max+($bar_chart_max/10);

        return $this->render('season-pass-sales', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'date_type' => $service->data_type(),
            'params' => $searchDataInfo[$searchModelName],
            'coupon_setting_list' => $coupon_setting_list,
            'series' => json_encode($series),
        ]);

    }
    public function actionSeasonPassSalesPrint()
    {


        $Params = Yii::$app->request->get();
        $searchModel = new MerchantPaymentLogSearch();
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'MerchantPaymentLogSearch';
        $reports_service = new ReportsService();
        $coupon_setting_list = CouponSetting::find()->where(['period_tracking'=>1])->asArray()->all();
        //列表
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $dataProvider       = $searchModel->seasonPasssalesSalesDataSearch($searchDataInfo,$coupon_setting_list);
        $admin_common_service = new AdminCommonService();
        $pdf_content          = $admin_common_service->inventoryPdfContent();
        $list = $dataProvider->allModels;
        $total = count($list);
        $echart_img = 'https://dbkk-admin.uniappstech.com/assets/upload/echart-img/season_pass_sales_chart_img.png';
        $html = $this->renderPartial('season-pass-sales-pdf-html',
            ['pdf_content' => $pdf_content,'list'=>$list,'coupon_setting_list'=>$coupon_setting_list,'total'=>$total,'echart_img'=>$echart_img,]
        );
        $pdf = new Pdf([
            'content'     => $html, //$this->renderPartial('privacy'),
            'cssInline'     => $this->renderPartial('//layouts/common-pdf-css'),
            'filename'    => 'Season Pass Reports.pdf',
            'methods'     => [
                'SetTitle'    => 'Season Pass Reports',
            ],
        ]);
        return $pdf->render();
    }
    public function actionAgentSales()
    {

        $Params = Yii::$app->request->get();
        $searchModel = new MerchantPaymentLogSearch();
        $service = new AdminCommonService();
        //处理默认时间格式
        $searchModelName = 'MerchantPaymentLogSearch';
        $reports_service = new ReportsService();
        $coupon_setting_list = CouponSetting::find()->where(['period_tracking'=>0])->asArray()->all();

        //条形图
        $searchDataInfo = $reports_service->searchData($searchModelName,$Params);
        $list       = $searchModel->agentSalesDataSearch($searchDataInfo,$coupon_setting_list);
        return $this->render('agent-sales', [
            'list' => $list,
            'searchModel' => $searchModel,
            'params' => $searchDataInfo[$searchModelName],
            'coupon_setting_list' => $coupon_setting_list,
        ]);

    }
}

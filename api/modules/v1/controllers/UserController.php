<?php
namespace api\modules\v1\controllers;
use ocilib\OciLib;
use Yii;
//use yii\rest\ActiveController;
use api\modules\v1\models\User;
use \mongosoft\soapclient\UblClient;
//yii::import('application.vendors.MyLib.*');

require_once('../../vendor/ocilib/OciLib.php');
//require_once('../../vendor/wsdl2phpgenerator/src/Config.php');
//require_once('../../vendor/wsdl2phpgenerator/src/Generator.php');

/**
 * User Controller API
 *
 */
class UserController extends AuthController
{
    //public $modelClass = 'api\modules\v1\models\User';
    public function actionLogin()
    {
        return true;
    }

    public function actionChangepassword()
    {
        $post = Yii::$app->request->post();
        $response = array();
        if(empty($post['userEmail']) || empty($post['userPass']) || empty($post['newPass']))
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }


        $result = $this->model->changePassword($post['userEmail'],$post['userPass'],$post['newPass']);
        $response['code'] = "401";
        if($result == "TRUE")
        {
            $this->model->addLog("change_password",$post['userEmail']." changed password");
            $response['code'] = "200";
        }

        $this->setResponse($response);
    }

    public function actionCgtpre()
    {
        $post = Yii::$app->request->post();
        $response = array();

        $post = Yii::$app->request->post();

        if(empty($post['custAccCode']) || empty($post['planCode']) || empty($post['unitType']) || empty($post['typeValue']))
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $post['amount'] = (isset($post['amount']) ? $post['amount'] : '');
        $post['unitPercent'] = (isset($post['unitPercent']) ? $post['unitPercent'] : '');
        $post['unit'] = (isset($post['unit']) ? $post['unit'] : '');


        //$c= $this->ociConnect();

        $ociLib = new OciLib();
        $c = $ociLib->ociConnect(Yii::$app->params['DB_USER'], Yii::$app->params['DB_PASS'], Yii::$app->params['DB_NAME']);

        $response['data'] = $this->model->getCgtPre($post['custAccCode'],$post['planCode'],$post['unitType'],$post['typeValue'],$post['amount'],$post['unitPercent'],$post['unit'],$post['navDate'],$c);
        if($response['data'] !== false)
        {
            $response['code'] = "200";
        }
        $this->setResponse($response);
    }

    public function actionCgtpost()
    {
        $post = Yii::$app->request->post();
        $response = array();
        $response['code'] = "200";
        if(empty($post['custAccCode']) || empty($post['transactionSno']) || empty($post['transactionType']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }


        //$c = $this->ociConnect();

        $ociLib = new OciLib();
        $c = $ociLib->ociConnect(Yii::$app->params['DB_USER'], Yii::$app->params['DB_PASS'], Yii::$app->params['DB_NAME']);

        $response['data'] = $this->model->getCgtPost($post['custAccCode'],$post['transactionSno'],$post['transactionType'],$c);
        $this->setResponse($response);
    }

    public function actionCustomers_data()
    {
        $post = Yii::$app->request->post();


        $userCd = $this->model->getUserCdByToken($this->authToken);
        $accountCode = (isset($post['accountCode']) ? $post['accountCode'] : "");
        $accountName = (isset($post['accountName']) ? $post['accountName'] : "");
        $cnic = (isset($post['cnic']) ? $post['cnic'] : "");
        $email= (isset($post['email']) ? $post['email'] : "");
        $cgtExempted = (isset($post['cgtExempted']) ? $post['cgtExempted'] : "");

        $zakatExempted = (isset($post['zakatExempted']) ? $post['zakatExempted'] : "");
        $phone = (isset($post['phone']) ? $post['phone'] : "");

        $response = $this->model->getCustomersList($userCd,$accountCode,$accountName,$cnic,$email,$cgtExempted,$zakatExempted,$phone);
        if($response)
        {
            $this->setResponse($response);
        }
        else
        {
            $response['code'] = "403";
            $this->setResponse($response);
        }
    }

    public function actionLoadearned()
    {
        $post = Yii::$app->request->post();
        if(empty($post['userCd']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $gsno = $this->model->getUserGroupSno($post['userCd']);
        $response = $this->model->getEarnedValue($gsno['GROUP_SNO']);
        $this->setResponse($response);
    }

    public function createResponse($response)
    {
        if(is_numeric($response))
            return $response;
        else
            return json_encode($response);
    }

    public function actionBalancedetail()
    {
        $post = Yii::$app->request->post();
        if(empty($post['accountNo']) || empty($post['customerId']))
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $client = new \mongosoft\soapclient\UblClient([
            'url' => Yii::$app->params['REPORTS']['SOAP_SERVICE_URL'],
        ]);
        //'00008903-1'
        $param = array("AccessKey"=>Yii::$app->params['REPORTS']['BALANCE_DETAIL_KEY'],'AccountNo'=>$post['accountNo'],'CustomerId'=>$post['customerId'],'Channel'=>Yii::$app->params['REPORTS']['BALANCE_DETAIL_CHANNEL'],'Type1'=>'','AvailableHolding'=>'','TransactionType'=>'');
        $res = $client->GetBalanceDetail($param);

        $res = $this->resultToJson($res->GetBalanceDetailResult);
        $this->setResponse($res);
        //echo $res;
    }

////////////////////////////////////////////
    public static function resultToJson($response)
    {
        try {
            if(isset($response->any))
            {
                $result = self::xmlStringToJson($response->any);
            }
            else
            {
                $result = self::xmlStringToJson($response);
            }

            if(isset($result['NewDataSet']['Table1']))
            {
                $result = $result['NewDataSet']['Table1'];
            }
        } catch(\Exception $ex) {
            $result = $response;
        }
        return $result;
    }

    public static function xmlStringToJson($xmlString)
    {
        $xml = simplexml_load_string($xmlString);
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    ///////////////////////////////////////////////////
    public function actionCustomerreport()
    {
        $post = Yii::$app->request->post();
        if(isset($post['accessKey']) && !empty($post['accessKey']) && isset($post['fromDate']) && !empty($post['fromDate']) && isset($post['toDate']) && !empty($post['toDate']) && isset($post['RegNo']) && !empty($post['RegNo']) && isset($post['toPlanCode']) && !empty($post['toPlanCode']) && isset($post['fromFundCode']) && !empty($post['fromFundCode']) && isset($post['toFundCode']) && !empty($post['toFundCode']) && isset($post['fromUnitType']) && !empty($post['fromUnitType']) && isset($post['toUnitType']) && !empty($post['toUnitType']) && isset($post['isProvision']) && !empty($post['isProvision']) && isset($post['reportType']) && !empty($post['reportType']))
        {

            if(empty($post['accountNo']) || empty($post['customerId']))
            {
                $response['code'] = "400";
                $this->setResponse($response);
            }

            $client = new \mongosoft\soapclient\UblClient([
                'url' => Yii::$app->params['REPORTS']['SOAP_SERVICE_URL'],
            ]);
            $param = array("AccessKey"=>Yii::$app->params['REPORTS']['CUSTOMER_REPORT_KEY'],'FromDate'=>$post['fromDate'],'ToDate'=>$post['toDate'],'RegNo'=>$post['RegNo'],'FromPlanCode'=>$post['fromPlanCode'],'ToPlanCode'=>$post['toPlanCode'],'FromFundCode'=>$post['fromFundCode'],'ToFundCode'=>$post['toFundCode'],'FromUnitType'=>$post['fromUnitType'],'ToUnitType'=> $post['toUnitType'],'IsProvision'=>$post['isProvision'],'ReportType'=>$post['reportType']);
            $res = $client->GetAccountStatement($param);

            $fileUrl = json_encode($this->resultToJson($res->GetBalanceDetailResult));

            /*$url = Yii::$app->params['REPORTS']['CUSTOMER_SERVICE_URL']."GetAccountStatement?AccessKey=".$post['accessKey']."&fromDate=".$post['fromDate']."&toDate=".$post['toDate']."&RegNo=".$post['RegNo']."&fromPlanCode=".$post['toPlanCode']."&toPlanCode=".$post['toPlanCode']."&fromFundCode=".$post['fromFundCode']."&toFundCode=".$post['toFundCode']."&fromUnitType=".$post['fromUnitType']."&toUnitType=".$post['toUnitType']."&isProvision=".$post['isProvision']."&reportType=".$post['reportType'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $fileUrl = curl_exec($ch);
            curl_close($ch);
            $fileUrl = substr($fileUrl,76,ceil(strlen($fileUrl) - 9 ));
            $fileUrl = str_replace('</string>',"",$fileUrl);*/

            if (substr($fileUrl, -3) == 'pdf')
            {
                $file = file_get_contents($fileUrl);
                $fileName = "pdfReport.pdf";//basename($fileUrl);

                $date = date("Ymdhis");
                if(file_put_contents("../../".Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName, $file))
                {
                    $response['code'] = '200';
                    $response['url'] =  Yii::$app->params['REPORTS']['REPORT_PATH'].Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName;
                    $this->setResponse($response);
                }
                else
                {
                    $response['code'] = '000';
                    $this->setResponse($response);
                }
            }
            else
            {
                $response['code'] = '-1';
                $this->setResponse($response);
            }
        }
        else{
            $response['code'] = '-2';
            $this->setResponse($response);
        }
    }

    public function actionCustomerreport_old()
    {
        $post = Yii::$app->request->post();
        if(isset($post['accessKey']) && isset($post['fromDate']) && isset($post['toDate']) && isset($post['RegNo']) && isset($post['fromPlanCode']) && isset($post['toPlanCode']) && isset($post['fromFundCode']) && isset($post['toFundCode']) && isset($post['fromUnitType']) && isset($post['toUnitType']) && isset($post['isProvision']) && isset($post['reportType']) )
        {
            $url = Yii::$app->params['REPORTS']['SOAP_SERVICE_URL']."?AccessKey=".$post['accessKey']."&fromDate=".$post['fromDate']."&toDate=".$post['toDate']."&RegNo=".$post['RegNo']."&fromPlanCode=".$post['toPlanCode']."&toPlanCode=".$post['toPlanCode']."&fromFundCode=".$post['fromFundCode']."&toFundCode=".$post['toFundCode']."&fromUnitType=".$post['fromUnitType']."&toUnitType=".$post['toUnitType']."&isProvision=".$post['isProvision']."&reportType=".$post['reportType'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $fileUrl = curl_exec($ch);
            curl_close($ch);

            $this->debug($fileUrl);exit;

            //$fileUrl = "http://www.urartuuniversity.com/content_images/pdf-sample.pdf";
            if (substr($fileUrl, -3) == 'pdf')
            {
                $file = file_get_contents($fileUrl);
                $fileName = basename($fileUrl);

                $date = date("Ymdhis");
                if(file_put_contents("../../".Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName, $file))
                {
                    $response['code'] = '200';
                    $response['url'] =  Yii::$app->params['REPORTS']['REPORT_PATH'].Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName;
                    $this->setResponse($response);
                }
                else
                {
                    $response['code'] = '000';
                    $this->setResponse($response);
                }
            }
            else
            {
                $response['code'] = '-1';
                $this->setResponse($response);
            }
        }
        else{
            $response['code'] = '-2';
            $this->setResponse($response);
        }
    }

    public function actionForgotpass()
    {
        $post = Yii::$app->request->post();

        if( empty($post['userEmail']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        if($this->model->forgotPass($post['userEmail']))
        {
            $this->model->addLog("forgot_password",$post['userEmail']." requested for forgot password");
            $response['code'] = "200";
        }
        $this->setResponse($response);
    }

    public function actionCodeverify()
    {
        $post = Yii::$app->request->post();
        if(empty($post['userEmail']) || empty($post['codeVerify']) || empty($post['newPass']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }
        if($this->model->validateEmail($post['userEmail']))
        {
            if($this->model->codeVarify($post['userEmail'],$post['codeVerify'],$post['newPass']))
            {
                $response['code'] = "200";
                $this->model->addLog("code_verify",$post['userEmail']." verified code successfully for forgot password");
            }
            else
            {
                $this->model->addLog("code_verify",$post['userEmail']." can not verify code for forgot password");
                $response['code'] = "403";
            }
        }
        else
        {
            $response['code'] = "403";
            $this->model->addLog("code_verify",$post['userEmail']." email can not be verified for forgot password");
        }
        $this->setResponse($response);
    }

    public function actionSalesentitygroup()
    {
        $post = Yii::$app->request->post();
        if( empty($post['groupCode']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }


        $response = $this->model->getSalesEntityGroup($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionSalesentitygroupdtl()
    {

        $response = $this->model->getSalesEntityGroupDtl();
        $this->setResponse($response);
    }

    public function actionCommissionstructure_mf()
    {
        $post = Yii::$app->request->post();

        $response = $this->model->getCommissionStructureMF($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionCommissionstructure_fl()
    {
        $post = Yii::$app->request->post();

        $response = $this->model->getCommissionStructureFL($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionCustomers_list()
    {
        $post = Yii::$app->request->post();

        $response = $this->model->getCustomersList($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionLogout()
    {
        $post = Yii::$app->request->post();
        $response = array();
        if( empty($post['authToken']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }


        $result = $this->model->logout($post['authToken']);

        foreach($result[0] as $res)
        {
            if($res == "TRUE")
            {
                $response['code'] = "200";
                //$response['message'] = "Login successfully";
            }
            if($res == "FALSE")
            {
                $response['code'] = "401";
                //$response['message'] = "Can not login, Try again";
            }
            if($res == "INACTIVE")
            {
                $response['code'] = "405";
                //$response['message'] = "INACTIVE";
            }
            if($res == "LOCK")
            {
                $response['code'] = "405";
//                $response['message'] = "LOCK";
            }
        }
        $this->setResponse($response);
    }

    public function actionChangedistributor()
    {
        $request = Yii::$app->request;
        $userEmail = $request->getBodyParam('userEmail');
        $dpCode = $request->getBodyParam('dpCode');

        if( empty($userEmail) || empty($dpCode) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $this->model->changeDistributor($userEmail,$dpCode);
        $response['code'] = "200";
        $this->setResponse($response);
    }

    public function actionAumrep()
    {
        $post = Yii::$app->request->post();
        if( empty($post['fromDate']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }
        $result = $this->model->getAumrep($post['fromDate']);

        //$result = "http://10.4.29.17:7778/reports/rwservlet?LEDGER&report=D:\imPro_11g\Forms\AGENT_HOLD.rep&&server=rep_standalone_2&DESNAME=D:\Oracle\Middleware\asinst_1\config\OHS\ohs1\htdocs\OIS-SOA\03-JUL-15-H8HASPY2BG03072015121613.pdf&DESFORMAT=PDF&DESTYPE=file&P_AS_ON_DATE=03-JUL-15&p_userid='WEB'&BUFFERS=9999";
        if($result)
        {
            $response['code'] = "200";
            $urlArray = explode("&",$result);
            foreach($urlArray as $uri)
            {
                $checkUri = substr($uri,0,7);
                if($checkUri == "DESNAME")
                {
                    $report = str_replace("DESNAME=","",$uri);
                }
            }
            if (substr($report, -3) == 'pdf')
            {
                $file = file_get_contents("'".$report."'");
                $fileName = "pdfReport.pdf";//basename($fileUrl);

                $date = date("Ymdhis");
                if(file_put_contents("../../".Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName, $file))
                {
                    $response['code'] = '200';
                    $response['url'] =  Yii::$app->params['REPORTS']['REPORT_PATH'].Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName;
                    $this->setResponse($response);
                }
                else
                {
                    $response['code'] = '000';
                    $this->setResponse($response);
                }
            }
            else
            {
                $response['code'] = '-1';
                $this->setResponse($response);
            }
        }
        else {
            $response['code'] = "403";
        }
        $this->setResponse($response);
    }

    public function actionDalrep()
    {
        $post = Yii::$app->request->post();
        if( empty($post['fromDate'])  && empty($post['toDate']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $result = $this->model->getDalrep($post['fromDate'],$post['toDate'],$post['p_ic']);
        //$result = "http://10.4.29.17:7778/reports/rwservlet?LEDGER&report=D:\imPro_11g\Forms\DAL_SAL_REP.rep&&server=rep_standalone_2&DESNAME=D:\Oracle\Middleware\asinst_1\config\OHS\ohs1\htdocs\OIS-SOA\03-JUL-15-K3UR9P8B9003072015121721.pdf&DESFORMAT=PDF&DESTYPE=file&P_FROM_DATE=03-JUL-15&P_TO_DATE=03-JUL-15&P_IC=MULTAN IC&p_userid='WEB'  ";
        if($result)
        {
            $response['code'] = "200";
            $urlArray = explode("&",$result);
            foreach($urlArray as $uri)
            {
                $checkUri = substr($uri,0,7);
                if($checkUri == "DESNAME")
                {
                   $report = str_replace("DESNAME=","",$uri);
                }
            }
            if (substr($report, -3) == 'pdf')
            {
                $file = file_get_contents("'".$report."'");
                $fileName = "pdfReport.pdf";//basename($fileUrl);

                $date = date("Ymdhis");
                if(file_put_contents("../../".Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName, $file))
                {
                    $response['code'] = '200';
                    $response['url'] =  Yii::$app->params['REPORTS']['REPORT_PATH'].Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName;
                    $this->setResponse($response);
                }
                else
                {
                    $response['code'] = '000';
                    $this->setResponse($response);
                }
            }
            else
            {
                $response['code'] = '-1';
                $this->setResponse($response);
            }
        }
        else {
            $response['code'] = "403";
        }
        $this->setResponse($response);
    }

    public function actionCprnrep()
    {
        $post = Yii::$app->request->post();

        if( empty($post['fromDate'])  && empty($post['toDate']) && empty($post['custAccCode']) && empty($post['fundCode']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }
        $result = $this->model->getCprnrep($post['fromDate'],$post['toDate'],$post['custAccCode'],$post['fundCode']);
        if($result)
        {
            $response['code'] = "200";
            $urlArray = explode("&",$result);
            foreach($urlArray as $uri)
            {
                $checkUri = substr($uri,0,7);
                if($checkUri == "DESNAME")
                {
                    $report = str_replace("DESNAME=","",$uri);
                }
            }
            if (substr($report, -3) == 'pdf')
            {
                $file = file_get_contents("'".$report."'");
                $fileName = "pdfReport.pdf";
                //basename($fileUrl);

                $date = date("Ymdhis");
                if(file_put_contents("../../".Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName, $file))
                {
                    $response['code'] = '200';
                    $response['url'] =  Yii::$app->params['REPORTS']['REPORT_PATH'].Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName;
                    $this->setResponse($response);
                }
                else
                {
                    $response['code'] = '000';
                    $this->setResponse($response);
                }
            }
            else
            {
                $response['code'] = '-1';
                $this->setResponse($response);
            }
        }
        else {
            $response['code'] = "403";
        }
        $this->setResponse($response);
    }

    public function actionAllgroupmembers()
    {
        $post = Yii::$app->request->post();
        if( empty($post['userCd']))
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $res = $this->model->getAllGroupMembers($post['userCd']);
        if($res)
        {
            $response['code'] = '200';
            $response['data'] = $res;
        }
        else
        {
            $response['code'] = '403';
        }
        $this->setResponse($response);
    }

    public function actionGroupcustomers()
    {
        $post = Yii::$app->request->post();
        if( empty($post['groupSno']))
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }

        $data = $this->model->getGroupCustomers($post['groupSno']);
        $response['code'] = '200';
        $brows = $this->model->getBusinessDate();
        $response['businessDate'] = $brows[0]['KEY_VALUE'];
        $response['data'] = $data;
        $this->setResponse($response);
    }

    public function actionGroupcustomersfundaum()
    {
        $post = Yii::$app->request->post();
        if( empty($post['groupSno']) || empty($post['fundCode']))
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }

        $data = $this->model->getGroupCustomerFundAum($post['groupSno'],$post['fundCode']);
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }

        public function actionAlloverfundaum()
    {
        $data = $this->model->getFundAum();
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }

    public function actionTransactiontrack()
    {
        $post = Yii::$app->request->post();
        if( empty($post['accountCode']) || empty($post['fromDate']) || empty($post['toDate']))
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $data = $this->model->getTransactionTrack($post['accountCode'],$post['fromDate'],$post['toDate']);
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }

    public function actionTransactionkeyword()
    {
        $post = Yii::$app->request->post();
        if( empty($post['keyword']) || empty($post['keyword1']) || empty($post['keyword2']))
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $data = $this->model->getTransactionKeyword($post['keyword'],$post['keyword1'],$post['keyword2'],$post['keyword3']);
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }

    public function actionInflowoutflow()
    {
        $post = Yii::$app->request->post();
        if( empty($post['groupSno']) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $data = $this->model->getInflowOutflow($post['groupSno']);
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }

    public function actionNotifications()
    {
        $post = Yii::$app->request->post();
        if( empty($post['userCd']) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $data = $this->model->getUserNotifications($post['userCd']);
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }

    public function actionNotificationread()
    {
        $request = Yii::$app->request;
        $notificationId = $request->getBodyParam('notificationId');
        if( empty($notificationId) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $response['code'] = '200';
        $this->model->getNotificationRead($notificationId);
        $this->setResponse($response);
    }


    public function debug($array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }

    public function actionContactus()
    {
        $post = Yii::$app->request->post();
        if( empty($post['msg']) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $body = "<table border='0' width='50%'>
                <tr><td>".$post['msg']."</td></tr>
                </table>";
        //echo $body;exit;

        \Yii::$app->mail->compose('your_view')
            ->setFrom([\Yii::$app->params['supportEmail'] => 'Test Mail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Contact Us' )
            ->setTextBody($body)
            ->send();
    }
}
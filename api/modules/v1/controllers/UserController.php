<?php
namespace api\modules\v1\controllers;
use ocilib\OciLib;
use Yii;
use \mongosoft\soapclient\UblClient;

require_once('../../vendor/ocilib/OciLib.php');

/**
 * User Controller API
 *
 */
class UserController extends AuthController
{
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
            $this->model->addLog("change_password",$post['userEmail']." changed password",$post['sessionSno']);
            $response['code'] = "200";
        }

        $this->setResponse($response);
    }

    public function actionCgtpre()
    {
        $post = Yii::$app->request->post();
        $response = array();

        if(empty($post['custAccCode']) || empty($post['planCode']) || empty($post['unitType']) || empty($post['typeValue']))
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $post['amount'] = (isset($post['amount']) ? $post['amount'] : '');
        $post['unitPercent'] = (isset($post['unitPercent']) ? $post['unitPercent'] : '');
        $post['unit'] = (isset($post['unit']) ? $post['unit'] : '');

        $ociLib = new OciLib();
        $c = $ociLib->ociConnect(Yii::$app->params['DB_USER'], Yii::$app->params['DB_PASS'], Yii::$app->params['DB_NAME']);

        $response['data'] = $this->model->getCgtPre($post['custAccCode'],$post['planCode'],$post['unitType'],$post['typeValue'],$post['amount'],$post['unitPercent'],$post['unit'],$post['navDate'],$c);
        if($response['data'] !== false)
        {
            $this->model->addLog("view_report",$post['groupCode']." view CGT pre report",$post['sessionSno']);
            $response['code'] = "200";
        }
        $this->setResponse($response);
    }

    public function actionCgtpost()
    {
        $post = Yii::$app->request->post();
        $response = array();

        if(empty($post['custAccCode']) || empty($post['transactionSno']) || empty($post['transactionType']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $ociLib = new OciLib();
        $c = $ociLib->ociConnect(Yii::$app->params['DB_USER'], Yii::$app->params['DB_PASS'], Yii::$app->params['DB_NAME']);

        $response['data'] = $this->model->getCgtPost($post['custAccCode'],$post['transactionSno'],$post['transactionType'],$c);
        if($response['data'] !== false)
        {
            $this->model->addLog("view_report",$post['groupCode']." view CGT post report",$post['sessionSno']);
            $response['code'] = "200";
        }
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
            $this->model->addLog("view_report",$post['groupCode']." view Customer report",$post['sessionSno']);
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
        if(!empty($response))
        {
            $this->model->addLog("view_report",$post['groupCode']." view load earned report",$post['sessionSno']);
        }
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
        if(!empty($res))
        {
            $this->model->addLog("view_report",$post['groupCode']." view balance detail report",$post['sessionSno']);
        }
        $this->setResponse($res);
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
        if(isset($post['fromDate']) && !empty($post['fromDate']) && isset($post['toDate']) && !empty($post['toDate']) && isset($post['RegNo']) && !empty($post['RegNo']) && isset($post['toPlanCode']) && !empty($post['toPlanCode']) && isset($post['fromPlanCode']) && !empty($post['fromPlanCode']) && isset($post['fromFundCode']) && !empty($post['fromFundCode']) && isset($post['toFundCode']) && !empty($post['toFundCode']) && isset($post['fromUnitType']) && !empty($post['fromUnitType']) && isset($post['toUnitType']) && !empty($post['toUnitType']) && isset($post['isProvision']) && !empty($post['isProvision']) && isset($post['reportType']) && !empty($post['reportType']))
        {
            $post = Yii::$app->request->post();

            $url = Yii::$app->params['REPORTS']['CUSTOMER_SERVICE_URL']."GetAccountStatement?AccessKey=".$post['accessKey']."&fromDate=".$post['fromDate']."&toDate=".$post['toDate']."&RegNo=".$post['RegNo']."&fromPlanCode=".$post['toPlanCode']."&toPlanCode=".$post['toPlanCode']."&fromFundCode=".$post['fromFundCode']."&toFundCode=".$post['toFundCode']."&fromUnitType=".$post['fromUnitType']."&toUnitType=".$post['toUnitType']."&isProvision=".$post['isProvision']."&reportType=".$post['reportType'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $fileUrl = curl_exec($ch);
            curl_close($ch);
            $fileUrl = substr($fileUrl,76,ceil(strlen($fileUrl) - 9 ));
            $fileUrl = str_replace('</string>',"",$fileUrl);

            if (substr($fileUrl, -3) == 'pdf')
            {
                $file = file_get_contents($fileUrl);
                $fileName = "pdfReport.pdf";

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
        $d=date ("d");
        $m=date ("m");
        $y=date ("Y");
        $t=time();
        $dmt=$d+$m+$y+$t;
        $ran= rand(0,10000000);
        $dmtran= $dmt+$ran;
        $un=  uniqid();
        $dmtun = $dmt.$un;
        $mdun = md5($dmtran.$un);
        $sort=substr($mdun, 16); // if you want sort length code.
        $mdun = substr($mdun,0,19);
        try{
            if($this->model->forgotPass($post['userEmail'],$mdun))
            {
                $this->model->addLog("forgot_password",$post['userEmail']." requested for forgot password");
                $body = 'Dear '.$post['userEmail'].'<br />It seems you may have forgotten your password!<p>Our system has recorded your attempt to login to UBL Funds Smart Partner Portal on '.date("l, F d, Y").' at '.date("H:i:s A");
                $body.= 'In case this was not you, please let us know immediately. < p>If you have forgotten your password, click < a href="'.Yii::$app->params['VERIFY_CODE_URL'].'" >here< /a>< p> your OTP code is < p> '.$mdun.' < p> to reset it and have the new password sent to your registered email address, or contact us in case of other issues.';

                //$this->sendEmail('UBL FM - Forgot password' ,$body,$post['userEmail'],'forgot password');
                $response['code'] = "200";
                $this->setResponse($response);
            }

        }
        catch (Exception $e)
        {
            return  $e->getMessage();
        }
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
        if(!empty($response))
        {
            $this->model->addLog("view_report",$post['groupCode']." view sales entity report",$post['sessionSno']);
        }
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
        if(!empty($response))
        {
            $this->model->addLog("view_report",$post['groupCode']." view commission structure management fees report",$post['sessionSno']);
        }
        $this->setResponse($response);
    }

    public function actionCommissionstructure_fl()
    {
        $post = Yii::$app->request->post();
        $response = $this->model->getCommissionStructureFL($post['groupCode']);
        if(!empty($response))
        {
            $this->model->addLog("view_report",$post['groupCode']." view commission structure frontend load report",$post['sessionSno']);
        }
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
        $this->authToken = $this->fetchAccessToken();

        if( empty($this->authToken) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }
        $this->model->logout($this->authToken);
        $response['code'] = "200";

        $this->model->addLog("logged_out",$post['groupCode']." logged out",$post['sessionSno']);
        $body = $this->logoutMailBody($post['userEmail']);
        $this->sendEmail('logged out',$body,$post['userEmail'],'logout');
        $this->setResponse($response);
    }

    public function actionChangedistributor()
    {
        $request = Yii::$app->request;
        $userEmail = $request->getBodyParam('userEmail');
        $dpCode = $request->getBodyParam('dpCode');
        $sessionSno = $request->getBodyParam('sessionSno');

        if( empty($userEmail) || empty($dpCode) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
        }

        $this->model->changeDistributor($userEmail,$dpCode);

        $this->model->addLog("change_distributor",$userEmail." changed distributor to ".$dpCode,$sessionSno);
        $response['code'] = "200";
        $this->setResponse($response);
    }

    public function requestToSavePdf($link)
    {
        $link = trim($link);
        $link = str_replace ( ' ', '%20', $link);
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(!curl_exec($ch)){
            die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }
        curl_close($ch);
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
        $this->requestToSavePdf($result);

        if($result)
        {
            $response['code'] = "200";
            $this->savePdfToLocal($result);
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

        $this->requestToSavePdf($result);

        if($result){
            $response['code'] = "200";
            $this->savePdfToLocal($result);
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
        $this->requestToSavePdf($result);

        if($result){
            $response['code'] = "200";
            $this->savePdfToLocal($result);
        }
        else {
            $response['code'] = "403";
        }
        $this->setResponse($response);
    }

    protected function savePdfToLocal($result)
    {
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
            $pdfStr = Yii::$app->params['DISTRIBUTOR_REPORTS_SERVER'];
            $reportArray = explode("%5C",urlencode($report));
            $flag = 0;
            foreach($reportArray as $ra)
            {
                if(substr($ra,0,7) == "OIS-SOA" || $flag==1)
                {
                    $flag = 1;
                    $pdfStr.="/".$ra;
                }
            }
            $report = $this->filterPdfStr($pdfStr);

            $fileName = "pdfReport.pdf";
            $date = date("Ymdhis");

            $content = file_get_contents($report);
            $save_to = "../../".Yii::$app->params['REPORTS']['REPORT_FOLDER']."/".$date.$fileName;

            if(file_put_contents($save_to, $content))
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

    protected function filterPdfStr($pdfStr)
    {
        $pdfStr = urldecode(str_replace("%","%2F",$pdfStr));
        $pdfStr = urldecode(str_replace("//OIS-SOA","/OIS-SOA",$pdfStr));

        $report = $pdfStr;
        $report = str_replace("%2F%5C","%2F",urlencode($report));
        $report = urldecode($report);
        return $report;
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
        $this->model->addLog("view_report",$post['groupCode']." view group customer fund aum report",$post['sessionSno']);
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
        $this->model->addLog("view_report",$post['groupCode']." view transaction track report",$post['sessionSno']);
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
        $this->model->addLog("view_report",$post['groupCode']." view transaction key word report",$post['sessionSno']);
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
        $this->model->addLog("view_report",$post['groupCode']." view inflow outflow report",$post['sessionSno']);
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
        $userCd = $request->getBodyParam('userCd');
        if( empty($notificationId) || empty($userCd) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $response['code'] = '200';
        $this->model->getNotificationRead($notificationId,$userCd);
        $this->setResponse($response);
    }

    public function actionAllnotifications()
    {
        $data = $this->model->getAllNotifications();
        $response['code'] = '200';
        $response['data'] = $data;
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
        if( empty($post['msg']) || empty($post['userEmail']) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $body = 'Dear <span class="partnerName">'.$post['userEmail'].'</span><br /><br />
                Thank you for contacting us through UBL Funds Smart Partner Portal on <span class="logoutT">'.date("l, F d, Y").' </span>at <span class="logoutD">'.date('H:i:s A');
        //$this->sendEmailToAdmin('Contact Us from '.$post['userEmail'],$post['msg']);
        //$this->sendEmail('Contact Us',$body,$post['userEmail'],'Contact Us');

        $response['code'] = '200';
        $this->setResponse($response);
    }

    public function actionProfilepicture()
    {
        $post = Yii::$app->request->post();
        if( empty($post['userCd']) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }

        $path = '../../'.Yii::$app->params['PROFILE_PIC_FOLDER'].'/';
        $viewPath = Yii::$app->params['PROFILE_PIC_FOLDER'].'/'. $post['userCd'].'_'.$_FILES['myFile']['name'];
        $location = $path . $post['userCd'].'_'.$_FILES['myFile']['name'];
        move_uploaded_file($_FILES['myFile']['tmp_name'], $location);
        $response['code'] = '200';
        $response['data'] = $viewPath;
        $this->model->addLog("change_profile_picture",$post['groupCode']." changed profile picture",$post['sessionSno']);
        $this->setResponse($response);
    }

    public function actionGrouptransaction()
    {
        $post = Yii::$app->request->post();
        if( empty($post['userCd']) || empty($post['groupSno']) )
        {
            $response['code'] = '400';
            $this->setResponse($response);
        }
        $data = $this->model->getGroupTransactions($post['userCd'],$post['groupSno']);
        $response['code'] = '200';
        $response['data'] = $data;
        $this->setResponse($response);
    }
}
<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use api\modules\v1\models\User;
use \mongosoft\soapclient\Client;

/**
 * User Controller API
 *
 */
class UserController extends AuthController
{
    public $modelClass = 'api\modules\v1\models\User';

    public function actionLogin()
    {
        $post = Yii::$app->request->post();
        $response = array();

        if(empty($post['userEmail']) || empty($post['userPass']))
        {
            $response['code'] = "400";
            $this->setResponse($response);
            exit;
        }
        $model = new User();
        $result = $model->login($post['userEmail'],$post['userPass']);

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

    public function actionChangepassword()
    {
        $post = Yii::$app->request->post();
        //$result = $model->login('ashfaqhassan@ublfunds.com','ORACLE');
        $response = array();
        if(empty($post['userEmail']) || empty($post['userPass']) || empty($post['newPass']))
        {
            $response['code'] = "400";
            $this->setResponse($response);
            exit;
        }

        $model = new User();
        $result = $model->changePassword($post['userEmail'],$post['userPass'],$post['newPass']);
        //$this->setResponse($result);
        $response['code'] = "401";
        if($result == "TRUE")
        {
            $model->addLog("change_password",$post['userEmail']." changed password");
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
            exit;
        }

        $post['amount'] = (isset($post['amount']) ? $post['amount'] : '');
        $post['unitPercent'] = (isset($post['unitPercent']) ? $post['unitPercent'] : '');
        $post['unit'] = (isset($post['unit']) ? $post['unit'] : '');

        $model = new User();
        $c= $this->ociConnect();
        $response['data'] = $model->getCgtPre($post['custAccCode'],$post['planCode'],$post['unitType'],$post['typeValue'],$post['amount'],$post['unitPercent'],$post['unit'],$post['navDate'],$c);
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
            exit;
        }

        $model = new User();
        $c = $this->ociConnect();

        $response['data'] = $model->getCgtPost($post['custAccCode'],$post['transactionSno'],$post['transactionType'],$c);
        $this->setResponse($response);
    }

    public function actionCustomers_data()
    {
        $post = Yii::$app->request->post();
        $model = new User();

        $userCd = $model->getUserCdByToken($this->authToken);
        $accountCode = (isset($post['accountCode']) ? $post['accountCode'] : "");
        $accountName = (isset($post['accountName']) ? $post['accountName'] : "");
        $cnic = (isset($post['cnic']) ? $post['cnic'] : "");
        $email= (isset($post['email']) ? $post['email'] : "");
        $cgtExempted = (isset($post['cgtExempted']) ? $post['cgtExempted'] : "");

        $zakatExempted = (isset($post['zakatExempted']) ? $post['zakatExempted'] : "");
        $phone = (isset($post['phone']) ? $post['phone'] : "");

        $response = $model->getCustomersList($userCd,$accountCode,$accountName,$cnic,$email,$cgtExempted,$zakatExempted,$phone);
        if($response)
        {
            $this->setResponse($response);
        }
        else
        {
            $response['code'] = "403";
            $this->setResponse($response);
            exit;
        }
    }

    public function actionLoadearned()
    {
        $post = Yii::$app->request->post();
        if(empty($post['groupSno']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
            exit;
        }
        $model = new User();
        $response = $model->getEarnedValue($post['groupSno']);
        $this->setResponse($response);
    }

    public function actionTest()
    {

        $post['accessKey'] = "p0n-k@";
        $post['accountNo'] = "00008903-1";
        $post['customerId'] = "00008903";
        $post['channel'] = "web";
        $post['type1'] = "";
        $post['availableHolding'] = "";
        $post['transactionType'] = "";

        $url = "http://124.29.246.107:1530/Service.asmx/GetBalanceDetail?accessKey=".$post['accessKey']."&accountNo=".$post['accountNo']."&customerId=".$post['customerId']."&Channel=".$post['channel']."&type1=".$post['type1']."&availableHolding=".$post['availableHolding']."&transactionType=".$post['transactionType'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $simple = curl_exec($ch);
        curl_close($ch);
        //echo $simple;exit;

        //$simple = "<para><note>simple note</note></para>";
        $p = xml_parser_create();
        xml_parse_into_struct($p, $simple, $vals, $index);
        xml_parser_free($p);
        echo "Index array\n";
        print_r($index['FUNDCODE']);
        echo "\nVals array\n";
        //print_r($vals);
        exit;

        $xml = simplexml_load_string($fileUrl);
        if ($xml === false) {
            echo "Failed loading XML: ";
            foreach(libxml_get_errors() as $error) {
                echo "<br>", $error->message;
            }
        } else {
            print_r($xml);
        }
        exit;

        $url = "http://124.29.246.107:1530/Service.asmx?op=GetBalanceDetail?accessKey=p0n-k@&accountNo=00008903-1&customerId=00008903&Channel=web&type1=''&availableHolding=''&transactionType=''";

        $client = new \mongosoft\soapclient\Client([
            'url' => $url,//'http://www.webservicex.com/globalweather.asmx?wsdl',
            //'url' => "http://124.29.246.107:1530/Service.asmx?wsdl",
        ]);
        //$client = Yii::$app->siteApi;
        //var_dump($client);exit;
        $res = $client->GetBalanceDetail('accessKey=p0n-k@','accountNo=00008903-1','customerId=00008903','Channel=web','type1=""','availableHolding=""','transactionType=""');
        print_r($res);
        exit;



        libxml_use_internal_errors(true);
        $myXMLData = '<?xml version="1.0" encoding="UTF-8"?>
                    <note>
                      <to>Tove</to>
                      <from>Jani</from>
                      <heading>Reminder</heading>
                      <body>Dont forget me this weekend!</body>
                    </note>';

        $url = "http://124.29.246.107:1530/Service.asmx?op=GetBalanceDetail?accessKey=p0n-k@&accountNo=00008903-1&customerId=00008903&Channel=web&type1=''&availableHolding=''&transactionType=''";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $fileUrl = curl_exec($ch);
        curl_close($ch);
        echo $fileUrl;exit;
       // $file = file_get_contents($fileUrl);
        $fileName = "abc2.xml";

        if(file_put_contents($fileName, $fileUrl))
        {
            if (file_exists("http://localhost/ublfm/api/web/".$fileName)) {
                $xml = simplexml_load_file("http://localhost/ublfm/api/web/".$fileName);

                print_r($xml);
            } else {
                exit('Failed to open test.xml.');
            }
            $xml=simplexml_load_file("http://localhost/ublfm/api/web/".$fileName) or die("Error: Cannot create object");
            print_r($xml);
            echo "yes";
        }




        $post['accessKey'] = "AM";
        $post['fromDate'] = "2-jun-2015";
        $post['toDate'] = "20-jun-2015";
        $post['RegNo'] = "00024554-1";
        $post['fromPlanCode'] = "000";
        $post['toPlanCode'] = "999";
        $post['fromFundCode'] = "000";
        $post['toFundCode'] = "999";
        $post['fromUnitType'] = "000";
        $post['toUnitType'] = "999";
        $post['isProvision'] = "Y";
        $post['reportType'] = "7";
        $this->debug($post);exit;

        //$url = 'http://www.webservicex.com/globalweather.asmx?wsdl';
        $url = "http://124.29.246.107:1530/Service.asmx/?wsdl";//GetAccountStatement?AccessKey=AM&fromDate=2-jun-2015&toDate=20-jun-2015&RegNo=00024554-1&fromPlanCode=000&toPlanCode=999&fromFundCode=000&toFundCode=999&fromUnitType=000&toUnitType=999&isProvision=Y&reportType=7";
        /*$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        echo $data;
        exit;*/

        $client = new \mongosoft\soapclient\Client([
            'url' => $url,//'http://www.webservicex.com/globalweather.asmx?wsdl',
            //'url' => "http://124.29.246.107:1530/Service.asmx?wsdl",
        ]);
        //$client = Yii::$app->siteApi;
        //var_dump($client);exit;
        $res = $client->GetAccountStatement($post['accessKey'],$post['fromDate'],$post['toDate'],$post['RegNo'],$post['fromPlanCode'],$post['toPlanCode'],$post['fromFundCode'],$post['toFundCode'],$post['fromUnitType'],$post['toUnitType'],$post['isProvision'],$post['reportType']);
        print_r($res);
        exit;
        //Yii::$app->db->createCommand("SET time_zone = '+5:00'")->execute();exit;
        $fileUrl = Yii::$app->params['PDF_REPORT_PATH'];
        //"http://www.urartuuniversity.com/content_images/pdf-sample.pdf";
        //echo "download:".Yii::$app->params['PDF_REPORT_DOWNLOAD_PATH'];exit;

        $file = file_get_contents($fileUrl);
        $fileName = basename($fileUrl);


        if(file_put_contents("../../".Yii::$app->params['REPORTS']['PDF_REPORT_DOWNLOAD_PATH']."/".$fileName, $file))
        {
            echo "yes";
            //return Setting::getFileDownloadPath() .$fileName;
        }
        else
        {
            echo "not";
        }

        /*$post = Yii::$app->request->post();
        $model = new User();
        $response = $model->getEarnedValue();
        $this->setResponse($response);exit;
        $this->debug($res);*/
        exit;
        /*\Yii::$app->mail->compose('your_view')
            ->setFrom([\Yii::$app->params['supportEmail'] => 'Test Mail'])
            ->setTo('mastermind_mohsin@hotmail.com')
            ->setSubject('UBL FM - Forgot password' )
            ->setTextBody("Hi Mohsin")
            ->send();*/
    }

    public function actionCustomerreport()
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
        $model = new User();
        if( empty($post['userEmail']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
            exit;
        }

        if($model->forgotPass($post['userEmail']))
        {
            $model->addLog("forgot_password",$post['userEmail']." requested for forgot password");
            $response['code'] = "200";
        }
        $this->setResponse($response);
    }

    public function actionCodeverify()
    {
        $post = Yii::$app->request->post();
        $model = new User();

        if(empty($post['userEmail']) || empty($post['codeVerify']) || empty($post['newPass']) )
        {
            $response['code'] = "400";
            $this->setResponse($response);
            exit;
        }
        if($model->validateEmail($post['userEmail']))
        {
            if($model->codeVarify($post['userEmail'],$post['codeVerify'],$post['newPass']))
            {
                $response['code'] = "200";
                $model->addLog("code_verify",$post['userEmail']." verified code successfully for forgot password");
            }
            else
            {
                $model->addLog("code_verify",$post['userEmail']." can not verify code for forgot password");
                $response['code'] = "403";
            }
        }
        else
        {
            $response['code'] = "403";
            $model->addLog("code_verify",$post['userEmail']." email can not be verified for forgot password");
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
            exit;
        }

        $model = new User();
        $response = $model->getSalesEntityGroup($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionSalesentitygroupdtl()
    {
        $model = new User();
        $response = $model->getSalesEntityGroupDtl();
        $this->setResponse($response);
    }

    public function actionCommissionstructure_mf()
    {
        $post = Yii::$app->request->post();
        $model = new User();
        $response = $model->getCommissionStructureMF($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionCommissionstructure_fl()
    {
        $post = Yii::$app->request->post();
        $model = new User();
        $response = $model->getCommissionStructureFL($post['groupCode']);
        $this->setResponse($response);
    }

    public function actionCustomers_list()
    {
        $post = Yii::$app->request->post();
        $model = new User();
        $response = $model->getCustomersList($post['groupCode']);
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
            exit;
        }

        $model = new User();
        $result = $model->logout($post['authToken']);

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

    public function changeDistributor()
    {
        $post = Yii::$app->request->post();
        $model = new User();
        $response = $model->changeDistributor($post['email'],$post['dp_code']);
        if($response)
        {
            $response['code'] = "200";
        }
        else {
            $response['code'] = "403";
        }

        $this->setResponse($response);
    }

    public function ociConnect()
    {
        $c = oci_connect(Yii::$app->params['DB_USER'], Yii::$app->params['DB_PASS'], Yii::$app->params['DB_NAME']);
        return $c;
    }

    public function debug($array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }
}
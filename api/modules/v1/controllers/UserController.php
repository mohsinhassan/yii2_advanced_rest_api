<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use api\modules\v1\models\User;

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
        $response['code'] = "200";

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

        /*$post['amount'] = (isset($post['amount']) ? $post['amount'] : 0);
        $post['unitPercent'] = (isset($post['unitPercent']) ? $post['unitPercent'] : 0);
        $post['unit'] = (isset($post['unit']) ? $post['unit'] : 0);*/

        $model = new User();
        $c= $this->ociConnect();

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
        //Yii::$app->db->createCommand("SET time_zone = '+5:00'")->execute();exit;
        $fileUrl = Yii::$app->params['PDF_REPORT_PATH'];
        //"http://www.urartuuniversity.com/content_images/pdf-sample.pdf";
        //echo "download:".Yii::$app->params['PDF_REPORT_DOWNLOAD_PATH'];exit;

        $file = file_get_contents($fileUrl);
        $fileName = basename($fileUrl);
        $file = file_get_contents($fileUrl);

        if(file_put_contents("../../".Yii::$app->params['PDF_REPORT_DOWNLOAD_PATH']."/".$fileName, $file))
        {
            echo "yes";
            return Setting::getFileDownloadPath() .$fileName;
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
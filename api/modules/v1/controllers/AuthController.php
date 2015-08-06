<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use \api\modules\v1\models\User;
use encryptdecrypt\EncryptDecrypt;
require_once('../../vendor/encryptdecrypt/EncryptDecrypt.php');

/**
 * User Controller API
 *
 */

class AuthController extends ActiveController
{
    protected $model;
    public function init() {
        parent::init();
        $this->model = new User();
    }
    protected $authToken;
    public $modelClass = 'api\modules\v1\models\AuthUser';

    public function beforeAction($action){
        $post = Yii::$app->request->post();
        $urlArray = explode("/",$_SERVER["REQUEST_URI"]);
        $urlStr = $urlArray[count($urlArray)-1];

        if(in_array( $urlStr,Yii::$app->params['BY_PASS_ACTIONS']))
        {
            return true;
        }

        $this->authToken = $this->fetchAccessToken();

        if(!isset($this->authToken) || empty($this->authToken))
        {
            if(empty($post['userEmail']) || empty($post['userPass']))
            {
                $response = "403";
                $this->setResponse($response);
                exit;
                //goto login screen
            }
            elseif($urlStr == 'login')
            {
                $result = $this->model->login($post['userEmail'],$post['userPass']);
                foreach($result[0] as $res)
                {
                    if($res == "TRUE")
                    {
                        $rows = $this->model->getUserCdGroupCode($post['userEmail']);
                        $userCd = $rows[0]['USER_CD'];
                        $groupCode = $rows[0]['GROUP_CODE'];
                        $tokenCode = $this->createAccessToken($userCd);
                        if($this->model->saveAccessToken($tokenCode,$userCd))
                        {
                            $sessionSno = $this->model->getUserSessionSno($tokenCode);
                            $this->model->addLog("login",$post['userEmail']." logged in",$sessionSno);
                            $userPrivs = $this->model->getUserPrivs($userCd);

                            $response['code'] = "200";
                            $response['authToken'] = $tokenCode;
                            $response['groupCode'] = $groupCode;
                            $response['sessionSno'] = $sessionSno;
                            $response['userCd'] = $userCd;
                            $response['userPrivs'] = $userPrivs;

                            //////sending email code ///////////////

                            $body = $this->loginMailBody($post['userEmail']);
                            //$this->sendEmail('Login',$body,"mastermind_mohsin@hotmail.com",'login');
                        }
                    }
                    if($res == "FALSE")
                    {
                        $response['code'] = "401";
                    }
                    if($res == "INACTIVE")
                    {
                        $response['code'] = "405";
                    }
                    if($res == "LOCK")
                    {
                        $response['code'] = "405";
                    }
                    $this->setResponse($response);
                    exit;
                }
            }
        }
        else
        {
            //session sno is necessary for logs and should pass in all calls
            if(!isset($post['sessionSno']) || empty($post['sessionSno']))
            {   $response['code'] = "007";
                $this->setResponse($response);
                exit;
            }
            $userCd = $this->fetchUserCd();
            if($this->model->checkAccessToken($userCd,$this->authToken,$post['sessionSno']))
            {
                $this->model->refreshToken($userCd,$this->authToken);
                return true;
            }
            else
            {
                $response['code'] = "006";
                $this->setResponse($response);
                exit;
                //goto login screen
            }
        }
    }

    private function fetchUserCd()
    {
        $key = $this->model->getDecryptKey();
        $enc = new EncryptDecrypt();

        $decode = $enc->simple_decrypt($this->authToken,$key);
        $tokenPieces = explode("|",$decode);
        if(isset($tokenPieces[0]))
        {
            return $tokenPieces[0];
        }
        else
        {
            return false;
        }
    }
    private function createAccessToken($userCd)
    {
        $key = $this->model->getDecryptKey();
        $data = $userCd."|".date("Ymd")." ".time();
        $enc = new EncryptDecrypt();
        $tokenCode =  $enc->simple_encrypt($data,$key);
        $tokenCode = str_replace("\/","/",$tokenCode);
        return $tokenCode;
    }

    protected function fetchAccessToken()
    {
        $headArray = getallheaders();
        foreach ($headArray as $name => $value)
        {
            if($name == 'authToken')
            {
                return $value;
                break;
            }
        }
    }

    public function setResponse($response)
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:  {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            exit(0);
        }

        $jsonResponse  = json_encode($response);
        echo $jsonResponse;
        exit;
    }
    protected function loginMailBody($email)
    {
        $body = 'Dear <span >'.$email.'</span><br />
        Our system has recorded your <span class="logouttxt">login</span> to UBL Funds Smart Partner Portal
        on <span>'.date("l, F d, Y").' </span>at <span >'.date("H:i:s A");
        return $body;
    }

    protected function logoutMailBody($email)
    {
        $body = '
        Dear <span >'.$email.'</span><br />
Thank you for visiting UBL Funds Smart Partner Portal. Our systen recorded your <span class="logouttxt">logout</span>
    on <span >'.date("l, F d, Y").' </span>at <span >'.date("H:i:s A");
        return $body;
    }
    protected function getMailBodyHead($mailAction)
    {
        $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>'.$mailAction.'</title></head>';
        return $body;
    }

    protected function getMailBodyFoot()
    {
        $body = '</span> In case you did not visit at that time, Please let us know immediately<br /><br />
        We hope you enjoyed the experience of convenience!<br /><br />
        Sincerely,'.'<br />
        UBL Funds Smart Partner Portal Team
        </p>
        </p>
        <div style="bottom: 0; width: 100%; float: left;">
        <div style="float:left; width: 80%; margin-left: 10%; margin-top: 30px; margin-bottom: 10px;">
        <span style="float:left; width: 33%; text-align: center; border-right: 1px solid #6c6c6c; color: #6c6c6c; font-size: 22px; font-weight: bold;">Call 0800 00026</span>
        <span style="float:left; width: 33%; text-align: center; border-right: 1px solid #6c6c6c; color: #6c6c6c; font-size: 22px; font-weight: bold;">www.UBLFunds.com</span>
        <span style="float:left; width: 33%; text-align: center; border-right: 1px solid #6c6c6c; color: #6c6c6c; font-size: 22px; font-weight: bold;">info@UBLFunds.com</span>
        </div>
        <span style="background:#004684; float:left;color: #fff; padding: 10px;">DISCLAIMER:
        This is an aut-generated email. Please do not reply to it. If you find any discrepancy in the information available in you online account, please inform us immediately by writing to us at info@UBLFunds.com or by calling us at 0800-00026</span>
        </div>
        </div>
        </body>
        </html>';
        return $body;
    }

    protected function sendEmail($subject,$bodyMid,$to,$mailAction='')
    {
        $body = $this->getMailBodyHead($mailAction);
        $body .= $bodyMid;
        echo $body .= $this->getMailBodyFoot();exit;

        $message = \Yii::$app->mail->compose('common_email_view', ['imageFileName1' => Yii::$app->params['REPORTS']['REPORT_PATH'].'api/web/images/logo1.png'], ['imageFileName2' => Yii::$app->params['REPORTS']['REPORT_PATH'].'api/web/images/logo2.png'], ['imageFileName3' => Yii::$app->params['REPORTS']['REPORT_PATH'].'api/web/images/logo3.png'], ['imageFileName4' => Yii::$app->params['REPORTS']['REPORT_PATH'].'api/web/images/logo4.png'])
            ->setFrom([\Yii::$app->params['supportEmail'] => 'ubl fm team'])
            ->setTo($to)
            ->setSubject($subject)
            ->setHtmlBody($body);
        $message->send();
    }
    protected function sendEmailToAdmin($subject,$body)
    {
         \Yii::$app->mail->compose('common_email_view')
            ->setFrom([\Yii::$app->params['supportEmail'] => 'Test Mail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject($subject)
            ->setHtmlBody($body)
             ->send();
    }
}
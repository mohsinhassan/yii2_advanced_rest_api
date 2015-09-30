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
    public function init()
    {
        parent::init();
        $this->model = new User();
        date_default_timezone_set('Asia/Karachi');
    }
    protected $authToken;
    public $modelClass = 'api\modules\v1\models\User';

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
                        $rows = $this->model->getUserProfileData($post['userEmail']);
                        $userCd = $rows[0]['USER_CD'];
                        $groupCode = $rows[0]['GROUP_CODE'];

                        if((empty($groupCode)) || (!$this->model->getCheckGroupMembers($userCd,$groupCode)))
                        {
                            $groupData = $this->model->getAllGroupMembers($userCd);
                            if(empty($groupData[0]['GROUP_SNO']) || !isset($groupData[0]['GROUP_CODE']))
                            {
                                // Distributor not available under logged in user or invalid distributor.
                                $response['code'] = "005";
                                $this->setResponse($response);
                                exit;
                            }
                            $this->model->changeDistributor($post['userEmail'],$groupData[0]['GROUP_CODE']);
                            $rows = $this->model->getUserProfileData($post['userEmail']);
                            $groupCode = $rows[0]['GROUP_CODE'];
                        }

                        $userName = $rows[0]['USER_NAME'];
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
                            $response['userName'] = $userName;
                            $response['userPrivs'] = $userPrivs;

                            //////sending email code ///////////////

                            $body = $this->loginMailBody();
                            $this->sendEmail('Login',$body,$post['userEmail'],'login');
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
                //invalid access token or session sno
                $response['code'] = "006";
                $this->setResponse($response);
                exit;
                //goto login screen
            }
        }
    }

    protected function fetchUserCd()
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

    protected function setResponse($response)
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
    protected function loginMailBody()
    {
		date_default_timezone_set('Asia/Karachi');
        $body = '<br />Dear <span >Partner,</span><br /><br />
        Our system has recorded your <span class="logouttxt">login</span> to UBL Funds Smart Partner Portal
        on <span>'.date("l, F d, Y").' </span>at <span >'.date("h:i:s A").".</span> In case this was not you, please let us know immediately.<br /><br />
        We hope you enjoyed the experience of convenience!<br /><br />";

        return $body;
    }

    protected function logoutMailBody()
    {
		date_default_timezone_set('Asia/Karachi');
        $body = '<br />Dear <span >Partner,</span><br /><br />Thank you for visiting UBL Funds Smart Partner Portal. Our system recorded your <span class="logouttxt">logout</span>
    on <span >'.date("l, F d, Y").' </span>at <span >'.date("h:i:s A").".</span> In case this was not you, please let us know immediately.<br /><br />
        We hope you enjoyed the experience of convenience!";
        return $body;
    }
    protected function getMailBodyHead()
    {
        $body = '<head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <title>Contacting</title>
					
					</head>
                            <body>
							<div class=" margin:0 auto; width:80%;">
                                    <div style=" float:left; width: 100%;">
                                    <span style="float: left; width: 100%; height: 435px;">
									<img alt="Welcome" src="'.Yii::$app->params['EMAIL_IMAGE_PATH'].'header.png" width="100%" >
									</span>
                                    </div>

                                    </div>
									<div style="float:left; color: #6c6c6c; font-size: 24px; line-height: 30px; padding: 10px;">';
        return $body;
    }

   protected function getMailBodyFoot()
    {
        $body = '<br /><br />
        Sincerely,'.'<br />
        UBL Funds Smart Partner Portal Team<br /><br /><div style="float: left; width: 100%; height: 120px;"><img src="'.Yii::$app->params['EMAIL_IMAGE_PATH'].'footer.png" alt="footer" width="100%" />
        </div>
        </div>
        </body>';
        return $body;
    }
	
	protected function sendEmail($subject,$bodyMid,$to,$mailAction='')
    {
		$body = $this->getMailBodyHead();
		$body .= $bodyMid;
        $body .= $this->getMailBodyFoot();


        $smtpHost = Yii::$app->params['SMTP_HOST_DEV'];
        $smtpPort = Yii::$app->params['SMTP_PORT_DEV'];
        $smtpFrom = Yii::$app->params['SMTP_FROM_DEV'];
        $smtpDebug = Yii::$app->params['SMTP_DEBUG_DEV'];

        if(YII_ENV == 'prod')
        {
            $smtpHost = Yii::$app->params['SMTP_HOST_PROD'];
            $smtpPort = Yii::$app->params['SMTP_PORT_PROD'];
            $smtpFrom = Yii::$app->params['SMTP_FROM_PROD'];
            $smtpDebug = Yii::$app->params['SMTP_DEBUG_PROD'];
        }

        // Create the Transport
        $transport = \Swift_SmtpTransport::newInstance($smtpHost, $smtpPort);

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($transport);

        // Create a message
        $message = \Swift_Message::newInstance($subject)
            ->setFrom([$smtpFrom => 'UBL Funds'])
            ->setTo($to)
            ->setSubject($subject." @ UBL Funds Partner Portal")
            //->addCc("sahmar@ublfunds.com")
            ->addCc("mohsin.hassan@tenpearls.com")
            //->addCc("numrah.zafar@tenpearls.com")
            ->setBody($body, 'text/html');

        if (!$mailer->send($message, $errors))
        {
            echo "Error:";
            print_r($errors);
        }
        
    }
    protected function sendEmailToAdmin($subject,$userDetail,$replyTo='')
    {
        $smtpHost = Yii::$app->params['SMTP_HOST_DEV'];
        $smtpPort = Yii::$app->params['SMTP_PORT_DEV'];
        $smtpFrom = Yii::$app->params['SMTP_FROM_DEV'];
        $smtpDebug = Yii::$app->params['SMTP_DEBUG_DEV'];

        if(YII_ENV == 'prod')
        {
            $smtpHost = Yii::$app->params['SMTP_HOST_PROD'];
            $smtpPort = Yii::$app->params['SMTP_PORT_PROD'];
            $smtpFrom = Yii::$app->params['SMTP_FROM_PROD'];
            $smtpDebug = Yii::$app->params['SMTP_DEBUG_PROD'];
        }

        $transport = \Swift_SmtpTransport::newInstance($smtpHost, $smtpPort);

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($transport);

        $userDetail = "<table border='0'>".
            "<tr ><td width='50%'>User name:</td><td width='50%'>".$userDetail['userName']."</td></tr><tr><td width='50%'>Partner :</td><td>".$userDetail['partner']."</td></tr>".
            "<tr><td>Address:</td><td>". $userDetail['address']."</td></tr><tr><td>Email:</td><td>". $userDetail['userEmail']."</td></tr>".
            "<tr><td>Cell number:</td><td>". $userDetail['cellNumber']."</td></tr><tr><td>Contact Person 1:</td><td>". $userDetail['contactPerson1']."</td></tr>".
            "<tr><td>Contact Person2:</td><td>". $userDetail['contactPerson2']."</td></tr><tr><td>Group Sno:</td><td>". $userDetail['groupSno']."</td></tr>".
            "<tr><td>Entity Type:</td><td>". $userDetail['entityType']."</td></tr><tr><td>Date Time:</td><td>". $userDetail['dateTime']."</td></tr>".
            "<tr><td>Details:</td><td>". $userDetail['detail']."</td></tr></table>";

        if(empty($replyTo))
        {
            $replyTo = Yii::$app->params['adminEmail'];
        }

        // Create a message
        $message = \Swift_Message::newInstance($subject)
            ->setFrom($smtpFrom)
            ->setSubject($subject)
            ->setTo(array($replyTo))
            ->addCc(Yii::$app->params['adminEmail'])
            //->addCc("sahmar@ublfunds.com")
            //->addCc("ahmar.jafri@gmail.com")
            //->addCc("mohsin.hassan@tenpearls.com")
            ->setBody($userDetail, 'text/html');

        if (!$mailer->send($message, $errors))
        {
            echo "Error:";
            print_r($errors);
        }
    }
}
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
            else
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
                            $this->model->addLog("login",$post['userEmail']." logged in");
                            $userPrivs = $this->model->getUserPrivs($userCd);
                            $response['code'] = "200";
                            $response['authToken'] = $tokenCode;
                            $response['groupCode'] = $groupCode;
                            $response['userPrivs'] = $userPrivs;
                            $response['userCd'] = $userCd;
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
            $userCd = $this->fetchUserCd();

            if($this->model->checkAccessToken($userCd,$this->authToken))
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

    private function fetchAccessToken()
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
}
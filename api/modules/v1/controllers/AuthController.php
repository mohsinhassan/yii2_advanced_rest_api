<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use \api\modules\v1\models\User;
/**
 * User Controller API
 *
 */
class AuthController extends ActiveController
{
    public function init() {
        parent::init();

    }

    public $authToken;
    public $modelClass = 'api\modules\v1\models\AuthUser';
    public function beforeAction($action){
        $post = Yii::$app->request->post();
        $posForgot = strpos($_SERVER["REQUEST_URI"],"forgotpass");
        $posCodeVerify = strpos($_SERVER["REQUEST_URI"],"codeverify");
        if($posForgot !== false || $posCodeVerify !== false)
        {
            return true;
        }

        $headArray = getallheaders();
        foreach ($headArray as $name => $value)
        {
            if($name == 'authToken')
            {
                $this->authToken = $value;
            }
        }

        if(!isset($this->authToken) || empty($this->authToken))
        {
            if(empty($post['userEmail']) || empty($post['userPass']))
            {
                $response = "403";
                $this->setResponse($response);
                exit;
                //goto login screen
            }
            else{
                $model = new User();


                $result = $model->login($post['userEmail'],$post['userPass']);
                foreach($result[0] as $res)
                {
                    if($res == "TRUE")
                    {
                        $rows = $model->getUserCdGroupCode($post['userEmail']);
                        $userCd = $rows[0]['USER_CD'];
                        $groupCode = $rows[0]['GROUP_CODE'];
                        $key = $model->getDecryptKey();
                        $data = $userCd."|".date("Ymd")." ".time();
                        $tokenCode =  $this->simple_encrypt($data,$key);
                        //$tokenCode = stripcslashes($tokenCode);
                        $tokenCode = str_replace("\/","/",$tokenCode);
                        /*if(strcasecmp( $tokenCode , $tokenCode2 ) != 0)
                        {
                            $tokenCode =  $this->simple_encrypt($data,$key);
                        }*/
                        if($model->saveAccessToken($tokenCode,$userCd))
                        {
                            $model->addLog("login",$post['userEmail']." logged in");
                            $userPrivs = $model->getUserPrivs($userCd);
                            $response['code'] = "200";
                            $response['authToken'] = $tokenCode;
                            $response['groupCode'] = $groupCode;
                            $response['userPrivs'] = $userPrivs;
                        }
                        // $response['message'] = "Login successfully";
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
                        // $response['message'] = "LOCK";
                    }
                    $this->setResponse($response);
                    exit;
                }
            }
        }
        else
        {
            $model =new User();
            $key = $model->getDecryptKey();
            $decode = $this->simple_decrypt($this->authToken,$key);
            $tokenPieces = explode("|",$decode);
            $userCd = $tokenPieces[0];

            if($model->checkAccessToken($userCd,$this->authToken))
            {
                $model->refreshToken($userCd,$this->authToken);
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

    public function simple_encrypt($text,$salt)
    {
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    public function simple_decrypt($text,$salt)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
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
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:  {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            exit(0);
        }

        $jsonResponse  = json_encode($response);
        echo $jsonResponse;
    }
}
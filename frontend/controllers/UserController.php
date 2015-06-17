<?php
namespace frontend\controllers;
use Yii;
use yii\rest\ActiveController;
//use yii\web\Controller;
use frontend\models\User;

/**
 * Country Controller API
 *
 * @author Budi Irawan <deerawan@gmail.com>
 */
class UserController extends ActiveController
{
    public $modelClass = 'frontend\models\User';
    public function actionLogin()
    {
        $post = Yii::$app->request->post();
        $model = new User();
        //$result = $model->login('ashfaqhassan@ublfunds.com','ORACLE');
        $response = array();
        $response['code'] = "401";
        if(empty($post['userEmail']) || empty($post['userPass']))
        {

            $response['code'] = "400";
            $response['message'] = "Email or password can not empty";
            return json_encode($response);
        }
        $result = $model->login($post['userEmail'],$post['userPass']);

        foreach($result[0] as $res)
        {
            if($res == "TRUE")
            {
                $response['code'] = "200";
            }
            if($res == "INACTIVE")
            {
                $response['code'] = "405";
                $response['message'] = "INACTIVE";
            }
            if($res == "LOCK")
            {
                $response['code'] = "405";
                $response['message'] = "LOCK";
            }
        }
        return $response;
    }

    public function actionChangepassword()
    {
        $post = Yii::$app->request->post();
        if(empty($post['userEmail']) || empty($post['oldPass']) || empty($post['oldPass']))
        {

            $response['code'] = "400";
            $response['message'] = "Email or password or new password can not empty";
            return json_encode($response);
        }

        $model = new User();
        $result = $model->changePassword($post['userEmail'],$post['oldPass'],$post['newPass']);

        $response = array();
        $response['code'] = "401";


        foreach($result[0] as $res)
        {
            if($res == "TRUE")
            {
                $response['code'] = "200";
                $response['message'] = "Password changed successfully";
            }
            if($res == "FALSE")
            {
                $response['code'] = "401";
                $response['message'] = "Wrong password";
            }
        }
        return json_encode($response);
    }

    public function actionGetusercd()
    {
        //FUNC_DP_PASSWORD_STATUS
        $post = Yii::$app->request->post();
        $model = new User();
        //$result = $model->login('ashfaqhassan@ublfunds.com','ORACLE');
        //echo $post['userEmail']."=".$post['oldPass']."=".$post['newPass'];exit;
        $result = $model->userCd($post['userEmail']);
        $this->debug($result);exit;

        $response = 203;
        foreach($result[0] as $res)
        {
            if($res == "TRUE")
            {
                $response =  200;
            }
            if($res == "INACTIVE")
            {
                $response =  "INACTIVE";
            }
            if($res == "LOCK")
            {
                $response =  "LOCK";
            }
        }
        return $response;
    }

    public function debug($array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }

    private function loadModel($id)
    {
        $model = Posts::find($id);

        if ($model == NULL)
            throw new HttpException(404, 'Model not found.');

        return $model;
    }
}
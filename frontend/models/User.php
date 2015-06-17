<?php
namespace frontend\models;
use \yii\db\ActiveRecord;
/**
 * Country Model
 *
 * @author Budi Irawan <deerawan@gmail.com>
 */
class User extends ActiveRecord
{
    public static function login($email,$password)
    {
        $connection = \Yii::$app->db;
        $sql = "select FUNC_DP_GET_LOGIN_STATUS('$email','$password') from dual";
        $command = $connection->createCommand($sql);
        $rows = $command->queryAll();
        return $rows;
    }
    public static function changePassword($email,$oldPassword,$newPassword)
    {
        $connection = \Yii::$app->db;
        $sql = "select FUNC_DP_PASSWORD_STATUS('$email','$oldPassword','$newPassword') from dual";
        $command = $connection->createCommand($sql);
        $rows = $command->queryAll();
        return $rows;
    }

    public static function userCd($email)
    {
        $connection = \Yii::$app->db;
        echo $sql = "select * from vw_user_profile where email_address ='$email'";
        $command = $connection->createCommand($sql);
        $rows = $command->queryAll();
        return $rows;
    }


}
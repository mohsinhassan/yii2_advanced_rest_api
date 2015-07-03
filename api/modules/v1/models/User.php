<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use \yii\db\mssql\PDO;
//use \yii\db\oci;

class User extends ActiveRecord
{
    public $connection;

    public function init() {
        $this->connection = \Yii::$app->db;
    }
    /**
     * @inheritdoc
     */
    //public $connection= new Yii::$app->db;

    public function login($email,$password)
    {
        try {
            //$connection = \Yii::$app->db;

            $sql = "select FUNC_DP_GET_LOGIN_STATUS('$email','$password') from dual";
            $command = $this->connection->createCommand($sql);
            $rows = $command->queryAll();
            return $rows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
    public function test()
    {echo "here";}

    public function changePassword($email,$oldPassword,$newPassword)
    {
        try{
            // $connection = \Yii::$app->db;
            $status = '';
            $command = $this->connection->createCommand("CALL PROC_DP_PASSWORD_STATUS(:P_EMAIL, :P_OLD_PASSWORD, :P_NEW_PASSWORD, :P_STATUS)");
            $command->bindParam(':P_EMAIL', $email, PDO::PARAM_STR);
            $command->bindParam(':P_OLD_PASSWORD', $oldPassword, PDO::PARAM_STR);
            $command->bindParam(':P_NEW_PASSWORD', $newPassword, PDO::PARAM_STR);
            $command->bindParam(':P_STATUS', $status, PDO::PARAM_STR, 20);
            $command->execute();
            return $status;

       } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getCgtPre($custAccCode,$planCode,$unitType,$typeValue,$amount=0,$unitPercent=0,$unit=0,$navDate,$c)
    {
        //$c = $this->ociConnect();
        try
        {
            $stmt = "BEGIN :return_cursor := PKG_CGT.func_cgt_pre_calc ('".$custAccCode."','".$planCode."','".$unitType."','".$typeValue."','',".$unitPercent.",'','".$navDate."'); end;";
            $s = oci_parse($c,$stmt);
            $rc = oci_new_cursor($c);

            oci_bind_by_name($s,':return_cursor',$rc,-1,OCI_B_CURSOR);

            oci_execute($s);
            oci_execute($rc);
            $row = oci_fetch_assoc($rc);
            return $row;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getCgtPost($custAccCode,$transactionSno,$transactionType,$c)
    {
        try
        {
            //$c = $this->ociConnect();
            $stmt = "BEGIN :return_cursor := PKG_CGT.func_cgt_post_calc('".$custAccCode."',".$transactionSno.",'".$transactionType."'); end;";
            $s = oci_parse($c,$stmt);
            $rc = oci_new_cursor($c);
            oci_bind_by_name($s,':return_cursor',$rc,-1,OCI_B_CURSOR);

            oci_execute($s);
            oci_execute($rc);
            $row = oci_fetch_assoc($rc);
            return $row;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getUserCd($email)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $res = $this->validateEmail($email);
            if($res)
            {
                $sql = "select USER_CD from vw_user_profile where email_address = '".$email."'";
                $command = $this->connection->createCommand($sql);
                $rows = $command->queryAll();
                return $rows[0]['USER_CD'];
            }
            else{
                return false;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getUserCdByToken($authToken)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $sql = "select USER_CD from pa_dp_session_history where token_code = '".$authToken."'";
            $command = $this->connection->createCommand($sql);
            $rows = $command->queryAll();
            return $rows[0]['USER_CD'];
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getUserCdGroupCode($email)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $res = $this->validateEmail($email);
            if($res)
            {
                $sql = "select USER_CD,GROUP_CODE from vw_user_profile where email_address = '".$email."'";
                $command = $this->connection->createCommand($sql);
                $rows = $command->queryAll();
                return $rows;
            }
            else{
                return false;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getUserGroupSno($userCd)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $sql = "select group_sno from vw_user_group_customer where user_cd= '$userCd' group by group_sno";
            $command = $this->connection->createCommand($sql);
            $rows = $command->queryOne();
            return $rows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        }
        elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        }
        elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }

        // Next get the name of the useragent yes seperately and for good reason
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        }
        elseif(preg_match('/Firefox/i',$u_agent))
        {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        }
        elseif(preg_match('/Chrome/i',$u_agent))
        {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        }
        elseif(preg_match('/Safari/i',$u_agent))
        {
            $bname = 'Apple Safari';
            $ub = "Safari";
        }
        elseif(preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Opera';
            $ub = "Opera";
        }
        elseif(preg_match('/Netscape/i',$u_agent))
        {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            }
            else {
                $version= $matches['version'][1];
            }
        }
        else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version==null || $version=="") {$version="?";}

        return array(
            'userAgent' => $u_agent,
            'name'      => $bname,
            'version'   => $version,
            'platform'  => $platform,
            'pattern'    => $pattern
        );
    }

    public function forgotPass($userEmail)
    {
        try
        {
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

            // $connection = \Yii::$app->db;
            $userCd = $this->getUserCd($userEmail);

            //$selRows = $this->getVerifyCodeExpireMinutes();
            //$selRows[0]['KEY_VALUE'];
            $ua=$this->getBrowser();
            $OS =  $ua['platform'];
            $browser =  $ua['name']. " " . $ua['version'];
            $sql = "insert into pa_dp_password_request values (".date("dis").",'".$userCd."',to_date('".date('Y-m-d H:i:s')."','yyyy-mm-dd HH24:MI:SS'),'".$_SERVER['REMOTE_ADDR']."','".$browser."','".$OS."','".substr($mdun,0,19)."',0)";

            $command = $this->connection->createCommand($sql);
            $rows = $command->execute();

            /* \Yii::$app->mail->compose('your_view')
                ->setFrom([\Yii::$app->params['supportEmail'] => 'Test Mail'])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject('UBL FM - Forgot password' )
                ->setTextBody(substr($mdun,0,19))
                ->send();
            */

            return $rows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function codeVarify($userEmail,$codeVerify,$newPassword)
    {
        try{
            // $connection = \Yii::$app->db;
            $userCd = $this->getUserCd($userEmail);
            $selRows = $this->getVerifyCodeExpireMinutes();
            $sql = "select * from PA_DP_PASSWORD_REQUEST where user_cd = '".$userCd."' and short_code = '".$codeVerify."' and request_date > to_date('".date('Y-m-d ').date('H:i', strtotime('-'.$selRows[0]['KEY_VALUE'].' minutes')).date(':s')."','yyyy-mm-dd HH24:MI:SS') and request_expired = 0";
            $command = $this->connection->createCommand($sql);
            $postCount = $command->queryScalar();
            if($postCount)
            {
                $updSql = "update PA_DP_PASSWORD_REQUEST set request_expired =1 where  user_cd = '".$userCd."' and short_code = '".$codeVerify."'";
                $updCommand = $this->connection->createCommand($updSql);
                $updCommand->execute();
                /////////////////////////Change password procedure code/////////////////////////
                $status = '';
                $oldPassword = '';
                $command = $this->connection->createCommand("CALL PROC_DP_PASSWORD_STATUS(:P_EMAIL, :P_OLD_PASSWORD, :P_NEW_PASSWORD, :P_STATUS)");
                $command->bindParam(':P_EMAIL', $userEmail, PDO::PARAM_STR);
                $command->bindParam(':P_OLD_PASSWORD', $oldPassword, PDO::PARAM_STR);
                $command->bindParam(':P_NEW_PASSWORD', $newPassword, PDO::PARAM_STR);
                $command->bindParam(':P_STATUS', $status, PDO::PARAM_STR, 20);
                $command->execute();
                /////////////////////////End of change password procedure code/////////////////////////

                return 1;
            }
            else
            {
                $updSql = "update PA_DP_PASSWORD_REQUEST set request_expired =1 where  user_cd = '".$userCd."' and short_code = '".$codeVerify."'";
                $updCommand = $this->connection->createCommand($updSql);
                $updCommand->execute();
                return 0;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

    }

    private function getOs() {

        $user_agent     =   $_SERVER['HTTP_USER_AGENT'];
            global $user_agent;

            $os_platform    =   "Unknown OS Platform";

            $os_array       =   array(
                '/windows nt 10/i'     =>  'Windows 10',
                '/windows nt 6.3/i'     =>  'Windows 8.1',
                '/windows nt 6.2/i'     =>  'Windows 8',
                '/windows nt 6.1/i'     =>  'Windows 7',
                '/windows nt 6.0/i'     =>  'Windows Vista',
                '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                '/windows nt 5.1/i'     =>  'Windows XP',
                '/windows xp/i'         =>  'Windows XP',
                '/windows nt 5.0/i'     =>  'Windows 2000',
                '/windows me/i'         =>  'Windows ME',
                '/win98/i'              =>  'Windows 98',
                '/win95/i'              =>  'Windows 95',
                '/win16/i'              =>  'Windows 3.11',
                '/macintosh|mac os x/i' =>  'Mac OS X',
                '/mac_powerpc/i'        =>  'Mac OS 9',
                '/linux/i'              =>  'Linux',
                '/ubuntu/i'             =>  'Ubuntu',
                '/iphone/i'             =>  'iPhone',
                '/ipod/i'               =>  'iPod',
                '/ipad/i'               =>  'iPad',
                '/android/i'            =>  'Android',
                '/blackberry/i'         =>  'BlackBerry',
                '/webos/i'              =>  'Mobile'
            );

            foreach ($os_array as $regex => $value) {

                if (preg_match($regex, $user_agent)) {
                    $os_platform    =   $value;
                }

            }

            return $os_platform;

    }

    public function validateEmail($userEmail)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $status = '';
            $oldPassword = '';
            $newPassword = '';
            $command = $this->connection->createCommand("CALL PROC_DP_PASSWORD_STATUS(:P_EMAIL, :P_OLD_PASSWORD, :P_NEW_PASSWORD, :P_STATUS)");
            $command->bindParam(':P_EMAIL', $userEmail, PDO::PARAM_STR);
            $command->bindParam(':P_OLD_PASSWORD', $oldPassword, PDO::PARAM_STR);
            $command->bindParam(':P_NEW_PASSWORD', $newPassword, PDO::PARAM_STR);
            $command->bindParam(':P_STATUS', $status, PDO::PARAM_STR, 20);
            $command->execute();
            return $status;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getSalesEntityGroup($groupCode)
    {
        try{
            // $connection = \Yii::$app->db;
            $sql = "select * from pa_sale_entity_group  where group_code = '".$groupCode."'";
            $command = $this->connection->createCommand($sql);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getSalesEntityGroupDtl()
    {
        try{
            // $connection = \Yii::$app->db;
            $sql = "select * from pa_sale_entity_group_dtl";
            $command = $this->connection->createCommand($sql);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function  getCommissionStructureMF($groupCode)
    {
        try{
        // $connection = \Yii::$app->db;
        $sql = "select * from vw_dp_commission where DISTRIBUTOR_CODE= '".$groupCode."' and DISTRIBUTOR_TYPE = 'D' and COMM_TYPE='F'";
        $command = $this->connection->createCommand($sql);
        $data = $command->queryAll();
        return $data;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function  getCommissionStructureFL($groupCode)
    {
        try{
            // $connection = \Yii::$app->db;
            $sql = "select * from vw_dp_commission where DISTRIBUTOR_CODE= '".$groupCode."' and DISTRIBUTOR_TYPE = 'I' and COMM_TYPE='S'";
            $command = $this->connection->createCommand($sql);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getCustomersList($userCd,$accountCode,$accountName,$cnic,$email,$cgtExempted,$zakatExempted,$phone)
    {
        try{
            // $connection = \Yii::$app->db;
            $sql = "select ACCOUNT_CODE from vw_user_group_customer where user_cd = '".$userCd."'";
            $command = $this->connection->createCommand($sql);
            $rows = $command->queryAll();

            $acSeries = "";
            $custGroupSnoArray = array();

            foreach($rows as $row)
            {
                $custGroupSnoArray[] = $row['ACCOUNT_CODE'];
            }
            $custGroupSnoArray = array_unique($custGroupSnoArray);

            foreach($custGroupSnoArray as $row)
            {
                $acSeries .= "'".$row."',";
            }
            $acSeries = substr($acSeries,0,(strlen($acSeries) - 1));

            $searchByAccountCode = 0;
            if(!empty($accountCode))
            {
                if (in_array($accountCode, $custGroupSnoArray))
                {
                    $searchByAccountCode = 1;
                }
                else
                {
                    return false;
                }
            }

            $sqlCust= "select * from vw_customers where 1 = 1 ";
            if($searchByAccountCode)
            {
                $sqlCust .= " and ACCOUNT_CODE = '".$accountCode."' ";
            }
            else
            {
                $sqlCust .= " and ACCOUNT_CODE in(".$acSeries.")";
            }
            if(!empty($email))
            {
                $sqlCust .= " and EMAIL = '".$email."'";
            }
            if(!empty($accountName))
            {
                $sqlCust .= " and ACCOUNT_NAME like '%".$accountName."%'";
            }
            if(!empty($cnic))
            {
                $sqlCust .= "and (CNIC = '".$cnic."' OR NTN = '".$cnic."')";
            }
            if(!empty($cgtExempted))
            {
                $sqlCust .= " and CGT_EXEMPTED = '".$cgtExempted."'";
            }
            if(!empty($zakatExempted))
            {
                $sqlCust .= " and ZAKAT_EXEMPTED = '".$zakatExempted."'";
            }
            if(!empty($phone))
            {
                $sqlCust .= " and (RES_PHONE_NO = '".$phone."' OR OFF_PHONE_NO ='".$phone."' OR MOBILE_NO='".$phone."') ";
            }
            $commandCust = $this->connection->createCommand($sqlCust);
            $data = $commandCust->queryAll();
            return $data;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getUserGroups($userCd)
    {
        try{
        // $connection = \Yii::$app->db;
        $sql = "select * from vw_user_group where user_cd = '".$userCd."'";
        $command = $this->connection->createCommand($sql);
        $rows = $command->queryAll();
        return $rows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    /*public function getUserGroupCustomers($userCd,$groupSno)
    {
        try{
        $connection = \Yii::$app->db;
        $sql = "select * from vw_user_group_customer where user_cd = '".$userCd."'";// and group_sno = ".$groupSno;
        $command = $connection->createCommand($sql);
        $rows = $command->queryAll();
        return $rows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }*/
    private function debug($res)
    {
        echo "<pre>";
        print_r($res);
        echo "</pre>";
    }

    public function getVerifyCodeExpireMinutes()
    {
        try{
            // $connection = \Yii::$app->db;
            $selSql = "select KEY_VALUE from dp_setup where key_desc= 'verify_code_expire_minutes'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
    ///////////////////////////////////////auth functions////////////////////////////////////////////
    public function saveAccessToken($authToken,$userCd)
    {
        try{
        // $connection = \Yii::$app->db;
        $selRows = $this->getTokenExpireMinutes();
        $this->logout($userCd);

        $sql = "insert into pa_dp_session_history values (".date('dhis').",'".$userCd."',to_date('".date('Y-m-d H:i:s')."','yyyy-mm-dd HH24:MI:SS'),";
        $sql .= "to_date('".date('Y-m-d ').date('H:i', strtotime('+'.$selRows[0]['KEY_VALUE'].' minutes')).date(':s')."','yyyy-mm-dd HH24:MI:SS'),'active','description','".$_SERVER['REMOTE_ADDR']."','".$authToken."',";
        $sql .= "to_date('".date('Y-m-d H:i:s')."','yyyy-mm-dd HH24:MI:SS'))";
        $command = $this->connection->createCommand($sql);
        $res = $command->execute();
        return $res;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function logout($tokenCode)
    {
        try{
            // $connection = \Yii::$app->db;
            $delSql = "delete from pa_dp_session_history where token_code = '".$tokenCode."' ";
            $delCommand = $this->connection->createCommand($delSql);
            $delCommand->execute();
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function checkAccessToken($userCd,$authToken)
    {
        try{
            // $connection = \Yii::$app->db;
            $selSql = "select token_code from pa_dp_session_history where token_code = '".$authToken."' and user_cd='".$userCd."' and ip_address = '".$_SERVER['REMOTE_ADDR']."' and status='active'  and session_end > to_date('".date('m/d/Y H:i:s')."','mm/dd/yyyy HH24:MI:SS')"; //6/17/2015 12:29:08 AM
            $selCommand = $this->connection->createCommand($selSql);
            $postCount = $selCommand->queryScalar();
            return $postCount;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function refreshToken($userCd,$token)
    {
        try{
            // $connection = \Yii::$app->db;
            $selRows = $this->getTokenExpireMinutes();
            $selSql = "update pa_dp_session_history set session_end = to_date('".date('Y-m-d ').date('H:i', strtotime('+'.$selRows[0]['KEY_VALUE'].' minutes')).date(':s')."','yyyy-mm-dd HH24:MI:SS') where user_cd = '".$userCd."' and token_code = '".$token."' and ip_address = '".$_SERVER['REMOTE_ADDR']."'";
            $selCommand = $this->connection->createCommand($selSql);
            $selCommand->execute();
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getAccessToken($userCd)
    {
        try{
            // $connection = \Yii::$app->db;
            $selSql =" select token_code from pa_dp_session_history where user_cd = '".$userCd."'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getDecryptKey()
    {
        try{
            // $connection = \Yii::$app->db;
            $selSql = "select KEY_VALUE from dp_setup where key_desc= 'decrypt_key'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows[0]['KEY_VALUE'];
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getTokenExpireMinutes()
    {
        try{
            // $connection = \Yii::$app->db;
            $selSql = "select KEY_VALUE from dp_setup where key_desc= 'access_token_expire_minutes'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function addLog($event,$msg)
    {
        try{
            // $connection = \Yii::$app->db;
            $evntSql = "select event_sno from Pa_Dp_Events where event_desc = '".$event."'";
            $eventCommand = $this->connection->createCommand($evntSql);
            $eventRows = $eventCommand->queryAll();
            $eventSno = $eventRows[0]['EVENT_SNO'];
            $insSql = "insert into PA_DP_LOGS (ACTION_DATE,EVENT_SNO,LOG_DESCRIP) values (to_date('".date("y-m-d H:i:s")."','yyyy-mm-dd HH24:MI:SS'),'$eventSno','$msg')";
            $insCommand = $this->connection->createCommand($insSql);
            $insCommand->execute();

        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getUserPrivs($userCd)
    {
        try{
            // $connection = \Yii::$app->db;
            $sql = "Select OPT_CD from VW_USER_PRIVS where user_cd='".$userCd."'";
            $command = $this->connection->createCommand($sql);
            $res = $command->queryAll();
            foreach($res as $resPriv)
            {
                $userPrivs[] = $resPriv['OPT_CD'];
            }
            $userPrivs = array_unique($userPrivs);
            return $userPrivs;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getEarnedValue($groupSno)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $loadComm = "";
            $mfComm = "";
            $ytdComm = "";
            $fromDate= date("1-M-y");
            $toDate = date("t-M-y");

            $command = $this->connection->createCommand("CALL PROC_DP_EARNED_VALUE (:P_GROUP_SNO, :P_FROM_DATE, :P_TO_DATE, :P_O_LOAD_COMM,:P_O_MF_COMM,:P_O_YTD_COMM)");
            $command->bindParam(':P_GROUP_SNO', $groupSno, PDO::PARAM_STR);
            $command->bindParam(':P_FROM_DATE', $fromDate, PDO::PARAM_STR);
            $command->bindParam(':P_TO_DATE', $toDate, PDO::PARAM_STR);
            $command->bindParam(':P_O_LOAD_COMM', $loadComm, PDO::PARAM_INT, 20);
            $command->bindParam(':P_O_MF_COMM', $mfComm, PDO::PARAM_INT, 20);
            $command->bindParam(':P_O_YTD_COMM', $ytdComm, PDO::PARAM_INT, 20);
            $command->execute();

            $response['loadComm'] = $loadComm;
            $response['mfComm'] = $mfComm;
            $response['ytdComm'] = $ytdComm;
            $response['fromDate'] = $fromDate;
            return $response;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function changeDistributor($email,$dp_code)
    {
        try
        {
            // $connection = \Yii::$app->db;
            $command = $this->connection->createCommand("call PROC_DP_UPDATE_GP_CODE ('".$email."' ,'".$dp_code."')");
            if($command->execute())
            {
                return 1;
            }
            else
            {
                return 0;
            }
        } catch (Exception $e)
        {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function getAumrep($fromDate)
    {
        $command = $this->connection->createCommand("select PKG_DP_REP.func_get_aum_rep ('$fromDate') from dual");
        $response = $command->queryAll();
        return $response;
    }

    public function getDalrep($fromDate,$toDate,$p_ic)
    {
        $command = $this->connection->createCommand("select PKG_DP_REP.FUNC_GET_DAL_REP ('$fromDate', '$toDate', '$p_ic') from dual");
        $result = $command->queryAll();
        $response = $result;
        return $response;

    }

    public function getCprnrep($fromDate,$toDate,$customerCode,$fundCode)
    {
            /*$connection = \Yii::$app->db;
            $fromDate = "01-jan-2015";
            $toDate = "31-jan-2015";
            $result = "";
            $customerCode = '00025894-1';
            $fundCode ='ASSF';*/

            $command = $this->connection->createCommand("select PKG_DP_REP.FUNC_GET_CPRN_REP ('$fromDate', '$toDate', '$customerCode','$fundCode') from dual");
            $response = $command->queryAll();
            return $response;
    }

    public function getAllGroupMembers($userCd)
    {
        $sql = "select * from PA_SALE_ENTITY_GROUP where group_sno in (select group_sno from VW_USER_GROUP where user_cd = '$userCd')";

        $command = $this->connection->createCommand($sql);
        $rows = $command->queryAll();
        return $rows;
    }
}
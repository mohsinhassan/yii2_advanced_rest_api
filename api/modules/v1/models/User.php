<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use \yii\db\mssql\PDO;

class User extends ActiveRecord
{
    public $connection;

    public function init() {
        $this->connection = \Yii::$app->db;
    }

    public function login($email,$password)
    {
        try{

            $sql = "select FUNC_DP_GET_LOGIN_STATUS(:email,:pass) from dual";
            $command = $this->connection->createCommand($sql);
            $command->bindValue('email',$email);
            $command->bindValue('pass',$password);
            $rows = $command->queryAll();
        return $rows;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function changePassword($email,$oldPassword,$newPassword)
    {
        try{
            $status = '';
            $command = $this->connection->createCommand("CALL PROC_DP_PASSWORD_STATUS(:P_EMAIL, :P_OLD_PASSWORD, :P_NEW_PASSWORD, :P_STATUS)");
            $command->bindParam(':P_EMAIL', $email, PDO::PARAM_STR);
            $command->bindParam(':P_OLD_PASSWORD', $oldPassword, PDO::PARAM_STR);
            $command->bindParam(':P_NEW_PASSWORD', $newPassword, PDO::PARAM_STR);
            $command->bindParam(':P_STATUS', $status, PDO::PARAM_STR, 20);
            $command->execute();
            return $status;

       } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getCgtPre($custAccCode,$planCode,$unitType,$typeValue,$amount=0,$unitPercent=0,$unit=0,$navDate,$c)
    {
        try
        {
            $stmt = "BEGIN :return_cursor := PKG_NEW_CGT.func_cgt_pre_calc('".$custAccCode."','".$planCode."','".$unitType."','".$typeValue."','".$amount."','".$unitPercent."','".$unit."','".$navDate."'); end;";
            $s = oci_parse($c,$stmt);
            $rc = oci_new_cursor($c);

            oci_bind_by_name($s,':return_cursor',$rc,-1,OCI_B_CURSOR);

            oci_execute($s);
            oci_execute($rc);
            $row = oci_fetch_assoc($rc);
            return $row;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getCgtPost($custAccCode,$transactionSno,$transactionType,$c)
    {
        try
        {
            $stmt = "BEGIN :return_cursor := pkg_new_cgt.func_cgt_post_calc('".$custAccCode."',".$transactionSno.",'".$transactionType."'); end;";
            $s = oci_parse($c,$stmt);
            $rc = oci_new_cursor($c);
            oci_bind_by_name($s,':return_cursor',$rc,-1,OCI_B_CURSOR);

            oci_execute($s);
            oci_execute($rc);
            $row = oci_fetch_assoc($rc);
            return $row;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getUserCd($email)
    {
        try
        {
            $res = $this->validateEmail($email);
            if($res)
            {
                $sql = "select USER_CD from vw_user_profile where email_address = :email";
                $command = $this->connection->createCommand($sql);
                $command->bindValue("email",$email);
                $rows = $command->queryAll();
                return $rows[0]['USER_CD'];
            }
            else{
                return false;
            }
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getUserCdByToken($authToken)
    {
        try
        {
            $sql = "select USER_CD,session_sno from pa_dp_session_history where token_code = :token";
            $command = $this->connection->createCommand($sql);
            $command->bindValue('token',$authToken);
            $rows = $command->queryAll();
            return $rows[0]['USER_CD'];
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getUserSessionSno($authToken)
    {
        try
        {
            $sql = "select session_sno from pa_dp_session_history where token_code = :token";
            $command = $this->connection->createCommand($sql);
            $command->bindValue('token',$authToken);
            $rows = $command->queryAll();
            return $rows[0]['SESSION_SNO'];
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }


    public function getUserCdGroupCode($email)
    {
        try
        {
            $res = $this->validateEmail($email);
            if($res)
            {
                $sql = "select USER_CD,GROUP_CODE from vw_user_profile where email_address = :email";
                $command = $this->connection->createCommand($sql);
                $command->bindValue('email',$email);
                $rows = $command->queryAll();
                return $rows;
            }
            else{
                return false;
            }
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getUserGroupSno($userCd)
    {
        try
        {
            $sql = "select group_sno from vw_user_group_customer where user_cd= :userCd group by group_sno";
            $command = $this->connection->createCommand($sql);
            $command->bindValue('userCd',$userCd);
            $rows = $command->queryOne();
            return $rows;
        } catch (Exception $e) {
            return  $e->getMessage();
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

    public function forgotPass($userEmail,$mdun)
    {
        try
        {
            $userCd = $this->getUserCd($userEmail);

            $ua=$this->getBrowser();
            $OS =  $ua['platform'];
            $browser =  $ua['name']. " " . $ua['version'];
            $sql = "insert into pa_dp_password_request values (".date("dis").", :userCd , to_date('".date('Y-m-d H:i:s')."','yyyy-mm-dd HH24:MI:SS'),:remoteAddress,:browser,:OS,:mdun,0)";

            $command = $this->connection->createCommand($sql);
            $command->bindValue('userCd',$userCd);
            $command->bindValue('remoteAddress',$_SERVER['REMOTE_ADDR']);
            $command->bindValue('browser',$browser);
            $command->bindValue('OS',$OS);
            $command->bindValue('mdun',$mdun);

            $rows = $command->execute();
            return $rows;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function codeVarify($userEmail,$codeVerify,$newPassword)
    {
        try{
            $userCd = $this->getUserCd($userEmail);
            $selRows = $this->getVerifyCodeExpireMinutes();
            $sql = "select * from PA_DP_PASSWORD_REQUEST where user_cd = :userCd and short_code = :verifyCode and request_date > to_date('".date('Y-m-d ').date('H:i', strtotime('-'.$selRows[0]['KEY_VALUE'].' minutes')).date(':s')."','yyyy-mm-dd HH24:MI:SS') and request_expired = 0";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
            $command->bindValue("verifyCode",$codeVerify);
            $postCount = $command->queryScalar();
            if($postCount)
            {
                $updSql = "update PA_DP_PASSWORD_REQUEST set request_expired =1 where  user_cd = :userCd and short_code = :verifyCode";
                $updCommand = $this->connection->createCommand($updSql);
                $command->bindValue("userCd",$userCd);
                $command->bindValue("verifyCode",$codeVerify);
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
                $updSql = "update PA_DP_PASSWORD_REQUEST set request_expired = 1 where  user_cd = :userCd and short_code = :codeVerify";
                $updCommand = $this->connection->createCommand($updSql);
                $updCommand->bindValue("userCd",$userCd);
                $updCommand->bindValue("codeVerify",$codeVerify);
                $updCommand->execute();
                return 0;
            }
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function validateEmail($userEmail)
    {
        try
        {
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
            return  $e->getMessage();
        }
    }

    public function getSalesEntityGroup($groupCode)
    {
        try{
            $sql = "select * from pa_sale_entity_group  where group_code = :groupCode";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("groupCode",$groupCode);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getSalesEntityGroupDtl()
    {
        try{
            $sql = "select * from pa_sale_entity_group_dtl";
            $command = $this->connection->createCommand($sql);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function  getCommissionStructureMF($groupCode)
    {
        try{
            $sql = "select * from vw_dp_commission where DISTRIBUTOR_CODE= :groupCode and DISTRIBUTOR_TYPE = 'D' and COMM_TYPE='F'";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("groupCode",$groupCode);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function  getCommissionStructureFL($groupCode)
    {
        try{
            $sql = "select * from vw_dp_commission where DISTRIBUTOR_CODE= :groupCode and DISTRIBUTOR_TYPE = 'I' and COMM_TYPE='S'";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("groupCode",$groupCode);
            $data = $command->queryAll();
            return $data;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getCustomersList($userCd,$accountCode,$accountName,$cnic,$email,$cgtExempted,$zakatExempted,$phone)
    {
        try{
            $sql = "select ACCOUNT_CODE from vw_user_group_customer where user_cd = :userCd";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
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
            return  $e->getMessage();
        }
    }

    public function getUserGroups($userCd)
    {
        try{
            $sql = "select * from vw_user_group where user_cd = :userCd";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
            $rows = $command->queryAll();
            return $rows;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getVerifyCodeExpireMinutes()
    {
        try{
            $selSql = "select KEY_VALUE from dp_setup where key_desc= 'verify_code_expire_minutes'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getBusinessDate()
    {
        $selSql = "select KEY_VALUE from dp_setup where key_desc= 'P_BUSINESS_DATE'";
        $selCommand = $this->connection->createCommand($selSql);
        $selRows = $selCommand->queryAll();
        return $selRows;
    }

    ///////////////////////////////////////auth functions////////////////////////////////////////////
    public function saveAccessToken($authToken,$userCd)
    {
        try{
            $selRows = $this->getTokenExpireMinutes();

            $sql = "insert into pa_dp_session_history values (".date('dhis').",:userCd,to_date('".date('Y-m-d H:i:s')."','yyyy-mm-dd HH24:MI:SS'),";
            $sql .= "to_date('".date('Y-m-d ').date('H:i', strtotime('+'.$selRows[0]['KEY_VALUE'].' minutes')).date(':s')."','yyyy-mm-dd HH24:MI:SS'),'active','description',:REMOTE_ADDR,:authToken,";
            $sql .= "to_date('".date('Y-m-d H:i:s')."','yyyy-mm-dd HH24:MI:SS'))";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
            $command->bindValue("REMOTE_ADDR",$_SERVER['REMOTE_ADDR']);
            $command->bindValue("authToken",$authToken);

            $res = $command->execute();
            return $res;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function logout($tokenCode)
    {
        try{
            $delSql = "delete from pa_dp_session_history where token_code = '".$tokenCode."' ";
            $delCommand = $this->connection->createCommand($delSql);
            $delCommand->execute();
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function checkAccessToken($userCd,$authToken,$sessionSno)
    {
        try{
            $selSql = "select token_code from pa_dp_session_history where token_code = :authToken and user_cd=:userCd and ip_address = :ipAddress and  session_sno = :sessionSno and status='active' and session_end > to_date('".date('m/d/Y H:i:s')."','mm/dd/yyyy HH24:MI:SS')"; //6/17/2015 12:29:08 AM
            $selCommand = $this->connection->createCommand($selSql);
            $selCommand->bindValue("authToken",$authToken);
            $selCommand->bindValue("userCd",$userCd);
            $selCommand->bindValue("sessionSno",$sessionSno);
            $selCommand->bindValue("ipAddress",$_SERVER['REMOTE_ADDR']);

            $postCount = $selCommand->queryScalar();
            return $postCount;
        }
        catch (Exception $e) {
        return  $e->getMessage();
        }
    }

    public function refreshToken($userCd,$token)
    {
        try{
            $selRows = $this->getTokenExpireMinutes();
            $selSql = "update pa_dp_session_history set session_end = to_date('".date('Y-m-d ').date('H:i', strtotime('+'.$selRows[0]['KEY_VALUE'].' minutes')).date(':s')."','yyyy-mm-dd HH24:MI:SS') where user_cd = '".$userCd."' and token_code = '".$token."' and ip_address = '".$_SERVER['REMOTE_ADDR']."'";
            $selCommand = $this->connection->createCommand($selSql);
            $selCommand->execute();
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getAccessToken($userCd)
    {
        try{
            $selSql =" select token_code from pa_dp_session_history where user_cd = :userCd";
            $selCommand = $this->connection->createCommand($selSql);
            $selCommand->bindValue("userCd",$userCd);
            $selRows = $selCommand->queryAll();
            return $selRows;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getDecryptKey()
    {
        try{
            $selSql = "select KEY_VALUE from dp_setup where key_desc= 'decrypt_key'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows[0]['KEY_VALUE'];
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getTokenExpireMinutes()
    {
        try{
            $selSql = "select KEY_VALUE from dp_setup where key_desc= 'access_token_expire_minutes'";
            $selCommand = $this->connection->createCommand($selSql);
            $selRows = $selCommand->queryAll();
            return $selRows;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function addLog($event,$msg,$sessionSno='')
    {
        try{
            $evntSql = "select event_sno from Pa_Dp_Events where event_desc = :event";
            $eventCommand = $this->connection->createCommand($evntSql);
            $eventCommand->bindValue("event",$event);
            $eventRows = $eventCommand->queryAll();
            $eventSno = $eventRows[0]['EVENT_SNO'];
            $insSql = "insert into PA_DP_LOGS(log_sno,ACTION_DATE,EVENT_SNO,LOG_DESCRIP,SESSION_SNO) values (PA_DP_LOG_SEQ.Nextval,to_date('".date("y-m-d H:i:s")."','yyyy-mm-dd HH24:MI:SS'),:eventSno,:msg,:sessionSno)";
            $insCommand = $this->connection->createCommand($insSql);
            $insCommand->bindValue("eventSno",$eventSno);
            $insCommand->bindValue("msg",$msg);
            $insCommand->bindValue("sessionSno",$sessionSno);
            $insCommand->execute();

        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getUserPrivs($userCd)
    {
        try{
            $sql = "Select OPT_CD from VW_USER_PRIVS where user_cd=:userCd";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
            $res = $command->queryAll();
            foreach($res as $resPriv)
            {
                $userPrivs[] = $resPriv['OPT_CD'];
            }
            $userPrivs = array_unique($userPrivs);
            return $userPrivs;
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getEarnedValue($groupSno)
    {
        try
        {
            $loadComm = "";
            $mfComm = "";
            $ytdComm = "";

            $selRows = $this->getBusinessDate();

            $fromDate = $selRows[0]['KEY_VALUE'];
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
            return  $e->getMessage();
        }
    }

    public function changeDistributor($email,$dpCode)
    {
        try{
            $command = $this->connection->createCommand("call PROC_DP_UPDATE_GP_CODE (:email , :dpCode)");
            $command->bindValue("email",$email);
            $command->bindValue("dpCode",$dpCode);
            if($command->execute())
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }

    }

    public function getAumrep($fromDate)
    {
        try{
            $result = "";
            $command = $this->connection->createCommand("call PKG_DP_REP.proc_get_aum_rep(:P_AS_ON_DATE, :RESULT)");
            $command->bindParam(':P_AS_ON_DATE', $fromDate, PDO::PARAM_STR);
            $command->bindParam(':RESULT', $result, PDO::PARAM_STR, 1000);
            $command->execute();
        return $result;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getDalrep($fromDate,$toDate,$p_ic)
    {
        try{
            $result = "";
            $command = $this->connection->createCommand("call PKG_DP_REP.PROC_GET_DAL_REP (:P_FROM_DATE, :P_TO_DATE, :P_IC, :P_RESULT)");
            $command->bindParam(':P_FROM_DATE', $fromDate, PDO::PARAM_STR);
            $command->bindParam(':P_TO_DATE', $toDate, PDO::PARAM_STR);
            $command->bindParam(':P_IC', $p_ic, PDO::PARAM_STR);
            $command->bindParam(':P_RESULT', $result, PDO::PARAM_STR, 1000);
            $command->execute();
        return $result;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getCprnrep($fromDate,$toDate,$customerCode,$fundCode)
    {
        try{
            $result = "";
            $command = $this->connection->createCommand("call PKG_DP_REP.PROC_GET_CPRN_REP(:P_FROM_DATE, :P_TO_DATE, :P_CUST_ACCT_CODE, :P_FUND_CODE :P_RESULT)");
            $command->bindParam(':P_FROM_DATE', $fromDate, PDO::PARAM_STR);
            $command->bindParam(':P_TO_DATE', $toDate, PDO::PARAM_STR);
            $command->bindParam(':P_CUST_ACCT_CODE', $customerCode, PDO::PARAM_STR);
            $command->bindParam(':P_FUND_CODE', $fundCode, PDO::PARAM_STR);
            $command->bindParam(':P_RESULT', $result, PDO::PARAM_STR, 1000);
            $command->execute();
            return $result;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getAllGroupMembers($userCd)
    {
        try{
            $sql = "select * from PA_SALE_ENTITY_GROUP where group_sno in (select group_sno from VW_USER_GROUP where user_cd = :userCd)";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }

    }

    public function getGroupCustomers($groupSno)
    {
        try{
            $sql = "select * from vw_dp_group_customer_AUM where group_sno = :groupSno";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("groupSno",$groupSno);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }

    }

    public function getGroupCustomerFundAum($groupSno,$fundCode)
    {
        try{
            $sql = "select * from vw_dp_group_customer_AUM where group_sno = :groupSno and fund_code = :fundCode";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("groupSno",$groupSno);
            $command->bindValue("fundCode",$fundCode);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getFundAum()
    {
        try{
            $sql = "select * from vw_dp_fund_AUM";
            $command = $this->connection->createCommand($sql);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }

    }

    public function getTransactionTrack($accountCode,$transDate,$toDate)
    {
        try{
            $sql = "select * from vw_dp_trans_track where account_code ='$accountCode' and transaction_date > to_date('".$transDate."','dd-mon-yyyy') and transaction_date < to_date('".$toDate."','dd-mon-yyyy')";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("accountCode",$accountCode);
            $command->bindValue("transDate",$transDate);
            $command->bindValue("toDate",$toDate);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getTransactionKeyword($keyword,$keyword1,$keyword2,$keyword3)
    {
        try{
            $sql = "select func_get_keywordtext_DP (:keyword, :keyword1, :keyword2, :keyword3) from dual";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("keyword",$keyword);
            $command->bindValue("keyword1",$keyword1);
            $command->bindValue("keyword2",$keyword2);
            $command->bindValue("keyword3",$keyword3);

            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getInflowOutflow($groupSno)
    {
        try{
            $sql = "select * from vw_dp_inflow_outflow where group_sno = :groupSno";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("groupSno",$groupSno);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getUserNotifications($userCd)
    {
        try{
            $sql = "select * from pa_dp_notification where notification_id not in (select notification_id from pa_dp_notification_user where user_id = :userCd ) order by notification_id";
            $command = $this->connection->createCommand($sql);
            $command->bindValue("userCd",$userCd);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getNotificationRead($notificationId,$userCd)
    {
        try{
            $sql = "insert into pa_dp_notification_user(user_id,notification_id,user_notification) values (:userCd,:notificationId,to_date('".date('d-m-Y')."','dd-mm-yyyy') )";
            $selCommand = $this->connection->createCommand($sql);
            $selCommand->bindValue("userCd",$userCd);
            $selCommand->bindValue("notificationId",$notificationId);
            $selCommand->execute();
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getAllNotifications()
    {
        try{
            $sql = "select * from pa_dp_notification order by notification_date";
            $command = $this->connection->createCommand($sql);
            $rows = $command->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }

    public function getGroupTransactions($userCd,$groupSno)
    {
        try{
            $sql = "select * from vw_user_group_transaction where user_cd = :user_cd and group_sno = :group_sno";
            $selCommand = $this->connection->createCommand($sql);
            $selCommand->bindValue('user_cd',$userCd);
            $selCommand->bindValue('group_sno',$groupSno);
            $rows = $selCommand->queryAll();
            return $rows;
        }
        catch (Exception $e) {
            return  $e->getMessage();
        }
    }
}
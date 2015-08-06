<?php
use yii\helpers\Html;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\MessageInterface the message being composed */
/* @var $content string main view render result */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
    <?php $this->beginBody() ?>
    <? //$content
    $body.= '<body style="margin:0px; padding:0px; text-decoration:none;">
    <div style="margin:0 auto; width:80%;">
        <div style="float:left; width: 100%;">
            <div style="float:left; background:url('.$message->embed($imageFileName1).') no-repeat; background-size: 100% auto; width:200px; height:70px; margin: 10px 10px 0px;"></div>
            <div style="float: right; background: url('.$message->embed($imageFileName2).') no-repeat; background-size: 100% auto; width:200px; height: 60px; margin: 20px 20px 0px;"></div>
            <span style="float: left; background: url('.$message->embed($imageFileName3).') repeat-y; background-size: 100% auto; width: 100%; height: 10px;"></span>
        </div>
    </div>
    <div style="width: 80%;margin: 0 auto;">
        <div style="float:left;width: 50%;margin: 50px 0px;">
            <div style="float:left;background: #004684;width: 55%;">
                <span style="float:left;color: #fff;font-size: 35px;padding: 10px;">Working together</span>
            </div>
            <div style="float:left;background-color: #14caef;width: 60%;">
                <span style="float:left; color: #fff; font-size: 35px; padding: 10px;"> to help People save</span>
            </div>

        </div>

        <div style="float: right; background: url('.$message->embed($imageFileName4).') no-repeat; background-size: 100% auto; width: 270px; height: 220px; margin: 20px 20px 0px;"></div>
        <div style="float:left;width: 100%;height: 10px;">
            <span style="background: #a8a9ae; width:20%; height: 10px; float: left;"></span>
            <span style="background:#14caef; width:20%; height:10px; float:left;"></span>
            <span style="background:#004684; width:60%; float: left; height: 10px;"></span>
        </div>


    </div>
    <div style="float:left; width: 80%; margin: 10px 10%;">
        <p style="float:left; color: #6c6c6c; font-size: 24px; line-height: 30px; padding: 10px;">';
    ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

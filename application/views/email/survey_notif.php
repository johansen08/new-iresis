<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <style type="text/css">
        body,
        html {
            padding: 0;
            margin: 0;
            position: relative;
            text-align: left;
            width: 100%;
            max-height: 100%;
            text-align: center;
            background-color: #f5f5f5;
        }
        
        #yo-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
            background-color: #f5f5f5;
            text-align: center;
        }
        
        #yo-wrapper a {
            color: #4b4b4b;
            text-decoration: none;
        }
        
        #yo-logo {
            background: transparent center no-repeat url('http://muniyo.com/assets/images/logo.png');
            height: 40px;
            margin: 0 0 25px;
        }
        
        #yo-footer {
            display: inline-block;
            font-family: Arial;
            font-size: 12px;
            color: #c8c8c8;
            line-height: 1.25;
            max-width: 300px;
            margin: 0 0 30px;
        }
        
        #yo-wrap {
            position: relative;
            min-width: 280px;
            max-width: 462px;
            display: inline-block;
        }
        
        #yo-wrap .top {
            height: 5px;
            background-image: linear-gradient(268deg, #ffe800, #ffcc00);
            margin: 0;
        }
        
        #yo-wrap .content {
            background: #fff;
            margin: 0;
            box-sizing: border-box;
            padding: 30px;
        }
        
        #yo-wrap .content h2 {
            font-family: Arial;
            font-weight: normal;
            font-size: 16px;
            line-height: 1.47;
            text-align: center;
            color: #4b4b4b;
            margin: 0 0 10px;
            padding: 0;
        }
        
        #yo-wrap .content h2 .strong {
            font-weight: bold;
        }
        
        #yo-wrap .content h4 {
            font-family: Arial;
            font-size: 16px;
            font-weight: bold;
            line-height: 1.47;
            text-align: center;
            color: #4b4b4b;
        }
        
        #yo-wrap .content .btn {
            display: inline-block;
            border-radius: 2.5px;
            box-shadow: 0 1.5px 2.5px 0 rgba(0, 0, 0, 0.14);
            background-color: #ffdc52;
            /*border: solid 0.5px #4a4a4a;*/
            font-family: Arial;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            color: #4a4a4a;
            text-decoration: none;
            padding: 17px 20px;
            margin: 20px 0;
        }
        
        #yo-wrap .content .garis {
            height: 1px;
            background-color: #979797;
            margin: 30px 0;
        }
        
        #yo-wrap .content .info-left h3,
        #yo-wrap .content .info-right h3 {
            font-family: Arial;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.36;
            text-align: left;
            color: #f69f3c;
            margin: 0;
            padding: 0;
        }
        
        #yo-wrap .content .info-left p,
        #yo-wrap .content .info-right p {
            font-family: Arial;
            font-size: 11px;
            line-height: 1.5;
            text-align: left;
            color: #4a4a4a;
        }
        
        #yo-wrap .content .info-right h3,
        #yo-wrap .content .info-right p {
            text-align: right;
        }
        
        #yo-wrap .bottom {
            box-sizing: border-box;
            height: 59px;
            background-image: linear-gradient(258deg, #ffe653, #ffc929);
            margin: 0;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            text-align: left;
            padding: 15px 30px;
        }
        
        #yo-wrap .bottom span {
            display: inline-block;
            font-family: Arial;
            font-size: 12px;
            font-weight: 500;
            color: #967a08;
            vertical-align: middle;
        }
        
        #yo-wrap .bottom a {
            display: inline-block;
            text-decoration: none;
            outline: none;
            background: transparent center no-repeat;
            vertical-align: middle;
            float: right;
            margin: 0 5px;
        }
        
        #yo-wrap .bottom a.instagram {
            background: transparent center no-repeat url("http://muniyo.com/assets/images/icon-instagram-email.png");
            background-size: 27px 27px;
            width: 27px;
            height: 27px;
        }
        
        #yo-wrap .bottom a.facebook {
            background: transparent center no-repeat url("http://muniyo.com/assets/images/icon-facebook-email.png");
            background-size: 27px 27px;
            width: 27px;
            height: 27px;
        }
        
        @media (max-width: 500px) {
            #yo-wrap {
                width: 280px;
            }
            #yo-wrap .content .info-left {
                background-position: center top;
                padding: 215px 0 0;
                height: auto;
            }
            #yo-wrap .content .info-right {
                background-position: center top;
                padding: 150px 0 0;
                height: auto;
            }
            #yo-wrap .content .info-left h3,
            #yo-wrap .content .info-right h3,
            #yo-wrap .content .info-right p,
            #yo-wrap .content .info-left p {
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <div id="yo-wrapper">

        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <div id="yo-logo"></div>

        <div id="yo-wrap">
            <div class="top"></div>
            <div class="content">

                <h2>Hai <b><?php echo ucwords($name) ?></b>,</h2>

                <?php if (!empty($notif_text)): ?>
                    <h2><?= $notif_text ?></h2>
                <?php else: ?>
                    <h2>Ada kuis berpoin dari Muniyo yang menunggumu. Buka tautan di bawah ini dan segera berpartisipasi.</h2>
                <?php endif; ?>

                <a class="btn" href="<?= $this->config->item("app_mainsite_url").'post/'.$survey_uid ?>">Klik di sini untuk melihat kuis</a>

            </div>
            <div class="bottom">
                <span>Ikuti kami di sosial media</span>
                <a class="facebook" href="https://web.facebook.com/muniyo.id/"></a>
                <a class="instagram" href="https://www.instagram.com/muniyo.id/"></a>
            </div>
        </div>
        <p></p>
        <div id="yo-footer">Email ini dibuat secara otomatis, mohon untuk tidak mengirimkan balasan ke email ini</div>

    </div>

</body>

</html>
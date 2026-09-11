<!DOCTYPE html>
<html lang="en" class="body-full-height">

<head>
    <!-- META SECTION -->
    <title>IRESIS - Sistem Informasi Resi dan Stock</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" id="theme" href="assets/css/theme-default.css" />
	
	<!-- CSS INCLUDE -->
    <link rel="stylesheet" type="text/css" id="theme" href="assets/css/theme-default.css" />
    <!-- EOF CSS INCLUDE -->
    <!-- STYLE OVERRIDE -->
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
            background-size: cover !important;
            background-color: #f5f7fa !important;
        }

        .login-container {
            background: transparent !important;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-box {
            background: #ffffff;
            border-radius: 20px;
            padding: 45px;
            width: 380px;
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.15);
            animation: fadeInDown 0.8s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-logo {
            text-align: center;
            font-size: 44px;
            font-weight: 700;
            color: #007bff;
            letter-spacing: 4px;
            margin-bottom: 5px;
            margin-top: -30px;
            text-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
        }

        .login-subtitle-text {
            text-align: center;
            font-size: 14px;
            color: #555;
            letter-spacing: 1px;
            margin-bottom: 25px;
            font-weight: 400;
        }

        .login-body .login-title {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
        }

        .login-box input,
        .login-box select {
            background: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px;
            color: #333 !important;
            padding: 10px 12px;
        }

        .login-box input::placeholder {
            color: #999;
        }

        .btn-info {
            background-color: #007bff !important;
            border: none !important;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn-info:hover {
            background-color: #0056b3 !important;
            transform: scale(1.03);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 12px;
        }

        .login-footer a {
            color: #e91e63;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .help-block {
            color: #ff2d55;
            margin-top: 5px;
            font-size: 11px;
            line-height: 1.4;
        }
    </style>
</head>

<body>

    <div class="login-container">

        <div class="login-box animated fadeInDown">
            <div class="login-body">
                <div class="login-title"><strong>Welcome</strong>, Please login</div>

                <?php if (!empty($message)) : ?>
                    <div class="login-subtitle"><?= $message ?></div>
                <?php endif; ?>

                <form action="auth" class="form-horizontal" method="post" id="loginForm" novalidate>
                    <input type="hidden" name="nama_komputer" value="<?= $machine_name ?>" />
                    <div class="form-group">
                        <div class="col-md-12">
                            <input type="text" name="username" id="username" class="form-control" placeholder="Username" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <select name="nama_pk" id="nama_pk" class="form-control select" data-live-search="true" style="font-weight: bold;">
                                <option value="" style="font-weight: bold;" selected disabled>-- Select Computer --</option>
                                <?php foreach ($list_pk as $pk) : ?>
                                    <option
                                            value="<?= $pk['nama_pk'] ?>"
                                            style="font-weight: bold;"
                                            <?= ($this->input->post('nama_pk') == $pk['nama_pk']) ? 'selected' : '' ?>>
                                            <?= $pk['nama_pk'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <select name="status_performa" id="status_performa" class="form-control select" data-live-search="true" style="font-weight: bold;">
                                <option value="" style="font-weight: bold;" selected disabled>-- Select Status Performa --</option>
                                <?php foreach ($list_status_performa as $role => $statuses) : ?>
                                    <!-- <optgroup label="<?= $role ?>"> -->
                                        <?php foreach ($statuses as $status) : ?>
                                            <option value="<?= $status['status_name'] ?>"><?= $status['status_name'] ?></option>
                                        <?php endforeach; ?>
                                    <!-- </optgroup> -->
                                <?php endforeach; ?>
                            </select>
                            <span class="help-block" style="color: #d9534f; margin-top: 5px; font-size: 12px;"><strong>* Status Performa wajib dipilih untuk menghindari kesalahan data</strong></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-info btn-block">Log In</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="login-footer">
                <div class="text-center">
                    &copy; ashari wibowo - beverra @2026
                    <p style="margin-top: 5px;">
                        Login dengan scanner <a href="login?machine_name=<?= $machine_name ?>&using_scanner=1">di sini</a>
                    </p>
                </div>
            </div>
        </div>

    </div>

</body>

</html>
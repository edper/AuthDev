<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auth Manager</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <script src="//oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
</head>
<body>

<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="/">Auth Manager</a>
        </div>
        <div class="collapse navbar-collapse">
            <div class="nav navbar-nav">
                <li><a href="/users/">Users</a></li>
                <li><a href="/groups/">Groups</a></li>
                <li><a href="/permissions/">Permissions</a></li>
            </div>
            <?php if (\PhpProjects\AuthDev\Authentication\LoginService::create()->isSessionAuthenticated()) : ?>
                <form action="/auth/logout" method="post" class="navbar-form navbar-right">
                    <input type="hidden" name="originalUrl" value="<?=htmlentities($_SERVER['REQUEST_URI'])?>">
                    <button type="submit" class="btn btn-default" id="logout">Logout</button>
                </form>
            <?php else : ?>
                <div class="nav navbar-nav navbar-right">
                    <a class="btn btn-default navbar-btn" href="/auth/login" id="login">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container-fluid">

    <?=$content;?>

</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body>
</html>

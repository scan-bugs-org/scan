<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/GeneralClassTemplate.php');
header("Content-Type: text/html; charset=" . $CHARSET);
?>
<html>
<head>
    <title>Contacts</title>
    <link
        href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>"
        type="text/css"
        rel="stylesheet"
    />
    <link
        href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo(isset($CSS_VERSION_LOCAL) ? '?ver=' . $CSS_VERSION_LOCAL : ''); ?>"
        type="text/css"
        rel="stylesheet"
    />
    <link
        href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css"
        type="text/css"
        rel="stylesheet"
    />
    <script
        src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js"
        type="text/javascript"
    ></script>
    <script
        src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js"
        type="text/javascript"
    ></script>
    <script type="text/javascript">

    </script>
    <script
        src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js?ver=140310"
        type="text/javascript"
    ></script>
    <script
        src="<?php echo $CLIENT_ROOT; ?>/js/symb/misc.generaltemplate.js?ver=140310"
        type="text/javascript"
    ></script>
</head>
<body>
<?php
$displayLeftMenu = true;
include($SERVER_ROOT . '/header.php');
?>
<div class="navpath">
    <a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
    <b>Contacts</b>
</div>
<!-- This is inner text! -->
<div id="innertext">
    <p>For Information about the SCAN Project, <b>including setting up a new
            collection:</b></p>
    <p>
        Neil Cobb<br>
        <a href="mailto:Neil.Cobb@nau.edu">Neil.Cobb@nau.edu</a><br>
        Northern Arizona University<br>
        928-607-4075
    </p>
</div>

<?php
include($SERVER_ROOT . '/footer.php');
?>
</body>
</html>

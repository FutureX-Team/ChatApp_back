<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body style="text-align: center; margin-top: 200px; background-color: lightblue;">
    <h1> hi
        <?php
            if($name) {
                echo "<h1>basel page</h1>" . $name;
            } else {
                echo "no name found";
            }
        ?>
    </h1>

</body>
</html>

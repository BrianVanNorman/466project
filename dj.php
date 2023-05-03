<html>
    <head>
        <title>DJ Interface</title>
        <h1>Karaoke Queues:</h1>
        <style>
            #table1 {
                width: 60%;
                border: 2px solid #777;
                border-spacing: 10px;
                border-collapse: collapse;
            }
            td {
                border: 1px solid #777;
                padding: 0.5rem;
            }
            .home-button {
                display: inline-block;
                padding: 8px 16px;
                background-color: #f1f1f1;
                border: 1px solid #ccc;
                border-radius: 4px;
                text-align: center;
                text-decoration: none;
                color: #000;
                font-weight: bold;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
<?php
    // Database config file
    include 'secrets.php';
    
    // Connection to db
    try {
        $dsn = "mysql:host=$host;dbname=$dbname";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $e) {
        die("<p>Connection to database failed: ${$e->getMessage()}</p>\n");
    }
?>

<div style="text-align: center;">
    <a href="home.html" class="home-button">Home</a>
</div>

<?php
    //  Function to print values in neat table  //
    function p_table(array &$rows)
    {
        echo "        <table id='table1'>\n";
        echo "             <tr>\n";
        foreach(array_keys($rows[0]) as $heading) {
            echo "                   <td><strong>$heading<strong></td>\n";
        }
        echo "             </tr>\n";
        foreach($rows as $row){
            echo "             <tr>\n";
            foreach($row as $col) {
                echo "                  <td>$col</td>\n";
            }
            echo "             </tr>\n";
        }
        echo "             </tr>\n";
        echo "        </table><br>\n"; 
    }
?>

<!-- First in, first out queue -->
<?php
    $sql = "select Queue.ID,UserName,SongName,Artist,FileID from Queue,User,Song,File  
    where Queue.UserID = User.ID and Queue.FileID = File.ID and Priority = 0 and Played = 'FALSE' 
    and File.SongID = Song.ID 
    order by Queue.ID asc;";
    $result = $pdo->query($sql);
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Queue:</h2>\n";
    p_table($rows);
?>

<!-- Priority queue -->
<div>
    <form method="get">
        <h4>Sort priority queue by:</h4>
        <label for="sortID">Time</label>
        <input type="radio" id="sortID" name="sort" value="0" checked="checked"><br>
        <label for="sortAmount">Amount Payed</label>
        <input type="radio" id="sortAmount" name="sort" value="1"><br>
        <input type="submit" name="sorts" value="Sort"><br>
    </form>
</div>
<?php
    if(isset($_GET['sorts']))
    {
        if($_GET['sort'] == "1")
        {
            $sql = "select Queue.ID,UserName,SongName,Artist,FileID,Payment from Queue,User,Song,File  
            where Queue.UserID = User.ID and Queue.FileID = File.ID and Priority = 1 and Played = 'FALSE' 
            and File.SongID = Song.ID 
            order by Payment desc;";
            $result = $pdo->query($sql);
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "<h2>Priority Queue:</h2>\n";
            p_table($rows); 
        }
        else
        {
            $sql = "select Queue.ID,UserName,SongName,Artist,FileID,Payment from Queue,User,Song,File  
            where Queue.UserID = User.ID and Queue.FileID = File.ID and Priority = 1 and Played = 'FALSE' 
            and File.SongID = Song.ID 
            order by Queue.ID asc;";
            $result = $pdo->query($sql);
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "<h2>Priority Queue:</h2>\n";
            p_table($rows); 
        }
    }
    else 
    {
        $sql = "select Queue.ID,UserName,SongName,Artist,FileID,Payment from Queue,User,Song,File  
        where Queue.UserID = User.ID and Queue.FileID = File.ID and Priority = 1 and Played = 'FALSE' 
        and File.SongID = Song.ID 
        order by Queue.ID asc;";
        $result = $pdo->query($sql);
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>Priority Queue:</h2>\n";
        p_table($rows); 
    }
?>
<br><form method="POST">
    <label for="update">Remove song from queue:</label>
        <select name="id" id="update" style="width: 100px; padding: 0.2rem;">
        <?php
            $sql = "select ID from Queue where Queue.Played = 'FALSE';";
            $result = $pdo->query($sql);
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            foreach($rows as $row) {
                foreach($row as $col) {
                    echo "              <option value=$col>$col</option>\n";
                }
            }
        ?>
        </select>
        <input type="submit" value="Update" name="update">
</form>
<?php
    if(isset($_POST['update']))
    {
        $update_q = $_POST['id'];
        $sql = "update Queue set Played = 1 where ID = '$update_q';";
        $pb = $pdo->prepare($sql);
        $pb->execute();
        if($pb->rowCount() > 0)
        { // Update succeeded
            header("Refresh:0");
        }
    }
?>

    </body>
</html>
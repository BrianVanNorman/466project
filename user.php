<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Singer Sign Up</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-right: 1px solid #ddd;
        }
        th:last-child, td:last-child {
            border-right: none;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        th {
            background-color: #f1f1f1;
            font-weight: bold;
            cursor: pointer;
            text-decoration: underline;
        }
        .results-table {
            margin-top: 80px;
        }
        #selected-song-container {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 300px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: none; /* Hidden until at least 1 song selected */
        }
        #selected-song-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        #versions-container {
            margin-bottom: 10px;
        }
        #user-name {
            width: 100%;
            margin-bottom: 10px;
        }
        #queue-buttons {
            display: flex;
            align-items: center;
        }
        #priority-queue-price {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <h1>Singer Sign Up</h1>
    <!-- Section for establishing and creating connection with DB -->
    <?php
        // Login info for the db
        include 'secrets.php';

        try {
            // Create new PDO connection
            $dsn = "mysql:host=$host;dbname=$dbname";
            $conn = new PDO($dsn, $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    ?>


    <!-- Search field and submit button -->
    <form method="POST">
        <div id="search-section">
            <h3>Search for a Song</h3>
            <input type="text" name="search-input" id="search-input" placeholder="Search by artist, title, or contributor">
            <button type="submit" id="search-button">Search</button>
        </div>
    </form>
    <!-- PHP for submitting search, querying the DB based on that search, and returning an assoc. array of results for displaying later. -->
    <?php
        if (isset($_POST['search-input']) && !empty($_POST['search-input'])) {
            $searchTerm = $_POST['search-input'];
                    
            try {
                // SQL Query returns a list of rows including the SongID, SongName, Artist, a list of Contributors, and a list of Versions.
                $query = "SELECT Song.ID as SongID, Song.SongName as Title, Song.Artist as Artist, 
                    GROUP_CONCAT(CONCAT(Contributors.ContName, ' (', '<i>', Contribution.ContRole, '</i>', ')') SEPARATOR ', ') as Contributors, 
                    GROUP_CONCAT(DISTINCT File.ID SEPARATOR ', ') as FileIDs,
                    GROUP_CONCAT(DISTINCT File.FileVersion SEPARATOR ', ') as Versions
                    FROM Song
                    LEFT JOIN Contribution ON Song.ID = Contribution.SongID
                    LEFT JOIN Contributors ON Contribution.ContributorID = Contributors.ID
                    LEFT JOIN File ON Song.ID = File.SongID
                    WHERE Song.SongName LIKE :searchTerm OR Artist LIKE :searchTerm OR Contributors.ContName LIKE :searchTerm
                    GROUP BY Song.ID
                    ORDER BY Song.Artist, Song.SongName";
            
                $stmt = $conn->prepare($query);
            
                // Use the user's search term to actually query the database.
                $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
                $stmt->execute();
            
                // Get the search results as an associative array
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    ?>

    <!-- (somewhat) dynamic container for displaying the currently selected song, as well as options to select a version and sign up for it. -->
    <form id="song-selection-form" method="POST" name="song-selection-form" novalidate>
        <div id="selected-song-container">
            <h2 id="selected-song-title"></h2>
                <input type="hidden" id="selected-song-id" name="selected-song-id">
                <input type="hidden" id="selected-file-id" name="selected-file-id">
                <div id="versions-container">
                    <!-- radio buttons added here dnyamically from selectSong js -->
                </div>
                <label for="user-name">Your Name:</label>
                <input type="text" id="user-name" name="user-name" required>
                <div id="queue-buttons" style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="submit" id="free-queue-button" style="width: 80px;" name="free-queue-button" onclick="setPaymentRequired(false)">Free Signup</button>
                    <div style="border-left: 1px solid #000; height: 50px;"></div>
                    <div id="priority-queue-section" style="display: flex; flex-direction: column; align-items: flex-end;">
                        <div style="display: inline-flex; align-items: center;">
                            <label for="priority-queue-amount">$</label>
                            <input type="number" id="priority-queue-amount" name="priority-queue-amount" min="1" step="1" value="0" required style="width: 80px; margin-left: 5px;">
                        </div>
                        <div style="display: inline-flex; align-items: center;">
                            <button type="submit" id="priority-queue-button" name="priority-queue-button" onclick="setPaymentRequired(true)">Priority Signup</button>
                        </div>
                    </div>
                </div>
        </div>
    </form>
    <?php
        if (isset($_POST['free-queue-button']) || isset($_POST['priority-queue-button'])) {
            $songId = $_POST['selected-song-id'];
            $fileId = $_POST['selected-file-id'];
            $userName = $_POST['user-name'];
            $priorityQueueAmount = $_POST['priority-queue-amount'];
            $priority = isset($_POST['priority-queue-button']);
        
            try {
                // First get highest UserID, to find new UserID
                $maxUserIdQuery = "SELECT MAX(ID) as maxUserId FROM User";
                $maxUserIdStmt = $conn->query($maxUserIdQuery);
                $maxUserIdResult = $maxUserIdStmt->fetch(PDO::FETCH_ASSOC);
                $newUserId = $maxUserIdResult['maxUserId'] + 1;

                // Insert the new User
                $insertUserQuery = "INSERT INTO User (ID, UserName) VALUES (:newUserId, :userName)";
                $insertUserStmt = $conn->prepare($insertUserQuery);
                $insertUserStmt->bindParam(':newUserId', $newUserId, PDO::PARAM_INT);
                $insertUserStmt->bindParam(':userName', $userName, PDO::PARAM_STR);
                $insertUserStmt->execute();


                // First get highest QueueID, to find new QueueID
                $queueIdQuery = "SELECT MAX(ID) as maxId FROM Queue";
                $queueIdStmt = $conn->prepare($queueIdQuery);
                $queueIdStmt->execute();
                $maxIdResult = $queueIdStmt->fetch(PDO::FETCH_ASSOC);
                $nextQueueId = $maxIdResult['maxId'] + 1;
        
                // Insert new Queue row
                $insertQueueQuery = "INSERT INTO Queue (ID, UserID, FileID, Priority, Payment, Played)
                                     VALUES (:nextQueueId, :userId, :fileId, :priority, :payment, false)";
        
                $insertQueueStmt = $conn->prepare($insertQueueQuery);
                $insertQueueStmt->bindValue(':nextQueueId', $nextQueueId);
                $insertQueueStmt->bindValue(':userId', $newUserId);
                $insertQueueStmt->bindValue(':fileId', $fileId);
                $insertQueueStmt->bindValue(':priority', $priority, PDO::PARAM_BOOL);
                $insertQueueStmt->bindValue(':payment', $priorityQueueAmount);
                $insertQueueStmt->execute();
        
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    ?>

    <!-- Search results table displays a list of all results that match the user's query. -->
    <div id="search-results-section" class="results-table">
        <h3>Search Results</h3>
        <table id="search-results-table">
            <thead>
                <tr>
                    <!-- These table headers connect to sortTable js for sort-by-column functionality -->
                    <th onclick="sortTable(0, 'search-results-table')">Title</th>
                    <th onclick="sortTable(1, 'search-results-table')">Artist</th>
                    <th onclick="sortTable(2, 'search-results-table')">Contributors</th>
                    <th onclick="sortTable(3, 'search-results-table')">Versions</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- PHP to dynamically populate the table based on the contents of the $results assoc. array -->
                <?php if (isset($results)): ?>
                    <?php foreach ($results as $result): ?>
                        <!-- Extra hidden values for accessing SongID and FileIDs later -->
                        <tr data-song-id="<?php echo htmlspecialchars($result['SongID']); ?>"
                            data-file-ids="<?php echo htmlspecialchars($result['FileIDs']); ?>">
                            <td><?php echo htmlspecialchars($result['Title']); ?></td>
                            <td><?php echo htmlspecialchars($result['Artist']); ?></td>
                            <td><?php echo htmlspecialchars_decode($result['Contributors']); ?></td>
                            <td><?php echo htmlspecialchars($result['Versions']); ?></td>
                            <td>
                                <button class="select-song" onclick='selectSong(this)'>Select</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- JS script section -->                    
    <script>
        // Function to select a specific song from the results list into the selected-song-container.
        function selectSong(button) {
            // get the full row of the selected song
            const row = button.parentElement.parentElement;
            // Scrape the values from each respective cell of the row
            const songTitle = row.cells[0].innerText;
            const songArtist = row.cells[1].innerText;
            const versions = row.cells[3].innerText.split(', ');

            // Also scrape the values we hid in the attribute of the tr
            const songId = row.getAttribute('data-song-id');
            const fileIds = row.getAttribute('data-file-ids').split(', ');

            // Set the songID
            document.getElementById('selected-song-id').value = songId;
        
            // Set song title - artist as display of selected-song-container
            document.getElementById('selected-song-title').innerText = songTitle + ' - ' + songArtist;
        
            // Clear any existing radio buttons and filenames from previous selections
            const versionsContainer = document.getElementById('versions-container');
            versionsContainer.innerHTML = ''; // Clear previous versions
        
            // Populate the selected-song-container with one radiobutton and filename for each filename
            // that exists for this song.
            for (let i = 0; i < versions.length; i++) {
                const radioButton = document.createElement('input');
                radioButton.type = 'radio';
                radioButton.name = 'song-version';
                radioButton.value = versions[i];
                radioButton.id = `version-${i}`;
                radioButton.setAttribute('data-file-id', fileIds[i]);

                // Make sure first radio button is selected by default
                if (i === 0) {
                    radioButton.checked = true;
                    document.getElementById('selected-file-id').value = fileIds[i];
                }
            
                const label = document.createElement('label');
                label.htmlFor = `version-${i}`;
                label.innerText = versions[i];

                // Add listener to set the value of the hidden fileid field when radio button is clicked
                radioButton.addEventListener('change', () => {
                    document.getElementById('selected-file-id').value = fileIds[i];
                });
            
                // append radio button, the label, and a line break.
                versionsContainer.appendChild(radioButton);
                versionsContainer.appendChild(label);
                versionsContainer.appendChild(document.createElement('br'));
            }
        
            // Set the selected-song-container as visible in case it wasn't already
            document.getElementById('selected-song-container').style.display = 'block';
        }

        // Helper function to validate submissions between free and priority queue buttons.
        function setPaymentRequired(isRequired) {
            // Get the payment input field by its ID
            const paymentInput = document.getElementById('priority-queue-amount');

            // Set or remove the 'required' attribute based on the value of 'isRequired'
            if (isRequired) {
                paymentInput.setAttribute('required', '');
            } else {
                paymentInput.removeAttribute('required');
                paymentInput.value = "0";
            }
        }

        // Function to sort the whole table by whatever column was clicked on by the user.
        function sortTable(n, tableId) {
            let table, rows, switching, i, x, y, shouldSwitch, switchcount = 0;
            table = document.getElementById(tableId);
            switching = true;
            let dir = "asc"; // Initialize direction as ascending

            // Loop that actually sorts data
            while (switching) {
                switching = false;
                rows = table.rows;
                for (i = 1; i < rows.length - 1; i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];

                    // Whether to sort ascending or descending.
                    if (dir === "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir === "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                // Operate and test for end of loop
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount === 0 && dir === "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    </script>
</body>
</html>

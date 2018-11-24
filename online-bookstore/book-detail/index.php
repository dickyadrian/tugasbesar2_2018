<?php
if (!isset($_COOKIE['access_token'])) {
    header("Location: /login/");
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

        if (!isset($_GET['id'])) {
            http_response_code(400);
            exit;
        }

        $book_id = $mysqli->real_escape_string($_GET['id']);

        $book_query = "SELECT title, author, synopsis, AVG(rating) AS rating FROM (SELECT * FROM `book` WHERE id = '$book_id') AS `book` LEFT OUTER JOIN `order` ON book.id = book_id";
        $review_query = "SELECT buyer_id, username, comments, rating FROM (SELECT buyer_id, comments, rating FROM `order` WHERE book_id = '$book_id' AND rating IS NOT NULL) AS `review` JOIN `user` ON user.id = buyer_id";

        $books = $mysqli->query($book_query);
        $reviews = $mysqli->query($review_query);
        if (!$books || !$reviews) {
            echo "Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error;
            exit;
        }

        $book = $books->fetch_assoc();

        break;
    case 'POST':
        require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

        $access_token = $_COOKIE['access_token'];
        $id = $mysqli->query("SELECT id FROM user WHERE access_token = '$access_token'");
        $id = $id->fetch_assoc();
        $id = $id['id'];

        $buyer_id = $mysqli->real_escape_string($id);
        $book_id = $mysqli->real_escape_string($_POST['id']);
        $quantity = $mysqli->real_escape_string($_POST['quantity']);

        $order_query = "INSERT INTO `order`(buyer_id, book_id, quantity, order_date) VALUES ('$buyer_id', '$book_id', '$quantity', CURRENT_DATE())";

        if (!$order = $mysqli->query($order_query)) {
            echo "Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error;
            exit;
        }

        http_response_code(202);
        echo $mysqli->insert_id;
        exit;
    default:
        http_response_code(405);
        exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?= $book['title'] ?></title>
    <link rel="stylesheet" href ="/header.css" type="text/css"/>
    <link rel="stylesheet" href ="/book-detail/book-detail.css" type="text/css"/>
</head>

<body class="browse">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/header.php' ?>
    <main>
        <section>
            <span class="right">
                <div class="book-cover"><img src="/book-detail/cover/<?= $book_id ?>.jpg" alt="cover of <?= $book['title'] ?>" /></div>
                <div class="rating">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++) { ?><img src="<?= $i <= $book['rating'] ? "full-star" : "empty-star" ?>.png" /><?php } ?>
                    </div>
                    <div class="number">
                        <?= number_format($book['rating'], 1) ?> / 5.0
                    </div>
                </div>
            </span>
            <span class="left">
                <h1 class="book-title"><?= $book['title'] ?></h1>
                <div class="book-author"><?= $book['author'] ?></div>
                <div class="book-synopsis"><?= $book['synopsis'] ?></div>
            </span>
        </section>
        <section>
            <h2>Order</h2>
            <form method="post">
                <input type="hidden" name="id" id="id" value="<?= $book_id ?>" />
                <div class="input">
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" list="quantity-option" onclick="this.select()" />
                    <datalist id="quantity-option">
                        <option value="1"></option>
                        <option value="2"></option>
                        <option value="3"></option>
                        <option value="4"></option>
                        <option value="5"></option>
                    </datalist>
                    <span id="quantity-error"></span>
                </div>
                <div class="button">
                    <button>Order</button>
                </div>
            </form>
        </section>
        <section>
            <h2>Reviews</h2>
            <ul>
                <?php while ($review = $reviews->fetch_assoc()) {
                    $profile_picture = glob($_SERVER['DOCUMENT_ROOT'] . "/profile/pictures/{$review['buyer_id']}.*");
                    $profile_picture = $profile_picture ? basename($profile_picture[0]) : "0.jpg";
                ?>
                <li>
                    <div class="review-profile-picture"><img src="/profile/pictures/<?= $profile_picture ?>" alt="picture of <?= $review['username'] ?>" /></div>
                    <div class="review-rating">
                        <img src="full-star.png" />
                        <?= number_format($review['rating'], 1) ?> / 5.0
                    </div>
                    <div class="review-username"><?= '@' . $review['username'] ?></div>
                    <div class="review-comments"><?= $review['comments'] ?></div>
                </li>
                <?php } ?>
            </ul>
        </section>
    </main>
    <div class="modal">
        <div class="modal-window">
            <div class="modal-header">
                <button class="modal-close" type="button">✖</button>
            </div>
            <div class="modal-content">
                <img class="check" src="check.png" alt="icon of check" />
                <div class="message">
                    <b>Order Success!</b><br />
                    Transaction Number: <span id="order-id"></span>
                </div>
            </div>
        </div>
    </div>
    <script src="modal.js"></script>
    <script src="order.js" type="module"></script>
    <script src="validation.js" type="module"></script>
</body>

</html>
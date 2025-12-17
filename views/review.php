<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;

/** @var string $code
  * @var string $encoded */

$clientRepository = new ClientRepository();
$client = $clientRepository->getByCode($code);
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отзыв о компании "<?= htmlspecialchars($client['title']) ?>"</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>

<div class="container">
    <div class="card">
        <div class="title">
            Оставьте отзыв о компании<br><span class="company-title"><?= htmlspecialchars($client['title']) ?></span>
        </div>

        <form method="post" action="/review/submit">
            <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <input type="hidden" name="encoded" value="<?= htmlspecialchars($encoded) ?>">

            <div class="rating">
                <input type="radio" id="star1" name="rating" value="1" required>
                <label for="star1">★</label>

                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2">★</label>

                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3">★</label>

                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4">★</label>

                <input type="radio" id="star5" name="rating" value="5">
                <label for="star5">★</label>
            </div>

            <div class="field">
                    <textarea
                            name="review"
                            placeholder="Расскажите, что вам понравилось или что можно улучшить"
                            required
                    ></textarea>
            </div>

            <button class="submit" type="submit">
                Отправить отзыв
            </button>
        </form>
    </div>
</div>

</body>
</html>

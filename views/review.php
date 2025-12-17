<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;
use App\Services\LinkService;
use App\Support\Logger;
use Throwable;

/** @var string $code
  * @var string $encoded
  * @var Container $container */

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$ratingValue = null;
$reviewValue = '';
$isSubmitted = false;
$errors = [];

if ($isPost) {
    $code    = $_REQUEST['code'] ?? $code;
    $encoded = $_REQUEST['encoded'] ?? $encoded;

    $ratingValue = isset($_REQUEST['rating']) ? (int) $_REQUEST['rating'] : null;
    $reviewValue = trim((string) ($_REQUEST['review'] ?? ''));

    if ($ratingValue === null || $ratingValue < 1 || $ratingValue > 5) {
        $errors[] = 'Выберите оценку от 1 до 5.';
    }

    if ($reviewValue === '') {
        $errors[] = 'Расскажите, что вам понравилось или что можно улучшить.';
    }

    if (!$errors) {
        $isSubmitted = true;

        $linkService = $container->get(LinkService::class);

        $decoded = $linkService->decodeParams($encoded);

        try {
            Logger::info('Review submitted', [
                'clientCode' => $code,
                'contactId'  => $decoded['contactId'],
                'dealId'     => $decoded['dealId'],
                'rating'     => $ratingValue,
                'review'     => $reviewValue,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable $e) {
            error_log('Review log failed: ' . $e->getMessage());
        }
    }
}

$clientRepository = new ClientRepository();
$client = $clientRepository->getByCode($code);
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отзыв о компании "<?= htmlspecialchars($client['title']) ?>"</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --bg: #f6f7fb;
            --card: #ffffff;
            --primary: #7b9cff;
            --primary-soft: #eef2ff;
            --text: #2b2b2b;
            --muted: #7a7a7a;
            --border: #e4e6ef;
            --radius: 16px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: var(--radius);
            padding: 24px 20px 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }

        .title {
            font-size: 20px;
            font-weight: 400;
            margin-bottom: 20px;
            text-align: center;
        }

        .title .company-title {
            font-size: 24px;
            font-weight: 600;
            background: #fbffaa;
            padding: 0 5px 5px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--muted);
            text-align: center;
            margin-bottom: 24px;
        }
        
        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            background: #fff4e5;
            border: 1px solid #ffd8a8;
            border-radius: 12px;
            color: #b57700;
            font-size: 14px;
        }

        .success {
            text-align: center;
            padding: 18px 12px 8px;
        }

        .success .subtitle {
            margin-bottom: 0;
        }

        /* ====== Rating ====== */
        .rating {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .rating input {
            display: none;
        }

        .rating label {
            font-size: 34px;
            color: #dcdfe8;
            cursor: pointer;
            transition: transform .15s, color .15s;
        }

        .rating label:hover {
            transform: scale(1.15);
        }

        .rating label:hover,
        .rating label:has(~ label:hover),
        .rating input:checked + label,
        .rating label:has(~ input:checked) {
            color: #ffd66e;
        }

        /* ====== Textarea ====== */
        .field {
            margin-bottom: 20px;
        }

        .field textarea {
            width: 100%;
            min-height: 120px;
            resize: vertical;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 14px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s;
        }

        .field textarea::placeholder {
            color: #aaa;
        }

        .field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }

        /* ====== Button ====== */
        .submit {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #7b9cff, #9ab3ff);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }

        .submit:active {
            transform: scale(.98);
            box-shadow: 0 6px 18px rgba(123,156,255,.4);
        }

        .footer-note {
            margin-top: 16px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <?php if ($isSubmitted): ?>
            <div class="success">
                <div class="title">Спасибо за ваш отзыв!</div>
                <div class="subtitle">Мы ценим вашу обратную связь и используем её, чтобы становиться лучше.</div>
            </div>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="alert">
                    <?= htmlspecialchars(implode(' ', $errors)) ?>
                </div>
            <?php endif; ?>

            <div class="title">
                Оставьте отзыв о компании<br><span class="company-title"><?= htmlspecialchars($client['title']) ?></span>
            </div>

            <form method="post" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
                <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                <input type="hidden" name="encoded" value="<?= htmlspecialchars($encoded) ?>">

                <div class="rating">
                    <input type="radio" id="star1" name="rating" value="1" required <?= $ratingValue === 1 ? 'checked' : '' ?>>
                    <label for="star1">★</label>

                    <input type="radio" id="star2" name="rating" value="2" <?= $ratingValue === 2 ? 'checked' : '' ?>>
                    <label for="star2">★</label>

                    <input type="radio" id="star3" name="rating" value="3" <?= $ratingValue === 3 ? 'checked' : '' ?>>
                    <label for="star3">★</label>

                    <input type="radio" id="star4" name="rating" value="4" <?= $ratingValue === 4 ? 'checked' : '' ?>>
                    <label for="star4">★</label>

                    <input type="radio" id="star5" name="rating" value="5" <?= $ratingValue === 5 ? 'checked' : '' ?>>
                    <label for="star5">★</label>
                </div>

                <div class="field">
                    <textarea
                        name="review"
                        placeholder="Расскажите, что вам понравилось или что можно улучшить"
                        required
                    ><?= htmlspecialchars($reviewValue) ?></textarea>
                </div>

                <button class="submit" type="submit">
                    Отправить отзыв
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;

$clientRepository = new ClientRepository();
$client = $clientRepository->getByDomain($_REQUEST['DOMAIN']);
?>

<style>
    .b24-settings-card {
        max-width: 520px;
        background: #ffffff;
        border-radius: 8px;
        padding: 20px 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .b24-settings-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #333;
    }

    .b24-form-group {
        margin-bottom: 16px;
    }

    .b24-form-label {
        display: block;
        font-size: 13px;
        margin-bottom: 6px;
        color: #555;
    }

    .b24-input {
        width: 100%;
        height: 38px;
        padding: 0 10px;
        border: 1px solid #cfd4d9;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color .2s, box-shadow .2s;
    }

    .b24-input:focus {
        outline: none;
        border-color: #2fc6f6;
        box-shadow: 0 0 0 2px rgba(47, 198, 246, 0.2);
    }

    .b24-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 20px;
    }

    .b24-btn {
        background: #2fc6f6;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 8px 18px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background .2s;
    }

    .b24-btn:hover {
        background: #25b5e4;
    }

    .b24-save-status {
        font-size: 13px;
        color: #4bb34b;
        display: none;
    }
</style>

<div class="b24-settings-card">
    <div class="b24-settings-title">
        Настройки
    </div>

    <form id="settings-form">
        <input type="hidden" name="domain" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>" />

        <div class="b24-form-group">
            <label for="webhook" class="b24-form-label">
                Ссылка на вебхук:
            </label>
            <input
                    id="webhook"
                    class="b24-input"
                    type="text"
                    name="webhook"
                    placeholder="Скопируйте в поле ссылку на входящий вебхук"
                    value="<?= htmlspecialchars($client['web_hook'] ?? '') ?>"
            />
        </div>

        <div class="b24-form-group">
            <label for="title" class="b24-form-label">
                Название компании в форме:
            </label>
            <input
                    id="title"
                    class="b24-input"
                    type="text"
                    name="title"
                    placeholder="например: Моя компания"
                    value="<?= htmlspecialchars($client['title'] ?? '') ?>"
            />
        </div>

        <div class="b24-form-group">
            <label for="code" class="b24-form-label">
                Код компании в ссылке на форму отзыва:
            </label>
            <input
                    id="code"
                    class="b24-input"
                    type="text"
                    name="code"
                    placeholder="например: my-company"
                    value="<?= htmlspecialchars($client['code'] ?? '') ?>"
            />
        </div>

        <div class="b24-actions">
            <button type="submit" class="b24-btn">
                Сохранить
            </button>

            <span id="save-status" class="b24-save-status">
                Сохранено
            </span>
        </div>
    </form>
</div>

<script>
    document.getElementById('settings-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const status = document.getElementById('save-status');

        try {
            const response = await fetch('/app-settings/update', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'OK') {
                status.style.display = 'inline';

                setTimeout(() => {
                    status.style.display = 'none';
                }, 3000);
            } else {
                alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
            }

        } catch (e) {
            alert('Ошибка сети: ' + e.message);
        }
    });
</script>

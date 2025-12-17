<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;

$clientRepository = new ClientRepository();
$client = $clientRepository->getByDomain($_REQUEST['DOMAIN']);
?>

<form id="settings-form">
    <input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>" />

    <label for="title">Идентификатор компании для ссылок:</label>
    <input id="title" type="text" name="title" value="<?= htmlspecialchars($client['title'] ?? '') ?>" />

    <button type="submit">Сохранить</button>

    <span id="save-status" style="display:none; margin-left:10px; color:green;">
        Сохранено
    </span>
</form>

<script>
    document.getElementById('settings-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/app-settings/update', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'OK') {
                const status = document.getElementById('save-status');

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
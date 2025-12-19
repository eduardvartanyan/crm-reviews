<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;

$clientRepository = new ClientRepository();
$domain = htmlspecialchars($_REQUEST['DOMAIN']);
$client = $clientRepository->getByDomain($domain);
?>

<style>
    .b24-app-layout {
        display: flex;
        gap: 24px;
        padding: 24px;
        background: #f5f7f8;
        justify-content: flex-start;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .b24-app-main{
        width: 400px;
        flex-shrink: 0;
    }

    .b24-app-sidebar {
        width: 580px;
        flex-shrink: 0;
    }

    @media (max-width: 1000px) {
        .b24-app-layout {
            flex-direction: column;
        }

        .b24-app-main,
        .b24-app-sidebar {
            width: 100%;
        }
    }

    .b24-settings-section {
        background: #f5f7f8;
    }

    .b24-settings-card {
        max-width: 520px;
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
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

    .b24-form-label.b24-required::before {
        content: '*';
        color: #ff5752;
        font-weight: 600;
    }

    .b24-input {
        width: 100%;
        height: 38px;
        padding: 0 10px;
        border: 1px solid #cfd4d9;
        border-radius: 4px;
        color: #2f343b;
        font-weight: 400;
        font-size: 14px;
        transition: border-color .2s, box-shadow .2s;
    }

    .b24-input:focus {
        outline: none;
        border-color: #2fc6f6;
        box-shadow: 0 0 0 2px rgba(47, 198, 246, 0.2);
    }

    .b24-input::placeholder {
        color: #9aa1ab;
        font-weight: 400;
    }

    .b24-form-hint {
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.4;
        color: #8a8f98;
    }

    .b24-form-hint code {
        background: #f1f3f5;
        padding: 1px 4px;
        border-radius: 4px;
        font-size: 11px;
    }

    .b24-form-hint a {
        color: #2067b0;
        text-decoration: none;
        border-bottom: 1px solid rgba(32, 103, 176, 0.3);
        transition: color .15s ease, border-color .15s ease;
    }

    .b24-form-hint a:hover {
        color: #1a5aa0;
        border-bottom-color: rgba(32, 103, 176, 0.6);
    }

    .b24-form-hint a:active {
        color: #144a85;
    }

    .b24-path {
        color: #6f737a;
        font-size: 12px;
        white-space: nowrap;
    }

    .b24-path a {
        color: #2067b0;
        text-decoration: none;
        border-bottom: 1px solid rgba(32, 103, 176, 0.3);
    }

    .b24-path a:hover {
        border-bottom-color: rgba(32, 103, 176, 0.6);
    }

    .b24-path-sep {
        margin: 0 4px;
        color: #b0b4bb;
    }

    .b24-field-name {
        background: #f1f3f5;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 12px;
        color: #555;
        white-space: nowrap;
    }

    .b24-chip {
        display: inline-block;
        background: #e8f0fe;
        color: #1a5aa0;
        padding: 1px 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 500;
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

    .b24-btn:disabled {
        background: #cfd4d9;
        cursor: not-allowed;
        box-shadow: none;
    }

    .b24-save-status {
        font-size: 13px;
        color: #4bb34b;
        display: none;
    }

    .b24-instruction-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px 20px 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .b24-instruction-card code {
        background: #f1f3f5;
        padding: 1px 4px;
        border-radius: 4px;
        font-size: 11px;
    }

    .b24-instruction-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #2f343b;
    }

    .b24-instruction-list {
        margin: 0;
        padding-left: 18px;
        font-size: 13px;
        line-height: 1.6;
        color: #4f545c;
    }

    .b24-instruction-list li {
        margin-bottom: 10px;
    }

    .b24-instruction-list ul li {
        margin-bottom: 6px;
    }

    .b24-instruction-note {
        margin: 16px 0;
        font-size: 12px;
        color: #8a8f98;
    }

    .b24-instruction-text {
        font-size: 13px;
        line-height: 1.6;
        color: #4f545c;
        margin-bottom: 14px;
    }

    .b24-instruction-text strong {
        font-weight: 600;
        color: #2f343b;
    }

    .b24-instruction-media {
        text-align: center;
        margin-top: 4px;
    }
</style>

<div class="b24-app-layout">
    <div class="b24-app-main">

        <div class="b24-settings-section">
            <div class="b24-settings-card">
                <div class="b24-settings-title">
                    Настройки
                </div>

                <form id="settings-form">
                    <input type="hidden" name="domain" value="<?= $domain ?>" />

                    <div class="b24-form-group">
                        <label for="webhook" class="b24-form-label b24-required">
                            Ссылка на вебхук:
                        </label>
                        <input
                                id="webhook"
                                class="b24-input"
                                type="text"
                                name="webhook"
                                placeholder="Скопируйте в поле ссылку на входящий вебхук"
                                value="<?= htmlspecialchars($client['web_hook'] ?? '') ?>"
                                required
                        />
                        <div class="b24-form-hint">
                            Создайте входящий вебхук в разделе
                            <span class="b24-path">
                            Разработчикам
                            <span class="b24-path-sep">›</span>
                            <a href="https://<?= $domain ?>/devops/section/standard/" target="_blank">
                                Другое
                            </a>
                        </span>.
                            Установите права <span class="b24-chip">CRM</span>.
                            Скопируйте значение поля <span class="b24-field-name">Вебхук для вызова REST API</span>
                        </div>
                    </div>

                    <div class="b24-form-group">
                        <label for="title" class="b24-form-label b24-required">
                            Название компании в форме:
                        </label>
                        <input
                                id="title"
                                class="b24-input"
                                type="text"
                                name="title"
                                placeholder="например: Моя компания"
                                value="<?= htmlspecialchars($client['title'] ?? '') ?>"
                                required
                        />
                        <div class="b24-form-hint">
                            Произвольное, узнаваемое клиентами название.
                            Клиенты его увидял в заголовке формы отзыва
                        </div>
                    </div>

                    <div class="b24-form-group">
                        <label for="code" class="b24-form-label b24-required">
                            Код компании в ссылке на форму отзыва:
                        </label>
                        <input
                                id="code"
                                class="b24-input"
                                type="text"
                                name="code"
                                placeholder="например: my-company"
                                value="<?= htmlspecialchars($client['code'] ?? '') ?>"
                                required
                        />
                        <div class="b24-form-hint">
                            Используется в ссылке на отзыв вида <code>https://crm-reviews.ru/r/my-company/</code>.
                            Только латиница, цифры и дефис
                        </div>
                    </div>

                    <div class="b24-actions">
                        <button type="submit" class="b24-btn" id="save-btn" disabled>Сохранить</button>
                        <span id="save-status" class="b24-save-status">Сохранено</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <aside class="b24-app-sidebar">
        <div class="b24-instruction-card">
            <div class="b24-instruction-title">
                Как это работает
            </div>

            <div class="b24-instruction-text">
                Для каждой сделки и контакта формируется <strong>уникальная ссылка на отзыв</strong> вида
                <code>https://crm-reviews.ru/r/my-company/</code>.
            </div>

            <ol class="b24-instruction-list">
                <li>
                    <strong>Формирование и использование ссылки</strong><br>
                    Ссылка создаётся автоматически в бизнес-процессе сделки с помощью специального действия
                    <span class="b24-field-name">Ссылка на отзыв</span>.
                </li>

                <li>
                    <strong>Использование ссылки</strong><br>
                    После выполнения действия дополнительных результатах бизнес-процесса повится <strong>массив</strong> ссылок.
                    <strong>Для каждого контакта своя ссылка</strong>.
                    Её можно использовать в последующих стандартных действиях, например:
                    <ul class="b24-instruction-sublist">
                        <li>отправить клиенту по e-mail,</li>
                        <li>отправить в мессенджер,</li>
                        <li>отправить по SMS.</li>
                    </ul>
                    <div class="b24-instruction-media">
                        <img src="/images/scr_1.png" width="480px" />
                    </div>
                </li>

                <li>
                    <strong>Заполнение отзыва клиентом</strong><br>
                    Клиент переходит по ссылке, открывается форма отзыва:
                    <ul class="b24-instruction-sublist">
                        <li>ставит оценку,</li>
                        <li>при желании оставляет комментарий,</li>
                        <li>сохраняет отзыв.</li>
                    </ul>
                    <div class="b24-instruction-media">
                        <img src="/images/scr_2.png" width="240px" />
                    </div>
                </li>

                <li>
                    <strong>Результат в Битрикс24</strong><br>
                    После сохранения отзыв автоматически:
                    <ul class="b24-instruction-sublist">
                        <li>добавляется в ленту сделки,</li>
                        <li>добавляется в ленту контакта.</li>
                    </ul>
                    Таким образом, вся обратная связь сохраняется прямо в CRM и доступна для дальнейшей работы.
                    <div class="b24-instruction-media">
                        <img src="/images/scr_3.png" width="520px" />
                    </div>
                </li>
            </ol>
        </div>
    </aside>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('settings-form');
        const saveBtn = document.getElementById('save-btn');

        const requiredFields = form.querySelectorAll('[required]');

        function checkFormValidity() {
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                }
            });

            saveBtn.disabled = !isValid;
        }

        checkFormValidity();

        requiredFields.forEach(field => {
            field.addEventListener('input', checkFormValidity);
            field.addEventListener('change', checkFormValidity);
        });
    });

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
                }, 2000);
            } else {
                alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
            }

        } catch (e) {
            alert('Ошибка сети: ' + e.message);
        }
    });
</script>

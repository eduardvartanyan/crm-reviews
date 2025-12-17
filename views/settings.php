<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;

$clientRepository = new ClientRepository();
$client = $clientRepository->getByDomain($_REQUEST['DOMAIN']);
?>

<form action="/">
    <label for="title">Идентификатор компании для ссылок:</label>
    <input id="title" type="text" name="title" value="<?= $client['title'] ?>" />
    <button type="submit">Сохранить</button>
</form>


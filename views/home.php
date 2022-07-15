<?php
$exampleListTable = new Cloakup_Table();
$exampleListTable->prepare_items();
?>
    <div class="wrap">
        <div id="icon-users" class="icon32"></div>
        <h2 class="wp-heading-inline">Campanhas</h2>
        <a href="?page=cloakup.php&action=add" class="page-title-action">Adicionar nova</a>
        <?php $exampleListTable->display(); ?>
    </div>
<?php
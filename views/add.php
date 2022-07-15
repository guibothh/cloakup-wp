<?php

// CHECK HTTP METHOD IS POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST)) {
        $json = file_get_contents($_FILES['config']['tmp_name']);

        $config = json_decode($json, true);

        $post = $_POST['post_id'];
        $campaign_id = $config['id'];
        $campaign_name = $config['name'];
        $campaign_slug = $config['slug'];
        $api_key = $config['api_key'];

        $errors = array();
        if (empty($post)) {
            $errors['post_id'] = 'O campo post_id é obrigatório';
        }
        if (empty($campaign_id)) {
            $errors['campaign_id'] = 'O campo campaign_id é obrigatório';
        }
        if (empty($campaign_name)) {
            $errors['campaign_name'] = 'O campo campaign_name é obrigatório';
        }
        if (empty($campaign_slug)) {
            $errors['campaign_slug'] = 'O campo campaign_slug é obrigatório';
        }
        if (empty($api_key)) {
            $errors['api_key'] = 'O campo api_key é obrigatório';
        }

        if (empty($errors)) {
            $cloakup = new Cloakup_Table();

            if ($cloakup->exists_page($post)) {
                $errors['post_id'] = 'Já existe uma campanha para essa página';
            } else {
                $result = $cloakup->create_campaign($_POST['post_id'], $config['id'], $config['name'], $config['slug'], $config['api_key']);
            }

        }
    }
}
$posts = get_posts(array(
    'post_type' => 'page'
));
?>
    <div class="wrap">
        <h2>Adicionar Campanha</h2>
        <?php
        if (!empty($errors)) {
            echo '<div class="error"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul></div>';
        } elseif (isset($result)) {
            echo '<div class="updated"><p>Campanha adicionada com sucesso! <a href="?page=cloakup.php">Ver campanha</a> </p></div>';
        }
        ?>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="post_id">Página</label></th>
                    <td>
                        <select name="post_id">
                            <?php foreach ($posts as $post) { ?>
                                <option value="<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="config">Configuração</label></th>
                    <td>
                        <input type="file" name="config" id="config">
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Adicionar">
            </p>
        </form>
    </div>
<?php

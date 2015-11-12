<?php echo $header; ?>

<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?>
        <a href="<?php echo $breadcrumb['href']; ?>">
            <?php echo $breadcrumb['text']; ?>
        </a>
        <?php } ?>
    </div>

    <?php if (!empty($error_warning)) { ?>
    <div class="warning">
        <?php echo $error_warning; ?>
    </div>
    <?php } ?>

    <div class="box">
        <div class="heading">
            <h1>
                <img src="view/image/module.png" alt="Expressly"/>
                <?php echo $heading_title; ?>
            </h1>

            <div class="buttons">
                <a onclick="$('#form').submit();return false;" class="button">
                    <?php echo $button_save; ?>
                </a>
                <a href="<?php echo $cancel; ?>" class="button">
                    <?php echo $button_cancel; ?>
                </a>
            </div>
        </div>

        <div class="content">
            <form action="<?php echo $action; ?>" method="POST" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td><?php echo $apikey; ?></td>
                        <td>
                            <input type="text" name="expressly_apikey" value="<?php echo $expressly_apikey; ?>" size="120"/>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <comment><?php echo $apikey_comment; ?></comment>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>

<?php echo $footer; ?>
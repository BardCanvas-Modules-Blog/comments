<?php
/**
 * Request login page
 * 
 * @package    BardCanvas
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */
?>

<h1>
    <?= $language->errors->access_denied ?>
</h1>

<div class="framed_content state_highlight" style="padding: 40px 20px;" align="center">
    <i class="fa fa-warning"></i>
    <?= $language->errors->page_requires_login ?>
</div>

<script type="text/javascript">
    $(document).ready(function() { show_login_form(); });
</script> 

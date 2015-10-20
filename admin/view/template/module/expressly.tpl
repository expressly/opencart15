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
                    <?php echo (!$registered ? $button_register : $button_save); ?>
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
                        <td><?php echo $image; ?></td>
                        <td>
                            <input type="text" name="expressly_image" value="<?php echo $expressly_image; ?>" size="100"/>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <comment><?php echo $image_comment; ?></comment>
                        </td>
                    <tr>
                        <td></td>
                        <td>
                            <img src="<?php echo $image_url; ?>" width="100px" height="100px" style="border: 1px solid black;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $terms; ?></td>
                        <td>
                            <input type="text" name="expressly_terms" value="<?php echo $expressly_terms; ?>" size="100"/>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <comment><?php echo $terms_comment; ?></comment>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $privacy; ?></td>
                        <td>
                            <input type="text" name="expressly_privacy" value="<?php echo $expressly_privacy; ?>" size="100"/>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <comment><?php echo $privacy_comment; ?></comment>
                        </td>
                    </tr>
                    <!--
                    <tr>
                        <td><?php echo $destination; ?></td>
                        <td>
                            <input type="text" name="expressly_destination"
                                   value="<?php echo $expressly_destination; ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $offer; ?></td>
                        <td>
                            <input type="hidden" name="expressly_offer" value="0"/>
                            <input type="checkbox" name="expressly_offer"
                                   value="1" <?php echo ($expressly_offer == 1 ? ' checked="checked"' : ''); ?> />
                        </td>
                    </tr>
                    !-->
                    <tr>
                        <td><?php echo $password; ?></td>
                        <td>
                            <input type="text" name="expressly_password" value="<?php echo $expressly_password; ?>" disabled size="100"/>
                        </td>
                    </tr>
                </table>
                <table id="module" class="list">
                    <thead>
                    <tr>
                        <td class="left"><?php echo $entry_layout; ?></td>
                        <td class="left"><?php echo $entry_position; ?></td>
                        <td class="left"><?php echo $entry_status; ?></td>
                        <td class="right"><?php echo $entry_sort_order; ?></td>
                        <td></td>
                    </tr>
                    </thead>
                    <?php $module_row = 0; ?>
                    <?php foreach ($modules as $module) { ?>
                    <tbody id="module-row<?php echo $module_row; ?>">
                    <tr>
                        <td class="left"><select name="expressly_module[<?php echo $module_row; ?>][layout_id]">
                                <?php foreach ($layouts as $layout) { ?>
                                <?php if ($layout['layout_id'] == $module['layout_id']) { ?>
                                <option value="<?php echo $layout['layout_id']; ?>" selected="selected"><?php echo $layout['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $layout['layout_id']; ?>"><?php echo $layout['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                        <td class="left"><select name="expressly_module[<?php echo $module_row; ?>][position]">
                                <?php if ($module['position'] == 'content_top') { ?>
                                <option value="content_top" selected="selected"><?php echo $text_content_top; ?></option>
                                <?php } else { ?>
                                <option value="content_top"><?php echo $text_content_top; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'content_bottom') { ?>
                                <option value="content_bottom" selected="selected"><?php echo $text_content_bottom; ?></option>
                                <?php } else { ?>
                                <option value="content_bottom"><?php echo $text_content_bottom; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'column_left') { ?>
                                <option value="column_left" selected="selected"><?php echo $text_column_left; ?></option>
                                <?php } else { ?>
                                <option value="column_left"><?php echo $text_column_left; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'column_right') { ?>
                                <option value="column_right" selected="selected"><?php echo $text_column_right; ?></option>
                                <?php } else { ?>
                                <option value="column_right"><?php echo $text_column_right; ?></option>
                                <?php } ?>
                            </select></td>
                        <td class="left"><select name="expressly_module[<?php echo $module_row; ?>][status]">
                                <?php if ($module['status']) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select></td>
                        <td class="right"><input type="text" name="expressly_module[<?php echo $module_row; ?>][sort_order]" value="<?php echo $module['sort_order']; ?>" size="3" /></td>
                        <td class="left"><a onclick="$('#module-row<?php echo $module_row; ?>').remove();" class="button"><?php echo $button_remove; ?></a></td>
                    </tr>
                    </tbody>
                    <?php $module_row++; ?>
                    <?php } ?>
                    <tfoot>
                    <tr>
                        <td colspan="4"></td>
                        <td class="left"><a onclick="addModule();" class="button"><?php echo $button_add_module; ?></a></td>
                    </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript"><!--
    var module_row = <?php echo $module_row; ?>;

    function addModule() {
        html  = '<tbody id="module-row' + module_row + '">';
        html += '  <tr>';
        html += '    <td class="left"><select name="expressly_module[' + module_row + '][layout_id]">';
    <?php foreach ($layouts as $layout) { ?>
            html += '      <option value="<?php echo $layout['layout_id']; ?>"><?php echo addslashes($layout['name']); ?></option>';
        <?php } ?>
        html += '    </select></td>';
        html += '    <td class="left"><select name="expressly_module[' + module_row + '][position]">';
        html += '      <option value="content_top"><?php echo $text_content_top; ?></option>';
        html += '      <option value="content_bottom"><?php echo $text_content_bottom; ?></option>';
        html += '      <option value="column_left"><?php echo $text_column_left; ?></option>';
        html += '      <option value="column_right"><?php echo $text_column_right; ?></option>';
        html += '    </select></td>';
        html += '    <td class="left"><select name="expressly_module[' + module_row + '][status]">';
        html += '      <option value="1" selected="selected"><?php echo $text_enabled; ?></option>';
        html += '      <option value="0"><?php echo $text_disabled; ?></option>';
        html += '    </select></td>';
        html += '    <td class="right"><input type="text" name="expressly_module[' + module_row + '][sort_order]" value="" size="3" /></td>';
        html += '    <td class="left"><a onclick="$(\'#module-row' + module_row + '\').remove();" class="button"><?php echo $button_remove; ?></a></td>';
        html += '  </tr>';
        html += '</tbody>';

        $('#module tfoot').before(html);

        module_row++;
    }
    //--></script>
<?php echo $footer; ?>
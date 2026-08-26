<?php $colspan = count($cols) + ($hidden_id ? 1 : 0); ?>

<form name="<?php echo $form_name ?>" id="<?php echo $form_name ?>" action="<?php echo $form_action ?>" <?php if($type ==='list'){echo 'onsubmit="return list_submit(this);"';} ?> method="post" enctype="multipart/form-data">
    <?php foreach ($hidden as $key => $value): ?>
        <input type="hidden" name="<?php echo $key ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES) ?>">
    <?php endforeach; ?>

    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <?php if($hidden_id): ?>
                    <col style="width:40px;">
                <?php endif; ?>
                <?php foreach ($cols as $col): ?>
                    <col <?php echo $col_style=$col['width']?"style='width:{$col['width']}'":"" ?>>
                <?php endforeach; ?>
            </colgroup>
            <thead>
                <tr>
                    <?php if($hidden_id): ?>
                        <th scope="col" class="bo-right">
                            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                        </th>
                    <?php endif; ?>
                    <?php foreach ($cols as $col): ?>
                        <th scope="col">
                            <?php echo $col['label'] ?>
                        </th>
                    <?php endforeach; ?>   
                </tr>
            </thead>

            <tbody>
            <?php
            for ($i=0; $i<count($result_list); $i++) {
                $row = $result_list[$i];
                $bg = 'bg'.($i % 2);
            ?>
            <tr class="<?php echo $bg; ?>">
                <?php if($hidden_id): ?>
                    <td>
                        <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row[$hidden_id] ?? '') ?></label>
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                        <input type="hidden" name="<?php echo $hidden_id.'['.$i.']' ?>" value="<?php echo $row[$hidden_id] ?? '' ?>">
                    </td>
                <?php endif; ?>
                <?php foreach ($cols as $col_name => $col): ?>
                    <td class="<?php if(isset($col['td_class'])) echo $col['td_class'] ?>">
                       <?php adm_input_field('list', $col_name, $col, $row, $i); ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php
            }
            if ($i == 0) {
                echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <?php if(count($actions) > 0): ?>
        <div class="btn_list01 btn_list">
            <?php foreach ($actions as $action) : ?>
                <input type="submit" name="act_button" value="<?php echo $action ?>" onclick="document.pressed=this.value"> 
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</form>

<script>
    if (typeof list_submit !== 'function') {
        window.list_submit = function(f)
        {
            <?php if($type==='list'): ?>
                if (!is_checked("chk[]")) {
                    alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
                    return false;
                }
            
                if(document.pressed == "선택삭제") {
                    if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
                        return false;
                    }
                }
            <?php endif; ?>
            return true;
        }
    }

    if (typeof check_all !== 'function') {
        window.check_all = function(f)
        {
            var chk = document.getElementsByName("chk[]");

            for (i=0; i<chk.length; i++)
                chk[i].checked = f.chkall.checked;
        }
    }
</script>
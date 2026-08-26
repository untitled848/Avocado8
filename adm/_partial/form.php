<?php
if(!defined('_GNUBOARD_'))exit;

/**
 * 어드민 단일 레코드 입력 폼 파셜 (type=form)
 *
 * adm_insert_form('form', $form_name, $form_action, $row, $hidden, $cols, $hidden_id, $actions)
 * 형태로 호출된다. list.php 와 동일한 $cols 정의 구조를 공유하므로,
 * 같은 컬럼 정의로 목록(일괄수정)과 단일 입력 폼을 모두 그릴 수 있다.
 *
 * 사용 변수 (모두 호출 함수 adm_insert_form 의 인자로 전달됨):
 *   $form_name    폼 name/id
 *   $form_action  action URL
 *   $cols         컬럼정의 col_name => array(
 *                     'label'       => 항목명(th),
 *                     'type'        => text|number|url|email|password|date|
 *                                      select|checkbox|radio|textarea|file|html|hidden,
 *                     'options'     => select/radio 보기값. [value] 또는 [value=>label],
 *                     'placeholder' => 입력 안내문구,
 *                     'help'        => 도움말(입력칸 아래 별도 줄),
 *                     'value'       => 레코드에 값이 없을 때 기본값,
 *                     'attr'        => input/select 에 덧붙일 속성 문자열,
 *                     'html'        => type=html 일 때 출력. 문자열 또는 function($row),
 *                  )
 *   $result_list  편집할 단일 레코드(연관배열). 비어있으면 생성 폼.
 *   $hidden       추가 hidden input (연관배열)
 *   $hidden_id    기본키 컬럼명. 값이 있으면 hidden 으로 출력(수정용)
 *   $actions      submit 버튼 라벨 배열 (기본 ['확인'])
 */

// 단일 레코드 확보 — 목록형(0-base)으로 넘어오면 첫 행 사용
$row = (isset($result_list) && is_array($result_list)) ? $result_list : array();
if (isset($row[0]) && is_array($row[0])) $row = $row[0];

// 폼에서는 list 의 기본 액션(선택수정/선택삭제) 대신 '확인' 을 기본값으로
$form_actions = (isset($actions) && is_array($actions) && count($actions)) ? $actions : array('확인');
if ($form_actions === array('선택수정', '선택삭제')) $form_actions = array('확인');
?>

<form name="<?php echo $form_name ?>" id="<?php echo $form_name ?>" action="<?php echo $form_action ?>" method="post" enctype="multipart/form-data">
    <?php foreach ($hidden as $key => $value): ?>
        <input type="hidden" name="<?php echo $key ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES) ?>">
    <?php endforeach; ?>
    <?php if ($hidden_id && isset($row[$hidden_id])): ?>
        <input type="hidden" name="<?php echo $hidden_id ?>" value="<?php echo htmlspecialchars($row[$hidden_id], ENT_QUOTES) ?>">
    <?php endif; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width:140px;">
                <col>
            </colgroup>
            <tbody>
            <?php foreach ($cols as $col_name => $col):
                $type_f = isset($col['type']) ? $col['type'] : 'text';

                // hidden 타입은 행으로 그리지 않고 hidden input 만 출력
                if ($type_f === 'hidden') {
                    $hv = array_key_exists($col_name, $row) ? $row[$col_name] : (isset($col['value']) ? $col['value'] : '');
                    echo '<input type="hidden" name="'.$col_name.'" value="'.htmlspecialchars($hv, ENT_QUOTES).'">'."\n";
                    continue;
                }
            ?>
                <tr>
                    <th scope="row"><?php echo isset($col['label']) ? $col['label'] : $col_name ?></th>
                    <td>
                        <?php adm_input_field('form', $col_name, $col, $row, 0); ?>
                        <?php if (!empty($col['help'])): ?>
                            <p class="frm_info" style="margin-top:6px;"><?php echo $col['help'] ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <?php foreach ($form_actions as $action): ?>
            <input type="submit" name="act_button" value="<?php echo htmlspecialchars($action, ENT_QUOTES) ?>" onclick="document.pressed=this.value" class="btn_submit" accesskey="s">
        <?php endforeach; ?>
    </div>
</form>

<?php
/**
 * 정산 payload 편집 partial
 *
 * 호출 측에서 다음 변수를 정의하고 include 한다:
 *   $payload_field    필드명 prefix (보통 'payload')
 *   $payload_initial  array of items (재편집 시 초기 데이터, 옵션)
 *   $payload_uid      여러 인스턴스 분리용 고유 접미사 (옵션)
 *
 * 결과적으로 payload[type][], payload[id][], payload[qty][] hidden input 들이 누적된다.
 */
if (!defined('_GNUBOARD_')) exit;
if (!isset($payload_field) || $payload_field === '') $payload_field = 'payload';
if (!isset($payload_uid)) $payload_uid = 'pl';
if (!isset($payload_initial) || !is_array($payload_initial)) $payload_initial = array();

$_types = function_exists('payout_item_types') ? payout_item_types() : array();
$_uid = preg_replace('/[^a-zA-Z0-9_]/', '', $payload_uid);
?>
<div class="cpe-wrap" id="cpe_<?php echo $_uid; ?>" data-field="<?php echo htmlspecialchars($payload_field, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="cpe-add">
        <select class="cpe-type">
            <?php foreach ($_types as $tk => $td) { ?>
                <option value="<?php echo htmlspecialchars($tk, ENT_QUOTES, 'UTF-8'); ?>"
                        data-search="<?php echo htmlspecialchars(isset($td['search_url'])?$td['search_url']:'', ENT_QUOTES, 'UTF-8'); ?>"
                        data-needid="<?php echo !empty($td['id']) ? '1' : '0'; ?>"
                        data-needqty="<?php echo !empty($td['qty']) ? '1' : '0'; ?>"
                ><?php echo htmlspecialchars(isset($td['label'])?$td['label']:$tk, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php } ?>
        </select>
        <span class="cpe-id-wrap">
            <input type="text" class="cpe-name" placeholder="이름 검색" autocomplete="off">
            <input type="hidden" class="cpe-id" value="">
            <ul class="cpe-suggest" style="display:none"></ul>
        </span>
        <span class="cpe-qty-wrap">
            <input type="number" class="cpe-qty" value="1" min="1">
        </span>
        <button type="button" class="btn btn_01 cpe-add-btn">추가</button>
    </div>
    <ul class="cpe-list">
        <?php foreach ($payload_initial as $idx => $item) {
            $t = isset($item['type']) ? $item['type'] : '';
            if ($t === '') continue;

            $i = isset($item['id'])   ? (int)$item['id']  : 0;
            $q = isset($item['qty'])  ? (int)$item['qty'] : 1;
            $label = isset($_types[$t]['label']) ? $_types[$t]['label'] : $t;
            if ($t === 'item' && $i ) {
                $it_name=sql_fetch (" SELECT it_name from {$g5['item_table']} where it_id='{$i}' ")['it_name'];
                $label .= ' ' . $it_name;
            }
           
        ?>
            <li class="cpe-item">
                <span class="cpe-item-label"><?php echo htmlspecialchars($label.' #'.$i.' × '.$q, ENT_QUOTES, 'UTF-8'); ?></span>
                <input type="hidden" name="<?php echo htmlspecialchars($payload_field, ENT_QUOTES, 'UTF-8'); ?>[type][]" value="<?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="<?php echo htmlspecialchars($payload_field, ENT_QUOTES, 'UTF-8'); ?>[id][]"   value="<?php echo $i; ?>">
                <input type="hidden" name="<?php echo htmlspecialchars($payload_field, ENT_QUOTES, 'UTF-8'); ?>[qty][]"  value="<?php echo $q; ?>">
                <button type="button" class="cpe-del">×</button>
            </li>
        <?php } ?>
    </ul>
</div>
<script>
(function(){
    var box = document.getElementById('cpe_<?php echo $_uid; ?>');
    if (!box) return;
    var field = box.getAttribute('data-field');
    var sel   = box.querySelector('.cpe-type');
    var nameI = box.querySelector('.cpe-name');
    var idI   = box.querySelector('.cpe-id');
    var qtyI  = box.querySelector('.cpe-qty');
    var idWrap= box.querySelector('.cpe-id-wrap');
    var qtyWrap= box.querySelector('.cpe-qty-wrap');
    var sugg  = box.querySelector('.cpe-suggest');
    var addBtn= box.querySelector('.cpe-add-btn');
    var list  = box.querySelector('.cpe-list');

    function refreshUI(){
        var opt = sel.options[sel.selectedIndex];
        var needId  = opt.getAttribute('data-needid')  === '1';
        var needQty = opt.getAttribute('data-needqty') === '1';
        idWrap.style.display  = needId  ? '' : 'none';
        qtyWrap.style.display = needQty ? '' : 'none';
        if (!needId) { nameI.value=''; idI.value=''; sugg.style.display='none'; sugg.innerHTML=''; }
    }
    sel.addEventListener('change', refreshUI);
    refreshUI();

    var debounceTimer = null;
    nameI.addEventListener('input', function(){
        var opt = sel.options[sel.selectedIndex];
        var url = opt.getAttribute('data-search');
        var kw  = nameI.value.trim();
        idI.value = '';
        if (!url || kw.length < 1) { sugg.style.display='none'; sugg.innerHTML=''; return; }
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function(){
            var fd = new FormData(); fd.append('keyword', kw);
            fd.append('list_obj','cpe_<?php echo $_uid; ?>'); fd.append('input_obj','cpe_name'); fd.append('output_obj','cpe_id');
            fetch(url, {method:'POST', body:fd, credentials:'same-origin'})
                .then(function(r){ return r.text(); })
                .then(function(html){
                    sugg.innerHTML = html;
                    sugg.style.display = 'block';
                    sugg.querySelectorAll('a').forEach(function(a){
                        a.addEventListener('click', function(e){
                            e.preventDefault();
                            // 그누 select_item 패턴 호환: onclick 인자 (list_obj, input_obj, name, output_obj, id)
                            var oc = a.getAttribute('onclick') || '';
                            var m = oc.match(/select_item\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/);
                            if (m) { nameI.value = m[3]; idI.value = m[5]; }
                            sugg.style.display='none';
                        });
                    });
                });
        }, 200);
    });

    addBtn.addEventListener('click', function(){
        var opt = sel.options[sel.selectedIndex];
        var type = sel.value;
        var needId  = opt.getAttribute('data-needid')  === '1';
        var needQty = opt.getAttribute('data-needqty') === '1';
        var id  = needId  ? (parseInt(idI.value, 10) || 0) : 0;
        var qty = needQty ? (parseInt(qtyI.value, 10) || 1) : 1;
        if (needId && id <= 0) { alert('항목을 선택하세요.'); return; }
        if (needQty && qty < 1) { alert('수량은 1 이상.'); return; }
        var itemName = needId ? nameI.value.trim() : '';
        var label = opt.textContent + (itemName ? ' ' + itemName : '') + (needId ? ' #' + id : '') + (needQty ? ' × ' + qty : '');
        var li = document.createElement('li');
        li.className = 'cpe-item';
        li.innerHTML =
            '<span class="cpe-item-label"></span>' +
            '<input type="hidden" name="'+field+'[type][]" value="">' +
            '<input type="hidden" name="'+field+'[id][]" value="">' +
            '<input type="hidden" name="'+field+'[qty][]" value="">' +
            '<button type="button" class="cpe-del">×</button>';
        li.querySelector('.cpe-item-label').textContent = label;
        var ins = li.querySelectorAll('input');
        ins[0].value = type; ins[1].value = id; ins[2].value = qty;
        li.querySelector('.cpe-del').addEventListener('click', function(){ li.parentNode.removeChild(li); });
        list.appendChild(li);
        // reset
        nameI.value=''; idI.value=''; qtyI.value=1;
        if (needId) sugg.style.display='none';
    });

    // initial delete buttons
    list.querySelectorAll('.cpe-del').forEach(function(btn){
        btn.addEventListener('click', function(){ var li = btn.closest('li'); if (li) li.parentNode.removeChild(li); });
    });
})();
</script>

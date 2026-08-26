<?php
if (!defined('_GNUBOARD_')) exit;
if (!isset($maker_items) || !is_array($maker_items)) {
	$maker_items = array();
}
if (empty($reuse_sel_id)) {
	$reuse_sel_id = 'add_item_reuse_sel';
}
$maker_reuse_only = !empty($maker_reuse_only);
?>
<div class="add-item-reuse">
	<select name="reuse_it_id" id="<?php echo htmlspecialchars($reuse_sel_id, ENT_QUOTES, 'UTF-8'); ?>" class="frm_input add-item-reuse-select" onchange="if (window.avoAddItemReuseToggle) window.avoAddItemReuseToggle(this);">
		<?php if (!$maker_reuse_only) { ?>
		<option value="0">신규 아이템 추가</option>
		<?php } ?>
		<?php foreach ($maker_items as $_mit) { ?>
		<option value="<?php echo (int)$_mit['it_id']; ?>"><?php echo htmlspecialchars(isset($_mit['it_name']) ? (string)$_mit['it_name'] : '', ENT_QUOTES, 'UTF-8'); ?></option>
		<?php } ?>
	</select>
</div>
<script>
(function(){
	function avoAddItemReuseToggle(sel) {
		if (!sel || !sel.form) return;
		var form = sel.form;
		var reuse = (parseInt(sel.value, 10) || 0) > 0;
		var box = form.querySelector('.add-item-form');
		var btn = form.querySelector('.js-add-item-submit');
		if (box) {
			if (reuse) box.setAttribute('hidden', 'hidden');
			else box.removeAttribute('hidden');
			var fields = box.querySelectorAll('input, select, textarea');
			for (var i = 0; i < fields.length; i++) {
				var f = fields[i];
				if (reuse) {
					if (f.required) f.setAttribute('data-was-required', '1');
					f.required = false;
					f.disabled = true;
				} else {
					f.disabled = false;
					if (f.getAttribute('data-was-required') === '1' || (f.classList && f.classList.contains('required'))) {
						f.required = true;
					}
				}
			}
		}
		if (btn) btn.textContent = reuse ? '지급하기' : '등록하기';
	}
	window.avoAddItemReuseToggle = avoAddItemReuseToggle;
	var sel = document.getElementById(<?php echo json_encode($reuse_sel_id); ?>);
	if (sel) {
		sel.addEventListener('change', function(){ avoAddItemReuseToggle(sel); });
		avoAddItemReuseToggle(sel);
	}
})();
</script>

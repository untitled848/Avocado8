/* ============================================================
 * Category chips (item_list / shop_list)
 *   Container: <details class="cat-chips-panel"><div class="cat-chips" data-name="cf_xxx_spr[]"></div></details>
 * ============================================================ */
(function(){
	function autoSize(inp){ inp.style.width = Math.max(40, (inp.value||'').length*9 + 16) + 'px'; }

	var dragSrc = null;
	function onDragStart(e){
		dragSrc = this; this.classList.add('chip-dragging');
		e.dataTransfer.effectAllowed='move';
		try{e.dataTransfer.setData('text/plain','x');}catch(_){}
	}
	function onDragOver(e){ e.preventDefault(); this.classList.add('chip-drop-over'); return false; }
	function onDragLeave(){ this.classList.remove('chip-drop-over'); }
	function onDrop(e){
		e.stopPropagation(); e.preventDefault();
		this.classList.remove('chip-drop-over');
		if (!dragSrc || dragSrc===this) return false;
		var rect = this.getBoundingClientRect();
		var after = (e.clientX - rect.left) > rect.width/2;
		this.parentNode.insertBefore(dragSrc, after ? this.nextSibling : this);
		return false;
	}
	function onDragEnd(){ this.classList.remove('chip-dragging'); }

	function bindChip(chip){
		var edit = chip.querySelector('.cat-chip-edit');
		var hidden = chip.querySelector('input[type=hidden]');
		var del = chip.querySelector('.cat-chip-del');
		if (edit && hidden){
			edit.addEventListener('input', function(){ hidden.value = edit.value; autoSize(edit); });
			autoSize(edit);
		}
		if (del){
			del.addEventListener('click', function(){ chip.parentNode.removeChild(chip); });
		}
		chip.addEventListener('dragstart', onDragStart);
		chip.addEventListener('dragover',  onDragOver);
		chip.addEventListener('dragleave', onDragLeave);
		chip.addEventListener('drop',      onDrop);
		chip.addEventListener('dragend',   onDragEnd);
	}

	function makeChip(text, name){
		var chip = document.createElement('span');
		chip.className = 'cat-chip';
		chip.setAttribute('draggable','true');
		var handle = document.createElement('span'); handle.className='cat-chip-handle'; handle.title='드래그'; handle.textContent='⋮⋮';
		var edit = document.createElement('input'); edit.type='text'; edit.className='cat-chip-edit'; edit.value=text;
		var del = document.createElement('button'); del.type='button'; del.className='cat-chip-del'; del.title='삭제'; del.textContent='×';
		var hidden = document.createElement('input'); hidden.type='hidden'; hidden.name=name; hidden.value=text;
		chip.appendChild(handle); chip.appendChild(edit); chip.appendChild(del); chip.appendChild(hidden);
		bindChip(chip);
		return chip;
	}

	function initList(list){
		var name = list.getAttribute('data-name') || 'cat[]';
		// 바로 다음 형제 .cat-add-row 에서 입력/버튼 탐색
		var addRow = list.nextElementSibling;
		while (addRow && !addRow.classList.contains('cat-add-row')) addRow = addRow.nextElementSibling;
		var addInput = addRow ? addRow.querySelector('.cat-add-input') : null;
		var addBtn   = addRow ? addRow.querySelector('.cat-add-btn')   : null;

		Array.prototype.forEach.call(list.querySelectorAll('.cat-chip:not(.chip-readonly)'), bindChip);

		function addNew(){
			var v = (addInput.value||'').trim();
			if (!v) return;
			list.appendChild(makeChip(v, name));
			addInput.value = '';
			addInput.focus();
		}
		if (addBtn)   addBtn.addEventListener('click', addNew);
		if (addInput) addInput.addEventListener('keydown', function(e){
			if (e.key==='Enter'){ e.preventDefault(); addNew(); }
		});
	}

	function init(panel){
		Array.prototype.forEach.call(panel.querySelectorAll('.cat-chips'), initList);
	}

	function bindAll(){
		Array.prototype.forEach.call(document.querySelectorAll('.cat-chips-panel'), init);
	}
	if (document.readyState !== 'loading') bindAll();
	else document.addEventListener('DOMContentLoaded', bindAll);
})();

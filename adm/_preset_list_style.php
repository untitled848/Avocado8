<?php
// 공유 스타일 (디자인/세계관 프리셋 리스트 통일)
if (defined('_PRESET_LIST_STYLE_LOADED_')) return;
define('_PRESET_LIST_STYLE_LOADED_', true);
?>
<style>
.pl-wrap { padding:0; }
.pl-help { background:#f0f7ff; border:1px solid #b3d4ff; padding:12px 16px; border-radius:6px; margin:8px 0 16px; color:#234; line-height:1.7; }
.pl-help strong { color:#0a4a8a; }

.pl-add-form { display:flex; gap:8px; align-items:center; padding:12px; background:#fafafa; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:16px; }
.pl-add-form input[type=text] { flex:1; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:13px; }

.pl-table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.pl-table thead th { background:#f5f5f5; padding:10px 12px; text-align:left; font-size:12px; color:#555; border-bottom:1px solid #ddd; }
.pl-table tbody td { padding:10px 12px; vertical-align:middle; border-bottom:1px solid #eee; }
.pl-table tbody tr:last-child td { border-bottom:none; }
.pl-table tbody tr:hover { background:#fafcff; }

.pl-col-id { width:70px; text-align:center; }
.pl-col-linked { width:280px; }
.pl-col-actions { width:260px; }

.pl-id { font-weight:bold; color:#666; position:relative; }
.pl-active-mark { color:#e8a800; margin-left:4px; font-size:14px; }
.pl-active-row { background:#fff8e7 !important; }
.pl-active-row .pl-id { color:#a07000; }

.pl-rename-form { display:flex; gap:6px; align-items:center; }
.pl-name-input { flex:1; min-width:120px; padding:6px 8px; border:1px solid #ccc; border-radius:3px; font-size:13px; }
.pl-tag { background:#eee; color:#666; font-size:11px; padding:2px 6px; border-radius:3px; }

.pl-badge { display:inline-block; background:#fff3d6; border:1px solid #e0c060; color:#7a5500; font-size:11px; padding:2px 7px; border-radius:3px; margin:1px 2px; text-decoration:none; }
.pl-badge:hover { background:#ffe9a8; }
.pl-muted { color:#999; font-size:12px; }

.pl-linked { font-size:12px; }
.pl-actions { white-space:nowrap; }
.pl-actions .ui-btn { margin-right:4px; }
.pl-danger { background:#a40000; color:#fff !important; border-color:#700000; }
</style>

<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 게시판 공통 헬퍼 — override-aware 템플릿 lookup
 *
 * 다양한 게시판 스킨이 공통으로 쓰는 패턴:
 *   - bbs/<기능디렉토리>/   : 컨트롤러 + 기본 템플릿 (template/)
 *   - skin/board/<스킨>/   : 스킨 entry + (선택) override 템플릿 (template/)
 *
 * 스킨 entry 는 다음과 같이 얇은 wrapper 면 됩니다:
 *   require G5_BBS_PATH . '/<feature>/<…>.controller.php';
 *   require bbs_template('<name>.tpl.php', $board_skin_path, '<feature>');
 *
 * 따라서 bbs/mmb/ 의 컨트롤러/템플릿을 mmb 이외 스킨에서도 그대로 재사용 가능합니다.
 */

if (!function_exists('bbs_template')) {
    /**
     * 템플릿 파일을 override-aware 방식으로 찾아 경로를 반환.
     *
     * 1) `$board_skin_path/template/<name>` 가 있으면 사용 (스킨 override)
     * 2) 없으면 `bbs/<feature>/template/<name>` (기본 템플릿)
     *
     * @param string $name             "write.tpl.php" 같은 파일명
     * @param string $board_skin_path  현재 스킨의 절대 경로
     * @param string $feature          bbs/ 아래 기능 디렉토리명 (기본 'mmb')
     * @return string 절대 경로
     */
    function bbs_template($name, $board_skin_path, $feature = 'mmb') {
        $name = ltrim($name, '/');
        if ($board_skin_path !== '') {
            $skin_tpl = $board_skin_path . '/template/' . $name;
            if (is_file($skin_tpl)) {
                return $skin_tpl;
            }
        }
        return G5_BBS_PATH . '/' . $feature . '/template/' . $name;
    }
}

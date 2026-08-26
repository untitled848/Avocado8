<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<div class="frame-theme-BODY" id="view_<?php echo $bo_table; ?>">
    <div class="inner">

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>

<article id="bo_v">
    <header>
        <h1 id="bo_v_title">
            <div class="frm-theme-COMMENT">
                <div class="inner">
                    <?php
                    // 분류
                    if (!empty($category_name) && isset($view['ca_name']) && $view['ca_name'] !== '') {
                        echo '['.get_text($view['ca_name']).'] ';
                    }

                    // 제목
                    if (isset($view['wr_subject'])) {
                        echo cut_str(get_text($view['wr_subject']), 70);
                    }
                    ?>
                </div>
            </div>
        </h1>
    </header>

    <section id="bo_v_info">
        <strong>
            <?php
            if (isset($view['name'])) {
                echo $view['name'];
            }
            if (!empty($is_ip_view) && isset($ip) && $ip !== '') {
                echo '&nbsp;('.$ip.')';
            }
            ?>
        </strong>
        <strong>
            <?php
            if (isset($view['wr_datetime'])) {
                echo date('y-m-d H:i', strtotime($view['wr_datetime']));
            }
            ?>
        </strong>
        <strong>
            <?php
            if (isset($view['wr_hit'])) {
                echo number_format($view['wr_hit']);
            }
            ?>
        </strong>
    </section>

    <?php
    // 첨부파일 존재 여부 및 개수 계산
    $cnt = 0;
    if (isset($view['file']) && is_array($view['file']) && isset($view['file']['count']) && $view['file']['count'] > 0) {
        $file_count = (int)$view['file']['count'];

        for ($i = 0; $i < $file_count; $i++) {
            if (
                isset($view['file'][$i]['source']) &&
                $view['file'][$i]['source'] &&
                empty($view['file'][$i]['view'])
            ) {
                $cnt++;
            }
        }
    }
    ?>

    <?php if ($cnt > 0) { ?>
    <!-- 첨부파일 시작 { -->
    <section id="bo_v_file">
        <h2>첨부파일</h2>
        <ul>
        <?php
        if (isset($file_count)) {
            for ($i = 0; $i < $file_count; $i++) {
                if (
                    isset($view['file'][$i]['source']) &&
                    $view['file'][$i]['source'] &&
                    empty($view['file'][$i]['view'])
                ) {
        ?>
            <li>
                <a href="<?php echo $view['file'][$i]['href']; ?>" class="view_file_download">
                    <img src="<?php echo $board_skin_url; ?>/img/icon_file.gif" alt="첨부">
                    <strong><?php echo $view['file'][$i]['source']; ?></strong>
                    <?php
                    if (isset($view['file'][$i]['content'])) {
                        echo $view['file'][$i]['content'];
                    }
                    ?>
                    (<?php echo $view['file'][$i]['size']; ?>)
                </a>
                <span class="bo_v_file_cnt">
                    <?php echo $view['file'][$i]['download']; ?>회 다운로드
                </span>
                <span>
                    DATE :
                    <?php echo $view['file'][$i]['datetime']; ?>
                </span>
            </li>
        <?php
                }
            }
        }
        ?>
        </ul>
    </section>
    <!-- } 첨부파일 끝 -->
    <?php } ?>

    <section id="bo_v_atc">

        <?php
            // 모듈 부가 렌더 (액션 로그, 다이스, 아이템 사용 로그 등)
            if (function_exists('bbs_render_row_extra')) {
                bbs_render_row_extra($view, array(
                    'bo_table'        => isset($bo_table) ? $bo_table : '',
                    'board_skin_url'  => isset($board_skin_url) ? $board_skin_url : '',
                    'board_skin_path' => isset($board_skin_path) ? $board_skin_path : '',
                    'scope'           => 'post',
                ));
            }
        ?>

        <?php
        // 본문 상단 이미지 출력
        if (isset($view['file']) && is_array($view['file']) && isset($view['file']['count']) && $view['file']['count'] > 0) {
            $v_img_count = (int)$view['file']['count'];

            echo "<div id=\"bo_v_img\">\n";

            for ($i = 0; $i < $v_img_count; $i++) {
                if (!empty($view['file'][$i]['view'])) {
                    echo get_view_thumbnail($view['file'][$i]['view']);
                }
            }

            echo "</div>\n";
        }
        ?>

        <!-- 본문 내용 시작 { -->
        <div id="bo_v_con">
            <?php
            if (isset($view['content'])) {
                echo get_view_thumbnail($view['content']);
            }
            ?>
        </div>
        <!-- } 본문 내용 끝 -->

 

    </section>

    <?php
    // 코멘트 입출력
    include_once(G5_BBS_PATH.'/view_comment.php');
    ?>

    <!-- 공지 전체 알림 발송 (관리자, notice 게시판) -->
    <?php if ($is_admin && isset($bo_table) && $bo_table === 'notice' && !empty($view['wr_id'])): ?>
    <div id="bo_v_notice_push" style="text-align:right; margin-bottom:8px;">
      <button type="button" id="btn-notice-push"
              data-wr="<?php echo (int)$view['wr_id']; ?>"
              data-bo="<?php echo htmlspecialchars($bo_table, ENT_QUOTES); ?>"
              data-token="<?php echo get_token(); ?>"
              class="ui-btn">전체 알림 발송</button>
    </div>
    <script>
    (function(){
      var btn = document.getElementById('btn-notice-push');
      if (!btn) return;
      btn.addEventListener('click', function(){
        if (!confirm('모든 멤버에게 이 공지 알림을 보내시겠습니까?')) return;
        btn.disabled = true;
        var fd = new FormData();
        fd.append('wr_id', btn.dataset.wr);
        fd.append('bo_table', btn.dataset.bo);
        fd.append('token', btn.dataset.token);
        fetch('<?php echo G5_URL; ?>/ajax/notice_call_send.php', {method:'POST', body:fd})
          .then(function(r){ return r.json(); })
          .then(function(d){
            if (d.ok) { alert(d.count + '명에게 발송했습니다.'); }
            else { alert('실패: ' + (d.msg || '오류')); }
          })
          .catch(function(){ alert('요청 실패'); })
          .finally(function(){ btn.disabled = false; });
      });
    })();
    </script>
    <?php endif; ?>

    <!-- 링크 버튼 시작 { -->
    <div id="bo_v_bot">
        <div class="bo_v_com" style="text-align: right;">
            <?php if (!empty($update_href)) { ?>
                <a href="<?php echo $update_href; ?>" class="ui-btn">수정</a>
            <?php } ?>
            <?php if (!empty($delete_href)) { ?>
                <a href="<?php echo $delete_href; ?>" class="ui-btn" onclick="del(this.href); return false;">삭제</a>
            <?php } ?>
            <?php if (!empty($list_href)) { ?>
                <a href="<?php echo $list_href; ?>" class="ui-btn">목록</a>
            <?php } ?>
            <?php if (!empty($reply_href)) { ?>
                <a href="<?php echo $reply_href; ?>" class="ui-btn">답변</a>
            <?php } ?>
            <?php if (!empty($write_href)) { ?>
                <a href="<?php echo $write_href; ?>" class="ui-btn">글쓰기</a>
            <?php } ?>
        </div>
    </div>
    <!-- } 링크 버튼 끝 -->

</article>
<!-- } 게시판 읽기 끝 -->
<br><br><br><br>

<script>
<?php if (isset($board['bo_download_point']) && $board['bo_download_point'] < 0) { ?>
$(function() {
    $("a.view_file_download").click(function() {
        if (typeof g5_is_member == "undefined" || !g5_is_member) {
            alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 보십시오.");
            return false;
        }

        var msg = "파일을 다운로드 하시면 포인트가 차감(<?php echo number_format($board['bo_download_point']); ?>점)됩니다.\n\n"
                + "포인트는 게시물당 한번만 차감되며 다음에 다시 다운로드 하셔도 중복하여 차감하지 않습니다.\n\n"
                + "그래도 다운로드 하시겠습니까?";

        if (confirm(msg)) {
            var href = $(this).attr("href") + "&js=on";
            $(this).attr("href", href);
            return true;
        }

        return false;
    });
});
<?php } ?>

function board_move(href)
{
    window.open(href, "boardmove", "left=50, top=50, width=500, height=550, scrollbars=1");
}
</script>

<script>
$(function() {
    $("a.view_image").click(function() {
        window.open(this.href, "large_image",
            "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
        return false;
    });

    // 추천, 비추천
    $("#good_button, #nogood_button").click(function() {
        var $tx;
        if (this.id === "good_button")
            $tx = $("#bo_v_act_good");
        else
            $tx = $("#bo_v_act_nogood");

        excute_good(this.href, $(this), $tx);
        return false;
    });

    // 이미지 리사이즈
    $("#bo_v_atc").viewimageresize();
});

function excute_good(href, $el, $tx)
{
    $.post(
        href,
        { js: "on" },
        function(data) {
            if (data.error) {
                alert(data.error);
                return false;
            }

            if (data.count) {
                $el.find("strong").text(number_format(String(data.count)));

                if ($tx.attr("id").indexOf("nogood") > -1) {
                    $tx.text("이 글을 비추천하셨습니다.");
                } else {
                    $tx.text("이 글을 추천하셨습니다.");
                }

                $tx.fadeIn(200).delay(2500).fadeOut(200);
            }
        },
        "json"
    );
}
</script>
<!-- } 게시글 읽기 끝 -->

    </div>
</div>

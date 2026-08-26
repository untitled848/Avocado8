<?php  
                    
    // 모듈 훅: wr_type 에 따라 로그 출력 방식을 교체한다.
    //   이미지 URL/클래스 계산은 list.log.skin.php 에서 끝냈고, 여기서는 출력만 한다.
    //   필터가 빈 문자열이 아닌 값을 반환하면 기본 출력(UPLOAD/URL) 대신 그 값을 사용한다.
    //   (예: 모듈이 추가한 VIDEO/YOUTUBE 등 wr_type 의 전용 출력)
    $mmb_log_output = '';
    if (function_exists('mdl_apply_filters')) {
        $mmb_log_output = mdl_apply_filters('mmb_list_log_img', '', isset($mmb_log_ctx) ? $mmb_log_ctx : array());
    }

    if ($mmb_log_output !== '') {
        echo $mmb_log_output;
    } else if($image_url) { ?>
        <div data-height="<?php echo $image_height?>">
            <?php echo $image_url?>
        </div>
    <?php  } ?>
    
    <?php 	if($log_class  && !G5_IS_MOBILE) { 
        // 접기 기능 (펼치기)
    ?>
    <a href="#" class="ui-open-log ui-btn">OPEN</a>
    <?php  } ?>
    <?php 	if($blind_class) { 
        // 블라인드 (19금 필터링)
    ?>
    <a href="#" class="ui-remove-blind"><span>해당 로그는 필터 된 로그 입니다.<br />확인을 원하실 경우 클릭해주세요.</span></a>
    <?php  } ?>
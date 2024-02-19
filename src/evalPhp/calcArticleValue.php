<?php
include_once __DIR__ . '/common.php';

function calcArticleValue($row, $config_eval) {
  $_CONF_INDEX_ALL = 128;
  $rtn = array();

  if ($row['tmp_article_datetime']) {
    $article_date_tmp = (string)$row['tmp_article_datetime'];
    $pattern_datetime = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
    if (preg_match($pattern_datetime, $article_date_tmp, $matches) === 1) {
      $article_date_tmp_arr = explode(' ', $article_date_tmp);
      $article_date = $article_date_tmp_arr[0];
      if (equals($article_date, '0000-00-00')) $article_date = '-';
    } else {
      $article_date = '-';
    }
  } else {
    $article_date = '-';
  }
  $rtn['article_date'] = $article_date;

  $categoryOutput = (int)$row['category_output'];// 기사 가치 산출 기준
  $newsComment = (int)$row['news_comment']; // 평가 처리 여부

  $rtn['article_page'] = getLocation($row['tmp_article_serial'], $row['tmp_case_name'], $row['tmp_part_name']);
  $rtn['article_length'] = getLength($row['tmp_article_contents']);
  $rtn['article_size_own'] = (float)getSize($categoryOutput, $row['tmp_article_area'], $row['tmp_article_contents']);
  $rtn['article_size_pixel'] = getSizePixel($row['coordinate']);
  if ($rtn['article_size_pixel'] == 0) {
    $rtn['article_size_pixel'] = $rtn['article_size_own'];
  }
  $rtn['article_size_pixel_ratio'] = getSizePixelRatio($row['page_size_pixel'], $rtn['article_size_pixel']);

  $articleSize = 1; // 면적 구하기 혹은 너비 값
  if (equals($config_eval['policy']['SZ']['value'], 'Y') && (equals($row['tmp_part_name'], '') || equals($config_eval['policy']['SO']['value'], 'Y'))) {
    $articleSize *= floatval($rtn['article_size_own']);
  } // 설정-가치계산-기사면적반영여부
  if (equals($config_eval['policy']['RT']['value'], 'Y') && (equals($row['tmp_part_name'], '') || equals($config_eval['policy']['RO']['value'], 'Y'))) {
    $articleSize *= $rtn['article_size_pixel_ratio'];
  } // 설정-가치계산-기사면적비율반영여부

  $config_eval_tmp = $config_eval['policy']['MD']['EVAL_VALUE_TYPE']['value'];
  if ((int)$config_eval_tmp == 0) { // 0:전체(all)
    $result_media_value = (float)$config_eval['policy']['SV']['value'];
  } else if ((int)$config_eval_tmp == 1) { // 1:유형(type)
    $result_media_value = (float)$config_eval['type'][$row['media_type']]['evalValue'];
  } else if ((int)$config_eval_tmp == 2) { // 2:매체별(media)
    $result_media_value = (float)$row['media_value'];
  } else if ((int)$config_eval_tmp == 3) { // 3:분류별(category)
    $result_media_value = (float)$config_eval['category'][$row['category_id']]['category_eval_value'];
  } // 매체가치
  $rtn['config_eval_tmp'] = $config_eval_tmp; // DEBUG

  if ($newsComment) {
    $eval_score = 0;
  } else {
    $eval_score = $articleSize * $result_media_value; // 면적 * 광고 단가 - 입력값 단위 천원
  }
  $eval_score_org = $eval_score;
  $eval_score_multi = 1;

  if (equals($config_eval['item']['M1'][$row['eval1_seq']]['group_isEval'], 'Y')) { // 수동1 isEval 이면 계산, 아니면 무시 isUse무관
    $eval1_score = (float)$config_eval['item']['M1'][$row['eval1_seq']]['score'];
  } else {
    $eval1_score = 1;
  } // 수동1 적용

  // 계산 시작 1:1 데이터
  $rtn['article_size'] = $articleSize; // 계산된 기사면적
  $rtn['eval_score_org'] = $eval_score_org; // 기사면적 * 매체가치
  $rtn['eval1_score'] = $eval1_score;

  $tmp_eval2_seqs = explode(',', $row['eval2_seqs']); $eval2_seqs = array();
  foreach ($tmp_eval2_seqs as $v) {
    if (intval($v) > 0) $eval2_seqs[] = intval($v);
  }
  $rtn['eval2_seqs'] = $eval2_seqs;

  // 계산 시작 1:n 데이터
  $eval2_score = 1; $config_eval2_tmp;
  foreach ($eval2_seqs as $ev) {
    $config_eval2_tmp = $config_eval['item']['AT_M2'][$ev];
    if (!$config_eval2_tmp || ($config_eval['policy']['NV'] && $config_eval['policy']['NV']['value'] === 'N' && ($config_eval2_tmp['isUse'] === 'N' || $config_eval2_tmp['group_isUse'] === 'N'))) continue; // cfg:N use:N => 미진행

     // 자동|수동2 isEval 이면 계산, 아니면 무시 isUse무관
    if (equals($config_eval2_tmp['group_isEval'], 'Y')) {
      $eval2_score_current = (float)$config_eval2_tmp['score'];
    } else {
      $eval2_score_current = 1;
    }
    $eval2_score *= $eval2_score_current;

    if ($config_eval2_tmp["group_seq"] == "7") {
        $rtn['eval2'][] = array(
            'eval2_seq' => $ev,
            'eval2_name' => $config_eval2_tmp["refValue"]."-".$config_eval2_tmp['value'],
            'eval2_score' => $eval2_score_current,
            'eval2_group_seq' => $config_eval2_tmp['group_seq'],
            'eval2_upper_name' => $config_eval2_tmp['group_name']
        );
    } else {
        $rtn['eval2'][] = array(
          'eval2_seq' => $ev,
          'eval2_name' => $config_eval2_tmp['value'],
          'eval2_score' => $eval2_score_current,
          'eval2_group_seq' => $config_eval2_tmp['group_seq'],
          'eval2_upper_name' => $config_eval2_tmp['group_name']
        );        
    }
  }

  $eval_conjunction_policy = $config_eval['policy']['MD']['EVAL_CALC_TYPE']['value'];

   // 평가가중치 합산 방법 1:평균 2:곱
  if ($eval_conjunction_policy == 1) { // 평가1 평가2 평균
    $eval_score_multi = ($eval1_score + $eval2_score) / 2;
  } else if ($eval_conjunction_policy == 2) { // 평가1 평가2 곱
    $eval_score_multi = $eval1_score * $eval2_score;
  } else {
    $eval_score_multi = 0;
  }
  $rtn['eval2_score'] = $eval2_score;
  $rtn['eval_score_multi'] = $eval_score_multi; // DEBUG
  $rtn['correction_value'] = floatval($config_eval['policy']['CV']['value']); // DEBUG
  $eval_score_total = $eval_score * $eval_score_multi * floatval($config_eval['policy']['CV']['value']);
  $rtn['eval_score'] = $eval_score_total; // 면적 * 매체 * 평가
  $rtn['eval_score_db'] = $eval_score_total; // 면적 * 매체 * 평가 복원

  return $rtn;
}

function getSizePixelRatio($pagePixelSize, $articlePixelSize) {
  $rtn = 0;
  if (is_numeric($pagePixelSize) && is_numeric($articlePixelSize)) {
    $pagePixelSize = intval($pagePixelSize);
    $articlePixelSize = intval($articlePixelSize);
    if ($pagePixelSize !== 0 && $articlePixelSize !== 0) {
      $rtn = intval(($articlePixelSize / $pagePixelSize) * 100 * 1000) / 100000;
    }
  }
  return $rtn;
}

function getSizePixel($coordinate) {
  $rtn = 0;
  if ($coordinate && is_string($coordinate)) { // NOT {}
    $cArray = explode('|', $coordinate);
    $xArray = array(); $yArray = array();
    foreach($cArray as $cv) {
      $coor = explode(',', $cv);
      if (count($coor) === 2) {
        $xArray[] = $coor[0]; $yArray[] = $coor[1];
      }
    }
    $xMax = max($xArray); $xMin = min($xArray);
    $yMax = max($yArray); $yMin = min($yArray);
    $xSize = intval($xMax) - intval($xMin);
    $ySize = intval($yMax) - intval($yMin);
    $rtn = intval($xSize) * intval($ySize);
  }
  return $rtn;
}

function getSize($categoryOutput, $articleArea, $articleContents) {
  $articleArea = explode("|", $articleArea);
  if ($articleArea[($categoryOutput-1)] == 0) {
    $articleContentsNew = preg_replace("/[^가-힣0-9A-Z]/ui", "", $articleContents);
    $letterCnt = mb_strlen($articleContentsNew, 'UTF-8');
    $size = $letterCnt * 0.125;
  } else {
    $size = $articleArea[($categoryOutput-1)];
  }
  return $size;
}

function getLocation($articleSerial, $partName, $caseName) {
  $rtn = '기타';
  if (!$partName && !$caseName && (mb_strlen($articleSerial, 'UTF-8') === 18 || strpos($articleSerial, '_UC') !== false) ) {
    $rtn = substr($articleSerial, 13, 2);
  }
  return $rtn;
}

function getLength($articleContents) {
  $articleContentsNew = preg_replace("/[^가-힣0-9A-Z]/ui", "", $articleContents);
  return mb_strlen($articleContentsNew, 'UTF-8');
}

function getEval1NamesArray($configEvalItemM1, $ev1Seq) {
  $rtn = array(); $tmpObj = $configEvalItemM1[$ev1Seq];
  while ($tmpObj) {
    $rtn[] = $tmpObj['value'];
    if (!$tmpObj['group_seq']) break;
    $tmpObj = $configEvalItemM1[$tmpObj['group_seq']];
  }
  return array_reverse($rtn);
}

function getEval2Names($ev2s, $configEval) {
    $rtn = array(); $_c;
    if(empty($ev2s)) return $rtn; 
    foreach($ev2s as $vk => $vv) {
        $_c = $configEval['item']['AT_M2'][$vv['eval2_seq']];
        if (!$_c) continue;
        if (equals($_c['group_isAuto'], 'Y')) { // 자동
            $rtn['eva_' . $vv['eval2_group_seq']] = $vv['eval2_name'];
        } else { // 수동2
            $rtn['ev2_' . $vv['eval2_group_seq']] = $vv['eval2_name'];
        }
    }
    return $rtn;
}

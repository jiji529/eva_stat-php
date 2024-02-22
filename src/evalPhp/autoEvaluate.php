<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/calcArticleValue.php';

function autoEvaluate($db, $config_eval, $news_id_arr, $premiumID, $reEvaluation) {
    $success = true; $rtn = array(); $rtn_meta = array(); $rtn_tmp; $rtn_tmp_idx = 0;
    
    $_PROCESS_LOCK_FILE = ready_lock($premiumID);
    logs('autoEvaluate.lock: '.$_PROCESS_LOCK_FILE);
    if ($_PROCESS_LOCK_FILE) {
        $fp = fopen($_PROCESS_LOCK_FILE, 'w');
        if (flock($fp, LOCK_EX)) {
            
            if (!is_array($news_id_arr) || count($news_id_arr) === 0) {
                $success = false;
                $message = 'autoEvaluate.news.empty1';
                logs('autoEvaluate.news.empty1');
            } else if ($db->Error()) {
                $success = false;
                $message = 'autoEvaluate.db.error1';
                logs('autoEvaluate.db.error1');
            } else {
                $sorted_gi_config = array();
                for ($i = 1; $i <= 6; $i++) {
                    if ($config_eval['group_item'][$i] == null) continue;
                    foreach($config_eval['group_item'][$i] as $v) {
                        if ($v['isUse'] === 'Y')
                            $sorted_gi_config[$i][] = $v;
                    }
                    if ($i <= 2) { // 크기1 글자수2 refValue 정렬
                        usort($sorted_gi_config[$i], function($a, $b) {
                            return $b['refValue'] - $a['refValue'];
                        });
                    } else { // 매체중요도3 출입기자4 수록지면5 order 정렬
                        usort($sorted_gi_config[$i], function($a, $b) {
                            return intval($a['order']) - intval($b['order']);
                        });
                    }
                } // 공통설정에서 자동평가 사항 변형
                
                // 매체중요도3 및 수록지면5 기본값 없으면 자동추가
                if (count($sorted_gi_config[3]) === 0) {
                    $query_failsafe_important = "insert into evalClassify (`value`, `refValue`, `score`, `isUse`, `order`, `evaluation_seq`) VALUES ('미할당', 'mediaGroup', 1, 'Y', 1, 3)";
                    $db->Query($query_failsafe_important);
                    $seq_failsafe_important = $db->GetLastInsertID();
                    $sorted_gi_config[3][] = array(
                        'group_seq' => '3',
                        'group_name' => '매체중요도',
                        'group_use' => 'Y',
                        'seq' => $seq_failsafe_important,
                        'order' => '1',
                        'value' => '미할당',
                        'refValue' => 'mediaGroup',
                        'score' => '1',
                        'isUse' => 'Y'
                    );
                    logs('  failsafe g3: ' . $seq_failsafe_important);
                }
                
                // mediaGroup 채워넣기
                $query_match = "SELECT md.media_id, md.media_name, mg.* FROM hnp_category md LEFT OUTER JOIN mediaGroup AS mg ON mg.hnp_category_media_id = md.media_id WHERE md.media_id > 100 AND md.media_name NOT IN ('') AND md.media_name IS NOT NULL AND `mg`.`seq` IS NULL";
                $db->Query($query_match);
                if ($db->Error()) {
                    $message = 'autoEvaluate.mediaGroup';
                    logs('autoEvaluate.mediaGroup');
                } else {
                    $media_fetch = array();
                    $media_default_seq = end($sorted_gi_config[3]);
                    $media_default_seq = $media_default_seq['seq'];
                    while ($row = mysqli_fetch_assoc($db->Records())) {
                        $media_fetch[] = '(' . $row['media_id'] . ',' . $media_default_seq . ')'; // 1기사크기
                    }
                    logs('media_fetch: '.$media_fetch);
                    if (is_array($media_fetch) && count($media_fetch) > 0) {
                        $query_insert = "INSERT INTO `mediaGroup` (`hnp_category_media_id`, `evalClassify_seq`) VALUES " . implode(',', $media_fetch);
                        logs('media_query: '.$query_insert);
                        $db->Query($query_insert);
                    } // query_insert
                    logs('media_fetch: ' . count($media_fetch));
                }
                
                if (count($sorted_gi_config[5]) === 0) {
                    $query_failsafe_position = "insert into evalClassify (`value`, `refValue`, `score`, `isUse`, `order`, `evaluation_seq`) VALUES ('기타', '기타', 1, 'Y', 1, 5)";
                    $db->Query($query_failsafe_position);
                    $seq_failsafe_position = $db->GetLastInsertID();
                    $sorted_gi_config[5][] = array(
                        'group_seq' => '5',
                        'group_name' => '출입기자',
                        'group_use' => 'Y',
                        'seq' => $seq_failsafe_position,
                        'order' => '1',
                        'value' => '기타',
                        'refValue' => '기타',
                        'score' => '2',
                        'isUse' => 'Y'
                    );
                    logs('  failsafe g5: ' . $seq_failsafe_position);
                }
                // 매체중요도(mediaGroup)에 없는 매체정보(hnp_category) 맞춤

                logs('sorted_gi_config : ' . json_encode($sorted_gi_config));
                
                $rtn_meta['input'] = count($news_id_arr);
                $news_id_arr_unit = array(); $news_id_arr_count = 0; $news_id_arr_count_acm = 0; $news_id_arr_entire = count($news_id_arr);
                $total_count_target = 0; $total_count_processed = 0;
                
                /* 재평가를 할 경우 */
                $autoSeq = array();
                $reEvalItemSeq = array();
                if ($reEvaluation >= 0) {
                    foreach ($config_eval['policy']['AT'] as $key) {
                        array_push($autoSeq, $key['seq']);
                    }
                    foreach ($sorted_gi_config[$reEvaluation] as $evalClass) {
                        array_push($reEvalItemSeq, $evalClass['seq']);
                    }
                }
                foreach($news_id_arr as $news_id) {
                    $news_id_arr_unit[] = $news_id; $news_id_arr_count++; $news_id_arr_count_acm++;
                    if ($news_id_arr_count > 10000 || $news_id_arr_count_acm >= $news_id_arr_entire) {
                        // do query
                        $news_id_str = '(' . implode(',', $news_id_arr_unit) . ')';
                        logs('autoEvaluate.news_id_str: ' . $news_id_str);
                        // 자동평가실행 -- news_id 기반
                        $SQL_MAX_LENGTH_CUTTER = 1024; //1048576 / 128; // =8192 레코드마다 무조건 잘라야함!
                        $cnt_success_fetch = 0; $cnt_fail_fetch = 0;
                        $idx_fetch = 0; $cnt_fetch = 0; $tmp_fetch; $data_fetch = array();
                        $query_fetch = "SELECT * FROM (";
                        $query_fetch .= "SELECT `hnp_news`.`news_id`";
                        $query_fetch .= ", `hnp_category`.`categoryOutput`";
                        $query_fetch .= ", if(`hnp_news`.`articleArea2`='', `hnp_news`.`articleArea`, `hnp_news`.`articleArea2`) AS `articleArea` ";
                        $query_fetch .= ", `hnp_news`.`news_contents`";
                        $query_fetch .= ", `mediaGroup`.`evalClassify_seq` AS `importance`";
                        $query_fetch .= ", `reporterGroup`.`evalClassify_seq` AS `reporter`";
                        $query_fetch .= ", `hnp_news`.`article_serial`, `hnp_news`.`part_name`";
                        $query_fetch .= ", COUNT(`hnp_news`.`news_id`) AS `eval_count` ";
                        $query_fetch .= ", `eval2`.`evaluation_seq` ";
                        $query_fetch .= ", `hnp_news`.`pos_rate`, `hnp_news`.`mid_rate`, `hnp_news`.`neg_rate` ";
                        $query_fetch .= "FROM `hnp_news`
                          LEFT JOIN `hnp_category` ON `hnp_category`.`media_id` = `hnp_news`.`media_id`
                          LEFT JOIN `mediaGroup` ON `mediaGroup`.`hnp_category_media_id` = `hnp_news`.`media_id`
                          LEFT JOIN `reporterGroup` ON `reporterGroup`.`hnp_category_media_id` = `hnp_news`.`media_id`
                          AND `hnp_news`.`news_reporter` = `reporterGroup`.`reporterName` AND `reporterGroup`.`isUse` = 'Y'
                          LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnp_news`.`news_id`
                          LEFT JOIN `evalClassify` AS `eval2` ON `eval2`.`seq` = `newsEval`.`evalClassify_seq` ";
                        $query_fetch .= " WHERE 1=1 ";
                        if ($reEvaluation < 0) {
                            $query_fetch .= " AND (`eval2`.`evaluation_seq` <= 1000 OR `eval2`.`evaluation_seq` IS NULL) ";
                        }
                        $query_fetch .= " AND `hnp_news`.`news_id` IN " . $news_id_str . " ";
                        $query_fetch .= " GROUP BY `hnp_news`.`news_id` ORDER BY `hnp_news`.`news_id`) `B` ";
                        if ($reEvaluation < 0) {
                            $query_fetch .= " WHERE `B`.`eval_count` = 1 AND `B`.`evaluation_seq` IS NULL ";
                        }
//                         logs('query_fetch : ' . $query_fetch);
                        logs('query_fetch_len : ' . strlen($query_fetch));
                        
                        $db->Query($query_fetch);
                        if ($db->Error()) {
                            $success = false;
                            $message = 'autoEvaluate.db.error2';
                            logs('autoEvaluate.db.error2');
                        } else if ($db->RowCount() == 0) {
                            $success = false;
                            $message = 'autoEvaluate.news.empty2';
                            logs('autoEvaluate.news.empty2');
                        } else {
                            $rtn_tmp[$rtn_tmp_idx] = array();
                            $rtn_tmp[$rtn_tmp_idx]['target'] = $db->RowCount();
                            $total_count_target += $db->RowCount();
                            logs('autoEvaluate.count: '.$db->RowCount());
                            
                            while ($row = mysqli_fetch_assoc($db->Records())) {
                                $idx_fetch = (int)($cnt_fetch / $SQL_MAX_LENGTH_CUTTER);
                                
                                // if (equals($config_eval['policy']['AT'][1]['value'], 'Y')) {
                                $articleAreaExplode = explode('|', $row['articleArea']);
                                
                                logs('front.articleArea : ' . json_encode($row['articleArea']));
                                logs('front.categoryOutput : ' . json_encode($row['categoryOutput']));
                                logs('front.articleAreaExplode : ' . json_encode($articleAreaExplode));
                                logs('front : ' . json_encode($articleAreaExplode[$row['categoryOutput']-1]));
                                logs('front : ' . json_encode($config_eval['policy']['OA']['value']));
                                if ($articleAreaExplode[$row['categoryOutput']-1] || (int)$config_eval['policy']['OA']['value'] === 0) {
                                    //$tmp_fetch = 36;
                                    $tmp_fetch = getSize($row['categoryOutput'], $row['articleArea'], $row['news_contents']);
                                    
                                    logs('front.calced : ' . $tmp_fetch);
                                    
                                    logs('tmp_fetch : ' . $tmp_fetch);
                                    $tmp_fetch_seq = end($sorted_gi_config[1]);
                                    logs('tmp_fetch_seq : ' . $tmp_fetch_seq);
                                    $tmp_fetch_seq = $tmp_fetch_seq['seq'];
                                    logs('tmp_fetch_seq : ' . $tmp_fetch_seq);
                                    
                                    logs('autoEvaluate.size: '.$tmp_fetch);
                                    logs('autoEvaluate.size.seq: '.$tmp_fetch_seq);
                                    foreach ($sorted_gi_config[1] as $ck => $cv) {
                                        if ( (float)$tmp_fetch >= (float)$cv['refValue'] ) {
                                            $tmp_fetch_seq = $cv['seq']; break;
                                        }
                                    }
                                } else {
                                    logs('tmp_fetch_seq lower course');
                                    $tmp_fetch_seq = $config_eval['policy']['OA']['value'];
                                }
                                
                                /* 크기 */
                                if (($reEvaluation >= 0 && in_array($tmp_fetch_seq, $reEvalItemSeq)) || $reEvaluation < 0) {
                                    $data_fetch[$idx_fetch][] = '(' . $row['news_id'] . ',' . $tmp_fetch_seq . ')'; // 1기사크기
                                    logs('autoEvaluate.size.seq.fin: '.$tmp_fetch_seq);
                                }
                                
                                /* 글자수 */
                                $tmp_fetch = getLength($row['news_contents']);
                                $tmp_fetch_seq = end($sorted_gi_config[2]);
                                $tmp_fetch_seq = $tmp_fetch_seq['seq'];
                                if (($reEvaluation >= 0 && in_array($tmp_fetch_seq, $reEvalItemSeq)) || $reEvaluation < 0) {
                                    logs('autoEvaluate.length: '.$tmp_fetch);
                                    logs('autoEvaluate.length.seq: '.$tmp_fetch_seq);
                                    foreach ($sorted_gi_config[2] as $ck => $cv) {
                                        if ( (float)$tmp_fetch >= (float)$cv['refValue'] ) {
                                            $tmp_fetch_seq = $cv['seq']; break;
                                        }
                                    }
                                    $data_fetch[$idx_fetch][] = '(' . $row['news_id'] . ',' . $tmp_fetch_seq . ')'; // 2기사길이
                                    logs('autoEvaluate.length.seq.fin: '.$tmp_fetch_seq);
                                }
                                /* 매체중요도 */
                                $tmp_fetch_seq = end($sorted_gi_config[3]); //var_dump($tmp_fetch_seq);
                                $tmp_fetch_seq = $tmp_fetch_seq['seq'];
                                if (($reEvaluation >= 0 && in_array($tmp_fetch_seq, $reEvalItemSeq)) || $reEvaluation < 0) {
                                    logs('autoEvaluate.importance: '.$row['importance']);
                                    logs('autoEvaluate.importance.seq: '.$tmp_fetch_seq);
                                    $tmp_fetch_seq = ($row['importance']) ? $row['importance'] : $tmp_fetch_seq;
                                    $data_fetch[$idx_fetch][] = '(' . $row['news_id'] . ',' . $tmp_fetch_seq . ')'; // 3매체중요도
                                    logs('autoEvaluate.importance.seq.fin: ' . $tmp_fetch_seq);
                                }
                                
                                /* 출입기자 */
                                $tmp_fetch_seq = end($sorted_gi_config[4]);
                                $tmp_fetch_seq = $tmp_fetch_seq['seq'];
                                if (($reEvaluation >= 0 && in_array($tmp_fetch_seq, $reEvalItemSeq)) || $reEvaluation < 0) {
                                    logs('autoEvaluate.reporter: '.$row['reporter']);
                                    logs('autoEvaluate.reporter.seq: '.$tmp_fetch_seq);
                                    $tmp_fetch_seq = ($row['reporter']) ? $row['reporter'] : $tmp_fetch_seq;
                                    $data_fetch[$idx_fetch][] = '(' . $row['news_id'] . ',' . $tmp_fetch_seq . ')'; // 4기자
                                    logs('autoEvaluate.reporter.seq.fin: '.$tmp_fetch_seq);
                                }
                                
                                /* 기사위치 */
                                $tmp_fetch = getLocation($row['article_serial'], $row['part_name'], $row['case_name']);
                                $tmp_fetch_seq = end($sorted_gi_config[5]);
                                $tmp_fetch_seq = $tmp_fetch_seq['seq'];
                                if (($reEvaluation >= 0 && in_array($tmp_fetch_seq, $reEvalItemSeq)) || $reEvaluation < 0) {
                                    logs('autoEvaluate.position: ' . $tmp_fetch);
                                    logs('autoEvaluate.position.seq: ' . $tmp_fetch_seq);
                                    foreach ($sorted_gi_config[5] as $ck => $cv) {
                                        if ((float)$tmp_fetch == (float)$cv['refValue']) {
                                            $tmp_fetch_seq = $cv['seq']; break;
                                        }
                                    }
                                    $data_fetch[$idx_fetch][] = '(' . $row['news_id'] . ',' . $tmp_fetch_seq . ')'; // 5수록지면
                                    logs('autoEvaluate.position.seq.fin: ' . $tmp_fetch_seq);
                                }
                                
                                /* 기사 긍-부정 */
                                $pnSeq = array(
                                    $row["pos_rate"]=>$sorted_gi_config[6][0]
                                    ,$row["mid_rate"]=>$sorted_gi_config[6][1]
                                    ,$row["neg_rate"]=>$sorted_gi_config[6][2]
                                );
                                ksort($pnSeq); // 내림차순 정렬
                                $per = array_key_last($pnSeq); // Percentage
                                $tmp_fetch_seq = $pnSeq[$per]["seq"]; // Sequence
                                $minValue = intval($pnSeq[$per]["refValue"]); // min
                                $per = floatval($per); //current
                                if ($per > -1) {
                                    $tmp_fetch_seq = ($minValue > $per ? null : $tmp_fetch_seq);
                                } else {
                                    $tmp_fetch_seq = null;
                                }
                                if ($tmp_fetch_seq != null 
                                    && (($reEvaluation >= 0 && in_array($tmp_fetch_seq, $reEvalItemSeq)) 
                                    || $reEvaluation < 0)) {
                                    $data_fetch[$idx_fetch][] = '(' . $row['news_id'] . ',' . $tmp_fetch_seq . ')'; // 5수록지면
                                    logs('autoEvaluate.position.seq.fin: ' . $tmp_fetch_seq);
                                }
                                
                                $cnt_fetch++;
                                logs('autoEvaluate.row: id-'.$row['news_id'].', data_fetch_count: '.count($data_fetch).', cnt_fetch: '.$cnt_fetch);
                            } // while query_fetch
                            $rtn_tmp[$rtn_tmp_idx]['processed'] = $cnt_fetch;
                            $total_count_processed += $cnt_fetch;
                            $rtn_tmp_idx++;
                            
                            if (is_array($data_fetch) && count($data_fetch) > 0) {
                                foreach ($data_fetch as $dk => $dv) {
                                    $query_insert = "INSERT INTO `newsEval` (`hnp_news_seq`, `evalClassify_seq`) VALUES " . implode(',', $dv);
                                    logs('autoEvaluate.insert: ' . strlen($query_insert));
                                    $db->Query($query_insert);
                                    if ($db->Error()) {
                                        logs('  query error : ' . $db->ErrorNumber());
                                        logs('  query error : ' . $db->Error());
                                    } else {
                                        logs('  query success!');
                                    }
                                }
                            } // query_insert
                            
                            flock($fp, LOCK_UN);
                            $success = true;
                        } // query_fetch success
                        
                        $news_id_arr_unit = array(); $news_id_arr_count = 0;
                    } // if (count)
                } // foreach
            } // db success
        }
    }
    if ($message) $rtn_meta['msg'] = $message;
    $rtn_meta['pid'] = $premiumID;
    $rtn_meta['success'] = $success;
    $rtn_meta['target'] = $total_count_target;
    $rtn_meta['processed'] = $total_count_processed;
    
    $rtn = $rtn_meta;
    // if ($rtn_tmp) $rtn[1] = $rtn_tmp;
    
    return $rtn;
}

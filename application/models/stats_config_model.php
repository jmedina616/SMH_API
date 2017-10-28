<?php

error_reporting(1);

require_once APPPATH . "/libraries/PHPExcel/Classes/PHPExcel.php";

class Stats_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
// Open the correct DB connection
        $this->config = $this->load->database('smhstats', TRUE);
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_all_child_stats($pid, $ks, $start_date, $end_date) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $childIds = $this->smportal->get_partner_child_acnts($pid, $ks);

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Content')
                    ->setCellValue('B1', 'Hits')
                    ->setCellValue('C1', 'Viewers')
                    ->setCellValue('D1', 'Duration')
                    ->setCellValue('E1', 'Duration per Hit (average)')
                    ->setCellValue('F1', 'Duration per Viewer (average)')
                    ->setCellValue('G1', 'Data Transfer');
            $i = 2;
            foreach ($childIds['childIds'] as $child) {
                syslog(LOG_NOTICE, "SMH DEBUG : get_all_child_stats0: " . $child);
                $vodStatsEntries = $this->getVodStats($child, $start_date, $end_date);
                syslog(LOG_NOTICE, "SMH DEBUG : get_all_child_stats1: " . print_r($vodStatsEntries, true));
                $content_vod_stats_zoomed_view = $this->get_vod_stats_zoomed($vodStatsEntries);
                foreach ($content_vod_stats_zoomed_view as $value) {
                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('B' . $i, $value[1])
                            ->setCellValue('C' . $i, $value[2])
                            ->setCellValue('D' . $i, $value[3])
                            ->setCellValue('E' . $i, $value[4])
                            ->setCellValue('F' . $i, $value[5])
                            ->setCellValue('G' . $i, $value[6]);
                    $i++;
                }
            }
            $objPHPExcel->getActiveSheet()->setTitle('Vod_Content');

            $filename = $pid . '_child_streaming_stats_' . date('m-d-Y_H_i_s');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');
            header("Content-Type: application/force-download");
            header("Content-Type: application/download");
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {
            return false;
        }
    }

    public function get_child_stats($pid, $ks, $cpid, $start_date, $end_date) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $vodStatsEntries = $this->getVodStats($cpid, $start_date, $end_date);
            $liveStatsEntries = $this->getLiveStats($cpid, $start_date, $end_date);
            $locationEntries = $this->getLocations($cpid, $start_date, $end_date);

            $content_vod_stats_zoomed_view = $this->get_vod_stats_zoomed($vodStatsEntries);
            $content_vod_stats_total = $this->get_vod_stats_total($vodStatsEntries);

            $content_live_stats_zoomed_view = $this->get_live_stats_zoomed($liveStatsEntries);
            $content_live_stats_total = $this->get_live_stats_total($liveStatsEntries);

            $cities_view = $this->get_cities_view($locationEntries);
            $countries_stats_total = $this->get_countries_total($locationEntries);

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Content')
                    ->setCellValue('B1', 'Hits')
                    ->setCellValue('C1', 'Viewers')
                    ->setCellValue('D1', 'Duration')
                    ->setCellValue('E1', 'Duration per Hit (average)')
                    ->setCellValue('F1', 'Duration per Viewer (average)')
                    ->setCellValue('G1', 'Data Transfer');

            $i = 2;
            foreach ($content_vod_stats_zoomed_view as $value) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3])
                        ->setCellValue('E' . $i, $value[4])
                        ->setCellValue('F' . $i, $value[5])
                        ->setCellValue('G' . $i, $value[6]);
                $i++;
            }

            if (count($content_vod_stats_total) > 0) {
                $i++;
                foreach ($content_vod_stats_total as $value) {
                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('B' . $i, $value[1])
                            ->setCellValue('C' . $i, $value[2])
                            ->setCellValue('D' . $i, $value[3])
                            ->setCellValue('E' . $i, $value[4])
                            ->setCellValue('F' . $i, $value[5])
                            ->setCellValue('G' . $i, $value[6]);
                }
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('B' . $i, 0)
                        ->setCellValue('C' . $i, 0)
                        ->setCellValue('D' . $i, '00:00:00')
                        ->setCellValue('E' . $i, '00:00:00')
                        ->setCellValue('F' . $i, '00:00:00')
                        ->setCellValue('G' . $i, '0.00 B');
            }

            $objPHPExcel->getActiveSheet()->setTitle('Vod_Content');

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(1)
                    ->setCellValue('A1', 'Content')
                    ->setCellValue('B1', 'Hits')
                    ->setCellValue('C1', 'Viewers')
                    ->setCellValue('D1', 'Duration')
                    ->setCellValue('E1', 'Duration per Hit (average)')
                    ->setCellValue('F1', 'Duration per Viewer (average)')
                    ->setCellValue('G1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Live_Content');

            $i = 2;
            foreach ($content_live_stats_zoomed_view as $value) {
                $objPHPExcel->setActiveSheetIndex(1)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3])
                        ->setCellValue('E' . $i, $value[4])
                        ->setCellValue('F' . $i, $value[5])
                        ->setCellValue('G' . $i, $value[6]);
                $i++;
            }

            if (count($content_live_stats_total) > 0) {
                $i++;
                foreach ($content_live_stats_total as $value) {
                    $objPHPExcel->setActiveSheetIndex(1)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('B' . $i, $value[1])
                            ->setCellValue('C' . $i, $value[2])
                            ->setCellValue('D' . $i, $value[3])
                            ->setCellValue('E' . $i, $value[4])
                            ->setCellValue('F' . $i, $value[5])
                            ->setCellValue('G' . $i, $value[6]);
                }
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(1)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('B' . $i, 0)
                        ->setCellValue('C' . $i, 0)
                        ->setCellValue('D' . $i, '00:00:00')
                        ->setCellValue('E' . $i, '00:00:00')
                        ->setCellValue('F' . $i, '00:00:00')
                        ->setCellValue('G' . $i, '0.00 B');
            }

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(2)
                    ->setCellValue('A1', 'Location')
                    ->setCellValue('B1', 'Hits')
                    ->setCellValue('C1', 'Viewers')
                    ->setCellValue('D1', 'Duration')
                    ->setCellValue('E1', 'Duration per Hit (average)')
                    ->setCellValue('F1', 'Duration per Viewer (average)')
                    ->setCellValue('G1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Geographic_Locations');

            $i = 2;
            foreach ($cities_view as $value) {
                $objPHPExcel->setActiveSheetIndex(2)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3])
                        ->setCellValue('E' . $i, $value[4])
                        ->setCellValue('F' . $i, $value[5])
                        ->setCellValue('G' . $i, $value[6]);
                $i++;
            }

            $i++;
            $objPHPExcel->setActiveSheetIndex(2)
                    ->setCellValue('A' . $i, $countries_stats_total[0])
                    ->setCellValue('B' . $i, $countries_stats_total[1])
                    ->setCellValue('C' . $i, $countries_stats_total[2])
                    ->setCellValue('D' . $i, $countries_stats_total[3])
                    ->setCellValue('E' . $i, $countries_stats_total[4])
                    ->setCellValue('F' . $i, $countries_stats_total[5])
                    ->setCellValue('G' . $i, $countries_stats_total[6]);

            $filename = $cpid . '_streaming_stats_' . date('m-d-Y_H_i_s');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');
            header("Content-Type: application/force-download");
            header("Content-Type: application/download");
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {
            return false;
        }
    }

    public function get_vod_stats_zoomed($vodStatsEntries) {
        $content_vod_stats_zoomed = array();
        foreach ($vodStatsEntries as $row) {
            if (!$this->multi_array_search($row['content'], $content_vod_stats_zoomed)) {
                array_push($content_vod_stats_zoomed, $row['content']);
            }
        }

        $content_vod_stats_zoomed_view = array();
        foreach ($content_vod_stats_zoomed as $c) {
            $hits = 0;
            $viewers = 0;
            $duration = 0;
            $duration_per_hit = 0;
            $duration_per_viewer = 0;
            $data_transfer = 0;
            foreach ($vodStatsEntries as $row) {
                if ($row['content'] == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $duration += $row['duration'];
                    $duration_per_hit += $row['duration_per_hit'];
                    $duration_per_viewer += $row['duration_per_viewer'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            $duration_formated = ($duration == 0) ? '00:00:00' : $duration;
            $duration_per_hit_formated = ($duration_per_hit == 0) ? '00:00:00' : $duration_per_hit;
            $duration_per_viewer_formated = ($duration_per_viewer == 0) ? '00:00:00' : $duration_per_viewer;
            array_push($content_vod_stats_zoomed_view, array($c, $hits, $viewers, $duration_formated, $duration_per_hit_formated, $duration_per_viewer_formated, $data_transfer_formated));
        }

        return $content_vod_stats_zoomed_view;
    }

    public function get_vod_stats_total($vodStatsEntries) {
        $vod_content = array();
        foreach ($vodStatsEntries as $row) {
            $content_explode = explode("/", $row['content']);
            $content_found = $content_explode[0];
            if (!$this->multi_array_search($content_found, $vod_content)) {
                array_push($vod_content, $content_found);
            }
        }

        $content_vod_stats_view = array();
        foreach ($vod_content as $c) {
            $hits = 0;
            $viewers = 0;
            $duration = 0;
            $duration_per_hit = 0;
            $duration_per_viewer = 0;
            $data_transfer = 0;
            foreach ($vodStatsEntries as $row) {
                $content_explode = explode("/", $row['content']);
                $content_found = $content_explode[0];
                if ($content_found == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $duration += $row['duration'];
                    $duration_per_hit += $row['duration_per_hit'];
                    $duration_per_viewer += $row['duration_per_viewer'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            $duration_formated = ($duration == 0) ? '00:00:00' : $duration;
            $duration_per_hit_formated = ($duration_per_hit == 0) ? '00:00:00' : $duration_per_hit;
            $duration_per_viewer_formated = ($duration_per_viewer == 0) ? '00:00:00' : $duration_per_viewer;
            array_push($content_vod_stats_view, array('Total', $hits, $viewers, $duration_formated, $duration_per_hit_formated, $duration_per_viewer_formated, $data_transfer_formated));
        }
        return $content_vod_stats_view;
    }

    public function get_live_stats_zoomed($liveStatsEntries) {
        $content_live_stats_zoomed = array();
        foreach ($liveStatsEntries as $row) {
            if (!$this->multi_array_search($row['content'], $content_live_stats_zoomed)) {
                array_push($content_live_stats_zoomed, $row['content']);
            }
        }

        $content_live_stats_zoomed_view = array();
        foreach ($content_live_stats_zoomed as $c) {
            $hits = 0;
            $viewers = 0;
            $duration = 0;
            $duration_per_hit = 0;
            $duration_per_viewer = 0;
            $data_transfer = 0;
            foreach ($liveStatsEntries as $row) {
                if ($row['content'] == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $duration += $row['duration'];
                    $duration_per_hit += $row['duration_per_hit'];
                    $duration_per_viewer += $row['duration_per_viewer'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            $duration_formated = ($duration == 0) ? '00:00:00' : $duration;
            $duration_per_hit_formated = ($duration_per_hit == 0) ? '00:00:00' : $duration_per_hit;
            $duration_per_viewer_formated = ($duration_per_viewer == 0) ? '00:00:00' : $duration_per_viewer;
            array_push($content_live_stats_zoomed_view, array($c, $hits, $viewers, $duration_formated, $duration_per_hit_formated, $duration_per_viewer_formated, $data_transfer_formated));
        }

        return $content_live_stats_zoomed_view;
    }

    public function get_live_stats_total($liveStatsEntries) {
        $live_content = array();
        foreach ($liveStatsEntries as $row) {
            $content_explode = explode("/", $row['content']);
            $content_found = $content_explode[0];
            if (!$this->multi_array_search($content_found, $live_content)) {
                array_push($live_content, $content_found);
            }
        }

        $content_live_stats_view = array();
        foreach ($live_content as $c) {
            $hits = 0;
            $viewers = 0;
            $duration = 0;
            $duration_per_hit = 0;
            $duration_per_viewer = 0;
            $data_transfer = 0;
            foreach ($liveStatsEntries as $row) {
                $content_explode = explode("/", $row['content']);
                $content_found = $content_explode[0];
                if ($content_found == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $duration += $row['duration'];
                    $duration_per_hit += $row['duration_per_hit'];
                    $duration_per_viewer += $row['duration_per_viewer'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            $duration_formated = ($duration == 0) ? '00:00:00' : $duration;
            $duration_per_hit_formated = ($duration_per_hit == 0) ? '00:00:00' : $duration_per_hit;
            $duration_per_viewer_formated = ($duration_per_viewer == 0) ? '00:00:00' : $duration_per_viewer;
            array_push($content_live_stats_view, array('Total', $hits, $viewers, $duration_formated, $duration_per_hit_formated, $duration_per_viewer_formated, $data_transfer_formated));
        }

        return $content_live_stats_view;
    }

    public function get_cities_view($locationEntries) {
        $cities = array();
        foreach ($locationEntries as $row) {
            if (!$this->multi_array_search($row['location'], $cities)) {
                array_push($cities, $row['location']);
            }
        }

        $cities_view = array();
        foreach ($cities as $city) {
            $hits = 0;
            $viewers = 0;
            $duration = 0;
            $duration_per_hit = 0;
            $duration_per_viewer = 0;
            $data_transfer = 0;
            foreach ($locationEntries as $row) {
                if ($row['location'] == $city) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $duration += $row['duration'];
                    $duration_per_hit += $row['duration_per_hit'];
                    $duration_per_viewer += $row['duration_per_viewer'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            $duration_formated = ($duration == 0) ? '00:00:00' : $duration;
            $duration_per_hit_formated = ($duration_per_hit == 0) ? '00:00:00' : $duration_per_hit;
            $duration_per_viewer_formated = ($duration_per_viewer == 0) ? '00:00:00' : $duration_per_viewer;
            array_push($cities_view, array($city, $hits, $viewers, $duration_formated, $duration_per_hit_formated, $duration_per_viewer_formated, $data_transfer_formated));
        }

        return $cities_view;
    }

    public function get_countries_total($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $duration = 0;
        $duration_per_hit = 0;
        $duration_per_viewer = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row['hits'];
            $viewers += $row['viewers'];
            $duration += $row['duration'];
            $duration_per_hit += $row['duration_per_hit'];
            $duration_per_viewer += $row['duration_per_viewer'];
            $data_transfer += $row['data_transfer'];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        $duration_formated = ($duration == 0) ? '00:00:00' : $duration;
        $duration_per_hit_formated = ($duration_per_hit == 0) ? '00:00:00' : $duration_per_hit;
        $duration_per_viewer_formated = ($duration_per_viewer == 0) ? '00:00:00' : $duration_per_viewer;
        array_push($locationsTotal, 'Total', $hits, $viewers, $duration_formated, $duration_per_hit_formated, $duration_per_viewer_formated, $data_transfer_formated);
        return $locationsTotal;
    }

    public function getLocations($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('locations')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $locationEntries = $query->result_array();

        return $locationEntries;
    }

    public function getLiveStats($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('content_live_stats')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $liveStatsEntries = $query->result_array();

        return $liveStatsEntries;
    }

    public function getVodStats($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('content_vod_stats')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        if ($query !== FALSE && $query->num_rows() > 0) {
            $vodStatsEntries = $query->result_array();
        } else {
            $vodStatsEntries = array();
        }

        return $vodStatsEntries;
    }

    public function multi_array_search($search_for, $search_in) {
        foreach ($search_in as $element) {
            if (($element === $search_for)) {
                return true;
            } elseif (is_array($element)) {
                $result = $this->multi_array_search($search_for, $element);
                if ($result == true)
                    return true;
            }
        }
        return false;
    }

    public function human_filesize($bytes, $decimals = 2) {
        $bytes_temp = $bytes;
        $labels = array('B', 'KB', 'MB', 'GB', 'TB');

        foreach ($labels as $label) {
            if ($bytes > 1024) {
                $bytes = $bytes / 1024;
            } else {
                break;
            }
        }

        $bytes_temp2 = number_format($bytes_temp);
        $bytes_temp3 = floatval(str_replace(",", ".", $bytes_temp2));
        return number_format($bytes_temp3, 2) . " " . $label;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

}

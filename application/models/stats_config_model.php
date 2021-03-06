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
            $child_vod_stats_total = array();
            $child_live_stats_total = array();
            $child_states_total = array();
            $child_cities_total = array();

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Account')
                    ->setCellValue('B1', 'Content')
                    ->setCellValue('C1', 'Hits')
                    ->setCellValue('D1', 'Viewers')
                    ->setCellValue('E1', 'Data Transfer');
            $i = 2;
            foreach ($childIds['childIds'] as $child) {
                $vodStatsEntries = $this->getVodStats($child, $start_date, $end_date);
                $content_vod_stats_zoomed_view = $this->get_vod_stats_zoomed($vodStatsEntries);
                $content_vod_stats_total = $this->get_child_vod_stats($vodStatsEntries);

                array_push($child_vod_stats_total, array($content_vod_stats_total[0][1], $content_vod_stats_total[0][2], $content_vod_stats_total[0][3]));

                foreach ($content_vod_stats_zoomed_view as $value) {
                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A' . $i, $child)
                            ->setCellValue('B' . $i, $value[0])
                            ->setCellValue('C' . $i, $value[1])
                            ->setCellValue('D' . $i, $value[2])
                            ->setCellValue('E' . $i, $value[3]);
                    $i++;
                }
            }

            $child_vod_stats_total_response = $this->get_child_vod_stats_total($child_vod_stats_total);
            if (count($child_vod_stats_total_response) > 0) {
                $i++;
                foreach ($child_vod_stats_total_response as $value) {
                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('C' . $i, $value[1])
                            ->setCellValue('D' . $i, $value[2])
                            ->setCellValue('E' . $i, $value[3]);
                }
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('C' . $i, 0)
                        ->setCellValue('D' . $i, 0)
                        ->setCellValue('E' . $i, '0.00 B');
            }
            $objPHPExcel->getActiveSheet()->setTitle('Vod_Content');


            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(1)
                    ->setCellValue('A1', 'Account')
                    ->setCellValue('B1', 'Content')
                    ->setCellValue('C1', 'Hits')
                    ->setCellValue('D1', 'Viewers')
                    ->setCellValue('E1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Live_Content');

            $i = 2;
            foreach ($childIds['childIds'] as $child) {
                $liveStatsEntries = $this->getLiveStats($child, $start_date, $end_date);
                $content_live_stats_zoomed_view = $this->get_live_stats_zoomed($liveStatsEntries);
                $content_live_stats_total = $this->get_child_live_stats($liveStatsEntries);
                array_push($child_live_stats_total, array($content_live_stats_total[0][1], $content_live_stats_total[0][2], $content_live_stats_total[0][3]));

                foreach ($content_live_stats_zoomed_view as $value) {
                    $objPHPExcel->setActiveSheetIndex(1)
                            ->setCellValue('A' . $i, $child)
                            ->setCellValue('B' . $i, $value[0])
                            ->setCellValue('C' . $i, $value[1])
                            ->setCellValue('D' . $i, $value[2])
                            ->setCellValue('E' . $i, $value[3]);
                    $i++;
                }
            }

            $child_live_stats_total_response = $this->get_child_live_stats_total($child_live_stats_total);

            if (count($child_live_stats_total_response) > 0) {
                $i++;
                foreach ($child_live_stats_total_response as $value) {
                    $objPHPExcel->setActiveSheetIndex(1)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('C' . $i, $value[1])
                            ->setCellValue('D' . $i, $value[2])
                            ->setCellValue('E' . $i, $value[3]);
                }
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(1)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('C' . $i, 0)
                        ->setCellValue('D' . $i, 0)
                        ->setCellValue('E' . $i, '0.00 B');
            }


            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(2)
                    ->setCellValue('A1', 'Account')
                    ->setCellValue('B1', 'Region')
                    ->setCellValue('C1', 'Country')
                    ->setCellValue('D1', 'Hits')
                    ->setCellValue('E1', 'Viewers')
                    ->setCellValue('F1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Geographic_Locations_Regions');

            $i = 2;
            foreach ($childIds['childIds'] as $child) {
                $locationStatesEntries = $this->getLocationsStates($child, $start_date, $end_date);
                $states_view = $this->get_states_view($locationStatesEntries);
                $states_stats_total = $this->get_child_states($locationStatesEntries);
                array_push($child_states_total, array($states_stats_total[1], $states_stats_total[2], $states_stats_total[3]));

                foreach ($states_view as $value) {
                    $objPHPExcel->setActiveSheetIndex(2)
                            ->setCellValue('A' . $i, $child)
                            ->setCellValue('B' . $i, $value[0])
                            ->setCellValue('C' . $i, $value[1])
                            ->setCellValue('D' . $i, $value[2])
                            ->setCellValue('E' . $i, $value[3])
                            ->setCellValue('F' . $i, $value[4]);
                    $i++;
                }
            }

            $child_states_stats_total_response = $this->get_child_states_stats_total($child_states_total);
            if (count($child_states_stats_total_response) > 0) {
                $i++;
                $objPHPExcel->setActiveSheetIndex(2)
                        ->setCellValue('A' . $i, $child_states_stats_total_response[0])
                        ->setCellValue('D' . $i, $child_states_stats_total_response[1])
                        ->setCellValue('E' . $i, $child_states_stats_total_response[2])
                        ->setCellValue('F' . $i, $child_states_stats_total_response[3]);
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(2)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('D' . $i, 0)
                        ->setCellValue('E' . $i, 0)
                        ->setCellValue('F' . $i, '0.00 B');
            }

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(3)
                    ->setCellValue('A1', 'Account')
                    ->setCellValue('B1', 'City')
                    ->setCellValue('C1', 'Country')
                    ->setCellValue('D1', 'Hits')
                    ->setCellValue('E1', 'Viewers')
                    ->setCellValue('F1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Geographic_Locations_Cities');

            $i = 2;
            foreach ($childIds['childIds'] as $child) {
                $locationCitiesEntries = $this->getLocationsCities($child, $start_date, $end_date);
                $cities_view = $this->get_cities_view($locationCitiesEntries);
                $cities_stats_total = $this->get_child_cities($locationCitiesEntries);
                array_push($child_cities_total, array($cities_stats_total[1], $cities_stats_total[2], $cities_stats_total[3]));

                foreach ($cities_view as $value) {
                    $objPHPExcel->setActiveSheetIndex(3)
                            ->setCellValue('A' . $i, $child)
                            ->setCellValue('B' . $i, $value[0])
                            ->setCellValue('C' . $i, $value[1])
                            ->setCellValue('D' . $i, $value[2])
                            ->setCellValue('E' . $i, $value[3])
                            ->setCellValue('F' . $i, $value[4]);
                    $i++;
                }
            }

            $child_cities_stats_total_response = $this->get_child_cities_stats_total($child_cities_total);
            if (count($child_cities_stats_total_response) > 0) {
                $i++;
                $objPHPExcel->setActiveSheetIndex(3)
                        ->setCellValue('A' . $i, $child_cities_stats_total_response[0])
                        ->setCellValue('D' . $i, $child_cities_stats_total_response[1])
                        ->setCellValue('E' . $i, $child_cities_stats_total_response[2])
                        ->setCellValue('F' . $i, $child_cities_stats_total_response[3]);
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(3)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('D' . $i, 0)
                        ->setCellValue('E' . $i, 0)
                        ->setCellValue('F' . $i, '0.00 B');
            }

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
            $locationStatesEntries = $this->getLocationsStates($cpid, $start_date, $end_date);
            $locationCitiesEntries = $this->getLocationsCities($cpid, $start_date, $end_date);

            $content_vod_stats_zoomed_view = $this->get_vod_stats_zoomed($vodStatsEntries);
            $content_vod_stats_total = $this->get_vod_stats_total($vodStatsEntries);

            $content_live_stats_zoomed_view = $this->get_live_stats_zoomed($liveStatsEntries);
            $content_live_stats_total = $this->get_live_stats_total($liveStatsEntries);

            $states_view = $this->get_states_view($locationStatesEntries);
            syslog(LOG_NOTICE, "SMH DEBUG : get_child_stats: " . print_r($states_view, true));
            $countries_stats_total = $this->get_states_total($locationStatesEntries);

            $cities_view = $this->get_cities_view($locationCitiesEntries);
            $countries_cities_total = $this->get_cities_total($locationCitiesEntries);

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Content')
                    ->setCellValue('B1', 'Hits')
                    ->setCellValue('C1', 'Viewers')
                    ->setCellValue('D1', 'Data Transfer');

            $i = 2;
            foreach ($content_vod_stats_zoomed_view as $value) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3]);
                $i++;
            }

            if (count($content_vod_stats_total) > 0) {
                $i++;
                foreach ($content_vod_stats_total as $value) {
                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('B' . $i, $value[1])
                            ->setCellValue('C' . $i, $value[2])
                            ->setCellValue('D' . $i, $value[3]);
                }
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('B' . $i, 0)
                        ->setCellValue('C' . $i, 0)
                        ->setCellValue('D' . $i, '0.00 B');
            }

            $objPHPExcel->getActiveSheet()->setTitle('Vod_Content');

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(1)
                    ->setCellValue('A1', 'Content')
                    ->setCellValue('B1', 'Hits')
                    ->setCellValue('C1', 'Viewers')
                    ->setCellValue('D1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Live_Content');

            $i = 2;
            foreach ($content_live_stats_zoomed_view as $value) {
                $objPHPExcel->setActiveSheetIndex(1)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3]);
                $i++;
            }

            if (count($content_live_stats_total) > 0) {
                $i++;
                foreach ($content_live_stats_total as $value) {
                    $objPHPExcel->setActiveSheetIndex(1)
                            ->setCellValue('A' . $i, $value[0])
                            ->setCellValue('B' . $i, $value[1])
                            ->setCellValue('C' . $i, $value[2])
                            ->setCellValue('D' . $i, $value[3]);
                }
            } else {
                $i++;
                $objPHPExcel->setActiveSheetIndex(1)
                        ->setCellValue('A' . $i, 'Total')
                        ->setCellValue('B' . $i, 0)
                        ->setCellValue('C' . $i, 0)
                        ->setCellValue('D' . $i, '0.00 B');
            }

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(2)
                    ->setCellValue('A1', 'Region')
                    ->setCellValue('B1', 'Country')
                    ->setCellValue('C1', 'Hits')
                    ->setCellValue('D1', 'Viewers')
                    ->setCellValue('E1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Geographic_Locations_Regions');

            $i = 2;
            foreach ($states_view as $value) {
                $objPHPExcel->setActiveSheetIndex(2)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3])
                        ->setCellValue('E' . $i, $value[4]);
                $i++;
            }

            $i++;
            $objPHPExcel->setActiveSheetIndex(2)
                    ->setCellValue('A' . $i, $countries_stats_total[0])
                    ->setCellValue('C' . $i, $countries_stats_total[1])
                    ->setCellValue('D' . $i, $countries_stats_total[2])
                    ->setCellValue('E' . $i, $countries_stats_total[3]);

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(3)
                    ->setCellValue('A1', 'City')
                    ->setCellValue('B1', 'Country')
                    ->setCellValue('C1', 'Hits')
                    ->setCellValue('D1', 'Viewers')
                    ->setCellValue('E1', 'Data Transfer');
            $objPHPExcel->getActiveSheet()->setTitle('Geographic_Locations_Cities');

            $i = 2;
            foreach ($cities_view as $value) {
                $objPHPExcel->setActiveSheetIndex(3)
                        ->setCellValue('A' . $i, $value[0])
                        ->setCellValue('B' . $i, $value[1])
                        ->setCellValue('C' . $i, $value[2])
                        ->setCellValue('D' . $i, $value[3])
                        ->setCellValue('E' . $i, $value[4]);
                $i++;
            }

            $i++;
            $objPHPExcel->setActiveSheetIndex(3)
                    ->setCellValue('A' . $i, $countries_cities_total[0])
                    ->setCellValue('C' . $i, $countries_cities_total[1])
                    ->setCellValue('D' . $i, $countries_cities_total[2])
                    ->setCellValue('E' . $i, $countries_cities_total[3]);

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
            $data_transfer = 0;
            foreach ($vodStatsEntries as $row) {
                if ($row['content'] == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            array_push($content_vod_stats_zoomed_view, array($c, $hits, $viewers, $data_transfer_formated));
        }

        return $content_vod_stats_zoomed_view;
    }

    public function get_child_live_stats($liveStatsEntries) {
        $vod_content = array();
        foreach ($liveStatsEntries as $row) {
            $content_explode = explode("/", $row['content']);
            $content_found = $content_explode[0];
            if (!$this->multi_array_search($content_found, $vod_content)) {
                array_push($vod_content, $content_found);
            }
        }

        $content_live_stats_view = array();
        foreach ($vod_content as $c) {
            $hits = 0;
            $viewers = 0;
            $data_transfer = 0;
            foreach ($liveStatsEntries as $row) {
                $content_explode = explode("/", $row['content']);
                $content_found = $content_explode[0];
                if ($content_found == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            array_push($content_live_stats_view, array('Total', $hits, $viewers, $data_transfer));
        }
        return $content_live_stats_view;
    }

    public function get_child_live_stats_total($vodStatsEntries) {
        $content_live_stats_view = array();
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        foreach ($vodStatsEntries as $row) {
            $hits += $row[0];
            $viewers += $row[1];
            $data_transfer += $row[2];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        array_push($content_live_stats_view, array('Total', $hits, $viewers, $data_transfer_formated));
        return $content_live_stats_view;
    }

    public function get_child_vod_stats_total($vodStatsEntries) {
        $content_vod_stats_view = array();
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        foreach ($vodStatsEntries as $row) {
            $hits += $row[0];
            $viewers += $row[1];
            $data_transfer += $row[2];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        array_push($content_vod_stats_view, array('Total', $hits, $viewers, $data_transfer_formated));
        return $content_vod_stats_view;
    }

    public function get_child_vod_stats($vodStatsEntries) {
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
            $data_transfer = 0;
            foreach ($vodStatsEntries as $row) {
                $content_explode = explode("/", $row['content']);
                $content_found = $content_explode[0];
                if ($content_found == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            array_push($content_vod_stats_view, array('Total', $hits, $viewers, $data_transfer));
        }
        return $content_vod_stats_view;
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
            $data_transfer = 0;
            foreach ($vodStatsEntries as $row) {
                $content_explode = explode("/", $row['content']);
                $content_found = $content_explode[0];
                if ($content_found == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            array_push($content_vod_stats_view, array('Total', $hits, $viewers, $data_transfer_formated));
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
            $data_transfer = 0;
            foreach ($liveStatsEntries as $row) {
                if ($row['content'] == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            array_push($content_live_stats_zoomed_view, array($c, $hits, $viewers, $data_transfer_formated));
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
            $data_transfer = 0;
            foreach ($liveStatsEntries as $row) {
                $content_explode = explode("/", $row['content']);
                $content_found = $content_explode[0];
                if ($content_found == $c) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            array_push($content_live_stats_view, array('Total', $hits, $viewers, $data_transfer_formated));
        }

        return $content_live_stats_view;
    }

    public function get_states_view($locationEntries) {
        $states = array();
        foreach ($locationEntries as $row) {
            if (!$this->multi_array_search($row['region'] . "/" . $row['country'], $states)) {
                array_push($states, $row['region'] . "/" . $row['country']);
            }
        }

        $states_view = array();
        foreach ($states as $state) {
            $hits = 0;
            $viewers = 0;
            $data_transfer = 0;
            foreach ($locationEntries as $row) {
                if (($row['region'] . "/" . $row['country']) == $state) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            $state_explode = explode("/", $state);
            array_push($states_view, array($state_explode[0], $state_explode[1], $hits, $viewers, $data_transfer_formated));
        }

        return $states_view;
    }

    public function get_states_total($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row['hits'];
            $viewers += $row['viewers'];
            $data_transfer += $row['data_transfer'];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        array_push($locationsTotal, 'Total', $hits, $viewers, $data_transfer_formated);
        return $locationsTotal;
    }

    public function get_cities_view($locationEntries) {
        $cities = array();
        foreach ($locationEntries as $row) {
            if (!$this->multi_array_search($row['city'], $cities)) {
                array_push($cities, array($row['city'], $row['country']));
            }
        }

        $cities_view = array();
        foreach ($cities as $city) {
            $hits = 0;
            $viewers = 0;
            $data_transfer = 0;
            foreach ($locationEntries as $row) {
                if ($row['city'] == $city[0]) {
                    $hits += $row['hits'];
                    $viewers += $row['viewers'];
                    $data_transfer += $row['data_transfer'];
                }
            }
            $data_transfer_formated = $this->human_filesize($data_transfer);
            array_push($cities_view, array($city[0], $city[1], $hits, $viewers, $data_transfer_formated));
        }

        return $cities_view;
    }

    public function get_cities_total($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row['hits'];
            $viewers += $row['viewers'];
            $data_transfer += $row['data_transfer'];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        array_push($locationsTotal, 'Total', $hits, $viewers, $data_transfer_formated);
        return $locationsTotal;
    }

    public function get_child_states($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row['hits'];
            $viewers += $row['viewers'];
            $data_transfer += $row['data_transfer'];
        }
        array_push($locationsTotal, 'Total', $hits, $viewers, $data_transfer);
        return $locationsTotal;
    }

    public function get_child_states_stats_total($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row[0];
            $viewers += $row[1];
            $data_transfer += $row[2];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        array_push($locationsTotal, 'Total', $hits, $viewers, $data_transfer_formated);
        return $locationsTotal;
    }

    public function get_child_cities($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row['hits'];
            $viewers += $row['viewers'];
            $data_transfer += $row['data_transfer'];
        }
        array_push($locationsTotal, 'Total', $hits, $viewers, $data_transfer);
        return $locationsTotal;
    }

    public function get_child_cities_stats_total($locationEntries) {
        $hits = 0;
        $viewers = 0;
        $data_transfer = 0;
        $locationsTotal = array();
        foreach ($locationEntries as $row) {
            $hits += $row[0];
            $viewers += $row[1];
            $data_transfer += $row[2];
        }
        $data_transfer_formated = $this->human_filesize($data_transfer);
        array_push($locationsTotal, 'Total', $hits, $viewers, $data_transfer_formated);
        return $locationsTotal;
    }

    public function getLocationsStates($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('locations_state')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $locationEntries = $query->result_array();

        return $locationEntries;
    }

    public function getLocationsCities($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('locations_city')
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
            if (($bytes / 1024) > 0.9) {
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
